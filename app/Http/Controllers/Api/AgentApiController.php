<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentNote;
use App\Models\AgentProfile;
use App\Models\User;
use App\Services\GoogleSheetSyncService;
use App\Services\MagicLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentApiController extends Controller
{
    /**
     * List agents and owners.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('agentProfile')->whereIn('role', ['agent', 'owner']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $cleanDigits = preg_replace('/[^0-9]/', '', GoogleSheetSyncService::toEnNumber($q));

            $query->where(function ($sub) use ($q, $cleanDigits) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('whatsapp_number', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhereHas('agentProfile', function ($p) use ($q) {
                        $p->where('agency_name', 'like', "%{$q}%")
                          ->orWhere('license_no', 'like', "%{$q}%");
                    });

                if (!empty($cleanDigits) && strlen($cleanDigits) >= 4) {
                    $sub->orWhere('phone', 'like', "%{$cleanDigits}%")
                        ->orWhere('whatsapp_number', 'like', "%{$cleanDigits}%");
                }
            });
        }

        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));
        $agents = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'total'   => $agents->total(),
            'data'    => $agents->map(fn (User $u) => $this->formatAgent($u)),
            'pagination' => [
                'current_page' => $agents->currentPage(),
                'last_page'    => $agents->lastPage(),
                'per_page'     => $agents->perPage(),
            ],
        ]);
    }

    /**
     * Create or update an agent/owner via JSON API.
     * Fully compatible with standard Agent JSON Schema.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'phone'                      => 'required|string|max:60',
            'whatsapp'                   => 'nullable|string|max:60',
            'whatsapp_number'            => 'nullable|string|max:60',
            'email'                      => 'nullable|email|max:255',
            'role'                       => 'nullable|string|in:agent,owner',
            'agency_name'                => 'nullable|string|max:255',
            'agency'                     => 'nullable|string|max:255',
            'license_no'                 => 'nullable|string|max:100',
            'experience_years'           => 'nullable|numeric|min:0|max:80',
            'direct_owner_network_count' => 'nullable|numeric|min:0',
            'rating'                     => 'nullable|numeric|min:1|max:5',
            'admin_fixed_rating'         => 'nullable|numeric|min:1|max:5',
            'is_verified'                => 'nullable',
            'is_verified_direct_agent'   => 'nullable',
            'notes'                      => 'nullable|string',
            'verification_notes'         => 'nullable|string',
        ]);

        $rawPhone = trim((string) $validated['phone']);
        $cleanPhoneDigits = preg_replace('/[^0-9]/', '', GoogleSheetSyncService::toEnNumber($rawPhone));

        if (empty($cleanPhoneDigits)) {
            return response()->json([
                'success' => false,
                'message' => 'সঠিক ফোন নম্বর প্রদান করা আবশ্যক।',
            ], 422);
        }

        $whatsapp = $validated['whatsapp_number'] ?? ($validated['whatsapp'] ?? $rawPhone);
        $role = $validated['role'] ?? 'agent';
        $agencyName = $validated['agency_name'] ?? ($validated['agency'] ?? null);

        // Find existing user by exact phone or matching digits
        $user = User::where('phone', $rawPhone)
            ->orWhere('phone', 'like', "%{$cleanPhoneDigits}%")
            ->orWhere('whatsapp_number', 'like', "%{$cleanPhoneDigits}%")
            ->first();

        $isNew = false;

        if (!$user) {
            $isNew = true;
            $email = !empty($validated['email'])
                ? trim($validated['email'])
                : ($role . '_' . Str::random(6) . '@propertykoi.local');

            // Ensure email uniqueness
            if (User::where('email', $email)->exists()) {
                $email = $role . '_' . Str::random(8) . '@propertykoi.local';
            }

            $user = User::create([
                'name'            => trim($validated['name']),
                'phone'           => $rawPhone,
                'whatsapp_number' => $whatsapp,
                'email'           => $email,
                'password'        => Hash::make(Str::random(16)),
                'role'            => $role,
                'is_verified'     => true,
            ]);

            MagicLoginService::getOrCreateToken($user);
        } else {
            // Update fields if provided
            $updates = [];
            if (!empty($validated['name']) && (empty($user->name) || str_starts_with($user->name, 'Agent '))) {
                $updates['name'] = trim($validated['name']);
            }
            if (!empty($whatsapp) && empty($user->whatsapp_number)) {
                $updates['whatsapp_number'] = $whatsapp;
            }
            if (!empty($validated['email']) && (empty($user->email) || str_ends_with($user->email, '@propertykoi.local'))) {
                $updates['email'] = trim($validated['email']);
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
        }

        // Agent Profile Resolution
        $profile = $user->agentProfile ?: new AgentProfile(['user_id' => $user->id]);
        if (!empty($agencyName)) {
            $profile->agency_name = $agencyName;
        }
        if (!empty($validated['license_no'])) {
            $profile->license_no = trim($validated['license_no']);
        }
        if (isset($validated['experience_years'])) {
            $profile->experience_years = (int) $validated['experience_years'];
        }
        if (isset($validated['direct_owner_network_count'])) {
            $profile->direct_owner_network_count = (int) $validated['direct_owner_network_count'];
        }

        $ratingVal = $validated['admin_fixed_rating'] ?? ($validated['rating'] ?? null);
        if ($ratingVal !== null) {
            $profile->admin_fixed_rating = (float) $ratingVal;
            $profile->rating = (float) $ratingVal;
        }

        $verifiedVal = $validated['is_verified_direct_agent'] ?? ($validated['is_verified'] ?? null);
        if ($verifiedVal !== null) {
            $isVerified = filter_var($verifiedVal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            $profile->is_verified_direct_agent = $isVerified;
            if ($isVerified && !$profile->verified_at) {
                $profile->verified_at = now();
            }
        } elseif ($isNew) {
            $profile->is_verified_direct_agent = true;
            $profile->verified_at = now();
            $profile->admin_fixed_rating = 5.0;
        }

        $verNotes = $validated['verification_notes'] ?? ($validated['notes'] ?? null);
        if (!empty($verNotes)) {
            $profile->verification_notes = trim($verNotes);
        }

        $profile->save();

        // Optional Internal Note
        if (!empty($validated['notes'])) {
            AgentNote::create([
                'user_id'        => $user->id,
                'author_user_id' => auth()->id(),
                'author_name'    => 'API Client / JSON Schema',
                'note_text'      => trim($validated['notes']),
            ]);
        }

        return response()->json([
            'success' => true,
            'is_new'  => $isNew,
            'message' => $isNew ? 'এজেন্ট প্রোফাইল সফলভাবে তৈরি করা হয়েছে!' : 'বিদ্যমান এজেন্টের তথ্য সফলভাবে হালনাগাদ করা হয়েছে।',
            'data'    => $this->formatAgent($user->fresh()),
        ], $isNew ? 201 : 200);
    }

    /**
     * Show single agent details.
     */
    public function show(int|string $id): JsonResponse
    {
        $user = User::with(['agentProfile', 'properties' => fn ($q) => $q->latest()->limit(15)])
            ->whereIn('role', ['agent', 'owner'])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('phone', $id);
            })
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => array_merge($this->formatAgent($user), [
                'recent_properties' => $user->properties->map(fn ($p) => [
                    'id'            => $p->id,
                    'property_code' => $p->property_code,
                    'title'         => $p->title,
                    'area'          => $p->area,
                    'status'        => $p->status,
                    'created_at'    => $p->created_at?->toIso8601String(),
                ]),
            ]),
        ]);
    }

    /**
     * Format agent object.
     */
    protected function formatAgent(User $user): array
    {
        $profile = $user->agentProfile;

        return [
            'id'                         => $user->id,
            'name'                       => $user->name,
            'phone'                      => $user->phone,
            'whatsapp_number'            => $user->whatsapp_number ?: $user->phone,
            'email'                      => $user->email,
            'role'                       => $user->role,
            'agency_name'                => $profile?->agency_name,
            'license_no'                 => $profile?->license_no,
            'experience_years'           => $profile?->experience_years ?? 1,
            'direct_owner_network_count' => $profile?->direct_owner_network_count ?? 0,
            'rating'                     => $profile?->effective_rating ?? 5.0,
            'is_verified_direct_agent'   => (bool) ($profile?->is_verified_direct_agent ?? false),
            'verification_notes'         => $profile?->verification_notes,
            'properties_count'           => $user->properties()->count(),
            'created_at'                 => $user->created_at?->toIso8601String(),
        ];
    }
}
