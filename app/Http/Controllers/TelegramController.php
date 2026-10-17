<?php

namespace App\Http\Controllers;

use App\Models\BuyerDemand;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    /**
     * 1-Click Connect Redirect: Opens Telegram App directly with deep link
     */
    public function connect(string $code): RedirectResponse
    {
        $deepLink = TelegramService::getDeepLinkForDemand($code);
        return redirect()->away($deepLink);
    }

    /**
     * AJAX status check for 1-click connect on demand success page / buyer dashboard
     */
    public function checkDemandStatus(string $code): JsonResponse
    {
        $demand = BuyerDemand::where('requirement_code', $code)->orWhere('id', $code)->first();

        if (!$demand) {
            return response()->json([
                'success'   => false,
                'connected' => false,
                'message'   => 'চাহিদা খুঁজে পাওয়া যায়নি।',
            ], 404);
        }

        // If not connected, pull pending updates from Telegram
        if (empty($demand->telegram_chat_id)) {
            TelegramService::syncPendingUpdates();
            $demand->refresh();
        }

        $connected = !empty($demand->telegram_chat_id);
        $deepLink = TelegramService::getDeepLinkForDemand($demand);
        $channelUrl = TelegramService::getChannelJoinUrl();

        return response()->json([
            'success'          => true,
            'connected'        => $connected,
            'chat_id'          => $demand->telegram_chat_id,
            'username'         => $demand->telegram_username ?? null,
            'deep_link'        => $deepLink,
            'channel_url'      => $channelUrl,
            'requirement_code' => $demand->requirement_code,
            'message'          => $connected
                ? '✅ টেলিগ্রাম সফলভাবে সংযুক্ত হয়েছে! এখন থেকে নোটিফিকেশন পাবেন।'
                : 'টেলিগ্রাম সংযোগের অপেক্ষায়...',
        ]);
    }

    /**
     * Production Webhook Receiver
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $update = $request->all();
        if (empty($update)) {
            return response()->json(['ok' => false, 'error' => 'empty_payload']);
        }

        $res = TelegramService::processIncomingUpdate($update);
        return response()->json(['ok' => true, 'result' => $res]);
    }

    /**
     * Helper to set Telegram Webhook URL on live domain
     */
    public function setWebhook(Request $request): JsonResponse
    {
        $webhookUrl = url('/api/telegram/webhook');
        $token = TelegramService::getBotToken();

        if (empty($token)) {
            return response()->json(['ok' => false, 'error' => 'Bot token missing']);
        }

        try {
            $res = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->asForm()
                ->post(TelegramService::API_BASE . $token . '/setWebhook', [
                    'url' => $webhookUrl,
                ])
                ->json();

            return response()->json($res);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
