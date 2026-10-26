@extends('layouts.admin')

@section('title', 'বায়ার ডিরেক্টরি (Buyers Directory)')
@section('page_title', 'বায়ার ডিরেক্টরি & CRM')

@section('page_badge')
    <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-900 border border-blue-300 flex items-center gap-1.5 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
        {{ $stats['total_buyers'] }} জন নিবন্ধিত ক্রেতা
    </span>
@endsection

@section('content')
<div class="space-y-6" x-data="{ showAddBuyerModal: false }">

    <!-- Top Action & Stats Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">ক্রেতা ও বায়ার ডেটাবেস (Buyer CRM Hub)</h2>
            <p class="text-xs text-slate-500 mt-0.5">আগ্রহী ক্রেতাদের চাহিদা, বাজেট, পছন্দের লোকেশন ও সরাসরি WhatsApp ফলোআপ</p>
        </div>
        <button type="button" @click="showAddBuyerModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition transform active:scale-95">
            <span>➕</span> নতুন বায়ার যুক্ত করুন
        </button>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">মোট বায়ার</div>
            <div class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $stats['total_buyers'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-emerald-600">সক্রিয় চাহিদা (Demands)</div>
            <div class="text-xl font-extrabold text-emerald-700 mt-0.5">{{ $stats['active_demands'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-blue-600">লিড ও ইনকোয়ারি</div>
            <div class="text-xl font-extrabold text-blue-700 mt-0.5">{{ $stats['total_leads'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-purple-600">ভিজিট শিডিউল</div>
            <div class="text-xl font-extrabold text-purple-700 mt-0.5">{{ $stats['visit_bookings'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-amber-600">জমি / প্লট ক্রেতা</div>
            <div class="text-xl font-extrabold text-amber-700 mt-0.5">{{ $stats['land_seekers'] }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-indigo-600">ফ্ল্যাট ক্রেতা</div>
            <div class="text-xl font-extrabold text-indigo-700 mt-0.5">{{ $stats['flat_seekers'] }}</div>
        </div>
    </div>

    <!-- Search & Filter Form -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.buyers.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম, ফোন নম্বর, WhatsApp, ইমেইল বা পছন্দের ব্লক..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>
            <div>
                <select name="type" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="all">পছন্দের ধরন: সকল (All)</option>
                    <option value="land" {{ request('type') === 'land' ? 'selected' : '' }}>জমি / প্লট</option>
                    <option value="flat" {{ request('type') === 'flat' ? 'selected' : '' }}>রেডি ফ্ল্যাট</option>
                    <option value="commercial" {{ request('type') === 'commercial' ? 'selected' : '' }}>কমার্শিয়াল স্পেস</option>
                    <option value="land_share" {{ request('type') === 'land_share' ? 'selected' : '' }}>ল্যান্ড শেয়ার</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition">
                    অনুসন্ধান করুন
                </button>
                @if(request()->hasAny(['search', 'type']))
                    <a href="{{ route('admin.buyers.index') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-center" title="রিসেট">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Buyers Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-4">বায়ারের বিবরণ</th>
                        <th class="p-4">পছন্দের ধরন &amp; লোকেশন</th>
                        <th class="p-4">বাজেট ও ক্রয়ের সময়সীমা</th>
                        <th class="p-4 text-center">চাহিদা ও কার্যকলাপ</th>
                        <th class="p-4">হোয়াটসঅ্যাপ চ্যাট</th>
                        <th class="p-4 text-right">ম্যানেজ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($buyers as $buyer)
                        @php
                            $cleanPhone = preg_replace('/[^0-9]/', '', $buyer->whatsapp_number ?: $buyer->phone);
                            $waPhone = str_starts_with($cleanPhone, '0') ? ('88' . $cleanPhone) : $cleanPhone;
                            $latestDemand = $buyer->buyerDemands->first();
                            $waMessage = "আসসালামু আলাইকুম {$buyer->name}, PropertyKoi থেকে আপনার চাহিদার প্রপার্টি বিষয়ে যোগাযোগ করছি। আপনার কাঙ্ক্ষিত বাজেটে কিছু চমৎকার অপশন এসেছে। আপনি কি বিস্তারিত দেখতে চান?";
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition group">
                            <!-- Buyer Details -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        {{ substr($buyer->name, 0, 1) }}
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="font-bold text-slate-900 text-sm">
                                            {{ $buyer->name }}
                                        </div>
                                        <div class="font-mono text-slate-600 text-[11px] flex items-center gap-1.5">
                                            <span>📞</span> {{ $buyer->phone }}
                                        </div>
                                        @if($buyer->email && !str_ends_with($buyer->email, '@propertykoi.local'))
                                            <div class="text-slate-400 text-[10px]">
                                                ✉️ {{ $buyer->email }}
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-slate-400">
                                            যোগদান: {{ $buyer->created_at->format('d M, Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Preferences -->
                            <td class="p-4">
                                <div class="space-y-1">
                                    <div>
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-slate-100 text-slate-800">
                                            @if($buyer->preferred_type === 'land') 🏞️ জমি / প্লট
                                            @elseif($buyer->preferred_type === 'flat') 🏢 রেডি ফ্ল্যাট
                                            @elseif($buyer->preferred_type === 'commercial') 🏬 কমার্শিয়াল
                                            @elseif($buyer->preferred_type === 'land_share') 🤝 ল্যান্ড শেয়ার
                                            @else 🎯 যেকোনো প্রপার্টি @endif
                                        </span>
                                    </div>
                                    @if($buyer->preferred_blocks)
                                        <div class="text-[11px] text-slate-600">
                                            ব্লক: <strong class="text-slate-800">{{ $buyer->preferred_blocks }}</strong>
                                        </div>
                                    @endif
                                    @if($latestDemand && !empty($latestDemand->preferred_areas))
                                        <div class="text-[10px] text-slate-500">
                                            এলাকা: {{ implode(', ', (array)$latestDemand->preferred_areas) }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Budget & Timeline -->
                            <td class="p-4">
                                <div class="space-y-1">
                                    <div class="font-semibold text-emerald-700 font-mono text-[11px]">
                                        {{ $buyer->budget_range ?: ($latestDemand ? ($latestDemand->min_budget ? number_format($latestDemand->min_budget/100000, 1) . '-' . number_format($latestDemand->max_budget/100000, 1) . ' লাখ' : 'নির্দিষ্ট নেই') : 'বাজেট উল্লেখ নেই') }}
                                    </div>
                                    @if($latestDemand && $latestDemand->purchase_timeline)
                                        <div class="text-[10px] text-slate-500">
                                            সময়সীমা: <span class="capitalize">{{ $latestDemand->purchase_timeline }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Demands & Activities -->
                            <td class="p-4 text-center">
                                <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.demands.index', ['search' => $buyer->phone]) }}" class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold hover:bg-emerald-100 transition" title="চাহিদা সমূহ">
                                        📋 {{ $buyer->buyerDemands->count() }} চাহিদা
                                    </a>
                                    <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold" title="লিড ইনকোয়ারি">
                                        🎯 {{ $buyer->leads->count() }} লিড
                                    </span>
                                    <span class="px-2 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold" title="সাইট ভিজিট">
                                        📅 {{ $buyer->visitBookings->count() }} ভিজিট
                                    </span>
                                </div>
                            </td>

                            <!-- WhatsApp 1-Click Followup -->
                            <td class="p-4">
                                <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode($waMessage) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-xs transition transform active:scale-95 whitespace-nowrap">
                                    <span>💬</span> WhatsApp চ্যাট
                                </a>
                            </td>

                            <!-- Delete / Manage -->
                            <td class="p-4 text-right">
                                <form action="{{ route('admin.buyers.destroy', $buyer) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে বায়ার {{ $buyer->name }}-এর রেকর্ড মুছে ফেলতে চান?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white rounded-xl text-xs font-bold transition border border-rose-200 shadow-xs inline-flex items-center gap-1" title="বায়ার মুছুন">
                                        <span>🗑️ মুছুন</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-500">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="text-4xl">👤</div>
                                    <div class="text-base font-bold text-slate-800">কোনো বায়ার পাওয়া যায়নি</div>
                                    <p class="text-xs text-slate-500">
                                        উপরে "নতুন বায়ার যুক্ত করুন" বোতামে ক্লিক করে নতুন ক্রেতার তথ্য ও প্রপার্টি চাহিদা সংরক্ষণ করুন।
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($buyers->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $buyers->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Add New Buyer -->
    <div x-show="showAddBuyerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto" @click.away="showAddBuyerModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span>👤</span> নতুন বায়ার ও চাহিদা যুক্ত করুন
                </h3>
                <button type="button" @click="showAddBuyerModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
            </div>

            <form action="{{ route('admin.buyers.store') }}" method="POST" class="space-y-4 mt-4 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">বায়ারের পুরো নাম *</label>
                        <input type="text" name="name" required placeholder="যেমন: তানভীর আহমেদ" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">মোবাইল নম্বর *</label>
                        <input type="text" name="phone" required placeholder="017XXXXXXXX" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">WhatsApp নম্বর</label>
                        <input type="text" name="whatsapp_number" placeholder="খালি রাখলে ফোন নম্বর ব্যবহৃত হবে" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ইমেইল (ঐচ্ছিক)</label>
                        <input type="email" name="email" placeholder="buyer@example.com" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">কাঙ্ক্ষিত প্রপার্টির ধরন</label>
                        <select name="preferred_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="land">জমি / প্লট (Land)</option>
                            <option value="flat">রেডি ফ্ল্যাট (Flat)</option>
                            <option value="commercial">কমার্শিয়াল স্পেস (Commercial)</option>
                            <option value="land_share">ল্যান্ড শেয়ার (Share)</option>
                            <option value="any">যেকোনো ধরন</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">পছন্দের এলাকা (Areas)</label>
                        <input type="text" name="preferred_areas" value="Aftabnagar" placeholder="যেমন: Aftabnagar, Bashundhara" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">পছন্দের ব্লক (Blocks)</label>
                        <input type="text" name="preferred_blocks" placeholder="যেমন: A, B, C, D" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">বাজেট টেক্সট (যেমন: ১ - ১.৫ কোটি)</label>
                        <input type="text" name="budget_range" placeholder="যেমন: ১ কোটি ২০ লাখ" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ন্যূনতম বাজেট (টাকায়)</label>
                        <input type="number" name="min_budget" placeholder="যেমন: 5000000" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">সর্বোচ্চ বাজেট (টাকায়)</label>
                        <input type="number" name="max_budget" placeholder="যেমন: 15000000" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">অভ্যন্তরীণ নোট বা বিশেষ রিকোয়ারমেন্ট</label>
                    <textarea name="notes" rows="2" placeholder="বায়ারের বিশেষ কোনো শর্ত বা চাহিদা থাকলে এখানে লিখুন..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showAddBuyerModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl">
                        বাতিল
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm transition">
                        বায়ার সেভ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
