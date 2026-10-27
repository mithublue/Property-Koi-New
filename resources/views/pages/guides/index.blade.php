@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">হোম</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">প্রপার্টি গাইড ও নলেজ হাব</span>
        </nav>

        <!-- Header & Hero Intro -->
        <div class="text-center max-w-3xl mx-auto mb-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-3 shadow-2xs">
                <span>📚</span> নলেজ হাব ও আইনি গাইডলাইন
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                বাংলাদেশে জমি ও ফ্ল্যাট কেনাবেচার <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600">নির্ভরযোগ্য গাইড</span>
            </h1>
            <p class="mt-3 text-sm sm:text-base text-slate-600 leading-relaxed">
                জমির দলিল ও নামজারি খতিয়ান যাচাই, ফ্ল্যাট ক্রয়ে রাজউক অনুমোদন, রেজিস্ট্রেশন ফি হিসাব এবং ল্যান্ড শেয়ার প্রকল্পের সঠিক নিয়ম জানুন এক ক্লিকেই।
            </p>

            <!-- Search Form -->
            <form action="{{ route('guides.index') }}" method="GET" class="mt-6 flex items-center max-w-xl mx-auto bg-white p-1.5 rounded-2xl border border-slate-200 shadow-sm focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-200 transition-all">
                @if(!empty($category) && $category !== 'all')
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif
                <div class="pl-3 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text"
                       name="q"
                       value="{{ $search ?? '' }}"
                       placeholder="বিষয় খুঁজুন (যেমন: দলিল যাচাই, রাজউক নকশা, রেজিস্ট্রেশন...)"
                       class="w-full px-3 py-2 text-sm text-slate-800 bg-transparent focus:outline-hidden">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                    অনুসন্ধান
                </button>
            </form>
        </div>

        <!-- Category Filter Pills -->
        <div class="flex items-center justify-center flex-wrap gap-2 mb-10">
            @foreach($categories as $key => $label)
                @php
                    $isActive = ($category === $key) || (empty($category) && $key === 'all');
                    $count = $counts[$key] ?? 0;
                @endphp
                <a href="{{ route('guides.index', array_filter(['category' => ($key === 'all' ? null : $key), 'q' => $search])) }}"
                   class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 border {{ $isActive ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-300 hover:bg-slate-50' }}">
                    <span>{{ $label }}</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $isActive ? 'bg-emerald-700 text-emerald-100' : 'bg-slate-100 text-slate-600' }}">{{ $count }}</span>
                </a>
            @endforeach
        </div>

        <!-- Special Location Hub: Aftabnagar Buyer Guide & Price Index 2026 -->
        <div class="mb-10 bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg border border-emerald-500/30 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
            <div class="space-y-2 max-w-2xl relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-xs border border-emerald-400/40">
                    <span>🔥</span> মার্কেট ট্রেন্ডিং স্পটলাইট: আফতাবনগর ২০২৬
                </span>
                <h3 class="text-xl sm:text-2xl font-black text-white m-0">
                    আফতাবনগর প্রপার্টি বায়ার গাইড ও কাঠাপ্রতি বাজারদর ২০২৬
                </h3>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed m-0">
                    ঢাকা ও সারা দেশের প্রপার্টি গাইডলাইনের পাশাপাশি বর্তমানে সর্বোচ্চ আলোচিত আফতাবনগরের ব্লকভিত্তিক কাঠাপ্রতি মূল্য তালিকা, ৩ কাঠার বাজেট ও দলিল যাচাই নির্দেশিকা।
                </p>
            </div>
            <div class="shrink-0 relative z-10 w-full md:w-auto">
                <a href="{{ route('guides.aftabnagar') }}" class="inline-flex items-center justify-center gap-2 w-full md:w-auto px-6 py-3 rounded-2xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-black text-xs sm:text-sm transition shadow-md">
                    <span>সম্পূর্ণ গাইড ও প্রাইস টেবিল দেখুন</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Featured Guide (Hero Card if Page 1) -->
        @if(!empty($featuredGuide))
            <div class="mb-12 bg-white rounded-3xl border border-slate-200/90 shadow-md hover:shadow-xl transition-all overflow-hidden group">
                <div class="grid grid-cols-1 lg:grid-cols-12 items-center">
                    <div class="lg:col-span-5 h-64 lg:h-96 relative overflow-hidden bg-slate-900">
                        <img src="{{ $featuredGuide->cover_url }}"
                             alt="{{ $featuredGuide->title }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                        <span class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-bold bg-amber-500 text-slate-950 shadow-md flex items-center gap-1">
                            <span>⭐</span> ফিচার্ড প্রপার্টি গাইড
                        </span>
                    </div>
                    <div class="lg:col-span-7 p-6 sm:p-10 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border {{ $featuredGuide->category_badge_class }}">
                                    {{ $featuredGuide->category_label }}
                                </span>
                                <span class="text-xs text-slate-400">⏱️ {{ $featuredGuide->reading_time_min }} মিনিট পড়ার সময়</span>
                                <span class="text-xs text-slate-400">👁️ {{ number_format($featuredGuide->views_count) }} ভিউ</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 leading-snug group-hover:text-emerald-600 transition-colors">
                                <a href="{{ route('guides.show', $featuredGuide->slug) }}">
                                    {{ $featuredGuide->title }}
                                </a>
                            </h2>
                            <p class="mt-3 text-sm sm:text-base text-slate-600 leading-relaxed line-clamp-3">
                                {{ $featuredGuide->excerpt }}
                            </p>
                        </div>
                        <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2.5 text-xs text-slate-500">
                                <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">🏢</span>
                                <div>
                                    <span class="font-bold text-slate-800 block">Property Koi Desk</span>
                                    <span>{{ $featuredGuide->formatted_date }}</span>
                                </div>
                            </div>
                            <a href="{{ route('guides.show', $featuredGuide->slug) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                                <span>সম্পূর্ণ পড়ুন</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Guides Grid -->
        @if($guides->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                @foreach($guides as $guide)
                    <article class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-lg transition-all flex flex-col justify-between overflow-hidden group">
                        <div>
                            <a href="{{ route('guides.show', $guide->slug) }}" class="block relative aspect-video bg-slate-100 overflow-hidden">
                                <img src="{{ $guide->cover_url }}"
                                     alt="{{ $guide->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy">
                                <span class="absolute top-3 left-3 px-2.5 py-0.5 rounded-md text-[11px] font-bold border backdrop-blur-xs bg-white/90 {{ $guide->category_badge_class }}">
                                    {{ $guide->category_label }}
                                </span>
                            </a>
                            <div class="p-5 sm:p-6">
                                <div class="flex items-center justify-between text-[11px] text-slate-400 mb-2.5">
                                    <span>⏱️ {{ $guide->reading_time_min }} মিনিট পাঠ</span>
                                    <span>👁️ {{ number_format($guide->views_count) }} পাঠক</span>
                                </div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-emerald-600 transition-colors line-clamp-2">
                                    <a href="{{ route('guides.show', $guide->slug) }}">
                                        {{ $guide->title }}
                                    </a>
                                </h3>
                                <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed line-clamp-3">
                                    {{ $guide->excerpt }}
                                </p>
                            </div>
                        </div>
                        <div class="px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">{{ $guide->formatted_date }}</span>
                            <a href="{{ route('guides.show', $guide->slug) }}" class="font-bold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                                <span>বিস্তারিত পড়ুন</span> &rarr;
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-10">
                {{ $guides->links() }}
            </div>
        @else
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 max-w-lg mx-auto">
                <span class="text-4xl block mb-3">🔍</span>
                <h3 class="text-lg font-bold text-slate-900">কোনো গাইড পাওয়া যায়নি</h3>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">আপনার অনুসন্ধানের সাথে সামঞ্জস্যপূর্ণ কোনো গাইড এই মুহূর্তে নেই।</p>
                <a href="{{ route('guides.index') }}" class="mt-4 inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    সকল গাইড দেখুন
                </a>
            </div>
        @endif

        <!-- Interactive Calculators Callout Banner -->
        <div class="mt-16 bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-8 sm:p-12 text-white shadow-xl relative overflow-hidden">
            <div class="max-w-2xl relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 mb-3">
                    <span>⚡</span> স্মার্ট রিয়েল এস্টেট টুলস
                </span>
                <h3 class="text-xl sm:text-3xl font-black tracking-tight leading-tight">
                    নিবন্ধন খরচ বা ল্যান্ড শেয়ারের সম্ভাব্য লাভ হিসাব করতে চান?
                </h3>
                <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                    আমাদের ২০২৬ সালের আপডেট করা সরকারি স্ট্যাম্প ডিউটি ও ট্যাক্স ক্যালকুলেটর এবং স্মার্ট ভ্যালুয়েশন এস্টিমেটর ব্যবহার করুন বিনামূল্যে।
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('calculators.registration') }}" class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-bold shadow-md transition flex items-center gap-2">
                        <span>🧮</span> রেজিস্ট্রেশন ফি ক্যালকুলেটর
                    </a>
                    <a href="{{ route('calculators.land-share') }}" class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs sm:text-sm font-bold border border-white/20 transition flex items-center gap-2">
                        <span>📊</span> ল্যান্ড শেয়ার ROI ক্যালকুলেটর
                    </a>
                    <a href="{{ route('valuation.index') }}" class="px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs sm:text-sm font-bold transition flex items-center gap-2">
                        <span>💡</span> প্রপার্টি ভ্যালুয়েশন যাচাই
                    </a>
                </div>
            </div>
            <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        </div>

        <!-- AEO Frequently Asked Questions Accordion -->
        @if(!empty($ogMeta['faqs']))
            <section class="mt-16 bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs">
                <div class="text-center max-w-2xl mx-auto mb-8">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">FAQ / প্রশ্নোত্তর</span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-2">প্রপার্টি গাইড ও আইনি যাচাই সংক্রান্ত সাধারণ প্রশ্ন</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">জমি ও ফ্ল্যাট ক্রয়ের আগে ক্রেতা ও বিনিয়োগকারীদের সবচেয়ে বেশি জিজ্ঞাসিত বিষয়সমূহ</p>
                </div>
                <div class="max-w-3xl mx-auto space-y-3" x-data="{ active: null }">
                    @foreach($ogMeta['faqs'] as $index => $faq)
                        <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all bg-slate-50/50">
                            <button type="button"
                                    @click="active = active === {{ $index }} ? null : {{ $index }}"
                                    class="w-full text-left p-4 sm:p-5 font-bold text-sm sm:text-base text-slate-900 flex items-center justify-between gap-4 cursor-pointer hover:text-emerald-700 transition">
                                <span>{{ $faq['question'] }}</span>
                                <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="active === {{ $index }} ? 'rotate-180 text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="active === {{ $index }}" x-collapse class="px-4 sm:px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                                {!! nl2br(e($faq['answer'])) !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
</div>
@endsection
