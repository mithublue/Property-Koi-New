<?php

namespace App\Services;

use App\Models\BuyerInteraction;
use App\Models\BuyerRequirement;
use App\Models\Lead;
use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyDirectSource;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyMedia;
use App\Models\PropertyShareDetail;
use App\Models\PropertyVerification;
use App\Models\Review;
use App\Models\User;
use App\Models\VisitBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WpLiveImportService
{
    /**
     * Test connection to the live WordPress site using Application Password
     */
    public function testConnection(string $url, string $username, string $password): array
    {
        $baseUrl = rtrim(trim($url), '/');
        $cleanPassword = str_replace(' ', '', trim($password));

        // 1. Try custom ADM REST API endpoint
        $res = $this->sendRequest("{$baseUrl}/wp-json/adm/v1/export-ping", $username, $cleanPassword);
        if ($res['success']) {
            return $res;
        }

        // 2. Try standalone sync bridge fallback
        $resBridge = $this->sendRequest("{$baseUrl}/wp-sync-bridge.php?action=ping", $username, $cleanPassword);
        if ($resBridge['success']) {
            return $resBridge;
        }

        // 3. Fallback: Test core WP REST API to verify credentials and count live properties via AJAX
        $resWp = $this->sendRequest("{$baseUrl}/wp-json/wp/v2/users/me", $username, $cleanPassword);
        if ($resWp['success'] && isset($resWp['data']['id'])) {
            $propCountText = 'Available on live site';
            $totalLiveProps = 0;
            try {
                $rLand = Http::timeout(6)->get("{$baseUrl}/wp-admin/admin-ajax.php?action=adm_filter_land&per_page=1")->json();
                $rFlat = Http::timeout(6)->get("{$baseUrl}/wp-admin/admin-ajax.php?action=adm_filter_flat&per_page=1")->json();
                $lTotal = (int) ($rLand['data']['total'] ?? 0);
                $fTotal = (int) ($rFlat['data']['total'] ?? 0);
                $totalLiveProps = $lTotal + $fTotal;
                if ($totalLiveProps > 0) {
                    $propCountText = "{$totalLiveProps}টি (জমি: {$lTotal}, ফ্ল্যাট: {$fTotal})";
                }
            } catch (\Throwable $e) {}

            return [
                'success' => true,
                'endpoint' => 'Live WP API + AJAX Gateway',
                'site_name' => parse_url($baseUrl, PHP_URL_HOST),
                'site_url' => $baseUrl,
                'authenticated_user' => $resWp['data']['slug'] ?? $username,
                'user_display_name' => $resWp['data']['name'] ?? $username,
                'counts' => [
                    'properties' => $propCountText,
                    'leads' => 'Available on live site',
                    'demands' => 'Available on live site',
                    'users' => 'Available on live site',
                ],
                'note' => $totalLiveProps > 0 
                    ? "লাইভ সাইটের সাথে সফলভাবে সংযুক্ত! মোট {$totalLiveProps}টি প্রোপার্টি তাৎক্ষণিক ইমপোর্টের জন্য প্রস্তুত।" 
                    : 'লাইভ সাইটের সাথে সফলভাবে সংযুক্ত!'
            ];
        }

        return [
            'success' => false,
            'message' => $res['message'] ?? 'Could not connect. Please check Site URL, Username, and Application Password.',
            'status_code' => $res['status_code'] ?? 0,
        ];
    }

    /**
     * Perform the actual import from the live WordPress site
     */
    public function runImport(array $config): array
    {
        $baseUrl = rtrim(trim($config['url'] ?? ''), '/');
        $username = trim($config['username'] ?? '');
        $password = str_replace(' ', '', trim($config['password'] ?? ''));

        $limit = max(1, min(500, (int) ($config['limit'] ?? 100)));
        $dupAction = ($config['duplicate_action'] ?? 'skip') === 'overwrite' ? 'overwrite' : 'skip';

        $scopeProps   = !empty($config['import_properties']);
        $scopeLeads   = !empty($config['import_leads']);
        $scopeDemands = !empty($config['import_demands']);
        $scopeUsers   = !empty($config['import_users']);
        $scopeReviews = !empty($config['import_reviews']);
        $syncCloudinary = !empty($config['sync_cloudinary']);

        $exportUrl = "{$baseUrl}/wp-json/adm/v1/export-full?limit={$limit}";
        $res = $this->sendRequest($exportUrl, $username, $password);

        if (!$res['success']) {
            // Try standalone bridge
            $bridgeUrl = "{$baseUrl}/wp-sync-bridge.php?action=export&limit={$limit}";
            $res = $this->sendRequest($bridgeUrl, $username, $password);
        }

        if (!$res['success']) {
            // Fallback: Fetch directly from live site AJAX endpoints (zero bridge/plugin upload needed)
            $res = $this->fetchViaLiveAjax($baseUrl, $username, $password, $limit);
        }

        if (!$res['success'] || !isset($res['data'])) {
            return [
                'success' => false,
                'message' => 'Failed to fetch export payload: ' . ($res['message'] ?? 'Unknown network or auth error'),
            ];
        }

        $payload = $res['data'];

        $stats = [
            'properties_imported' => 0,
            'properties_updated'  => 0,
            'properties_skipped'  => 0,
            'users_imported'      => 0,
            'leads_imported'      => 0,
            'demands_imported'    => 0,
            'reviews_imported'    => 0,
            'media_uploaded'      => 0,
            'logs'                => [],
        ];

        DB::beginTransaction();
        try {
            // 1. IMPORT USERS
            if ($scopeUsers && !empty($payload['users'])) {
                foreach ($payload['users'] as $u) {
                    $email = trim($u['user_email'] ?? '');
                    $phone = trim($u['phone'] ?? '');
                    if (empty($email) && empty($phone)) continue;

                    $existing = User::where(function ($q) use ($email, $phone) {
                        if (!empty($email)) {
                            $q->where('email', $email);
                        }
                        if (!empty($phone)) {
                            $q->orWhere('phone', $phone);
                        }
                    })->first();

                    if (!$existing) {
                        User::create([
                            'name'            => $u['display_name'] ?: ($u['user_login'] ?: 'WP User'),
                            'email'           => $email ?: ('agent_' . substr(md5($phone ?: Str::random(6)), 0, 8) . '@propertykoi.com'),
                            'phone'           => !empty($phone) ? $phone : null,
                            'whatsapp_number' => $u['whatsapp_number'] ?? (!empty($phone) ? $phone : null),
                            'role'            => in_array($u['role'] ?? '', ['admin', 'agent']) ? $u['role'] : 'agent',
                            'password'        => Hash::make(Str::random(16)),
                            'created_at'      => $u['created_at'] ?? now(),
                        ]);
                        $stats['users_imported']++;
                    }
                }
            }

            // 2. IMPORT PROPERTIES
            if ($scopeProps && !empty($payload['properties'])) {
                foreach ($payload['properties'] as $wpProp) {
                    $propCode = trim($wpProp['property_code'] ?? '');
                    if (empty($propCode)) continue;

                    $existingProp = Property::where('property_code', $propCode)->first();

                    if ($existingProp && $dupAction === 'skip') {
                        $stats['properties_skipped']++;
                        $stats['logs'][] = "⏩ [SKIPPED] #{$propCode} (ইতিমধ্যে ডাটাবেসে বিদ্যমান)";
                        continue;
                    }

                    $propData = [
                        'property_code'      => $propCode,
                        'type'               => $wpProp['type'] ?? 'land',
                        'title'              => $wpProp['title'] ?? 'Untitled Property',
                        'slug'               => $wpProp['slug'] ?? Str::slug($wpProp['title'] ?? 'property-' . $propCode),
                        'description'        => $wpProp['description'] ?? null,
                        'area'               => $wpProp['area'] ?? 'Aftabnagar',
                        'sub_area'           => $wpProp['sub_area'] ?? null,
                        'block'              => $wpProp['block'] ?? null,
                        'sector'             => $wpProp['sector'] ?? null,
                        'road_number'        => $wpProp['road_number'] ?? null,
                        'road_width_ft'      => (int) ($wpProp['road_width_ft'] ?? 20),
                        'plot_number'        => $wpProp['plot_number'] ?? null,
                        'holding_number'     => $wpProp['holding_number'] ?? null,
                        'address_visibility' => is_string($wpProp['address_visibility'] ?? null) ? json_decode($wpProp['address_visibility'], true) : ($wpProp['address_visibility'] ?? null),
                        'facing'             => $wpProp['facing'] ?? null,
                        'status'             => $wpProp['status'] ?? 'live',
                        'is_featured'        => !empty($wpProp['is_featured']) ? 1 : 0,
                        'is_best_deal'       => !empty($wpProp['is_best_deal']) ? 1 : 0,
                        'is_negotiable'      => !empty($wpProp['is_negotiable']) ? 1 : 0,
                        'latitude'           => $wpProp['latitude'] ?? null,
                        'longitude'          => $wpProp['longitude'] ?? null,
                        'google_map_pin_url' => $wpProp['google_map_pin_url'] ?? null,
                        'other_features'     => $wpProp['other_features'] ?? null,
                        'fb_caption'         => $wpProp['fb_caption'] ?? null,
                        'fb_post_id'         => $wpProp['fb_post_id'] ?? null,
                        'views_count'        => (int) ($wpProp['views_count'] ?? 0),
                        'created_at'         => $wpProp['created_at'] ?? now(),
                        'updated_at'         => $wpProp['updated_at'] ?? now(),
                    ];

                    if ($existingProp) {
                        $existingProp->update($propData);
                        $targetProp = $existingProp;
                        $stats['properties_updated']++;
                        $stats['logs'][] = "🔄 [UPDATED] #{$propCode} - {$targetProp->title} (ভিউস: {$targetProp->views_count})";
                    } else {
                        // Ensure unique slug
                        if (Property::where('slug', $propData['slug'])->exists()) {
                            $propData['slug'] .= '-' . strtolower(Str::random(4));
                        }
                        $targetProp = Property::create($propData);
                        $stats['properties_imported']++;
                        $stats['logs'][] = "✅ [IMPORTED] #{$propCode} - {$targetProp->title} (ভিউস: {$targetProp->views_count})";
                    }

                    // Land details
                    $ld = $wpProp['land_details'] ?? $wpProp['land'] ?? null;
                    if (!empty($ld)) {
                        $soilStatus = in_array($ld['soil_filling_status'] ?? '', ['filled', 'partial', 'unfilled']) 
                            ? $ld['soil_filling_status'] 
                            : 'filled';

                        PropertyLandDetail::updateOrCreate(
                            ['property_id' => $targetProp->id],
                            [
                                'katha_size'          => (float) ($ld['katha_size'] ?? 0),
                                'price_total'         => (float) ($ld['price_total'] ?? ($wpProp['price'] ?? 0)),
                                'price_per_katha'     => (float) ($ld['price_per_katha'] ?? 0),
                                'is_corner'           => !empty($ld['is_corner']) ? 1 : 0,
                                'is_park_facing'      => !empty($ld['is_park_facing']) ? 1 : 0,
                                'is_lake_facing'      => !empty($ld['is_lake_facing']) ? 1 : 0,
                                'boundary_wall_done'  => !empty($ld['boundary_wall_done']) ? 1 : 0,
                                'soil_filling_status' => $soilStatus,
                                'demarcation_ready'   => !empty($ld['demarcation_ready']) ? 1 : 0,
                            ]
                        );
                    }

                    // Flat details
                    $fd = $wpProp['flat_details'] ?? $wpProp['flat'] ?? null;
                    if (!empty($fd)) {
                        $compStatus = in_array($fd['completion_status'] ?? '', ['ready', 'ongoing', 'under_construction']) 
                            ? $fd['completion_status'] 
                            : 'ready';

                        PropertyFlatDetail::updateOrCreate(
                            ['property_id' => $targetProp->id],
                            [
                                'developer_name'    => $fd['developer_name'] ?? null,
                                'project_name'      => $fd['project_name'] ?? null,
                                'building_type'     => $fd['building_type'] ?? 'multi_unit',
                                'completion_status' => $compStatus,
                                'handover_date'     => $fd['handover_date'] ?? null,
                                'flat_size_sqft'    => (float) ($fd['flat_size_sqft'] ?? 0),
                                'floor_number'      => (int) ($fd['floor_number'] ?? 1),
                                'total_floors'      => (int) ($fd['total_floors'] ?? 6),
                                'bedrooms'          => (int) ($fd['bedrooms'] ?? 3),
                                'bathrooms'         => (int) ($fd['bathrooms'] ?? 3),
                                'balconies'         => (int) ($fd['balconies'] ?? 2),
                                'price_total'       => (float) ($fd['price_total'] ?? ($wpProp['price'] ?? 0)),
                                'price_per_sqft'    => (float) ($fd['price_per_sqft'] ?? 0),
                                'parking_available' => !empty($fd['parking_available']) ? 1 : 0,
                                'garage_price'      => (float) ($fd['garage_price'] ?? 0),
                                'utility_price'     => (float) ($fd['utility_price'] ?? 0),
                                'down_payment'      => $fd['down_payment'] ?? null,
                                'has_lift'          => !empty($fd['has_lift']) ? 1 : 0,
                                'has_generator'     => !empty($fd['has_generator']) ? 1 : 0,
                                'has_substation'    => !empty($fd['has_substation']) ? 1 : 0,
                                'has_gas'           => !empty($fd['has_gas']) ? 1 : 0,
                                'land_share_size'   => $fd['land_share_size'] ?? null,
                            ]
                        );
                    }

                    // Commercial details
                    $cd = $wpProp['commercial_details'] ?? $wpProp['commercial'] ?? null;
                    if (!empty($cd)) {
                        $spaceType = in_array($cd['space_type'] ?? '', ['shop', 'office', 'showroom', 'warehouse', 'floor']) 
                            ? $cd['space_type'] 
                            : 'shop';
                        $rentStatus = in_array($cd['current_rent_status'] ?? '', ['vacant', 'rented']) 
                            ? $cd['current_rent_status'] 
                            : 'vacant';

                        PropertyCommercialDetail::updateOrCreate(
                            ['property_id' => $targetProp->id],
                            [
                                'space_type'        => $spaceType,
                                'size_sqft'         => (float) ($cd['size_sqft'] ?? 0),
                                'floor_level'       => $cd['floor_level'] ?? 'Ground',
                                'price_total'       => (float) ($cd['price_total'] ?? ($wpProp['price'] ?? 0)),
                                'price_per_sqft'    => (float) ($cd['price_per_sqft'] ?? 0),
                                'monthly_rent'      => $cd['monthly_rent'] ?? null,
                                'current_rent_status'=> $rentStatus,
                            ]
                        );
                    }

                    // Land share details
                    $sd = $wpProp['share_details'] ?? $wpProp['share'] ?? $wpProp['land_share'] ?? null;
                    if (!empty($sd)) {
                        PropertyShareDetail::updateOrCreate(
                            ['property_id' => $targetProp->id],
                            [
                                'total_shares'        => $sd['total_shares'] ?? 1,
                                'available_shares'    => $sd['available_shares'] ?? 1,
                                'share_price'         => $sd['share_price'] ?? 0,
                                'land_size_katha'     => $sd['land_size_katha'] ?? 0,
                                'proposed_flat_size'  => $sd['proposed_flat_size'] ?? 0,
                                'estimated_handover'  => $sd['estimated_handover'] ?? null,
                            ]
                        );
                    }

                    // Direct sources (owners & agents)
                    $sources = $wpProp['direct_sources'] ?? [];
                    if (empty($sources) && !empty($wpProp['primary_source'])) {
                        $sources[] = $wpProp['primary_source'];
                    }

                    if (!empty($sources)) {
                        foreach ($sources as $src) {
                            PropertyDirectSource::updateOrCreate(
                                [
                                    'property_id' => $targetProp->id,
                                    'source_type' => $src['source_type'] ?? 'direct_owner',
                                ],
                                [
                                    'owner_name'         => $src['owner_name'] ?? ($src['display_name'] ?? 'Property Owner'),
                                    'owner_phone'        => $src['owner_phone'] ?? ($src['contact_phone'] ?? ''),
                                    'owner_whatsapp'     => $src['owner_whatsapp'] ?? ($src['contact_whatsapp'] ?? null),
                                    'owner_email'        => $src['owner_email'] ?? ($src['user_email'] ?? null),
                                    'asking_price'       => $src['asking_price'] ?? ($wpProp['price'] ?? 0),
                                    'commission_agreed'  => $src['commission_agreed'] ?? 0,
                                    'notes'              => $src['notes'] ?? ($src['relationship_declaration'] ?? null),
                                    'is_verified'        => !empty($src['is_verified']) ? 1 : 0,
                                ]
                            );
                        }
                    }

                    // Media (Photos / Videos)
                    $mediaList = $wpProp['media'] ?? [];
                    if (empty($mediaList) && !empty($wpProp['cover_url'])) {
                        $cover = $wpProp['cover_url'];
                        if (!str_contains($cover, 'og-land.jpg') && !str_contains($cover, 'og-flat.jpg') && !str_contains($cover, 'og-default.jpg')) {
                            $mediaList[] = [
                                'file_path'   => $cover,
                                'media_type'  => 'photo',
                                'caption'     => 'Featured Cover Photo',
                                'sort_order'  => 0,
                                'is_featured' => 1,
                            ];
                        }
                    }

                    if (!empty($mediaList)) {
                        foreach ($mediaList as $mediaItem) {
                            $filePath = $mediaItem['file_path'] ?? ($mediaItem['url'] ?? '');
                            if (empty($filePath)) continue;

                            // Handle Cloudinary upload if requested and image is local WP URL
                            if ($syncCloudinary && CloudinaryService::isConfigured() && !str_starts_with($filePath, 'https://res.cloudinary.com/')) {
                                $cldUrl = $this->migrateImageToCloudinary($filePath, $baseUrl);
                                if ($cldUrl) {
                                    $filePath = $cldUrl;
                                    $stats['media_uploaded']++;
                                }
                            }

                            PropertyMedia::firstOrCreate(
                                [
                                    'property_id' => $targetProp->id,
                                    'file_path'   => $filePath,
                                ],
                                [
                                    'media_type'  => $mediaItem['media_type'] ?? 'photo',
                                    'caption'     => $mediaItem['caption'] ?? null,
                                    'sort_order'  => (int) ($mediaItem['sort_order'] ?? 0),
                                    'is_featured' => !empty($mediaItem['is_featured']) ? 1 : 0,
                                ]
                            );
                        }
                    }

                    // Verification
                    $verif = $wpProp['verification'] ?? $wpProp['verifications'] ?? null;
                    if (!empty($verif)) {
                        PropertyVerification::updateOrCreate(
                            ['property_id' => $targetProp->id],
                            [
                                'owner_identity_checked'   => !empty($verif['owner_identity_checked']) ? 1 : 0,
                                'location_checked'         => !empty($verif['location_checked']) ? 1 : 0,
                                'basic_info_checked'       => !empty($verif['basic_info_checked']) ? 1 : 0,
                                'basic_documents_checked'  => !empty($verif['basic_documents_checked']) ? 1 : 0,
                                'disclaimer_acknowledged'  => !empty($verif['disclaimer_acknowledged']) ? 1 : 0,
                                'verified_at'              => $verif['verified_at'] ?? null,
                                'audit_notes'              => $verif['audit_notes'] ?? null,
                            ]
                        );
                    }
                }
            }

            // 3. IMPORT LEADS & VISITS
            if ($scopeLeads && !empty($payload['leads'])) {
                foreach ($payload['leads'] as $wpLead) {
                    $phone = trim($wpLead['buyer_phone'] ?? '');
                    if (empty($phone)) continue;

                    Lead::firstOrCreate(
                        [
                            'buyer_phone' => $phone,
                            'created_at'  => $wpLead['created_at'] ?? now(),
                        ],
                        [
                            'buyer_name'   => $wpLead['buyer_name'] ?? 'Visitor',
                            'buyer_email'  => $wpLead['buyer_email'] ?? null,
                            'enquiry_type' => $wpLead['enquiry_type'] ?? 'general',
                            'stage'        => $wpLead['stage'] ?? 'new',
                            'message'      => $wpLead['message'] ?? null,
                        ]
                    );
                    $stats['leads_imported']++;
                }
            }

            // 4. IMPORT BUYER DEMANDS (CHAHIDA)
            if ($scopeDemands && !empty($payload['demands'])) {
                foreach ($payload['demands'] as $dem) {
                    $code = $dem['requirement_code'] ?? ('REQ-' . strtoupper(Str::random(6)));
                    BuyerRequirement::firstOrCreate(
                        ['requirement_code' => $code],
                        [
                            'buyer_name'        => $dem['buyer_name'] ?? 'Buyer',
                            'buyer_phone'       => $dem['buyer_phone'] ?? '',
                            'buyer_email'       => $dem['buyer_email'] ?? null,
                            'property_type'     => $dem['property_type'] ?? 'land',
                            'preferred_blocks'  => $dem['preferred_blocks'] ?? null,
                            'budget_min'        => $dem['budget_min'] ?? null,
                            'budget_max'        => $dem['budget_max'] ?? null,
                            'size_min'          => $dem['size_min'] ?? null,
                            'size_max'          => $dem['size_max'] ?? null,
                            'status'            => $dem['status'] ?? 'active',
                            'created_at'        => $dem['created_at'] ?? now(),
                        ]
                    );
                    $stats['demands_imported']++;
                }
            }

            // 5. IMPORT REVIEWS
            if ($scopeReviews && !empty($payload['reviews'])) {
                foreach ($payload['reviews'] as $rev) {
                    Review::firstOrCreate(
                        [
                            'reviewer_phone' => $rev['reviewer_phone'] ?? '',
                            'created_at'     => $rev['created_at'] ?? now(),
                        ],
                        [
                            'reviewer_name'  => $rev['reviewer_name'] ?? 'Customer',
                            'rating'         => (int) ($rev['rating'] ?? 5),
                            'review_text'    => $rev['review_text'] ?? '',
                            'target_type'    => $rev['target_type'] ?? 'owner',
                            'status'         => $rev['status'] ?? 'approved',
                        ]
                    );
                    $stats['reviews_imported']++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "ইমপোর্ট সফলভাবে সম্পন্ন হয়েছে! মোট প্রপার্টি: {$stats['properties_imported']}টি নতুন যুক্ত, {$stats['properties_updated']}টি আপডেট, {$stats['properties_skipped']}টি স্কিপ করা হয়েছে।",
                'stats'   => $stats,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('WpLiveImport failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'ইমপোর্ট করার সময় এরর হয়েছে: ' . $e->getMessage(),
                'stats'   => $stats,
            ];
        }
    }

    /**
     * Download an image, convert to WebP, and upload to Cloudinary
     */
    protected function migrateImageToCloudinary(string $rawUrl, string $baseUrl): ?string
    {
        try {
            $imageUrl = $rawUrl;
            if (str_starts_with($rawUrl, '/')) {
                $imageUrl = $baseUrl . $rawUrl;
            }

            $imgData = @file_get_contents($imageUrl);
            if (!$imgData) return null;

            $tmpFile = tempnam(sys_get_temp_dir(), 'wp_img_') . '.tmp';
            file_put_contents($tmpFile, $imgData);

            $webpPath = CloudinaryService::ensureWebp($tmpFile);
            $cldRes = CloudinaryService::upload($webpPath, 'aftabnagar_marketplace');

            @unlink($tmpFile);
            if ($webpPath !== $tmpFile) {
                @unlink($webpPath);
            }

            return $cldRes['secure_url'] ?? null;
        } catch (\Throwable $e) {
            Log::warning("Failed to migrate image {$rawUrl} to Cloudinary: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback to fetching live properties directly from WordPress public AJAX endpoints
     */
    protected function fetchViaLiveAjax(string $baseUrl, string $username, string $password, int $limit): array
    {
        $allProps = [];
        $actions = ['adm_filter_land', 'adm_filter_flat', 'adm_filter_land_share', 'adm_filter_commercial'];

        foreach ($actions as $action) {
            if (count($allProps) >= $limit) break;

            $page = 1;
            while (count($allProps) < $limit) {
                try {
                    $response = Http::timeout(25)
                        ->get("{$baseUrl}/wp-admin/admin-ajax.php", [
                            'action'   => $action,
                            'page'     => $page,
                            'per_page' => 24,
                        ]);

                    if (!$response->successful()) break;

                    $json = $response->json();
                    $props = $json['data']['properties'] ?? [];
                    if (empty($props)) break;

                    foreach ($props as $item) {
                        $allProps[] = $item;
                        if (count($allProps) >= $limit) break;
                    }

                    $totalPages = (int) ($json['data']['total_pages'] ?? 1);
                    if ($page >= $totalPages) break;
                    $page++;
                } catch (\Throwable $e) {
                    Log::warning("WpLiveImportService: AJAX fetch error on {$action} p{$page}: " . $e->getMessage());
                    break;
                }
            }
        }

        if (empty($allProps)) {
            return [
                'success' => false,
                'message' => 'লাইভ সাইট থেকে কোনো প্রোপার্টি ডাটা পাওয়া যায়নি। সাইটের সংযোগ চেক করুন।',
            ];
        }

        // Extract users from direct_sources & primary_source
        $users = [];
        $seenUserKeys = [];
        foreach ($allProps as $p) {
            $sources = $p['direct_sources'] ?? [];
            if (!empty($p['primary_source'])) {
                $sources[] = $p['primary_source'];
            }

            foreach ($sources as $s) {
                $name = trim($s['owner_name'] ?? ($s['display_name'] ?? ''));
                $phone = trim($s['owner_phone'] ?? ($s['contact_phone'] ?? ''));
                $email = trim($s['user_email'] ?? ($s['owner_email'] ?? ''));

                $userKey = $email ?: ($phone ?: $name);
                if (!empty($userKey) && !isset($seenUserKeys[$userKey])) {
                    $seenUserKeys[$userKey] = true;
                    $users[] = [
                        'user_login'      => Str::slug($name ?: 'user') ?: 'user_' . rand(100, 999),
                        'display_name'    => $name ?: 'Live User',
                        'user_email'      => $email ?: (Str::slug($name ?: 'user') . '_' . rand(10, 99) . '@propertykoi.com'),
                        'phone'           => $phone,
                        'whatsapp_number' => $s['contact_whatsapp'] ?? ($s['owner_whatsapp'] ?? $phone),
                        'role'            => 'agent',
                        'created_at'      => $s['created_at'] ?? now()->toDateTimeString(),
                    ];
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'properties' => $allProps,
                'users'      => $users,
                'leads'      => [],
                'demands'    => [],
                'reviews'    => [],
            ]
        ];
    }

    /**
     * HTTP helper with Basic Authentication
     */
    protected function sendRequest(string $url, string $username, string $password): array
    {
        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(25)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'PropertyKoi-Laravel-Importer/1.0',
                ])
                ->get($url);

            $status = $response->status();
            $body = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $status,
                    'data' => $body,
                    'site_name' => $body['site_name'] ?? parse_url($url, PHP_URL_HOST),
                    'site_url' => $body['site_url'] ?? $url,
                    'authenticated_user' => $body['authenticated_user'] ?? $username,
                    'user_display_name' => $body['user_display_name'] ?? $username,
                    'wp_version' => $body['wp_version'] ?? 'N/A',
                    'counts' => $body['counts'] ?? [],
                ];
            }

            return [
                'success' => false,
                'status_code' => $status,
                'message' => $body['message'] ?? ($body['error'] ?? "HTTP error {$status}"),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status_code' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
}
