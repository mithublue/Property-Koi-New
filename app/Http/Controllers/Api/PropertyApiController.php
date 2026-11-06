<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentProfile;
use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyDirectSource;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyShareDetail;
use App\Models\PropertyVerification;
use App\Models\User;
use App\Services\GoogleSheetSyncService;
use App\Services\MagicLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PropertyApiController extends Controller
{
    /**
     * List properties with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Property::with([
            'media',
            'directSources.sourceUser.agentProfile',
            'landDetail',
            'flatDetail',
            'shareDetail',
            'commercialDetail',
        ]);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('area') || $request->filled('place')) {
            $area = $request->area ?: $request->place;
            $query->where('area', 'like', "%{$area}%");
        }

        if ($request->filled('block')) {
            $query->where('block', $request->block);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('property_code', 'like', "%{$q}%")
                    ->orWhere('area', 'like', "%{$q}%")
                    ->orWhere('block', 'like', "%{$q}%")
                    ->orWhere('plot_number', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $perPage = min(100, max(5, (int) $request->query('per_page', 20)));
        $properties = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'total'   => $properties->total(),
            'data'    => $properties->map(fn (Property $p) => $this->formatProperty($p)),
            'pagination' => [
                'current_page' => $properties->currentPage(),
                'last_page'    => $properties->lastPage(),
                'per_page'     => $properties->perPage(),
            ],
        ]);
    }

    /**
     * Create a single property from API / Google Sheets / Gemini AI.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $property = $this->createPropertyFromPayload($data);

        return response()->json([
            'success' => true,
            'message' => "প্রপার্টি #{$property->property_code} সফলভাবে ডেটাবেজে যুক্ত করা হয়েছে!",
            'data'    => $this->formatProperty($property),
        ], 201);
    }

    /**
     * Bulk create properties from an array of sheet rows.
     */
    public function bulk(Request $request): JsonResponse
    {
        $items = $request->input('properties', $request->input('rows', []));
        if (!is_array($items) || empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'properties বা rows কী-তে অন্তত ১টি প্রপার্টির তথ্য প্রদান করুন।',
            ], 422);
        }

        $created = [];
        $errors = [];

        foreach ($items as $idx => $item) {
            try {
                $prop = $this->createPropertyFromPayload($item);
                $created[] = [
                    'index'         => $idx,
                    'id'            => $prop->id,
                    'property_code' => $prop->property_code,
                    'title'         => $prop->title,
                    'area'          => $prop->area,
                    'status'        => $prop->status,
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'index' => $idx,
                    'error' => $e->getMessage(),
                    'item'  => $item,
                ];
            }
        }

        return response()->json([
            'success'      => count($created) > 0,
            'total_input'  => count($items),
            'total_saved'  => count($created),
            'total_failed' => count($errors),
            'created'      => $created,
            'errors'       => $errors,
        ], count($created) > 0 ? 201 : 422);
    }

    /**
     * Core handler to parse and persist property and agent data.
     */
    protected function createPropertyFromPayload(array $payload): Property
    {
        return DB::transaction(function () use ($payload) {
            // 1. Resolve Location & Place
            $area = trim((string) ($payload['area'] ?? ($payload['place'] ?? 'ঢাকা')));
            $subArea = trim((string) ($payload['sub_area'] ?? ''));
            $block = trim((string) ($payload['block'] ?? ''));
            $sector = trim((string) ($payload['sector'] ?? ''));
            $road = trim((string) ($payload['road_number'] ?? ($payload['road'] ?? '')));
            $plotNumber = trim((string) ($payload['plot_number'] ?? ''));

            // Auto-clean block (e.g. "Block F" -> "F")
            if (empty($block) && preg_match('/(?:block|ব্লক)\s*([a-zA-Z0-9]+)/iu', $subArea, $m)) {
                $block = strtoupper($m[1]);
            }
            $block = trim(preg_replace('/^(?:block|ব্লক)\s*/iu', '', $block));

            // Auto-clean road
            $road = trim(preg_replace('/^(?:road|রোড|নং|#)\s*/iu', '', $road));
            $roadWidth = (int) preg_replace('/[^0-9]/', '', (string) ($payload['road_width_ft'] ?? ($payload['road_width'] ?? 0)));

            // 2. Resolve Property Type
            $rawType = strtolower(trim((string) ($payload['type'] ?? '')));
            if (empty($rawType)) {
                if (!empty($payload['katha']) || !empty($plotNumber) || str_contains(strtolower($area), 'plot')) {
                    $rawType = 'land';
                } elseif (!empty($payload['bedrooms']) || !empty($payload['flat_size_sqft'])) {
                    $rawType = 'flat';
                } else {
                    $rawType = 'land';
                }
            }

            $type = match ($rawType) {
                'flat', 'apartment', 'ফ্ল্যাট' => 'flat',
                'land_share', 'share', 'শেয়ার', 'শেয়ার' => 'land_share',
                'commercial', 'shop', 'দোকান' => 'commercial',
                default => 'land',
            };

            // 3. Resolve Size (Katha / Sqft)
            $rawSize = trim((string) ($payload['katha'] ?? ($payload['size'] ?? ($payload['katha_size'] ?? ($payload['flat_size_sqft'] ?? '')))));
            $size = $this->parseSizeValue($rawSize);

            // 4. Resolve Price & Total Price
            $rawUnitPrice = trim((string) ($payload['price'] ?? ($payload['price_per_katha'] ?? ($payload['price_per_unit'] ?? ''))));
            $rawTotalPrice = trim((string) ($payload['total_price'] ?? ($payload['price_total'] ?? '')));

            $pricePerUnit = $this->parseMoneyValue($rawUnitPrice);
            $priceTotal = $this->parseMoneyValue($rawTotalPrice);

            // Auto-calculate if one is missing
            if ($priceTotal <= 0 && $pricePerUnit > 0 && $size > 0) {
                $priceTotal = $pricePerUnit * $size;
            } elseif ($pricePerUnit <= 0 && $priceTotal > 0 && $size > 0) {
                $pricePerUnit = round($priceTotal / $size, 2);
            }

            // 5. Title & Descriptions
            $notes = trim((string) ($payload['extra_notes'] ?? ($payload['notes'] ?? ($payload['description'] ?? ''))));
            $title = trim((string) ($payload['title'] ?? ''));
            if (empty($title)) {
                $areaTitle = !empty($area) ? ucfirst($area) : 'ঢাকা';
                $blockTitle = !empty($block) ? " Block {$block}" : '';
                $sizeTitle = $size > 0 ? ($type === 'flat' ? " {$size} Sqft" : " {$size} Katha") : '';
                $typeTitle = match ($type) {
                    'flat' => 'Flat',
                    'land_share' => 'Land Share',
                    'commercial' => 'Commercial Space',
                    default => 'Plot',
                };
                $title = "{$areaTitle}{$blockTitle}{$sizeTitle} {$typeTitle} For Sale";
            }

            // 6. Agent / Contact Resolution
            $agentName = trim((string) ($payload['agent'] ?? ($payload['agent_name'] ?? '')));
            $contactPhone = trim((string) ($payload['contact'] ?? ($payload['phone'] ?? ($payload['agent_phone'] ?? ($payload['owner_phone'] ?? '')))));
            $messenger = trim((string) ($payload['messenger'] ?? ($payload['whatsapp'] ?? '')));
            $sourceType = trim((string) ($payload['source_type'] ?? 'agent'));

            $agentUser = null;
            if (!empty($contactPhone)) {
                $agentUser = $this->findOrCreateAgent($agentName, $contactPhone, $messenger, $sourceType);
            }

            // 7. Status (mapped to valid schema enum)
            $rawStatus = strtolower(trim((string) ($payload['status'] ?? 'pending_verification')));
            $status = match ($rawStatus) {
                'live', 'published' => 'live',
                'draft', 'directory' => 'draft',
                'booked' => 'booked',
                'sold' => 'sold',
                default => 'pending_verification',
            };

            // 8. Generate Codes
            $code = 'PK-' . rand(10000, 99999);
            $slug = Str::slug($title) . '-' . strtolower(Str::random(5));

            $creatorId = $agentUser?->id ?: (auth()->id() ?: User::whereIn('role', ['admin', 'administrator'])->value('id'));

            $property = Property::create([
                'property_code'      => $code,
                'type'               => $type,
                'title'              => $title,
                'slug'               => $slug,
                'description'        => $notes ?: "গুগল শিট / এআই সহকারী মারফত সংগৃহীত প্রপার্টি।",
                'area'               => $area,
                'sub_area'           => $subArea,
                'block'              => $block,
                'sector'             => $sector,
                'road_number'        => $road,
                'road_width_ft'      => $roadWidth > 0 ? $roadWidth : 20,
                'plot_number'        => $plotNumber,
                'facing'             => $payload['facing'] ?? null,
                'status'             => $status,
                'is_negotiable'      => !empty($payload['is_negotiable']),
                'is_featured'        => !empty($payload['is_featured']),
                'created_by_user_id' => $creatorId,
                'hidden_note'        => $payload['hidden_note'] ?? ($notes ? "Notes: {$notes}" : null),
                'custom_fields'      => [
                    'source'    => 'Google Sheet / Gemini API',
                    'messenger' => $messenger,
                    'raw_input' => $payload,
                ],
                'address_visibility' => [
                    'block'          => true,
                    'sector'         => true,
                    'road_number'    => true,
                    'plot_number'    => true,
                    'holding_number' => false,
                ],
            ]);

            // 9. Attach Type Details
            if ($type === 'land') {
                PropertyLandDetail::create([
                    'property_id'         => $property->id,
                    'katha_size'          => $size > 0 ? $size : 0.00,
                    'price_total'         => $priceTotal > 0 ? $priceTotal : 0.00,
                    'price_per_katha'     => $pricePerUnit > 0 ? $pricePerUnit : 0.00,
                    'is_corner'           => !empty($payload['is_corner']),
                    'is_lake_facing'      => !empty($payload['is_lake_facing']),
                    'boundary_wall_done'  => false,
                    'soil_filling_status' => 'filled',
                ]);
            } elseif ($type === 'flat') {
                PropertyFlatDetail::create([
                    'property_id'       => $property->id,
                    'flat_size_sqft'    => $size > 0 ? $size : 0.00,
                    'price_total'       => $priceTotal > 0 ? $priceTotal : 0.00,
                    'price_per_sqft'    => $pricePerUnit > 0 ? $pricePerUnit : 0.00,
                    'bedrooms'          => (int) ($payload['bedrooms'] ?? 3),
                    'bathrooms'         => (int) ($payload['bathrooms'] ?? 3),
                    'floor_number'      => (int) ($payload['floor_number'] ?? 0),
                    'completion_status' => $payload['completion_status'] ?? 'ready',
                    'has_lift'          => !empty($payload['has_lift']),
                    'parking_available' => !empty($payload['parking_available']),
                ]);
            } elseif ($type === 'land_share') {
                PropertyShareDetail::create([
                    'property_id'     => $property->id,
                    'land_size_katha' => $size > 0 ? $size : 0.00,
                    'share_price'     => $priceTotal > 0 ? $priceTotal : 0.00,
                ]);
            } elseif ($type === 'commercial') {
                PropertyCommercialDetail::create([
                    'property_id' => $property->id,
                    'space_sqft'  => $size > 0 ? $size : 0.00,
                    'price_total' => $priceTotal > 0 ? $priceTotal : 0.00,
                ]);
            }

            // 10. Direct Source Linking
            if ($agentUser) {
                PropertyDirectSource::create([
                    'property_id'              => $property->id,
                    'source_user_id'           => $agentUser->id,
                    'source_type'              => $sourceType,
                    'relationship_declaration' => 'Google Sheets / AI Integration থেকে সংগৃহীত।',
                    'owner_name'               => $agentUser->name,
                    'owner_phone'              => $agentUser->phone,
                    'asking_price'             => $priceTotal > 0 ? $priceTotal : 0.00,
                    'is_primary_source'        => true,
                    'verification_status'      => 'pending',
                ]);
            }

            // 11. Verification Checklist
            PropertyVerification::create([
                'property_id'             => $property->id,
                'owner_identity_checked'  => false,
                'location_checked'        => false,
                'basic_info_checked'      => false,
                'basic_documents_checked' => false,
            ]);

            return $property;
        });
    }

    /**
     * Find existing agent by phone or create new agent profile.
     */
    protected function findOrCreateAgent(string $name, string $phone, ?string $messenger = null, string $role = 'agent'): User
    {
        $rawPhone = trim($phone);
        $cleanDigits = preg_replace('/[^0-9]/', '', GoogleSheetSyncService::toEnNumber($rawPhone));

        $user = User::where('phone', $rawPhone);
        if (!empty($cleanDigits) && strlen($cleanDigits) >= 6) {
            $user->orWhere('phone', 'like', "%{$cleanDigits}%")
                 ->orWhere('whatsapp_number', 'like', "%{$cleanDigits}%");
        }
        $existing = $user->first();

        if ($existing) {
            if (!empty($name) && str_starts_with($existing->name, 'Agent ')) {
                $existing->update(['name' => $name]);
            }
            return $existing;
        }

        $agentName = !empty($name) ? $name : ('Agent ' . substr($cleanDigits, -4));
        $email = 'agent_' . Str::random(6) . '@propertykoi.local';

        $newUser = User::create([
            'name'            => $agentName,
            'phone'           => $rawPhone,
            'whatsapp_number' => $messenger ?: $rawPhone,
            'email'           => $email,
            'password'        => Hash::make(Str::random(16)),
            'role'            => in_array($role, ['agent', 'owner']) ? $role : 'agent',
            'is_verified'     => true,
        ]);

        MagicLoginService::getOrCreateToken($newUser);

        AgentProfile::create([
            'user_id'                  => $newUser->id,
            'admin_fixed_rating'       => 5.0,
            'is_verified_direct_agent' => true,
            'verified_at'              => now(),
        ]);

        return $newUser;
    }

    /**
     * Parse size strings like "4 katha", "100*20 katha", "1500 sqft".
     */
    protected function parseSizeValue(string $str): float
    {
        $en = GoogleSheetSyncService::toEnNumber(trim($str));
        if (empty($en)) {
            return 0.0;
        }

        if (preg_match('/^([0-9.]+)\s*\*\s*([0-9.]+)$/', $en, $m)) {
            return (float) $m[1] * (float) $m[2];
        }
        if (preg_match('/^([0-9.]+)\s*\+\s*([0-9.]+)$/', $en, $m)) {
            return (float) $m[1] + (float) $m[2];
        }
        if (preg_match('/([0-9.]+)/', $en, $m)) {
            return (float) $m[1];
        }

        return 0.0;
    }

    /**
     * Parse currency strings like "1.55cr", "6.2 cr", "15 লাখ", "1500000".
     */
    protected function parseMoneyValue(string $str): float
    {
        $en = strtolower(GoogleSheetSyncService::toEnNumber(trim($str)));
        if (empty($en)) {
            return 0.0;
        }

        // Crore (e.g. 1.55cr, 6.2 crore, ১.৫৫ কোটি)
        if (preg_match('/([0-9.]+)\s*(?:cr|crore|কোটি)/i', $en, $m)) {
            return (float) $m[1] * 10000000;
        }

        // Lakh / Lac (e.g. 80 lac, ৫০ লাখ)
        if (preg_match('/([0-9.]+)\s*(?:lac|lakh|লাখ)/i', $en, $m)) {
            return (float) $m[1] * 100000;
        }

        // Numeric string without suffix
        $clean = preg_replace('/[^0-9.]/', '', $en);
        if (is_numeric($clean)) {
            $val = (float) $clean;
            // If user enters 1.55 or 6.2 without suffix, assume crore
            if ($val > 0 && $val < 50) {
                return $val * 10000000;
            }
            return $val;
        }

        return 0.0;
    }

    /**
     * Standard response formatting for a property.
     */
    protected function formatProperty(Property $p): array
    {
        $primarySource = $p->directSources->first();
        $sourceUser = $primarySource?->sourceUser;

        return [
            'id'            => $p->id,
            'property_code' => $p->property_code,
            'type'          => $p->type,
            'type_label'    => $p->type_label,
            'title'         => $p->title,
            'slug'          => $p->slug,
            'area'          => $p->area,
            'block'         => $p->block,
            'road'          => $p->road_number,
            'plot_number'   => $p->plot_number,
            'status'        => $p->status,
            'size'          => $p->landDetail?->katha_size ?? ($p->flatDetail?->flat_size_sqft ?? null),
            'price_total'   => $p->landDetail?->price_total ?? ($p->flatDetail?->price_total ?? null),
            'formatted_price' => $p->formatted_price,
            'agent'         => $sourceUser ? [
                'id'              => $sourceUser->id,
                'name'            => $sourceUser->name,
                'phone'           => $sourceUser->phone,
                'whatsapp_number' => $sourceUser->whatsapp_number,
            ] : null,
            'url'           => $p->status === 'live' ? route('property.show', $p->slug) : null,
            'admin_edit_url' => url("/backend/properties/{$p->id}/edit"),
            'created_at'    => $p->created_at?->toIso8601String(),
        ];
    }
}
