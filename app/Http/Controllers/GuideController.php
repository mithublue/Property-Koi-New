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

    public function show(Request $request, string $slug): View
    {
        $guide = Guide::where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();

        // Increment view counter safely for unique visitor visits (ignore admins, bots, and repeated reloads)
        $isAdmin = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->role === 'admin' || auth()->user()->role === 'administrator');
        $ua = strtolower((string) request()->userAgent());
        $isBot = (bool) preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview)/i', $ua);

        if (!$isAdmin && !$isBot) {
            $visitorId = md5(request()->ip() . '|' . (request()->userAgent() ?? 'ua'));
            $cacheKey = "viewed_guide_{$guide->id}_{$visitorId}";
            $sessionKey = "viewed_guide_{$guide->id}";

            if (!cache()->has($cacheKey) && !session()->has($sessionKey)) {
                $guide->timestamps = false;
                $guide->increment('views_count');
                $guide->timestamps = true;
                $guide->views_count++;
                cache()->put($cacheKey, true, now()->addHours(24));
                session()->put($sessionKey, true);
            }
        }

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

    /**
     * Dedicated High-Authority Buyer Guide & Price Index Hub for Aftabnagar, Dhaka.
     */
    public function aftabnagarGuide(Request $request): View
    {
        $plotsCount = Property::where('status', 'live')->where('type', 'land')
            ->where(function ($q) {
                $q->where('area', 'like', '%aftab%')
                  ->orWhere('area', 'like', '%আফতাব%');
            })->count();

        $flatsCount = Property::where('status', 'live')->where('type', 'flat')
            ->where(function ($q) {
                $q->where('area', 'like', '%aftab%')
                  ->orWhere('area', 'like', '%আফতাব%');
            })->count();

        $sharesCount = Property::where('status', 'live')->where('type', 'land_share')
            ->where(function ($q) {
                $q->where('area', 'like', '%aftab%')
                  ->orWhere('area', 'like', '%আফতাব%');
            })->count();

        $commercialCount = Property::where('status', 'live')->where('type', 'commercial')
            ->where(function ($q) {
                $q->where('area', 'like', '%aftab%')
                  ->orWhere('area', 'like', '%আফতাব%');
            })->count();

        $featuredAftabnagar = Property::with(['land', 'flat', 'share', 'commercial', 'media'])
            ->where('status', 'live')
            ->where(function ($q) {
                $q->where('area', 'like', '%aftab%')
                  ->orWhere('area', 'like', '%আফতাব%');
            })
            ->orderBy('is_featured', 'desc')
            ->latest()
            ->limit(6)
            ->get();

        $blockPriceIndex = [
            [
                'block'          => 'A',
                'name_bn'        => 'ব্লক এ (Block A)',
                'road_width'     => '২৫ - ৬০ ফুট',
                'price_per_katha'=> '৳১.২০ - ৳১.৬০ কোটি',
                'budget_3_katha' => '৳৩.৬০ - ৳৪.৮০ কোটি',
                'status'         => 'সম্পূর্ণ উন্নত ও রেডি',
                'badge'          => 'প্রাইম গেটওয়ে',
                'highlight'      => 'রামপুরা ব্রিজ ও হাতিরঝিল প্রবেশদ্বার সংলগ্ন, মেইন রোড ও বাণিজ্যিক সম্ভাবনাময়',
                'ideal_for'      => 'লাক্সারি রেসিডেন্সিয়াল ও হাই-ভ্যালু কমার্শিয়াল',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'A']),
            ],
            [
                'block'          => 'B',
                'name_bn'        => 'ব্লক বি (Block B)',
                'road_width'     => '২৫ - ৫০ ফুট',
                'price_per_katha'=> '৳১.১৫ - ৳১.৪৫ কোটি',
                'budget_3_katha' => '৳৩.৪৫ - ৳৪.৩৫ কোটি',
                'status'         => 'সম্পূর্ণ উন্নত ও রেডি',
                'badge'          => 'শিক্ষা ও কমিউনিটি',
                'highlight'      => 'ইস্ট ওয়েস্ট বিশ্ববিদ্যালয় সংলগ্ন, সার্বক্ষণিক প্রাণবন্ত ও চমৎকার ড্রেনেজ',
                'ideal_for'      => 'উচ্চ ভাড়া ফলন ও পরিবার নিয়ে স্থায়ী বসবাস',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'B']),
            ],
            [
                'block'          => 'C',
                'name_bn'        => 'ব্লক সি (Block C)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳১.১০ - ৳১.৩৫ কোটি',
                'budget_3_katha' => '৳৩.৩০ - ৳৪.০৫ কোটি',
                'status'         => 'উন্নত আবাসিক',
                'badge'          => 'শান্ত আবাসিক',
                'highlight'      => 'প্রশস্ত আবাসিক রাস্তা, মসজিদ ও প্লেগ্রাউন্ড সংলগ্ন নিরিবিলি পরিবেশ',
                'ideal_for'      => 'নিজস্ব বাড়ি নির্মাণ ও শান্ত পরিবারিক আবাসন',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'C']),
            ],
            [
                'block'          => 'D',
                'name_bn'        => 'ব্লক ডি (Block D)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳৯৫ লাখ - ৳১.২৫ কোটি',
                'budget_3_katha' => '৳২.৮৫ - ৳৩.৭৫ কোটি',
                'status'         => 'উন্নত ও ঘনবসতি',
                'badge'          => 'জনপ্রিয় ফ্যামিলি জোন',
                'highlight'      => 'ঢাকা ইমপেরিয়াল কলেজ ও স্থানীয় বাজারের কাছে, দ্রুত সংযোগ সুবিধা',
                'ideal_for'      => 'রেডি ফ্ল্যাট ক্রেতা ও মাঝারি বাজেটের প্লট',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'D']),
            ],
            [
                'block'          => 'E',
                'name_bn'        => 'ব্লক ই (Block E)',
                'road_width'     => '২৫ - ৫০ ফুট',
                'price_per_katha'=> '৳৮৫ লাখ - ৳১.১৫ কোটি',
                'budget_3_katha' => '৳২.৫৫ - ৳৩.৪৫ কোটি',
                'status'         => 'উন্নয়নশীল আবাসিক',
                'badge'          => 'লেকভিউ ও খোলামেলা',
                'highlight'      => 'লেক ভিউ কর্নার প্লট ও খোলামেলা বাতাস, চমৎকার সড়ক নেটওয়ার্ক',
                'ideal_for'      => 'প্রকৃতিপ্রেমী পরিবার ও দীর্ঘমেয়াদী বিনিয়োগ',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'E']),
            ],
            [
                'block'          => 'F',
                'name_bn'        => 'ব্লক এফ (Block F)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳৮০ লাখ - ৳১.০৫ কোটি',
                'budget_3_katha' => '৳২.৪০ - ৳৩.১৫ কোটি',
                'status'         => 'উন্নয়নশীল',
                'badge'          => 'উচ্চ সম্ভাবনা',
                'highlight'      => 'নতুন ভবন নির্মাণ কাজ দ্রুত চলমান, সাশ্রয়ী ফ্ল্যাট ও প্লট',
                'ideal_for'      => 'ল্যান্ড শেয়ার প্রকল্প ও সাশ্রয়ী নিজস্ব ফ্ল্যাট',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'F']),
            ],
            [
                'block'          => 'G',
                'name_bn'        => 'ব্লক জি (Block G)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳৭৫ লাখ - ৳৯৫ লাখ',
                'budget_3_katha' => '৳২.২৫ - ৳২.৮৫ কোটি',
                'status'         => 'দ্রুত বর্ধনশীল',
                'badge'          => 'হাই ROI জোন',
                'highlight'      => 'অপেক্ষাকৃত সাশ্রয়ী জমি, পরবর্তী ২-৩ বছরে সর্বোচ্চ মূলধনী বৃদ্ধি',
                'ideal_for'      => 'বিনিয়োগকারী ও ভবিষ্যতের আবাসন পরিকল্পনা',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'G']),
            ],
            [
                'block'          => 'H',
                'name_bn'        => 'ব্লক এইচ (Block H)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳৭০ লাখ - ৳৯০ লাখ',
                'budget_3_katha' => '৳২.১০ - ৳২.৭০ কোটি',
                'status'         => 'দ্রুত বর্ধনশীল',
                'badge'          => 'স্মার্ট ইনভেস্টমেন্ট',
                'highlight'      => 'সহজ প্রবেশাধিকার ও নতুন গ্যাস/বিদ্যুৎ অবকাঠামো উন্নয়ন',
                'ideal_for'      => 'মধ্যম বাজেটের প্লট ক্রেতা',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'H']),
            ],
            [
                'block'          => 'I',
                'name_bn'        => 'ব্লক আই (Block I)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳৬৫ লাখ - ৳৮৫ লাখ',
                'budget_3_katha' => '৳১.৯৫ - ৳২.৫৫ কোটি',
                'status'         => 'নতুন আবাসিক জোন',
                'badge'          => 'বাজেট ফ্রেন্ডলি',
                'highlight'      => 'সাশ্রয়ী মূল্যে ৩ ও ৫ কাঠার প্লট প্রাপ্তির দারুণ সুযোগ',
                'ideal_for'      => 'যৌথ ল্যান্ড শেয়ার ও দীর্ঘমেয়াদী সঞ্চয়',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'I']),
            ],
            [
                'block'          => 'J',
                'name_bn'        => 'ব্লক জে (Block J)',
                'road_width'     => '২৫ - ৫০ ফুট',
                'price_per_katha'=> '৳৬০ লাখ - ৳৮০ লাখ',
                'budget_3_katha' => '৳১.৮০ - ৳২.৪০ কোটি',
                'status'         => 'ভরাট ও উন্নয়ন চলমান',
                'badge'          => 'গ্রোথ ফেজ',
                'highlight'      => 'সমান্তরাল প্রশস্ত রাস্তা ও সংযোগ সড়কের বিস্তার',
                'ideal_for'      => 'ভবিষ্যত প্রজন্মের জন্য নিশ্চিত সম্পদ তৈরি',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'J']),
            ],
            [
                'block'          => 'K',
                'name_bn'        => 'ব্লক কে (Block K)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳৫৫ লাখ - ৳৭৫ লাখ',
                'budget_3_katha' => '৳১.৬৫ - ৳২.২৫ কোটি',
                'status'         => 'ভরাট সম্পন্ন',
                'badge'          => 'সাশ্রয়ী দর',
                'highlight'      => 'তুলনামূলক কম পুঁজিতে নিশ্চিত জমি ক্রয়ের সুযোগ',
                'ideal_for'      => 'কম বাজেটে জমি ক্রেতা',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'K']),
            ],
            [
                'block'          => 'L',
                'name_bn'        => 'ব্লক এল (Block L)',
                'road_width'     => '২৫ - ৪০ ফুট',
                'price_per_katha'=> '৳৫০ লাখ - ৳৭০ লাখ',
                'budget_3_katha' => '৳১.৫০ - ৳২.১০ কোটি',
                'status'         => 'উন্নয়নাধীন',
                'badge'          => 'সাশ্রয়ী প্লট',
                'highlight'      => 'দক্ষিণমুখী ও কর্নার প্লটের চমৎকার প্রাপ্যতা',
                'ideal_for'      => 'দূরদর্শী বিনিয়োগকারী',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'L']),
            ],
            [
                'block'          => 'M & Ext',
                'name_bn'        => 'ব্লক এম ও এক্সটেনশন (Block M & Ext)',
                'road_width'     => '২৫ - ৬০ ফুট (বাইপাস সংযোগ)',
                'price_per_katha'=> '৳৪৫ লাখ - ৳৬৫ লাখ',
                'budget_3_katha' => '৳১.৩৫ - ৳১.৯৫ কোটি',
                'status'         => 'ভবিষ্যত উন্নয়ন জোন',
                'badge'          => 'সর্বনিম্ন কাঠাপ্রতি দর',
                'highlight'      => 'ঢাকা বাইপাস ও মাদানি এভিনিউ এক্সটেনশন নিকটবর্তী রোড কানেক্টিভিটি',
                'ideal_for'      => 'সর্বোচ্চ গুণিতক লাভজনক দীর্ঘমেয়াদী বিনিয়োগ',
                'search_url'     => route('properties.land', ['area' => 'Aftabnagar', 'block' => 'M']),
            ],
        ];

        $ogMeta = SeoService::getAftabnagarBuyerGuideMeta();

        return view('pages.guides.aftabnagar-buyer-guide', compact(
            'blockPriceIndex',
            'featuredAftabnagar',
            'plotsCount',
            'flatsCount',
            'sharesCount',
            'commercialCount',
            'ogMeta'
        ));
    }
}
