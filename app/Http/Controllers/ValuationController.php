<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyValuationService;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ValuationController extends Controller
{
    public function index(Request $request): View
    {
        $area = $request->query('area', 'aftabnagar');
        $type = $request->query('type', 'flat');
        $block = $request->query('block', '');

        $initialParams = [
            'area'         => $area,
            'block'        => $block,
            'type'         => $type,
            'size'         => $type === 'land' ? 3.5 : 1350,
            'road_width'   => 25,
            'facing'       => 'south',
            'is_corner'    => false,
            'has_lake_view'=> false,
            'floor_level'  => 4,
            'has_parking'  => true,
        ];

        $initialEstimate = PropertyValuationService::estimate($initialParams);

        // Fetch live matching properties to showcase real market options
        $matchingProperties = Property::where('status', 'live')
            ->when($type === 'land', fn($q) => $q->where('type', 'land'))
            ->when($type === 'flat', fn($q) => $q->where('type', 'flat'))
            ->latest()
            ->limit(3)
            ->get();

        $benchmarks = PropertyValuationService::getBenchmarks();
        $ogMeta = SeoService::getOgMeta('valuation');

        return view('pages.valuation', compact(
            'initialParams',
            'initialEstimate',
            'matchingProperties',
            'benchmarks',
            'ogMeta'
        ));
    }

    public function estimate(Request $request): JsonResponse
    {
        $params = $request->validate([
            'area'          => 'required|string',
            'block'         => 'nullable|string',
            'type'          => 'required|string|in:flat,land',
            'size'          => 'required|numeric|min:0.5|max:10000',
            'road_width'    => 'nullable|numeric|min:10|max:150',
            'facing'        => 'nullable|string',
            'is_corner'     => 'nullable|boolean',
            'has_lake_view' => 'nullable|boolean',
            'floor_level'   => 'nullable|integer|min:1|max:30',
            'has_parking'   => 'nullable|boolean',
        ]);

        $result = PropertyValuationService::estimate($params);

        return response()->json([
            'success'  => true,
            'estimate' => $result,
        ]);
    }
}
