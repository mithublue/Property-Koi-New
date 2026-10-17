<?php

namespace App\Services;

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

        $desc = trim("{$area} {$block}-এ যাচাইকৃত {$typeBn}{$specsText}{$priceText}। ১০০% ভেরিফাইড কাগজপত্র, কোনো চেইন ব্রোকার ছাড়া সরাসরি মালিক বা অথরাইজড সোর্সের সাথে কথা বলুন PropertyKoi-তে।");
        return Str::limit($desc, 165, '');
    }

    /**
     * Build dynamic Meta Keywords for a property or page.
     */
    public static function buildPropertyKeywords(Property $p): string
    {
        $area = $p->area ?: 'Aftabnagar';
        $block = $p->block ?: 'A';
        $typeBn = $p->type_label;

        $keywords = [
            $p->title,
            "{$area} {$typeBn} বিক্রয়",
            "আফতাবনগর {$typeBn}",
            "আফতাবনগর ব্লক {$block} {$typeBn}",
            "Aftabnagar Block {$block} {$p->type} for sale",
            "{$area} property for sale",
            "direct owner property {$area}",
            "ভেরিফাইড প্রপার্টি ঢাকা",
            "PropertyKoi",
            "প্রপার্টি কই",
        ];

        if ($p->type === 'land' && $p->land?->katha_size) {
            $keywords[] = "{$p->land->katha_size} কাঠা জমি আফতাবনগর";
            $keywords[] = "Aftabnagar {$p->land->katha_size} katha plot";
        } elseif ($p->type === 'flat' && $p->flat?->flat_size_sqft) {
            $keywords[] = "{$p->flat->flat_size_sqft} sqft flat in {$area}";
            $keywords[] = "রেডি ফ্ল্যাট আফতাবনগর";
        } elseif ($p->type === 'commercial') {
            $keywords[] = "আফতাবনগর দোকান ও কমার্শিয়াল স্পেস";
            $keywords[] = "commercial shop for sale in {$area}";
        } elseif ($p->type === 'land_share') {
            $keywords[] = "ল্যান্ড শেয়ার প্রজেক্ট আফতাবনগর";
            $keywords[] = "land share flat in {$area}";
        }

        return implode(', ', array_unique(array_filter($keywords)));
    }

    /**
     * Build dynamic AEO (Answer Engine Optimization) FAQ items for a single property.
     */
    public static function getPropertyFaqs(Property $p): array
    {
        $area = $p->area ?: 'আফতাবনগর';
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
                'question' => "কোনো ব্রোকার ছাড়া সরাসরি প্রপার্টি ভিজিট বা মালিকের সাথে যোগাযোগের উপায় কী?",
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
                    'question' => 'আফতাবনগরে আবাসিক ও কমার্শিয়াল প্লটের বর্তমান দর কেমন?',
                    'answer'   => 'আফতাবনগরের ব্লক A থেকে N পর্যন্ত লোকেশন, রাস্তার প্রশস্ততা (২০ ফুট থেকে ৬০+ ফুট), কর্নার প্লট এবং লেকভিউ অবস্থানের ওপর ভিত্তি করে জমির মূল্য নির্ধারিত হয়। PropertyKoi-তে সরাসরি মালিকের প্রদত্ত সর্বশেষ ভেরিফাইড মূল্য তালিকা ফিল্টার করে দেখতে পারেন।',
                ],
                [
                    'question' => 'জমি কেনার আগে কোন কোন কাগজপত্র যাচাই করা জরুরি?',
                    'answer'   => 'জমি ক্রয়ের পূর্বে মূল দলিল, বায়া দলিল, সিএস/এসএ/আরএস/মহানগর জরিপ খতিয়ান, ইস্টার্ন হাউজিং বরাদ্দপত্র ও ছাড়পত্র, হালনাগাদ নামজারি (Mutation), ডিসিআর এবং সর্বশেষ খাজনা রশিদ যাচাই করা আবশ্যক।',
                ],
                [
                    'question' => 'PropertyKoi-তে জমি কিনলে কি কোনো চেইন ব্রোকার কমিশন দিতে হয়?',
                    'answer'   => 'না। আমাদের প্ল্যাটফর্মে কোনো চেইন ব্রোকার বা দালাল চক্রের স্থান নেই। আপনি সরাসরি যাচাইকৃত মালিক বা অথরাইজড সোর্সের সাথে স্বচ্ছভাবে সাইট ভিজিট ও ডিল সম্পন্ন করতে পারবেন।',
                ],
            ],
            'flat-search' => [
                [
                    'question' => 'আফতাবনগরে রেডি ফ্ল্যাট এবং নির্মাণাধীন ফ্ল্যাটের মধ্যে কোনটি সুবিধাজনক?',
                    'answer'   => 'তাৎক্ষণিক বসবাস বা ভাড়ার আয়ের জন্য ১০০% রেডি ফ্ল্যাট সেরা। অন্যদিকে কিস্তি সুবিধা ও তুলনামূলক কম মূল্যে বিনিয়োগের জন্য চলমান (Under Construction) ফ্ল্যাট প্রজেক্টগুলো বেশ সাশ্রয়ী।',
                ],
                [
                    'question' => 'ফ্ল্যাট কেনার সময় ইউটিলিটি ও কার পার্কিং চার্জ কি মূল্যের অন্তর্ভুক্ত থাকে?',
                    'answer'   => 'প্রতিটি ফ্ল্যাট লিস্টিংয়ের স্পেসিফিকেশন টেবিলে মূল্যের পাশাপাশি গ্যারেজ/পার্কিং মূল্য, ইউটিলিটি চার্জ এবং ডাউন পেমেন্টের তথ্য স্পষ্টভাবে উল্লেখ থাকে।',
                ],
            ],
            'commercial-search' => [
                [
                    'question' => 'আফতাবনগরে দোকান বা অফিস স্পেসে বিনিয়োগের সুবিধা কী?',
                    'answer'   => 'দ্রুত বর্ধনশীল আবাসিক এলাকা হওয়ায় আফতাবনগর মেইন রোড ও এভিনিউ রোডগুলোর মার্কেট, শোরুম এবং অফিস স্পেসে উচ্চ রেন্টাল ইল্ড (ভাড়া আয়) এবং দ্রুত ক্যাপিটাল অ্যাপ্রিসিয়েশন পাওয়া যায়।',
                ],
            ],
            'land-share-search' => [
                [
                    'question' => 'ল্যান্ড শেয়ার প্রজেক্টে ফ্ল্যাট নির্মাণের খরচ সাধারণ ফ্ল্যাটের চেয়ে কতটা কম?',
                    'answer'   => 'যৌথভাবে জমি ক্রয় করে নির্মাণ খরচ কিস্তিতে পরিশোধ করার ফলে বাজারমূল্যের চেয়ে প্রায় ২৫% থেকে ৩৫% কম খরচে নিজের পছন্দের ফ্ল্যাটের মালিক হওয়া সম্ভব।',
                ],
            ],
            default => [
                [
                    'question' => 'PropertyKoi.com কীভাবে ক্রেতা ও বিক্রেতাদের সহায়তা করে?',
                    'answer'   => 'PropertyKoi হলো ঢাকা ও আফতাবনগরের একটি ডিরেক্ট রিয়েল এস্টেট মার্কেটপ্লেস, যেখানে ৪-পয়েন্ট ভেরিফিকেশনের মাধ্যমে যাচাইকৃত জমি, ফ্ল্যাট, কমার্শিয়াল স্পেস ও ল্যান্ড শেয়ার সরাসরি মালিক বা সোর্সের মাধ্যমে কেনা-বেচা করা যায়।',
                ],
                [
                    'question' => 'পছন্দের প্রপার্টি না পেলে অটোমেটিক নোটিফিকেশন পাওয়ার উপায় কী?',
                    'answer'   => 'আমাদের "চাহিদা জানান (Chahida)" পেজে আপনার বাজেট, পছন্দের ব্লক ও প্রপার্টির ধরন সাবমিট করে রাখলে নতুন প্রপার্টি যুক্ত হওয়া মাত্রই টেলিগ্রাম, ইমেইল ও পুশ নোটিফিকেশনে তাৎক্ষণিক অ্যালার্ট পাবেন।',
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
        $siteDesc = 'আফতাবনগর, পূর্বাচল ও ঢাকায় সরাসরি মালিক ও ভেরিফাইড জমি, ফ্ল্যাট, কমার্শিয়াল স্পেস এবং ল্যান্ড শেয়ার মার্কেটপ্লেস। কোনো চেইন ব্রোকার ছাড়া ১০০% যাচাইকৃত প্রপার্টি কিনুন বা বিক্রি করুন।';
        $defaultKeywords = 'আফতাবনগর জমি বিক্রয়, আফতাবনগর ফ্ল্যাট, Aftabnagar land for sale, Aftabnagar flat price, রেডি ফ্ল্যাট ঢাকা, কমার্শিয়াল দোকান আফতাবনগর, ল্যান্ড শেয়ার প্রজেক্ট, PropertyKoi, প্রপার্টি কই, বসুন্ধরা জমি, পূর্বাচল প্লট';

        if ($property) {
            $typeLabel = $property->type_label;
            $loc = ($property->area ?: 'আফতাবনগর') . ($property->block ? ' • ব্লক ' . $property->block : '');
            $title = "{$property->title} | {$typeLabel} - {$loc} | {$siteName}";
            $desc = self::buildPropertyDescription($property);
            $keywords = self::buildPropertyKeywords($property);
            $canonicalUrl = route('property.show', $property->slug ?: $property->id);

            $defaultTypeAsset = match ($property->type) {
                'flat' => 'og-flat.jpg',
                'commercial', 'land_share' => 'og-commercial.jpg',
                default => 'og-land.jpg',
            };

            $images = [];
            if ($property->relationLoaded('media') || $property->media()->exists()) {
                foreach ($property->media as $m) {
                    if ($m->media_type === 'photo' && !empty($m->file_path)) {
                        $fmt = self::formatOgImageUrl($m->file_path, $defaultTypeAsset);
                        if ($m->is_cover) {
                            array_unshift($images, $fmt);
                        } else {
                            $images[] = $fmt;
                        }
                    }
                }
            }
            if (empty($images)) {
                $images[] = self::formatOgImageUrl($property->cover_url, $defaultTypeAsset);
            }
            $images = array_values(array_unique($images));
            $primaryImage = $images[0];
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
                'title'       => "আফতাবনগর ও ঢাকায় জমি ও প্লট খুঁজুন | Land & Plots for Sale - {$siteName}",
                'description' => 'আফতাবনগর, পূর্বাচল ও ঢাকার সেরা লোকেশনে আবাসিক ও বাণিজ্যিক প্লট। ২০ থেকে ৬০ ফুট রাস্তা, লেকভিউ ও কর্নার প্লট সরাসরি মালিকের কাছ থেকে কিনুন।',
                'keywords'    => 'আফতাবনগর জমি বিক্রয়, আফতাবনগর প্লট দাম, কর্নার প্লট আফতাবনগর, লেকভিউ প্লট, Aftabnagar land for sale, residential plot Dhaka, Purbachal plot, Bashundhara plot, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_land_og_image', 'og-land.jpg'),
                'url'         => route('properties.land'),
                'type'        => 'website',
            ],
            'flat-search' => [
                'title'       => "রেডি ও নির্মাণাধীন ফ্ল্যাট ও অ্যাপার্টমেন্ট | Flats & Apartments for Sale - {$siteName}",
                'description' => 'আফতাবনগর ও ঢাকায় ১০০% রেডি এবং নির্মাণাধীন আধুনিক ফ্ল্যাট ও ডুপ্লেক্স। লিফট, জেনারেটর, কার পার্কিং সুবিধাসহ সরাসরি ভেরিফাইড লিস্টিং।',
                'keywords'    => 'আফতাবনগর রেডি ফ্ল্যাট, ফ্ল্যাট বিক্রয় ঢাকা, ৩ বেডরুম ফ্ল্যাট আফতাবনগর, ready flat in Aftabnagar, apartment for sale Dhaka, PropertyKoi flat',
                'image'       => self::getCustomOrAssetImage('adm_seo_flat_og_image', 'og-flat.jpg'),
                'url'         => route('properties.flat'),
                'type'        => 'website',
            ],
            'commercial-search' => [
                'title'       => "দোকান ও বাণিজ্যিক স্পেস বিক্রয় | Commercial Shops & Offices - {$siteName}",
                'description' => 'আফতাবনগর ও ঢাকার প্রাইম মার্কেটে দোকান, শোরুম, অফিস স্পেস ও ওপেন ফ্লোর বিক্রয়। নিশ্চিত ভাড়া বা উচ্চ মুনাফাযুক্ত বাণিজ্যিক বিনিয়োগের সেরা সুযোগ।',
                'keywords'    => 'আফতাবনগর দোকান বিক্রয়, কমার্শিয়াল স্পেস ঢাকা, অফিস স্পেস বিক্রয় আফতাবনগর, commercial shop for sale Aftabnagar, showroom space Dhaka, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_commercial_og_image', 'og-commercial.jpg'),
                'url'         => route('properties.commercial'),
                'type'        => 'website',
            ],
            'land-share-search' => [
                'title'       => "যৌথ ল্যান্ড শেয়ার প্রকল্পসমূহ | Land Share Projects - {$siteName}",
                'description' => 'যৌথ অর্থায়নে সাশ্রয়ী মূল্যে নিজের ফ্ল্যাট গড়ার নির্ভরযোগ্য মাধ্যম। আফতাবনগরে ট্রাস্টেড ও ভেরিফাইড ল্যান্ড শেয়ার প্রজেক্ট সরাসরি যাচাই করুন।',
                'keywords'    => 'ল্যান্ড শেয়ার আফতাবনগর, যৌথ জমির শেয়ার ঢাকা, কম খরচে ফ্ল্যাট শেয়ার, Aftabnagar land share project, flat share Dhaka, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_commercial_og_image', 'og-commercial.jpg'),
                'url'         => route('properties.share'),
                'type'        => 'website',
            ],
            'all-search' => [
                'title'       => "সকল ভেরিফাইড প্রপার্টি লিস্টিং | জমি, ফ্ল্যাট ও কমার্শিয়াল - {$siteName}",
                'description' => 'আফতাবনগর, বসুন্ধরা, পূর্বাচল ও বনশ্রীর সকল যাচাইকৃত জমি, রেডি ফ্ল্যাট, দোকান এবং ল্যান্ড শেয়ার প্রপার্টি এক জায়গায় খুঁজুন ও তুলনা করুন।',
                'keywords'    => $defaultKeywords,
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'url'         => route('properties.index'),
                'type'        => 'website',
            ],
            'map-view' => [
                'title'       => "আফতাবনগর ইন্টারেক্টিভ ম্যাপ ভিউ | Map View - {$siteName}",
                'description' => 'ম্যাপে সরাসরি প্রতিটি ব্লক, রোড ও প্লটের সঠিক অবস্থান, রাস্তার প্রশস্ততা ও মূল্য যাচাই করুন।',
                'keywords'    => 'আফতাবনগর ম্যাপ, প্রপার্টি ম্যাপ ভিউ, Aftabnagar block map, plot location map Dhaka, PropertyKoi map',
                'image'       => self::getCustomOrAssetImage('adm_seo_land_og_image', 'og-land.jpg'),
                'url'         => route('map.view'),
                'type'        => 'website',
            ],
            'sell-property' => [
                'title'       => "আপনার প্রপার্টি বা দোকান সরাসরি বিক্রি করুন | Post Property Free - {$siteName}",
                'description' => 'কোনো মধ্যস্বত্বভোগী ছাড়া সরাসরি প্রকৃত ক্রেতাদের কাছে আপনার জমি, ফ্ল্যাট বা দোকান পৌঁছান। সম্পূর্ণ ফ্রি লিস্টিং।',
                'keywords'    => 'প্রপার্টি বিক্রি করুন, জমি বিক্রির বিজ্ঞাপন, ফ্ল্যাট বিক্রি আফতাবনগর, sell property Dhaka, post free property ad Bangladesh, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_sell_og_image', 'og-sell.jpg'),
                'url'         => route('property.create'),
                'type'        => 'website',
            ],
            'chahida' => [
                'title'       => "আপনার প্রপার্টি চাহিদা জানান | প্রপার্টি কই - {$siteName}",
                'description' => 'আপনার বাজেট ও পছন্দ অনুযায়ী কাঙ্ক্ষিত জমি বা ফ্ল্যাটের চাহিদা দিন। উপযুক্ত ডিল পেলেই তাৎক্ষণিক ও দৈনিক ক্রন শিডিউলে নোটিফিকেশন পাবেন।',
                'keywords'    => 'প্রপার্টি চাহিদা, জমি কিনতে চাই আফতাবনগর, ফ্ল্যাট রিকোয়ারমেন্ট ঢাকা, buyer property requirement Dhaka, PropertyKoi chahida',
                'image'       => self::getCustomOrAssetImage('adm_seo_chahida_og_image', 'og-chahida.jpg'),
                'url'         => route('chahida.index'),
                'type'        => 'website',
            ],
            'compare' => [
                'title'       => "প্রপার্টি তুলনা ম্যাট্রিক্স | Compare Properties - {$siteName}",
                'description' => 'একাধিক জমি, ফ্ল্যাট বা কমার্শিয়াল স্পেসের মূল্য, আকার, রাস্তা ও সুযোগ-সুবিধা পাশাপাশি তুলনা করে সেরা সিদ্ধান্ত নিন।',
                'keywords'    => 'প্রপার্টি তুলনা, জমি ও ফ্ল্যাট কম্পেয়ার, compare properties Dhaka, Aftabnagar plot comparison, PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_flat_og_image', 'og-flat.jpg'),
                'url'         => route('compare.index'),
                'type'        => 'website',
            ],
            'shortlist' => [
                'title'       => "আমার পছন্দের প্রপার্টি শর্টলিস্ট | Saved Properties - {$siteName}",
                'description' => 'আপনার পছন্দের সেইভ করা জমি, ফ্ল্যাট ও কমার্শিয়াল প্রপার্টিসমূহ এক নজরে দেখুন ও তুলনা করুন।',
                'keywords'    => 'শর্টলিস্ট প্রপার্টি, পছন্দের জমি ও ফ্ল্যাট, saved properties PropertyKoi',
                'image'       => self::getCustomOrAssetImage('adm_seo_chahida_og_image', 'og-chahida.jpg'),
                'url'         => route('shortlist'),
                'type'        => 'website',
            ],
            default => [
                'title'       => "{$siteName} | সরাসরি মালিক ও ভেরিফাইড প্রপার্টি মার্কেটপ্লেস — ঢাকা ও আফতাবনগর",
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
                '@type'           => 'WebSite',
                '@id'             => $siteUrl . '#website',
                'url'             => $siteUrl,
                'name'            => $siteName,
                'inLanguage'      => 'bn-BD',
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => route('properties.index') . '?search={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type'       => 'RealEstateAgent',
                '@id'         => $siteUrl . '#organization',
                'name'        => $siteName,
                'url'         => $siteUrl,
                'logo'        => asset('assets/icons/icon-512x512.png'),
                'image'       => self::getCustomOrAssetImage('adm_seo_default_og_image', 'og-default.jpg'),
                'description' => $desc ?: 'আফতাবনগর ও ঢাকার রিয়েল এস্টেট প্রপার্টি কেনা-বেচার বিশ্বস্ত ডিরেক্ট মার্কেটপ্লেস।',
                'telephone'   => Setting::get('adm_admin_whatsapp_number', Setting::get('support_phone', '+8801672646242')),
                'address'     => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => 'Aftabnagar, Badda',
                    'addressRegion'   => 'Dhaka',
                    'postalCode'      => '1212',
                    'addressCountry'  => 'BD',
                ],
                'areaServed'  => [
                    ['@type' => 'AdministrativeArea', 'name' => 'Aftabnagar, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Bashundhara R/A, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Purbachal, Dhaka'],
                    ['@type' => 'AdministrativeArea', 'name' => 'Banasree, Dhaka'],
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
            'streetAddress'   => 'Block ' . ($prop->block ?: 'A') . ($prop->road_number ? ', Road ' . $prop->road_number : '') . ($prop->road_width_ft ? ' (' . $prop->road_width_ft . 'ft Road)' : ''),
            'addressLocality' => $prop->area ?: 'Aftabnagar',
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
                        '@type'         => 'Offer',
                        'price'         => $prop->raw_price > 0 ? $prop->raw_price : null,
                        'priceCurrency' => 'BDT',
                        'availability'  => $prop->status === 'sold' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
                        'url'           => $url,
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
