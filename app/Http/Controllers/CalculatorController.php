<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalculatorController extends Controller
{
    /**
     * Calculators Hub Page
     */
    public function index(): View
    {
        $ogMeta = SeoService::getOgMeta('calculators');
        return view('pages.calculators.index', compact('ogMeta'));
    }

    /**
     * Bangladesh Property Registration Fee & Tax Calculator
     */
    public function registration(Request $request): View
    {
        $ogMeta = SeoService::getOgMeta('calculator-registration');
        return view('pages.calculators.registration', compact('ogMeta'));
    }

    /**
     * Land Share ROI & Savings Calculator
     */
    public function landShareRoi(Request $request): View
    {
        $featuredShares = Property::where('status', 'live')
            ->where('type', 'land_share')
            ->latest()
            ->limit(3)
            ->get();

        $ogMeta = SeoService::getOgMeta('calculator-land-share');
        return view('pages.calculators.land-share-roi', compact('featuredShares', 'ogMeta'));
    }
}
