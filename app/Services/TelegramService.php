<?php

namespace App\Services;

use App\Models\BuyerDemand;
use App\Models\Property;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    const API_BASE = 'https://api.telegram.org/bot';

    public static function getBotToken(): string
    {
        $setting = \App\Models\Setting::get('adm_telegram_bot_token');
        if (!empty($setting)) {
            return trim($setting);
        }
        return trim((string) config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN', '')));
    }

    public static function getBotUsername(): string
    {
        $setting = \App\Models\Setting::get('adm_telegram_bot_username');
        if (!empty($setting)) {
            return ltrim(trim($setting), '@');
        }
        return ltrim(trim((string) config('services.telegram.bot_username', env('TELEGRAM_BOT_USERNAME', 'AftabnagarPropertyBot'))), '@');
    }

    public static function getAdminChatId(): string
    {
        $setting = \App\Models\Setting::get('adm_telegram_admin_chat_id');
        if (!empty($setting)) {
            return trim($setting);
        }
        return trim((string) config('services.telegram.admin_chat_id', env('TELEGRAM_ADMIN_CHAT_ID', '')));
    }

    public static function isConfigured(): bool
    {
        return !empty(self::getBotToken());
    }

    public static function resolveChatIdFromUsername(string $username, ?string $tokenOverride = null): ?int
    {
        $cleanUser = strtolower(ltrim(trim($username), '@'));
        if (empty($cleanUser)) return null;

        $token = $tokenOverride ? trim($tokenOverride) : self::getBotToken();
        if (empty($token)) return null;

        try {
            $url = self::API_BASE . $token . '/getUpdates';
            $response = Http::timeout(10)->withoutVerifying()->get($url);
            $json = $response->json();

            if (!empty($json['ok']) && !empty($json['result'])) {
                foreach (array_reverse($json['result']) as $update) {
                    $msg = $update['message'] ?? ($update['edited_message'] ?? null);
                    if (!$msg) continue;

                    $fromUser = strtolower($msg['from']['username'] ?? '');
                    $chatUser = strtolower($msg['chat']['username'] ?? '');
                    $chatId = $msg['chat']['id'] ?? null;

                    if (($fromUser === $cleanUser || $chatUser === $cleanUser) && $chatId) {
                        // If this matches adminChatId, auto-update setting
                        $adminSetting = strtolower(ltrim(trim((string)\App\Models\Setting::get('adm_telegram_admin_chat_id')), '@'));
                        if ($adminSetting === $cleanUser) {
                            \App\Models\Setting::set('adm_telegram_admin_chat_id', (string)$chatId);
                        }
                        return (int) $chatId;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[TelegramService] resolveChatIdFromUsername error: ' . $e->getMessage());
        }

        return null;
    }

    public static function sendMessage(string|int $chatId, string $text, array $replyMarkup = [], ?string $tokenOverride = null): array
    {
        $token = $tokenOverride ? trim($tokenOverride) : self::getBotToken();
        if (empty($token)) {
            return ['success' => false, 'error' => 'Telegram Bot Token কনফিগার করা নেই।'];
        }

        $targetChat = trim((string)$chatId);

        // If username was passed (e.g. @maqblue or maqblue), try resolving via getUpdates
        if (!is_numeric($targetChat) && !str_starts_with($targetChat, '-')) {
            $resolved = self::resolveChatIdFromUsername($targetChat, $token);
            if ($resolved) {
                $targetChat = (string) $resolved;
            } else {
                $botUser = self::getBotUsername();
                $cleanName = ltrim($targetChat, '@');
                return [
                    'success' => false,
                    'error' => "টেলিগ্রামের নিরাপত্তা নিয়ম অনুযায়ী সরাসরি ইউজারনেমে (@{$cleanName}) মেসেজ পাঠানো যায় না। অনুগ্রহ করে টেলিগ্রাম অ্যাপে @{$botUser} এ গিয়ে /start চাপলে আপনার নিউমেরিক Chat ID (যেমন: 6891457989) দেখতে পাবেন, সেটি ব্যবহার করুন।"
                ];
            }
        }

        $url = self::API_BASE . $token . '/sendMessage';
        $payload = [
            'chat_id' => (string) $targetChat,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ];

        if (!empty($replyMarkup)) {
            // Telegram Bot API rejects inline buttons with localhost or 127.0.0.1 URLs.
            // Filter them out during local development so messages deliver reliably.
            if (isset($replyMarkup['inline_keyboard']) && is_array($replyMarkup['inline_keyboard'])) {
                $sanitizedRows = [];
                foreach ($replyMarkup['inline_keyboard'] as $row) {
                    $sanitizedRow = [];
                    foreach ($row as $btn) {
                        $btnUrl = $btn['url'] ?? '';
                        if ($btnUrl && (str_contains($btnUrl, 'localhost') || str_contains($btnUrl, '127.0.0.1'))) {
                            continue;
                        }
                        $sanitizedRow[] = $btn;
                    }
                    if (!empty($sanitizedRow)) {
                        $sanitizedRows[] = $sanitizedRow;
                    }
                }
                if (!empty($sanitizedRows)) {
                    $payload['reply_markup'] = json_encode(['inline_keyboard' => $sanitizedRows]);
                }
            } else {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }
        }

        try {
            $response = Http::timeout(15)->withoutVerifying()->asForm()->post($url, $payload);
            $json = $response->json();

            if ($response->successful() && !empty($json['ok'])) {
                return [
                    'success' => true,
                    'message_id' => $json['result']['message_id'] ?? null,
                    'result' => $json['result'] ?? null,
                ];
            }

            $errMsg = $json['description'] ?? ('HTTP ' . $response->status() . ': ' . $response->body());
            Log::warning('[TelegramService] Send error: ' . $errMsg);
            return ['success' => false, 'error' => $errMsg];
        } catch (\Throwable $e) {
            Log::error('[TelegramService] Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function sendDemandConfirmation(string|int $chatId, BuyerDemand $demand, array $matches = []): array
    {
        $siteName = config('app.name', 'PropertyKoi');
        $buyerName = e($demand->buyer_name ?: 'সম্মানিত ক্রেতা');
        $typeLabel = match($demand->property_type) {
            'land'       => '🌱 জমি / প্লট',
            'flat'       => '🏢 আবাসিক ফ্ল্যাট',
            'land_share' => '🤝 ল্যান্ড শেয়ার',
            'commercial' => '🏬 কমার্শিয়াল স্পেস',
            default      => 'যেকোনো ধরনের',
        };
        $areas = is_array($demand->preferred_areas) ? implode(', ', $demand->preferred_areas) : ($demand->preferred_areas ?: 'আফতাবনগর');

        $text = "📋 <b>{$siteName} — প্রপার্টি চাহিদা নিশ্চিতকরণ</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "আসসালামু আলাইকুম <b>{$buyerName}</b>,\n";
        $text .= "PropertyKoi-তে আপনার প্রপার্টি চাহিদা সফলভাবে গ্রহণ করা হয়েছে!\n\n";
        $text .= "🔖 <b>রেফারেন্স কোড:</b> <code>#{$demand->requirement_code}</code>\n";
        $text .= "📌 <b>ধরন:</b> {$typeLabel}\n";
        $text .= "📍 <b>পছন্দের এরিয়া:</b> {$areas}\n";
        $text .= "🟢 <b>স্ট্যাটাস:</b> সক্রিয় (Active)\n\n";
        $text .= "🎯 <i>আমাদের AI ইঞ্জিন আপনার পছন্দের সাথে মিলে এমন নতুন কোনো প্রপার্টি লিস্টিং হওয়ামাত্রই স্বয়ংক্রিয়ভাবে আপনাকে এই চ্যাটে অ্যালার্ট পাঠাবে।</i>\n";

        $buttons = [
            [
                ['text' => '🔍 চাহিদার স্ট্যাটাস দেখুন', 'url' => route('chahida.show', $demand->requirement_code)]
            ]
        ];

        if (!empty($matches)) {
            $text .= "\n🔥 <b>ইতিমধ্যে " . count($matches) . "টি প্রাথমিক প্রপার্টি ম্যাচ পাওয়া গেছে!</b>";
        }

        return self::sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    public static function sendPropertyMatch(string|int $chatId, BuyerDemand $demand, array $matches): array
    {
        $siteName = config('app.name', 'PropertyKoi');
        $buyerName = e($demand->buyer_name ?: 'সম্মানিত ক্রেতা');
        $matchCount = count($matches);

        $text = "🎯 <b>{$siteName} — প্রপার্টি ডিমান্ড ম্যাচ অ্যালার্ট</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "আসসালামু আলাইকুম <b>{$buyerName}</b>,\n";
        $text .= "আপনার চাহিদা (রেফারেন্স: <code>{$demand->requirement_code}</code>) এর সাথে মিলে এমন <b>{$matchCount}টি</b> নতুন প্রপার্টি পাওয়া গেছে:\n\n";

        $buttons = [];
        $i = 1;

        foreach ($matches as $match) {
            /** @var Property $p */
            $p = $match['property'];
            $priceStr = $p->formatted_price ?: 'আলোচনা সাপেক্ষ';
            $locStr = ($p->area ?: 'আফতাবনগর') . ($p->block ? ' • ব্লক ' . $p->block : ($p->sub_area ? ' • ' . $p->sub_area : ''));
            $score = $match['score'] ?? 100;
            $detailUrl = url('/property/' . $p->slug);

            $text .= "<b>{$i}. {$p->title}</b>\n";
            $text .= "📍 অবস্থান: {$locStr}\n";
            $text .= "💰 মূল্য: <b>{$priceStr}</b> | স্কোর: <b>{$score}%</b>\n";
            $text .= "🔗 বিস্তারিত: <a href=\"{$detailUrl}\">এখানে ক্লিক করে দেখুন</a>\n\n";

            if ($i <= 3) {
                $buttons[] = [
                    ['text' => "🏡 {$i}. {$p->title} দেখুন", 'url' => $detailUrl]
                ];
            }
            $i++;
        }

        $unsubUrl = route('demand.unsubscribe', ['token' => $demand->unsubscribe_token]);
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "🔕 <i>নোটিফিকেশন বন্ধ করতে:</i> <a href=\"{$unsubUrl}\">আনসাবস্ক্রাইব করুন</a>";

        $buttons[] = [
            ['text' => '🔕 নোটিফিকেশন বন্ধ / আনসাবস্ক্রাইব', 'url' => $unsubUrl]
        ];

        return self::sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    public static function sendAdminAlert(string $message): array
    {
        $adminChatId = self::getAdminChatId();
        if (empty($adminChatId)) {
            return ['success' => false, 'error' => 'Admin Telegram Chat ID কনফিগার করা নেই।'];
        }
        return self::sendMessage($adminChatId, $message);
    }

    public static function testConnection(?string $token = null, ?string $chatId = null): array
    {
        $token = $token ? trim($token) : self::getBotToken();
        if (empty($token)) {
            return ['success' => false, 'error' => 'Telegram Bot Token প্রদান করা হয়নি।'];
        }

        $url = self::API_BASE . $token . '/getMe';

        try {
            $response = Http::timeout(12)->withoutVerifying()->get($url);
            $json = $response->json();

            if ($response->successful() && !empty($json['ok'])) {
                $bot = $json['result'] ?? [];
                $botName = $bot['first_name'] ?? 'Bot';
                $botUsername = $bot['username'] ?? '';

                $msg = sprintf('✅ বট সফলভাবে কানেক্ট হয়েছে! বট নেম: %s (@%s)', $botName, $botUsername);

                $targetChat = $chatId ? trim($chatId) : self::getAdminChatId();
                if (!empty($targetChat)) {
                    $pingRes = self::sendMessage($targetChat, "🔔 <b>টেস্ট নোটিফিকেশন:</b> PropertyKoi মার্কেটপ্লেস থেকে টেলিগ্রাম বট সফলভাবে কানেক্ট হয়েছে!", [], $token);
                    if ($pingRes['success']) {
                        $msg .= " এবং চ্যাটে ({$targetChat}) সফলভাবে টেস্ট মেসেজ পাঠানো হয়েছে!";
                    } else {
                        $msg .= " ⚠️ তবে চ্যাটে মেসেজ যায়নি: " . ($pingRes['error'] ?? '');
                    }
                }

                return ['success' => true, 'message' => $msg, 'bot' => $bot];
            }

            return ['success' => false, 'error' => $json['description'] ?? 'টেলিগ্রাম সার্ভার ত্রুটি'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'টেলিগ্রাম সার্ভারে যোগাযোগ করা যায়নি: ' . $e->getMessage()];
        }
    }

    public static function getDeepLinkForDemand(BuyerDemand|string $demandOrCode): string
    {
        $code = $demandOrCode instanceof BuyerDemand ? $demandOrCode->requirement_code : $demandOrCode;
        $botUsername = self::getBotUsername();
        return "https://t.me/{$botUsername}?start=req_{$code}";
    }

    public static function getChannelJoinUrl(): ?string
    {
        $channel = \App\Models\Setting::get('adm_telegram_channel_url');
        if (!empty($channel)) {
            return trim($channel);
        }
        $channelUser = \App\Models\Setting::get('adm_telegram_channel_username');
        if (!empty($channelUser)) {
            return 'https://t.me/' . ltrim(trim($channelUser), '@');
        }
        return null;
    }

    public static function processIncomingUpdate(array $update): array
    {
        $msg = $update['message'] ?? ($update['edited_message'] ?? null);
        if (!$msg) {
            return ['handled' => false, 'reason' => 'no_message'];
        }

        $chatId = $msg['chat']['id'] ?? null;
        if (!$chatId) {
            return ['handled' => false, 'reason' => 'no_chat_id'];
        }

        $text = trim($msg['text'] ?? '');
        $fromUser = strtolower(ltrim($msg['from']['username'] ?? ($msg['chat']['username'] ?? ''), '@'));
        $firstName = $msg['from']['first_name'] ?? 'সম্মানিত ইউজার';

        // 1. Check deep link with requirement code: /start req_REQ-XXXXXX or /start req_17
        if (preg_match('/^\/start\s+req_([A-Za-z0-9_-]+)/i', $text, $matches)) {
            $code = trim($matches[1]);
            $demand = BuyerDemand::where('requirement_code', $code)->orWhere('id', $code)->first();

            if ($demand) {
                $demand->telegram_chat_id = (string) $chatId;
                if ($fromUser) {
                    $demand->telegram_username = $fromUser;
                }
                $channels = (array) ($demand->notify_channels ?: []);
                if (!in_array('telegram', $channels, true)) {
                    $channels[] = 'telegram';
                }
                $demand->notify_channels = array_values($channels);
                $demand->save();

                // Propagate telegram_chat_id to other demands by this buyer
                if (!empty($demand->buyer_phone)) {
                    BuyerDemand::where('buyer_phone', $demand->buyer_phone)
                        ->where(function($q) {
                            $q->whereNull('telegram_chat_id')->orWhere('telegram_chat_id', '');
                        })
                        ->update([
                            'telegram_chat_id' => (string) $chatId,
                            'telegram_username' => $fromUser ?: null,
                        ]);
                }
                if (!empty($demand->buyer_email)) {
                    BuyerDemand::where('buyer_email', $demand->buyer_email)
                        ->where(function($q) {
                            $q->whereNull('telegram_chat_id')->orWhere('telegram_chat_id', '');
                        })
                        ->update([
                            'telegram_chat_id' => (string) $chatId,
                            'telegram_username' => $fromUser ?: null,
                        ]);
                }

                // Send immediate welcome & confirmation for this demand
                $matchesList = BuyerDemandMatchService::findMatchingPropertiesForRequirement($demand);
                self::sendDemandConfirmation($chatId, $demand, $matchesList);

                // If matches exist, also send property match alert with cards
                if (!empty($matchesList)) {
                    self::sendPropertyMatch($chatId, $demand, $matchesList);
                }

                return [
                    'handled'   => true,
                    'type'      => 'demand_connected',
                    'code'      => $demand->requirement_code,
                    'chat_id'   => $chatId,
                    'username'  => $fromUser,
                ];
            }
        }

        // 2. Check admin connect: /start admin
        if (preg_match('/^\/start\s+admin/i', $text)) {
            \App\Models\Setting::set('adm_telegram_admin_chat_id', (string) $chatId);
            self::sendMessage($chatId, "👑 <b>অ্যাডমিন অ্যালার্ট সক্রিয় হয়েছে!</b>\nএখন থেকে সকল নতুন লিড ও প্রপার্টি নোটিফিকেশন এই চ্যাটে আসবে।\nChat ID: <code>{$chatId}</code>");
            return [
                'handled'  => true,
                'type'     => 'admin_connected',
                'chat_id'  => $chatId,
                'username' => $fromUser,
            ];
        }

        // 3. Simple /start or /start id
        if ($text === '/start' || preg_match('/^\/start\s+id/i', $text)) {
            // Also check if admin username matches
            $adminUser = strtolower(ltrim(trim((string)\App\Models\Setting::get('adm_telegram_admin_chat_id')), '@'));
            if (!empty($adminUser) && ($adminUser === $fromUser)) {
                \App\Models\Setting::set('adm_telegram_admin_chat_id', (string) $chatId);
            }

            // Auto-bind pending demands matching this username
            if (!empty($fromUser)) {
                $pendingDemands = BuyerDemand::where('telegram_username', $fromUser)
                    ->where(function($q) {
                        $q->whereNull('telegram_chat_id')->orWhere('telegram_chat_id', '');
                    })->get();
                foreach ($pendingDemands as $pDemand) {
                    $pDemand->update([
                        'telegram_chat_id' => (string) $chatId,
                    ]);
                    $matchesList = BuyerDemandMatchService::findMatchingPropertiesForRequirement($pDemand);
                    self::sendDemandConfirmation($chatId, $pDemand, $matchesList);
                    if (!empty($matchesList)) {
                        self::sendPropertyMatch($chatId, $pDemand, $matchesList);
                    }
                }
            }

            $reply = "👋 আসসালামু আলাইকুম <b>{$firstName}</b>!\n";
            $reply .= "<b>PropertyKoi অ্যালার্ট বটে আপনাকে স্বাগতম।</b>\n━━━━━━━━━━━━━━━━━━━━\n";
            $reply .= "🆔 আপনার Telegram Chat ID: <code>{$chatId}</code>\n";
            if ($fromUser) {
                $reply .= "👤 আপনার ইউজারনেম: @{$fromUser}\n";
            }
            $reply .= "\n⚡ <b>প্রপার্টি অ্যালার্ট চালু করতে:</b>\nওয়েবসাইটে আপনার পছন্দের প্রপার্টি চাহিদা সাবমিট করুন অথবা আপনার অ্যাকাউন্টে গিয়ে ১-ক্লিক লিঙ্কে চাপুন।\n\nনতুন কোনো প্রপার্টি লিস্টিং হওয়া মাত্রই স্বয়ংক্রিয়ভাবে আপনি এখানে অ্যালার্ট পাবেন!";

            self::sendMessage($chatId, $reply);

            return [
                'handled'  => true,
                'type'     => 'greeting_sent',
                'chat_id'  => $chatId,
                'username' => $fromUser,
            ];
        }

        return ['handled' => false, 'reason' => 'unrecognized_command'];
    }

    public static function syncPendingUpdates(): array
    {
        $token = self::getBotToken();
        if (empty($token)) {
            return ['success' => false, 'error' => 'Bot token not set'];
        }

        $lastUpdateId = (int) \App\Models\Setting::get('adm_telegram_last_update_id', 0);
        $url = self::API_BASE . $token . '/getUpdates';
        $params = [
            'timeout' => 0,
            'limit'   => 50,
        ];
        if ($lastUpdateId > 0) {
            $params['offset'] = $lastUpdateId + 1;
        }

        try {
            $response = Http::timeout(10)->withoutVerifying()->get($url, $params);
            $json = $response->json();

            if (empty($json['ok']) || empty($json['result'])) {
                return ['success' => true, 'processed' => 0];
            }

            $maxId = $lastUpdateId;
            $processed = [];

            foreach ($json['result'] as $up) {
                $uId = $up['update_id'] ?? 0;
                if ($uId > $maxId) {
                    $maxId = $uId;
                }
                $res = self::processIncomingUpdate($up);
                $processed[] = $res;
            }

            if ($maxId > $lastUpdateId) {
                \App\Models\Setting::set('adm_telegram_last_update_id', (string) $maxId);
            }

            return [
                'success'   => true,
                'processed' => count($processed),
                'details'   => $processed,
            ];
        } catch (\Throwable $e) {
            Log::warning('[TelegramService] syncPendingUpdates error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

