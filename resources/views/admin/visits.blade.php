@extends('layouts.admin')

@section('title', 'সাইট ভিজিট ও শিডিউলার')
@section('page_title', 'সাইট ভিজিট ও শিডিউলার (Visits & Scheduler)')

@section('content')
<div class="space-y-6" x-data="visitScheduleManager()">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 m-0 flex items-center gap-2">
                <span>📅</span> সাইট ভিজিট শিডিউলার
            </h1>
            <p class="text-sm text-slate-500 mt-1">ভিজিট স্লট কনফিগার করুন এবং বুকিং অনুমোদন করুন</p>
        </div>
        @if($pendingCount > 0)
            <div class="px-3.5 py-1.5 bg-amber-50 text-amber-800 text-xs font-bold rounded-full border border-amber-300 animate-pulse flex items-center gap-1.5 self-start sm:self-center shadow-sm">
                <span>⏳</span> <span>{{ $pendingCount }}টি অনুমোদন বাকি</span>
            </div>
        @endif
    </div>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-2 bg-slate-200/70 p-1.5 rounded-2xl w-fit">
        <button type="button"
                @click="activeTab = 'schedule'"
                :class="activeTab === 'schedule' ? 'bg-white shadow-sm text-slate-900 font-extrabold' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-5 py-2 rounded-xl text-xs transition flex items-center gap-2">
            <span>📆</span> শিডিউল ম্যানেজার
        </button>
        <button type="button"
                @click="activeTab = 'bookings'"
                :class="activeTab === 'bookings' ? 'bg-white shadow-sm text-slate-900 font-extrabold' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-5 py-2 rounded-xl text-xs transition flex items-center gap-2">
            <span>📋</span> বুকিং ও অনুমোদন
            @if($pendingCount > 0)
                <span class="bg-amber-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full">{{ $pendingCount }}</span>
            @endif
        </button>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- TAB 1: SCHEDULE MANAGER (Calendar + Slot Drawer) -->
    <!-- ══════════════════════════════════════════════ -->
    <div x-show="activeTab === 'schedule'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start" x-cloak>

        <!-- Left Column: Full Month Interactive Calendar (7 cols out of 12) -->
        <div class="lg:col-span-7 xl:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <!-- Calendar Header with Month/Year Switcher -->
            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                <a href="{{ route('admin.visits.index', ['tab' => 'schedule', 'cal_month' => $prevMonth, 'cal_year' => $prevYear]) }}"
                   class="p-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-95 transition text-sm font-bold flex items-center gap-1">
                    <span>←</span>
                </a>
                <h2 class="font-bold text-base md:text-lg m-0 flex items-center gap-2">
                    <span>{{ $monthBn[$calMonth] }} {{ $calYear }}</span>
                </h2>
                <a href="{{ route('admin.visits.index', ['tab' => 'schedule', 'cal_month' => $nextMonth, 'cal_year' => $nextYear]) }}"
                   class="p-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-95 transition text-sm font-bold flex items-center gap-1">
                    <span>→</span>
                </a>
            </div>

            <!-- Day Headers (Mon - Sun) -->
            <div class="grid grid-cols-7 text-center text-[11px] font-black text-slate-600 uppercase bg-slate-50 border-b border-slate-200">
                @foreach (['সো', 'মঙ্গ', 'বু', 'বৃহ', 'শু', 'শনি', 'রবি'] as $day)
                    <div class="py-2.5">{{ $day }}</div>
                @endforeach
            </div>

            <!-- Calendar Days Grid -->
            <div class="grid grid-cols-7 divide-x divide-y divide-slate-100 flex-1">
                {{-- Leading blanks before first day of month (Mon=1 ... Sun=7) --}}
                @for ($b = 1; $b < $firstDow; $b++)
                    <div class="min-h-[4.5rem] bg-slate-50/40"></div>
                @endfor

                @php
                    $todayStr = now()->format('Y-m-d');
                @endphp

                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dateStr = sprintf('%04d-%02d-%02d', $calYear, $calMonth, $day);
                        $isPast = $dateStr < $todayStr;
                        $isToday = $dateStr === $todayStr;
                        $pendCnt = $pendingPerDate[$dateStr] ?? 0;
                    @endphp

                    <div @click="selectDate('{{ $dateStr }}')"
                         :class="{
                             'ring-2 ring-slate-900 bg-white shadow-md z-10': selectedDate === '{{ $dateStr }}',
                             'bg-emerald-50/70 hover:bg-emerald-100/90 cursor-pointer': getAvailCount('{{ $dateStr }}') > 0 && selectedDate !== '{{ $dateStr }}',
                             'hover:bg-slate-50 cursor-pointer': getAvailCount('{{ $dateStr }}') === 0 && selectedDate !== '{{ $dateStr }}',
                             'opacity-80': {{ $isPast ? 'true' : 'false' }}
                         }"
                         class="min-h-[4.5rem] sm:min-h-[5rem] p-1.5 sm:p-2 text-xs transition relative select-none flex flex-col justify-between">

                        <!-- Date Number & Top Badges -->
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs {{ $isToday ? 'bg-slate-900 text-white w-6 h-6 rounded-full inline-flex items-center justify-center shadow-sm' : ($isPast ? 'text-slate-400' : 'text-slate-800') }}">
                                {{ $day }}
                            </span>

                            @if($pendCnt > 0)
                                <span class="bg-amber-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full shadow-sm" title="{{ $pendCnt }}টি পেন্ডিং বুকিং">
                                    {{ $pendCnt }}
                                </span>
                            @endif
                        </div>

                        <!-- Configured Slots Indicator Badge -->
                        <div class="mt-auto pt-1">
                            <div x-show="getAvailCount('{{ $dateStr }}') > 0" class="inline-flex items-center gap-1 text-[10px] text-emerald-800 font-bold bg-emerald-100/80 px-1.5 py-0.5 rounded-md">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                <span x-text="getAvailCount('{{ $dateStr }}') + 'টি'"></span>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

            <!-- Legend Bar -->
            <div class="p-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center gap-4 text-xs text-slate-600 font-medium">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-emerald-100 border border-emerald-400 rounded inline-block"></span>
                    <span>স্লট কনফিগার করা আছে</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-amber-400 rounded-full inline-block"></span>
                    <span>Pending বুকিং আছে</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-slate-200 border border-slate-300 rounded inline-block"></span>
                    <span>অতীত / কনফিগার নেই</span>
                </span>
            </div>
        </div>

        <!-- Right Column: Selected Date Slot Editor Panel (5 cols out of 12) -->
        <div class="lg:col-span-5 xl:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between lg:sticky lg:top-6">

            <!-- When NO Date Selected -->
            <template x-if="!selectedDate">
                <div class="flex flex-col items-center justify-center py-20 text-slate-400 text-center gap-3">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-3xl">
                        📅
                    </div>
                    <p class="font-bold text-slate-700 text-base m-0">একটি তারিখ বেছে নিন</p>
                    <p class="text-xs text-slate-500 max-w-xs m-0">বাম দিকের ক্যালেন্ডার থেকে যে দিনের স্লট সেট করতে চান সেটায় ক্লিক করুন।</p>
                </div>
            </template>

            <!-- When Date Selected -->
            <template x-if="selectedDate">
                <div class="space-y-4">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">স্লট সম্পাদনা করুন</span>
                            <h3 class="font-bold text-slate-900 text-base md:text-lg m-0" x-text="selectedDate"></h3>
                        </div>
                        <button type="button" @click="selectedDate = null" class="text-slate-400 hover:text-slate-700 text-xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition">
                            ✕
                        </button>
                    </div>

                    <p class="text-xs text-slate-500 m-0 font-medium">
                        <span class="inline-block w-2.5 h-2.5 bg-emerald-600 rounded-sm align-middle mr-1"></span>
                        সবুজ = Available (ক্লিক করে toggle করুন)
                    </p>

                    <!-- 19 Slots 3-Column Grid -->
                    <div class="grid grid-cols-3 gap-2 py-1">
                        @foreach ($slotTimes as $t)
                            <button type="button"
                                    @click="toggleSlot('{{ $t }}')"
                                    :class="slotsForDate['{{ $t }}']
                                        ? 'bg-emerald-600 text-white border-emerald-700 shadow-sm ring-1 ring-emerald-300'
                                        : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    class="py-2.5 rounded-xl border text-xs font-bold transition text-center select-none active:scale-95">
                                {{ $t }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Quick Batch Actions: সব চালু / সব বন্ধ -->
                    <div class="flex gap-2 pt-2">
                        <button type="button"
                                @click="setAllSlots(true)"
                                class="flex-1 py-2 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-xs font-bold rounded-xl border border-slate-200 hover:border-emerald-300 transition text-center">
                            সব চালু
                        </button>
                        <button type="button"
                                @click="setAllSlots(false)"
                                class="flex-1 py-2 bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-700 text-xs font-bold rounded-xl border border-slate-200 hover:border-rose-300 transition text-center">
                            সব বন্ধ
                        </button>
                    </div>

                    <!-- Save Button -->
                    <button type="button"
                            @click="saveSlots()"
                            :disabled="savingSlots"
                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-60 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                        <template x-if="savingSlots">
                            <span class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                সেভ হচ্ছে...
                            </span>
                        </template>
                        <template x-if="!savingSlots">
                            <span>💾 স্লট সেভ করুন</span>
                        </template>
                    </button>

                    <!-- Feedback Message -->
                    <div x-show="saveMsg"
                         x-transition
                         class="p-2.5 rounded-xl text-center text-xs font-bold"
                         :class="saveStatus === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'"
                         x-text="saveMsg"
                         style="display: none;">
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- TAB 2: BOOKINGS & APPROVALS                    -->
    <!-- ══════════════════════════════════════════════ -->
    <div x-show="activeTab === 'bookings'" class="space-y-4" x-cloak>
        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.visits.index', ['tab' => 'bookings', 'status' => 'all']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition {{ $bookingStatus === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-slate-300' }}">
                    সব ({{ $stats['total_bookings'] }})
                </a>
                <a href="{{ route('admin.visits.index', ['tab' => 'bookings', 'status' => 'pending']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition {{ $bookingStatus === 'pending' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}">
                    ⏳ পেন্ডিং ({{ $stats['pending'] }})
                </a>
                <a href="{{ route('admin.visits.index', ['tab' => 'bookings', 'status' => 'approved']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition {{ $bookingStatus === 'approved' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                    ✅ অনুমোদিত ({{ $stats['approved'] }})
                </a>
                <a href="{{ route('admin.visits.index', ['tab' => 'bookings', 'status' => 'rejected']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold border transition {{ $bookingStatus === 'rejected' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100' }}">
                    ❌ বাতিল ({{ $stats['rejected'] }})
                </a>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                            <th class="p-4">তারিখ ও সময়</th>
                            <th class="p-4">গ্রাহকের বিবরণ</th>
                            <th class="p-4">প্রপার্টি</th>
                            <th class="p-4">স্ট্যাটাস</th>
                            <th class="p-4 text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bookings as $booking)
                            <tr class="hover:bg-slate-50/70 transition" x-data="{
                                status: '{{ $booking->status }}',
                                loading: false,
                                waUrl: '{{ !empty($booking->buyer_phone) ? 'https://wa.me/88' . preg_replace('/[^0-9]/', '', $booking->buyer_phone) : '#' }}',
                                async updateBooking(newStatus) {
                                    if (!confirm(`এই বুকিংটি ${newStatus === 'approved' ? 'অনুমোদন' : 'বাতিল'} করতে চান?`)) return;
                                    this.loading = true;
                                    try {
                                        const res = await fetch('{{ route('admin.visits.booking-status', $booking) }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({ status: newStatus })
                                        });
                                        const data = await res.json();
                                        if (data.success) {
                                            this.status = newStatus;
                                            if (data.whatsapp_url) this.waUrl = data.whatsapp_url;
                                            if (window.showToast) window.showToast(`বুকিং #${ '{{ $booking->id }}' } সফলভাবে আপডেট হয়েছে!`, 'success');
                                        } else {
                                            alert(data.message || 'বুকিং আপডেট ব্যর্থ হয়েছে।');
                                        }
                                    } catch(e) {
                                        alert('সার্ভার এরর');
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }">
                                <td class="p-4 font-bold text-slate-900">
                                    <div>📅 {{ is_string($booking->slot_date) ? \Carbon\Carbon::parse($booking->slot_date)->format('d M, Y') : $booking->slot_date->format('d M, Y') }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">⏰ {{ $booking->slot_time }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900">{{ $booking->buyer_name }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">📞 {{ $booking->buyer_phone }}</div>
                                    @if(!empty($booking->message))
                                        <div class="text-[11px] text-slate-500 italic mt-1 bg-slate-50 p-1.5 rounded border border-slate-100">
                                            "{{ Str::limit($booking->message, 80) }}"
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4">
                                    @if($booking->property)
                                        <a href="{{ route('property.show', $booking->property->slug) }}" target="_blank" class="font-bold text-slate-900 hover:text-emerald-600 transition">
                                            {{ $booking->property->title }}
                                        </a>
                                        <div class="text-[11px] text-emerald-700 font-mono mt-0.5 font-bold">#{{ $booking->property->property_code }}</div>
                                    @else
                                        <span class="text-slate-400 font-medium">সাধারণ সাইট ভিজিট</span>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full font-bold text-[10px] tracking-wide uppercase transition"
                                          :class="{
                                              'bg-emerald-100 text-emerald-800 border border-emerald-300': status === 'approved',
                                              'bg-amber-100 text-amber-800 border border-amber-300': status === 'pending',
                                              'bg-rose-100 text-rose-800 border border-rose-300': status === 'rejected',
                                              'bg-slate-100 text-slate-700 border border-slate-300': status === 'cancelled'
                                          }"
                                          x-text="status">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <template x-if="status === 'pending'">
                                            <div class="flex items-center gap-1.5">
                                                <button type="button"
                                                        @click="updateBooking('approved')"
                                                        :disabled="loading"
                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl text-xs transition shadow-sm">
                                                    অনুমোদন
                                                </button>
                                                <button type="button"
                                                        @click="updateBooking('rejected')"
                                                        :disabled="loading"
                                                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-700 font-bold rounded-xl text-xs transition border border-rose-200">
                                                    বাতিল
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="status !== 'pending'">
                                            <a :href="waUrl" target="_blank" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-xl text-xs transition flex items-center gap-1.5 border border-emerald-200 shadow-sm">
                                                <span>💬</span> হোয়াটসঅ্যাপ
                                            </a>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center text-xs text-slate-400">
                                    কোনো বুকিং রেকর্ড পাওয়া যায়নি।
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($bookings->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function visitScheduleManager() {
    return {
        activeTab: '{{ $activeTab }}',
        selectedDate: '{{ $selectedDate }}',
        slotsForDate: {},
        slotMap: @json($slotMap),
        allSlotTimes: @json($slotTimes),
        savingSlots: false,
        saveMsg: '',
        saveStatus: 'success',

        init() {
            if (this.selectedDate) {
                this.selectDate(this.selectedDate);
            }
        },

        getAvailCount(dateStr) {
            const s = this.slotMap[dateStr];
            if (!s) return 0;
            return Object.values(s).filter(Boolean).length;
        },

        selectDate(dateStr) {
            this.selectedDate = dateStr;
            this.saveMsg = '';

            const daySlots = this.slotMap[dateStr] || {};
            const slots = {};
            this.allSlotTimes.forEach(t => {
                // If not set yet in DB, default to false (or true if needed)
                slots[t] = Boolean(daySlots[t]);
            });
            this.slotsForDate = slots;
        },

        toggleSlot(time) {
            this.slotsForDate[time] = !this.slotsForDate[time];
        },

        setAllSlots(status) {
            this.allSlotTimes.forEach(t => {
                this.slotsForDate[t] = Boolean(status);
            });
        },

        async saveSlots() {
            if (!this.selectedDate) return;
            this.savingSlots = true;
            this.saveMsg = '';

            const availableTimes = Object.entries(this.slotsForDate)
                .filter(([time, isAvail]) => isAvail)
                .map(([time]) => time);

            try {
                const res = await fetch('{{ route('admin.visits.save-slots') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        date: this.selectedDate,
                        available_times: availableTimes,
                        slots: this.slotsForDate
                    })
                });

                const data = await res.json();
                if (data.success) {
                    this.saveStatus = 'success';
                    this.saveMsg = '✓ ' + (data.message || 'সফলভাবে সেভ হয়েছে!');
                    // Update the reactive slotMap so calendar badge updates immediately
                    this.slotMap[this.selectedDate] = { ...this.slotsForDate };

                    if (window.showToast) {
                        window.showToast(`${this.selectedDate} তারিখের স্লট সেভ হয়েছে!`, 'success');
                    }
                } else {
                    this.saveStatus = 'error';
                    this.saveMsg = '✗ ' + (data.message || 'সেভ করতে সমস্যা হয়েছে।');
                }
            } catch(e) {
                this.saveStatus = 'error';
                this.saveMsg = '✗ সার্ভার সংযোগে ত্রুটি।';
            } finally {
                this.savingSlots = false;
                setTimeout(() => {
                    this.saveMsg = '';
                }, 3500);
            }
        }
    }
}
</script>
@endpush
