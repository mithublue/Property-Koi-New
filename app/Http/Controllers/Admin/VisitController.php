<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSlot;
use App\Models\VisitBooking;
use App\Services\MessageTemplateService;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    const PREDEFINED_SLOTS = [
        '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
        '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
        '18:00'
    ];

    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'schedule');
        $selectedDate = $request->query('date', now()->format('Y-m-d'));
        $bookingStatus = $request->query('status', 'all');

        $calMonth = (int) $request->query('cal_month', now()->month);
        $calYear = (int) $request->query('cal_year', now()->year);
        $calMonth = max(1, min(12, $calMonth));
        $calYear = max(2020, min(2035, $calYear));

        $prevMonth = $calMonth - 1;
        $prevYear = $calYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $calMonth + 1;
        $nextYear = $calYear;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $calMonth, $calYear);
        $firstDow = (int) date('N', mktime(0, 0, 0, $calMonth, 1, $calYear)); // 1=Mon, 7=Sun

        // Slot map for this month: [ 'YYYY-MM-DD' => [ '09:00' => true, ... ] ]
        $existingSlots = ScheduleSlot::whereYear('slot_date', $calYear)
            ->whereMonth('slot_date', $calMonth)
            ->get(['slot_date', 'slot_time', 'is_available']);

        $slotMap = [];
        foreach ($existingSlots as $s) {
            $d = is_string($s->slot_date) ? substr($s->slot_date, 0, 10) : $s->slot_date->format('Y-m-d');
            $slotMap[$d][$s->slot_time] = (bool) $s->is_available;
        }

        // Pending bookings count per date for the calendar
        $pendingPerDate = VisitBooking::where('status', 'pending')
            ->whereYear('slot_date', $calYear)
            ->whereMonth('slot_date', $calMonth)
            ->selectRaw('DATE(slot_date) as s_date, count(*) as cnt')
            ->groupBy('s_date')
            ->pluck('cnt', 's_date')
            ->toArray();

        // Total pending count for badge
        $pendingCount = VisitBooking::where('status', 'pending')->count();

        // Bookings for tab 2
        $bookingsQuery = VisitBooking::with(['property', 'user']);
        if ($bookingStatus !== 'all') {
            $bookingsQuery->where('status', $bookingStatus);
        }

        $bookings = $bookingsQuery->latest()->paginate(20)->withQueryString();

        $stats = [
            'total_bookings' => VisitBooking::count(),
            'pending'        => $pendingCount,
            'approved'       => VisitBooking::where('status', 'approved')->count(),
            'rejected'       => VisitBooking::where('status', 'rejected')->count(),
            'cancelled'      => VisitBooking::where('status', 'cancelled')->count(),
        ];

        $monthBn = ['', 'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

        return view('admin.visits', [
            'activeTab'       => $activeTab,
            'selectedDate'    => $selectedDate,
            'bookingStatus'   => $bookingStatus,
            'calMonth'        => $calMonth,
            'calYear'         => $calYear,
            'prevMonth'       => $prevMonth,
            'prevYear'        => $prevYear,
            'nextMonth'       => $nextMonth,
            'nextYear'        => $nextYear,
            'daysInMonth'     => $daysInMonth,
            'firstDow'        => $firstDow,
            'slotMap'         => $slotMap,
            'pendingPerDate'  => $pendingPerDate,
            'pendingCount'    => $pendingCount,
            'slotTimes'       => self::PREDEFINED_SLOTS,
            'bookings'        => $bookings,
            'stats'           => $stats,
            'monthBn'         => $monthBn,
        ]);
    }

    public function toggleSlot(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|string',
        ]);

        $date = $request->date;
        $time = $request->time;

        $slot = ScheduleSlot::firstOrNew(['slot_date' => $date, 'slot_time' => $time]);
        if ($request->has('is_available')) {
            $slot->is_available = (bool) $request->input('is_available');
        } else {
            $slot->is_available = !$slot->exists ? false : !$slot->is_available;
        }
        $slot->save();

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'is_available' => (bool) $slot->is_available,
                'date'         => $date,
                'time'         => $time,
            ]);
        }

        return back()->with('success', "স্লট {$time} স্ট্যাটাস পরিবর্তন করা হয়েছে।");
    }

    public function saveSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->input('date') ?: $request->input('slot_date');
        $availableTimes = $request->input('available_times', []);
        $slots = $request->input('slots', []);

        foreach (self::PREDEFINED_SLOTS as $time) {
            $isAvail = false;
            if (!empty($availableTimes) && is_array($availableTimes)) {
                $isAvail = in_array($time, $availableTimes);
            } elseif (isset($slots[$time])) {
                $isAvail = (bool) $slots[$time];
            }

            ScheduleSlot::updateOrCreate(
                ['slot_date' => $date, 'slot_time' => $time],
                ['is_available' => $isAvail]
            );
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'স্লট সফলভাবে সেভ হয়েছে!',
                'date'    => $date,
            ]);
        }

        return back()->with('success', "{$date} তারিখের স্লট সফলভাবে সেভ করা হয়েছে।");
    }

    public function batchUpdateSlots(Request $request)
    {
        $request->validate([
            'date'   => 'required|date',
            'action' => 'required|in:open_all,close_all',
        ]);

        $date = $request->date;
        $isAvailable = ($request->action === 'open_all');

        foreach (self::PREDEFINED_SLOTS as $time) {
            ScheduleSlot::updateOrCreate(
                ['slot_date' => $date, 'slot_time' => $time],
                ['is_available' => $isAvailable]
            );
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'is_available' => $isAvailable,
                'date'         => $date,
                'message'      => $isAvailable ? 'সারাদিনের সবগুলো স্লট ওপেন করা হয়েছে।' : 'সারাদিনের সবগুলো স্লট বন্ধ করা হয়েছে।'
            ]);
        }

        return back()->with('success', $isAvailable ? 'সারাদিনের সবগুলো স্লট ওপেন করা হয়েছে।' : 'সারাদিনের সবগুলো স্লট বন্ধ করা হয়েছে।');
    }

    public function updateBookingStatus(Request $request, VisitBooking $booking)
    {
        $status = $request->validate([
            'status' => 'required|in:pending,approved,rejected,cancelled'
        ])['status'];

        $booking->status = $status;
        if ($status === 'approved') {
            $booking->approved_at = now();
            $booking->approved_by = auth()->id();
        }
        $booking->save();

        // If approved, generate WhatsApp confirmation URL
        $whatsappUrl = null;
        if ($status === 'approved' && !empty($booking->buyer_phone)) {
            $msg = MessageTemplateService::render('visit_approved', [
                'buyer_name'    => $booking->buyer_name,
                'property_code' => $booking->property?->property_code ?? '',
                'date'          => is_string($booking->slot_date) ? $booking->slot_date : $booking->slot_date->format('d M, Y'),
                'time'          => $booking->slot_time,
            ]);

            $cleanPhone = preg_replace('/[^0-9]/', '', $booking->buyer_phone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '88' . $cleanPhone;
            }
            $whatsappUrl = 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($msg);
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'status'       => $status,
                'whatsapp_url' => $whatsappUrl,
            ]);
        }

        return back()->with('success', "বুকিং স্ট্যাটাস '{$status}' আপডেট করা হয়েছে।");
    }
}
