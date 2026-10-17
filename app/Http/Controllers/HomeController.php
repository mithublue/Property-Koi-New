<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyDirectSource;
use App\Services\SeoService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featured = Property::with(['land', 'flat', 'share', 'commercial', 'media'])
            ->where('status', 'live')
            ->where('is_featured', true)
            ->limit(6)
            ->get();

        if ($featured->isEmpty()) {
            $featured = Property::with(['land', 'flat', 'share', 'commercial', 'media'])
                ->where('status', 'live')
                ->latest()
                ->limit(6)
                ->get();
        }

        $latestLands = Property::with(['land', 'media'])
            ->where('type', 'land')
            ->where('status', 'live')
            ->latest()
            ->limit(4)
            ->get();

        $latestFlats = Property::with(['flat', 'media'])
            ->where('type', 'flat')
            ->where('status', 'live')
            ->latest()
            ->limit(4)
            ->get();

        $latestCommercial = Property::with(['commercial', 'media'])
            ->where('type', 'commercial')
            ->where('status', 'live')
            ->latest()
            ->limit(4)
            ->get();

        $stats = [
            'total_properties' => Property::where('status', 'live')->count(),
            'total_owners'     => PropertyDirectSource::where('source_type', 'direct_owner')->count(),
            'total_blocks'     => Property::whereNotNull('block')->distinct('block')->count('block'),
        ];

        $seo = SeoService::getOgMeta('home');

        return view('pages.home', [
            'featured'         => $featured,
            'latestLands'      => $latestLands,
            'latestFlats'      => $latestFlats,
            'latestCommercial' => $latestCommercial,
            'stats'            => $stats,
            'seo'              => $seo,
        ]);
    }
}
