@extends('layouts.app')

@section('content')
<div class="space-y-12 md:space-y-16 pb-12">

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-b from-emerald-900 via-slate-900 to-slate-950 text-white pt-12 md:pt-20 pb-16 md:pb-24 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <!-- Background subtle glow -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.15),transparent_50%)]"></div>

        <div class="max-w-5xl mx-auto text-center relative z-10 space-y-5">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs md:text-sm font-semibold tracking-wide">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                ১০০% সরাসরি মালিক ও ভেরিফাইড প্রপার্টি
            </span>

            <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight leading-tight md:leading-tight">
                ঢাকা ও আফতাবনগরের <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">সরাসরি প্রপার্টি</span> মার্কেটপ্লেস
            </h1>

            <p class="max-w-2xl mx-auto text-sm sm:text-base md:text-lg text-slate-300 leading-relaxed font-normal">
                কোনো মধ্যস্বত্বভোগী ছাড়া সরাসরি প্রকৃত মালিক ও ভেরিফাইড এজেন্টের সাথে আলোচনা করে জমি, ফ্ল্যাট বা দোকান কিনুন ও বিক্রি করুন।
            </p>

            <!-- Search Filter Box -->
            <div class="max-w-4xl mx-auto mt-8 bg-white/95 backdrop-blur-md rounded-3xl p-4 sm:p-6 shadow-2xl border border-white/20 text-slate-800"
                 x-data="{ selectedType: 'all' }">

                <!-- Type Selector Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-3 mb-3 border-b border-slate-100 no-scrollbar">
                    <button type="button"
                            @click="selectedType = 'all'"
                            :class="selectedType === 'all' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm whitespace-nowrap transition-all">
                        সকল ক্যাটাগরি
                    </button>
                    <button type="button"
                            @click="selectedType = 'land'"
                            :class="selectedType === 'land' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm whitespace-nowrap transition-all">
                        জমি ও প্লট
                    </button>
                    <button type="button"
                            @click="selectedType = 'flat'"
                            :class="selectedType === 'flat' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm whitespace-nowrap transition-all">
                        ফ্ল্যাট / অ্যাপার্টমেন্ট
                    </button>
                    <button type="button"
                            @click="selectedType = 'land_share'"
                            :class="selectedType === 'land_share' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm whitespace-nowrap transition-all">
                        ল্যান্ড শেয়ার
                    </button>
                    <button type="button"
                            @click="selectedType = 'commercial'"
                            :class="selectedType === 'commercial' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm whitespace-nowrap transition-all">
                        দোকান / স্পেস
                    </button>
                </div>

                <form action="{{ route('properties.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-left">
                    <input type="hidden" name="type" :value="selectedType">

                    <!-- Area Select -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">এলাকা</label>
                        <select name="area" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500">
                            <option value="Aftabnagar">আফতাবনগর (Aftabnagar)</option>
                            <option value="Bashundhara R/A">বসুন্ধরা আ/এ (Bashundhara)</option>
                            <option value="Purbachal">পূর্বাচল (Purbachal)</option>
                            <option value="Banasree">বনশ্রী (Banasree)</option>
                        </select>
                    </div>

                    <!-- Block Select -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">ব্লক / সেক্টর</label>
                        <select name="block" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500">
                            <option value="all">সকল ব্লক</option>
                            @foreach(['A','B','C','D','E','F','G','H','I','J','K','L','M','N'] as $b)
                                <option value="{{ $b }}">ব্লক {{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Road Width -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">রাস্তার প্রশস্ততা</label>
                        <select name="road_width" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500">
                            <option value="">যেকোনো রাস্তা</option>
                            <option value="20">২০+ ফুট রাস্তা</option>
                            <option value="25">২৫+ ফুট রাস্তা</option>
                            <option value="30">৩০+ ফুট রাস্তা</option>
                            <option value="40">৪০+ ফুট প্রশস্থ অ্যাভিনিউ</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full py-2.5 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span>প্রপার্টি খুঁজুন</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats Bar -->
            <div class="pt-6 grid grid-cols-3 gap-4 max-w-xl mx-auto text-center border-t border-white/10 text-xs sm:text-sm">
                <div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-400">{{ $stats['total_properties'] }}+</div>
                    <div class="text-slate-400 text-[11px] sm:text-xs">লাইভ প্রপার্টি</div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-400">{{ $stats['total_owners'] }}+</div>
                    <div class="text-slate-400 text-[11px] sm:text-xs">সরাসরি মালিক</div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-400">{{ $stats['total_blocks'] }}টি</div>
                    <div class="text-slate-400 text-[11px] sm:text-xs">আফতাবনগর ব্লক</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Properties Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <span class="text-emerald-600 font-bold text-xs uppercase tracking-wider block">এক্সক্লুসিভ কালেকশন</span>
                <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight">ফিচার্ড ও হট ডিল প্রপার্টি</h2>
            </div>
            <a href="{{ route('properties.index') }}" class="text-sm font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 group">
                <span>সবগুলো দেখুন</span>
                <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($featured as $prop)
                <x-property-card :property="$prop" />
            @empty
                <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-3xl border border-slate-200">
                    <p class="font-medium">বর্তমানে কোনো ফিচার্ড প্রপার্টি পাওয়া যায়নি।</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Latest Land / Plots Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <span class="text-emerald-600 font-bold text-xs uppercase tracking-wider block">জমি ও প্লট</span>
                <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight">আফতাবনগরের সেরা প্লটসমূহ</h2>
            </div>
            <a href="{{ route('properties.land') }}" class="text-sm font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 group">
                <span>সকল জমি দেখুন</span>
                <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @forelse($latestLands as $prop)
                <x-property-card :property="$prop" />
            @empty
                <div class="col-span-full py-8 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <p>বর্তমানে কোনো জমির লিস্টিং নেই।</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Latest Flats Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <span class="text-teal-600 font-bold text-xs uppercase tracking-wider block">ফ্ল্যাট ও অ্যাপার্টমেন্ট</span>
                <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight">রেডি ও চলমান ফ্ল্যাটসমূহ</h2>
            </div>
            <a href="{{ route('properties.flat') }}" class="text-sm font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 group">
                <span>সকল ফ্ল্যাট দেখুন</span>
                <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @forelse($latestFlats as $prop)
                <x-property-card :property="$prop" />
            @empty
                <div class="col-span-full py-8 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <p>বর্তমানে কোনো ফ্ল্যাটের লিস্টিং নেই।</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Direct Buyer Demand CTA Banner -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-emerald-600 via-teal-700 to-slate-900 rounded-3xl p-6 sm:p-10 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl">
            <div class="space-y-2 text-center md:text-left">
                <span class="px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider inline-block">
                    ডিমান্ড ম্যাচিং ইঞ্জিন
                </span>
                <h3 class="text-2xl sm:text-3xl font-black tracking-tight">আপনার পছন্দের জমি বা ফ্ল্যাট খুঁজে পাচ্ছেন না?</h3>
                <p class="text-emerald-100 text-sm sm:text-base max-w-xl">
                    আপনার বাজেট ও চাহিদামতো রিকোয়ারমেন্ট সাবমিট করে রাখুন। উপযুক্ত কোনো প্রপার্টি আসার সাথে সাথেই টেলিগ্রাম, ইমেইল বা পুশ নোটিফিকেশনে অ্যালার্ট পাবেন।
                </p>
            </div>
            <a href="{{ route('chahida.index') }}"
               class="px-6 py-3.5 rounded-2xl bg-white text-emerald-800 hover:bg-emerald-50 font-black text-sm sm:text-base shadow-lg transition-transform hover:scale-105 shrink-0">
                🎯 চাহিদা জমা দিন
            </a>
        </div>
    </section>

    <!-- AEO (Answer Engine Optimization) & Buyer Guide FAQ Section -->
    @if(!empty($seo['faqs']) && count($seo['faqs']) > 0)
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                            🤖 AEO & Real Estate Guide
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-2">
                            আফতাবনগর ও ঢাকায় প্রপার্টি কেনা-বেচা সংক্রান্ত সাধারণ প্রশ্নোত্তর (FAQ)
                        </h2>
                    </div>
                    <a href="{{ route('properties.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 underline">
                        সকল ভেরিফাইড প্রপার্টি দেখুন &rarr;
                    </a>
                </div>

                <div class="space-y-3" x-data="{ homeFaq: 0 }">
                    @foreach($seo['faqs'] as $idx => $faq)
                        <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all"
                             :class="homeFaq === {{ $idx }} ? 'bg-emerald-50/30 border-emerald-300' : 'bg-slate-50/60'">
                            <button type="button"
                                    @click="homeFaq = homeFaq === {{ $idx }} ? null : {{ $idx }}"
                                    class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-extrabold text-sm text-slate-900 cursor-pointer">
                                <span class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs flex items-center justify-center shrink-0">প্র</span>
                                    <span>{{ $faq['question'] ?? ($faq['q'] ?? '') }}</span>
                                </span>
                                <span class="text-slate-400 font-bold text-lg transition-transform"
                                      :class="homeFaq === {{ $idx }} ? 'rotate-180 text-emerald-600' : ''">⌄</span>
                            </button>
                            <div x-show="homeFaq === {{ $idx }}" x-cloak class="px-5 pb-4 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                                {{ $faq['answer'] ?? ($faq['a'] ?? '') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</div>
@endsection
