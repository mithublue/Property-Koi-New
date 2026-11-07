<?php

namespace App\Services;

use App\Models\Guide;
use Illuminate\Support\Str;

class GuideImageService
{
    /**
     * Map of curated royalty-free real estate photography on Unsplash.
     * All URLs are sized for high-definition cards and 1200x630 Open Graph (OG) standards.
     */
    protected const TOPIC_COLLECTIONS = [
        // 1. Expatriate / NRB / International Investment / Remittance
        'nrb' => [
            'patterns' => [
                'প্রবাসী', 'রেমিট্যান্স', 'এনআরবি', 'nrb', 'expatriate', 'remittance',
                'embassy', 'foreign currency', 'dual citizen', 'repatriation',
            ],
            'url' => 'https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 2. Power of Attorney / Am-Moktarnama / Legal Authorization
        'power_of_attorney' => [
            'patterns' => [
                'পাওয়ার অব অ্যাটর্নি', 'আম-মোক্তারনামা', 'আম মোক্তারনামা',
                'power of attorney', 'moktarnama',
            ],
            'url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 3. Inheritance / Waris / Batowara / Farayez / Family Land Law
        'waris_inheritance' => [
            'patterns' => [
                'ওয়ারিশ', 'উত্তরাধিকার', 'ফারায়েজ', 'ফারায়েয', 'বাটোয়ারা', 'বাটোয়ারা',
                'waris', 'inheritance', 'farayez', 'batowara', 'succession',
            ],
            'url' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 4. Bayna Nama / Sale Agreement / Contract / Deal Handshake
        'bayna_contract' => [
            'patterns' => [
                'বায়না চুক্তি', 'বায়না চুক্তি', 'বায়না দলিল', 'বায়না দলিল', 'বায়না', 'বায়না',
                'bayna nama', 'bayna', 'agreement for sale', 'sale agreement',
            ],
            'url' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 5. RAJUK / Building Plan / DAP / FAR / Setback / Architectural Approval
        'rajuk_plan' => [
            'patterns' => [
                'রাজউক', 'ড্যাপ', 'dap', 'rajuk', 'ফার', 'far', 'নকশা অনুমোদন',
                'প্ল্যান অনুমোদন', 'setback', 'occupancy certificate', 'blueprint',
            ],
            'url' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 6. Bank Home Loan / Interest / Mortgage / Finance
        'home_loan' => [
            'patterns' => [
                'হোম লোন', 'ব্যাংক লোন', 'সুদের হার', 'গৃহ ঋণ',
                'home loan', 'bank home loan', 'mortgage', 'interest rate',
            ],
            'url' => 'https://images.unsplash.com/photo-1565372195458-9de0b320ef04?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 7. Hidden Costs / Handover / Parking / Utility Packages / Common Space
        'hidden_costs' => [
            'patterns' => [
                'হিডেন কস্ট', 'হিডেন', 'পার্কিং', 'ইউটিলিটি', 'কমন স্পেস', 'হ্যান্ডওভার',
                'কার্পেট এরিয়া', 'hidden cost', 'hidden costs', 'parking charge', 'super built',
            ],
            'url' => 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 8. Ready Flat vs Under Construction / Building / Engineering Site
        'construction_vs_ready' => [
            'patterns' => [
                'রেডি ফ্ল্যাট বনাম', 'নির্মাণাধীন', 'ready flat vs', 'under construction',
                'কনস্ট্রাকশন', 'রেডি ফ্ল্যাট',
            ],
            'url' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 9. Registration Cost / Stamp Duty / Tax / AIT / Government Fees
        'registration_tax' => [
            'patterns' => [
                'রেজিস্ট্রেশন খরচ', 'রেজিস্ট্রি খরচ', 'স্ট্যাম্প ডিউটি', 'উৎস কর',
                'registration cost', 'stamp duty', 'ait', 'gain tax',
            ],
            'url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 10. Land Share / Co-operative Housing Investment
        'land_share' => [
            'patterns' => [
                'ল্যান্ড শেয়ার', 'ল্যান্ড শেয়ার', 'land share', 'share project',
                'cooperative housing', 'শেয়ার প্রজেক্ট',
            ],
            'url' => 'https://images.unsplash.com/photo-1574362848149-11496d93a7c7?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 11. E-Namjari / Mutation / Title Deed Verification / AC Land / Legal Appeals
        'mutation_records' => [
            'patterns' => [
                'নামজারি', 'খারিজ', 'এসি ল্যান্ড', 'এডিসি', 'খতিয়ান', 'খতিয়ান',
                'দলিল', 'বায়া দলিল', 'বায়া দলিল', 'mutation', 'namjari', 'khotian',
                'baya dolil', 'record', 'porcha', 'পর্চা', 'land title',
            ],
            'url' => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 12. Land / Plots / Demarcated Katha / Aftabnagar / Purbachal / Bashundhara
        'land_plots' => [
            'patterns' => [
                'জমি', 'প্লট', 'কাঠা', 'আফতাবনগর', 'বসুন্ধরা', 'পূর্বাচল', 'উত্তরা',
                'মিরপুর', 'plot', 'land', 'katha', 'aftabnagar', 'bashundhara', 'purbachal',
            ],
            'url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 13. Flats & Modern Apartments / Luxury Interior Living
        'apartment_flat' => [
            'patterns' => [
                'ফ্ল্যাট', 'অ্যাপার্টমেন্ট', 'বেডরুম', 'ড্রয়িং', 'লাক্সারি',
                'flat', 'apartment', 'condo', 'living room', 'interior',
            ],
            'url' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&h=630&q=80',
        ],

        // 14. Commercial / Shop / Office / Market / Corporate
        'commercial_office' => [
            'patterns' => [
                'কমার্সিয়াল', 'কমার্শিয়াল', 'দোকান', 'অফিস', 'মার্কেট', 'স্পেস',
                'commercial', 'office', 'shop', 'retail',
            ],
            'url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&h=630&q=80',
        ],
    ];

    /**
     * Resolve a royalty-free image URL matching the guide's title, category, and keywords.
     * Returns null if no suitable topical match is found, triggering fallback to default thumbnail.
     */
    public static function resolveForTopic(?string $title, ?string $category = null, ?string $keywords = null, ?string $content = null): ?string
    {
        $cleanTitle = mb_strtolower(trim((string) $title));

        // 1. Give highest priority to the Guide Title
        if (! empty($cleanTitle)) {
            foreach (self::TOPIC_COLLECTIONS as $collection) {
                foreach ($collection['patterns'] as $pattern) {
                    if (str_contains($cleanTitle, mb_strtolower($pattern))) {
                        return $collection['url'];
                    }
                }
            }
        }

        // 2. Secondary check against category, keywords, and excerpt
        $secondaryHaystack = mb_strtolower(implode(' ', array_filter([$category, $keywords, Str::limit(strip_tags((string) $content), 300)])));

        if (! empty(trim($secondaryHaystack))) {
            foreach (self::TOPIC_COLLECTIONS as $collection) {
                foreach ($collection['patterns'] as $pattern) {
                    if (str_contains($secondaryHaystack, mb_strtolower($pattern))) {
                        return $collection['url'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve suitable royalty-free image specifically for a Guide instance.
     */
    public static function resolveForGuide(Guide $guide): ?string
    {
        return self::resolveForTopic(
            $guide->title,
            $guide->category,
            $guide->seo_keywords,
            $guide->excerpt ?: $guide->content
        );
    }

    /**
     * Get the default thumbnail asset URL based on guide category.
     */
    public static function getFallbackForCategory(?string $category): string
    {
        return asset('assets/images/guide-default.png');
    }
}
