<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentProfile;
use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyDirectSource;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyMedia;
use App\Models\PropertyShareDetail;
use App\Models\PropertyVerification;
use App\Models\Setting;
use App\Models\User;
use App\Services\GoogleSheetSyncService;
use App\Services\MagicLoginService;
use App\Services\PropertySearchService;
use App\Services\WatermarkService;
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

        if ($request->filled('type') || $request->filled('property_type')) {
            $type = $request->type ?: $request->property_type;
            $query->where('type', $type);
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
     * Get system watermark configuration for external sync tools and image stampers.
     */
    public function watermarkSettings(): JsonResponse
    {
        $logo = Setting::get('adm_watermark_logo', '');
        return response()->json([
            'success' => true,
            'data'    => [
                'enabled'  => WatermarkService::isEnabled(),
                'type'     => Setting::get('adm_watermark_type', 'text'),
                'text'     => Setting::get('adm_watermark_text', 'PropertyKoi.com'),
                'position' => Setting::get('adm_watermark_position', 'bottom-right'),
                'opacity'  => (int) Setting::get('adm_watermark_opacity', 80),
                'scale'    => (int) Setting::get('adm_watermark_scale', 20),
                'logo_url' => (!empty($logo) && file_exists(public_path($logo))) ? asset($logo) : null,
            ]
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
     * Fully compatible with the "Sell Property" JSON Schema and Google Sheets.
     */
    protected function createPropertyFromPayload(array $payload): Property
    {
        return DB::transaction(function () use ($payload) {
            // Normalize incoming keys (lowercase, replace spaces and dashes with underscores)
            $normalized = [];
            foreach ($payload as $k => $v) {
                $normKey = strtolower(trim(str_replace([' ', '-'], '_', (string) $k)));
                if (!isset($normalized[$normKey])) {
                    $normalized[$normKey] = $v;
                }
            }
            $payload = array_merge($normalized, $payload);

            // 1. Resolve Location & Place
            $area = trim((string) ($payload['area'] ?? ($payload['place'] ?? 'ঢাকা')));
            $customArea = trim((string) ($payload['custom_area'] ?? ''));
            if (($area === '__other__' || empty($area)) && !empty($customArea)) {
                $area = $customArea;
            }

            $subArea = trim((string) ($payload['sub_area'] ?? ''));
            $block = trim((string) ($payload['block'] ?? ''));
            $sector = trim((string) ($payload['sector'] ?? ''));
            $road = trim((string) ($payload['road_number'] ?? ($payload['road'] ?? ($payload['road_no'] ?? ''))));
            $plotNumber = trim((string) ($payload['plot_number'] ?? ($payload['plot'] ?? ($payload['plot_no'] ?? ''))));
            $holdingNumber = trim((string) ($payload['holding_number'] ?? ''));

            // Auto-clean block (e.g. "Block F" -> "F")
            if (empty($block) && preg_match('/(?:block|ব্লক)\s*([a-zA-Z0-9]+)/iu', $subArea, $m)) {
                $block = strtoupper($m[1]);
            }
            $block = trim(preg_replace('/^(?:block|ব্লক)\s*/iu', '', $block));

            // Auto-clean road
            $road = trim(preg_replace('/^(?:road|রোড|নং|#)\s*/iu', '', $road));
            $roadWidth = (int) preg_replace('/[^0-9]/', '', (string) ($payload['road_width_ft'] ?? ($payload['road_width'] ?? 20)));

            // 2. Resolve Property Type
            $rawType = strtolower(trim((string) ($payload['property_type'] ?? ($payload['type'] ?? ''))));
            if (empty($rawType)) {
                if (!empty($payload['katha']) || !empty($payload['price_per_katha']) || !empty($plotNumber) || str_contains(strtolower($area), 'plot')) {
                    $rawType = 'land';
                } elseif (!empty($payload['bedrooms']) || !empty($payload['flat_size_sqft']) || !empty($payload['price_per_sqft'])) {
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
            $rawSize = trim((string) ($payload['size'] ?? ($payload['katha'] ?? ($payload['katha_size'] ?? ($payload['flat_size_sqft'] ?? ($payload['size_sqft'] ?? ''))))));
            $size = $this->parseSizeValue($rawSize);

            // 4. Resolve Price & Financials
            $rawUnitPrice = trim((string) ($payload['price_per_katha'] ?? ($payload['price_per_sqft'] ?? ($payload['price'] ?? ($payload['price_per_unit'] ?? '')))));
            $rawTotalPrice = trim((string) ($payload['asking_price'] ?? ($payload['total_price'] ?? ($payload['price_total'] ?? ''))));

            $pricePerUnit = $this->parseMoneyValue($rawUnitPrice);
            $askingPrice = $this->parseMoneyValue($rawTotalPrice);

            $garagePrice = $this->parseMoneyValue((string) ($payload['garage_price'] ?? ''));
            $utilityPrice = $this->parseMoneyValue((string) ($payload['utility_price'] ?? ''));
            $downPayment = $this->parseMoneyValue((string) ($payload['down_payment'] ?? ''));

            // Auto-calculate asking price if missing
            if ($askingPrice <= 0 && $pricePerUnit > 0 && $size > 0) {
                $askingPrice = ($pricePerUnit * $size) + $garagePrice + $utilityPrice;
            } elseif ($pricePerUnit <= 0 && $askingPrice > 0 && $size > 0) {
                $pricePerUnit = round(($askingPrice - $garagePrice - $utilityPrice) / $size, 2);
            }

            // 5. Aggressive SEO Title & AEO Description
            $rawDesc = trim((string) ($payload['description'] ?? ($payload['extra_notes'] ?? ($payload['notes'] ?? ''))));
            $title = trim((string) ($payload['title'] ?? ''));
            if (empty($title)) {
                $areaTitle = !empty($area) ? ucfirst($area) : 'ঢাকা';
                $blockTitle = !empty($block) ? " Block {$block}" : '';
                $sectorTitle = !empty($sector) ? " Sector {$sector}" : '';
                $facingTitle = !empty($payload['facing']) ? (' ' . ucfirst(trim((string)$payload['facing'])) . '-Facing') : '';
                $roadTitle = !empty($road) ? " ({$road} Road)" : '';

                if ($type === 'flat') {
                    $sizeTitle = $size > 0 ? " {$size} Sqft" : '';
                    $bedsTitle = !empty($payload['bedrooms']) ? " {$payload['bedrooms']} Bed" : '';
                    $bathsTitle = !empty($payload['bathrooms']) ? " {$payload['bathrooms']} Bath" : '';
                    $floorNum = (int)($payload['floor_number'] ?? 0);
                    $floorTitle = $floorNum > 0 ? " " . $this->ordinalSuffix($floorNum) . " Floor" : '';
                    $specs = trim("{$bedsTitle}{$bathsTitle}{$floorTitle}");
                    $specsFormatted = !empty($specs) ? " ({$specs})" : '';
                    $title = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle} Ready Flat For Sale{$specsFormatted}";
                } elseif ($type === 'land_share') {
                    $sizeTitle = $size > 0 ? " {$size} Sqft" : '';
                    $title = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle} Land Share Apartment Project For Sale";
                } elseif ($type === 'commercial') {
                    $sizeTitle = $size > 0 ? " {$size} Sqft" : '';
                    $title = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle} Commercial Space / Shop For Sale";
                } else { // 'land' / plot
                    $sizeTitle = $size > 0 ? " {$size} Katha" : '';
                    $title = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle}{$facingTitle} Prime Residential Plot For Sale{$roadTitle}";
                }
            }

            $description = $this->buildAggressiveAeoDescription($type, $title, $rawDesc, $payload, $size, $askingPrice, $pricePerUnit);

            // 6. Agent / Owner Resolution
            $ownerName = trim((string) ($payload['owner_name'] ?? ($payload['agent_name'] ?? ($payload['agent'] ?? ''))));
            $ownerPhone = trim((string) ($payload['owner_phone'] ?? ($payload['contact'] ?? ($payload['phone'] ?? ($payload['agent_phone'] ?? '')))));
            $sourceType = trim((string) ($payload['source_type'] ?? 'agent'));
            $rawDeclaration = trim((string) ($payload['relationship_declaration'] ?? ''));
            if (empty($rawDeclaration) || preg_match('/api|google|sheet|sync|bot|script|crawler/i', $rawDeclaration)) {
                $declaration = ($sourceType === 'owner' || $sourceType === 'direct_owner')
                    ? 'সরাসরি প্রপার্টির মালিক (Direct Owner)'
                    : 'অনুমোদিত ডিরেক্ট এজেন্ট (Direct Agent)';
            } else {
                $declaration = $rawDeclaration;
            }

            $agentUser = null;
            if (!empty($ownerPhone)) {
                $messenger = trim((string) ($payload['messenger'] ?? ($payload['messanger'] ?? ($payload['whatsapp'] ?? ($payload['whatsapp_number'] ?? '')))));
                $agentUser = $this->findOrCreateAgent($ownerName, $ownerPhone, $messenger, $sourceType);
            }

            // 7. Status Resolution
            $rawStatus = strtolower(trim((string) ($payload['status'] ?? 'pending_verification')));
            $status = match ($rawStatus) {
                'live', 'published' => 'live',
                'draft', 'directory' => 'draft',
                'booked' => 'booked',
                'sold' => 'sold',
                default => 'pending_verification',
            };

            // 8. Address Visibility
            $rawVis = $payload['address_visibility'] ?? [];
            if (is_string($rawVis)) {
                $rawVis = json_decode($rawVis, true) ?: [];
            }
            $addressVisibility = [
                'block'          => $this->toBool($rawVis['block'] ?? true, true),
                'sector'         => $this->toBool($rawVis['sector'] ?? true, true),
                'road_number'    => $this->toBool($rawVis['road_number'] ?? true, true),
                'road_width_ft'  => $this->toBool($rawVis['road_width_ft'] ?? true, true),
                'plot_number'    => $this->toBool($rawVis['plot_number'] ?? true, true),
                'holding_number' => $this->toBool($rawVis['holding_number'] ?? false, false),
            ];

            // 9. Other Features Array
            $otherFeatures = $payload['other_features'] ?? [];
            if (is_string($otherFeatures)) {
                $otherFeatures = json_decode($otherFeatures, true) ?: [];
            }
            if (!is_array($otherFeatures)) {
                $otherFeatures = [];
            }

            // 10. Hidden Note & Keywords (Never publicly visible)
            $hiddenNote = trim((string) ($payload['hidden_note'] ?? ($payload['private_note'] ?? ($payload['private note'] ?? ''))));
            $hiddenTags = !empty($hiddenNote) ? PropertySearchService::extractKeywordsFromNote($hiddenNote) : null;

            // 11. Extract Any Extra / Custom Columns from Google Sheet
            $standardKeys = [
                'owner_name', 'owner_phone', 'contact', 'agent', 'agent_name', 'phone', 'agent_phone',
                'source_type', 'relationship_declaration', 'property_type', 'type', 'area', 'place',
                'custom_area', 'sub_area', 'title', 'block', 'sector', 'road_number', 'road', 'road_no',
                'road_width_ft', 'road_width', 'plot_number', 'plot', 'plot_no', 'holding_number', 'hidden_note',
                'private_note', 'private note',
                'address_visibility', 'facing', 'size', 'katha', 'katha_size', 'price_per_katha',
                'price', 'price_per_unit', 'total_price', 'price_total', 'flat_size_sqft', 'price_per_sqft',
                'garage_price', 'utility_price', 'down_payment', 'asking_price', 'is_negotiable',
                'description', 'notes', 'extra_notes', 'fb_caption', 'post_to_facebook', 'youtube_video_url',
                'google_map_pin_url', 'latitude', 'longitude', 'is_corner', 'is_lake_facing',
                'boundary_wall_done', 'soil_filling_status', 'bedrooms', 'bathrooms', 'balconies',
                'floor_number', 'completion_status', 'has_lift', 'parking_available', 'has_generator',
                'has_substation', 'has_gas', 'other_features', 'status', 'messenger', 'messanger', 'whatsapp', 'whatsapp_number'
            ];
            $extraColumns = [];
            foreach ($payload as $pKey => $pVal) {
                if (!in_array($pKey, $standardKeys) && $pVal !== '' && $pVal !== null) {
                    $extraColumns[$pKey] = $pVal;
                }
            }

            // 12. Generate Codes & Slugs
            $code = 'PK-' . rand(10000, 99999);
            $slug = Str::slug($title) . '-' . strtolower(Str::random(5));
            $creatorId = $agentUser?->id ?: (auth()->id() ?: User::whereIn('role', ['admin', 'administrator'])->value('id'));

            // 13. Create Property Record
            $property = Property::create([
                'property_code'      => $code,
                'type'               => $type,
                'title'              => $title,
                'slug'               => $slug,
                'description'        => $description,
                'area'               => $area,
                'sub_area'           => $subArea ?: ($block ? "Block {$block}" : null),
                'block'              => $block ?: null,
                'sector'             => $sector ?: null,
                'road_number'        => $road ?: null,
                'road_width_ft'      => $roadWidth > 0 ? $roadWidth : 20,
                'plot_number'        => $plotNumber ?: null,
                'holding_number'     => $holdingNumber ?: null,
                'facing'             => $payload['facing'] ?? null,
                'status'             => $status,
                'is_negotiable'      => $this->toBool($payload['is_negotiable'] ?? true, true),
                'is_featured'        => $this->toBool($payload['is_featured'] ?? false, false),
                'is_best_deal'       => $this->toBool($payload['is_best_deal'] ?? false, false),
                'created_by_user_id' => $creatorId,
                'latitude'           => !empty($payload['latitude']) ? (float) $payload['latitude'] : null,
                'longitude'          => !empty($payload['longitude']) ? (float) $payload['longitude'] : null,
                'google_map_pin_url' => $payload['google_map_pin_url'] ?? null,
                'fb_caption'         => $payload['fb_caption'] ?? null,
                'hidden_note'        => $hiddenNote ?: null,
                'hidden_tags'        => $hiddenTags,
                'other_features'     => $otherFeatures,
                'address_visibility' => $addressVisibility,
                'custom_fields'      => array_merge($extraColumns, [
                    'source'            => 'Google Sheet / API Integration',
                    'post_to_facebook'  => $this->toBool($payload['post_to_facebook'] ?? false, false),
                    'youtube_video_url' => $payload['youtube_video_url'] ?? null,
                    'raw_sheet_row'     => $payload,
                ]),
            ]);

            // 13. Attach Type Details
            if ($type === 'land') {
                PropertyLandDetail::create([
                    'property_id'         => $property->id,
                    'katha_size'          => $size > 0 ? $size : 0.00,
                    'price_total'         => $askingPrice > 0 ? $askingPrice : 0.00,
                    'price_per_katha'     => $pricePerUnit > 0 ? $pricePerUnit : 0.00,
                    'is_corner'           => $this->toBool($payload['is_corner'] ?? false, false),
                    'is_lake_facing'      => $this->toBool($payload['is_lake_facing'] ?? false, false),
                    'boundary_wall_done'  => $this->toBool($payload['boundary_wall_done'] ?? false, false),
                    'soil_filling_status' => in_array($payload['soil_filling_status'] ?? '', ['filled', 'completed', 'ready']) ? 'filled' : 'filled',
                ]);
            } elseif ($type === 'flat') {
                $flatSize = !empty($payload['flat_size_sqft']) ? (float) $payload['flat_size_sqft'] : ($size > 0 ? $size : 0.00);
                PropertyFlatDetail::create([
                    'property_id'       => $property->id,
                    'flat_size_sqft'    => $flatSize,
                    'price_total'       => $askingPrice > 0 ? $askingPrice : 0.00,
                    'price_per_sqft'    => $pricePerUnit > 0 ? $pricePerUnit : 0.00,
                    'garage_price'      => $garagePrice,
                    'utility_price'     => $utilityPrice,
                    'down_payment'      => $downPayment,
                    'bedrooms'          => (int) ($payload['bedrooms'] ?? 3),
                    'bathrooms'         => (int) ($payload['bathrooms'] ?? 3),
                    'balconies'         => (int) ($payload['balconies'] ?? 2),
                    'floor_number'      => (int) ($payload['floor_number'] ?? 1),
                    'completion_status' => $payload['completion_status'] ?? 'ready',
                    'has_lift'          => $this->toBool($payload['has_lift'] ?? false, false),
                    'parking_available' => $this->toBool($payload['parking_available'] ?? false, false),
                    'has_generator'     => $this->toBool($payload['has_generator'] ?? false, false),
                    'has_substation'    => $this->toBool($payload['has_substation'] ?? false, false),
                    'has_gas'           => $this->toBool($payload['has_gas'] ?? false, false),
                ]);
            } elseif ($type === 'land_share') {
                PropertyShareDetail::create([
                    'property_id'     => $property->id,
                    'project_name'    => $payload['project_name'] ?? null,
                    'developer_name'  => $payload['developer_name'] ?? null,
                    'land_size_katha' => $size > 0 ? $size : 0.00,
                    'flat_size_sqft'  => !empty($payload['flat_size_sqft']) ? (float) $payload['flat_size_sqft'] : 0.00,
                    'share_price'     => $askingPrice > 0 ? $askingPrice : 0.00,
                    'has_lift'        => $this->toBool($payload['has_lift'] ?? false, false),
                    'has_parking'     => $this->toBool($payload['parking_available'] ?? ($payload['has_parking'] ?? false), false),
                ]);
            } elseif ($type === 'commercial') {
                $commSize = !empty($payload['size_sqft']) ? (float) $payload['size_sqft'] : ($size > 0 ? $size : 0.00);
                PropertyCommercialDetail::create([
                    'property_id'        => $property->id,
                    'commercial_type'    => !empty($payload['commercial_type']) ? trim((string)$payload['commercial_type']) : 'shop',
                    'market_name'        => !empty($payload['market_name']) ? trim((string)$payload['market_name']) : null,
                    'shop_number'        => !empty($payload['shop_number']) ? trim((string)$payload['shop_number']) : null,
                    'size_sqft'          => $commSize,
                    'floor_number'       => !empty($payload['floor_number']) ? (int) $payload['floor_number'] : 1,
                    'total_floors'       => !empty($payload['total_floors']) ? (int) $payload['total_floors'] : null,
                    'price_total'        => $askingPrice > 0 ? $askingPrice : 0.00,
                    'price_per_sqft'     => $pricePerUnit > 0 ? $pricePerUnit : 0.00,
                    'frontage_ft'        => !empty($payload['frontage_ft']) ? (float) $payload['frontage_ft'] : null,
                    'ceiling_height_ft'  => !empty($payload['ceiling_height_ft']) ? (float) $payload['ceiling_height_ft'] : null,
                    'completion_status'  => !empty($payload['completion_status']) ? trim((string)$payload['completion_status']) : 'ready',
                    'monthly_rent_yield' => !empty($payload['monthly_rent_yield']) ? $this->parseMoneyValue((string)$payload['monthly_rent_yield']) : null,
                    'washroom_type'      => !empty($payload['washroom_type']) ? trim((string)$payload['washroom_type']) : 'common',
                    'has_lift'           => $this->toBool($payload['has_lift'] ?? false, false),
                    'has_generator'      => $this->toBool($payload['has_generator'] ?? false, false),
                    'has_central_ac'     => $this->toBool($payload['has_central_ac'] ?? false, false),
                    'has_fire_safety'    => $this->toBool($payload['has_fire_safety'] ?? false, false),
                    'has_cctv'           => $this->toBool($payload['has_cctv'] ?? false, false),
                    'has_parking'        => $this->toBool($payload['has_parking'] ?? ($payload['parking_available'] ?? false), false),
                    'has_escalator'      => $this->toBool($payload['has_escalator'] ?? false, false),
                    'suitable_for'       => is_array($payload['suitable_for'] ?? null)
                        ? implode(', ', array_filter($payload['suitable_for']))
                        : (!empty($payload['suitable_for']) ? trim((string)$payload['suitable_for']) : null),
                ]);
            }

            // 14. YouTube Video Embed
            if (!empty($payload['youtube_video_url'])) {
                PropertyMedia::create([
                    'property_id' => $property->id,
                    'file_path'   => trim($payload['youtube_video_url']),
                    'media_type'  => 'video_embed',
                    'caption'     => 'YouTube Video Tour',
                    'sort_order'  => 99,
                    'is_cover'    => false,
                ]);
            }

            // 14.1 Photo Media (Images / Cloudinary URLs)
            $rawImages = $payload['images'] ?? ($payload['photos'] ?? ($payload['media'] ?? ($payload['media_urls'] ?? [])));
            if (is_string($rawImages)) {
                $rawImages = preg_split('/[\r\n,|;]+/', $rawImages);
            }
            if (is_array($rawImages) && !empty($rawImages)) {
                $imgOrder = 0;
                foreach ($rawImages as $imgUrl) {
                    $imgUrl = trim((string) $imgUrl);
                    if (empty($imgUrl)) continue;
                    PropertyMedia::create([
                        'property_id' => $property->id,
                        'file_path'   => $imgUrl,
                        'media_type'  => 'photo',
                        'is_cover'    => ($imgOrder === 0),
                        'sort_order'  => ++$imgOrder,
                    ]);
                }
            }

            // 15. Direct Source Linking
            if ($agentUser) {
                PropertyDirectSource::create([
                    'property_id'              => $property->id,
                    'source_user_id'           => $agentUser->id,
                    'source_type'              => $sourceType,
                    'relationship_declaration' => $declaration,
                    'owner_name'               => $agentUser->name,
                    'owner_phone'              => $agentUser->phone,
                    'asking_price'             => $askingPrice > 0 ? $askingPrice : 0.00,
                    'is_primary_source'        => true,
                    'verification_status'      => 'pending',
                ]);
            }

            // 16. Verification Checklist (Pending)
            PropertyVerification::create([
                'property_id'             => $property->id,
                'owner_identity_checked'  => false,
                'location_checked'        => false,
                'basic_info_checked'      => false,
                'basic_documents_checked' => false,
            ]);

            // 17. Aggressive AEO & Search Engine Instant Indexing (IndexNow)
            if ($property->status === 'live') {
                try {
                    \App\Services\SeoService::pingIndexNow($property);
                } catch (\Throwable $e) {}
            }

            return $property;
        });
    }

    /**
     * Find existing agent by phone or create new agent profile.
     */
    protected function findOrCreateAgent(string $name, string $phone, ?string $messenger = null, string $role = 'agent'): User
    {
        $rawPhone = ltrim(trim($phone), "-\t\n\r\0\x0B ");
        if (preg_match('/^880\s*1/', $rawPhone)) {
            $rawPhone = '+' . $rawPhone;
        }
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
     * Update an existing property via API.
     */
    public function update(Request $request, string|int $identifier): JsonResponse
    {
        $property = Property::where('id', $identifier)
            ->orWhere('property_code', $identifier)
            ->orWhere('slug', $identifier)
            ->first();

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found.',
            ], 404);
        }

        $payload = $request->all();

        // Normalize incoming keys (lowercase, replace spaces and dashes with underscores)
        $normalized = [];
        foreach ($payload as $k => $v) {
            $normKey = strtolower(trim(str_replace([' ', '-'], '_', (string) $k)));
            if (!isset($normalized[$normKey])) {
                $normalized[$normKey] = $v;
            }
        }
        $payload = array_merge($normalized, $payload);

        // 1. Update size if provided
        $sizeUpdated = false;
        $size = 0.0;
        if (isset($payload['katha']) || isset($payload['size']) || isset($payload['katha_size']) || isset($payload['flat_size_sqft']) || isset($payload['size_sqft'])) {
            $rawSize = trim((string) ($payload['katha'] ?? ($payload['size'] ?? ($payload['katha_size'] ?? ($payload['flat_size_sqft'] ?? ($payload['size_sqft'] ?? ''))))));
            if ($rawSize !== '') {
                $size = $this->parseSizeValue($rawSize);
                if ($property->type === 'land' && $property->landDetail) {
                    $property->landDetail->update(['katha_size' => $size]);
                    $sizeUpdated = true;
                } elseif ($property->type === 'flat' && $property->flatDetail) {
                    $property->flatDetail->update(['flat_size_sqft' => $size]);
                    $sizeUpdated = true;
                } elseif ($property->type === 'commercial' && $property->commercialDetail) {
                    $property->commercialDetail->update(['size_sqft' => $size]);
                    $sizeUpdated = true;
                }
            }
        }
        if (!$sizeUpdated) {
            $size = (float) ($property->landDetail?->katha_size ?? ($property->flatDetail?->flat_size_sqft ?? ($property->commercialDetail?->size_sqft ?? 0.0)));
        }

        // 2. Update price if provided (price_per_katha, total_price, asking_price)
        $rawUnitPrice = trim((string) ($payload['price_per_katha'] ?? ($payload['price_per_sqft'] ?? ($payload['price'] ?? ($payload['price_per_unit'] ?? '')))));
        $rawTotalPrice = trim((string) ($payload['total_price'] ?? ($payload['asking_price'] ?? ($payload['price_total'] ?? ''))));

        $pricePerUnit = !empty($rawUnitPrice) ? $this->parseMoneyValue($rawUnitPrice) : 0.0;
        $totalPrice = !empty($rawTotalPrice) ? $this->parseMoneyValue($rawTotalPrice) : 0.0;

        if ($property->type === 'land' && $property->landDetail) {
            if ($totalPrice <= 0 && $pricePerUnit > 0 && $size > 0) {
                $totalPrice = $pricePerUnit * $size;
            } elseif ($pricePerUnit <= 0 && $totalPrice > 0 && $size > 0) {
                $pricePerUnit = round($totalPrice / $size, 2);
            }

            $priceUpdates = [];
            if ($totalPrice > 0) {
                $priceUpdates['price_total'] = $totalPrice;
            }
            if ($pricePerUnit > 0) {
                $priceUpdates['price_per_katha'] = $pricePerUnit;
            } elseif ($totalPrice > 0 && $size > 0) {
                $priceUpdates['price_per_katha'] = round($totalPrice / $size, 2);
            }
            if (!empty($priceUpdates)) {
                $property->landDetail->update($priceUpdates);
            }
        } elseif ($property->type === 'flat' && $property->flatDetail) {
            if ($totalPrice <= 0 && $pricePerUnit > 0 && $size > 0) {
                $totalPrice = $pricePerUnit * $size;
            } elseif ($pricePerUnit <= 0 && $totalPrice > 0 && $size > 0) {
                $pricePerUnit = round($totalPrice / $size, 2);
            }
            $flatUpdates = [];
            if ($totalPrice > 0) {
                $flatUpdates['price_total'] = $totalPrice;
            }
            if ($pricePerUnit > 0) {
                $flatUpdates['price_per_sqft'] = $pricePerUnit;
            } elseif ($totalPrice > 0 && $size > 0) {
                $flatUpdates['price_per_sqft'] = round($totalPrice / $size, 2);
            }
            if (isset($payload['bedrooms'])) $flatUpdates['bedrooms'] = (int) $payload['bedrooms'];
            if (isset($payload['bathrooms'])) $flatUpdates['bathrooms'] = (int) $payload['bathrooms'];
            if (isset($payload['balconies'])) $flatUpdates['balconies'] = (int) $payload['balconies'];
            if (isset($payload['floor_number'])) $flatUpdates['floor_number'] = (int) $payload['floor_number'];
            if (isset($payload['completion_status'])) $flatUpdates['completion_status'] = $payload['completion_status'];
            if (isset($payload['has_lift'])) $flatUpdates['has_lift'] = $this->toBool($payload['has_lift']);
            if (isset($payload['parking_available'])) $flatUpdates['parking_available'] = $this->toBool($payload['parking_available']);
            if (isset($payload['has_generator'])) $flatUpdates['has_generator'] = $this->toBool($payload['has_generator']);
            if (isset($payload['has_gas'])) $flatUpdates['has_gas'] = $this->toBool($payload['has_gas']);
            if (isset($payload['garage_price'])) $flatUpdates['garage_price'] = $this->parseMoneyValue((string)$payload['garage_price']);
            if (isset($payload['utility_price'])) $flatUpdates['utility_price'] = $this->parseMoneyValue((string)$payload['utility_price']);
            if (!empty($flatUpdates)) {
                $property->flatDetail->update($flatUpdates);
            }
        } elseif ($property->type === 'commercial' && $property->commercialDetail) {
            if ($totalPrice <= 0 && $pricePerUnit > 0 && $size > 0) {
                $totalPrice = $pricePerUnit * $size;
            } elseif ($pricePerUnit <= 0 && $totalPrice > 0 && $size > 0) {
                $pricePerUnit = round($totalPrice / $size, 2);
            }
            $commUpdates = [];
            if ($totalPrice > 0) $commUpdates['price_total'] = $totalPrice;
            if ($pricePerUnit > 0) $commUpdates['price_per_sqft'] = $pricePerUnit;
            if ($size > 0) $commUpdates['size_sqft'] = $size;
            if (isset($payload['commercial_type'])) $commUpdates['commercial_type'] = trim((string)$payload['commercial_type']);
            if (isset($payload['market_name'])) $commUpdates['market_name'] = trim((string)$payload['market_name']);
            if (isset($payload['shop_number'])) $commUpdates['shop_number'] = trim((string)$payload['shop_number']);
            if (isset($payload['floor_number'])) $commUpdates['floor_number'] = (int) $payload['floor_number'];
            if (isset($payload['total_floors'])) $commUpdates['total_floors'] = (int) $payload['total_floors'];
            if (isset($payload['frontage_ft'])) $commUpdates['frontage_ft'] = (float) $payload['frontage_ft'];
            if (isset($payload['ceiling_height_ft'])) $commUpdates['ceiling_height_ft'] = (float) $payload['ceiling_height_ft'];
            if (isset($payload['completion_status'])) $commUpdates['completion_status'] = trim((string)$payload['completion_status']);
            if (isset($payload['monthly_rent_yield'])) $commUpdates['monthly_rent_yield'] = $this->parseMoneyValue((string)$payload['monthly_rent_yield']);
            if (isset($payload['washroom_type'])) $commUpdates['washroom_type'] = trim((string)$payload['washroom_type']);
            if (isset($payload['has_lift'])) $commUpdates['has_lift'] = $this->toBool($payload['has_lift']);
            if (isset($payload['has_generator'])) $commUpdates['has_generator'] = $this->toBool($payload['has_generator']);
            if (isset($payload['has_central_ac'])) $commUpdates['has_central_ac'] = $this->toBool($payload['has_central_ac']);
            if (isset($payload['has_fire_safety'])) $commUpdates['has_fire_safety'] = $this->toBool($payload['has_fire_safety']);
            if (isset($payload['has_cctv'])) $commUpdates['has_cctv'] = $this->toBool($payload['has_cctv']);
            if (isset($payload['has_parking']) || isset($payload['parking_available'])) {
                $commUpdates['has_parking'] = $this->toBool($payload['has_parking'] ?? $payload['parking_available']);
            }
            if (isset($payload['has_escalator'])) $commUpdates['has_escalator'] = $this->toBool($payload['has_escalator']);
            if (isset($payload['suitable_for'])) {
                $commUpdates['suitable_for'] = is_array($payload['suitable_for'])
                    ? implode(', ', array_filter($payload['suitable_for']))
                    : trim((string)$payload['suitable_for']);
            }
            if (!empty($commUpdates)) {
                $property->commercialDetail->update($commUpdates);
            }
        }

        // 3. Update status if provided
        if (isset($payload['status']) || isset($payload['sell_status'])) {
            $rawStatus = strtolower(trim((string) ($payload['status'] ?? ($payload['sell_status'] ?? ''))));
            if ($rawStatus !== '') {
                $status = match (true) {
                    str_contains($rawStatus, 'sold') || str_contains($rawStatus, 'বিক্রি') || str_contains($rawStatus, 'বিক্রিত') => 'sold',
                    str_contains($rawStatus, 'booked') || str_contains($rawStatus, 'বুক') || str_contains($rawStatus, 'advance') => 'booked',
                    str_contains($rawStatus, 'draft') || str_contains($rawStatus, 'directory') || str_contains($rawStatus, 'ড্রাফট') => 'draft',
                    str_contains($rawStatus, 'live') || str_contains($rawStatus, 'active') || str_contains($rawStatus, 'available') || str_contains($rawStatus, 'চলমান') => 'live',
                    default => 'pending_verification',
                };
                $property->update(['status' => $status]);
            }
        }

        // 4. Update Location details (block, sector, road, plot, area)
        $propUpdates = [];
        if (isset($payload['block'])) {
            $block = trim(preg_replace('/^(?:block|ব্লক)\s*/iu', '', (string) $payload['block']));
            $propUpdates['block'] = $block ?: null;
            if ($block) {
                $propUpdates['sub_area'] = "Block {$block}";
            }
        }
        if (isset($payload['sector'])) {
            $propUpdates['sector'] = trim((string) $payload['sector']) ?: null;
        }
        if (isset($payload['road_number']) || isset($payload['road']) || isset($payload['road_no'])) {
            $road = trim(preg_replace('/^(?:road|রোড|নং|#)\s*/iu', '', (string) ($payload['road_number'] ?? ($payload['road'] ?? ($payload['road_no'] ?? '')))));
            $propUpdates['road_number'] = $road ?: null;
        }
        if (isset($payload['plot_number']) || isset($payload['plot']) || isset($payload['plot_no'])) {
            $propUpdates['plot_number'] = trim((string) ($payload['plot_number'] ?? ($payload['plot'] ?? ($payload['plot_no'] ?? '')))) ?: null;
        }
        if (isset($payload['place']) || isset($payload['area'])) {
            $area = trim((string) ($payload['place'] ?? ($payload['area'] ?? '')));
            if (!empty($area) && $area !== '__other__') {
                $propUpdates['area'] = $area;
            }
        }
        if (isset($payload['holding_number'])) {
            $propUpdates['holding_number'] = trim((string) $payload['holding_number']) ?: null;
        }
        if (isset($payload['facing'])) {
            $propUpdates['facing'] = trim((string) $payload['facing']) ?: null;
        }
        if (isset($payload['other_features'])) {
            $rawFeatures = $payload['other_features'];
            if (is_string($rawFeatures)) {
                $rawFeatures = preg_split('/[\r\n,|;]+/', $rawFeatures);
            }
            if (is_array($rawFeatures)) {
                $propUpdates['other_features'] = array_values(array_filter(array_map('trim', $rawFeatures)));
            }
        }

        // 5. Update title or description
        if (isset($payload['title']) && !empty(trim((string) $payload['title']))) {
            $propUpdates['title'] = trim((string) $payload['title']);
        } elseif ($sizeUpdated || isset($payload['block']) || isset($payload['place']) || isset($payload['sector']) || isset($payload['road'])) {
            $currentArea = $propUpdates['area'] ?? $property->area;
            $currentBlock = $propUpdates['block'] ?? $property->block;
            $currentSector = $propUpdates['sector'] ?? $property->sector;
            $currentRoad = $propUpdates['road_number'] ?? $property->road_number;
            $currentFacing = $propUpdates['facing'] ?? $property->facing;
            $currentType = $property->type;

            $areaTitle = !empty($currentArea) ? ucfirst($currentArea) : 'ঢাকা';
            $blockTitle = !empty($currentBlock) ? " Block {$currentBlock}" : '';
            $sectorTitle = !empty($currentSector) ? " Sector {$currentSector}" : '';
            $facingTitle = !empty($currentFacing) ? (' ' . ucfirst(trim((string)$currentFacing)) . '-Facing') : '';
            $roadTitle = !empty($currentRoad) ? " ({$currentRoad} Road)" : '';

            if ($currentType === 'flat') {
                $flatDetail = $property->flatDetail;
                $sizeVal = $flatDetail?->flat_size_sqft ?: $size;
                $sizeTitle = $sizeVal > 0 ? " {$sizeVal} Sqft" : '';
                $bedsTitle = $flatDetail?->bedrooms ? " {$flatDetail->bedrooms} Bed" : '';
                $bathsTitle = $flatDetail?->bathrooms ? " {$flatDetail->bathrooms} Bath" : '';
                $floorNum = (int)($flatDetail?->floor_number ?? 0);
                $floorTitle = $floorNum > 0 ? " " . $this->ordinalSuffix($floorNum) . " Floor" : '';
                $specs = trim("{$bedsTitle}{$bathsTitle}{$floorTitle}");
                $specsFormatted = !empty($specs) ? " ({$specs})" : '';
                $propUpdates['title'] = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle} Ready Flat For Sale{$specsFormatted}";
            } elseif ($currentType === 'land_share') {
                $sizeTitle = $size > 0 ? " {$size} Sqft" : '';
                $propUpdates['title'] = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle} Land Share Apartment Project For Sale";
            } elseif ($currentType === 'commercial') {
                $sizeTitle = $size > 0 ? " {$size} Sqft" : '';
                $propUpdates['title'] = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle} Commercial Space / Shop For Sale";
            } else {
                $sizeTitle = $size > 0 ? " {$size} Katha" : '';
                $propUpdates['title'] = "{$areaTitle}{$blockTitle}{$sectorTitle}{$sizeTitle}{$facingTitle} Prime Residential Plot For Sale{$roadTitle}";
            }
        }

        if (isset($payload['description'])) {
            $desc = trim((string) $payload['description']);
            if (!empty($desc)) {
                $propUpdates['description'] = (strlen($desc) < 80 || !str_contains($desc, 'স্পেসিফিকেশন'))
                    ? $this->buildAggressiveAeoDescription($property->type, $propUpdates['title'] ?? $property->title, $desc, array_merge($property->toArray(), $payload), $size, $totalPrice, $pricePerUnit)
                    : $desc;
            }
        }

        // 6. Hidden Note
        if (isset($payload['hidden_note']) || isset($payload['private_note'])) {
            $note = trim((string) ($payload['hidden_note'] ?? $payload['private_note']));
            $propUpdates['hidden_note'] = $note ?: null;
            $propUpdates['hidden_tags'] = !empty($note) ? PropertySearchService::extractKeywordsFromNote($note) : null;
        }

        if (!empty($propUpdates)) {
            $property->update($propUpdates);
        }

        // 7. Agent / Contact Resolution if provided
        $contact = trim((string) ($payload['contact'] ?? ($payload['owner_phone'] ?? ($payload['phone'] ?? ($payload['agent_phone'] ?? '')))));
        $agentName = trim((string) ($payload['agent'] ?? ($payload['owner_name'] ?? ($payload['agent_name'] ?? ''))));
        if (!empty($contact)) {
            $messenger = trim((string) ($payload['messenger'] ?? ($payload['messanger'] ?? ($payload['whatsapp'] ?? ''))));
            $sourceType = trim((string) ($payload['source_type'] ?? 'agent'));
            $agentUser = $this->findOrCreateAgent($agentName, $contact, $messenger, $sourceType);
            if ($agentUser) {
                $source = $property->directSources->first();
                if ($source) {
                    $source->update([
                        'source_user_id' => $agentUser->id,
                        'source_type'    => in_array($sourceType, ['owner', 'direct_owner']) ? 'owner' : 'agent',
                    ]);
                } else {
                    PropertyDirectSource::create([
                        'property_id'               => $property->id,
                        'source_user_id'            => $agentUser->id,
                        'source_type'               => in_array($sourceType, ['owner', 'direct_owner']) ? 'owner' : 'agent',
                        'relationship_declaration'  => ($sourceType === 'owner') ? 'সরাসরি প্রপার্টির মালিক (Direct Owner)' : 'অনুমোদিত ডিরেক্ট এজেন্ট (Direct Agent)',
                    ]);
                }
            }
        }

        // 8. Attach New Images / Cloudinary URLs if provided
        if (isset($payload['images']) || isset($payload['photos']) || isset($payload['media']) || isset($payload['media_urls'])) {
            $rawImages = $payload['images'] ?? ($payload['photos'] ?? ($payload['media'] ?? ($payload['media_urls'] ?? [])));
            if (is_string($rawImages)) {
                $rawImages = preg_split('/[\r\n,|;]+/', $rawImages);
            }
            if (is_array($rawImages) && !empty($rawImages)) {
                // If replacing images, remove existing photos first
                if (!empty($payload['replace_images']) || $this->toBool($payload['replace_images'] ?? false)) {
                    $property->media()->where('media_type', 'photo')->delete();
                }
                $order = $property->media()->where('media_type', 'photo')->count();
                foreach ($rawImages as $imgUrl) {
                    $imgUrl = trim((string) $imgUrl);
                    if (empty($imgUrl)) continue;
                    if ($property->media()->where('file_path', $imgUrl)->exists()) continue;
                    PropertyMedia::create([
                        'property_id' => $property->id,
                        'file_path'   => $imgUrl,
                        'media_type'  => 'photo',
                        'is_cover'    => ($order === 0),
                        'sort_order'  => ++$order,
                    ]);
                }
            }
        }

        $property->refresh();

        if ($property->status === 'live') {
            try {
                \App\Services\SeoService::pingIndexNow($property);
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'success' => true,
            'message' => "প্রপার্টি #{$property->property_code} সফলভাবে আপডেট করা হয়েছে!",
            'data'    => $this->formatProperty($property),
        ]);
    }

    /**
     * Helper to get ordinal English suffix for floor numbers (1st, 2nd, 3rd, 4th...)
     */
    protected function ordinalSuffix(int $num): string
    {
        if (!in_array(($num % 100), [11, 12, 13])) {
            switch ($num % 10) {
                case 1:  return $num . 'st';
                case 2:  return $num . 'nd';
                case 3:  return $num . 'rd';
            }
        }
        return $num . 'th';
    }

    /**
     * Build rich, highly structured, aggressive AEO (Answer Engine Optimization) & SEO description.
     */
    protected function buildAggressiveAeoDescription(
        string $type,
        string $title,
        ?string $userDesc,
        array $payload,
        float $size,
        float $askingPrice,
        float $pricePerUnit
    ): string {
        $area = ucfirst(trim((string)($payload['place'] ?? ($payload['area'] ?? 'ঢাকা'))));
        $block = trim(preg_replace('/^(?:block|ব্লক)\s*/iu', '', (string)($payload['block'] ?? '')));
        $sector = trim((string)($payload['sector'] ?? ''));
        $road = trim((string)($payload['road_number'] ?? ($payload['road'] ?? '')));
        $plot = trim((string)($payload['plot_number'] ?? ($payload['plot'] ?? '')));
        $facing = trim((string)($payload['facing'] ?? ''));
        $otherFeatures = $payload['other_features'] ?? [];
        if (is_string($otherFeatures)) {
            $otherFeatures = preg_split('/[\r\n,|;]+/', $otherFeatures);
        }
        $otherFeatures = is_array($otherFeatures) ? array_filter(array_map('trim', $otherFeatures)) : [];

        $typeLabel = match ($type) {
            'flat' => 'রেডি ফ্ল্যাট (Ready Flat)',
            'commercial' => 'বাণিজ্যিক স্পেস (Commercial Space)',
            'land_share' => 'ল্যান্ড শেয়ার প্রকল্প (Land Share)',
            default => 'আবাসিক প্লট (Residential Land / Plot)',
        };

        // Formatted Price String
        $priceStr = 'আলোচনা সাপেক্ষ';
        if ($askingPrice >= 10000000) {
            $priceStr = '৳' . number_format($askingPrice / 10000000, 2) . ' কোটি';
        } elseif ($askingPrice >= 100000) {
            $priceStr = '৳' . number_format($askingPrice / 100000, 2) . ' লাখ';
        }

        $unitPriceStr = '';
        if ($pricePerUnit > 0) {
            if ($type === 'flat' || $type === 'commercial' || $type === 'land_share') {
                $unitPriceStr = " (প্রতি বর্গফুট: ৳" . number_format($pricePerUnit, 0) . ")";
            } else {
                if ($pricePerUnit >= 10000000) {
                    $unitPriceStr = " (প্রতি কাঠা: ৳" . number_format($pricePerUnit / 10000000, 2) . " কোটি)";
                } else {
                    $unitPriceStr = " (প্রতি কাঠা: ৳" . number_format($pricePerUnit / 100000, 2) . " লাখ)";
                }
            }
        }

        $lines = [];

        // Preserve and highlight user's custom notes if present and not purely technical
        $cleanUserDesc = trim((string)$userDesc);
        if (!empty($cleanUserDesc) && !preg_match('/^(?:api|google|sheet|sync|bot)/i', $cleanUserDesc)) {
            $lines[] = "【বিশেষ বিবরণ ও রিমার্কস】: {$cleanUserDesc}";
            $lines[] = "";
        }

        // Executive overview for Google SGE / AI Answer Engines
        $sizeDesc = $size > 0 ? ($type === 'flat' ? "{$size} বর্গফুট" : "{$size} কাঠা") : 'যাচাইকৃত আয়তনের';
        $locationDesc = $area . (!empty($block) ? " ব্লক {$block}" : '') . (!empty($sector) ? " সেক্টর {$sector}" : '');
        $lines[] = "ঢাকার অন্যতম প্রাইম লোকেশন {$locationDesc}-এ আকর্ষণীয় {$sizeDesc} {$typeLabel} বিক্রয়ের জন্য তালিকাভুক্ত করা হয়েছে। নিষ্কণ্টক মালিকানা ও শতভাগ ভেরিফাইড কাগজপত্রের নিশ্চয়তাসহ সরাসরি ক্রেতাদের জন্য এটি একটি সুবর্ণ সুযোগ।";
        $lines[] = "";

        // Structured Bullet points (Key Specifications)
        $lines[] = "📌 প্রপার্টির মূল বৈশিষ্ট্য ও স্পেসিফিকেশন (Property Key Specifications):";
        $lines[] = "• লোকেশন: {$locationDesc}" . (!empty($road) ? ", রোড: {$road}" : '') . (!empty($plot) ? ", প্লট: {$plot}" : '');
        $lines[] = "• মোট আয়তন: " . ($size > 0 ? ($type === 'flat' ? "{$size} বর্গফুট (Sqft)" : "{$size} কাঠা (Katha)") : 'অন-সাইট যাচাইকৃত');

        if (!empty($facing)) {
            $lines[] = "• দিক ও অবস্থান: {$facing} মুখী" . (!empty($payload['is_corner']) ? ", আকর্ষণীয় কর্নার প্লট" : '') . (!empty($payload['is_lake_facing']) ? ", মনোরম লেকভিউ অবস্থান" : '');
        }

        if ($type === 'flat') {
            $beds = $payload['bedrooms'] ?? 3;
            $baths = $payload['bathrooms'] ?? 3;
            $balc = $payload['balconies'] ?? 2;
            $floor = $payload['floor_number'] ?? 1;
            $lines[] = "• রুম বিন্যাস: {$beds}টি বেডরুম, {$baths}টি বাথরুম, {$balc}টি বারান্দা (ফ্লোর: {$floor} তলা)";

            $amenities = [];
            if (!empty($payload['has_lift'])) $amenities[] = 'প্যাসেঞ্জার লিফট';
            if (!empty($payload['parking_available'])) $amenities[] = 'ডেডিকেটেড কার পার্কিং';
            if (!empty($payload['has_generator'])) $amenities[] = 'স্ট্যান্ডবাই জেনারেটর ব্যাকআপ';
            if (!empty($payload['has_gas'])) $amenities[] = 'গ্যাস সংযোগ';
            if (!empty($amenities)) {
                $lines[] = "• আধুনিক সুবিধাসমূহ: " . implode(', ', $amenities);
            }
        } elseif ($type === 'land') {
            $landAmenities = [];
            if (!empty($payload['boundary_wall_done'])) $landAmenities[] = 'চারপাশে সীমানা প্রাচীর (Boundary Wall) সম্পন্ন';
            if (!empty($payload['soil_filling_status'])) $landAmenities[] = 'মাটি ভরাট ও রেডি প্লট';
            if (!empty($landAmenities)) {
                $lines[] = "• প্লটের সার্বিক অবস্থা: " . implode(', ', $landAmenities);
            }
        } elseif ($type === 'commercial') {
            $commDetails = [];
            if (!empty($payload['commercial_type'])) $commDetails[] = "স্পেসের ধরণ: " . ucfirst($payload['commercial_type']);
            if (!empty($payload['market_name'])) $commDetails[] = "মার্কেট/ভবন: " . $payload['market_name'];
            if (!empty($payload['shop_number'])) $commDetails[] = "দোকান/স্পেস নং: " . $payload['shop_number'];
            if (!empty($payload['floor_number'])) $commDetails[] = "ফ্লোর: " . $this->ordinalSuffix((int)$payload['floor_number']) . " Floor";
            if (!empty($payload['frontage_ft'])) $commDetails[] = "সম্মুখভাগ (Frontage): " . $payload['frontage_ft'] . " ft";
            if (!empty($commDetails)) {
                $lines[] = "• স্পেস বিবরণ: " . implode(', ', $commDetails);
            }
            if (!empty($payload['suitable_for'])) {
                $lines[] = "• উপযুক্ত ব্যবসা: " . (is_array($payload['suitable_for']) ? implode(', ', $payload['suitable_for']) : $payload['suitable_for']);
            }
            if (!empty($payload['monthly_rent_yield'])) {
                $lines[] = "• সম্ভাব্য মাসিক ভাড়া আয় (Rental Yield): ৳" . number_format((float)$payload['monthly_rent_yield'], 0) . " টাকা";
            }
            $commAmenities = [];
            if (!empty($payload['has_lift'])) $commAmenities[] = 'লিফট';
            if (!empty($payload['has_generator'])) $commAmenities[] = 'জেনারেটর';
            if (!empty($payload['has_central_ac'])) $commAmenities[] = 'সেন্ট্রাল এসি';
            if (!empty($payload['has_fire_safety'])) $commAmenities[] = 'ফায়ার সেফটি';
            if (!empty($payload['has_cctv'])) $commAmenities[] = 'সিসিটিভি নজরদারি';
            if (!empty($payload['has_parking']) || !empty($payload['parking_available'])) $commAmenities[] = 'পার্কিং সুবিধা';
            if (!empty($payload['has_escalator'])) $commAmenities[] = 'এস্কেলেটর';
            if (!empty($commAmenities)) {
                $lines[] = "• বাণিজ্যিক সুবিধাসমূহ: " . implode(', ', $commAmenities);
            }
        }

        if (!empty($otherFeatures)) {
            $lines[] = "• অতিরিক্ত সুবিধা: " . implode(', ', $otherFeatures);
        }

        $lines[] = "• প্রস্তাবিত বিক্রয় মূল্য: {$priceStr}{$unitPriceStr}" . (!empty($payload['is_negotiable']) ? ' (আলোচনা সাপেক্ষ)' : ' (ফিক্সড রেট)');
        $lines[] = "";

        // Legal & Safety Verification Trust signals (crucial for E-E-A-T and AEO)
        $lines[] = "🛡️ লিগ্যাল ভেরিফিকেশন ও ট্রান্সফার নিরাপত্তা (Legal & Title Assurance):";
        $lines[] = "• কাগজপত্র: মূল দলিল, পূর্ববর্তী বায়া দলিল (Chain of Title), সিএস/এসএ/আরএস/সিটি জরিপ খতিয়ান এবং নামজারি (E-Namjari) ও খাজনা রশিদ সম্পূর্ণ হালনাগাদ ও যাচাইকৃত।";
        $lines[] = "• রাজউক / আবাসন অনুমোদন: অনুমোদিত মাস্টারপ্ল্যান এবং নিয়ম মেনে হস্তান্তরযোগ্য নিরাপদ প্রপার্টি।";
        $lines[] = "• সরাসরি পরিদর্শন: সরেজমিনে প্লট/ফ্ল্যাটটি পরিদর্শন করতে ও কাগজপত্র পর্যালোচনার জন্য PropertyKoi-তে সাইট ভিজিট শিডিউল করুন।";

        return implode("\n", $lines);
    }

    /**
     * Parse size strings like "4 katha", "100*20 katha", "2.5 * 20 katha", "15 bigha", "1500 sqft".
     */
    protected function parseSizeValue(string $str): float
    {
        $en = strtolower(GoogleSheetSyncService::toEnNumber(trim($str)));
        if (empty($en)) {
            return 0.0;
        }

        // Handle multiplication: e.g. "2.5 * 20 katha", "100*20", "2.5x20"
        if (preg_match('/([0-9.]+)\s*[\*xX×]\s*([0-9.]+)/u', $en, $m)) {
            return round((float) $m[1] * (float) $m[2], 2);
        }

        // Handle addition: e.g. "5 + 2 katha"
        if (preg_match('/([0-9.]+)\s*\+\s*([0-9.]+)/', $en, $m)) {
            return round((float) $m[1] + (float) $m[2], 2);
        }

        // Handle bigha auto-conversion to katha: 1 bigha = 20 katha
        if (preg_match('/([0-9.]+)\s*(?:bigha|বিঘা)/i', $en, $m)) {
            return round((float) $m[1] * 20, 2);
        }

        // Handle standard numeric
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
     * Helper to safely convert mixed values to boolean.
     */
    protected function toBool(mixed $val, bool $default = false): bool
    {
        if ($val === null || $val === '') {
            return $default;
        }
        if (is_bool($val)) {
            return $val;
        }
        $filtered = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $filtered !== null ? $filtered : $default;
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
            'size'          => $p->landDetail?->katha_size ?? ($p->flatDetail?->flat_size_sqft ?? ($p->commercialDetail?->size_sqft ?? null)),
            'price_total'   => $p->landDetail?->price_total ?? ($p->flatDetail?->price_total ?? ($p->commercialDetail?->price_total ?? null)),
            'formatted_price' => $p->formatted_price,
            'agent'         => $sourceUser ? [
                'id'              => $sourceUser->id,
                'name'            => $sourceUser->name,
                'phone'           => $sourceUser->phone,
                'whatsapp_number' => $sourceUser->whatsapp_number,
            ] : null,
            'url'           => $p->status === 'live' ? route('property.show', $p->slug) : null,
            'cover_image'   => $p->cover_url,
            'media'         => $p->media->map(fn ($m) => [
                'file_path'  => $m->file_path,
                'media_type' => $m->media_type,
                'is_cover'   => (bool) $m->is_cover,
            ])->values()->all(),
            'admin_edit_url' => url("/backend/properties/{$p->id}/edit"),
            'created_at'    => $p->created_at?->toIso8601String(),
        ];
    }
}
