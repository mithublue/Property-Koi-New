<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Models\Property;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $search = $request->query('q');

        $query = Guide::published()->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');

        if (!empty($category) && $category !== 'all') {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $guides = $query->paginate(9)->withQueryString();

        $featuredGuide = null;
        if (empty($category) && empty($search) && $guides->currentPage() === 1) {
            $featuredGuide = Guide::published()->where('is_featured', true)->latest()->first();
        }

        $categories = [
            'all'             => 'সব গাইড',
            'legal_docs'      => 'দলিল ও আইনি যাচাই',
            'market_insights' => 'মার্কেট অ্যানালাইসিস',
            'buying_guides'   => 'ক্রয়-বিক্রয় গাইডলাইন',
            'investment'      => 'বিনিয়োগ ও ল্যান্ড শেয়ার',
        ];

        $counts = [
            'all'             => Guide::published()->count(),
            'legal_docs'      => Guide::published()->where('category', 'legal_docs')->count(),
            'market_insights' => Guide::published()->where('category', 'market_insights')->count(),
            'buying_guides'   => Guide::published()->where('category', 'buying_guides')->count(),
            'investment'      => Guide::published()->where('category', 'investment')->count(),
        ];

        $ogMeta = SeoService::getOgMeta('guides');

        return view('pages.guides.index', compact(
            'guides',
            'featuredGuide',
            'categories',
            'counts',
            'category',
            'search',
            'ogMeta'
        ));
    }

    public function show(string $slug): View
    {
        $guide = Guide::where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();

        // Increment view counter safely without updating timestamp
        $guide->timestamps = false;
        $guide->increment('views_count');
        $guide->timestamps = true;

        $relatedGuides = Guide::published()
            ->where('id', '!=', $guide->id)
            ->where('category', $guide->category)
            ->latest()
            ->limit(3)
            ->get();

        if ($relatedGuides->count() < 3) {
            $extra = Guide::published()
                ->where('id', '!=', $guide->id)
                ->whereNotIn('id', $relatedGuides->pluck('id'))
                ->latest()
                ->limit(3 - $relatedGuides->count())
                ->get();
            $relatedGuides = $relatedGuides->concat($extra);
        }

        // Contextual properties to show in sidebar or CTA based on guide category
        $propertyType = match ($guide->category) {
            'legal_docs'      => 'land',
            'buying_guides'   => 'flat',
            'investment'      => 'land_share',
            default           => null,
        };

        $featuredPropertiesQuery = Property::where('status', 'live')->orderBy('is_featured', 'desc')->latest();
        if ($propertyType) {
            $featuredPropertiesQuery->where('type', $propertyType);
        }
        $featuredProperties = $featuredPropertiesQuery->limit(3)->get();

        $ogMeta = SeoService::getGuideMeta($guide);

        return view('pages.guides.show', compact('guide', 'relatedGuides', 'featuredProperties', 'ogMeta'));
    }
}
