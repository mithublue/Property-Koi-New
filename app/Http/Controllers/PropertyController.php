<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyDirectSource;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyMedia;
use App\Models\PropertyShareDetail;
use App\Models\PropertyVerification;
use App\Models\Setting;
use App\Services\SeoService;
use App\Services\WatermarkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        return $this->buildListingView($request, 'all');
    }

    public function landSearch(Request $request): View
    {
        return $this->buildListingView($request, 'land');
    }

    public function flatSearch(Request $request): View
    {
        return $this->buildListingView($request, 'flat');
    }

    public function commercialSearch(Request $request): View
    {
        return $this->buildListingView($request, 'commercial');
    }

    public function shareSearch(Request $request): View
    {
        return $this->buildListingView($request, 'land_share');
    }

    private function buildListingView(Request $request, string $forcedType = 'all'): View
    {
        $query = Property::with(['land', 'flat', 'share', 'commercial', 'directSources', 'verification', 'media'])
            ->where('status', 'live');

        $type = $forcedType !== 'all' ? $forcedType : $request->query('type');
        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        // Keyword search (title, code, block, road, area, description)
        $keyword = trim((string) ($request->query('search') ?: $request->query('keyword', '')));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('property_code', 'like', "%{$keyword}%")
                  ->orWhere('area', 'like', "%{$keyword}%")
                  ->orWhere('sub_area', 'like', "%{$keyword}%")
                  ->orWhere('block', 'like', "%{$keyword}%")
                  ->orWhere('sector', 'like', "%{$keyword}%")
                  ->orWhere('road_number', 'like', "%{$keyword}%")
                  ->orWhere('plot_number', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('area') && $request->area !== 'all') {
            $query->where('area', $request->area);
        }

        if ($request->filled('sub_area') && $request->sub_area !== 'all') {
            $query->where('sub_area', 'like', '%' . $request->sub_area . '%');
        }

        if ($request->filled('block') && $request->block !== 'all') {
            $query->where('block', $request->block);
        }

        if ($request->filled('sector') && $request->sector !== 'all') {
            $query->where('sector', $request->sector);
        }

        if ($request->filled('road_number')) {
            $query->where('road_number', 'like', '%' . trim($request->road_number) . '%');
        }

        if ($request->filled('road_width')) {
            $query->where('road_width_ft', '>=', (int) $request->road_width);
        }

        if ($request->filled('facing') && $request->facing !== 'all') {
            $query->where('facing', $request->facing);
        }

        if ($request->filled('negotiable') && $request->negotiable !== 'all') {
            $query->where('is_negotiable', $request->negotiable === '1');
        }

        if ($request->filled('source_type') && $request->source_type !== 'all') {
            $sourceType = $request->source_type;
            $query->whereHas('directSources', function ($q) use ($sourceType) {
                $q->where('source_type', $sourceType);
            });
        }

        // Price range filters across property subtypes
        $priceMin = $request->filled('price_min') ? (float) $request->price_min : null;
        $priceMax = $request->filled('price_max') ? (float) $request->price_max : null;

        if ($priceMin !== null && $priceMin > 0) {
            $query->where(function ($q) use ($priceMin) {
                $q->whereHas('land', fn ($sq) => $sq->where('price_total', '>=', $priceMin))
                  ->orWhereHas('flat', fn ($sq) => $sq->where('price_total', '>=', $priceMin))
                  ->orWhereHas('commercial', fn ($sq) => $sq->where('price_total', '>=', $priceMin))
                  ->orWhereHas('share', fn ($sq) => $sq->where('share_price', '>=', $priceMin))
                  ->orWhereHas('directSources', fn ($sq) => $sq->where('asking_price', '>=', $priceMin));
            });
        }

        if ($priceMax !== null && $priceMax > 0) {
            $query->where(function ($q) use ($priceMax) {
                $q->whereHas('land', fn ($sq) => $sq->where('price_total', '>', 0)->where('price_total', '<=', $priceMax))
                  ->orWhereHas('flat', fn ($sq) => $sq->where('price_total', '>', 0)->where('price_total', '<=', $priceMax))
                  ->orWhereHas('commercial', fn ($sq) => $sq->where('price_total', '>', 0)->where('price_total', '<=', $priceMax))
                  ->orWhereHas('share', fn ($sq) => $sq->where('share_price', '>', 0)->where('share_price', '<=', $priceMax))
                  ->orWhereHas('directSources', fn ($sq) => $sq->where('asking_price', '>', 0)->where('asking_price', '<=', $priceMax));
            });
        }

        // Category-specific filters: LAND
        if ($type === 'land' || $request->hasAny(['katha_min', 'katha_max', 'soil_filling_status', 'is_corner', 'is_lake_facing', 'is_park_facing', 'boundary_wall_done', 'price_per_katha_max'])) {
            if ($request->filled('katha_min')) {
                $query->whereHas('land', fn ($q) => $q->where('katha_size', '>=', (float) $request->katha_min));
            }
            if ($request->filled('katha_max')) {
                $query->whereHas('land', fn ($q) => $q->where('katha_size', '<=', (float) $request->katha_max));
            }
            if ($request->filled('price_per_katha_max')) {
                $query->whereHas('land', fn ($q) => $q->where('price_per_katha', '>', 0)->where('price_per_katha', '<=', (float) $request->price_per_katha_max));
            }
            if ($request->filled('soil_filling_status') && $request->soil_filling_status !== 'all') {
                $query->whereHas('land', fn ($q) => $q->where('soil_filling_status', $request->soil_filling_status));
            }
            if ($request->boolean('is_corner')) {
                $query->whereHas('land', fn ($q) => $q->where('is_corner', true));
            }
            if ($request->boolean('is_lake_facing')) {
                $query->whereHas('land', fn ($q) => $q->where('is_lake_facing', true));
            }
            if ($request->boolean('is_park_facing')) {
                $query->whereHas('land', fn ($q) => $q->where('is_park_facing', true));
            }
            if ($request->boolean('boundary_wall_done')) {
                $query->whereHas('land', fn ($q) => $q->where('boundary_wall_done', true));
            }
        }

        // Category-specific filters: FLAT
        if ($type === 'flat') {
            if ($request->filled('completion_status') && $request->completion_status !== 'all') {
                $query->whereHas('flat', fn ($q) => $q->where('completion_status', $request->completion_status));
            }
            if ($request->filled('bedrooms') && $request->bedrooms !== 'all') {
                $query->whereHas('flat', fn ($q) => $q->where('bedrooms', '>=', (int) $request->bedrooms));
            }
            if ($request->filled('bathrooms') && $request->bathrooms !== 'all') {
                $query->whereHas('flat', fn ($q) => $q->where('bathrooms', '>=', (int) $request->bathrooms));
            }
            if ($request->filled('sqft_min')) {
                $query->whereHas('flat', fn ($q) => $q->where('flat_size_sqft', '>=', (float) $request->sqft_min));
            }
            if ($request->filled('sqft_max')) {
                $query->whereHas('flat', fn ($q) => $q->where('flat_size_sqft', '<=', (float) $request->sqft_max));
            }
            if ($request->filled('floor_number') && $request->floor_number !== 'all') {
                $query->whereHas('flat', fn ($q) => $q->where('floor_number', (int) $request->floor_number));
            }
            if ($request->boolean('has_lift')) {
                $query->whereHas('flat', fn ($q) => $q->where('has_lift', true));
            }
            if ($request->boolean('parking_available')) {
                $query->whereHas('flat', fn ($q) => $q->where('parking_available', true));
            }
            if ($request->boolean('has_generator')) {
                $query->whereHas('flat', fn ($q) => $q->where('has_generator', true));
            }
            if ($request->boolean('has_gas')) {
                $query->whereHas('flat', fn ($q) => $q->where('has_gas', true));
            }
        }

        // Category-specific filters: COMMERCIAL
        if ($type === 'commercial') {
            if ($request->filled('commercial_type') && $request->commercial_type !== 'all') {
                $query->whereHas('commercial', fn ($q) => $q->where('commercial_type', $request->commercial_type));
            }
            if ($request->filled('floor_number') && $request->floor_number !== 'all') {
                $query->whereHas('commercial', fn ($q) => $q->where('floor_number', 'like', '%' . $request->floor_number . '%'));
            }
            if ($request->filled('market_name')) {
                $query->whereHas('commercial', fn ($q) => $q->where('market_name', 'like', '%' . $request->market_name . '%'));
            }
            if ($request->filled('sqft_min')) {
                $query->whereHas('commercial', fn ($q) => $q->where('size_sqft', '>=', (float) $request->sqft_min));
            }
            if ($request->filled('sqft_max')) {
                $query->whereHas('commercial', fn ($q) => $q->where('size_sqft', '<=', (float) $request->sqft_max));
            }
            if ($request->filled('completion_status') && $request->completion_status !== 'all') {
                $query->whereHas('commercial', fn ($q) => $q->where('completion_status', $request->completion_status));
            }
            if ($request->boolean('has_lift')) {
                $query->whereHas('commercial', fn ($q) => $q->where('has_lift', true));
            }
            if ($request->boolean('has_generator')) {
                $query->whereHas('commercial', fn ($q) => $q->where('has_generator', true));
            }
            if ($request->boolean('has_parking')) {
                $query->whereHas('commercial', fn ($q) => $q->where('has_parking', true));
            }
            if ($request->boolean('has_fire_safety')) {
                $query->whereHas('commercial', fn ($q) => $q->where('has_fire_safety', true));
            }
            if ($request->boolean('has_central_ac')) {
                $query->whereHas('commercial', fn ($q) => $q->where('has_central_ac', true));
            }
        }

        // Category-specific filters: LAND SHARE
        if ($type === 'land_share') {
            if ($request->filled('sqft_min')) {
                $query->whereHas('share', fn ($q) => $q->where('flat_size_sqft', '>=', (float) $request->sqft_min));
            }
            if ($request->filled('sqft_max')) {
                $query->whereHas('share', fn ($q) => $q->where('flat_size_sqft', '<=', (float) $request->sqft_max));
            }
            if ($request->filled('katha_min')) {
                $query->whereHas('share', fn ($q) => $q->where('land_size_katha', '>=', (float) $request->katha_min));
            }
            if ($request->filled('bedrooms') && $request->bedrooms !== 'all') {
                $query->whereHas('share', fn ($q) => $q->where('bedrooms', '>=', (int) $request->bedrooms));
            }
            if ($request->filled('bathrooms') && $request->bathrooms !== 'all') {
                $query->whereHas('share', fn ($q) => $q->where('bathrooms', '>=', (int) $request->bathrooms));
            }
            if ($request->filled('share_price_max')) {
                $query->whereHas('share', fn ($q) => $q->where('share_price', '>', 0)->where('share_price', '<=', (float) $request->share_price_max));
            }
            if ($request->boolean('has_lift')) {
                $query->whereHas('share', fn ($q) => $q->where('has_lift', true));
            }
            if ($request->boolean('has_parking')) {
                $query->whereHas('share', fn ($q) => $q->where('has_parking', true));
            }
            if ($request->boolean('has_generator')) {
                $query->whereHas('share', fn ($q) => $q->where('has_generator', true));
            }
        }

        // Sorting
        $sort = $request->query('sort', 'latest');
        if ($sort === 'popular') {
            $query->orderBy('views_count', 'desc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $properties = $query->paginate(12)->withQueryString();

        $pageSlug = match ($forcedType) {
            'land' => 'land-search',
            'flat' => 'flat-search',
            'commercial' => 'commercial-search',
            'land_share' => 'land-share-search',
            default => 'all-search',
        };

        $seo = SeoService::getOgMeta($pageSlug);

        return view('pages.properties', [
            'properties' => $properties,
            'currentType' => $forcedType,
            'seo' => $seo,
            'filters' => $request->all(),
        ]);
    }

    public function show(string $slug): View
    {
        $property = Property::with([
            'land',
            'flat',
            'share',
            'commercial',
            'directSources.sourceUser.agentProfile',
            'verification',
            'media',
        ])->where('slug', $slug)->firstOrFail();

        // Increment views count safely
        $property->increment('views_count');

        // Fetch approved reviews for this property or its primary sources
        $sourceUserIds = $property->directSources->pluck('source_user_id')->filter()->unique()->toArray();
        $reviews = \App\Models\Review::where('status', 'approved')
            ->where(function ($q) use ($property, $sourceUserIds) {
                $q->where('property_id', $property->id);
                if (!empty($sourceUserIds)) {
                    $q->orWhereIn('target_user_id', $sourceUserIds);
                }
            })
            ->latest()
            ->limit(10)
            ->get();

        // Fetch similar properties in same block/area
        $similar = Property::with(['land', 'flat', 'share', 'commercial', 'media'])
            ->where('id', '!=', $property->id)
            ->where('status', 'live')
            ->where('type', $property->type)
            ->where(function ($q) use ($property) {
                if ($property->block) {
                    $q->where('block', $property->block);
                } else {
                    $q->where('area', $property->area);
                }
            })
            ->limit(3)
            ->get();

        $seo = SeoService::getOgMeta('', $property);

        return view('pages.property-detail', [
            'property' => $property,
            'reviews'  => $reviews,
            'similar'  => $similar,
            'seo'      => $seo,
        ]);
    }

    public function create(Request $request): View
    {
        $seo = SeoService::getOgMeta('sell-property');
        $role = $request->query('role', 'owner');
        $is_owner_user = $role === 'owner';
        $is_agent_user = $role === 'agent';
        $user = auth()->user();

        $dev_mode = Setting::get('adm_developer_mode', '1') === '1';

        return view('pages.sell-property', [
            'seo'               => $seo,
            'role'              => $role,
            'is_owner_user'     => $is_owner_user,
            'is_agent_user'     => $is_agent_user,
            'dev_mode'          => $dev_mode,
            'user_display_name' => $user ? ($user->name ?: $user->username) : '',
            'user_phone'        => $user ? ($user->whatsapp_number ?: $user->phone) : '',
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('property_type', $request->input('type', 'land'));
        if (!in_array($type, ['land', 'flat', 'land_share', 'commercial'])) {
            $type = 'land';
        }

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'area'               => 'nullable|string|max:100',
            'custom_area'        => 'nullable|string|max:100',
            'sub_area'           => 'nullable|string|max:100',
            'block'              => 'nullable|string|max:50',
            'sector'             => 'nullable|string|max:50',
            'road_number'        => 'nullable|string|max:50',
            'road_width_ft'      => 'nullable|numeric',
            'plot_number'        => 'nullable|string|max:100',
            'holding_number'     => 'nullable|string|max:50',
            'facing'             => 'nullable|string|max:30',
            'description'        => 'nullable|string',
            'fb_caption'         => 'nullable|string',
            'is_negotiable'      => 'nullable',
            'owner_name'         => 'nullable|string|max:191',
            'owner_phone'        => 'nullable|string|max:50',
            'asking_price'       => 'nullable|numeric',
            'youtube_video_url'  => 'nullable|string|max:255',
            'google_map_pin_url' => 'nullable|string|max:255',
            'latitude'           => 'nullable|numeric',
            'longitude'          => 'nullable|numeric',
        ]);

        $area = $validated['area'] ?? 'Aftabnagar';
        if ($area === '__other__' && !empty($validated['custom_area'])) {
            $area = $validated['custom_area'];
        }

        $code = 'PK-' . strtoupper(Str::random(6));
        $slug = Str::slug($validated['title']) . '-' . strtolower(Str::random(5));

        $addressVisibility = $request->input('address_visibility');
        if (is_string($addressVisibility)) {
            $addressVisibility = json_decode($addressVisibility, true) ?: [];
        }
        $defaults = [
            'block'          => true,
            'sector'         => true,
            'road_number'    => true,
            'road_width_ft'  => true,
            'plot_number'    => true,
            'holding_number' => true,
        ];
        if (!is_array($addressVisibility) || empty($addressVisibility)) {
            $addressVisibility = $defaults;
        } else {
            $addressVisibility = array_merge($defaults, $addressVisibility);
        }

        $otherFeatures = $request->input('other_features');
        if (is_string($otherFeatures)) {
            $otherFeatures = json_decode($otherFeatures, true) ?: [];
        }
        if (!is_array($otherFeatures)) {
            $otherFeatures = [];
        }

        $isNegotiable = $request->has('is_negotiable')
            ? (int) $request->input('is_negotiable') === 1
            : true;

        $property = Property::create([
            'property_code'      => $code,
            'type'               => $type,
            'title'              => $validated['title'],
            'slug'               => $slug,
            'area'               => $area,
            'sub_area'           => $validated['sub_area'] ?? ($validated['block'] ?? null),
            'block'              => $validated['block'] ?? null,
            'sector'             => $validated['sector'] ?? null,
            'road_width_ft'      => (int) ($validated['road_width_ft'] ?? 20),
            'road_number'        => $validated['road_number'] ?? null,
            'plot_number'        => $validated['plot_number'] ?? null,
            'holding_number'     => $validated['holding_number'] ?? null,
            'address_visibility' => $addressVisibility,
            'facing'             => $validated['facing'] ?? null,
            'description'        => $validated['description'] ?? null,
            'fb_caption'         => $validated['fb_caption'] ?? null,
            'is_negotiable'      => $isNegotiable,
            'google_map_pin_url' => $validated['google_map_pin_url'] ?? null,
            'latitude'           => $validated['latitude'] ?? null,
            'longitude'          => $validated['longitude'] ?? null,
            'other_features'     => $otherFeatures,
            'status'             => 'live',
            'created_by_user_id' => auth()->id(),
        ]);

        $askingPrice = (float) ($request->input('asking_price') ?: 0);

        // Subdetail record based on type
        if ($property->type === 'land') {
            $katha = (float) ($request->input('size') ?: ($request->input('katha_size') ?: 3.0));
            $pricePerKatha = (float) ($request->input('price_per_katha') ?: 0);
            if ($askingPrice <= 0 && $katha > 0 && $pricePerKatha > 0) {
                $askingPrice = $katha * $pricePerKatha;
            }

            PropertyLandDetail::create([
                'property_id'         => $property->id,
                'katha_size'          => $katha,
                'price_total'         => $askingPrice,
                'price_per_katha'     => $pricePerKatha ?: ($katha > 0 ? round($askingPrice / $katha, 2) : 0),
                'is_corner'           => $request->boolean('is_corner'),
                'is_lake_facing'      => $request->boolean('is_lake_facing'),
                'boundary_wall_done'  => $request->boolean('boundary_wall_done'),
                'soil_filling_status' => in_array($request->input('soil_filling_status'), ['filled', 'completed', 'ready']) ? 'filled' : 'unfilled',
            ]);
        } elseif ($property->type === 'flat') {
            $sqft = (float) ($request->input('flat_size_sqft') ?: 1200);
            $rate = (float) ($request->input('price_per_sqft') ?: 0);
            $garage = (float) ($request->input('garage_price') ?: 0);
            $utility = (float) ($request->input('utility_price') ?: 0);
            if ($askingPrice <= 0 && $sqft > 0 && $rate > 0) {
                $askingPrice = ($sqft * $rate) + $garage + $utility;
            }

            PropertyFlatDetail::create([
                'property_id'       => $property->id,
                'flat_size_sqft'    => $sqft,
                'bedrooms'          => (int) ($request->input('bedrooms') ?: 3),
                'bathrooms'         => (int) ($request->input('bathrooms') ?: 3),
                'balconies'         => (int) ($request->input('balconies') ?: 2),
                'floor_number'      => (int) ($request->input('floor_number') ?: 1),
                'total_floors'      => (int) ($request->input('total_floors') ?: 6),
                'completion_status' => $request->input('completion_status') ?: 'ready',
                'price_total'       => $askingPrice,
                'price_per_sqft'    => $rate ?: ($sqft > 0 ? round($askingPrice / $sqft, 2) : 0),
                'garage_price'      => $garage,
                'utility_price'     => $utility,
                'down_payment'      => (float) ($request->input('down_payment') ?: 0),
                'parking_available' => $request->boolean('parking_available'),
                'has_lift'          => $request->boolean('has_lift'),
                'has_generator'     => $request->boolean('has_generator'),
                'has_substation'    => $request->boolean('has_substation'),
                'has_gas'           => $request->boolean('has_gas'),
            ]);
        } elseif ($property->type === 'commercial') {
            $sqft = (float) ($request->input('size_sqft') ?: ($request->input('size') ?: 300));
            $rate = (float) ($request->input('price_per_sqft') ?: 0);
            if ($askingPrice <= 0 && $sqft > 0 && $rate > 0) {
                $askingPrice = $sqft * $rate;
            }

            PropertyCommercialDetail::create([
                'property_id'        => $property->id,
                'size_sqft'          => $sqft,
                'commercial_type'    => $request->input('commercial_type') ?: 'shop',
                'market_name'        => $request->input('market_name'),
                'shop_number'        => $request->input('shop_number'),
                'floor_number'       => (string) ($request->input('floor_number') ?: '1'),
                'total_floors'       => (int) ($request->input('total_floors') ?: 6),
                'price_total'        => $askingPrice,
                'price_per_sqft'     => $rate ?: ($sqft > 0 ? round($askingPrice / $sqft, 2) : 0),
                'frontage_ft'        => (float) ($request->input('frontage_ft') ?: 0),
                'ceiling_height_ft'  => (float) ($request->input('ceiling_height_ft') ?: 0),
                'completion_status'  => $request->input('completion_status') ?: 'ready',
                'monthly_rent_yield' => (float) ($request->input('monthly_rent_yield') ?: 0),
                'washroom_type'      => $request->input('washroom_type') ?: 'common',
                'has_lift'           => $request->boolean('has_lift'),
                'has_escalator'      => $request->boolean('has_escalator'),
                'has_generator'      => $request->boolean('has_generator'),
                'has_parking'        => $request->boolean('has_parking'),
                'has_central_ac'     => $request->boolean('has_central_ac'),
                'has_cctv'           => $request->boolean('has_cctv'),
                'has_fire_safety'    => $request->boolean('has_fire_safety'),
            ]);
        } elseif ($property->type === 'land_share') {
            PropertyShareDetail::create([
                'property_id'             => $property->id,
                'project_name'            => $request->input('project_name'),
                'developer_name'          => $request->input('developer_name'),
                'land_size_katha'         => (float) ($request->input('land_size_katha') ?: 0),
                'building_stories'        => $request->input('building_stories'),
                'flat_size_sqft'          => (float) ($request->input('flat_size_sqft') ?: 0),
                'share_price'             => $askingPrice,
                'bedrooms'                => (string) ($request->input('bedrooms') ?: '3'),
                'bathrooms'               => (string) ($request->input('bathrooms') ?: '3'),
                'balconies'               => (string) ($request->input('balconies') ?: '2'),
                'units_per_floor'         => (string) ($request->input('units_per_floor') ?: '1'),
                'total_shares'            => (int) ($request->input('total_shares') ?: 0),
                'available_shares'        => (int) ($request->input('available_shares') ?: 0),
                'has_parking'             => $request->boolean('has_parking'),
                'has_drawing_dining'      => $request->boolean('has_drawing_dining'),
                'has_family_living'       => $request->boolean('has_family_living'),
                'has_lift'                => $request->boolean('has_lift'),
                'has_generator'           => $request->boolean('has_generator'),
                'has_substation'          => $request->boolean('has_substation'),
                'construction_start_date' => $request->input('construction_start_date'),
                'construction_duration'   => $request->input('construction_duration'),
                'current_progress'        => $request->input('current_progress'),
                'location_highlights'     => $request->input('location_highlights'),
            ]);
        }

        // Direct source record
        $ownerName = $request->input('owner_name') ?: (auth()->user()?->name ?: 'প্রপার্টি মালিক');
        $ownerPhone = $request->input('owner_phone') ?: (auth()->user()?->phone ?: '');
        $sourceType = $request->input('source_type') ?: 'direct_owner';
        $declaration = $request->input('relationship_declaration') ?: 'সরাসরি প্রপার্টির মালিক (Direct Owner)';

        PropertyDirectSource::create([
            'property_id'              => $property->id,
            'source_user_id'           => auth()->id() ?: (\App\Models\User::first()?->id),
            'source_type'              => $sourceType,
            'relationship_declaration' => $declaration,
            'owner_name'               => $ownerName,
            'owner_phone'              => $ownerPhone,
            'asking_price'             => $askingPrice,
            'is_primary_source'        => true,
            'verification_status'      => 'pending',
        ]);

        // Upload directory
        $uploadDir = public_path('uploads/properties');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $coverIdx = (int) $request->input('cover_index', 0);
        $order = 0;

        // Process Direct File Uploads (property_images[] or images[])
        $uploadedFiles = $request->file('property_images') ?: ($request->file('images') ?: []);
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        foreach ($uploadedFiles as $idx => $file) {
            if (!$file || !$file->isValid()) continue;

            $filename = 'prop_' . $property->id . '_' . date('Ymd_His') . '_' . Str::random(6) . '.webp';
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            $relativeUrl = 'uploads/properties/' . $filename;

            $converted = WatermarkService::convertToWebp($file->getRealPath(), $destPath, true);
            if (!$converted) {
                $file->move($uploadDir, $filename);
            }

            $cloudUrl = \App\Services\CloudinaryService::upload($destPath);
            if ($cloudUrl && file_exists($destPath)) {
                @unlink($destPath);
            }
            $finalPath = $cloudUrl ?: $relativeUrl;

            PropertyMedia::create([
                'property_id' => $property->id,
                'file_path'   => $finalPath,
                'media_type'  => 'photo',
                'is_cover'    => ($idx === $coverIdx),
                'sort_order'  => ++$order,
            ]);
        }

        // Attach existing media paths (e.g., from clipboard uploader)
        $mediaPaths = $request->input('media_paths', []);
        if (is_array($mediaPaths)) {
            foreach ($mediaPaths as $idx => $path) {
                if (empty($path)) continue;
                PropertyMedia::create([
                    'property_id' => $property->id,
                    'file_path'   => $path,
                    'media_type'  => 'photo',
                    'is_cover'    => ($order === 0 && $idx === 0),
                    'sort_order'  => ++$order,
                ]);
            }
        }

        // YouTube video embed
        if (!empty($validated['youtube_video_url'])) {
            PropertyMedia::create([
                'property_id' => $property->id,
                'file_path'   => $validated['youtube_video_url'],
                'media_type'  => 'video_embed',
                'is_cover'    => false,
                'sort_order'  => 99,
            ]);
        }

        // Push notification & Telegram alert for new property
        \App\Services\FcmService::notifyEvent(
            'adm_fcm_notify_property_submit',
            '🏡 নতুন প্রপার্টি লিস্টিং সাবমিট হয়েছে!',
            "{$property->title} (#{$property->property_code}) নতুন সাবমিট হয়েছে, অনুমোদনের জন্য অপেক্ষা করছে।",
            route('admin.properties.index', ['search' => $property->property_code]),
            $property
        );
        \App\Services\TelegramService::sendAdminAlert("🏡 <b>নতুন প্রপার্টি লিস্টিং সাবমিট!</b>\nশিরোনাম: {$property->title}\nকোড: #{$property->property_code}\nএলাকা: {$property->area}, ব্লক: {$property->block}");

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'আপনার প্রপার্টি বিজ্ঞাপনটি সফলভাবে জমা হয়েছে!',
                'data'    => [
                    'message'      => 'আপনার প্রপার্টি বিজ্ঞাপনটি সফলভাবে জমা হয়েছে!',
                    'property_id'  => $property->id,
                    'redirect_url' => route('property.show', $property->slug),
                    'portal_url'   => auth()->check() ? route('buyer.dashboard') : route('properties.land'),
                ],
            ]);
        }

        return redirect()->route('property.show', $property->slug)
            ->with('success', 'আপনার প্রপার্টি বিজ্ঞাপনটি সফলভাবে জমা হয়েছে!');
    }
}
