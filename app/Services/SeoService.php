<?php

namespace App\Services;

use App\Models\Guide;
use App\Models\Property;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Get the primary site name from settings.
     */
    public static function getSiteName(): string
    {
        $name = trim((string) Setting::get('adm_site_name', Setting::get('site_name', '')));
        if (empty($name) || strtolower($name) === 'laravel') {
            return 'PropertyKoi.com';
        }
        return $name;
    }

    /**
     * Format Image URL specifically for Facebook / WhatsApp / Telegram Open Graph (1200x630 absolute URL).
     */
    public static function formatOgImageUrl(?string $url, string $defaultAsset = 'og-default.jpg'): string
    {
        $url = trim((string) $url);

        if (empty($url) || str_contains($url, 'example.com')) {
            return asset('assets/images/' . $defaultAsset);
        }

        // Convert Cloudinary WebP to JPG with 1200x630 crop so Facebook/WhatsApp scrapers parse it reliably
        if (str_contains($url, 'res.cloudinary.com')) {
            $url = preg_replace('/\.webp(\?.*)?$/i', '.jpg$1', $url);
            if (!str_contains($url, '/image/upload/c_') && !str_contains($url, '/image/upload/w_')) {
                $url = str_replace('/image/upload/', '/image/upload/c_fill,w_1200,h_630,f_jpg,q_auto/', $url);
            }
            return $url;
        }

        // Ensure Unsplash images are sized for OG 1200x630
        if (str_contains($url, 'images.unsplash.com')) {
            $url = preg_replace('/([?&])w=\d+/', '$1w=1200', $url);
            $url = preg_replace('/([?&])h=\d+/', '$1h=630', $url);
            if (!str_contains($url, 'w=1200')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'auto=format&fit=crop&w=1200&h=630&q=85';
            }
            return $url;
        }

        // Convert relative path to absolute asset URL
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            return asset(ltrim($url, '/'));
        }

        return $url;
    }

    /**
     * Get custom OG image from settings or fallback to bundled asset.
     */
    public static function getCustomOrAssetImage(string $key, string $defaultAsset): string
    {
        $custom = trim((string) Setting::get($key, ''));
        if (!empty($custom) && !str_contains($custom, 'example.com')) {
            return self::formatOgImageUrl($custom, $defaultAsset);
        }
        return asset('assets/images/' . $defaultAsset);
    }

    /**
     * Build rich SEO & AEO Meta Description for a single property.
     */
    public static function buildPropertyDescription(Property $p): string
    {
        $typeBn = $p->type_label;
        $area = $p->area ?: 'আফতাবনগর';
        $block = $p->block ? ('ব্লক ' . $p->block) : '';
        $road = $p->road_width_ft ? ($p->road_width_ft . ' ফুট রাস্তা') : '';
        $facing = $p->facing ? ($p->facing . ' মুখী') : '';

        $sizeStr = '';
        if ($p->type === 'land' && $p->land?->katha_size) {
            $sizeStr = $p->land->katha_size . ' কাঠা জমি';
        } elseif ($p->type === 'flat' && $p->flat?->flat_size_sqft) {
            $sizeStr = $p->flat->flat_size_sqft . ' বর্গফুট ফ্ল্যাট';
            if ($p->flat->bedrooms) {
                $sizeStr .= ' (' . $p->flat->bedrooms . ' বেড)';
            }
        } elseif ($p->type === 'commercial' && $p->commercial?->size_sqft) {
            $sizeStr = $p->commercial->size_sqft . ' বর্গফুট ' . ($p->commercial->commercial_type ?: 'কমার্শিয়াল স্পেস');
            if ($p->commercial->market_name) {
                $sizeStr .= ', ' . $p->commercial->market_name;
            }
        } elseif ($p->type === 'land_share' && $p->share?->flat_size_sqft) {
            $sizeStr = $p->share->flat_size_sqft . ' বর্গফুট ল্যান্ড শেয়ার';
        }

        $specs = array_filter([$sizeStr, $road, $facing]);
        $specsText = !empty($specs) ? (' (' . implode(', ', $specs) . ')') : '';
        $priceText = $p->formatted_price ? " | মূল্য: {$p->formatted_price}" : '';

        $desc = trim("{$area} {$block}-এ যাচাইকৃত {$typeBn}{$specsText}{$priceText}। ১০০% ভেরিফাইড কাগজপত্র, স্বচ্ছ প্রক্রিয়ায় মালিক বা অথরাইজড সোর্সের সাথে সরাসরি কথা বলুন PropertyKoi-তে।");
        return Str::limit($desc, 165, '');
    }

    /**
     * Build dynamic Meta Keywords for a property or page.
     */
    public static function buildPropertyKeywords(Property $p): string
    {
        $area = $p->area ?: 'ঢাকা';
        $block = $p->block ?: '';
        $typeBn = $p->type_label;

        $keywords = [
            $p->title,
            "{$area} {$typeBn} বিক্রয়",
            "{$area} {$typeBn}",
            "{$area} property for sale",
            "direct owner property {$area}",
            "{$typeBn} বিক্রয় ঢাকা",
            "ভেরিফাইড প্রপার্টি ঢাকা",
            "PropertyKoi",
            "প্রপার্টি কই",
        ];

        if ($block) {
            $keywords[] = "{$area} ব্লক {$block} {$typeBn}";
            $keywords[] = "{$area} Block {$block} {$p->type} for sale";
        }

        if ($p->type === 'land' && $p->land?->katha_size) {
            $keywords[] = "{$p->land->katha_size} কাঠা জমি {$area}";
            $keywords[] = "{$area} {$p->land->katha_size} katha plot";
        } elseif ($p->type === 'flat' && $p->flat?->flat_size_sqft) {
            $keywords[] = "{$p->flat->flat_size_sqft} sqft flat in {$area}";
            $keywords[] = "রেডি ফ্ল্যাট {$area}";
            $keywords[] = "ready flat for sale in {$area}";
        } elseif ($p->type === 'commercial') {
            $keywords[] = "{$area} দোকান ও কমার্শিয়াল স্পেস";
            $keywords[] = "commercial shop for sale in {$area}";
        } elseif ($p->type === 'land_share') {
            $keywords[] = "ল্যান্ড শেয়ার প্রজেক্ট {$area}";
            $keywords[] = "land share flat in {$area}";
        }

        return implode(', ', array_unique(array_filter($keywords)));
    }

    /**
     * Build dynamic AEO (Answer Engine Optimization) FAQ items for a single property.
     */
    public static function getPropertyFaqs(Property $p): array
    {
        $area = $p->area ?: 'ঢাকা';
        $block = $p->block ? ('ব্লক ' . $p->block) : 'প্রাইম জোন';
        $road = $p->road_width_ft ? ($p->road_width_ft . ' ফুট প্রশস্ত রাস্তা') : 'প্রশস্ত পাকা রাস্তা';
        $facing = $p->facing ? ($p->facing . ' মুখী') : 'আদর্শ মুখী';

        $sizeText = match ($p->type) {
            'land' => ($p->land?->katha_size ?? $p->size ?? 'নির্ধারিত') . ' কাঠা',
            'flat' => ($p->flat?->flat_size_sqft ?? 'নির্ধারিত') . ' বর্গফুট' . ($p->flat?->bedrooms ? " ({$p->flat->bedrooms} বেড, {$p->flat->bathrooms} বাথ)" : ''),
            'commercial' => ($p->commercial?->size_sqft ?? 'নির্ধারিত') . ' বর্গফুট (' . ($p->commercial?->commercial_type ?: 'কমার্শিয়াল স্পেস') . ')',
            'land_share' => ($p->share?->flat_size_sqft ?? 'নির্ধারিত') . ' বর্গফুট ফ্ল্যাট শেয়ার (মোট জমি ' . ($p->share?->land_size_katha ?? '') . ' কাঠা)',
            default => 'যাচাইকৃত আয়তন',
        };

        $priceDetail = $p->formatted_price ?: 'আলোচনা সাপেক্ষ';
        if ($p->type === 'land' && $p->land?->price_per_katha > 0) {
            $perKathaCr = $p->land->price_per_katha >= 10000000
                ? number_format($p->land->price_per_katha / 10000000, 2) . ' কোটি টাকা/কাঠা'
                : number_format($p->land->price_per_katha / 100000, 2) . ' লাখ টাকা/কাঠা';
            $priceDetail .= " (প্রতি কাঠা: {$perKathaCr})";
        }

        return [
            [
                'question' => "এই প্রপার্টিটির (#{$p->property_code}) সঠিক লোকেশন, ব্লক এবং সামনের রাস্তার প্রশস্ততা কত?",
                'answer'   => "প্রপার্টিটি {$area}-এর {$block}-এ অবস্থিত। প্রপার্টির সামনের সড়ক {$road} এবং এটি {$facing}।",
            ],
            [
                'question' => "এই প্রপার্টির মোট আয়তন এবং প্রস্তাবিত মূল্য কত?",
                'answer'   => "প্রপার্টিটির আয়তন {$sizeText} এবং প্রস্তাবিত মূল্য {$priceDetail}।" . ($p->is_negotiable ? ' প্রকৃত ক্রেতাদের জন্য মূল্যে আলোচনার সুযোগ রয়েছে।' : ' এটি নির্ধারিত মূল্যে বিক্রির জন্য তালিকাভুক্ত।'),
            ],
            [
                'question' => "প্রপার্টির মালিকানা দলিল, নামজারি ও কাগজপত্র কি যাচাইকৃত (Verified)?",
                'answer'   => "হ্যাঁ, PropertyKoi-এর ৪-পয়েন্ট ভেরিফিকেশন প্রক্রিয়ার আওতায় মালিকের পরিচয়, সরেজমিন লোকেশন, মৌলিক মাপ এবং প্রাথমিক কাগজপত্র (দলিল, নামজারি খতিয়ান ও খাজনা) যাচাই সাপেক্ষে এটি তালিকাভুক্ত করা হয়েছে।",
            ],
            [
                'question' => "সরাসরি প্রপার্টি ভিজিট বা প্রতিনিধির সাথে যোগাযোগের উপায় কী?",
                'answer'   => "এই পেজের 'সাইট ভিজিট শিডিউল করুন' বাটনে ক্লিক করে আপনার সুবিধাজনক তারিখ ও সময়ে ভিজিট বুক করতে পারেন, অথবা সরাসরি হোয়াটসঅ্যাপ ও দর প্রস্তাব বাটনের মাধ্যমে আমাদের ডিরেক্ট কোঅর্ডিনেশন টিমের সাথে যোগাযোগ করতে পারেন।",
            ],
        ];
    }

    /**
     * Build dynamic AEO FAQ items for Hub / Search pages.
     */
    public static function getHubFaqs(string $page = 'home'): array
    {
        return match ($page) {
            'land-search' => [
                [
                    'question' => 'আফতাবনগরে আবাসিক ও কমার্শিয়াল প্লটের বর্তমান কাঠাপ্রতি দর কেমন?',
                    'answer'   => 'আফতাবনগরের ব্লক A থেকে M পর্যন্ত লোকেশন, রাস্তার প্রশস্ততা (২০ ফুট থেকে ৬০+ ফুট), কর্নার প্লট এবং লেকভিউ অবস্থানের ওপর ভিত্তি করে জমির মূল্য প্রতি কাঠা সাধারণত ৬০ লাখ থেকে ১.৫ কোটি+ টাকা পর্যন্ত হয়ে থাকে। PropertyKoi-তে সর্বশেষ ভেরিফাইড মূল্য তালিকা ফিল্টার করে দেখতে পারেন।',
                ],
                [
                    'question' => 'জমি কেনার আগে কোন কোন কাগজপত্র ও দলিল যাচাই করা জরুরি?',
                    'answer'   => 'জমি ক্রয়ের পূর্বে মূল রেজিস্ট্রি দলিল, বায়া দলিল (Chain of Title), সিএস/এসএ/আরএস/মহানগর জরিপ খতিয়ান, ইস্টার্ন হাউজিং বরাদ্দপত্র ও ছাড়পত্র, হালনাগাদ নামজারি (Mutation), ডিসিআর এবং সর্বশেষ ভূমি উন্নয়ন কর (খাজনা) রশিদ যাচাই করা আবশ্যক।',
                ],
                [
                    'question' => 'PropertyKoi-তে জমি বা ফ্ল্যাট কেনার ক্ষেত্রে স্বচ্ছতা কীভাবে বজায় রাখা হয়?',
                    'answer'   => 'PropertyKoi একটি ভেরিফাইড ও প্রফেশনাল মার্কেটপ্লেস। এখানে প্রপার্টির কাগজপত্র, সরেজমিন মাপ ও সঠিক বাজারদর সম্পূর্ণ স্বচ্ছভাবে উপস্থাপন করা হয়, যাতে ক্রেতা সরাসরি যোগাযোগ ও সাইট ভিজিটের মাধ্যমে নিশ্চিত হয়ে সেরা ডিল সম্পন্ন করতে পারেন।',
                ],
                [
                    'question' => 'How to find verified residential or commercial land in Aftabnagar, Dhaka?',
                    'answer'   => 'On PropertyKoi.com, browse 100% verified plot listings by block (A to M), road width (20ft to 60ft), and katha size. Schedule a direct site visit and inspect legal documents with complete transparency.',
                ],
            ],
            'flat-search' => [
                [
                    'question' => 'আফতাবনগরে রেডি ফ্ল্যাট এবং নির্মাণাধীন ফ্ল্যাটের মধ্যে কোনটি কেনা ভালো?',
                    'answer'   => 'তাৎক্ষণিক বসবাস বা ভাড়ার আয়ের জন্য ১০০% রেডি ফ্ল্যাট সেরা। অন্যদিকে কিস্তি সুবিধা ও তুলনামূলক কম মূল্যে ভবিষ্যতের জন্য নিশ্চিত বিনিয়োগের জন্য চলমান (Under Construction) ফ্ল্যাট প্রজেক্টগুলো সাশ্রয়ী।',
                ],
                [
                    'question' => 'ফ্ল্যাট কেনার সময় ইউটিলিটি ও কার পার্কিং চার্জ কি মূল্যের অন্তর্ভুক্ত থাকে?',
                    'answer'   => 'PropertyKoi-এর প্রতিটি ফ্ল্যাট লিস্টিংয়ের স্পেসিফিকেশন টেবিলে মোট মূল্যের পাশাপাশি গ্যারেজ/পার্কিং মূল্য, ইউটিলিটি চার্জ এবং ডাউন পেমেন্টের পরিমাণ স্পষ্টভাবে উল্লেখ থাকে।',
                ],
                [
                    'question' => 'ব্যাংক লোন সুবিধা সম্বলিত ফ্ল্যাট কীভাবে খুঁজে পাব?',
                    'answer'   => 'PropertyKoi-তে তালিকাভুক্ত বেশিরভাগ রেডি ফ্ল্যাটেই অনুমোদিত প্ল্যান ও রাজউক ছাড়পত্র থাকায় ব্যাংক বা আর্থিক প্রতিষ্ঠান থেকে হোম লোন সুবিধা পাওয়া সহজ। ফিল্টারে ব্যাংক লোন উপযোগী ফ্ল্যাটগুলো সহজেই দেখে নিতে পারেন।',
                ],
                [
                    'question' => 'What is the average price of a 3-bedroom ready flat in Aftabnagar, Dhaka?',
                    'answer'   => 'Ready 3-bedroom flats in Aftabnagar (1200 to 1800 sq ft) typically range from 75 Lakh to 1.6 Crore BDT depending on block location, building road width, lift, and generator amenities.',
                ],
            ],
            'commercial-search' => [
                [
                    'question' => 'আফতাবনগরে দোকান বা অফিস স্পেসে বিনিয়োগের সুবিধা ও ভাড়া আয় (Rental Yield) কেমন?',
                    'answer'   => 'দ্রুত বর্ধনশীল প্রিমিয়াম আবাসিক এলাকা হওয়ায় আফতাবনগর মেইন রোড, জহুরুল ইসলাম এভিনিউ এবং সংযোগ সড়কগুলোর মার্কেট ও বাণিজ্যিক ভবনে দোকান ও অফিস স্পেসে উচ্চ মাসিক ভাড়া আয় ও দ্রুত মূলধনী বৃদ্ধি (Capital Appreciation) পাওয়া যায়।',
                ],
                [
                    'question' => 'কমার্শিয়াল প্রপার্টি কেনার পূর্বে কোন অনুমোদনগুলো দেখা প্রয়োজন?',
                    'answer'   => 'বাণিজ্যিক স্পেস ক্রয়ের পূর্বে রাজউক অনুমোদিত কমার্শিয়াল প্ল্যান, ফায়ার সেফটি সার্টিফিকেট, পরিবেশ ছাড়পত্র এবং ভবনের অকুপেন্সি সার্টিফিকেট যাচাই করা জরুরি।',
                ],
            ],
            'land-share-search' => [
                [
                    'question' => 'যৌথ ল্যান্ড শেয়ারিং প্রকল্পে ফ্ল্যাট নির্মাণের খরচ সাধারণ ফ্ল্যাটের চেয়ে কতটা কম?',
                    'answer'   => 'যৌথভাবে জমি ক্রয় করে ডেভলপার প্রফিট ছাড়াই নিজস্ব খরচে ফ্ল্যাট নির্মাণ করার ফলে বাজারমূল্যের চেয়ে প্রায় ২৫% থেকে ৩৫% কম খরচে নিজস্ব ফ্ল্যাটের মালিক হওয়া সম্ভব।',
                ],
                [
                    'question' => 'ল্যান্ড শেয়ার প্রকল্পে বিনিয়োগ করার সঠিক নিয়ম কী?',
                    'answer'   => 'সবার আগে জমির খতিয়ান ও মালিকানা হিস্যা অনুযায়ী যৌথ দলিল রেজিস্ট্রি এবং নামজারি সম্পন্ন করতে হবে। এরপর যৌথ একাউন্টের মাধ্যমে কিস্তিতে নির্মাণ কাজ পরিচালনা করা হয়।',
                ],
            ],
            'chahida' => [
                [
                    'question' => 'PropertyKoi-তে ক্রেতারা কীভাবে তাদের প্রপার্টি রিকোয়ারমেন্ট বা চাহিদা জমা দিতে পারেন?',
                    'answer'   => 'PropertyKoi-এর "চাহিদা জানান (Chahida)" পেজে গিয়ে আপনার পছন্দের এলাকা (যেমন আফতাবনগর), প্রপার্টির ধরন (জমি, ফ্ল্যাট বা দোকান), বাজেট ও সাইজ লিখে সহজেই সাবমিট করতে পারেন। এতে কোনো রেজিস্ট্রেশন ফি প্রয়োজন হয় না।',
                ],
                [
                    'question' => 'স্বয়ংক্রিয় এআই ডিমান্ড ম্যাচিং ইঞ্জিন কীভাবে কাজ করে?',
                    'answer'   => 'আমাদের স্মার্ট ম্যাচিং ইঞ্জিন নতুন কোনো জমি বা ফ্ল্যাট লিস্টিং আসামাত্রই ক্রেতাদের পূর্বের চাহিদার সাথে বাজেট (±১৫%), ব্লক, রাস্তার মাপ ইত্যাদি ক্রস-ম্যাচ করে তাৎক্ষণিকভাবে উপযুক্ত লিস্টিং খুঁজে বের করে।',
                ],
                [
                    'question' => 'টেলিগ্রাম, ইমেইল ও পুশ নোটিফিকেশনে প্রপার্টি ম্যাচিং অ্যালার্ট কীভাবে পাওয়া যায়?',
                    'answer'   => 'চাহিদা সাবমিটের সময় টেলিগ্রাম এবং ইমেইল অপশন সিলেক্ট করলে উপযুক্ত প্রপার্টি আসার সাথে সাথেই আপনার টেলিগ্রাম বট চ্যাটে এবং ইনবক্সে ছবি ও বিস্তারিত লিংকসহ স্বয়ংক্রিয় নোটিফিকেশন পৌঁছে যায়।',
                ],
                [
                    'question' => 'একজন ক্রেতা সর্বোচ্চ কয়টি চাহিদা সাবমিট করতে পারেন?',
                    'answer'   => 'প্ল্যাটফর্মের স্বচ্ছতা ও সুষ্ঠু ব্যবস্থাপনার স্বার্থে একজন ব্যবহারকারী সর্বোচ্চ ৩টি সক্রিয় চাহিদা জমা রাখতে পারেন। নতুন চাহিদা দিতে হলে বায়ার ড্যাশবোর্ড থেকে পূর্বের যেকোনো একটি চাহিদা মুছে ফেলা যায়।',
                ],
            ],
            'sell-property' => [
                [
                    'question' => 'PropertyKoi-তে জমি বা ফ্ল্যাট বিক্রির বিজ্ঞাপন কীভাবে ফ্রিতে পোস্ট করব?',
                    'answer'   => '"প্রপার্টি বিক্রি করুন" পেজে গিয়ে প্রপার্টির ধরন, ব্লক, রোড, সাইজ, ছবি এবং আপনার যোগাযোগের নম্বর দিয়ে ১-ক্লিকেই লিস্টিং জমা দিতে পারেন। এটি সম্পূর্ণ বিনামূল্যে।',
                ],
                [
                    'question' => 'বিজ্ঞাপন পোস্ট করার পর কত দ্রুত ক্রেতা পাওয়া যায়?',
                    'answer'   => 'আপনার বিজ্ঞাপন অনুমোদিত হওয়ার সাথে সাথে আমাদের ডাটাবেজে থাকা শত শত সক্রিয় ক্রেতার চাহিদার সাথে ম্যাচ হয়ে স্বয়ংক্রিয়ভাবে পুশ ও টেলিগ্রাম অ্যালার্ট চলে যায়, ফলে দ্রুত প্রকৃত ক্রেতা পাওয়া সম্ভব হয়।',
                ],
                [
                    'question' => 'মালিকের গোপনীয়তা ও তথ্য কি সুরক্ষিত থাকে?',
                    'answer'   => 'হ্যাঁ। আপনি চাইলে প্রপার্টির প্লট বা হোল্ডিং নম্বর গোপন রাখতে পারেন এবং শুধুমাত্র অ্যাডমিন অনুমোদিত যাচাইকৃত ক্রেতাদের সাথে কথা বলতে পারেন।',
                ],
            ],
            'guides' => [
                [
                    'question' => 'প্রপার্টি গাইড ও নলেজ হাবে কী ধরনের তথ্য পাওয়া যায়?',
                    'answer'   => 'জমির মূল দলিল, বায়া দলিল ও নামজারি খতিয়ান যাচাই, ফ্ল্যাট ক্রয়ে রাজউক নকশা অনুমোদন পরীক্ষা, জমি ও ফ্ল্যাটের বর্তমান বাজার দর, এবং ল্যান্ড শেয়ার বিনিয়োগ সংক্রান্ত নির্ভরযোগ্য সরকারি ও প্রফেশনাল গাইডলাইন পাওয়া যায়।',
                ],
                [
                    'question' => 'গাইডগুলো কি বাংলাদেশের সর্বশেষ আইন ও নিয়মের সাথে সামঞ্জস্যপূর্ণ?',
                    'answer'   => 'হ্যাঁ, আমাদের প্রতিটি গাইড ভূমি মন্ত্রণালয়, সাব-রেজিস্ট্রি অধিদপ্তর ও রাজউকের সর্বশেষ সরকারি বিধিমালা ও হালনাগাদ সার্কুলার অনুযায়ী আইন ও রিয়েল এস্টেট বিশেষজ্ঞদের দ্বারা নিয়মিত আপডেট করা হয়।',
                ],
            ],
            'calculators' => [
                [
                    'question' => 'প্রপার্টি ক্যালকুলেটরের মাধ্যমে কী কী হিসাব করা যায়?',
                    'answer'   => 'বাংলাদেশে জমি ও ফ্ল্যাট রেজিস্ট্রেশনের সরকারি খরচ (স্ট্যাম্প শুল্ক, নিবন্ধন ফি, স্থানীয় কর, উৎসে কর) এবং ল্যান্ড শেয়ার প্রকল্পে ফ্ল্যাট নির্মাণে সম্ভাব্য সাশ্রয় ও লাভ রিয়েলটাইমে হিসাব করা যায়।',
                ],
                [
                    'question' => 'ক্যালকুলেটরের হিসাব কতটা নির্ভুল?',
                    'answer'   => 'এটি ২০২৬ সালের অর্থ মন্ত্রণালয় ও জাতীয় রাজস্ব বোর্ডের (এনবিআর) সর্বশেষ এলাকাভিত্তিক করহার অনুযায়ী তৈরি। তবে মৌজা মূল্যের তারতম্যের কারণে বাস্তব ক্ষেত্রে সামান্য পার্থক্য হতে পারে।',
                ],
            ],
            'valuation' => [
                [
                    'question' => 'স্মার্ট ভ্যালুয়েশন এস্টিমেটর কীভাবে প্রপার্টির মূল্য হিসাব করে?',
                    'answer'   => 'এলাকা (আফতাবনগর, বসুন্ধরা, গুলশান ইত্যাদি), রাস্তার প্রশস্ততা (২০-৬০ ফুট), ফ্ল্যাটের ফ্লোর লেভেল, পার্কিং সুবিধা ও কর্নার/লেকভিউ অবস্থানের ওপর ভিত্তি করে আমাদের অ্যানালিটিক্স ইঞ্জিন বর্তমান ফেয়ার মার্কেট রেঞ্জ নির্ধারণ করে।',
                ],
                [
                    'question' => 'এই মূল্যায়ন কি বিনামূল্যে ব্যবহার করা যায়?',
                    'answer'   => 'হ্যাঁ, ক্রেতা ও বিক্রেতা উভয়েই সম্পূর্ণ বিনামূল্যে যেকোনো এলাকার প্রপার্টির আনুমানিক বাজারদর যাচাই করতে পারেন।',
                ],
            ],
            default => [
                [
                    'question' => 'PropertyKoi.com কী এবং এটি কীভাবে বাংলাদেশের একটি বিশ্বস্ত প্রপার্টি প্ল্যাটফর্ম (Property Buy & Sell Platform in Bangladesh)?',
                    'answer'   => 'PropertyKoi হলো ঢাকা, বসুন্ধরা, গুলশান, বনশ্রী ও আফতাবনগরসহ যেকোনো এলাকার সরাসরি রিয়েল এস্টেট কেনা-বেচার আধুনিক মার্কেটপ্লেস (Property Buy Sell Platform in Bangladesh)। এখানে ১০০% ভেরিফাইড জমি, রেডি ফ্ল্যাট, দোকান ও ল্যান্ড শেয়ার স্বচ্ছ প্রক্রিয়ায় প্রকৃত মালিক বা অথরাইজড সোর্সের সাথে কেনাবেচা করা যায়।',
                ],
                [
                    'question' => 'PropertyKoi-তে সরাসরি ভেরিফাইড জমি বা ফ্ল্যাট কেনার প্রক্রিয়া কী?',
                    'answer'   => 'PropertyKoi-তে প্রতিটি লিস্টিংয়ের সাথে "সাইট ভিজিট শিডিউল করুন" এবং সরাসরি যোগাযোগ সুবিধা রয়েছে। এখানে ক্লিক করে আপনি যাচাইকৃত তথ্যসহ সরাসরি কথা বলে সরেজমিন প্রপার্টি দেখতে পারবেন।',
                ],
                [
                    'question' => 'PropertyKoi-এর ৪-পয়েন্ট ভেরিফিকেশন (4-Point Verification) কী?',
                    'answer'   => 'আমাদের টিম প্রতিটি প্রপার্টির (১) মালিকের সত্যতা ও জাতীয় পরিচয়পত্র, (২) সরেজমিন অবস্থান ও রাস্তার প্রশস্ততা, (৩) মূল দলিল ও বায়া দলিল রেকর্ড এবং (৪) হালনাগাদ নামজারি ও খাজনা রশিদ যাচাই করে "Verified" ব্যাজ প্রদান করে।',
                ],
                [
                    'question' => 'How does PropertyKoi compare with traditional real estate classified websites in Bangladesh?',
                    'answer'   => 'Unlike unverified classifieds with inaccurate listings, PropertyKoi focuses on verified direct listings, 4-point title verification, and automated buyer-demand notifications via Telegram & Email, ensuring 100% transparent transactions.',
                ],
                [
                    'question' => 'পছন্দের প্রপার্টি খুঁজে না পেলে করণীয় কী?',
                    'answer'   => 'আমাদের "চাহিদা জানান (Chahida)" পেজে আপনার কাঙ্ক্ষিত বাজেট ও এলাকা সাবমিট করে রাখুন। নতুন কোনো প্রপার্টি তালিকাভুক্ত হওয়ামাত্রই আমাদের সিস্টেম আপনাকে স্বয়ংক্রিয়ভাবে নোটিফিকেশন পাঠাবে।',
                ],
            ],
        };
    }

    /**
     * Get Complete SEO, Open Graph, Twitter & AEO Metadata for any page or property.
     */
    public static function getOgMeta(string $page = '', ?Property $property = null): array
    {
        $siteName = self::getSiteName();
        $siteDesc = 'PropertyKoi.com হলো বাংলাদেশের শীর্ষস্থানীয় প্রপার্টি কেনা-বেচার প্ল্যাটফর্ম (Property Buy & Sell Platform in Bangladesh)। স্বচ্ছ ও ভেরিফাইড প্রক্রিয়ায় ঢাকা, বসুন্ধরা, গুলশান, বনশ্রী ও আফতাবনগরের ভেরিফাইড আবাসিক প্লট, জমি, রেডি ফ্ল্যাট, কমার্শিয়াল স্পেস ও ল্যান্ড শেয়ার সরাসরি কেনাবেচা করুন।';
        $defaultKeywords = 'property buy sell platform in bangladesh, real estate marketplace bangladesh, buy sell plot flat dhaka, bashundhara flat for sale, gulshan property for sale, aftabnagar plot for sale, ready flat in dhaka, direct owner property bangladesh, জমি ফ্ল্যাট কেনাবেচা ঢাকা, বসুন্ধরা ফ্ল্যাট, আফতাবনগর জমি বিক্রয়, রেডি ফ্ল্যাট ঢাকা, প্রপার্টি কই, PropertyKoi';

        if ($property) {
            $typeLabel = $property->type_label;
            $loc = ($property->area ?: 'ঢাকা') . ($property->block ? ' • ব্লক ' . $property->block : '');
            $title = "{$property->title} | {$typeLabel} - {$loc} | {$siteName}";
            $desc = self::buildPropertyDescription($property);
            $keywords = self::buildPropertyKeywords($property);
            $canonicalUrl = route('property.show', $property->slug ?: $property->id);

            $defaultTypeAsset = match ($property->type) {
                'flat' => 'og-flat.jpg',
                'commercial', 'land_share' => 'og-commercial.jpg',
                default => 'og-land.jpg',
            };

            // Resolve primary cover image for all OG and Social previews
            $coverMedia = null;
            if ($property->relationLoaded('media')) {
                $coverMedia = $property->media->firstWhere('is_cover', true) ?? $property->media->first();
            } elseif ($property->media()->exists()) {
                $coverMedia = $property->media()->where('is_cover', true)->first() ?? $property->media()->first();
            }
            $coverUrl = ($coverMedia && !empty($coverMedia->file_path))
                ? $coverMedia->file_path
                : $property->cover_url;
            $primaryImage = self::formatOgImageUrl($coverUrl, $defaultTypeAsset);
            $images = [$primaryImage];
            $faqs = self::getPropertyFaqs($property);

            return [
                'title'       => $title,
                'description' => $desc,
                'keywords'    => $keywords,
                'image'       => $primaryImage,
                'images'      => $images,
                'image_alt'   => "{$property->title} - {$loc}",
                'url'         => $canonicalUrl,
                'type'        => 'article',
                'robots'      => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
                'faqs'        => $faqs,
                'schema'      => self::getPropertySchema($property, $canonicalUrl, $title, $desc, $primaryImage, $faqs),
            ];
        }

        $faqs = self::getHubFaqs($page);

        $meta = match ($page) {
            'land-search' => [
                'title'       => "Plots & Land for Sale in Dhaka & Aftabnagar | জমি ও প্লট বিক্রয় — {$siteName}",
                'description' => 'ঢাকা, বসুন্ধরা, আফতাবনগর ও সংলগ্ন এলাকার ভেরিফাইড আবাসিক ও বাণিজ্যিক প্লট। ২০ থেকে ৬০ ফুট রাস্তা, কর্নার ও লেকভিউ প্লট কিনুন ১০০% যাচাইকৃত নামজারি ও দলিলসহ। নিরাপদ ও নির্ভরযোগ্য প্ল্যাটফর্ম।',
                'keywords'    => 'plots for sale in dhaka, aftabnagar plot for sale, buy land in bashundhara, buy land in aftabnagar dhaka, residential plot for sale dhaka, aftabnagar katha price, direct owner plot, আফতাবনগর জমি বিক্রয়, বসুন্ধরা জমি, প্লট কেনাবেচা ঢাকা, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_land_og_image', 'og-land.jpg'),
                'url'         => route('properties.land'),
                'type'        => 'website',
            ],
            'flat-search' => [
                'title'       => "Ready Flats & Apartments for Sale in Dhaka | ফ্ল্যাট বিক্রয় — {$siteName}",
                'description' => 'ঢাকা, বসুন্ধরা, গুলশান, আফতাবনগর ও বনশ্রীতে ১০০% রেডি এবং নির্মাণাধীন আধুনিক ৩/৪ বেডরুমের ফ্ল্যাট ও অ্যাপার্টমেন্ট। লিফট, জেনারেটর ও কার পার্কিংসহ ভেরিফাইড মালিক ও নির্মাতাদের লিস্টিং। স্বচ্ছ প্রক্রিয়ায় সেরা ডিল।',
                'keywords'    => 'ready flat in dhaka, flat in bashundhara, flat for sale in gulshan, apartment for sale in aftabnagar dhaka, 3 bed flat dhaka, luxury flat dhaka, direct owner flat sale, রেডি ফ্ল্যাট ঢাকা, বসুন্ধরা ফ্ল্যাট, গুলশান ফ্ল্যাট, ফ্ল্যাট বিক্রয় ঢাকা, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_flat_og_image', 'og-flat.jpg'),
                'url'         => route('properties.flat'),
                'type'        => 'website',
            ],
            'commercial-search' => [
                'title'       => "Commercial Space & Shops for Sale in Dhaka | দোকান ও বাণিজ্যিক স্পেস — {$siteName}",
                'description' => 'আফতাবনগর ও ঢাকার প্রধান সড়কে দোকান, শোরুম, অফিস স্পেস ও ওপেন ফ্লোর বিক্রয়। উচ্চ ভাড়া আয় ও নিশ্চিত মূলধনী বৃদ্ধির বাণিজ্যিক বিনিয়োগের সেরা ভেরিফাইড লিস্টিং।',
                'keywords'    => 'commercial space for sale dhaka, shop for sale in aftabnagar, showroom space aftabnagar, office space for sale dhaka, দোকান বিক্রয় আফতাবনগর, বাণিজ্যিক স্পেস ঢাকা, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_commercial_og_image', 'og-commercial.jpg'),
                'url'         => route('properties.commercial'),
                'type'        => 'website',
            ],
            'land-share-search' => [
                'title'       => "Land Share Projects in Aftabnagar, Dhaka | যৌথ ল্যান্ড শেয়ার প্রকল্প — {$siteName}",
                'description' => 'যৌথভাবে জমি কিনে সাশ্রয়ী খরচে আধুনিক ফ্ল্যাট নির্মাণের ভেরিফাইড ল্যান্ড শেয়ার প্রজেক্ট। বাজারদরের চেয়ে ৩০-৩৫% কম খরচে নিজস্ব ফ্ল্যাটের মালিক হোন।',
                'keywords'    => 'land share flat in aftabnagar, land share project dhaka, affordable flat share bangladesh, ল্যান্ড শেয়ার প্রজেক্ট ঢাকা, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_commercial_og_image', 'og-commercial.jpg'),
                'url'         => route('properties.share'),
                'type'        => 'website',
            ],
            'all-search' => [
                'title'       => "Verified Properties for Sale in Dhaka | সকল প্রপার্টি লিস্টিং — {$siteName}",
                'description' => 'আফতাবনগর, বসুন্ধরা, পূর্বাচল ও ঢাকার সকল যাচাইকৃত জমি, রেডি ফ্ল্যাট, দোকান এবং ল্যান্ড শেয়ার প্রপার্টি এক জায়গায় খুঁজুন ও তুলনা করুন। সম্পূর্ণ ভেরিফাইড ও নির্ভরযোগ্য।',
                'keywords'    => $defaultKeywords,
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => route('properties.index'),
                'type'        => 'website',
            ],
            'map-view' => [
                'title'       => "Aftabnagar Plot & Flat Interactive Map View | ম্যাপ ভিউ — {$siteName}",
                'description' => 'ম্যাপে সরাসরি প্রতিটি ব্লক, রোড ও প্লটের সঠিক ভৌগোলিক অবস্থান, রাস্তার প্রশস্ততা ও কাঠাপ্রতি মূল্য যাচাই করুন।',
                'keywords'    => 'আফতাবনগর ম্যাপ, প্রপার্টি ম্যাপ ভিউ, Aftabnagar block map, plot location map Dhaka, PropertyKoi map',
                'image'       => self::getCustomOrAssetImage('adm_seo_land_og_image', 'og-land.jpg'),
                'url'         => route('map.view'),
                'type'        => 'website',
            ],
            'sell-property' => [
                'title'       => "Post Free Property Ad in Bangladesh | প্রপার্টি বিক্রির ফ্রি বিজ্ঞাপন দিন — {$siteName}",
                'description' => 'আপনার জমি, ফ্ল্যাট বা দোকান বিক্রির বিজ্ঞাপন সম্পূর্ণ ফ্রিতে পোস্ট করুন। সরাসরি হাজারো প্রকৃত ক্রেতার সাথে সংযোগ স্থাপন করে দ্রুত ডিল সম্পন্ন করুন।',
                'keywords'    => 'post free property ad bangladesh, sell property online dhaka, free real estate listing bangladesh, post flat land ad, প্রপার্টি বিক্রির বিজ্ঞাপন, জমি বিক্রি, ফ্ল্যাট বিক্রি, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_sell_og_image', 'og-sell.jpg'),
                'url'         => route('property.create'),
                'type'        => 'website',
            ],
            'chahida' => [
                'title'       => "Submit Property Requirements & Buyer Demand | প্রপার্টি চাহিদা জানান — {$siteName}",
                'description' => 'ঢাকা ও আফতাবনগরে পছন্দের জমি বা ফ্ল্যাট খুঁজছেন? আপনার বাজেট ও পছন্দের চাহিদা সাবমিট করুন। আমাদের এআই ইঞ্জিন স্বয়ংক্রিয়ভাবে সরাসরি মালিকের প্রপার্টি ম্যাচ করে তাৎক্ষণিক টেলিগ্রাম ও ইমেইল অ্যালার্ট পাঠাবে।',
                'keywords'    => 'buyer property requirement dhaka, property demand submission bangladesh, plot flat buyer alert, aftabnagar property wanted, প্রপার্টি চাহিদা ঢাকা, জমি কিনতে চাই, PropertyKoi chahida',
                'image'       => self::getCustomOrAssetImage('adm_seo_chahida_og_image', 'og-chahida.jpg'),
                'url'         => route('chahida.index'),
                'type'        => 'website',
            ],
            'compare' => [
                'title'       => "প্রপার্টি তুলনা ম্যাট্রিক্স | Compare Properties — {$siteName}",
                'description' => 'একাধিক জমি, ফ্ল্যাট বা কমার্শিয়াল স্পেসের মূল্য, আকার, রাস্তা ও সুযোগ-সুবিধা পাশাপাশি তুলনা করে সেরা সিদ্ধান্ত নিন।',
                'keywords'    => 'প্রপার্টি তুলনা, জমি ও ফ্ল্যাট কম্পেয়ার, compare properties Dhaka, Aftabnagar plot comparison, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_flat_og_image', 'og-flat.jpg'),
                'url'         => route('compare.index'),
                'type'        => 'website',
            ],
            'shortlist' => [
                'title'       => "আমার পছন্দের প্রপার্টি শর্টলিস্ট | Saved Properties — {$siteName}",
                'description' => 'আপনার পছন্দের সেইভ করা জমি, ফ্ল্যাট ও কমার্শিয়াল প্রপার্টিসমূহ এক নজরে দেখুন ও তুলনা করুন।',
                'keywords'    => 'শর্টলিস্ট প্রপার্টি, পছন্দের জমি ও ফ্ল্যাট, saved properties PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_chahida_og_image', 'og-chahida.jpg'),
                'url'         => route('shortlist'),
                'type'        => 'website',
            ],
            'guides' => [
                'title'       => "Real Estate Guides & Property Insights | প্রপার্টি গাইড ও নলেজ হাব — {$siteName}",
                'description' => 'জমির দলিল যাচাই, নামজারি খতিয়ান, ফ্ল্যাট ক্রয়ে রাজউক অনুমোদন এবং ল্যান্ড শেয়ার প্রকল্পের নির্ভরযোগ্য আইনি ও প্র্যাকটিক্যাল গাইড।',
                'keywords'    => 'real estate guides bangladesh, jomir dolil jachai, namjari khotian, rajuk plan approval, land share project guide, প্রপার্টি গাইড, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => route('guides.index'),
                'type'        => 'website',
            ],
            'calculators' => [
                'title'       => "Property Calculators & ROI Tools | প্রপার্টি ক্যালকুলেটর — {$siteName}",
                'description' => 'বাংলাদেশে জমি ও ফ্ল্যাট রেজিস্ট্রেশনের সরকারি খরচ ও কর হিসাব এবং ল্যান্ড শেয়ার প্রকল্পের সম্ভাব্য সাশ্রয় রিয়েলটাইমে গণনা করুন।',
                'keywords'    => 'property registration cost calculator bangladesh, stamp duty calculator, land share roi calculator, ফ্ল্যাট রেজিস্ট্রেশন খরচ, প্রপার্টি ক্যালকুলেটর, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => route('calculators.index'),
                'type'        => 'website',
            ],
            'calculator-registration' => [
                'title'       => "Property Registration Fee Calculator 2026 | জমি ও ফ্ল্যাট রেজিস্ট্রেশন খরচ ক্যালকুলেটর — {$siteName}",
                'description' => 'স্ট্যাম্প ডিউটি, নিবন্ধন ফি, স্থানীয় সরকার কর ও উৎসে করসহ ২০২৬ সালের সর্বশেষ সরকারি নিয়মে রেজিস্ট্রেশন খরচের নিখুঁত হিসাব করুন।',
                'keywords'    => 'registration fee calculator bangladesh 2026, stamp duty bangladesh, flat registration cost dhaka, জমির রেজিস্ট্রেশন খরচ, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => route('calculators.registration'),
                'type'        => 'website',
            ],
            'calculator-land-share' => [
                'title'       => "Land Share ROI & Savings Calculator | ল্যান্ড শেয়ার সেভিংস ক্যালকুলেটর — {$siteName}",
                'description' => 'রেডি ফ্ল্যাটের সাথে ল্যান্ড শেয়ার প্রকল্পের খরচ তুলনা করে কত লক্ষ টাকা সাশ্রয় ও লাভ হবে তা তাৎক্ষণিক হিসাব করুন।',
                'keywords'    => 'land share roi calculator bangladesh, flat construction cost savings, ল্যান্ড শেয়ার লাভ হিসাব, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => route('calculators.land-share'),
                'type'        => 'website',
            ],
            'valuation' => [
                'title'       => "Smart Property Valuation Estimator | প্রপার্টি মার্কেট ভ্যালু এস্টিমেটর — {$siteName}",
                'description' => 'ঢাকা, বসুন্ধরা, গুলশান, বনশ্রী ও আফতাবনগরের জমি ও ফ্ল্যাটের বর্তমান আনুমানিক বাজারমূল্য, রাস্তার প্রশস্ততা ও ফ্লোর অনুযায়ী রিয়েলটাইমে যাচাই করুন।',
                'keywords'    => 'property valuation bangladesh, dhaka flat price estimator, plot price aftabnagar bashundhara, জমির দাম যাচাই, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => route('valuation.index'),
                'type'        => 'website',
            ],
            default => [
                'title'       => "Property Buy & Sell Platform in Bangladesh — জমি ও ফ্ল্যাট কেনাবেচার মার্কেটপ্লেস | {$siteName}",
                'description' => $siteDesc,
                'keywords'    => $defaultKeywords,
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => url('/'),
                'type'        => 'website',
            ],
        };

        $meta['images'] = [$meta['image']];
        $meta['image_alt'] = $meta['title'];
        $meta['robots'] = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        $meta['faqs'] = $faqs;
        $meta['schema'] = self::getWebsiteSchema($meta['title'], $meta['description'], $meta['url'], $faqs);

        return $meta;
    }

    /**
     * Build comprehensive Schema.org @graph for Hub & Homepage (SEO + AEO FAQPage).
     */
    public static function getWebsiteSchema(?string $title = null, ?string $desc = null, ?string $url = null, array $faqs = []): array
    {
        $siteName = self::getSiteName();
        $siteUrl = url('/');
        $currentUrl = $url ?: $siteUrl;

        $graph = [
            [
                '@type'                  => 'WebSite',
                '@id'                    => $siteUrl . '#website',
                'url'                    => $siteUrl,
                'name'                   => $siteName,
                'alternateName'          => ['Property Koi', 'PropertyKoi', 'PropertyKoi.com', 'প্রপার্টি কই', 'Property Buy Sell Platform Bangladesh'],
                'description'            => 'PropertyKoi is Bangladesh\'s premier direct-owner property buy and sell platform and real estate marketplace for verified land, ready flats, land shares, and commercial spaces in Dhaka.',
                'inLanguage'             => ['bn-BD', 'en-US'],
                'potentialAction'        => [
                    '@type'       => 'SearchAction',
                    'target'      => route('properties.index') . '?search={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type'                   => 'RealEstateAgent',
                '@id'                     => $siteUrl . '#organization',
                'name'                    => $siteName,
                'alternateName'           => ['Property Koi', 'PropertyKoi', 'PropertyKoi.com'],
                'url'                     => $siteUrl,
                'logo'                    => asset('assets/icons/icon-512x512.png'),
                'image'                   => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'description'             => $desc ?: 'PropertyKoi.com হলো বাংলাদেশের বিশ্বস্ত প্রপার্টি কেনা-বেচার প্ল্যাটফর্ম (Property Buy Sell Platform in Bangladesh)।',
                'disambiguatingDescription' => 'PropertyKoi is a direct real estate buy and sell platform in Bangladesh specializing in verified land, flats, and commercial properties in Dhaka.',
                'slogan'                  => 'বাংলাদেশের বিশ্বস্ত প্রপার্টি মার্কেটপ্লেস — ১০০% ভেরিফাইড ও স্বচ্ছ লেনদেন',
                'telephone'               => Setting::get('adm_admin_whatsapp_number', Setting::get('support_phone', '+8801672646242')),
                'knowsAbout'              => [
                    'Real Estate in Bangladesh',
                    'Property Buy and Sell Platform',
                    'Plots and Land for Sale in Dhaka',
                    'Bashundhara R/A Plots and Flats',
                    'Gulshan Apartments and Real Estate',
                    'Aftabnagar Plots and Flats',
                    'Ready Apartments for Sale in Dhaka',
                    'Commercial Space for Sale in Dhaka',
                    'Land Share Projects in Bangladesh',
                    'Property Title Verification and Legal Due Diligence',
                ],
                'hasOfferCatalog'         => [
                    '@type'            => 'OfferCatalog',
                    'name'             => 'PropertyKoi Real Estate Categories',
                    'itemListElement'  => [
                        [
                            '@type' => 'OfferCatalog',
                            'name'  => 'Residential Land & Plots for Sale',
                            'url'   => route('properties.land'),
                        ],
                        [
                            '@type' => 'OfferCatalog',
                            'name'  => 'Ready & Ongoing Flats and Apartments for Sale',
                            'url'   => route('properties.flat'),
                        ],
                        [
                            '@type' => 'OfferCatalog',
                            'name'  => 'Commercial Space & Shops for Sale',
                            'url'   => route('properties.commercial'),
                        ],
                        [
                            '@type' => 'OfferCatalog',
                            'name'  => 'Land Share Projects',
                            'url'   => route('properties.share'),
                        ],
                    ],
                ],
                'address'                 => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => 'Main Road, Block A',
                    'addressLocality' => 'Aftabnagar, Badda',
                    'addressRegion'   => 'Dhaka',
                    'postalCode'      => '1212',
                    'addressCountry'  => 'BD',
                ],
                'areaServed'              => [
                    ['@type' => 'AdministrativeArea', 'name' => 'Dhaka, Bangladesh'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Bashundhara R/A, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Gulshan, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Aftabnagar, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Banasree, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Purbachal, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Uttara, Dhaka'],
                    ['@type' => 'Country', 'name' => 'Bangladesh'],
                ],
            ],
        ];

        if (!empty($faqs)) {
            $faqEntities = [];
            foreach ($faqs as $f) {
                $faqEntities[] = [
                    '@type'          => 'Question',
                    'name'           => $f['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $f['answer'],
                    ],
                ];
            }
            $graph[] = [
                '@type'      => 'FAQPage',
                '@id'        => $currentUrl . '#faq',
                'mainEntity' => $faqEntities,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];
    }

    /**
     * Build comprehensive Schema.org @graph for a single Property (RealEstateListing + Place/Residence + BreadcrumbList + AEO FAQPage).
     */
    public static function getPropertySchema(
        Property $prop,
        ?string $url = null,
        ?string $title = null,
        ?string $desc = null,
        ?string $image = null,
        array $faqs = []
    ): array {
        $url = $url ?: route('property.show', $prop->slug ?: $prop->id);
        $desc = $desc ?: self::buildPropertyDescription($prop);
        $image = $image ?: self::formatOgImageUrl($prop->cover_url);
        $faqs = !empty($faqs) ? $faqs : self::getPropertyFaqs($prop);

        $isLand = $prop->type === 'land';
        $isFlat = $prop->type === 'flat';
        $isCommercial = $prop->type === 'commercial';
        $isShare = $prop->type === 'land_share';

        $itemType = $isLand ? 'Place' : ($isCommercial ? 'Place' : 'SingleFamilyResidence');

        $address = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => ($prop->block ? 'Block ' . $prop->block : '') . ($prop->road_number ? ', Road ' . $prop->road_number : '') . ($prop->road_width_ft ? ' (' . $prop->road_width_ft . 'ft Road)' : ''),
            'addressLocality' => $prop->area ?: 'Dhaka',
            'addressRegion'   => 'Dhaka',
            'postalCode'      => '1212',
            'addressCountry'  => 'BD',
        ];

        $itemOffered = [
            '@type'       => $itemType,
            'name'        => $prop->title,
            'description' => strip_tags($prop->description ?: $desc),
            'address'     => $address,
            'image'       => $image,
        ];

        if ($prop->latitude && $prop->longitude) {
            $itemOffered['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float) $prop->latitude,
                'longitude' => (float) $prop->longitude,
            ];
        }

        if ($isFlat && $prop->flat) {
            if ($prop->flat->flat_size_sqft) {
                $itemOffered['floorSize'] = [
                    '@type'    => 'QuantitativeValue',
                    'value'    => (float) $prop->flat->flat_size_sqft,
                    'unitCode' => 'FTK',
                ];
            }
            if ($prop->flat->bedrooms) {
                $itemOffered['numberOfRooms'] = (int) $prop->flat->bedrooms;
                $itemOffered['numberOfBedrooms'] = (int) $prop->flat->bedrooms;
            }
            if ($prop->flat->bathrooms) {
                $itemOffered['numberOfBathroomsTotal'] = (int) $prop->flat->bathrooms;
            }
        } elseif ($isCommercial && $prop->commercial?->size_sqft) {
            $itemOffered['floorSize'] = [
                '@type'    => 'QuantitativeValue',
                'value'    => (float) $prop->commercial->size_sqft,
                'unitCode' => 'FTK',
            ];
        } elseif ($isLand && $prop->land?->katha_size) {
            $itemOffered['floorSize'] = [
                '@type'    => 'QuantitativeValue',
                'value'    => (float) $prop->land->katha_size,
                'unitText' => 'Katha',
            ];
        } elseif ($isShare && $prop->share?->flat_size_sqft) {
            $itemOffered['floorSize'] = [
                '@type'    => 'QuantitativeValue',
                'value'    => (float) $prop->share->flat_size_sqft,
                'unitCode' => 'FTK',
            ];
        }

        $categoryUrl = match ($prop->type) {
            'land' => route('properties.land'),
            'flat' => route('properties.flat'),
            'commercial' => route('properties.commercial'),
            'land_share' => route('properties.share'),
            default => route('properties.index'),
        };

        $faqEntities = [];
        foreach ($faqs as $f) {
            $faqEntities[] = [
                '@type'          => 'Question',
                'name'           => $f['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $f['answer'],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'        => 'RealEstateListing',
                    '@id'          => $url . '#listing',
                    'name'         => $prop->title,
                    'description'  => $desc,
                    'url'          => $url,
                    'image'        => $image,
                    'datePosted'   => $prop->created_at ? $prop->created_at->toIso8601String() : now()->toIso8601String(),
                    'dateModified' => $prop->updated_at ? $prop->updated_at->toIso8601String() : now()->toIso8601String(),
                    'mainEntity'   => $itemOffered,
                    'offers'       => [
                        '@type'            => 'Offer',
                        'price'            => $prop->raw_price > 0 ? $prop->raw_price : null,
                        'priceCurrency'    => 'BDT',
                        'availability'     => $prop->status === 'sold' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
                        'url'              => $url,
                        'businessFunction' => 'https://schema.org/SellAction',
                        'seller'           => [
                            '@type' => 'Organization',
                            'name'  => 'PropertyKoi',
                            'url'   => url('/'),
                        ],
                    ],
                    'speakable'    => [
                        '@type'       => 'SpeakableSpecification',
                        'cssSelector' => ['h1', '.property-summary-text', '.property-specs-card'],
                    ],
                ],
                [
                    '@type'           => 'BreadcrumbList',
                    '@id'             => $url . '#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type'    => 'ListItem',
                            'position' => 1,
                            'name'     => 'হোম',
                            'item'     => url('/'),
                        ],
                        [
                            '@type'    => 'ListItem',
                            'position' => 2,
                            'name'     => $prop->type_label,
                            'item'     => $categoryUrl,
                        ],
                        [
                            '@type'    => 'ListItem',
                            'position' => 3,
                            'name'     => $prop->title,
                            'item'     => $url,
                        ],
                    ],
                ],
                [
                    '@type'      => 'FAQPage',
                    '@id'        => $url . '#faq',
                    'mainEntity' => $faqEntities,
                ],
            ],
        ];
    }

    /**
     * Get Complete SEO, Open Graph, Twitter & AEO Metadata for a Guide article.
     */
    public static function getGuideMeta(Guide $guide): array
    {
        $siteName = self::getSiteName();
        $title = $guide->seo_title ?: "{$guide->title} | প্রপার্টি গাইড — {$siteName}";
        $desc = $guide->seo_description ?: ($guide->excerpt ?: Str::limit(strip_tags($guide->content), 160));
        $keywords = $guide->seo_keywords ?: "{$guide->title}, {$guide->category_label}, real estate guide bangladesh, jomir dolil namjari, PropertyKoi";
        $url = route('guides.show', $guide->slug);
        $image = self::formatOgImageUrl($guide->cover_url, 'og-default.jpg');
        $faqs = is_array($guide->faqs) ? $guide->faqs : [];

        return [
            'title'       => $title,
            'description' => $desc,
            'keywords'    => $keywords,
            'image'       => $image,
            'images'      => [$image],
            'image_alt'   => $guide->title,
            'url'         => $url,
            'type'        => 'article',
            'robots'      => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'faqs'        => $faqs,
            'schema'      => self::getGuideSchema($guide, $url, $title, $desc, $image, $faqs),
        ];
    }

    /**
     * Get Complete SEO, Open Graph, Twitter & AEO Metadata for Aftabnagar Buyer Hub & Price Index.
     */
    public static function getAftabnagarBuyerGuideMeta(): array
    {
        $siteName = self::getSiteName();
        $title = "Aftabnagar Property Buyer Guide & Price Index 2026 | আফতাবনগর প্লট ও ফ্ল্যাট বাজারদর ২০২৬ — {$siteName}";
        $desc = "আফতাবনগরের প্রতিটি ব্লকের (A-M) বর্তমান জমির কাঠাপ্রতি বাজারদর, ৩ কাঠা প্লটের বাজেট, রেডি ফ্ল্যাটের স্কয়ারফুট রেট, ল্যান্ড শেয়ার ও আইনি দলিল যাচাই নির্দেশিকা ২০২৬।";
        $keywords = "aftabnagar plot price per katha, aftabnagar flat price, aftabnagar land for sale, aftabnagar 3 katha plot price, aftabnagar property price 2026, ready flat in aftabnagar, eastern housing aftabnagar, aftabnagar block A plot, aftabnagar block B, aftabnagar block C flat, aftabnagar block D, aftabnagar block E, aftabnagar block F, aftabnagar block G H I J K L M, aftabnagar land share, আফতাবনগর জমির দাম, আফতাবনগর প্লট বিক্রয়, আফতাবনগর রেডি ফ্ল্যাট, আফতাবনগর ল্যান্ড শেয়ার, আফতাবনগর প্রপার্টি গাইড, PropertyKoi";
        $url = route('guides.aftabnagar');
        $image = self::getCustomOrAssetImage('adm_seo_land_og_image', 'og-land.jpg');

        $faqs = [
            [
                'question' => 'আফতাবনগরে আবাসিক প্লটের বর্তমান কাঠাপ্রতি বাজারদর কেমন (২০২৬)?',
                'answer'   => '২০২৬ সালের হালনাগাদ বাজারদর অনুযায়ী আফতাবনগরের ব্লক A থেকে C (প্রাইম জোন) পর্যন্ত প্রতি কাঠা জমির দাম ১.১০ কোটি থেকে ১.৬০ কোটি টাকা। ব্লক D থেকে F পর্যন্ত ৮০ লাখ থেকে ১.২৫ কোটি টাকা এবং তুলনামূলক নতুন সম্প্রসারিত ব্লক G থেকে M ও এক্সটেনশনে প্রতি কাঠা ৪৫ লাখ থেকে ৯৫ লাখ টাকা পর্যন্ত রয়েছে। রাস্তার প্রশস্ততা (২০ থেকে ৬০ ফুট), লেকভিউ এবং কর্নার পজিশনের ওপর ভিত্তি করে দাম পরিবর্তিত হয়।',
            ],
            [
                'question' => 'আফতাবনগরে ৩ কাঠা ও ৫ কাঠা প্লটের গড় বাজেট কত হতে পারে?',
                'answer'   => 'আফতাবনগরে ৩ কাঠার একটি স্ট্যান্ডার্ড আবাসিক প্লটের জন্য ব্লকভেদে মোট বাজেট ১.৫০ কোটি থেকে ৪.৫০ কোটি টাকা পর্যন্ত হতে পারে। ৫ কাঠার প্লটের ক্ষেত্রে বাজেট ২.৫০ কোটি থেকে ৭.৫০ কোটি টাকার মধ্যে ওঠানামা করে।',
            ],
            [
                'question' => 'বসবাসের জন্য আফতাবনগরের কোন কোন ব্লক সবচেয়ে জনপ্রিয় ও সেরা?',
                'answer'   => 'তাৎক্ষণিক বসবাস, পরিবার ও শিশুদের সুবিধার জন্য ব্লক A, B, C ও D সবচেয়ে জনপ্রিয়। এই ব্লকগুলো রামপুরা ব্রিজ, হাতিরঝিল প্রবেশদ্বার, ইস্ট ওয়েস্ট বিশ্ববিদ্যালয় এবং সুপরিচিত স্কুল ও বাজারের খুব কাছে অবস্থিত। নিরিবিলি পরিবেশের জন্য ব্লক E ও F এবং দীর্ঘমেয়াদী বিনিয়োগের জন্য ব্লক G থেকে M অত্যন্ত আকর্ষণীয়।',
            ],
            [
                'question' => 'আফতাবনগরে রেডি ফ্ল্যাট বনাম নির্মাণাধীন ফ্ল্যাটের স্কয়ারফুট রেট কেমন?',
                'answer'   => 'আফতাবনগরে ৩ বেডরুমের রেডি ফ্ল্যাটের বর্তমান মূল্য প্রতি স্কয়ারফুট ৬,২০০ থেকে ৯,৫০০ টাকা (ব্লকভেদে মোট ৯০ লাখ থেকে ১.৮ কোটি টাকা)। অন্যদিকে চলমান বা আন্ডার-কনস্ট্রাকশন ফ্ল্যাট প্রতি স্কয়ারফুট ৫,২০০ থেকে ৬,৮০০ টাকার মধ্যে কিস্তি সুবিধাসহ পাওয়া যায়।',
            ],
            [
                'question' => 'আফতাবনগরে ল্যান্ড শেয়ার (Land Share) ফ্ল্যাট কেনার সুবিধা ও খরচ কেমন?',
                'answer'   => 'ল্যান্ড শেয়ার পদ্ধতিতে ক্রেতারা যৌথভাবে জমি কিনে নির্মাণ খরচে ফ্ল্যাট তৈরি করেন। এতে প্রচলিত ডেভেলপার রেটের চেয়ে ৩০% থেকে ৩৫% পর্যন্ত সাশ্রয় হয়। আফতাবনগরে ল্যান্ড শেয়ার ফ্ল্যাটে প্রতি স্কয়ারফুট খরচ সাধারণত ৩,৮০০ থেকে ৪,৮০০ টাকার মধ্যে সম্পন্ন করা সম্ভব।',
            ],
            [
                'question' => 'ইস্টার্ন হাউজিং লিমিটেড (EHL) আফতাবনগর প্লট কেনার সময় কী কী দলিল চেক করতে হয়?',
                'answer'   => 'ইস্টার্ন হাউজিংয়ের প্লট কেনার পূর্বে মূল বরাদ্দপত্র (Allotment Letter), দখল হস্তান্তরপত্র (Possession Letter), ইএইচএল ট্রান্সফার পারমিশন বা নো-অবজেকশন সার্টিফিকেট (NOC), মূল রেজিস্ট্রি সাব-কবলা দলিল, বায়া দলিল এবং হালনাগাদ নামজারি ও খাজনা রশিদ নিখুঁতভাবে যাচাই করতে হবে।',
            ],
            [
                'question' => 'আফতাবনগরে নামজারি (Mutation) ও ডিসিআর (DCR) যাচাই করার উপায় কী?',
                'answer'   => 'সংশ্লিষ্ট সহকারী কমিশনার (ভূমি) অফিস বা ভূমি মন্ত্রণালয়ের ই-নামজারি পোর্টাল (mutation.land.gov.bd) থেকে খতিয়ান নম্বর ও প্রস্তাবিত খতিয়ান এন্ট্রি অনলাইনে যাচাই করা যায়। সাথে ডিসিআর (DCR) ও হালনাগাদ ভূমি উন্নয়ন কর রশিদ মিলিয়ে নিশ্চিত হতে হয়।',
            ],
            [
                'question' => 'রাজউক (RAJUK) ড্যাপ (DAP) এবং ভবন নির্মাণের অনুমোদন আফতাবনগরে কীভাবে কাজ করে?',
                'answer'   => 'আফতাবনগর রাজউকের ডিটেইল্ড এরিয়া প্ল্যান (DAP) আওতাভুক্ত। বাড়ি নির্মাণের জন্য রাজউকের ভূমি ব্যবহার ছাড়পত্র (Land Use Clearance) এবং ইমারত নির্মাণ বিধিমালা অনুযায়ী নকশা অনুমোদন (Building Plan Approval) গ্রহণ করতে হয়। রাস্তার প্রশস্ততার ওপর ভবনের উচ্চতা ও এফএআর (FAR) নির্ধারিত হয়।',
            ],
            [
                'question' => 'রামপুরা ব্রিজ, হাতিরঝিল ও গুলশানের সাথে আফতাবনগরের যাতায়াত ব্যবস্থা কেমন?',
                'answer'   => 'আফতাবনগরের প্রধান প্রবেশদ্বার রামপুরা ব্রিজ ও হাতিরঝিলের সাথে সরাসরি সংযুক্ত। হাতিরঝিল এক্সপ্রেসওয়ে ব্যবহারের মাধ্যমে গুলশান, বনানী, কারওয়ান বাজার ও ধানমন্ডিতে খুব অল্প সময়ে ট্রাফিক জ্যাম এড়িয়ে যাতায়াত করা যায়। এছাড়াও বনশ্রী ও মেরাদিয়া দিয়ে খিলগাঁওয়ের সাথে সহজ বিকল্প সড়ক যোগাযোগ রয়েছে।',
            ],
            [
                'question' => 'আফতাবনগরে স্কুল, বিশ্ববিদ্যালয় ও নাগরিক সুযোগ-সুবিধা কেমন?',
                'answer'   => 'আফতাবনগরে রয়েছে ইস্ট ওয়েস্ট ইউনিভার্সিটির মতো খ্যাতনামা বিদ্যাপীঠ, ঢাকা ইমপেরিয়াল কলেজ, একাধিক স্বনামধন্য ইংলিশ মিডিয়াম ও বাংলা মাধ্যম স্কুল, মসজিদ, মাদ্রাসা, সুপারশপ এবং মনোরম লেকওয়াক পাড়।',
            ],
            [
                'question' => 'PropertyKoi-তে আফতাবনগরের যাচাইকৃত প্রপার্টি কীভাবে খুঁজে পাবো?',
                'answer'   => 'PropertyKoi.com-এর ল্যান্ড সার্চ বা ফ্ল্যাট সার্চ ফিল্টারে এলাকা হিসেবে "Aftabnagar" এবং আপনার পছন্দের নির্দিষ্ট "Block" (যেমন Block A, B, C) নির্বাচন করুন। আপনি সরাসরি যাচাইকৃত মূল্য, রাস্তার মাপ এবং বিস্তারিত স্পেসিফিকেশনসহ লাইভ লিস্টিং দেখতে পারবেন।',
            ],
            [
                'question' => 'পছন্দের প্লট বা ফ্ল্যাট খুঁজে না পেলে কীভাবে রিকোয়ারমেন্ট সাবমিট করবো?',
                'answer'   => 'PropertyKoi-এর "চাহিদা জানান (Chahida)" বাটনে গিয়ে আপনার কাঙ্ক্ষিত এলাকা "আফতাবনগর", পছন্দের ব্লক, আয়তন ও বাজেট সিলেক্ট করে সাবমিট করুন। উপযুক্ত কোনো প্রপার্টি তালিকাভুক্ত হওয়ামাত্র সিস্টেম আপনাকে স্বয়ংক্রিয়ভাবে অ্যালার্ট পাঠাবে।',
            ],
        ];

        $siteUrl = url('/');
        $schema = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'         => 'WebSite',
                    '@id'           => $siteUrl . '#website',
                    'url'           => $siteUrl,
                    'name'          => $siteName,
                    'alternateName' => ['PropertyKoi', 'PropertyKoi.com', 'প্রপার্টি কই'],
                ],
                [
                    '@type'       => 'Organization',
                    '@id'         => $siteUrl . '#organization',
                    'name'        => $siteName,
                    'url'         => $siteUrl,
                    'logo'        => [
                        '@type' => 'ImageObject',
                        'url'   => self::getCustomOrAssetImage('adm_seo_default_og_image', 'logo.png'),
                    ],
                ],
                [
                    '@type'            => 'WebPage',
                    '@id'              => $url . '#webpage',
                    'url'              => $url,
                    'name'             => $title,
                    'description'      => $desc,
                    'isPartOf'         => ['@id' => $siteUrl . '#website'],
                    'inLanguage'       => 'bn-BD',
                    'breadcrumb'       => ['@id' => $url . '#breadcrumb'],
                    'about'            => [
                        '@type' => 'Place',
                        'name'  => 'Aftabnagar, Dhaka',
                        'address' => [
                            '@type'           => 'PostalAddress',
                            'addressLocality' => 'Dhaka',
                            'addressRegion'   => 'Dhaka Division',
                            'addressCountry'  => 'BD',
                        ],
                    ],
                ],
                [
                    '@type'           => 'Dataset',
                    '@id'             => $url . '#dataset',
                    'name'            => 'Aftabnagar Real Estate Price Index 2026',
                    'description'     => 'Comprehensive block-by-block property price data (per katha rate, flat square foot rate, road widths) for Aftabnagar, Dhaka in 2026.',
                    'url'             => $url,
                    'temporalCoverage'=> '2026',
                    'spatialCoverage' => 'Aftabnagar, Dhaka, Bangladesh',
                    'creator'         => ['@id' => $siteUrl . '#organization'],
                ],
                [
                    '@type'           => 'BreadcrumbList',
                    '@id'             => $url . '#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type'    => 'ListItem',
                            'position' => 1,
                            'name'     => 'হোম',
                            'item'     => $siteUrl,
                        ],
                        [
                            '@type'    => 'ListItem',
                            'position' => 2,
                            'name'     => 'প্রপার্টি গাইডস',
                            'item'     => route('guides.index'),
                        ],
                        [
                            '@type'    => 'ListItem',
                            'position' => 3,
                            'name'     => 'আফতাবনগর বায়ার গাইড ও প্রাইস ইনডেক্স ২০২৬',
                            'item'     => $url,
                        ],
                    ],
                ],
                [
                    '@type'      => 'FAQPage',
                    '@id'        => $url . '#faq',
                    'mainEntity' => array_map(function ($f) {
                        return [
                            '@type'          => 'Question',
                            'name'           => $f['question'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => $f['answer'],
                            ],
                        ];
                    }, $faqs),
                ],
            ],
        ];

        return [
            'title'       => $title,
            'description' => $desc,
            'keywords'    => $keywords,
            'image'       => $image,
            'images'      => [$image],
            'image_alt'   => 'Aftabnagar Property Buyer Guide and Price Index 2026',
            'url'         => $url,
            'type'        => 'website',
            'robots'      => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'faqs'        => $faqs,
            'schema'      => $schema,
        ];
    }

    /**
     * Build dynamic SEO meta for property search pages, with hyper-focused Aftabnagar buyer targeting.
     */
    public static function getSearchOgMeta(string $forcedType, array $filters = []): array
    {
        $siteName = self::getSiteName();
        $area = trim((string) ($filters['area'] ?? ''));
        $block = trim((string) ($filters['block'] ?? ''));
        $isAftab = (stripos($area, 'aftab') !== false || strpos($area, 'আফতাব') !== false);

        $pageSlug = match ($forcedType) {
            'land' => 'land-search',
            'flat' => 'flat-search',
            'commercial' => 'commercial-search',
            'land_share' => 'land-share-search',
            default => 'all-search',
        };

        if (!$isAftab && empty($block)) {
            return self::getOgMeta($pageSlug);
        }

        $blockText = !empty($block) ? "ব্লক {$block} (Block {$block})" : "ব্লক A-M";
        $blockQuery = !empty($block) ? " Block {$block}" : "";

        if ($forcedType === 'land') {
            $title = "Aftabnagar{$blockQuery} Plots for Sale | আফতাবনগর {$blockText} জমি ও প্লট বিক্রয় ২০২৬ — {$siteName}";
            $desc = "আফতাবনগর {$blockText}-এ সরাসরি যাচাইকৃত আবাসিক ও বাণিজ্যিক প্লট। ২০ থেকে ৬০ ফুট রাস্তা, কর্নার ও লেকভিউ প্লটের কাঠাপ্রতি বাজারদর ও লাইভ লিস্টিং দেখুন।";
            $keywords = "aftabnagar plot for sale, aftabnagar block {$block} plot, buy land in aftabnagar, aftabnagar plot price per katha, আফতাবনগর জমি বিক্রয়, আফতাবনগর প্লট, PropertyKoi";
            $image = self::getCustomOrAssetImage('adm_seo_land_og_image', 'og-land.jpg');
            $url = route('properties.land', array_filter(['area' => 'Aftabnagar', 'block' => $block]));
        } elseif ($forcedType === 'flat') {
            $title = "Flats for Sale in Aftabnagar{$blockQuery} | আফতাবনগর {$blockText} ফ্ল্যাট বিক্রয় — {$siteName}";
            $desc = "আফতাবনগর {$blockText}-এ ৩ ও ৪ বেডরুমের রেডি এবং নির্মাণাধীন আধুনিক ফ্ল্যাট ও অ্যাপার্টমেন্ট। সঠিক স্কয়ারফুট রেট ও ভেরিফাইড কাগজপত্রে কিনুন PropertyKoi-তে।";
            $keywords = "flat for sale in aftabnagar, ready flat aftabnagar, apartment aftabnagar block {$block}, 3 bed flat aftabnagar, আফতাবনগর ফ্ল্যাট বিক্রয়, PropertyKoi";
            $image = self::getCustomOrAssetImage('adm_seo_flat_og_image', 'og-flat.jpg');
            $url = route('properties.flat', array_filter(['area' => 'Aftabnagar', 'block' => $block]));
        } elseif ($forcedType === 'land_share') {
            $title = "Land Share Projects in Aftabnagar{$blockQuery} | আফতাবনগর ল্যান্ড শেয়ার — {$siteName}";
            $desc = "আফতাবনগর {$blockText}-এ সাশ্রয়ী খরচে আধুনিক ফ্ল্যাট নির্মাণের ভেরিফাইড যৌথ ল্যান্ড শেয়ার প্রকল্প। বাজারদরের চেয়ে ৩০-৩৫% কম খরচে নিজস্ব ফ্ল্যাট।";
            $keywords = "land share aftabnagar, land share flat project aftabnagar block {$block}, affordable flat dhaka, আফতাবনগর ল্যান্ড শেয়ার, PropertyKoi";
            $image = self::getCustomOrAssetImage('adm_seo_commercial_og_image', 'og-commercial.jpg');
            $url = route('properties.share', array_filter(['area' => 'Aftabnagar', 'block' => $block]));
        } else {
            $title = "Properties for Sale in Aftabnagar{$blockQuery} | আফতাবনগর {$blockText} সকল প্রপার্টি — {$siteName}";
            $desc = "আফতাবনগর {$blockText}-এর সকল যাচাইকৃত আবাসিক প্লট, রেডি ফ্ল্যাট, দোকান ও ল্যান্ড শেয়ার প্রপার্টি এক জায়গায় খুঁজুন ও তুলনা করুন।";
            $keywords = "aftabnagar properties for sale, aftabnagar plot flat shop, aftabnagar real estate, আফতাবনগর প্রপার্টি, PropertyKoi";
            $image = self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg');
            $url = route('properties.index', array_filter(['area' => 'Aftabnagar', 'block' => $block]));
        }

        $faqs = self::getHubFaqs($pageSlug);
        $schema = self::getWebsiteSchema($title, $desc, $url, $faqs);

        return [
            'title'       => $title,
            'description' => $desc,
            'keywords'    => $keywords,
            'image'       => $image,
            'images'      => [$image],
            'image_alt'   => $title,
            'url'         => $url,
            'type'        => 'website',
            'robots'      => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'faqs'        => $faqs,
            'schema'      => $schema,
        ];
    }

    /**
     * Build comprehensive Schema.org @graph for a single Guide (Article + BreadcrumbList + FAQPage).
     */
    public static function getGuideSchema(
        Guide $guide,
        string $url,
        string $title,
        string $desc,
        string $image,
        array $faqs = []
    ): array {
        $siteName = self::getSiteName();
        $siteUrl = url('/');
        $authorName = $guide->author_name ?: $siteName . ' Research Team';

        $graph = [
            [
                '@type'         => 'WebSite',
                '@id'           => $siteUrl . '#website',
                'url'           => $siteUrl,
                'name'          => $siteName,
                'alternateName' => ['PropertyKoi', 'PropertyKoi.com', 'প্রপার্টি কই'],
            ],
            [
                '@type'       => 'Organization',
                '@id'         => $siteUrl . '#organization',
                'name'        => $siteName,
                'url'         => $siteUrl,
                'logo'        => [
                    '@type' => 'ImageObject',
                    'url'   => self::getCustomOrAssetImage('adm_seo_default_og_image', 'logo.png'),
                ],
            ],
            [
                '@type'            => 'Article',
                '@id'              => $url . '#article',
                'isPartOf'         => ['@id' => $siteUrl . '#website'],
                'headline'         => $title,
                'description'      => $desc,
                'mainEntityOfPage' => $url,
                'image'            => [
                    '@type' => 'ImageObject',
                    'url'   => $image,
                ],
                'datePublished'    => $guide->created_at ? $guide->created_at->toIso8601String() : now()->toIso8601String(),
                'dateModified'     => $guide->updated_at ? $guide->updated_at->toIso8601String() : now()->toIso8601String(),
                'author'           => [
                    '@type' => 'Person',
                    'name'  => $authorName,
                ],
                'publisher'        => ['@id' => $siteUrl . '#organization'],
                'inLanguage'       => 'bn-BD',
                'articleSection'   => $guide->category_label,
                'wordCount'        => str_word_count(strip_tags($guide->content)),
                'speakable'        => [
                    '@type'       => 'SpeakableSpecification',
                    'cssSelector' => ['h1', '.guide-lead', '.guide-content-body'],
                ],
            ],
            [
                '@type'           => 'BreadcrumbList',
                '@id'             => $url . '#breadcrumb',
                'itemListElement' => [
                    [
                        '@type'    => 'ListItem',
                        'position' => 1,
                        'name'     => 'হোম',
                        'item'     => $siteUrl,
                    ],
                    [
                        '@type'    => 'ListItem',
                        'position' => 2,
                        'name'     => 'প্রপার্টি গাইডস',
                        'item'     => route('guides.index'),
                    ],
                    [
                        '@type'    => 'ListItem',
                        'position' => 3,
                        'name'     => $guide->title,
                        'item'     => $url,
                    ],
                ],
            ],
        ];

        if (!empty($faqs)) {
            $faqEntities = [];
            foreach ($faqs as $f) {
                if (!empty($f['question']) && !empty($f['answer'])) {
                    $faqEntities[] = [
                        '@type'          => 'Question',
                        'name'           => $f['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => $f['answer'],
                        ],
                    ];
                }
            }
            if (!empty($faqEntities)) {
                $graph[] = [
                    '@type'      => 'FAQPage',
                    '@id'        => $url . '#faq',
                    'mainEntity' => $faqEntities,
                ];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];
    }

    public static function pingIndexNow(Property $property): array
    {
        try {
            $enabled = Setting::isTrue('seo_indexnow_enabled', false);
            if (!$enabled) {
                return ['success' => false, 'message' => 'IndexNow disabled'];
            }

            $apiKey = self::getIndexNowKey();
            if (empty($apiKey)) {
                return ['success' => false, 'message' => 'IndexNow key missing'];
            }

            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $propertyUrl = route('property.show', $property->slug ?: $property->id);

            $response = Http::timeout(4)->post('https://api.indexnow.org/indexnow', [
                'host'        => $host,
                'key'         => $apiKey,
                'keyLocation' => url('/' . $apiKey . '.txt'),
                'urlList'     => [$propertyUrl],
            ]);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::warning('IndexNow ping error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function getIndexNowKey(): string
    {
        return Setting::get('adm_seo_indexnow_key', Setting::get('seo_indexnow_key', 'b8f2a91c3d4e5f6a7b8c9d0e1f2a3b4c'));
    }

    public static function pingAllIndexNow(): array
    {
        try {
            $apiKey = self::getIndexNowKey();
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

            $urls = [
                url('/'),
                route('guides.aftabnagar'),
                route('properties.land'),
                route('properties.flat'),
                route('properties.commercial'),
                route('properties.share'),
                route('map.view'),
                route('property.create'),
                route('chahida.index'),
            ];

            $liveProps = Property::where('status', 'live')->get();
            if ($liveProps->isEmpty()) {
                $liveProps = Property::all();
            }

            foreach ($liveProps as $p) {
                $urls[] = route('property.show', $p->slug ?: $p->id);
            }

            $urls = array_values(array_unique($urls));

            $response = Http::timeout(10)->post('https://api.indexnow.org/indexnow', [
                'host'        => $host,
                'key'         => $apiKey,
                'keyLocation' => url('/' . $apiKey . '.txt'),
                'urlList'     => $urls,
            ]);

            return [
                'success' => $response->successful() || $response->status() === 200 || $response->status() === 202,
                'count'   => count($urls),
                'status'  => $response->status(),
                'message' => 'সফলভাবে ' . count($urls) . 'টি পেজ IndexNow সার্চ ইঞ্জিনগুলোতে তাৎক্ষণিক ইনডেক্সিংয়ের জন্য পাঠানো হয়েছে!',
            ];
        } catch (\Throwable $e) {
            Log::warning('IndexNow ping all error: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'IndexNow পিং পাঠানো সম্ভব হয়নি: ' . $e->getMessage(),
            ];
        }
    }
}
