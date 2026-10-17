<?php

namespace App\Http\Controllers;

use App\Models\BuyerInteraction;
use App\Models\FcmDeviceToken;
use App\Models\Lead;
use App\Models\Property;
use App\Models\ScheduleSlot;
use App\Models\VisitBooking;
use App\Services\FcmService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function bookVisit(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $property = Property::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'buyer_name'  => 'required|string|max:191',
            'buyer_phone' => 'required|string|max:50',
            'slot_date'   => 'required|date|after_or_equal:today',
            'slot_time'   => 'required|string|max:20',
            'message'     => 'nullable|string',
        ]);

        $lead = Lead::create([
            'property_id'   => $property->id,
            'buyer_name'    => $validated['buyer_name'],
            'buyer_phone'   => $validated['buyer_phone'],
            'enquiry_type'  => 'visit_request',
            'stage'         => 'visit_scheduled',
            'message'       => $validated['message'] ?? null,
            'user_id'       => auth()->id(),
        ]);

        $booking = VisitBooking::create([
            'property_id' => $property->id,
            'lead_id'     => $lead->id,
            'user_id'     => auth()->id(),
            'slot_date'   => $validated['slot_date'],
            'slot_time'   => $validated['slot_time'],
            'buyer_name'  => $validated['buyer_name'],
            'buyer_phone' => $validated['buyer_phone'],
            'message'     => $validated['message'] ?? null,
            'status'      => 'pending',
        ]);

        // Send alert to admin via Telegram
        TelegramService::sendAdminAlert("📅 <b>নতুন সাইট ভিজিট বুকিং!</b>\nপ্রপার্টি: {$property->title}\nগ্রাহক: {$validated['buyer_name']} ({$validated['buyer_phone']})\nতারিখ: {$validated['slot_date']} | সময়: {$validated['slot_time']}");

        // Send Push Notification to Admin & Agent
        FcmService::notifyEvent(
            'adm_fcm_notify_visit_booking',
            '📅 নতুন সাইট ভিজিট বুকিং!',
            "{$validated['buyer_name']} ({$validated['buyer_phone']}) #{$property->property_code} প্রপার্টিতে {$validated['slot_date']} তারিখে ভিজিট বুক করেছেন।",
            route('admin.visits.index', ['date' => $validated['slot_date']]),
            $property
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'আপনার সাইট ভিজিট রিকোয়েস্ট সফলভাবে গ্রহণ করা হয়েছে!',
                'data'    => [
                    'status'  => 'pending',
                    'message' => 'আমাদের প্রতিনিধি শিগগিরই আপনার সাথে যোগাযোগ করবেন।'
                ]
            ]);
        }

        return back()->with('success', 'আপনার সাইট ভিজিট রিকোয়েস্ট সফলভাবে গ্রহণ করা হয়েছে! আমাদের প্রতিনিধি শিগগিরই আপনার সাথে যোগাযোগ করবেন।');
    }

    public function submitLead(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $property = Property::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'buyer_name'          => 'required|string|max:191',
            'buyer_phone'         => 'required|string|max:50',
            'enquiry_type'        => 'nullable|string|in:visit_request,more_info,make_offer',
            'preferred_date'      => 'nullable|string',
            'preferred_time_slot' => 'nullable|string',
            'offered_price'       => 'nullable|numeric',
            'message'             => 'nullable|string',
        ]);

        $enquiryType = $validated['enquiry_type'] ?? 'more_info';

        $lead = Lead::create([
            'property_id'         => $property->id,
            'buyer_name'          => $validated['buyer_name'],
            'buyer_phone'         => $validated['buyer_phone'],
            'enquiry_type'        => $enquiryType,
            'stage'               => 'new',
            'offered_price'       => $validated['offered_price'] ?? null,
            'message'             => $validated['message'] ?? null,
            'user_id'             => auth()->id(),
        ]);

        $msgTitle = match($enquiryType) {
            'make_offer' => '💰 <b>নতুন দর প্রস্তাব (Make Offer)!</b>',
            'visit_request' => '📅 <b>সাইট ভিজিট রিকোয়েস্ট!</b>',
            default => '❓ <b>প্রপার্টি তথ্য জিজ্ঞাসা (More Info)!</b>'
        };

        $priceNote = !empty($validated['offered_price']) ? "\nপ্রস্তাবিত মূল্য: " . number_format($validated['offered_price']) . " টাকা" : "";
        TelegramService::sendAdminAlert("{$msgTitle}\nপ্রপার্টি: {$property->title}\nগ্রাহক: {$validated['buyer_name']} ({$validated['buyer_phone']}){$priceNote}");

        // Send Push Notification based on enquiry type
        $eventKey = match($enquiryType) {
            'make_offer' => 'adm_fcm_notify_offer',
            'visit_request' => 'adm_fcm_notify_visit_booking',
            default => 'adm_fcm_notify_more_info'
        };
        $cleanTitle = match($enquiryType) {
            'make_offer' => '💰 নতুন দর প্রস্তাব (Offer Price)!',
            'visit_request' => '📅 সাইট ভিজিট রিকোয়েস্ট!',
            default => 'ℹ️ বিস্তারিত তথ্য অনুসন্ধানের অনুরোধ!'
        };
        $body = "{$validated['buyer_name']} ({$validated['buyer_phone']}) #{$property->property_code} প্রপার্টির জন্য রিকোয়েস্ট পাঠিয়েছেন।";
        if (!empty($validated['offered_price'])) {
            $body = "{$validated['buyer_name']} #{$property->property_code} প্রপার্টিতে " . number_format($validated['offered_price']) . " টাকা অফার করেছেন।";
        }
        FcmService::notifyEvent($eventKey, $cleanTitle, $body, route('admin.leads.index'), $property);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'আপনার রিকোয়েস্ট সফলভাবে গ্রহণ করা হয়েছে! আমাদের টিম শীঘ্রই যোগাযোগ করবে।',
                'data'    => ['lead_id' => $lead->id]
            ]);
        }

        return back()->with('success', 'আপনার রিকোয়েস্ট সফলভাবে গ্রহণ করা হয়েছে!');
    }

    public function getAvailableSlots(Request $request): JsonResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $predefinedSlots = [
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
            '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
            '18:00'
        ];

        $disabledSlots = ScheduleSlot::whereBetween('slot_date', [$startDate, $endDate])
            ->where('is_available', false)
            ->get()
            ->groupBy(function ($s) {
                return $s->slot_date instanceof \DateTimeInterface ? $s->slot_date->format('Y-m-d') : (string) $s->slot_date;
            });

        $bookedSlots = VisitBooking::whereBetween('slot_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'approved'])
            ->get()
            ->groupBy(function ($b) {
                return $b->slot_date instanceof \DateTimeInterface ? $b->slot_date->format('Y-m-d') : (string) $b->slot_date;
            });

        $slotsByDate = [];
        $totalDays = (int) date('t', strtotime($startDate));
        $today = now()->format('Y-m-d');

        for ($d = 1; $d <= $totalDays; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            if ($dateStr < $today) continue;

            $dateDisabled = $disabledSlots->get($dateStr)?->pluck('slot_time')->toArray() ?? [];
            $dateBooked = $bookedSlots->get($dateStr)?->pluck('slot_time')->toArray() ?? [];

            $available = [];
            foreach ($predefinedSlots as $slot) {
                if (!in_array($slot, $dateDisabled) && !in_array($slot, $dateBooked)) {
                    $available[] = $slot;
                }
            }

            if (!empty($available)) {
                $slotsByDate[$dateStr] = $available;
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'slots_by_date' => $slotsByDate,
            ],
        ]);
    }

    public function trackWhatsApp(Request $request, string $slug): JsonResponse
    {
        $property = Property::where('slug', $slug)->first();
        if ($property) {
            BuyerInteraction::create([
                'user_id'          => auth()->id(),
                'property_id'      => $property->id,
                'interaction_type' => 'whatsapp_contact',
            ]);

            FcmService::notifyEvent(
                'adm_fcm_notify_whatsapp_click',
                '💬 হোয়াটসঅ্যাপ যোগাযোগ ক্লিক!',
                "একজন ক্রেতা #{$property->property_code} প্রপার্টির জন্য হোয়াটসঅ্যাপ বাটনে ক্লিক করেছেন।",
                route('admin.properties.index', ['search' => $property->property_code]),
                $property
            );
        }

        return response()->json(['success' => true]);
    }

    public function trackLiveChat(Request $request): JsonResponse
    {
        $phone = $request->input('phone');
        $name = $request->input('name') ?: (auth()->check() ? auth()->user()->name : 'ওয়েবসাইট ভিজিটর');
        $message = $request->input('message');
        $propertyCode = $request->input('property_code');
        $property = $propertyCode ? Property::where('property_code', $propertyCode)->first() : null;

        if ($property) {
            BuyerInteraction::create([
                'user_id'          => auth()->id(),
                'property_id'      => $property->id,
                'interaction_type' => 'whatsapp_contact',
            ]);
        }

        if (!empty($phone) || !empty($message)) {
            Lead::create([
                'property_id'  => $property?->id,
                'buyer_name'   => $name,
                'buyer_phone'  => $phone ?: 'WhatsApp Chat',
                'enquiry_type' => 'chat_inquiry',
                'stage'        => 'new',
                'message'      => $message,
                'user_id'      => auth()->id(),
            ]);

            $propInfo = $property ? "\n🏢 প্রপার্টি: #{$property->property_code} - {$property->title}" : "";
            TelegramService::sendAdminAlert("💬 <b>নতুন লাইভ চ্যাট মেসেজ!</b>\nভিজিটর: {$name}\nযোগাযোগ: " . ($phone ?: 'WhatsApp Live Chat') . "{$propInfo}\nমেসেজ: {$message}");
        }

        FcmService::notifyEvent(
            'adm_fcm_notify_whatsapp_click',
            '💬 নতুন লাইভ চ্যাট মেসেজ!',
            "{$name} লাইভ চ্যাটে যোগাযোগ করেছেন।" . ($property ? " (#{$property->property_code})" : ""),
            $property ? route('admin.properties.index', ['search' => $property->property_code]) : route('admin.leads.index')
        );

        return response()->json(['success' => true]);
    }

    public function registerFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|max:500',
        ]);

        FcmDeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id'     => auth()->id(),
                'device_info' => $request->header('User-Agent'),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function submitReview(Request $request, string $slug): JsonResponse
    {
        $property = Property::with('directSources')->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'reviewer_name'  => 'required|string|max:191',
            'reviewer_phone' => 'required|string|max:50',
            'rating'         => 'required|integer|min:1|max:5',
            'review_text'    => 'required|string|max:1000',
            'target_user_id' => 'nullable|integer',
            'target_type'    => 'nullable|string|in:agent,owner',
        ]);

        $primarySource = $property->directSources->first();
        $targetUserId = $validated['target_user_id'] ?? ($primarySource?->source_user_id ?? $property->created_by_user_id ?? 1);
        $targetType = $validated['target_type'] ?? ($primarySource && $primarySource->source_type === 'direct_agent' ? 'agent' : 'owner');

        $review = \App\Models\Review::create([
            'target_user_id' => $targetUserId,
            'target_type'    => $targetType,
            'property_id'    => $property->id,
            'reviewer_name'  => $validated['reviewer_name'],
            'reviewer_phone' => $validated['reviewer_phone'],
            'rating'         => (int) $validated['rating'],
            'review_text'    => $validated['review_text'],
            'status'         => 'approved',
        ]);

        // Recalculate average rating for target user
        if ($targetUserId) {
            $stats = \App\Models\Review::where('target_user_id', $targetUserId)
                ->where('status', 'approved')
                ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg_rating')
                ->first();

            if ($stats && $stats->cnt > 0) {
                \App\Models\User::where('id', $targetUserId)->update([
                    'average_rating' => round((float) $stats->avg_rating, 2),
                    'review_count'   => (int) $stats->cnt,
                ]);
            }
        }

        TelegramService::sendAdminAlert("⭐ <b>নতুন প্রপার্টি ও সোর্স রিভিউ ({$validated['rating']}/5)!</b>\nপ্রপার্টি: {$property->title} (#{$property->property_code})\nরিভিউদাতা: {$validated['reviewer_name']} ({$validated['reviewer_phone']})\nমন্তব্য: {$validated['review_text']}");

        return response()->json([
            'success' => true,
            'message' => 'আপনার মূল্যবান রেটিং ও রিভিউ সফলভাবে যুক্ত হয়েছে!',
            'data'    => [
                'id'            => $review->id,
                'reviewer_name' => $review->reviewer_name,
                'rating'        => $review->rating,
                'review_text'   => $review->review_text,
                'created_at'    => 'এইমাত্র',
            ],
        ]);
    }
}
