<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyDirectSource;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyMedia;
use App\Models\PropertyShareDetail;
use App\Models\PropertyVerification;
use App\Models\User;
use App\Services\BuyerDemandMatchService;
use App\Services\FacebookAutoPostService;
use App\Services\PropertySearchService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with(['media', 'creator', 'verification', 'directSources', 'landDetail', 'flatDetail', 'commercialDetail', 'shareDetail']);

        $filterCode = trim((string) ($request->input('property_code') ?: $request->input('search', '')));
        $filterInserterPhone = trim((string) $request->input('inserter_phone', ''));
        $filterArea = trim((string) ($request->input('prop_area') ?: $request->input('area', '')));
        $filterType = trim((string) ($request->input('prop_type') ?: $request->input('type', '')));
        $filterStatus = trim((string) ($request->input('prop_status') ?: $request->input('status', '')));

        $filterSource = trim((string) ($request->input('prop_source') ?: $request->input('source', '')));
        if ($request->input('filter') === 'unassigned') {
            $filterSource = 'unassigned';
        }

        // 1. Property Code or Search
        if ($request->filled('property_code')) {
            $query->where('property_code', 'like', "%{$filterCode}%");
        } elseif ($request->filled('search')) {
            PropertySearchService::applySearchQuery($query, $filterCode, true);
        }

        // 2. Inserter Phone
        if (!empty($filterInserterPhone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $filterInserterPhone);
            $last10 = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;

            $query->where(function ($q) use ($filterInserterPhone, $cleanPhone, $last10) {
                $q->whereHas('creator', function ($uq) use ($filterInserterPhone, $cleanPhone, $last10) {
                    $uq->where('phone', 'like', "%{$filterInserterPhone}%")
                       ->orWhere('whatsapp_number', 'like', "%{$filterInserterPhone}%");
                    if (!empty($cleanPhone)) {
                        $uq->orWhere('phone', 'like', "%{$cleanPhone}%")
                           ->orWhere('whatsapp_number', 'like', "%{$cleanPhone}%");
                    }
                    if (!empty($last10)) {
                        $uq->orWhere('phone', 'like', "%{$last10}%")
                           ->orWhere('whatsapp_number', 'like', "%{$last10}%");
                    }
                })
                ->orWhereHas('directSources', function ($sq) use ($filterInserterPhone, $cleanPhone, $last10) {
                    $sq->where('owner_phone', 'like', "%{$filterInserterPhone}%");
                    if (!empty($cleanPhone)) {
                        $sq->orWhere('owner_phone', 'like', "%{$cleanPhone}%");
                    }
                    if (!empty($last10)) {
                        $sq->orWhere('owner_phone', 'like', "%{$last10}%");
                    }
                    $sq->orWhereHas('sourceUser', function ($suq) use ($filterInserterPhone, $cleanPhone, $last10) {
                        $suq->where('phone', 'like', "%{$filterInserterPhone}%")
                            ->orWhere('whatsapp_number', 'like', "%{$filterInserterPhone}%");
                        if (!empty($cleanPhone)) {
                            $suq->orWhere('phone', 'like', "%{$cleanPhone}%")
                                ->orWhere('whatsapp_number', 'like', "%{$cleanPhone}%");
                        }
                        if (!empty($last10)) {
                            $suq->orWhere('phone', 'like', "%{$last10}%")
                                ->orWhere('whatsapp_number', 'like', "%{$last10}%");
                        }
                    });
                });
            });
        }

        // 3. Area
        if (!empty($filterArea) && $filterArea !== 'all') {
            PropertySearchService::applyAreaFilter($query, $filterArea);
        }

        // 4. Property Type
        if (!empty($filterType)) {
            $query->where('type', $filterType);
        }

        // 5. Status
        if (!empty($filterStatus) && $filterStatus !== 'any') {
            if ($filterStatus === 'pending' || $filterStatus === 'pending_verification') {
                $query->whereIn('status', ['pending', 'pending_verification']);
            } else {
                $query->where('status', $filterStatus);
            }
        }

        // 6. Data Source / Insert Source
        if (!empty($filterSource)) {
            if (in_array($filterSource, ['sheet_import', 'sheet', 'bulk'], true)) {
                $query->whereHas('directSources', function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('relationship_declaration', 'like', '%Google Sheets%')
                            ->orWhere('relationship_declaration', 'like', '%Sheets sync%')
                            ->orWhere('relationship_declaration', 'like', '%bulk%')
                            ->orWhere('relationship_declaration', 'like', '%import%');
                    });
                });
            } elseif ($filterSource === 'direct') {
                $query->whereDoesntHave('directSources', function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('relationship_declaration', 'like', '%Google Sheets%')
                            ->orWhere('relationship_declaration', 'like', '%Sheets sync%')
                            ->orWhere('relationship_declaration', 'like', '%bulk%')
                            ->orWhere('relationship_declaration', 'like', '%import%');
                    });
                });
            } elseif (in_array($filterSource, ['unassigned', 'no_inserter', 'parentless'], true)) {
                $query->where(function ($q) {
                    $q->whereNull('created_by_user_id')
                      ->orWhere('created_by_user_id', 0)
                      ->orWhereDoesntHave('creator');
                });
            }
        }

        $properties = $query->latest()->paginate(20)->withQueryString();
        $agents = User::whereIn('role', ['agent', 'owner'])->orderBy('name')->get();

        // Available areas
        $availableAreas = Property::whereNotNull('area')
            ->where('area', '!=', '')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->toArray();

        if (empty($availableAreas)) {
            $availableAreas = ['Aftabnagar', 'Banasree', 'Purbachal', 'Bashundhara'];
        }

        // Filter status check
        $isFiltered = !empty($filterCode)
            || !empty($filterInserterPhone)
            || !empty($filterArea)
            || !empty($filterType)
            || (!empty($filterStatus) && $filterStatus !== 'any')
            || !empty($filterSource);

        // Active filter summary text
        $activeFilterParts = [];
        if (!empty($filterSource)) {
            $srcLabels = [
                'unassigned'   => '⚠️ আন-অ্যাসাইনড ইনসার্টার',
                'sheet_import' => '📊 গুগল শিট',
                'direct'       => '👤 ডিরেক্ট লিস্টিং',
            ];
            $activeFilterParts[] = $srcLabels[$filterSource] ?? $filterSource;
        }
        if (!empty($filterCode)) {
            $activeFilterParts[] = 'কোড: ' . $filterCode;
        }
        if (!empty($filterInserterPhone)) {
            $activeFilterParts[] = 'ইনসার্টার মোবাইল: ' . $filterInserterPhone;
        }
        if (!empty($filterArea)) {
            $activeFilterParts[] = 'এলাকা: ' . $filterArea;
        }
        if (!empty($filterType)) {
            $typeNames = [
                'land'       => 'জমি',
                'flat'       => 'ফ্ল্যাট',
                'land_share' => 'ল্যান্ড শেয়ার',
                'commercial' => 'কমার্শিয়াল'
            ];
            $activeFilterParts[] = 'টাইপ: ' . ($typeNames[$filterType] ?? $filterType);
        }
        if (!empty($filterStatus) && $filterStatus !== 'any') {
            $activeFilterParts[] = 'স্ট্যাটাস: ' . $filterStatus;
        }
        $filterSummary = implode(', ', $activeFilterParts);

        $stats = [
            'total'      => Property::count(),
            'live'       => Property::where('status', 'live')->count(),
            'pending'    => Property::whereIn('status', ['pending', 'pending_verification'])->count(),
            'booked'     => Property::where('status', 'booked')->count(),
            'sold'       => Property::where('status', 'sold')->count(),
            'archived'   => Property::where('status', 'archived')->count(),
            'unassigned' => Property::where(fn($q) => $q->whereNull('created_by_user_id')->orWhere('created_by_user_id', 0))->count(),
        ];

        return view('admin.properties.index', compact(
            'properties',
            'agents',
            'stats',
            'availableAreas',
            'isFiltered',
            'filterCode',
            'filterInserterPhone',
            'filterArea',
            'filterType',
            'filterStatus',
            'filterSource',
            'filterSummary'
        ));
    }

    public function directory(Request $request)
    {
        $query = Property::with(['media', 'creator', 'verification', 'directSources', 'landDetail', 'flatDetail', 'commercialDetail', 'shareDetail']);

        $filterCode     = trim((string) ($request->input('property_code') ?: $request->input('search', '')));
        $filterKeyword  = trim((string) $request->input('keyword', ''));
        $filterNote     = trim((string) $request->input('note', ''));
        $filterArea     = trim((string) ($request->input('area') ?: ''));

        // Multiselect Types: supports array (types[] or type[]) and comma-separated string
        $rawTypes = $request->input('types', $request->input('type', []));
        $filterTypes = is_array($rawTypes) ? $rawTypes : explode(',', (string)$rawTypes);
        $filterTypes = array_values(array_filter(array_map('trim', $filterTypes), fn($t) => !empty($t) && $t !== 'all'));

        // Multiselect Statuses: supports array (statuses[] or status[]) and comma-separated string
        $rawStatuses = $request->input('statuses', $request->input('status', []));
        $filterStatuses = is_array($rawStatuses) ? $rawStatuses : explode(',', (string)$rawStatuses);
        $filterStatuses = array_values(array_filter(array_map('trim', $filterStatuses), fn($s) => !empty($s) && $s !== 'all'));

        // Status Filter
        if (!empty($filterStatuses)) {
            $expandedStatuses = $filterStatuses;
            if (in_array('pending', $expandedStatuses, true) && !in_array('pending_verification', $expandedStatuses, true)) {
                $expandedStatuses[] = 'pending_verification';
            }
            $query->whereIn('status', $expandedStatuses);
        } else {
            // Default directory scope: draft / non-live
            $query->where(function ($q) {
                $q->where('status', 'draft')
                  ->orWhere('status', '!=', 'live');
            });
        }

        // Type Filter
        if (!empty($filterTypes)) {
            $query->whereIn('type', $filterTypes);
        }

        // Area Filter
        if (!empty($filterArea) && $filterArea !== 'all') {
            PropertySearchService::applyAreaFilter($query, $filterArea);
        }

        // Main Property Search (bilingual)
        if ($request->filled('property_code')) {
            $query->where('property_code', 'like', "%{$filterCode}%");
        } elseif ($request->filled('search')) {
            PropertySearchService::applySearchQuery($query, $filterCode, true);
        }

        // Dedicated Keyword / Tag search
        if (!empty($filterKeyword)) {
            $escaped = trim(json_encode($filterKeyword, JSON_UNESCAPED_SLASHES), '"');
            $query->where(function ($q) use ($filterKeyword, $escaped) {
                $q->where('hidden_tags', 'like', "%{$filterKeyword}%")
                  ->orWhere('hidden_tags', 'like', "%{$escaped}%")
                  ->orWhere('other_features', 'like', "%{$filterKeyword}%")
                  ->orWhere('custom_fields', 'like', "%{$filterKeyword}%")
                  ->orWhere('custom_fields', 'like', "%{$escaped}%");
            });
        }

        // Dedicated Hidden Note text search
        if (!empty($filterNote)) {
            $query->where('hidden_note', 'like', "%{$filterNote}%");
        }

        $properties = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_private' => Property::where(fn($q) => $q->where('status', 'draft')->orWhere('status', '!=', 'live'))->count(),
            'draft'         => Property::where('status', 'draft')->count(),
            'pending'       => Property::whereIn('status', ['pending', 'pending_verification'])->count(),
            'sold'          => Property::where('status', 'sold')->count(),
            'booked'        => Property::where('status', 'booked')->count(),
            'plots'         => Property::where('type', 'land')->where('status', '!=', 'live')->count(),
            'flats'         => Property::where('type', 'flat')->where('status', '!=', 'live')->count(),
            'commercial'    => Property::where('type', 'commercial')->where('status', '!=', 'live')->count(),
            'share'         => Property::where('type', 'land_share')->where('status', '!=', 'live')->count(),
        ];

        $filterType = implode(',', $filterTypes);
        $filterStatus = implode(',', $filterStatuses);

        return view('admin.properties.directory', compact(
            'properties',
            'stats',
            'filterCode',
            'filterKeyword',
            'filterNote',
            'filterTypes',
            'filterStatuses',
            'filterType',
            'filterStatus',
            'filterArea'
        ));
    }

    public function updateHiddenNote(Request $request, Property $property)
    {
        $note = $request->input('hidden_note');
        $tags = PropertySearchService::extractKeywordsFromNote($note);

        $property->hidden_note = $note;
        $property->hidden_tags = $tags;
        $property->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'     => true,
                'message'     => 'গোপন নোট সফলভাবে সংরক্ষিত হয়েছে।',
                'hidden_note' => $property->hidden_note,
                'hidden_tags' => $property->hidden_tags ?: [],
            ]);
        }

        return back()->with('success', 'গোপন নোট ও কিওয়ার্ড সফলভাবে সংরক্ষিত হয়েছে।');
    }

    public function quickView(Property $property)
    {
        $property->load([
            'media',
            'creator.agentProfile',
            'verification',
            'directSources.sourceUser.agentProfile',
            'landDetail',
            'flatDetail',
            'commercialDetail',
            'shareDetail',
        ]);

        return view('admin.properties.partials.quick-view', compact('property'));
    }

    public function create(Request $request)
    {
        return redirect()->route('property.create', $request->query());
    }

    public function store(Request $request)
    {
        $ownerPhone = $request->input('owner_phone') ?: ($request->input('source_phone') ?: $request->input('source_whatsapp'));
        if (!empty($ownerPhone)) {
            $request->merge(['owner_phone' => $ownerPhone]);
        }

        // Support saving directly to private directory / offline vault
        if ($request->input('destination') === 'directory' || $request->input('save_mode') === 'directory') {
            $request->merge(['status' => 'draft']);
        } elseif (!$request->filled('status')) {
            $request->merge(['status' => 'live']);
        }

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'type'                => 'required|in:land,flat,commercial,land_share',
            'area'                => 'nullable|string|max:100',
            'block'               => 'nullable|string|max:50',
            'plot_number'         => 'nullable|string|max:50',
            'road_number'         => 'nullable|string|max:50',
            'facing'              => 'nullable|string|max:50',
            'size'                => 'nullable|numeric',
            'asking_price'        => 'nullable|numeric',
            'price_per_katha'     => 'nullable|numeric',
            'is_negotiable'       => 'nullable|boolean',
            'is_featured'         => 'nullable|boolean',
            'status'              => 'required|in:live,pending,pending_verification,sold,booked,draft,archived,verified,rejected',
            'created_by_user_id'  => 'nullable|exists:users,id',
            'description'         => 'nullable|string',
            'notes'               => 'nullable|string',
            'fb_caption'          => 'nullable|string',
            'owner_name'          => 'nullable|string|max:255',
            'owner_phone'         => 'required|string|max:50',
            'source_type'         => 'nullable|string|max:50',
        ], [
            'owner_phone.required' => 'মালিকের ফোন নম্বর / WhatsApp প্রদান করা আবশ্যক।',
        ]);

        $code = 'AP-' . strtoupper(Str::random(6));
        $slug = Str::slug($validated['title']) . '-' . strtolower(Str::random(5));

        $property = Property::create(array_merge($validated, [
            'property_code' => $code,
            'slug'          => $slug,
            'is_negotiable' => $request->boolean('is_negotiable'),
            'is_featured'   => $request->boolean('is_featured'),
            'address_visibility' => $request->input('address_visibility', [
                'block' => true,
                'sector' => true,
                'road_number' => true,
                'road_width_ft' => true,
                'plot_number' => true,
                'holding_number' => true,
            ]),
        ]));

        if ($request->has('hidden_note')) {
            $hiddenNote = $request->input('hidden_note');
            $property->hidden_note = $hiddenNote;
            $property->hidden_tags = PropertySearchService::extractKeywordsFromNote($hiddenNote);
            $property->save();
        }

        // Initialize verification record
        PropertyVerification::create([
            'property_id'               => $property->id,
            'owner_identity_checked'    => $request->boolean('checklist_owner_identity'),
            'location_checked'          => $request->boolean('checklist_location'),
            'basic_info_checked'        => $request->boolean('checklist_basic_info'),
            'basic_documents_checked'   => $request->boolean('checklist_documents'),
            'audit_notes'               => $request->input('verification_notes'),
            'verified_at'               => ($property->status === 'live' || $property->status === 'verified') ? now() : null,
        ]);

        // Details by type
        $this->saveTypeDetails($property, $request);

        // Direct Sources / Owner Contact Information
        $ownerPhone = $request->input('owner_phone') ?: ($request->input('source_phone') ?: $request->input('source_whatsapp'));
        $ownerName = $request->input('owner_name') ?: $request->input('source_name');
        if (!empty($ownerPhone) || !empty($ownerName)) {
            $sourceType = $request->input('source_type', 'direct_owner');
            $relDecl = match($sourceType) {
                'direct_owner', 'owner' => 'সরাসরি প্রপার্টির মালিক (Direct Owner)',
                'direct_agent', 'agent' => 'এজেন্ট / মিডিয়া (Agent)',
                'developer'             => 'ডেভেলপার (Developer)',
                default                 => 'সরাসরি প্রপার্টির মালিক (Direct Owner)',
            };

            PropertyDirectSource::create([
                'property_id'              => $property->id,
                'source_user_id'           => $property->created_by_user_id ?: auth()->id(),
                'source_type'              => $sourceType,
                'relationship_declaration' => $relDecl,
                'owner_name'               => $ownerName,
                'owner_phone'              => $ownerPhone,
                'asking_price'             => $request->input('asking_price'),
                'is_primary_source'        => true,
                'verification_status'      => 'verified',
            ]);
        }

        // Auto post to FB and trigger Buyer Demand match if live
        if ($property->status === 'live') {
            SeoService::pingIndexNow($property);
            BuyerDemandMatchService::matchNewProperty($property);
            if (\App\Models\Setting::isTrue('adm_fb_autopost_enabled') || \App\Models\Setting::isTrue('fb_autopost_enabled')) {
                FacebookAutoPostService::postProperty($property);
            }
        }

        if ($property->status === 'draft' || $request->input('destination') === 'directory') {
            return redirect()->route('admin.properties.directory')->with('success', "প্রপার্টি #{$property->property_code} সফলভাবে প্রাইভেট ডিরেক্টরিতে (ডাটাবেস ভল্ট) সংরক্ষিত হয়েছে। এটি জনসাধারণের কাছে দৃশ্যমান নয়।");
        }

        return redirect()->route('admin.properties.index')->with('success', "প্রপার্টি #{$property->property_code} সফলভাবে তৈরি হয়েছে!");
    }

    public function publishToLive(Request $request, Property $property)
    {
        // 1. Ensure SEO-optimized unique slug
        if (empty($property->slug)) {
            $property->slug = Str::slug($property->title) . '-' . strtolower($property->property_code ?: Str::random(5));
        }

        // 2. Update status & timestamp
        $property->status = 'live';
        $property->last_verified_at = now();
        $property->save();

        // 3. Mark verification checklist as fully verified
        PropertyVerification::updateOrCreate(
            ['property_id' => $property->id],
            [
                'verification_status'    => 'verified',
                'verified_at'            => now(),
                'owner_identity_checked' => true,
                'location_checked'       => true,
                'basic_info_checked'     => true,
                'basic_documents_checked'=> true,
            ]
        );

        // 4. Instant search engine indexing ping via IndexNow
        $indexNowResult = SeoService::pingIndexNow($property);

        // 5. Automated Buyer Demand Matching & notifications
        $demandMatches = BuyerDemandMatchService::matchNewProperty($property);

        // 6. Facebook auto-post if enabled
        if (\App\Models\Setting::isTrue('adm_fb_autopost_enabled') || \App\Models\Setting::isTrue('fb_autopost_enabled')) {
            FacebookAutoPostService::postProperty($property);
        }

        $matchedCount = $demandMatches['matched_demands'] ?? 0;
        $publicUrl = route('property.show', $property->slug);

        $msg = "প্রপার্টি #{$property->property_code} সফলভাবে লাইভ সাইটে পাবলিশ করা হয়েছে! SEO & AEO মেটাডাটা, Schema.org LD-JSON এবং AI FAQ প্রস্তুত হয়েছে।";
        if ($matchedCount > 0) {
            $msg .= " এবং {$matchedCount} জন আগ্রহী বায়ারের সাথে ম্যাচ করে নোটিফিকেশন পাঠানো হয়েছে!";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'         => true,
                'property_id'     => $property->id,
                'public_url'      => $publicUrl,
                'matched_demands' => $matchedCount,
                'message'         => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function unpublishToDirectory(Request $request, Property $property)
    {
        $property->status = 'draft';
        $property->save();

        $msg = "প্রপার্টি #{$property->property_code} সফলভাবে প্রাইভেট ডিরেক্টরিতে সরিয়ে নেওয়া হয়েছে। এটি এখন আর জনসাধারণের কাছে দৃশ্যমান নয়।";

        if ($request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'property_id' => $property->id,
                'message'     => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function edit(Property $property)
    {
        $property->load(['media', 'verification', 'directSources', 'landDetail', 'flatDetail', 'commercialDetail', 'shareDetail']);
        $agents = User::whereIn('role', ['agent', 'owner'])->orderBy('name')->get();
        return view('admin.properties.edit', compact('property', 'agents'));
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'type'                => 'required|in:land,flat,commercial,land_share',
            'area'                => 'nullable|string|max:100',
            'block'               => 'nullable|string|max:50',
            'plot_number'         => 'nullable|string|max:50',
            'road_number'         => 'nullable|string|max:50',
            'facing'              => 'nullable|string|max:50',
            'size'                => 'nullable|numeric',
            'asking_price'        => 'nullable|numeric',
            'price_per_katha'     => 'nullable|numeric',
            'status'              => 'required|in:live,pending,pending_verification,sold,booked,draft,archived,verified,rejected',
            'created_by_user_id'  => 'nullable|exists:users,id',
            'description'         => 'nullable|string',
            'notes'               => 'nullable|string',
            'fb_caption'          => 'nullable|string',
            'owner_name'          => 'nullable|string|max:255',
            'owner_phone'         => 'nullable|string|max:50',
            'source_type'         => 'nullable|string|max:50',
        ]);

        $prevStatus = $property->status;

        $property->update(array_merge($validated, [
            'is_negotiable' => $request->boolean('is_negotiable'),
            'is_featured'   => $request->boolean('is_featured'),
            'address_visibility' => $request->input('address_visibility', $property->address_visibility ?? [
                'block' => true,
                'sector' => true,
                'road_number' => true,
                'road_width_ft' => true,
                'plot_number' => true,
                'holding_number' => true,
            ]),
        ]));

        if ($request->has('hidden_note')) {
            $hiddenNote = $request->input('hidden_note');
            $property->hidden_note = $hiddenNote;
            $property->hidden_tags = PropertySearchService::extractKeywordsFromNote($hiddenNote);
            $property->save();
        }

        $this->saveTypeDetails($property, $request);

        // Direct Source / WhatsApp Contact Update
        $ownerPhone = $request->input('owner_phone') ?: ($request->input('source_phone') ?: $request->input('source_whatsapp'));
        $ownerName = $request->input('owner_name') ?: $request->input('source_name');
        if (!empty($ownerPhone) || !empty($ownerName)) {
            $primarySource = $property->directSources()->where('is_primary_source', true)->first()
                ?? $property->directSources()->first();

            $sourceType = $request->input('source_type', $primarySource?->source_type ?? 'direct_owner');
            $relDecl = match($sourceType) {
                'direct_owner', 'owner' => 'সরাসরি প্রপার্টির মালিক (Direct Owner)',
                'direct_agent', 'agent' => 'এজেন্ট / মিডিয়া (Agent)',
                'developer'             => 'ডেভেলপার (Developer)',
                default                 => $primarySource?->relationship_declaration ?? 'সরাসরি প্রপার্টির মালিক (Direct Owner)',
            };

            if ($primarySource) {
                $primarySource->update([
                    'owner_name'               => $ownerName ?: $primarySource->owner_name,
                    'owner_phone'              => $ownerPhone ?: $primarySource->owner_phone,
                    'source_type'              => $sourceType,
                    'relationship_declaration' => $relDecl,
                    'asking_price'             => $request->input('asking_price', $primarySource->asking_price),
                    'is_primary_source'        => true,
                ]);
            } else {
                PropertyDirectSource::create([
                    'property_id'              => $property->id,
                    'source_user_id'           => $request->input('created_by_user_id') ?: auth()->id(),
                    'source_type'              => $sourceType,
                    'relationship_declaration' => $relDecl,
                    'owner_name'               => $ownerName,
                    'owner_phone'              => $ownerPhone,
                    'asking_price'             => $request->input('asking_price'),
                    'is_primary_source'        => true,
                    'verification_status'      => 'verified',
                ]);
            }
        }

        // Update verification
        $verification = $property->verification ?: new PropertyVerification(['property_id' => $property->id]);
        $verification->owner_identity_checked  = $request->boolean('checklist_owner_identity');
        $verification->location_checked        = $request->boolean('checklist_location');
        $verification->basic_info_checked      = $request->boolean('checklist_basic_info');
        $verification->basic_documents_checked = $request->boolean('checklist_documents');
        $verification->audit_notes             = $request->input('verification_notes');
        if ($property->status === 'live' && !$verification->verified_at) {
            $verification->verified_at = now();
        }
        $verification->save();

        if ($property->status === 'live' && $prevStatus !== 'live') {
            SeoService::pingIndexNow($property);
            if (\App\Models\Setting::isTrue('adm_fb_autopost_enabled') || \App\Models\Setting::isTrue('fb_autopost_enabled')) {
                FacebookAutoPostService::postProperty($property);
            }
        }

        $activeTab = $request->input('active_tab', 'basic');

        return redirect()->route('admin.properties.edit', ['property' => $property, 'tab' => $activeTab])
            ->with('success', "প্রপার্টি #{$property->property_code} সফলভাবে আপডেট হয়েছে!");
    }

    public function destroy(Request $request, Property $property)
    {
        $code = $property->property_code;
        $property->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "প্রপার্টি #{$code} সফলভাবে ডিলিট করা হয়েছে।"
            ]);
        }

        return redirect()->route('admin.properties.index')->with('success', "প্রপার্টি #{$code} ডিলিট করা হয়েছে।");
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            Property::whereIn('id', $ids)->delete();
            return response()->json(['success' => true, 'count' => count($ids)]);
        }
        return response()->json(['success' => false, 'error' => 'কোনো প্রপার্টি নির্বাচন করা হয়নি।']);
    }

    public function bulkStatus(Request $request)
    {
        $ids = $request->input('ids', []);
        $status = $request->validate([
            'status' => 'required|in:live,pending,pending_verification,sold,booked,draft,archived,verified,rejected,negotiation'
        ])['status'];

        if ($status === 'pending') {
            $status = 'pending_verification';
        } elseif ($status === 'archived') {
            $status = 'draft';
        }

        if (!empty($ids)) {
            Property::whereIn('id', $ids)->update(['status' => $status]);
            return response()->json(['success' => true, 'count' => count($ids), 'status' => $status]);
        }
        return response()->json(['success' => false, 'error' => 'কোনো প্রপার্টি নির্বাচন করা হয়নি।']);
    }

    public function updateStatus(Request $request, Property $property)
    {
        if ($request->isMethod('GET')) {
            return redirect()->route('admin.properties.index');
        }

        $status = $request->validate([
            'status' => 'required|in:live,pending,pending_verification,sold,booked,draft,archived,verified,rejected,negotiation'
        ])['status'];

        if ($status === 'pending') {
            $status = 'pending_verification';
        } elseif ($status === 'archived') {
            $status = 'draft';
        }

        $property->update(['status' => $status]);

        if ($status === 'live') {
            SeoService::pingIndexNow($property);
            BuyerDemandMatchService::matchNewProperty($property);
            if (\App\Models\Setting::isTrue('adm_fb_autopost_enabled') || \App\Models\Setting::isTrue('fb_autopost_enabled')) {
                FacebookAutoPostService::postProperty($property);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status'  => $status,
                'property_id' => $property->id,
                'message' => "স্ট্যাটাস পরিবর্তন হয়ে '" . strtoupper($status) . "' হয়েছে।"
            ]);
        }

        return redirect()->route('admin.properties.index')->with('success', "স্ট্যাটাস পরিবর্তন হয়ে '{$status}' হয়েছে।");
    }

    public function autoAssignInserters(Request $request)
    {
        $agentId = $request->input('agent_id');
        $agent = User::find($agentId);

        if (!$agent) {
            return response()->json(['success' => false, 'error' => 'নির্বাচিত এজেন্ট পাওয়া যায়নি।']);
        }

        $propertyIds = $request->input('property_ids', []);

        if (!empty($propertyIds) && is_array($propertyIds)) {
            $count = Property::whereIn('id', $propertyIds)->update(['created_by_user_id' => $agent->id]);
        } else {
            $count = Property::where(function ($q) {
                $q->whereNull('created_by_user_id')->orWhere('created_by_user_id', 0);
            })->update(['created_by_user_id' => $agent->id]);
        }

        return response()->json([
            'success'        => true,
            'assigned_count' => $count,
            'agent_name'     => $agent->name,
            'message'        => "সফলভাবে {$count}টি প্রপার্টি {$agent->name}-এর সাথে অ্যাসাইন করা হয়েছে!"
        ]);
    }

    public function postToFacebook(Property $property, Request $request)
    {
        $caption = $request->input('caption');
        $res = FacebookAutoPostService::postProperty($property, $caption);

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        if ($res['success']) {
            return back()->with('success', "ফেসবুক পেজে সফলভাবে পোস্ট করা হয়েছে! (Post ID: {$res['post_id']})");
        }

        return back()->with('error', "ফেসবুকে পোস্ট ব্যর্থ: " . ($res['error'] ?? 'অজানা ত্রুটি'));
    }

    private function saveTypeDetails(Property $property, Request $request): void
    {
        if ($property->type === 'land') {
            $land = $property->landDetail;
            PropertyLandDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'katha_size'          => (float) ($request->input('size') ?? $land?->katha_size ?? 0),
                    'price_total'         => (float) ($request->input('asking_price') ?? $land?->price_total ?? 0),
                    'price_per_katha'     => (float) ($request->input('price_per_katha') ?? $land?->price_per_katha ?? 0),
                    'is_corner'           => $request->input('plot_category') === 'corner',
                    'is_lake_facing'      => $request->input('plot_category') === 'lake_view',
                    'boundary_wall_done'  => $request->boolean('boundary_wall'),
                    'soil_filling_status' => $request->input('soil_type', $land?->soil_filling_status ?? 'filled'),
                ]
            );
            if ($request->filled('road_width_ft')) {
                $property->updateQuietly(['road_width_ft' => (int) $request->input('road_width_ft')]);
            }
        } elseif ($property->type === 'flat') {
            $flat = $property->flatDetail;
            $sqft = (float) ($request->input('flat_size_sqft') ?? $request->input('size') ?? $flat?->flat_size_sqft ?? 0);
            $priceTotal = (float) ($request->input('asking_price') ?? $flat?->price_total ?? 0);
            $pricePerSqft = $sqft > 0 ? round($priceTotal / $sqft, 2) : 0;
            PropertyFlatDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'flat_size_sqft'    => $sqft,
                    'bedrooms'          => (int) ($request->input('bedrooms') ?? $flat?->bedrooms ?? 3),
                    'bathrooms'         => (int) ($request->input('bathrooms') ?? $flat?->bathrooms ?? 3),
                    'floor_number'      => (int) preg_replace('/[^0-9]/', '', (string) ($request->input('floor_no') ?? $flat?->floor_number ?? 1)) ?: 1,
                    'total_floors'      => (int) ($request->input('total_floors') ?? $flat?->total_floors ?? 8),
                    'price_total'       => $priceTotal,
                    'price_per_sqft'    => (float) ($request->input('price_per_sqft') ?? $flat?->price_per_sqft ?? $pricePerSqft),
                    'has_lift'          => $request->boolean('lift', $flat?->has_lift ?? false),
                    'has_generator'     => $request->boolean('generator', $flat?->has_generator ?? false),
                    'parking_available' => $request->boolean('parking', $flat?->parking_available ?? false),
                    'has_gas'           => $request->boolean('has_gas') || $request->input('gas_type') === 'titas' || ($flat?->has_gas ?? false),
                    'completion_status' => $request->input('flat_condition', $flat?->completion_status ?? 'ready'),
                ]
            );
        } elseif ($property->type === 'commercial') {
            $comm = $property->commercialDetail;
            PropertyCommercialDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'size_sqft'       => (float) ($request->input('commercial_size_sqft') ?? $request->input('size') ?? $comm?->size_sqft ?? 0),
                    'commercial_type' => $request->input('commercial_type', $comm?->commercial_type ?? 'shop'),
                    'floor_number'    => (int) preg_replace('/[^0-9]/', '', (string) ($request->input('commercial_floor_no') ?? $comm?->floor_number ?? 1)) ?: 1,
                    'price_total'     => (float) ($request->input('asking_price') ?? $comm?->price_total ?? 0),
                    'suitable_for'    => $request->input('suitable_for', $comm?->suitable_for),
                ]
            );
        } elseif ($property->type === 'land_share') {
            $share = $property->shareDetail;
            PropertyShareDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'total_shares'     => (int) ($request->input('total_shares') ?? $share?->total_shares ?? 1),
                    'available_shares' => (int) ($request->input('available_shares') ?? $share?->available_shares ?? 1),
                    'share_price'      => (float) ($request->input('price_per_share') ?? $request->input('asking_price') ?? $share?->share_price ?? 0),
                    'land_size_katha'  => (float) ($request->input('share_land_size_katha') ?? $request->input('size') ?? $share?->land_size_katha ?? 0),
                    'building_stories' => $request->input('building_stories', $share?->building_stories),
                ]
            );
        }
    }
}
