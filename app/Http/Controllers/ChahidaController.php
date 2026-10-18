<?php

namespace App\Http\Controllers;

use App\Models\BuyerDemand;
use App\Services\BuyerDemandMatchService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChahidaController extends Controller
{
    public function index(): View
    {
        $botUsername = TelegramService::getBotUsername();
        return view('pages.chahida', [
            'botUsername' => $botUsername,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'buyer_name'        => 'required|string|max:191',
            'buyer_phone'       => 'required|string|max:50',
            'buyer_email'       => 'nullable|email|max:191',
            'property_type'     => 'required|in:land,flat,land_share,commercial,any',
            'preferred_areas'   => 'nullable|array',
            'preferred_blocks'  => 'nullable|array',
            'preferred_facings' => 'nullable|array',
            'min_road_width_ft' => 'nullable|numeric',
            'min_size'          => 'nullable|numeric',
            'max_size'          => 'nullable|numeric',
            'min_budget'        => 'nullable|numeric',
            'max_budget'        => 'nullable|numeric',
            'purpose'           => 'nullable|string|max:100',
            'purchase_timeline' => 'nullable|string|max:100',
            'notify_channels'   => 'nullable|array',
            'telegram_chat_id'  => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'criteria_details'  => 'nullable|array',
        ]);

        $channels = $validated['notify_channels'] ?? ['push', 'email'];

        // Strict validation: if email channel is selected, email must be provided!
        if (in_array('email', $channels, true) && empty($validated['buyer_email'])) {
            return back()->withInput()->withErrors([
                'buyer_email' => 'ইমেইল নোটিফিকেশন পেতে হলে অনুগ্রহ করে একটি সঠিক ইমেইল ঠিকানা প্রদান করুন।'
            ]);
        }

        // Collect rich criteria details (Plot & Flat specifications)
        $criteriaDetails = [];
        $criteriaFields = [
            'katha_size_choice', 'corner_plot', 'land_status', 'flat_size_preset',
            'bed_count', 'bath_count', 'floor_preference', 'handover_status',
            'parking_needed', 'budget_preset', 'commercial_type'
        ];
        foreach ($criteriaFields as $f) {
            if ($request->filled($f)) {
                $criteriaDetails[$f] = $request->input($f);
            }
        }
        if ($request->has('amenities') && is_array($request->input('amenities'))) {
            $criteriaDetails['amenities'] = $request->input('amenities');
        }
        if ($request->has('criteria_details') && is_array($request->input('criteria_details'))) {
            $criteriaDetails = array_merge($criteriaDetails, $request->input('criteria_details'));
        }

        $facings = $request->input('preferred_facings');
        if (is_string($facings)) {
            $facings = array_filter(array_map('trim', explode(',', $facings)));
        }
        if (empty($facings)) {
            $facings = ['Any'];
        }

        $teleInput = trim($validated['telegram_chat_id'] ?? '');
        $teleChatId = null;
        $teleUsername = null;
        if (!empty($teleInput)) {
            if (is_numeric($teleInput) || str_starts_with($teleInput, '-')) {
                $teleChatId = $teleInput;
            } else {
                $teleUsername = ltrim($teleInput, '@');
                $resolved = \App\Services\TelegramService::resolveChatIdFromUsername($teleUsername);
                if ($resolved) {
                    $teleChatId = (string) $resolved;
                }
            }
        }

        // Auto-link with previous Telegram connection if user has submitted before with same phone or email
        if (empty($teleChatId)) {
            $prevConnected = BuyerDemand::whereNotNull('telegram_chat_id')
                ->where('telegram_chat_id', '!=', '')
                ->where(function ($q) use ($validated) {
                    $q->where('buyer_phone', $validated['buyer_phone']);
                    if (!empty($validated['buyer_email'])) {
                        $q->orWhere('buyer_email', $validated['buyer_email']);
                    }
                })
                ->latest()
                ->first();

            if ($prevConnected) {
                $teleChatId = $prevConnected->telegram_chat_id;
                $teleUsername = $prevConnected->telegram_username;
            }
        }

        $demand = BuyerDemand::create([
            'buyer_name'        => $validated['buyer_name'],
            'buyer_phone'       => $validated['buyer_phone'],
            'buyer_email'       => $validated['buyer_email'] ?? null,
            'property_type'     => $validated['property_type'],
            'preferred_areas'   => !empty($validated['preferred_areas']) ? $validated['preferred_areas'] : ['Aftabnagar'],
            'preferred_blocks'  => $validated['preferred_blocks'] ?? [],
            'preferred_facings' => array_values($facings),
            'min_road_width_ft' => !empty($validated['min_road_width_ft']) ? (int) $validated['min_road_width_ft'] : null,
            'min_size'          => $validated['min_size'] ?? null,
            'max_size'          => $validated['max_size'] ?? null,
            'min_budget'        => $validated['min_budget'] ?? null,
            'max_budget'        => $validated['max_budget'] ?? null,
            'purpose'           => $validated['purpose'] ?? 'own_house',
            'purchase_timeline' => $validated['purchase_timeline'] ?? 'within_3_months',
            'notify_channels'   => $channels,
            'telegram_chat_id'  => $teleChatId,
            'telegram_username' => $teleUsername,
            'notes'             => $validated['notes'] ?? null,
            'criteria_details'  => !empty($criteriaDetails) ? $criteriaDetails : null,
            'user_id'           => auth()->id(),
            'status'            => 'active',
        ]);

        // Run instant match
        $matches = BuyerDemandMatchService::findMatchingPropertiesForRequirement($demand);
        $matchNotice = '';

        // Always send official demand receipt confirmation (Email & Telegram if connected)
        BuyerDemandMatchService::sendDemandReceivedConfirmation($demand, $matches);

        if (!empty($matches)) {
            BuyerDemandMatchService::dispatchDemandNotifications($demand, $matches);
            $matchCount = count($matches);
            $matchNotice = " আমরা ইতিমধ্যে আপনার চাহিদার সাথে মেলে এমন {$matchCount}টি প্রপার্টি খুঁজে পেয়েছি এবং আপনাকে নোটিফিকেশন পাঠিয়েছি!";
        }

        // Instant confirmation push notification to buyer
        if (in_array('push', $channels, true)) {
            $buyerTitle = '📋 আপনার চাহিদা রিকোয়েস্ট সফলভাবে জমা হয়েছে!';
            $buyerBody  = "রিকোয়ারমেন্ট কোড: #{$demand->requirement_code}। আপনার পছন্দের প্রপার্টি পাওয়া মাত্রই আপনাকে অ্যালার্ট পাঠানো হবে।";
            $buyerUrl   = route('chahida.show', $demand->requirement_code);
            if (!empty($demand->user_id)) {
                $userTokens = \App\Models\FcmDeviceToken::where('user_id', $demand->user_id)->pluck('token');
                foreach ($userTokens as $t) {
                    \App\Services\FcmService::sendToToken($t, $buyerTitle, $buyerBody, $buyerUrl);
                }
            } else {
                \App\Services\FcmService::broadcastPush($buyerTitle, $buyerBody, $buyerUrl);
            }
        }

        // Push notification & Telegram alert for new buyer requirement to Admin
        \App\Services\FcmService::notifyEvent(
            'adm_fcm_notify_lead_submit',
            '📋 নতুন কাস্টমার চাহিদা/রিকোয়ারমেন্ট জমা!',
            "{$validated['buyer_name']} ({$validated['buyer_phone']}) নতুন রিকোয়ারমেন্ট জমা দিয়েছেন (কোড: #{$demand->requirement_code})।",
            route('admin.leads.index')
        );
        \App\Services\TelegramService::sendAdminAlert("📋 <b>নতুন কাস্টমার রিকোয়ারমেন্ট!</b>\nনাম: {$validated['buyer_name']} ({$validated['buyer_phone']})\nটাইপ: {$validated['property_type']}\nকোড: #{$demand->requirement_code}");

        return redirect()->route('chahida.show', $demand->requirement_code)
            ->with('success', "আপনার চাহিদা সফলভাবে সংরক্ষিত হয়েছে (কোড: {$demand->requirement_code})।{$matchNotice}")
            ->with('just_submitted', true);
    }

    public function show(string $code): View
    {
        $demand = BuyerDemand::where('requirement_code', $code)->firstOrFail();
        $matches = BuyerDemandMatchService::findMatchingPropertiesForRequirement($demand);

        return view('pages.chahida-success', [
            'demand'  => $demand,
            'matches' => $matches,
        ]);
    }

    public function unsubscribe(string $token): View
    {
        $demand = BuyerDemand::where('unsubscribe_token', $token)->first();

        if ($demand) {
            $demand->update(['status' => 'closed']);
        }

        return view('pages.unsubscribe', [
            'demand' => $demand,
        ]);
    }
}
