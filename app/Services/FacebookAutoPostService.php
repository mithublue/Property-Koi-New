<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookAutoPostService
{
    const GRAPH_API_VERSION = 'v19.0';

    /**
     * Check if FB Post credentials are configured
     */
    public static function isConfigured(): bool
    {
        $pageId = trim(Setting::get('adm_fb_page_id', Setting::get('fb_page_id', '')));
        $token  = trim(Setting::get('adm_fb_page_access_token', Setting::get('fb_access_token', '')));

        return !empty($pageId) && !empty($token);
    }

    /**
     * Post a property to the configured Facebook Page
     */
    public static function postProperty(Property $property, ?string $customCaption = null, ?string $customImageUrl = null): array
    {
        if (!self::isConfigured()) {
            return [
                'success' => false,
                'error'   => 'Facebook Page ID অথবা Page Access Token কনফিগার করা নেই। অনুগ্রহ করে সেটিংস পেজে গিয়ে কনফিগার করুন।'
            ];
        }

        $pageId = trim(Setting::get('adm_fb_page_id', Setting::get('fb_page_id', '')));
        $token  = trim(Setting::get('adm_fb_page_access_token', Setting::get('fb_access_token', '')));

        $caption = !empty(trim($customCaption ?? '')) ? trim($customCaption) : self::buildCaption($property);
        $imageUrl = !empty($customImageUrl) ? $customImageUrl : self::getPropertyImageUrl($property);

        try {
            if (!empty($imageUrl)) {
                // Post Photo with caption
                $endpoint = "https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/{$pageId}/photos";
                $response = Http::timeout(25)->post($endpoint, [
                    'url'          => $imageUrl,
                    'caption'      => $caption,
                    'access_token' => $token,
                ]);
            } else {
                // Post Feed link with message
                $endpoint = "https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/{$pageId}/feed";
                $response = Http::timeout(25)->post($endpoint, [
                    'message'      => $caption,
                    'link'         => url('/property/' . $property->slug),
                    'access_token' => $token,
                ]);
            }

            $resBody = $response->json();

            if ($response->successful() && (!empty($resBody['id']) || !empty($resBody['post_id']))) {
                $postId = $resBody['post_id'] ?? $resBody['id'];
                
                // Update property with fb_post_id
                $property->update(['fb_post_id' => $postId]);

                Log::info("FB Post Success (Property #{$property->id}): Post ID {$postId}");
                return ['success' => true, 'post_id' => $postId];
            }

            $errMsg = $resBody['error']['message'] ?? ('HTTP Error ' . $response->status());
            Log::error("FB Graph API Error (Property #{$property->id}): " . json_encode($resBody));
            return ['success' => false, 'error' => $errMsg];

        } catch (\Throwable $e) {
            Log::error("FB AutoPost Exception (Property #{$property->id}): " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a test post to Facebook
     */
    public static function sendTestPost(): array
    {
        $pageId = trim(Setting::get('adm_fb_page_id', Setting::get('fb_page_id', '')));
        $token  = trim(Setting::get('adm_fb_page_access_token', Setting::get('fb_access_token', '')));

        if (empty($pageId) || empty($token)) {
            return ['success' => false, 'error' => 'অনুগ্রহ করে Facebook Page ID এবং Page Access Token পূরণ করুন।'];
        }

        $testCaption = "🚀 PropertyKoi.com টেস্ট পোস্ট\n\n"
                     . "আমাদের রিয়েল এস্টেট প্ল্যাটফর্মের Facebook Graph API ইন্টিগ্রেশন সফলভাবে সক্রিয় হয়েছে! ✅\n\n"
                     . "🌐 ভিজিট করুন: " . url('/') . "\n"
                     . "📅 সময়: " . now()->format('Y-m-d h:i A');

        $endpoint = "https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/{$pageId}/feed";

        try {
            $response = Http::timeout(25)->post($endpoint, [
                'message'      => $testCaption,
                'link'         => url('/'),
                'access_token' => $token,
            ]);

            $resBody = $response->json();

            if ($response->successful() && !empty($resBody['id'])) {
                return ['success' => true, 'post_id' => $resBody['id']];
            }

            $errMsg = $resBody['error']['message'] ?? ('HTTP Error ' . $response->status());
            return ['success' => false, 'error' => $errMsg];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build standard Bengali caption
     */
    public static function buildCaption(Property $p): string
    {
        $customTemplate = Setting::get('adm_fb_caption_template');
        if (!empty($customTemplate) && trim($customTemplate) !== '') {
            $typeBn = match($p->type) {
                'land' => 'জমি / প্লট',
                'flat' => 'রেডি ফ্ল্যাট',
                'commercial' => 'কমার্শিয়াল স্পেস',
                'land_share' => 'ল্যান্ড শেয়ার',
                default => 'প্রপার্টি',
            };
            $priceStr = $p->asking_price ? '৳ ' . number_format($p->asking_price) : 'আলোচনা সাপেক্ষে';
            $sizeStr = $p->size ? self::toBnNumber($p->size) . ' কাঠা' : ($p->flat_size_sqft ? self::toBnNumber($p->flat_size_sqft) . ' স্কয়ারফিট' : '');
            $adminWa = Setting::get('adm_admin_whatsapp_number', Setting::get('admin_whatsapp_number', '01811556677'));

            $replacements = [
                '{title}'       => (string) $p->title,
                '{code}'        => (string) $p->property_code,
                '{type}'        => $typeBn,
                '{area}'        => (string) ($p->area ?: 'আফতাবনগর'),
                '{block}'       => (string) ($p->block ?: ''),
                '{plot}'        => (string) ($p->plot_number ?: ''),
                '{road}'        => (string) ($p->road_number ?: ''),
                '{facing}'      => (string) ($p->facing ?: ''),
                '{size}'        => $sizeStr,
                '{price}'       => $priceStr,
                '{url}'         => url('/property/' . $p->slug),
                '{phone}'       => $adminWa,
                '{description}' => strip_tags((string) $p->description),
            ];

            return strtr($customTemplate, $replacements);
        }

        $typeBn = match($p->type) {
            'land' => 'জমি / প্লট',
            'flat' => 'রেডি ফ্ল্যাট',
            'commercial' => 'কমার্শিয়াল স্পেস',
            'land_share' => 'ল্যান্ড শেয়ার',
            default => 'প্রপার্টি',
        };

        $priceStr = $p->asking_price ? '৳ ' . number_format($p->asking_price) : 'আলোচনা সাপেক্ষে';
        if ($p->price_per_katha && $p->price_per_katha > 0) {
            $priceStr .= " (প্রতি কাঠা: ৳ " . number_format($p->price_per_katha) . ")";
        }

        $sizeStr = $p->size ? self::toBnNumber($p->size) . ' কাঠা' : ($p->flat_size_sqft ? self::toBnNumber($p->flat_size_sqft) . ' স্কয়ারফিট' : '');

        $facingBn = [
            'north'      => 'উত্তরমুখী',
            'south'      => 'দক্ষিণমুখী',
            'east'       => 'পূর্বমুখী',
            'west'       => 'পশ্চিমমুখী',
            'north_east' => 'উত্তর-পূর্বমুখী',
            'south_east' => 'দক্ষিণ-পূর্বমুখী',
            'north_west' => 'উত্তর-পশ্চিমমুখী',
            'south_west' => 'দক্ষিণ-পশ্চিমমুখী',
        ][$p->facing] ?? $p->facing ?? '';

        $adminWa = Setting::get('admin_whatsapp_number', '01811556677');
        $propUrl = url('/property/' . $p->slug);

        $lines = [];
        $lines[] = "🏡 [নতুন লিস্টিং] " . ($p->title ?? 'আকর্ষণীয় প্রপার্টি');
        $lines[] = "🆔 কোড: #" . ($p->property_code ?? '');
        $lines[] = "📌 ক্যাটাগরি: " . $typeBn;
        $lines[] = "📍 লোকেশন: " . ($p->area ?? 'আফতাবনগর') . (!empty($p->block) ? ", ব্লক: {$p->block}" : '') . (!empty($p->road_number) ? " (রোড: {$p->road_number})" : '');

        if (!empty($sizeStr)) {
            $lines[] = "📐 সাইজ: " . $sizeStr;
        }
        if (!empty($facingBn)) {
            $lines[] = "🧭 ফেসিং: " . $facingBn;
        }
        $lines[] = "💰 মূল্য: " . $priceStr . ($p->is_negotiable ? " (আলোচনা সাপেক্ষে)" : "");

        if (!empty($p->description)) {
            $shortDesc = \Illuminate\Support\Str::words(strip_tags($p->description), 25, '...');
            $lines[] = "\n📝 বিবরণ: " . $shortDesc;
        }

        $lines[] = "\n🌐 সম্পূর্ণ ছবি ও ম্যাপ লোকেশন দেখতে লিংকে যান:";
        $lines[] = "🔗 " . $propUrl;
        $lines[] = "\n📞 সরাসরি ভিজিট শিডিউল ও তথ্যের জন্য যোগাযোগ:";
        $lines[] = "📱 WhatsApp / Call: " . $adminWa;
        $lines[] = "\n#PropertyKoi #Aftabnagar #RealEstate #DhakaProperties #" . str_replace(' ', '', $p->area ?? 'Aftabnagar');

        return implode("\n", $lines);
    }

    /**
     * Get absolute public image URL for property
     */
    public static function getPropertyImageUrl(Property $p): ?string
    {
        $cover = $p->media()->where('media_type', 'photo')->orderByDesc('is_cover')->orderBy('sort_order')->first();
        if (!$cover || empty($cover->file_path)) {
            return null;
        }

        $url = $cover->file_path;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = asset($url);
        }

        return $url;
    }

    public static function toBnNumber($number): string
    {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, (string)$number);
    }
}
