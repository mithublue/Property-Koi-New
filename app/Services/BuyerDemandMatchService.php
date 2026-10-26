<?php

namespace App\Services;

use App\Models\BuyerDemand;
use App\Models\DemandNotification;
use App\Models\Property;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BuyerDemandMatchService
{
    public static function runDailyDemandMatching(bool $force = false): array
    {
        $query = BuyerDemand::where('status', 'active');
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('last_notified_at')
                  ->orWhere('last_notified_at', '<', now()->subHours(20));
            });
        }

        $demands = $query->orderBy('id', 'asc')->limit(100)->get();

        $summary = [
            'total_requirements' => $demands->count(),
            'matched_demands'    => 0,
            'push_sent'          => 0,
            'email_sent'         => 0,
            'telegram_sent'      => 0,
            'phone_logs'         => 0,
            'details'            => [],
        ];

        foreach ($demands as $demand) {
            $matches = self::findMatchingPropertiesForRequirement($demand);
            if (empty($matches)) {
                continue;
            }

            // Exclude already notified properties
            $newMatches = [];
            foreach ($matches as $match) {
                $propId = $match['property']->id;
                $alreadySent = DemandNotification::where('buyer_demand_id', $demand->id)
                    ->where('property_id', $propId)
                    ->exists();

                if (!$alreadySent) {
                    $newMatches[] = $match;
                }
            }

            if (empty($newMatches)) {
                continue;
            }

            $summary['matched_demands']++;
            $dispatchRes = self::dispatchDemandNotifications($demand, $newMatches);

            $summary['push_sent']     += $dispatchRes['push_sent'];
            $summary['email_sent']    += $dispatchRes['email_sent'];
            $summary['telegram_sent'] += $dispatchRes['telegram_sent'];
            $summary['phone_logs']    += $dispatchRes['phone_logs'];

            $uniqueMatchesCount = DemandNotification::where('buyer_demand_id', $demand->id)
                ->distinct('property_id')
                ->count('property_id');

            $demand->update([
                'last_notified_at' => now(),
                'match_count'      => $uniqueMatchesCount,
                'status'           => 'matched',
            ]);

            $summary['details'][] = [
                'requirement_code' => $demand->requirement_code,
                'buyer_name'       => $demand->buyer_name,
                'new_matches'      => count($newMatches),
            ];
        }

        return $summary;
    }

    /**
     * Trigger immediate demand matching when a new property is published to live.
     */
    public static function matchNewProperty(Property $property): array
    {
        if ($property->status !== 'live') {
            return [
                'matched_demands' => 0,
                'push_sent'       => 0,
                'email_sent'      => 0,
                'telegram_sent'   => 0,
                'phone_logs'      => 0,
                'details'         => [],
            ];
        }

        $property->loadMissing(['land', 'flat', 'share', 'commercial', 'media']);

        $demands = BuyerDemand::where('status', 'active')->get();
        $matchedCount = 0;
        $pushSent = 0;
        $emailSent = 0;
        $telegramSent = 0;
        $phoneLogs = 0;
        $details = [];

        foreach ($demands as $demand) {
            $matches = self::findMatchingPropertiesForRequirement($demand);
            $thisMatch = null;
            foreach ($matches as $m) {
                if ($m['property']->id === $property->id) {
                    $thisMatch = $m;
                    break;
                }
            }

            if (!$thisMatch) {
                continue;
            }

            // Exclude if already notified
            $alreadySent = DemandNotification::where('buyer_demand_id', $demand->id)
                ->where('property_id', $property->id)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $matchedCount++;
            $dispatchRes = self::dispatchDemandNotifications($demand, [$thisMatch]);

            $pushSent     += $dispatchRes['push_sent'];
            $emailSent    += $dispatchRes['email_sent'];
            $telegramSent += $dispatchRes['telegram_sent'];
            $phoneLogs    += $dispatchRes['phone_logs'];

            $uniqueMatchesCount = DemandNotification::where('buyer_demand_id', $demand->id)
                ->distinct('property_id')
                ->count('property_id');

            $demand->update([
                'last_notified_at' => now(),
                'match_count'      => $uniqueMatchesCount,
                'status'           => 'matched',
            ]);

            $details[] = [
                'requirement_code' => $demand->requirement_code,
                'buyer_name'       => $demand->buyer_name,
                'score'            => $thisMatch['score'],
            ];
        }

        return [
            'matched_demands' => $matchedCount,
            'push_sent'       => $pushSent,
            'email_sent'      => $emailSent,
            'telegram_sent'   => $telegramSent,
            'phone_logs'      => $phoneLogs,
            'details'         => $details,
        ];
    }

    public static function findMatchingPropertiesForRequirement(BuyerDemand $demand): array
    {
        $query = Property::with(['land', 'flat', 'share', 'commercial', 'media'])
            ->where('status', 'live');

        if (!empty($demand->property_type) && $demand->property_type !== 'any') {
            $query->where('type', $demand->property_type);
        }

        $prefAreas = (array) ($demand->preferred_areas ?: []);
        if (!empty($prefAreas)) {
            $expanded = [];
            foreach ($prefAreas as $pa) {
                if (stripos($pa, 'Bashundhara') !== false) {
                    $expanded[] = 'Bashundhara';
                    $expanded[] = 'Bashundhara R/A';
                } elseif (stripos($pa, 'Aftabnagar') !== false) {
                    $expanded[] = 'Aftabnagar';
                    $expanded[] = 'Aftab Nagar';
                } elseif (stripos($pa, 'Purbachal') !== false) {
                    $expanded[] = 'Purbachal';
                    $expanded[] = 'Purbachal New Town';
                } else {
                    $expanded[] = $pa;
                }
            }
            $query->whereIn('area', array_unique($expanded));
        }

        $properties = $query->orderBy('created_at', 'desc')->limit(50)->get();
        if ($properties->isEmpty()) {
            return [];
        }

        $prefBlocks = (array) ($demand->preferred_blocks ?: []);
        $minBudget = (float) ($demand->min_budget ?? 0);
        $maxBudget = (float) ($demand->max_budget ?? 0);
        $minSize   = (float) ($demand->min_size ?? 0);
        $maxSize   = (float) ($demand->max_size ?? 0);

        // Budget tolerance (±15%)
        $budgetLower = $minBudget > 0 ? ($minBudget * 0.85) : 0;
        $budgetUpper = $maxBudget > 0 ? ($maxBudget * 1.15) : 0;

        // Size tolerance (±15%)
        $sizeLower = $minSize > 0 ? ($minSize * 0.85) : 0;
        $sizeUpper = $maxSize > 0 ? ($maxSize * 1.15) : 0;

        $matches = [];

        foreach ($properties as $prop) {
            $score = 100.0;
            $reasons = [];

            // 1. Block Match
            if (!empty($prefBlocks)) {
                $blockMatched = false;
                foreach ($prefBlocks as $pb) {
                    if (strcasecmp((string)$prop->block, (string)$pb) === 0 || strcasecmp((string)$prop->sub_area, (string)$pb) === 0) {
                        $blockMatched = true;
                        break;
                    }
                }
                if ($blockMatched) {
                    $reasons[] = 'পছন্দের ব্লকের সাথে মিলেছে (' . ($prop->block ?: $prop->sub_area) . ')';
                } else {
                    $score -= 25.0;
                    $reasons[] = 'ব্লক কিছুটা ভিন্ন';
                }
            }

            // 2. Budget Match
            $price = $prop->raw_price;
            if ($price > 0 && ($budgetLower > 0 || $budgetUpper > 0)) {
                if ($budgetLower > 0 && $price < $budgetLower) {
                    $score -= 20.0;
                    $reasons[] = 'বাজেটের চেয়ে কম মূল্যের';
                } elseif ($budgetUpper > 0 && $price > $budgetUpper) {
                    $score -= 30.0;
                    $reasons[] = 'বাজেটের চেয়ে কিছুটা বেশি';
                } else {
                    $reasons[] = 'বাজেটের মধ্যে রয়েছে';
                }
            }

            // 3. Size Match
            $size = 0.0;
            if ($prop->type === 'land' && $prop->land) {
                $size = (float) $prop->land->katha_size;
            } elseif ($prop->type === 'flat' && $prop->flat) {
                $size = (float) $prop->flat->flat_size_sqft;
            } elseif ($prop->type === 'commercial' && $prop->commercial) {
                $size = (float) $prop->commercial->size_sqft;
            }

            if ($size > 0 && ($sizeLower > 0 || $sizeUpper > 0)) {
                if ($sizeLower > 0 && $size < $sizeLower) {
                    $score -= 20.0;
                    $reasons[] = 'সাইজ কিছুটা ছোট';
                } elseif ($sizeUpper > 0 && $size > $sizeUpper) {
                    $score -= 15.0;
                    $reasons[] = 'সাইজ কিছুটা বড়';
                } else {
                    $reasons[] = 'পছন্দের সাইজের সাথে মানানসই';
                }
            }

            // 4. Road Width Match
            if (!empty($demand->min_road_width_ft) && $demand->min_road_width_ft > 0) {
                $propRoad = 0;
                if ($prop->type === 'land' && $prop->land) {
                    $propRoad = (int) ($prop->land->road_width_ft ?? 0);
                } elseif ($prop->type === 'flat' && $prop->flat) {
                    $propRoad = (int) ($prop->flat->road_width_ft ?? 0);
                }
                if ($propRoad > 0) {
                    if ($propRoad >= $demand->min_road_width_ft) {
                        $score += 5.0;
                        $reasons[] = "রাস্তা {$propRoad} ফিট (কাঙ্ক্ষিত প্রশস্ততা রয়েছে)";
                    } else {
                        $score -= 10.0;
                        $reasons[] = "রাস্তা কাঙ্ক্ষিত মাপের চেয়ে কিছুটা ছোট ({$propRoad} ফিট)";
                    }
                }
            }

            // 5. Facing Match
            $facings = (array) ($demand->preferred_facings ?? []);
            if (!empty($facings) && !in_array('Any', $facings, true)) {
                $propFacing = '';
                if ($prop->type === 'land' && $prop->land) {
                    $propFacing = $prop->land->facing ?? '';
                } elseif ($prop->type === 'flat' && $prop->flat) {
                    $propFacing = $prop->flat->facing ?? '';
                }
                if ($propFacing) {
                    $matchedFacing = false;
                    foreach ($facings as $f) {
                        if (stripos($propFacing, $f) !== false) {
                            $matchedFacing = true;
                            break;
                        }
                    }
                    if ($matchedFacing) {
                        $score += 5.0;
                        $reasons[] = "পছন্দের ফেসিং ({$propFacing}) মিলেছে";
                    }
                }
            }

            // 6. Flat specific matches (bedrooms)
            if ($prop->type === 'flat' && $prop->flat && !empty($demand->criteria_details)) {
                $cd = (array) $demand->criteria_details;
                if (!empty($cd['bed_count'])) {
                    $reqBeds = (int) $cd['bed_count'];
                    $propBeds = (int) ($prop->flat->bedrooms ?? 0);
                    if ($propBeds >= $reqBeds) {
                        $score += 5.0;
                        $reasons[] = "বেডরুম সংখ্যা ({$propBeds} বেড) মানানসই";
                    }
                }
            }

            if ($score >= 50.0) {
                $matches[] = [
                    'property' => $prop,
                    'score'    => round(max(50.0, min(100.0, $score)), 1),
                    'reasons'  => $reasons,
                ];
            }
        }

        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($matches, 0, 5);
    }

    public static function sendDemandReceivedConfirmation(BuyerDemand $demand, array $matches = []): array
    {
        $channels = is_array($demand->notify_channels)
            ? $demand->notify_channels
            : array_filter(array_map('trim', explode(',', strtolower((string)($demand->notify_channels ?: 'push,email,telegram')))));

        $result = [
            'email_sent'     => false,
            'telegram_sent'  => false,
            'telegram_error' => null,
        ];

        $siteName = config('app.name', 'PropertyKoi');

        // 1. Email confirmation to buyer
        if (in_array('email', $channels, true) && !empty($demand->buyer_email) && filter_var($demand->buyer_email, FILTER_VALIDATE_EMAIL)) {
            $subject = "[{$siteName}] আপনার প্রপার্টি চাহিদা সফলভাবে গৃহীত হয়েছে (কোড: #{$demand->requirement_code})";
            $html = view('emails.demand-received', [
                'demand'   => $demand,
                'matches'  => $matches,
                'siteName' => $siteName,
            ])->render();

            try {
                Mail::html($html, function ($msg) use ($demand, $subject, $siteName) {
                    $msg->to($demand->buyer_email, $demand->buyer_name)
                        ->subject($subject);
                });
                $result['email_sent'] = true;
            } catch (\Throwable $e) {
                Log::warning('[BuyerDemandMatchService] Confirmation email error: ' . $e->getMessage());
            }
        }

        // 2. Telegram confirmation to buyer
        if (in_array('telegram', $channels, true) && !empty($demand->telegram_chat_id)) {
            $teleRes = TelegramService::sendDemandConfirmation($demand->telegram_chat_id, $demand, $matches);
            $result['telegram_sent'] = !empty($teleRes['success']);
            $result['telegram_error'] = $teleRes['error'] ?? null;
        }

        return $result;
    }

    public static function dispatchDemandNotifications(BuyerDemand $demand, array $matches): array
    {
        $channels = is_array($demand->notify_channels)
            ? $demand->notify_channels
            : array_filter(array_map('trim', explode(',', strtolower((string)($demand->notify_channels ?: 'push,email,telegram')))));

        $pushSent = 0;
        $emailSent = 0;
        $telegramSent = 0;
        $phoneLogs = 0;

        $topMatch = $matches[0];
        $topProp = $topMatch['property'];
        $matchCount = count($matches);
        $siteName = config('app.name', 'PropertyKoi');
        $detailUrl = url('/property/' . $topProp->slug);

        // 1. Web Push Notification via FCM
        if (in_array('push', $channels, true)) {
            $title = "🎯 নতুন প্রপার্টি ম্যাচ অ্যালার্ট!";
            $body = "{$demand->buyer_name}, আপনার চাহিদার সাথে মিলে এমন {$matchCount}টি নতুন প্রপার্টি পাওয়া গেছে। প্রথমটি: {$topProp->title} ({$topProp->formatted_price})";
            $pushRes = FcmService::broadcastPush($title, $body, $detailUrl);
            if ($pushRes > 0) {
                $pushSent++;
                foreach ($matches as $m) {
                    DemandNotification::updateOrCreate([
                        'buyer_demand_id' => $demand->id,
                        'property_id' => $m['property']->id,
                        'channel' => 'push',
                    ], [
                        'user_id' => $demand->user_id,
                        'match_score' => $m['score'],
                        'status' => 'sent',
                        'delivery_details' => json_encode(['title' => $title, 'matches' => $matchCount]),
                    ]);
                }
            }
        }

        // 2. Email Notification
        if (in_array('email', $channels, true) && !empty($demand->buyer_email) && filter_var($demand->buyer_email, FILTER_VALIDATE_EMAIL)) {
            $subject = "[{$siteName}] আপনার চাহিদার প্রপার্টি ম্যাচ অ্যালার্ট (রেফারেন্স: {$demand->requirement_code})";
            $html = view('emails.demand-match', [
                'demand' => $demand,
                'matches' => $matches,
                'siteName' => $siteName,
            ])->render();

            try {
                Mail::html($html, function ($msg) use ($demand, $subject, $siteName) {
                    $msg->to($demand->buyer_email, $demand->buyer_name)
                        ->subject($subject);
                });
                $emailSent++;
                foreach ($matches as $m) {
                    DemandNotification::updateOrCreate([
                        'buyer_demand_id' => $demand->id,
                        'property_id' => $m['property']->id,
                        'channel' => 'email',
                    ], [
                        'user_id' => $demand->user_id,
                        'match_score' => $m['score'],
                        'status' => 'sent',
                        'delivery_details' => json_encode(['to' => $demand->buyer_email, 'subject' => $subject]),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('[BuyerDemandMatchService] Email sending error: ' . $e->getMessage());
            }
        }

        // 3. Telegram Notification
        if (in_array('telegram', $channels, true) && !empty($demand->telegram_chat_id)) {
            $teleRes = TelegramService::sendPropertyMatch($demand->telegram_chat_id, $demand, $matches);
            if (!empty($teleRes['success'])) {
                $telegramSent++;
                foreach ($matches as $m) {
                    DemandNotification::updateOrCreate([
                        'buyer_demand_id' => $demand->id,
                        'property_id' => $m['property']->id,
                        'channel' => 'telegram',
                    ], [
                        'user_id' => $demand->user_id,
                        'match_score' => $m['score'],
                        'status' => 'sent',
                        'delivery_details' => json_encode(['chat_id' => $demand->telegram_chat_id, 'message_id' => $teleRes['message_id'] ?? null]),
                    ]);
                }
            }
        }

        // 4. Phone / WhatsApp Log
        if (in_array('phone', $channels, true) && !empty($demand->buyer_phone)) {
            $phoneLogs++;
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$demand->buyer_phone);
            $waPhone = str_starts_with($cleanPhone, '0') ? ('88' . $cleanPhone) : $cleanPhone;
            $waText = urlencode("আসসালামু আলাইকুম {$demand->buyer_name},\nProperty Koi-তে আপনার চাহিদার সাথে মিলে এমন {$matchCount}টি প্রপার্টি পাওয়া গেছে:\n\n১. {$topProp->title} ({$topProp->formatted_price})\nলিংক: {$detailUrl}\n\nপ্রপার্টিটি সরাসরি দেখতে যোগাযোগ করতে পারেন।");
            $waLink = "https://wa.me/{$waPhone}?text={$waText}";

            foreach ($matches as $m) {
                DemandNotification::updateOrCreate([
                    'buyer_demand_id' => $demand->id,
                    'property_id' => $m['property']->id,
                    'channel' => 'phone',
                ], [
                    'user_id' => $demand->user_id,
                    'match_score' => $m['score'],
                    'status' => 'ready_for_call',
                    'delivery_details' => json_encode([
                        'phone' => $demand->buyer_phone,
                        'whatsapp_link' => $waLink,
                    ]),
                ]);
            }
        }

        return [
            'push_sent'     => $pushSent,
            'email_sent'    => $emailSent,
            'telegram_sent' => $telegramSent,
            'phone_logs'    => $phoneLogs,
        ];
    }
}
