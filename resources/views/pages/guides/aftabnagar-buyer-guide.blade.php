@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12" x-data="aftabnagarGuideData()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10 sm:space-y-12">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center flex-wrap gap-2 text-xs text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition flex items-center gap-1">
                <span>🏠</span> <span>হোম</span>
            </a>
            <span>/</span>
            <a href="{{ route('guides.index') }}" class="hover:text-emerald-600 transition">প্রপার্টি গাইডস</a>
            <span>/</span>
            <span class="text-slate-800 font-bold truncate">আফতাবনগর বায়ার গাইড ও কাঠাপ্রতি বাজারদর ২০২৬</span>
        </nav>

        <!-- Hero Header -->
        <header class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-10 lg:p-12 shadow-xs relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-emerald-50 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-teal-50 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-4xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-black tracking-wide">
                    <span>📍</span>
                    <span>আফতাবনগর রিয়েল এস্টেট মার্কেট ইন্টেলিজেন্স ২০২৬</span>
                </div>

                <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                    আফতাবনগর প্রপার্টি বায়ার গাইড ও <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600">কাঠাপ্রতি বাজারদর ২০২৬</span>
                </h1>

                <p class="text-sm sm:text-base text-slate-600 leading-relaxed font-normal">
                    প্লট, রেডি ফ্ল্যাট ও ল্যান্ড শেয়ার কেনার জন্য ঢাকা রিয়েল এস্টেটের সবচেয়ে তথ্যবহুল নির্দেশিকা। ব্লকভিত্তিক কাঠাপ্রতি মূল্য তালিকা (ব্লক A থেকে M ও এক্সটেনশন), ৩ কাঠার বাজেট প্রজেকশন, আইনি দলিল যাচাই চেকলিস্ট এবং সরাসরি ভেরিফাইড প্রপার্টি ডিরেক্টরি।
                </p>

                <!-- Quick Market Statistics -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 pt-4">
                    <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">আবাসিক ব্লক</span>
                        <span class="text-lg sm:text-xl font-black text-slate-900 mt-0.5 block">১৪টি ব্লক (A - M)</span>
                        <span class="text-[10px] text-emerald-600 font-semibold">মেইন ও শাখা রোড</span>
                    </div>

                    <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">জমির কাঠাপ্রতি দর</span>
                        <span class="text-lg sm:text-xl font-black text-slate-900 mt-0.5 block">৳৪৫ লাখ – ৳১.৬০ কোটি</span>
                        <span class="text-[10px] text-emerald-600 font-semibold">ব্লক ও রোডের প্রশস্ততানুযায়ী</span>
                    </div>

                    <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">রেডি ফ্ল্যাট রেট</span>
                        <span class="text-lg sm:text-xl font-black text-slate-900 mt-0.5 block">৳৬,২০০ – ৳৯,৫০০</span>
                        <span class="text-[10px] text-emerald-600 font-semibold">প্রতি বর্গফুট (৩-৪ বেড)</span>
                    </div>

                    <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">ল্যান্ড শেয়ার খরচ</span>
                        <span class="text-lg sm:text-xl font-black text-slate-900 mt-0.5 block">৳৩,৮০০ – ৳৪,৮০০</span>
                        <span class="text-[10px] text-teal-600 font-semibold">৩০-৩৫% পর্যন্ত সাশ্রয়ী</span>
                    </div>
                </div>

                <!-- Navigation Quick-Jump Pills -->
                <div class="flex items-center flex-wrap gap-2 pt-2 text-xs">
                    <span class="text-slate-400 font-semibold">দ্রুত দেখুন:</span>
                    <a href="#price-index-table" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 font-bold transition">📊 ব্লকভিত্তিক জমির দর</a>
                    <a href="#block-comparison" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 font-bold transition">🧭 কোন ব্লক সেরা?</a>
                    <a href="#flat-pricing" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 font-bold transition">🏢 ফ্ল্যাট ও শেয়ার দর</a>
                    <a href="#legal-checklist" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 font-bold transition">📜 দলিল যাচাই চেকলিস্ট</a>
                    <a href="#buyer-faqs" class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 font-bold transition">❓ বায়ার প্রশ্নোত্তর</a>
                </div>
            </div>
        </header>

        <!-- Section 1: Block-by-Block Land Price Index Table 2026 -->
        <section id="price-index-table" class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg mb-1.5">
                        <span>📊</span> <span>Aftabnagar Plot Price per Katha 2026</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        আফতাবনগর ব্লকভিত্তিক জমির কাঠাপ্রতি মূল্য তালিকা (২০২৬)
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        সরেজমিন মার্কেট ডাটা ও ভেরিফাইড রেজিস্ট্রি লেনদেনের ভিত্তিতে হালনাগাদ করা হয়েছে।
                    </p>
                </div>

                <!-- Filter Controls -->
                <div class="flex items-center flex-wrap gap-2">
                    <input type="text"
                           x-model="searchFilter"
                           placeholder="ব্লকের নাম লিখুন (যেমন: A, B, C)..."
                           class="px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:border-emerald-500 w-48 sm:w-60 shadow-2xs">

                    <button type="button"
                            @click="filterZone = 'all'"
                            :class="filterZone === 'all' ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-3 py-2 rounded-xl text-xs transition cursor-pointer">
                        সব ব্লক
                    </button>
                    <button type="button"
                            @click="filterZone = 'prime'"
                            :class="filterZone === 'prime' ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-3 py-2 rounded-xl text-xs transition cursor-pointer">
                        প্রাইম (A-C)
                    </button>
                    <button type="button"
                            @click="filterZone = 'family'"
                            :class="filterZone === 'family' ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-3 py-2 rounded-xl text-xs transition cursor-pointer">
                        ফ্যামিলি (D-F)
                    </button>
                    <button type="button"
                            @click="filterZone = 'growth'"
                            :class="filterZone === 'growth' ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-3 py-2 rounded-xl text-xs transition cursor-pointer">
                        হাই ROI (G-M)
                    </button>
                </div>
            </div>

            <!-- Responsive Table Container -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-left text-xs sm:text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 font-black border-b border-slate-200 uppercase text-[11px] tracking-wider">
                            <th class="p-3.5 sm:p-4">ব্লক (Block)</th>
                            <th class="p-3.5 sm:p-4">রাস্তার প্রশস্ততা</th>
                            <th class="p-3.5 sm:p-4 text-emerald-800">কাঠাপ্রতি গড় দর ২০২৬</th>
                            <th class="p-3.5 sm:p-4">৩ কাঠা প্লটের বাজেট</th>
                            <th class="p-3.5 sm:p-4">অবস্থান ও মূল বৈশিষ্ট্য</th>
                            <th class="p-3.5 sm:p-4 text-right">লাইভ লিস্টিং</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($blockPriceIndex as $item)
                            <tr class="hover:bg-slate-50/80 transition"
                                x-show="isBlockVisible('{{ $item['block'] }}', '{{ addslashes($item['name_bn']) }}', '{{ addslashes($item['highlight']) }}')">
                                <td class="p-3.5 sm:p-4 font-bold text-slate-900 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-900 font-black flex items-center justify-center text-xs">{{ $item['block'] }}</span>
                                        <div>
                                            <span class="block font-bold text-slate-800">{{ $item['name_bn'] }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $item['badge'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3.5 sm:p-4 text-slate-600 whitespace-nowrap">{{ $item['road_width'] }}</td>
                                <td class="p-3.5 sm:p-4 font-black text-emerald-700 whitespace-nowrap">{{ $item['price_per_katha'] }}</td>
                                <td class="p-3.5 sm:p-4 font-semibold text-slate-800 whitespace-nowrap">{{ $item['budget_3_katha'] }}</td>
                                <td class="p-3.5 sm:p-4 text-slate-600 text-xs">
                                    <p class="m-0 leading-relaxed">{{ $item['highlight'] }}</p>
                                    <span class="text-[11px] text-teal-700 font-semibold">সেরা ব্যবহার: {{ $item['ideal_for'] }}</span>
                                </td>
                                <td class="p-3.5 sm:p-4 text-right whitespace-nowrap">
                                    <a href="{{ $item['search_url'] }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white font-extrabold text-xs transition border border-emerald-200 hover:border-emerald-600 shadow-2xs">
                                        <span>প্লট দেখুন</span>
                                        <span>&rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pro-Tip Box -->
            <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-xs text-amber-900 space-y-1">
                <span class="font-bold flex items-center gap-1.5">
                    <span>💡</span> <span>কর্নার ও লেকভিউ প্লটের প্রিমিয়াম রুল:</span>
                </span>
                <p class="m-0 leading-relaxed text-amber-800">
                    আফতাবনগরে কর্নার প্লট (Corner Plot) এবং হাতিরঝিল বা লেক সংলগ্ন প্লটের ক্ষেত্রে সাধারণ প্লটের চেয়ে কাঠাপ্রতি প্রায় ১০% থেকে ২০% প্রিমিয়াম মূল্য যুক্ত হয়ে থাকে। এছাড়া ৪০ থেকে ৬০ ফুট প্রধান সংযোগ সড়কের প্লটগুলোর বাণিজ্যিক মূল্যায়ন সাধারণ আবাসিক রোডের চেয়ে উল্লেখযোগ্যভাবে বেশি।
                </p>
            </div>
        </section>

        <!-- Section 2: Residential vs Investment Matrix (Best Block to Live) -->
        <section id="block-comparison" class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 uppercase tracking-wider">
                    🧭 বায়ার জোন অ্যানালাইসিস
                </span>
                <h2 class="text-xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    বসবাস বনাম বিনিয়োগ: আফতাবনগরের কোন ব্লক আপনার জন্য সেরা?
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    আপনার অগ্রাধিকার কি তাৎক্ষণিক বসবাস, স্কুলের দূরত্ব, নাকি দ্রুত মূলধনী লাভ? আপনার চাহিদানুযায়ী সঠিক জোন নির্বাচন করুন।
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                <!-- Zone 1: Prime Living & Gateway -->
                <div class="p-6 rounded-3xl bg-slate-50 border border-slate-200 hover:border-emerald-300 transition hover:shadow-md space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 font-black flex items-center justify-center text-lg">
                                🌟
                            </span>
                            <span class="text-[11px] font-black uppercase text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                সর্বোচ্চ ডিমান্ড
                            </span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 m-0">প্রাইম লিভিং জোন (ব্লক A, B, C)</h3>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            রামপুরা ব্রিজ ও হাতিরঝিল প্রবেশপথের সবচেয়ে নিকটে। ইস্ট ওয়েস্ট বিশ্ববিদ্যালয়, খ্যাতনামা ব্যাংক ও বাণিজ্যিক মার্কেট এই ব্লকে অবস্থিত।
                        </p>
                        <ul class="text-xs text-slate-600 space-y-1.5 list-none p-0 m-0">
                            <li class="flex items-center gap-1.5">
                                <span class="text-emerald-500 font-bold">✓</span> <span>হাতিরঝিল দিয়ে গুলশান-কারওয়ান বাজারে দ্রুত যাতায়াত</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-emerald-500 font-bold">✓</span> <span>১০০% রেডি ড্রেনেজ, সুয়ারেজ ও প্রশস্ত পাকা রাস্তা</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-emerald-500 font-bold">✓</span> <span>সর্বোচ্চ বাসা ভাড়া ও বাণিজ্যিক দোকান ডিমান্ড</span>
                            </li>
                        </ul>
                    </div>
                    <div class="pt-4 border-t border-slate-200/60">
                        <span class="text-xs text-slate-500 block">কাঠাপ্রতি দর:</span>
                        <span class="text-base font-black text-slate-900 block">৳১.১০ - ৳১.৬০ কোটি</span>
                        <a href="{{ route('properties.land', ['area' => 'Aftabnagar', 'block' => 'A']) }}" class="mt-3 block text-center py-2 rounded-xl bg-slate-900 hover:bg-emerald-600 text-white font-extrabold text-xs transition">
                            ব্লক A, B, C লিস্টিং দেখুন
                        </a>
                    </div>
                </div>

                <!-- Zone 2: Family & Calm Living -->
                <div class="p-6 rounded-3xl bg-slate-50 border border-slate-200 hover:border-emerald-300 transition hover:shadow-md space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-800 font-black flex items-center justify-center text-lg">
                                🏡
                            </span>
                            <span class="text-[11px] font-black uppercase text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200">
                                ফ্যামিলি ফ্রেন্ডলি
                            </span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 m-0">শান্ত আবাসিক জোন (ব্লক D, E, F)</h3>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            বাচ্চাদের স্কুল, কলেজ, খেলার মাঠ এবং মসজিদের কাছাকাছি মনোরম শান্ত পরিবেশ। পরিবার নিয়ে নিজস্ব বাড়ি বা রেডি ফ্ল্যাটে বসবাসের জন্য আদর্শ।
                        </p>
                        <ul class="text-xs text-slate-600 space-y-1.5 list-none p-0 m-0">
                            <li class="flex items-center gap-1.5">
                                <span class="text-teal-600 font-bold">✓</span> <span>ঢাকা ইমপেরিয়াল কলেজ ও একাধিক ইংলিশ মিডিয়াম স্কুল</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-teal-600 font-bold">✓</span> <span>লেকওয়াক ও কর্নার প্লটের চমৎকার খোলা পরিবেশ</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-teal-600 font-bold">✓</span> <span>রেডি ও নির্মাণাধীন ৩ বেডরুমের ফ্ল্যাটের বিপুল সমাহার</span>
                            </li>
                        </ul>
                    </div>
                    <div class="pt-4 border-t border-slate-200/60">
                        <span class="text-xs text-slate-500 block">কাঠাপ্রতি দর:</span>
                        <span class="text-base font-black text-slate-900 block">৳৮০ লাখ - ৳১.২৫ কোটি</span>
                        <a href="{{ route('properties.land', ['area' => 'Aftabnagar', 'block' => 'D']) }}" class="mt-3 block text-center py-2 rounded-xl bg-slate-900 hover:bg-emerald-600 text-white font-extrabold text-xs transition">
                            ব্লক D, E, F লিস্টিং দেখুন
                        </a>
                    </div>
                </div>

                <!-- Zone 3: High Growth & Investment -->
                <div class="p-6 rounded-3xl bg-slate-50 border border-slate-200 hover:border-emerald-300 transition hover:shadow-md space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-800 font-black flex items-center justify-center text-lg">
                                📈
                            </span>
                            <span class="text-[11px] font-black uppercase text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-200">
                                সর্বোচ্চ ROI
                            </span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 m-0">উচ্চ প্রবৃদ্ধি জোন (ব্লক G - M ও Ext)</h3>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            সাশ্রয়ী মূল্যে জমি কেনা বা ল্যান্ড শেয়ার প্রজেক্ট করার সবচেয়ে সম্ভাবনাময় এলাকা। ঢাকা বাইপাস ও নতুন সংযোগ সড়কের সুবাদে দ্রুত দাম বৃদ্ধি পাচ্ছে।
                        </p>
                        <ul class="text-xs text-slate-600 space-y-1.5 list-none p-0 m-0">
                            <li class="flex items-center gap-1.5">
                                <span class="text-indigo-600 font-bold">✓</span> <span>সবচেয়ে সাশ্রয়ী কাঠাপ্রতি মূল্যে জমি কেনার সুযোগ</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-indigo-600 font-bold">✓</span> <span>আগামী ৩-৫ বছরে দ্বিগুণ মূলধনী বৃদ্ধির সেরা সুযোগ</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="text-indigo-600 font-bold">✓</span> <span>যৌথ ল্যান্ড শেয়ারে কম খরচে ফ্ল্যাট তৈরির সেরা সম্ভাবনা</span>
                            </li>
                        </ul>
                    </div>
                    <div class="pt-4 border-t border-slate-200/60">
                        <span class="text-xs text-slate-500 block">কাঠাপ্রতি দর:</span>
                        <span class="text-base font-black text-slate-900 block">৳৪৫ লাখ - ৳৯৫ লাখ</span>
                        <a href="{{ route('properties.land', ['area' => 'Aftabnagar', 'block' => 'G']) }}" class="mt-3 block text-center py-2 rounded-xl bg-slate-900 hover:bg-emerald-600 text-white font-extrabold text-xs transition">
                            ব্লক G থেকে M লিস্টিং দেখুন
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Flat & Land Share Price Guide -->
        <section id="flat-pricing" class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                    🏢 ফ্ল্যাট ও ল্যান্ড শেয়ার রেট
                </span>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1.5">
                    আফতাবনগরে ফ্ল্যাট কেনা বনাম ল্যান্ড শেয়ার: খরচ ও সাশ্রয়ের তুলনা
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    রেডি ফ্ল্যাট, চলমান প্রকল্প এবং যৌথ ল্যান্ড শেয়ারের বর্তমান রেট ও আর্থিক বিশ্লেষণের সঠিক চিত্র।
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 1. Ready Flats -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-black text-slate-900 m-0">১০০% রেডি ফ্ল্যাট (Ready Flats)</h4>
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md">তাৎক্ষণিক বসবাস</span>
                    </div>
                    <div class="text-xs text-slate-600 space-y-1">
                        <div><strong>স্কয়ারফুট রেট:</strong> ৳৬,২০০ – ৳৯,৫০০ / বর্গফুট</div>
                        <div><strong>৩ বেডরুমের সাইজ:</strong> ১৩৫০ – ২০০০ বর্গফুট</div>
                        <div><strong>আনুমানিক বাজেট:</strong> ৳৯০ লাখ – ৳১.৮০ কোটি</div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed m-0">
                        বিদ্যুৎ, লিফট, জেনারেটর এবং পার্কিংসহ ১০০% প্রস্তুত। ব্যাংক লোনের সুবিধা পাওয়া যায় এবং অবিলম্বে পরিবার নিয়ে উঠা সম্ভব।
                    </p>
                    <a href="{{ route('properties.flat', ['area' => 'Aftabnagar']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-800">
                        <span>আফতাবনগর রেডি ফ্ল্যাট দেখুন</span> <span>&rarr;</span>
                    </a>
                </div>

                <!-- 2. Ongoing / Under-Construction -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-black text-slate-900 m-0">চলমান ফ্ল্যাট প্রকল্প (Ongoing)</h4>
                        <span class="text-[10px] font-bold text-teal-700 bg-teal-100 px-2 py-0.5 rounded-md">কিস্তি সুবিধা</span>
                    </div>
                    <div class="text-xs text-slate-600 space-y-1">
                        <div><strong>স্কয়ারফুট রেট:</strong> ৳৫,২০০ – ৳৬,৮০০ / বর্গফুট</div>
                        <div><strong>ডাউন পেমেন্ট:</strong> ২০% – ৩০%</div>
                        <div><strong>হস্তান্তর সময়:</strong> ১২ থেকে ২৪ মাস</div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed m-0">
                        হস্তান্তরের সময় পর্যন্ত দীর্ঘমেয়াদী কিস্তিতে মূল্য পরিশোধের সুবিধা থাকে। প্রাথমিক অবস্থায় বুকিং দিলে অভ্যন্তরীণ নকশা কাস্টমাইজ করা যায়।
                    </p>
                    <a href="{{ route('properties.flat', ['area' => 'Aftabnagar']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-teal-700 hover:text-teal-800">
                        <span>চলমান ফ্ল্যাট প্রকল্প দেখুন</span> <span>&rarr;</span>
                    </a>
                </div>

                <!-- 3. Land Share Projects -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-black text-slate-900 m-0">ল্যান্ড শেয়ার প্রকল্প (Land Share)</h4>
                        <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-md">৩০-৩৫% সাশ্রয়</span>
                    </div>
                    <div class="text-xs text-slate-600 space-y-1">
                        <div><strong>প্রতি বর্গফুট খরচ:</strong> ৳৩,৮০০ – ৳৪,৮০০ (অন-কস্ট)</div>
                        <div><strong>শেয়ার মূল্য:</strong> ৳১৮ লাখ – ৳৩৫ লাখ (জমির অংশ)</div>
                        <div><strong>নির্মাণ খরচ:</strong> নির্মাণ কাজ অনুযায়ী ধাপে ধাপে</div>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed m-0">
                        ডেভেলপারের চড়া প্রফিট মার্জিন ছাড়া সরাসরি জমির মালিকানা সাব-কবলা রেজিস্ট্রিসহ নিজের ফ্ল্যাট নির্মাণের সবচেয়ে আধুনিক পদ্ধতি।
                    </p>
                    <a href="{{ route('properties.share', ['area' => 'Aftabnagar']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 hover:text-indigo-800">
                        <span>আফতাবনগর ল্যান্ড শেয়ার দেখুন</span> <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Section 4: Buyer Legal Due Diligence Checklist (4-Point Verification) -->
        <section id="legal-checklist" class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-base">
                    📜
                </span>
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight m-0">
                        আফতাবনগরে জমি বা ফ্ল্যাট কেনার আগে ক্রেতার আইনি চেকলিস্ট
                    </h2>
                    <p class="text-xs text-slate-500 m-0">নিরাপদ ও নিষ্কণ্টক ক্রয়ের জন্য ৪টি মৌলিক ডকুমেন্টেশন যাচাই ধাপ</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black flex items-center justify-center text-xs">১</div>
                    <h4 class="text-xs font-black text-slate-900">ইএইচএল বরাদ্দ ও মূল দলিল</h4>
                    <p class="text-xs text-slate-600 leading-relaxed m-0">
                        ইস্টার্ন হাউজিং লিমিটেডের মূল অ্যালটমেন্ট লেটার, পজেশন লেটার এবং মূল রেজিস্ট্রি সাব-কবলা দলিলের সাথে বায়া দলিলের ধারাবাহিকতা যাচাই করুন।
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black flex items-center justify-center text-xs">২</div>
                    <h4 class="text-xs font-black text-slate-900">হালনাগাদ নামজারি ও ডিসিআর</h4>
                    <p class="text-xs text-slate-600 leading-relaxed m-0">
                        সহকারী কমিশনার (ভূমি) অফিস কর্তৃক প্রদত্ত নামজারি খতিয়ান, ডিসিআর ও প্রস্তাবিত খতিয়ান এন্ট্রি সরকারের ই-নামজারি পোর্টালে অনলাইন ভেরিফাই করুন।
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black flex items-center justify-center text-xs">৩</div>
                    <h4 class="text-xs font-black text-slate-900">ভূমি উন্নয়ন কর (খাজনা)</h4>
                    <p class="text-xs text-slate-600 leading-relaxed m-0">
                        চলতি অর্থবছরের হালনাগাদ ভূমি উন্নয়ন কর রশিদ পরিশোধিত কি-না এবং হোল্ডিং নম্বরে কোনো বকেয়া নেই তা নিশ্চিত করুন।
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black flex items-center justify-center text-xs">৪</div>
                    <h4 class="text-xs font-black text-slate-900">রাজউক ড্যাপ ও নকশা ক্লিয়ারেন্স</h4>
                    <p class="text-xs text-slate-600 leading-relaxed m-0">
                        বিল্ডিং নির্মাণের ক্ষেত্রে রাজউকের ভূমি ব্যবহার ছাড়পত্র, রাস্তার প্রশস্ততার ওপর নির্ধারিত এফএআর (FAR) এবং প্ল্যান অনুমোদন রয়েছে কি-না যাচাই করুন।
                    </p>
                </div>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200">
                <div class="text-xs text-emerald-950 space-y-0.5">
                    <strong>PropertyKoi ভেরিফাইড গ্যারান্টি:</strong>
                    <p class="m-0 text-emerald-800">আমাদের প্ল্যাটফর্মের প্রতিটি লিস্টিং প্রকাশের পূর্বে টিম কর্তৃক প্রাথমিক কাগজপত্র ও সরেজমিন অবস্থান অডিট করা হয়।</p>
                </div>
                <a href="{{ route('calculators.registration') }}" class="shrink-0 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs transition shadow-xs">
                    রেজিস্ট্রেশন ফি হিসাব করুন &rarr;
                </a>
            </div>
        </section>

        <!-- Section 5: Live Verified Properties in Aftabnagar -->
        @if($featuredAftabnagar->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight m-0">
                            আফতাবনগরের সর্বশেষ ভেরিফাইড প্রপার্টি লিস্টিং
                        </h2>
                        <p class="text-xs text-slate-500 m-0">সরাসরি ভেরিফাইড প্রপার্টি দেখুন এবং সাইট ভিজিট শিডিউল করুন</p>
                    </div>
                    <a href="{{ route('properties.index', ['area' => 'Aftabnagar']) }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                        <span>সকল লিস্টিং ({{ $plotsCount + $flatsCount + $sharesCount + $commercialCount }})</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($featuredAftabnagar as $item)
                        @include('components.property-card', ['p' => $item])
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Section 6: Action Widgets (Chahida Match & Valuation) -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Chahida Widget -->
            <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-slate-900 to-emerald-950 text-white space-y-4 shadow-lg flex flex-col justify-between">
                <div class="space-y-2">
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-xs border border-emerald-400/30 inline-block">
                        🎯 বায়ার ডিমান্ড ম্যাচিং
                    </span>
                    <h3 class="text-xl font-black text-white m-0">আফতাবনগরে কাঙ্ক্ষিত প্রপার্টি পাচ্ছেন না?</h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed m-0">
                        আপনার বাজেট ও পছন্দের ব্লকের রিকোয়ারমেন্ট আমাদের চাহিদা পোর্টালে জমা দিন। কাঙ্ক্ষিত শর্ত মিলে গেলে সিস্টেম আপনাকে টেলিগ্রাম ও ইমেইলে স্বয়ংক্রিয় নোটিফিকেশন পাঠাবে।
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('chahida.index', ['area' => 'Aftabnagar']) }}" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs transition shadow-md">
                        <span>আফতাবনগরে চাহিদা জানান</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Valuation Widget -->
            <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-4 flex flex-col justify-between">
                <div class="space-y-2">
                    <span class="px-3 py-1 rounded-full bg-teal-50 text-teal-800 font-bold text-xs border border-teal-200 inline-block">
                        ⚡ এআই ভ্যালুয়েশন টুল
                    </span>
                    <h3 class="text-xl font-black text-slate-900 m-0">আফতাবনগর প্লট বা ফ্ল্যাটের সঠিক দর যাচাই</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed m-0">
                        রোডের প্রশস্ততা, ফ্লোর লেভেল ও ব্লকের অবস্থান ইনপুট দিয়ে বর্তমান বাজারমূল্য সেকেন্ডেই হিসাব করুন সম্পূর্ণ ফ্রিতে।
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('valuation.index') }}" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 rounded-2xl bg-slate-900 hover:bg-emerald-700 text-white font-black text-xs transition shadow-md">
                        <span>স্মার্ট ভ্যালুয়েশন এস্টিমেটর খুলুন</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Section 7: Exhaustive AEO / GEO Buyer FAQs Section -->
        <section id="buyer-faqs" class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-10 shadow-xs space-y-6">
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 uppercase tracking-wider">
                    ❓ বায়ার জিজ্ঞাসা ও সমাধান
                </span>
                <h2 class="text-xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    আফতাবনগর প্রপার্টি নিয়ে সচরাচর জিজ্ঞাসিত প্রশ্নাবলী (Aftabnagar Buyer FAQs)
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    আফতাবনগরে জমি, ফ্ল্যাট বা ল্যান্ড শেয়ার কেনার সময় ক্রেতাদের মনে আসা যাবতীয় বাস্তব প্রশ্নের নির্ভরযোগ্য তথ্যভিত্তিক উত্তর।
                </p>
            </div>

            <div class="space-y-3 pt-4">
                @foreach($ogMeta['faqs'] ?? [] as $index => $faq)
                    <div class="border border-slate-200 rounded-2xl overflow-hidden transition"
                         x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                        <button type="button"
                                @click="open = !open"
                                class="w-full text-left p-4 sm:p-5 bg-slate-50/70 hover:bg-slate-100 flex items-center justify-between gap-4 transition cursor-pointer">
                            <span class="text-xs sm:text-sm font-black text-slate-900 flex items-center gap-2">
                                <span class="text-emerald-600">Q{{ $index + 1 }}.</span>
                                <span>{{ $faq['question'] }}</span>
                            </span>
                            <span class="w-6 h-6 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 text-slate-500 font-black text-xs"
                                  x-text="open ? '−' : '+'"></span>
                        </button>

                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="p-4 sm:p-5 text-xs sm:text-sm text-slate-600 leading-relaxed bg-white border-t border-slate-100">
                            {{ $faq['answer'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Explore Other Prime Locations in Dhaka & Bangladesh -->
        <section class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 m-0">
                        ঢাকা ডিভিশন ও সারা দেশের অন্যান্য জনপ্রিয় প্রপার্টি এলাকা
                    </h3>
                    <p class="text-xs text-slate-500 m-0">আফতাবনগরের পাশাপাশি বসুন্ধরা, পূর্বাচল ও ঢাকার অন্যান্য ভেরিফাইড প্রপার্টি দেখুন</p>
                </div>
                <a href="{{ route('properties.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                    <span>সব লিস্টিং</span> <span>&rarr;</span>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 pt-2">
                <a href="{{ route('properties.land', ['area' => 'Bashundhara']) }}" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-400 hover:bg-emerald-50/40 transition text-center group">
                    <span class="text-lg block mb-1">🏢</span>
                    <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700 block">বসুন্ধরা আ/এ</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">ব্লক A-P প্লট ও ফ্ল্যাট</span>
                </a>
                <a href="{{ route('properties.land', ['area' => 'Purbachal']) }}" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-400 hover:bg-emerald-50/40 transition text-center group">
                    <span class="text-lg block mb-1">🌳</span>
                    <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700 block">পূর্বাচল নতুন শহর</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">রাজউক সেক্টর প্লট</span>
                </a>
                <a href="{{ route('properties.flat', ['area' => 'Gulshan']) }}" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-400 hover:bg-emerald-50/40 transition text-center group">
                    <span class="text-lg block mb-1">💎</span>
                    <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700 block">গুলশান ও বনানী</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">লাক্সারি ফ্ল্যাট</span>
                </a>
                <a href="{{ route('properties.flat', ['area' => 'Uttara']) }}" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-400 hover:bg-emerald-50/40 transition text-center group">
                    <span class="text-lg block mb-1">🚇</span>
                    <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700 block">উত্তরা ও মিরপুর</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">মেট্রোরেল করিডোর</span>
                </a>
                <a href="{{ route('properties.flat', ['area' => 'Dhanmondi']) }}" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-400 hover:bg-emerald-50/40 transition text-center group">
                    <span class="text-lg block mb-1">🏫</span>
                    <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700 block">ধানমন্ডি</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">অভিজাত এলাকা</span>
                </a>
                <a href="{{ route('properties.land', ['area' => 'Savar']) }}" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-emerald-400 hover:bg-emerald-50/40 transition text-center group">
                    <span class="text-lg block mb-1">🏭</span>
                    <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700 block">সাভার ও কেরানীগঞ্জ</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">গ্রোথ ইনভেস্টমেন্ট</span>
                </a>
            </div>
        </section>

    </div>
</div>

<script>
function aftabnagarGuideData() {
    return {
        searchFilter: '',
        filterZone: 'all',
        isBlockVisible(block, nameBn, highlight) {
            // Zone filter
            if (this.filterZone === 'prime') {
                if (!['A', 'B', 'C'].includes(block)) return false;
            } else if (this.filterZone === 'family') {
                if (!['D', 'E', 'F'].includes(block)) return false;
            } else if (this.filterZone === 'growth') {
                if (['A', 'B', 'C', 'D', 'E', 'F'].includes(block)) return false;
            }

            // Search filter
            if (this.searchFilter.trim() !== '') {
                const q = this.searchFilter.trim().toLowerCase();
                const matchBlock = block.toLowerCase().includes(q);
                const matchName = (nameBn || '').toLowerCase().includes(q);
                const matchHighlight = (highlight || '').toLowerCase().includes(q);
                return matchBlock || matchName || matchHighlight;
            }

            return true;
        }
    };
}
</script>
@endsection
