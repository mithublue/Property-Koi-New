<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentNote;
use App\Models\AgentProfile;
use App\Models\User;
use App\Services\GoogleSheetSyncService;
use App\Services\MagicLoginService;
use App\Services\MessageTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role', 'all');
        $query = User::with(['agentProfile', 'properties'])->whereIn('role', ['agent', 'owner']);

        if ($role !== 'all') {
            $query->where('role', $role);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Ensure every user has a profile & magic token
        foreach ($users as $user) {
            if (!$user->agentProfile) {
                AgentProfile::create(['user_id' => $user->id]);
                $user->load('agentProfile');
            }
            if (empty($user->magic_token)) {
                MagicLoginService::getOrCreateToken($user);
            }
        }

        $stats = [
            'total_agents' => User::where('role', 'agent')->count(),
            'total_owners' => User::where('role', 'owner')->count(),
            'verified'     => AgentProfile::where('is_verified_direct_agent', true)->count(),
            'penalized'    => AgentProfile::whereIn('penalty_status', ['penalty_spam', 'bypassed_warning'])->count(),
        ];

        return view('admin.agents', compact('users', 'stats', 'role'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $cleanDigits = preg_replace('/[^0-9]/', '', GoogleSheetSyncService::toEnNumber($q));

        $agents = User::with('agentProfile')
            ->whereIn('role', ['agent', 'owner'])
            ->where(function ($query) use ($q, $cleanDigits) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('whatsapp_number', 'like', "%{$q}%")
                    ->orWhereHas('agentProfile', function ($p) use ($q) {
                        $p->where('agency_name', 'like', "%{$q}%");
                    });

                if (!empty($cleanDigits) && strlen($cleanDigits) >= 3) {
                    $query->orWhere('phone', 'like', "%{$cleanDigits}%")
                          ->orWhere('whatsapp_number', 'like', "%{$cleanDigits}%");
                }
            })
            ->limit(15)
            ->get()
            ->map(function ($user) {
                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'phone'           => $user->phone,
                    'whatsapp_number' => $user->whatsapp_number ?: $user->phone,
                    'role'            => $user->role,
                    'agency_name'     => $user->agentProfile?->agency_name ?? '',
                ];
            });

        return response()->json($agents);
    }

    public function updateProfile(Request $request, User $user)
    {
        $profile = $user->agentProfile ?: new AgentProfile(['user_id' => $user->id]);

        if ($request->has('rating')) {
            $profile->admin_fixed_rating = $request->input('rating');
        }

        if ($request->has('penalty_status')) {
            $profile->penalty_status = $request->input('penalty_status');
            $profile->penalty_reason = $request->input('penalty_reason');
        }

        if ($request->has('is_verified_direct_agent')) {
            $profile->is_verified_direct_agent = $request->boolean('is_verified_direct_agent');
            if ($profile->is_verified_direct_agent && !$profile->verified_at) {
                $profile->verified_at = now();
            }
        }

        $profile->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'প্রোফাইল আপডেট সফল হয়েছে।']);
        }

        return back()->with('success', 'প্রোফাইল তথ্য আপডেট করা হয়েছে।');
    }

    public function addNote(Request $request, User $user)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        AgentNote::create([
            'user_id'        => $user->id,
            'author_user_id' => auth()->id(),
            'author_name'    => auth()->user()?->name ?? 'Admin',
            'note_text'      => $validated['note'],
        ]);

        return back()->with('success', 'নোট সফলভাবে সংরক্ষণ করা হয়েছে।');
    }

    public function regenerateMagicToken(User $user)
    {
        $token = MagicLoginService::regenerateToken($user);
        $url = MagicLoginService::getMagicLoginUrl($user);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'url'     => $url,
            'message' => 'নতুন ম্যাজিক লিঙ্ক তৈরি করা হয়েছে!'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'phone'                    => 'required|string|max:50|unique:users,phone',
            'whatsapp_number'          => 'nullable|string|max:50',
            'email'                    => 'nullable|email|max:255|unique:users,email',
            'role'                     => 'required|in:agent,owner',
            'agency_name'              => 'nullable|string|max:255',
            'admin_fixed_rating'       => 'nullable|numeric|min:1|max:5',
            'is_verified_direct_agent' => 'nullable|boolean',
            'notes'                    => 'nullable|string',
        ], [
            'name.required'  => 'সদস্যের নাম আবশ্যক।',
            'phone.required' => 'ফোন নম্বর আবশ্যক।',
            'phone.unique'   => 'এই ফোন নম্বরের সদস্য ইতিমধ্যে নিবন্ধিত।',
            'email.unique'   => 'এই ইমেইলটি অন্য অ্যাকাউন্টে ব্যবহৃত হচ্ছে।',
        ]);

        $rawPhone = preg_replace('/[^0-9]/', '', $validated['phone']);
        $whatsapp = !empty($validated['whatsapp_number'])
            ? preg_replace('/[^0-9]/', '', $validated['whatsapp_number'])
            : $rawPhone;

        $user = User::create([
            'name'            => $validated['name'],
            'phone'           => $validated['phone'],
            'whatsapp_number' => $whatsapp,
            'email'           => $validated['email'] ?: ($validated['role'] . '_' . Str::random(6) . '@propertykoi.local'),
            'password'        => Hash::make(Str::random(16)),
            'role'            => $validated['role'],
            'is_verified'     => true,
        ]);

        MagicLoginService::getOrCreateToken($user);

        AgentProfile::create([
            'user_id'                  => $user->id,
            'agency_name'              => $request->input('agency_name'),
            'admin_fixed_rating'       => $validated['admin_fixed_rating'] ?? 5.0,
            'is_verified_direct_agent' => $request->boolean('is_verified_direct_agent', true),
            'verified_at'              => now(),
        ]);

        if (!empty($validated['notes'])) {
            AgentNote::create([
                'user_id'        => $user->id,
                'author_user_id' => auth()->id(),
                'author_name'    => auth()->user()?->name ?? 'Admin',
                'note_text'      => $validated['notes'],
            ]);
        }

        $label = $validated['role'] === 'agent' ? 'এজেন্ট' : 'মালিক';
        return redirect()->route('admin.agents.index')->with('success', "নতুন {$label} {$user->name} সফলভাবে যুক্ত করা হয়েছে!");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'phone'                    => 'required|string|max:50|unique:users,phone,' . $user->id,
            'whatsapp_number'          => 'nullable|string|max:50',
            'email'                    => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'role'                     => 'required|in:agent,owner',
            'agency_name'              => 'nullable|string|max:255',
            'admin_fixed_rating'       => 'nullable|numeric|min:1|max:5',
            'is_verified_direct_agent' => 'nullable|boolean',
            'penalty_status'           => 'nullable|in:verified,unverified,bypassed_warning,penalty_spam',
            'penalty_reason'           => 'nullable|string|max:255',
        ], [
            'name.required'  => 'সদস্যের নাম আবশ্যক।',
            'phone.required' => 'ফোন নম্বর আবশ্যক।',
            'phone.unique'   => 'এই ফোন নম্বরের সদস্য ইতিমধ্যে নিবন্ধিত।',
            'email.unique'   => 'এই ইমেইলটি অন্য অ্যাকাউন্টে ব্যবহৃত হচ্ছে।',
        ]);

        $rawPhone = preg_replace('/[^0-9]/', '', $validated['phone']);
        $whatsapp = !empty($validated['whatsapp_number'])
            ? preg_replace('/[^0-9]/', '', $validated['whatsapp_number'])
            : $rawPhone;

        $user->update([
            'name'            => $validated['name'],
            'phone'           => $validated['phone'],
            'whatsapp_number' => $whatsapp,
            'email'           => $validated['email'] ?: $user->email,
            'role'            => $validated['role'],
        ]);

        $profile = $user->agentProfile ?: new AgentProfile(['user_id' => $user->id]);
        $profile->agency_name = $request->input('agency_name');
        if ($request->filled('admin_fixed_rating')) {
            $profile->admin_fixed_rating = $validated['admin_fixed_rating'];
        }
        $profile->is_verified_direct_agent = $request->boolean('is_verified_direct_agent');
        if ($profile->is_verified_direct_agent && !$profile->verified_at) {
            $profile->verified_at = now();
        }
        if ($request->filled('penalty_status')) {
            $profile->penalty_status = $request->input('penalty_status');
            $profile->penalty_reason = $request->input('penalty_reason');
        }
        $profile->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'সদস্যের তথ্য সফলভাবে আপডেট করা হয়েছে।',
                'user'    => $user->load('agentProfile'),
            ]);
        }

        $label = $user->role === 'agent' ? 'এজেন্ট' : 'মালিক';
        return redirect()->route('admin.agents.index')->with('success', "{$label} {$user->name}-এর তথ্য সফলভাবে আপডেট করা হয়েছে!");
    }
}
