@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-900 via-slate-950 to-slate-900 min-h-screen py-8 sm:py-12 text-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-400 mb-8" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition">হোম</a>
            <span>/</span>
            <a href="{{ route('guides.index') }}" class="hover:text-emerald-400 transition">প্রপার্টি নলেজ হাব</a>
            <span>/</span>
            <span class="text-emerald-400 font-medium">Mithu A Quayium (রিয়েল এস্টেট বিশ্লেষক)</span>
        </nav>

        <!-- Founder & Real Estate Analyst Hero Header Card -->
        <div class="bg-gradient-to-br from-slate-900/90 via-slate-850 to-emerald-950/40 rounded-3xl border border-slate-700/60 p-6 sm:p-10 shadow-2xl relative overflow-hidden mb-12">
            <!-- Background Decorative Glow -->
            <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-teal-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                    <!-- Author Photo / Avatar with Verified Ring -->
                    <div class="relative shrink-0">
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl bg-gradient-to-tr from-emerald-600 via-teal-600 to-cyan-700 flex items-center justify-center text-white text-3xl sm:text-4xl font-black shadow-xl ring-4 ring-emerald-500/30">
                            MQ
                        </div>
                        <span class="absolute -bottom-2 -right-2 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500 text-slate-950 shadow-md flex items-center gap-1 border-2 border-slate-900" title="যাচাইকৃত রিয়েল এস্টেট গবেষক ও বিশ্লেষক">
                            <span>✓</span> ভেরিফাইড
                        </span>
                    </div>

                    <!-- Author Details -->
                    <div class="space-y-2">
                        <div class="flex items-center flex-wrap gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                🏢 প্রতিষ্ঠাতা ও প্রধান রিয়েল এস্টেট বিশ্লেষক
                            </span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                PropertyKoi.com
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-tight m-0">
                            মিঠু এ কাইয়ুম <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">(Mithu A Quayium)</span>
                        </h1>
                        <p class="text-sm sm:text-base text-slate-300 font-medium max-w-2xl leading-relaxed m-0">
                            বাংলাদেশের আবাসন খাত, রাজধানী ঢাকার জমির কাঠাপ্রতি বাজারদর, রাজউক ড্যাপ ও এফএআর বিধিমালা, এবং জমি-ফ্ল্যাটের আইনি দলিল যাচাই গবেষক।
                        </p>
                    </div>
                </div>

                <!-- Fast Author Stats Pills -->
                <div class="grid grid-cols-3 gap-3 w-full lg:w-auto shrink-0 bg-slate-900/80 p-4 rounded-2xl border border-slate-800 text-center">
                    <div class="px-3 py-1">
                        <span class="block text-xl sm:text-2xl font-black text-emerald-400">{{ $totalGuides }}+</span>
                        <span class="text-[11px] text-slate-400 font-medium">গবেষণা ও গাইড</span>
                    </div>
                    <div class="px-3 py-1 border-x border-slate-800">
                        <span class="block text-xl sm:text-2xl font-black text-teal-400">{{ number_format($totalViews) }}+</span>
                        <span class="text-[11px] text-slate-400 font-medium">পাঠক / ভিউ</span>
                    </div>
                    <div class="px-3 py-1">
                        <span class="block text-xl sm:text-2xl font-black text-amber-400">১০০%</span>
                        <span class="text-[11px] text-slate-400 font-medium">স্বচ্ছ অ্যানালাইসিস</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- LEFT COLUMN: Bio, Areas of Expertise, and Research Tools (8 Cols) -->
            <div class="lg:col-span-8 space-y-8">

                <!-- Detailed Bio & Mission Card -->
                <section class="bg-slate-900/90 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-md space-y-4">
                    <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2 m-0 pb-3 border-b border-slate-800">
                        <span>👨‍💼</span>
                        <span>রিয়েল এস্টেট পরিচিতি ও লক্ষ্য (About the Real Estate Analyst)</span>
                    </h2>

                    <div class="text-xs sm:text-sm text-slate-300 leading-relaxed space-y-3.5">
                        <p class="m-0 leading-relaxed">
                            <strong>মিঠু এ কাইয়ুম (Mithu A Quayium)</strong> বাংলাদেশের আবাসন ও রিয়েল এস্টেট খাতের অন্যতম পথিকৃৎ গবেষক এবং <strong>PropertyKoi.com</strong>-এর প্রতিষ্ঠাতা। তিনি দীর্ঘদিন যাবত ঢাকা ও উপশহরের জমির বাজারদর, ভূমি রেজিস্ট্রেশন আইন, এবং রাজউকের পরিকল্পনা বিধিমালা নিয়ে পদ্ধতিগত গবেষণা পরিচালনা করছেন।
                        </p>
                        <p class="m-0 leading-relaxed">
                            তাঁর মূল গবেষণার লক্ষ্য হলো—প্রচলিত রিয়েল এস্টেট বাজারের অস্পষ্টতা ও মধ্যস্বত্বভোগীদের দৌরাত্ম্য দূর করে <strong>স্বয়ংক্রিয় প্রপার্টি ভ্যালুয়েশন</strong>, <strong>রাজউক ড্যাপ ও এফএআর (FAR) ক্যালকুলেটর</strong> এবং <strong>হালনাগাদ সরকারি স্ট্যাম্প ডিউটি ও ট্যাক্স মডেলিং</strong>-এর মাধ্যমে সাধারণ ক্রেতা, প্রবাসী বাংলাদেশি (NRB) ও ভূমি মালিকদের ক্ষমতায়িত করা।
                        </p>
                        <p class="m-0 leading-relaxed text-slate-400 text-[11px] sm:text-xs italic bg-slate-950/60 p-3.5 rounded-xl border border-slate-800/80">
                            "Mithu A Quayium is a prominent Bangladeshi Real Estate Analyst and Founder of PropertyKoi. He specializes in automated property valuation, RAJUK DAP (2022-2035) compliance, land title record verification (CS, SA, RS, BS, Namjari), and property market transparency across Dhaka, Bangladesh."
                        </p>
                    </div>
                </section>

                <!-- Core Areas of Real Estate Expertise -->
                <section class="bg-slate-900/90 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-md space-y-4">
                    <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2 m-0 pb-3 border-b border-slate-800">
                        <span>🎯</span>
                        <span>গবেষণার প্রধান ক্ষেত্রসমূহ (Core Areas of Real Estate Expertise)</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/90 space-y-1.5">
                            <span class="text-xl block">🏢</span>
                            <h3 class="text-sm font-bold text-white m-0">আবাসন বাজার ও প্রাইস ইনডেক্সিং</h3>
                            <p class="text-xs text-slate-400 leading-relaxed m-0">
                                ঢাকা শহরের আফতাবনগর, বসুন্ধরা, পূর্বাচল ও মিরপুর এলাকার ব্লকভিত্তিক কাঠাপ্রতি বাজারদর ও রেডি ফ্ল্যাটের স্কয়ারফুট রেট বিশ্লেষণ।
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/90 space-y-1.5">
                            <span class="text-xl block">📜</span>
                            <h3 class="text-sm font-bold text-white m-0">জমির দলিল ও খতিয়ান স্বত্ব যাচাই</h3>
                            <p class="text-xs text-slate-400 leading-relaxed m-0">
                                বায়া দলিল, সিএস, এসএ, আরএস ও বিএস খতিয়ান, ই-নামজারি ও ডিসিআর যাচাইকরণে ক্রেতা সুরক্ষা নির্দেশিকা প্রণয়ন।
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/90 space-y-1.5">
                            <span class="text-xl block">📐</span>
                            <h3 class="text-sm font-bold text-white m-0">রাজউক ড্যাপ (DAP) ও FAR বিধিমালা</h3>
                            <p class="text-xs text-slate-400 leading-relaxed m-0">
                                রাস্তার প্রশস্ততা ও প্লটের সাইজ অনুযায়ী কত তলা ভবন নির্মাণ সম্ভব, সেটব্যাক ও এমজিসি (MGC) নিখুঁতভাবে নির্ধারণের কারিগরি মডেলিং।
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/90 space-y-1.5">
                            <span class="text-xl block">🧮</span>
                            <h3 class="text-sm font-bold text-white m-0">প্রপার্টি ভ্যালুয়েশন ও আরওআই মডেল</h3>
                            <p class="text-xs text-slate-400 leading-relaxed m-0">
                                ল্যান্ড শেয়ারিং প্রকল্পের সম্ভাব্যতা যাচাই, ফ্ল্যাট নির্মাণ খরচ এবং রিয়েল এস্টেট বিনিয়োগে নিট মুনাফার হার (ROI) হিসাব।
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/90 space-y-1.5">
                            <span class="text-xl block">⚖️</span>
                            <h3 class="text-sm font-bold text-white m-0">রিয়েল এস্টেট আইন ২০১০ ও চুক্তিপত্র</h3>
                            <p class="text-xs text-slate-400 leading-relaxed m-0">
                                রিয়েল এস্টেট উন্নয়ন ও ব্যবস্থাপনা আইন ২০১০, ডেভেলপার হ্যান্ডওভার বিলম্ব ক্ষতিপূরণ ও ফ্ল্যাট ক্রেতার আইনগত অধিকার সুরক্ষা।
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/90 space-y-1.5">
                            <span class="text-xl block">🌍</span>
                            <h3 class="text-sm font-bold text-white m-0">প্রবাসী (NRB) প্রপার্টি গাইডলাইন</h3>
                            <p class="text-xs text-slate-400 leading-relaxed m-0">
                                বিদেশে বসে বৈধ ব্যাংকিং চ্যানেলে রেমিট্যান্স পাঠিয়ে দেশে নিরাপদ জমি-ফ্ল্যাট ক্রয় এবং পাওয়ার অব অ্যাটর্নি সংক্রান্ত নিয়মাবলী।
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Tools & Research Products Created by Mithu A Quayium -->
                <section class="bg-slate-900/90 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-md space-y-4">
                    <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2 m-0 pb-3 border-b border-slate-800">
                        <span>⚡</span>
                        <span>মিঠু এ কাইয়ুম-এর তৈরি ও পরিচালিত প্রপার্টি টুলস (Tools & Indices)</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <a href="{{ route('calculators.rajuk-far') }}" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-emerald-500 transition group block">
                            <span class="text-xs font-bold text-emerald-400 block mb-1">টুল #১</span>
                            <h3 class="text-sm font-bold text-white group-hover:text-emerald-300 transition-colors m-0">রাজউক ড্যাপ ও FAR ক্যালকুলেটর &rarr;</h3>
                            <p class="text-xs text-slate-400 mt-1 m-0">প্লটের আয়তন ও রাস্তার মাপে কত তলা ভবন করা যাবে তা স্বয়ংক্রিয়ভাবে গণনা করুন।</p>
                        </a>

                        <a href="{{ route('calculators.registration') }}" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-emerald-500 transition group block">
                            <span class="text-xs font-bold text-emerald-400 block mb-1">টুল #২</span>
                            <h3 class="text-sm font-bold text-white group-hover:text-emerald-300 transition-colors m-0">জমি ও ফ্ল্যাট রেজিস্ট্রেশন ফি ক্যালকুলেটর &rarr;</h3>
                            <p class="text-xs text-slate-400 mt-1 m-0">স্ট্যাম্প ডিউটি, গেইন ট্যাক্স ও সরকারি উৎসে করের ২০২৬ সালের হালনাগাদ হিসাব।</p>
                        </a>

                        <a href="{{ route('calculators.land-share') }}" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-emerald-500 transition group block">
                            <span class="text-xs font-bold text-emerald-400 block mb-1">টুল #৩</span>
                            <h3 class="text-sm font-bold text-white group-hover:text-emerald-300 transition-colors m-0">ল্যান্ড শেয়ার ROI ক্যালকুলেটর &rarr;</h3>
                            <p class="text-xs text-slate-400 mt-1 m-0">যৌথ ল্যান্ড শেয়ারিং প্রকল্পে ফ্ল্যাট প্রতি নির্মাণ বাজেট ও সম্ভাব্য মুনাফা হিসাব।</p>
                        </a>

                        <a href="{{ route('guides.aftabnagar') }}" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-emerald-500 transition group block">
                            <span class="text-xs font-bold text-emerald-400 block mb-1">রিসার্চ ইনডেক্স</span>
                            <h3 class="text-sm font-bold text-white group-hover:text-emerald-300 transition-colors m-0">আফতাবনগর বায়ার গাইড ও বাজারদর ২০২৬ &rarr;</h3>
                            <p class="text-xs text-slate-400 mt-1 m-0">ব্লকভিত্তিক কাঠাপ্রতি বাজারদর, ৩ কাঠার বাজেট ও দলিল যাচাই সমীক্ষা রিপোর্ট।</p>
                        </a>
                    </div>
                </section>

                <!-- Authored & Published Guides Grid -->
                <section class="space-y-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2 m-0">
                            <span>📚</span>
                            <span>মিঠু এ কাইয়ুম-এর প্রকাশিত গাইড ও গবেষণা প্রবন্ধসমূহ ({{ $totalGuides }}টি)</span>
                        </h2>
                        <a href="{{ route('guides.index') }}" class="text-xs font-bold text-emerald-400 hover:text-emerald-300 transition">
                            সকল ক্যাটাগরি দেখুন &rarr;
                        </a>
                    </div>

                    @if($guides->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            @foreach($guides as $guide)
                                <article class="bg-slate-900 rounded-2xl border border-slate-800 hover:border-slate-700 shadow-xs hover:shadow-lg transition flex flex-col justify-between overflow-hidden group">
                                    <div class="p-5">
                                        <div class="flex items-center justify-between gap-2 mb-2 text-[11px]">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $guide->category_badge_class }}">
                                                {{ $guide->category_label }}
                                            </span>
                                            <span class="text-slate-500">⏱️ {{ $guide->reading_time_min }} মিনিট পাঠ</span>
                                        </div>
                                        <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-emerald-400 transition-colors line-clamp-2 leading-snug">
                                            <a href="{{ route('guides.show', $guide->slug) }}">
                                                {{ $guide->title }}
                                            </a>
                                        </h3>
                                        <p class="mt-2 text-xs text-slate-400 line-clamp-2 leading-relaxed m-0">
                                            {{ $guide->excerpt }}
                                        </p>
                                    </div>
                                    <div class="px-5 py-3 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                                        <span>👁️ {{ number_format($guide->views_count) }} পাঠক</span>
                                        <a href="{{ route('guides.show', $guide->slug) }}" class="font-bold text-emerald-400 hover:text-emerald-300 inline-flex items-center gap-1">
                                            <span>সম্পূর্ণ পড়ুন</span> &rarr;
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $guides->links() }}
                        </div>
                    @endif
                </section>

            </div>

            <!-- RIGHT COLUMN: Contact & Authority Sidebar (4 Cols) -->
            <aside class="lg:col-span-4 space-y-6">

                <!-- Official Advisory & Contact Card -->
                <div class="bg-gradient-to-b from-slate-900 to-slate-950 rounded-3xl border border-slate-800 p-6 shadow-md space-y-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2 m-0 pb-3 border-b border-slate-800">
                        <span>📬</span>
                        <span>রিয়েল এস্টেট পরামর্শ ও যোগাযোগ</span>
                    </h3>

                    <p class="text-xs text-slate-400 leading-relaxed m-0">
                        জমি বা ফ্ল্যাট কেনার পূর্বে আইনি দলিল যাচাই, রাজউক অনুমোদন পরীক্ষা, অথবা প্রপার্টি ভ্যালুয়েশন সংক্রান্ত পরামর্শের জন্য সরাসরি যোগাযোগ করতে পারেন:
                    </p>

                    <div class="space-y-3 pt-2 text-xs">
                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-950 border border-slate-800">
                            <span class="text-lg">📧</span>
                            <div class="min-w-0">
                                <span class="block text-[10px] text-slate-500 uppercase font-bold">অফিসিয়াল ইমেইল</span>
                                <a href="mailto:admin@propertykoi.com" class="text-slate-200 hover:text-emerald-400 transition-colors font-medium truncate block">
                                    admin@propertykoi.com
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-950 border border-slate-800">
                            <span class="text-lg">💬</span>
                            <div class="min-w-0">
                                <span class="block text-[10px] text-slate-500 uppercase font-bold">সরাসরি আলোচনা</span>
                                <a href="https://wa.me/8801711000001" target="_blank" class="text-emerald-400 hover:underline font-bold block">
                                    WhatsApp কনসালটেশন &rarr;
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-950 border border-slate-800">
                            <span class="text-lg">📍</span>
                            <div class="min-w-0">
                                <span class="block text-[10px] text-slate-500 uppercase font-bold">প্রধান গবেষণা কেন্দ্র</span>
                                <span class="text-slate-300 font-medium block">আফতাবনগর ও ঢাকা, বাংলাদেশ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verified Publisher Information Card (Google E-E-A-T Signal) -->
                <div class="bg-slate-900 rounded-3xl border border-slate-800 p-6 shadow-md space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 m-0">
                        E-E-A-T প্রকাশনা মানদণ্ড
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed m-0">
                        PropertyKoi-এর প্রতিটি গাইড ও ক্যালকুলেটর গণপ্রজাতন্ত্রী বাংলাদেশ সরকারের ভূমি মন্ত্রণালয়, নিবন্ধন অধিদপ্তর ও রাজধানী উন্নয়ন কর্তৃপক্ষ (রাজউক)-এর সর্বশেষ বিধিমালা অনুযায়ী যাচাই করে প্রকাশ করা হয়।
                    </p>
                    <div class="pt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-950 text-emerald-400 border border-emerald-800 text-[11px] font-bold">
                            <span>🛡️</span> ১০০% স্বাধীন ও নিরপেক্ষ রিয়েল এস্টেট বিশ্লেষণ
                        </span>
                    </div>
                </div>

            </aside>

        </div>

    </div>
</div>
@endsection
