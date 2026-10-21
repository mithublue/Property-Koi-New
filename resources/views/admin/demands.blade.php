@extends('layouts.admin')

@section('title', 'প্রপার্টি চাহিদা ও রিকোয়ারমেন্ট')
@section('page_title', 'প্রপার্টি চাহিদা ও বায়ার রিকোয়ারমেন্ট (Buyer Demands & Matching)')

@section('content')
<div class="space-y-6" x-data="demandManager()">

    <!-- Top Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">মোট চাহিদা</div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ $stats['total_demands'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg">
                📋
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">ইউনিক বায়ার/গ্রাহক</div>
                <div class="text-xl sm:text-2xl font-black text-emerald-600 mt-0.5">{{ $stats['unique_submitters'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                👥
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">টেলিগ্রাম কানেক্টেড</div>
                <div class="text-xl sm:text-2xl font-black text-sky-600 mt-0.5">{{ $stats['telegram_connected'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg">
                ✈️
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">ম্যাচ হওয়া চাহিদা</div>
                <div class="text-xl sm:text-2xl font-black text-indigo-600 mt-0.5">{{ $stats['matched_demands'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                🎯
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between col-span-2 lg:col-span-1">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">সক্রিয় রিকোয়ারমেন্ট</div>
                <div class="text-xl sm:text-2xl font-black text-amber-600 mt-0.5">{{ $stats['active_demands'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                ⚡
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.demands.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 text-xs">
            
            <!-- Search Text -->
            <div class="lg:col-span-4">
                <label class="block text-[11px] font-bold text-slate-600 mb-1">সার্চ করুন (কোড, নাম, ফোন/হোয়াটসঅ্যাপ বা ইমেইল)</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $filters['search'] }}" 
                           placeholder="চাহিদা কোড (#REQ), নাম, ফোন বা ইমেইল..." 
                           class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                </div>
            </div>

            <!-- Property Type -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-600 mb-1">প্রপার্টি ধরন</label>
                <select name="property_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="all">সব ধরন (All)</option>
                    <option value="land" {{ $filters['property_type'] === 'land' ? 'selected' : '' }}>🌱 জমি / প্লট</option>
                    <option value="flat" {{ $filters['property_type'] === 'flat' ? 'selected' : '' }}>🏢 ফ্ল্যাট</option>
                    <option value="land_share" {{ $filters['property_type'] === 'land_share' ? 'selected' : '' }}>🤝 ল্যান্ড শেয়ার</option>
                    <option value="commercial" {{ $filters['property_type'] === 'commercial' ? 'selected' : '' }}>🏬 কমার্শিয়াল</option>
                </select>
            </div>

            <!-- Status -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-600 mb-1">স্ট্যাটাস</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="all">সব স্ট্যাটাস</option>
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>সক্রিয় (Active)</option>
                    <option value="matched" {{ $filters['status'] === 'matched' ? 'selected' : '' }}>ম্যাচ পাওয়া (Matched)</option>
                    <option value="closed" {{ $filters['status'] === 'closed' ? 'selected' : '' }}>সমাপ্ত (Closed)</option>
                </select>
            </div>

            <!-- Telegram Connection -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-600 mb-1">টেলিগ্রাম সংযোগ</label>
                <select name="telegram" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="all">সকল গ্রাহক</option>
                    <option value="connected" {{ $filters['telegram'] === 'connected' ? 'selected' : '' }}>✅ টেলিগ্রাম কানেক্টেড</option>
                    <option value="not_connected" {{ $filters['telegram'] === 'not_connected' ? 'selected' : '' }}>⏳ এখনও কানেক্টেড নয়</option>
                </select>
            </div>

            <!-- Submit / Reset Buttons -->
            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition cursor-pointer text-center">
                    ফিল্টার
                </button>
                <a href="{{ route('admin.demands.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition text-center" title="রিসেট">
                    🔄
                </a>
            </div>
        </form>
    </div>

    <!-- Submitters & Demands Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-3.5">গ্রাহক / সাবমিটার</th>
                        <th class="p-3.5">যোগাযোগের বিবরণ</th>
                        <th class="p-3.5">টেলিগ্রাম অ্যালার্ট</th>
                        <th class="p-3.5 text-center">মোট চাহিদা</th>
                        <th class="p-3.5">সর্বশেষ রিকোয়ারমেন্ট</th>
                        <th class="p-3.5 text-center">মোট ম্যাচ</th>
                        <th class="p-3.5 text-center">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($submitters as $sub)
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" @click="openDrawer({{ json_encode($sub) }})">
                            <!-- Submitter Profile -->
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                                        {{ mb_substr($sub['name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm hover:text-emerald-700 transition">
                                            {{ $sub['name'] }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            প্রথম চাহিদা: {{ $sub['first_seen'] ? \Carbon\Carbon::parse($sub['first_seen'])->format('d M, Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Contact (WhatsApp, Phone, Email) -->
                            <td class="p-3.5" @click.stop>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <a href="tel:{{ $sub['phone'] }}" class="font-semibold text-slate-800 hover:text-emerald-600 flex items-center gap-1">
                                            <span>📞</span> {{ $sub['phone'] }}
                                        </a>
                                        @if($sub['whatsapp_url'])
                                            <a href="{{ $sub['whatsapp_url'] }}" target="_blank" rel="noopener" 
                                               class="px-2 py-0.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold rounded-md text-[10px] inline-flex items-center gap-1 transition"
                                               title="হোয়াটসঅ্যাপে চ্যাট করুন">
                                                <span>💬 WhatsApp</span>
                                            </a>
                                        @endif
                                    </div>
                                    @if($sub['email'])
                                        <div class="text-[11px] text-slate-500 flex items-center gap-1">
                                            <span>✉️</span> <a href="mailto:{{ $sub['email'] }}" class="hover:underline">{{ $sub['email'] }}</a>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Telegram Status -->
                            <td class="p-3.5">
                                @if($sub['telegram_connected'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                        <span>কানেক্টেড</span>
                                        @if($sub['telegram_username'])
                                            <span class="font-mono text-[10px] text-sky-600">({{ '@' . $sub['telegram_username'] }})</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>পেন্ডিং</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Total Demands Submitted -->
                            <td class="p-3.5 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-xl font-bold text-xs bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ $sub['demands_count'] }} টি
                                </span>
                            </td>

                            <!-- Latest Requirement -->
                            <td class="p-3.5">
                                @if($sub['latest_demand'])
                                    @php 
                                        $ld = $sub['latest_demand']; 
                                        $typeIcon = match($ld->property_type) {
                                            'land' => '🌱',
                                            'flat' => '🏢',
                                            'land_share' => '🤝',
                                            'commercial' => '🏬',
                                            default => '📌'
                                        };
                                        $areaStr = is_array($ld->preferred_areas) ? implode(', ', $ld->preferred_areas) : ($ld->preferred_areas ?: 'আফতাবনগর');
                                    @endphp
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 text-[11px]">
                                            #{{ $ld->requirement_code }}
                                        </span>
                                        <span class="text-xs font-semibold text-slate-800">
                                            {{ $typeIcon }} {{ ucfirst($ld->property_type) }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        📍 {{ $areaStr }}
                                        @if($ld->min_budget || $ld->max_budget)
                                            • ৳ {{ $ld->min_budget ? number_format($ld->min_budget / 100000, 1) : '০' }} - {{ $ld->max_budget ? number_format($ld->max_budget / 100000, 1) : '∞' }} লাখ
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Total Matches -->
                            <td class="p-3.5 text-center">
                                @if($sub['total_matches'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-black text-xs bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <span>🎯</span> {{ $sub['total_matches'] }}টি ম্যাচ
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">ম্যাচিং অপেক্ষমান</span>
                                @endif
                            </td>

                            <!-- Action Button -->
                            <td class="p-3.5 text-center" @click.stop>
                                <button type="button" @click="openDrawer({{ json_encode($sub) }})" 
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-xs flex items-center gap-1 mx-auto cursor-pointer">
                                    <span>চাহিদা দেখুন</span> &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-400">
                                <div class="text-3xl mb-2">📋</div>
                                <div class="font-semibold text-slate-600">কোনো গ্রাহকের প্রপার্টি চাহিদা পাওয়া যায়নি।</div>
                                <div class="text-xs text-slate-400 mt-1">ফিল্টারের শর্ত পরিবর্তন করে পুনরায় চেষ্টা করুন।</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submitters->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $submitters->links() }}
            </div>
        @endif
    </div>

    <!-- ======================================================== -->
    <!-- RIGHT SIDE SLIDE-OVER DRAWER (CHAHIDA ACCORDIONS & MATCHES) -->
    <!-- ======================================================== -->
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex justify-end"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="w-full max-w-2xl bg-white h-full shadow-2xl flex flex-col justify-between"
             @click.away="drawerOpen = false"
             x-transition:enter="transform transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">

            <!-- Drawer Header: Submitter Profile -->
            <div class="p-5 sm:p-6 bg-slate-900 text-white flex items-start justify-between border-b border-slate-800 shrink-0">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center font-black text-lg shadow-inner">
                        <span x-text="activeSubmitter?.name ? activeSubmitter.name.charAt(0) : 'U'"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-black text-white m-0" x-text="activeSubmitter?.name || 'গ্রাহকের নাম'"></h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                বায়ার প্রোফাইল
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-300 mt-1 flex-wrap">
                            <span class="flex items-center gap-1">
                                📞 <span x-text="activeSubmitter?.phone"></span>
                            </span>
                            <template x-if="activeSubmitter?.email">
                                <span class="flex items-center gap-1 text-slate-400">
                                    ✉️ <span x-text="activeSubmitter?.email"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <button @click="drawerOpen = false" class="text-slate-400 hover:text-white text-2xl font-bold p-1 leading-none transition cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- Quick Action Toolbar for Submitter -->
            <div class="px-6 py-3 bg-slate-800 text-white flex items-center justify-between gap-3 text-xs border-b border-slate-700/80 shrink-0 flex-wrap">
                <div class="flex items-center gap-2">
                    <!-- WhatsApp -->
                    <template x-if="activeSubmitter?.whatsapp_url">
                        <a :href="activeSubmitter.whatsapp_url" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition shadow-xs">
                            <span>💬 WhatsApp চ্যাট</span>
                        </a>
                    </template>
                    <!-- Call -->
                    <template x-if="activeSubmitter?.phone">
                        <a :href="'tel:' + activeSubmitter.phone" 
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 font-semibold transition">
                            <span>📞 সরাসরি কল</span>
                        </a>
                    </template>
                </div>

                <!-- Telegram connection status badge in drawer -->
                <div>
                    <template x-if="activeSubmitter?.telegram_connected">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-sky-950 text-sky-300 border border-sky-700 text-[11px] font-bold">
                            <span>✈️ টেলিগ্রাম সক্রিয়</span>
                            <span class="font-mono text-[10px]" x-text="'(' + activeSubmitter.telegram_chat_id + ')'"></span>
                        </span>
                    </template>
                    <template x-if="!activeSubmitter?.telegram_connected">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-700/80 text-slate-300 text-[11px]">
                            <span>⏳ টেলিগ্রাম পেন্ডিং</span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- Drawer Scrollable Body: Accordion of Demands -->
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4 bg-slate-50">

                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                        <span>📋</span> সাবমিট করা চাহিদাসমূহ 
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-800" 
                              x-text="(activeSubmitter?.demands?.length || 0) + ' টি'"></span>
                    </h3>
                    <span class="text-[11px] text-slate-400">ক্লিক করে বিস্তারিত ও ম্যাচিং দেখুন</span>
                </div>

                <!-- Accordions Container -->
                <div class="space-y-3">
                    <template x-for="(demand, idx) in (activeSubmitter?.demands || [])" :key="demand.id">
                        <div class="rounded-2xl border bg-white shadow-xs transition-all overflow-hidden"
                             :class="expandedAccordions[demand.id] ? 'border-emerald-300 ring-2 ring-emerald-100' : 'border-slate-200 hover:border-slate-300'">

                            <!-- Accordion Header Button -->
                            <button type="button" @click="toggleAccordion(demand.id)"
                                    class="w-full p-4 text-left flex items-center justify-between gap-3 bg-white hover:bg-slate-50 transition cursor-pointer">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <!-- Demand Code -->
                                    <span class="font-mono font-black text-xs px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        #<span x-text="demand.requirement_code"></span>
                                    </span>

                                    <!-- Property Type Badge -->
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-800"
                                          x-text="getPropertyTypeLabel(demand.property_type)">
                                    </span>

                                    <!-- Match Count Badge -->
                                    <template x-if="demand.matches_count > 0">
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center gap-1">
                                            <span>🎯</span> <span x-text="demand.matches_count + 'টি ম্যাচ'"></span>
                                        </span>
                                    </template>
                                    <template x-if="demand.matches_count === 0">
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-500">
                                            ম্যাচ নেই
                                        </span>
                                    </template>

                                    <!-- Status Badge -->
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                                          :class="{
                                              'bg-emerald-100 text-emerald-800': demand.status === 'matched',
                                              'bg-blue-100 text-blue-800': demand.status === 'active',
                                              'bg-slate-200 text-slate-600': demand.status === 'closed'
                                          }"
                                          x-text="demand.status === 'active' ? 'সক্রিয়' : (demand.status === 'matched' ? 'ম্যাচড' : 'সমাপ্ত')">
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-slate-400 hidden sm:inline" x-text="formatDate(demand.created_at)"></span>
                                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200"
                                         :class="expandedAccordions[demand.id] ? 'rotate-180 text-emerald-600' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </button>

                            <!-- Accordion Body (Expanded Content) -->
                            <div x-show="expandedAccordions[demand.id]" x-collapse class="border-t border-slate-100 p-5 space-y-5 bg-white">

                                <!-- SECTION 1: DETAILED SPECIFICATIONS -->
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                        <span>📝</span> চাহিদার পূর্ণ বিবরণ ও ক্রাইটেরিয়া
                                    </h4>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                                        <div>
                                            <span class="text-slate-400 text-[10px] block">পছন্দের এলাকা:</span>
                                            <span class="font-bold text-slate-800" x-text="formatAreas(demand.preferred_areas)"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">পছন্দের ব্লক:</span>
                                            <span class="font-bold text-slate-800" x-text="formatBlocks(demand.preferred_blocks)"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">বাজেট পরিসীমা:</span>
                                            <span class="font-bold text-slate-800" x-text="formatBudget(demand.min_budget, demand.max_budget)"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">আয়তন / সাইজ:</span>
                                            <span class="font-bold text-slate-800" x-text="formatSize(demand.min_size, demand.max_size, demand.property_type)"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">ফেসিং (Facing):</span>
                                            <span class="font-bold text-slate-800" x-text="formatFacings(demand.preferred_facings)"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">রাস্তার প্রশস্ততা (Road):</span>
                                            <span class="font-bold text-slate-800" x-text="demand.min_road_width_ft ? (demand.min_road_width_ft + ' ফিট+') : 'যে কোনো'"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">উদ্দেশ্য (Purpose):</span>
                                            <span class="font-semibold text-slate-700" x-text="getPurposeLabel(demand.purpose)"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">কেনার সময়সীমা:</span>
                                            <span class="font-semibold text-slate-700" x-text="getTimelineLabel(demand.purchase_timeline)"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 text-[10px] block">সাবমিশন তারিখ:</span>
                                            <span class="font-mono text-slate-600" x-text="formatDate(demand.created_at)"></span>
                                        </div>
                                    </div>

                                    <!-- Criteria Details (Flat / Land custom choices) -->
                                    <template x-if="demand.criteria_details && Object.keys(demand.criteria_details).length > 0">
                                        <div class="mt-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
                                            <span class="text-[10px] font-bold uppercase text-slate-500 block mb-1.5">অতিরিক্ত স্পেসিফিকেশন:</span>
                                            <div class="flex flex-wrap gap-1.5">
                                                <template x-for="(val, key) in demand.criteria_details" :key="key">
                                                    <span class="px-2 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 font-medium text-[11px]">
                                                        <span class="text-slate-400" x-text="formatCriteriaKey(key) + ': '"></span>
                                                        <strong x-text="val"></strong>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Customer Special Notes -->
                                    <template x-if="demand.notes">
                                        <div class="mt-2.5 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs">
                                            <span class="text-[10px] font-bold text-amber-900 block mb-0.5">📌 গ্রাহকের বিশেষ নোট / চাহিদা:</span>
                                            <p class="text-slate-700 italic m-0" x-text="demand.notes"></p>
                                        </div>
                                    </template>
                                </div>

                                <!-- SECTION 2: MATCHED PROPERTIES (কয়টা ও কি কি ম্যাচ পেল) -->
                                <div class="pt-3 border-t border-slate-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                                            <span>🎯</span> ম্যাচ হওয়া প্রপার্টি সমূহ 
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-800"
                                                  x-text="(demand.matches_data?.length || 0) + ' টি'"></span>
                                        </h4>
                                        <span class="text-[10px] text-slate-400">AI অ্যালগরিদম স্কোর অনুযায়ী</span>
                                    </div>

                                    <!-- Matched Property Cards List -->
                                    <div class="space-y-3">
                                        <template x-for="match in (demand.matches_data || [])" :key="match.id">
                                            <div class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col sm:flex-row gap-3">
                                                
                                                <!-- Cover Image -->
                                                <div class="w-full sm:w-28 h-20 rounded-xl overflow-hidden bg-slate-200 shrink-0 relative">
                                                    <img :src="match.cover_url" :alt="match.title" class="w-full h-full object-cover">
                                                    <span class="absolute top-1 left-1 bg-slate-900/80 text-white text-[9px] font-mono px-1.5 py-0.5 rounded">
                                                        #<span x-text="match.code"></span>
                                                    </span>
                                                </div>

                                                <!-- Content -->
                                                <div class="flex-1 flex flex-col justify-between">
                                                    <div>
                                                        <div class="flex items-start justify-between gap-2">
                                                            <a :href="match.url" target="_blank" class="font-bold text-slate-900 text-xs hover:text-emerald-600 line-clamp-1">
                                                                <span x-text="match.title"></span>
                                                            </a>
                                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0"
                                                                  x-text="match.score + '% Match'">
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center gap-3 text-[11px] text-slate-600 mt-1">
                                                            <span>📍 <span x-text="match.location"></span></span>
                                                            <span class="font-bold text-emerald-700" x-text="match.price"></span>
                                                        </div>

                                                        <!-- Match Reasons -->
                                                        <template x-if="match.reasons && match.reasons.length">
                                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                                <template x-for="r in match.reasons" :key="r">
                                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-100" x-text="'✓ ' + r"></span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <!-- Match Action Buttons -->
                                                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-slate-200/60">
                                                        <a :href="match.url" target="_blank" 
                                                           class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-[10px] transition">
                                                            প্রপার্টি দেখুন &rarr;
                                                        </a>
                                                        <a :href="match.share_wa_url" target="_blank" rel="noopener"
                                                           class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] transition flex items-center gap-1">
                                                            <span>💬 বায়ারকে হোয়াটসঅ্যাপে পাঠান</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- If No Matches -->
                                        <template x-if="!demand.matches_data || demand.matches_data.length === 0">
                                            <div class="p-4 rounded-xl border border-dashed border-slate-300 text-center text-xs text-slate-500 space-y-1">
                                                <div class="text-lg">🔍</div>
                                                <div class="font-bold text-slate-700">বর্তমানে কোনো সরাসরি ম্যাচিং লিস্টিং নেই</div>
                                                <p class="text-[11px] text-slate-400 m-0">
                                                    নতুন প্রপার্টি লিস্টিং আসামাত্র AI সিস্টেম স্বয়ংক্রিয়ভাবে বায়ারকে ইমেইল ও টেলিগ্রামে নোটিফাই করবে।
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- SECTION 3: DEMAND STATUS & MANAGEMENT -->
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <label class="text-[11px] font-bold text-slate-600">স্ট্যাটাস:</label>
                                        <select :value="demand.status" 
                                                @change="updateStatus(demand.id, $event.target.value)"
                                                class="text-xs font-bold px-2.5 py-1 rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                                            <option value="active">সক্রিয় (Active)</option>
                                            <option value="matched">ম্যাচ পাওয়া (Matched)</option>
                                            <option value="closed">সমাপ্ত (Closed)</option>
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <!-- Telegram Resend Alert -->
                                        <template x-if="activeSubmitter?.telegram_connected">
                                            <button type="button" @click="sendTelegramAlert(demand.id)"
                                                    class="px-3 py-1 bg-sky-50 hover:bg-sky-100 text-sky-800 font-bold rounded-lg text-xs border border-sky-200 transition flex items-center gap-1 cursor-pointer">
                                                <span>✈️ টেলিগ্রামে অ্যালার্ট পাঠান</span>
                                            </button>
                                        </template>

                                        <!-- Delete Demand -->
                                        <form :action="'/backend/demands/' + demand.id" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই চাহিদাটি মুছে ফেলতে চান?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg text-xs transition border border-rose-200 cursor-pointer">
                                                মুছে ফেলুন
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Drawer Footer -->
            <div class="p-4 bg-slate-100 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 shrink-0">
                <span>PropertyKoi AI Demand Matching Engine</span>
                <button type="button" @click="drawerOpen = false" 
                        class="px-4 py-1.5 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition cursor-pointer">
                    বন্ধ করুন
                </button>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
function demandManager() {
    return {
        drawerOpen: false,
        activeSubmitter: null,
        expandedAccordions: {},

        openDrawer(submitter) {
            this.activeSubmitter = submitter;
            this.expandedAccordions = {};
            // Auto expand the first demand by default
            if (submitter.demands && submitter.demands.length > 0) {
                this.expandedAccordions[submitter.demands[0].id] = true;
            }
            this.drawerOpen = true;
        },

        toggleAccordion(demandId) {
            this.expandedAccordions[demandId] = !this.expandedAccordions[demandId];
        },

        updateStatus(demandId, status) {
            fetch(`/backend/demands/${demandId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update in memory
                    if (this.activeSubmitter && this.activeSubmitter.demands) {
                        const d = this.activeSubmitter.demands.find(x => x.id === demandId);
                        if (d) d.status = status;
                    }
                    alert('✅ স্ট্যাটাস সফলভাবে আপডেট হয়েছে!');
                }
            })
            .catch(() => alert('স্ট্যাটাস আপডেট করতে সমস্যা হয়েছে।'));
        },

        sendTelegramAlert(demandId) {
            fetch(`/backend/demands/${demandId}/telegram-alert`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message || (data.success ? '✅ টেলিগ্রামে অ্যালার্ট পাঠানো হয়েছে!' : 'ব্যর্থ হয়েছে'));
            })
            .catch(() => alert('টেলিগ্রাম বার্তা পাঠাতে ত্রুটি হয়েছে।'));
        },

        getPropertyTypeLabel(type) {
            const map = {
                'land': '🌱 জমি / প্লট',
                'flat': '🏢 আবাসিক ফ্ল্যাট',
                'land_share': '🤝 ল্যান্ড শেয়ার',
                'commercial': '🏬 কমার্শিয়াল',
                'any': 'যেকোনো ধরনের'
            };
            return map[type] || type;
        },

        formatAreas(areas) {
            if (!areas) return 'আফতাবনগর';
            if (Array.isArray(areas)) return areas.join(', ');
            return areas;
        },

        formatBlocks(blocks) {
            if (!blocks || !blocks.length) return 'যে কোনো ব্লক';
            if (Array.isArray(blocks)) return 'ব্লক ' + blocks.join(', ');
            return blocks;
        },

        formatBudget(min, max) {
            if (!min && !max) return 'আলোচনা সাপেক্ষ';
            const fMin = min ? (min / 100000).toFixed(1) + ' লাখ' : '০';
            const fMax = max ? (max / 100000).toFixed(1) + ' লাখ' : 'সর্বোচ্চ';
            return `৳ ${fMin} - ${fMax}`;
        },

        formatSize(min, max, type) {
            if (!min && !max) return 'যেকোনো সাইজ';
            const unit = type === 'land' ? 'কাঠা' : 'বর্গফুট';
            const fMin = min ? Number(min) : '০';
            const fMax = max ? Number(max) : 'সর্বোচ্চ';
            return `${fMin} - ${fMax} ${unit}`;
        },

        formatFacings(facings) {
            if (!facings || !facings.length) return 'যে কোনো দিক';
            if (Array.isArray(facings)) return facings.join(', ');
            return facings;
        },

        getPurposeLabel(p) {
            const map = {
                'own_house': 'নিজের বসবাসের জন্য',
                'investment': 'ভবিষ্যত বিনিয়োগ',
                'commercial': 'বাণিজ্যিক ব্যবহারের জন্য'
            };
            return map[p] || p || 'বসবাস';
        },

        getTimelineLabel(t) {
            const map = {
                'urgent': 'জরুরি (১ মাসের মধ্যে)',
                'within_3_months': '৩ মাসের মধ্যে',
                'flexible': 'সময় নিয়ে ভালো প্রপার্টি খুঁজছি'
            };
            return map[t] || t || '৩ মাসের মধ্যে';
        },

        formatDate(iso) {
            if (!iso) return '';
            try {
                const d = new Date(iso);
                return d.toLocaleDateString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch(e) {
                return iso;
            }
        },

        formatCriteriaKey(key) {
            const map = {
                'bed_count': 'বেডরুম',
                'bath_count': 'বাথরুম',
                'floor_preference': 'পছন্দের ফ্লোর',
                'corner_plot': 'কর্নার প্লট',
                'land_status': 'জমির অবস্থা',
                'parking_needed': 'পার্কিং',
                'handover_status': 'হস্তান্তর সময়'
            };
            return map[key] || key;
        }
    };
}
</script>
@endpush
@endsection
