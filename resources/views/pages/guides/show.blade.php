@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center flex-wrap gap-2 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">হোম</a>
            <span>/</span>
            <a href="{{ route('guides.index') }}" class="hover:text-emerald-600 transition">প্রপার্টি গাইডস</a>
            <span>/</span>
            <a href="{{ route('guides.index', ['category' => $guide->category]) }}" class="hover:text-emerald-600 transition">{{ $guide->category_label }}</a>
            <span>/</span>
            <span class="text-slate-800 font-medium truncate max-w-[200px] sm:max-w-md">{{ $guide->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

            <!-- Main Article Column (8 Cols) -->
            <article class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-10 shadow-xs">

                <!-- Meta Header -->
                <header class="mb-8 pb-8 border-b border-slate-100">
                    <div class="flex items-center flex-wrap gap-2.5 mb-4">
                        <span class="px-3 py-1 rounded-lg text-xs font-bold border {{ $guide->category_badge_class }}">
                            {{ $guide->category_label }}
                        </span>
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <span>⏱️</span> {{ $guide->reading_time_min }} মিনিট পড়ার সময়
                        </span>
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <span>👁️</span> {{ number_format($guide->views_count) }} বার পঠিত
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-snug">
                        {{ $guide->title }}
                    </h1>

                    @if(!empty($guide->excerpt))
                        <p class="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed font-normal bg-slate-50 p-4 rounded-2xl border-l-4 border-emerald-500">
                            {{ $guide->excerpt }}
                        </p>
                    @endif

                    <div class="mt-6 flex items-center justify-between flex-wrap gap-4 pt-4 border-t border-slate-100 text-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">
                                ✍️
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block text-sm">{{ $guide->author_name ?: 'PropertyKoi Research Team' }}</span>
                                <span class="text-slate-400">আপডেট: {{ $guide->formatted_date }}</span>
                            </div>
                        </div>

                        <!-- Social Share Pill -->
                        <div class="flex items-center gap-2" x-data="{ copied: false }">
                            <span class="text-slate-400 text-xs font-semibold mr-1">শেয়ার:</span>
                            <a href="https://api.whatsapp.com/send?text={{ urlencode($guide->title . ' ' . url()->current()) }}"
                               target="_blank"
                               rel="noopener"
                               class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition flex items-center justify-center font-bold text-xs"
                               title="WhatsApp এ শেয়ার করুন">
                                WA
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                               target="_blank"
                               rel="noopener"
                               class="w-8 h-8 rounded-full bg-sky-50 text-sky-600 hover:bg-sky-600 hover:text-white transition flex items-center justify-center font-bold text-xs"
                               title="Facebook এ শেয়ার করুন">
                                FB
                            </a>
                            <button type="button"
                                    @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2500)"
                                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition text-xs font-semibold flex items-center gap-1 cursor-pointer">
                                <span x-show="!copied">🔗 লিংক কপি</span>
                                <span x-show="copied" class="text-emerald-700 font-bold">✅ কপি হয়েছে!</span>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                @if(!empty($guide->cover_image))
                    <div class="mb-8 rounded-2xl overflow-hidden bg-slate-900 shadow-sm aspect-video">
                        <img src="{{ $guide->cover_url }}"
                             alt="{{ $guide->title }}"
                             class="w-full h-full object-cover">
                    </div>
                @endif

                <!-- Article Rich Content Body -->
                <div class="guide-content-body prose prose-slate sm:prose-lg max-w-none prose-headings:font-black prose-headings:tracking-tight prose-headings:text-slate-900 prose-h2:text-xl sm:prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4 prose-p:leading-relaxed prose-p:text-slate-700 prose-li:text-slate-700 prose-table:text-sm prose-th:bg-slate-100 prose-th:p-3 prose-td:p-3 prose-a:text-emerald-600 prose-a:font-bold hover:prose-a:text-emerald-700">
                    {!! $guide->content !!}
                </div>

                <!-- AEO FAQ Section for this Guide -->
                @if(!empty($guide->faqs) && is_array($guide->faqs))
                    <section class="mt-12 pt-8 border-t border-slate-200">
                        <div class="flex items-center gap-2 mb-6">
                            <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">💡</span>
                            <div>
                                <h3 class="text-xl font-black text-slate-900">গাইড সংক্রান্ত সাধারণ জিজ্ঞাসা (FAQ)</h3>
                                <p class="text-xs text-slate-500">আইনি ও সরকারি নিয়মাবলী সম্পর্কে সুস্পষ্ট ব্যাখ্যা</p>
                            </div>
                        </div>

                        <div class="space-y-3" x-data="{ openFaq: 0 }">
                            @foreach($guide->faqs as $i => $faq)
                                @if(!empty($faq['question']) && !empty($faq['answer']))
                                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50">
                                        <button type="button"
                                                @click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}"
                                                class="w-full text-left p-4 sm:p-5 font-bold text-sm sm:text-base text-slate-900 flex items-center justify-between gap-4 cursor-pointer hover:text-emerald-700 transition">
                                            <span>{{ $faq['question'] }}</span>
                                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === {{ $i }} ? 'rotate-180 text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="openFaq === {{ $i }}" x-collapse class="px-4 sm:px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                                            {!! nl2br(e($faq['answer'])) !!}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Legal Disclaimer & Editorial Transparency -->
                <div class="mt-10 p-5 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs sm:text-sm leading-relaxed flex items-start gap-3">
                    <span class="text-lg shrink-0">⚖️</span>
                    <div>
                        <strong class="font-bold block mb-0.5">আইনি ও সম্পাদকীয় ঘোষণা:</strong>
                        এই গাইডে উল্লেখিত তথ্যাদি ভূমি মন্ত্রণালয়, জাতীয় রাজস্ব বোর্ড (NBR), এবং রাজউকের বিধিমালা ও বিশেষজ্ঞদের বিশ্লেষণ অনুযায়ী সংকলিত। যেকোনো জমি বা ফ্ল্যাট কেনার চূড়ান্ত সিদ্ধান্ত গ্রহণের আগে সংশ্লিষ্ট সাব-রেজিস্ট্রি অফিস বা একজন বিজ্ঞ আইনজীবীর মাধ্যমে কাগজপত্র সরেজমিন যাচাই করার পরামর্শ দেওয়া হচ্ছে।
                    </div>
                </div>

                <!-- Article Navigation / Bottom CTAs -->
                <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between flex-wrap gap-4">
                    <a href="{{ route('guides.index') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-emerald-600 hover:text-emerald-700">
                        &larr; <span>সকল গাইডে ফিরে যান</span>
                    </a>
                    @auth
                    <a href="{{ route('property.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
                        <span>বিনামূল্যে আপনার প্রপার্টি বিজ্ঞাপন দিন</span> &rarr;
                    </a>
                    @endauth
                </div>

            </article>

            <!-- Sidebar Column (4 Cols) -->
            <aside class="lg:col-span-4 space-y-6">

                <!-- Interactive Calculators Widget Card -->
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden">
                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-white/20 text-white uppercase tracking-wider block w-max mb-3">
                        স্মার্ট ক্যালকুলেটর
                    </span>
                    <h3 class="text-lg font-black leading-snug">
                        রেজিস্ট্রেশন বা ল্যান্ড শেয়ারের সাশ্রয় হিসাব করুন
                    </h3>
                    <p class="mt-2 text-xs text-emerald-100 leading-relaxed">
                        স্ট্যাম্প ডিউটি, ফি ও উৎসে করের সরকারি ২০২৬ তালিকা অনুযায়ী নিখুঁত হিসাব করুন মাত্র ১ মিনিটে।
                    </p>
                    <div class="mt-5 space-y-2">
                        <a href="{{ route('calculators.registration') }}" class="w-full py-2.5 px-4 rounded-xl bg-white text-emerald-800 hover:bg-emerald-50 text-xs font-bold transition flex items-center justify-between shadow-xs">
                            <span>🧮 রেজিস্ট্রেশন ফি ক্যালকুলেটর</span>
                            <span>&rarr;</span>
                        </a>
                        <a href="{{ route('calculators.land-share') }}" class="w-full py-2.5 px-4 rounded-xl bg-emerald-800/80 hover:bg-emerald-800 text-white text-xs font-bold transition flex items-center justify-between border border-emerald-500/40">
                            <span>📊 ল্যান্ড শেয়ার ROI ক্যালকুলেটর</span>
                            <span>&rarr;</span>
                        </a>
                        <a href="{{ route('valuation.index') }}" class="w-full py-2.5 px-4 rounded-xl bg-teal-800/80 hover:bg-teal-800 text-white text-xs font-bold transition flex items-center justify-between border border-teal-500/40">
                            <span>💡 প্রপার্টি মার্কেট ভ্যালু যাচাই</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- Contextual Live Properties for Sale -->
                @if($featuredProperties->count() > 0)
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                <span>🏢</span> যাচাইকৃত ভেরিফাইড প্রপার্টি
                            </h3>
                            <a href="{{ route('properties.index') }}" class="text-[11px] font-bold text-emerald-600 hover:underline">সকল দেখুন</a>
                        </div>
                        <div class="space-y-3.5">
                            @foreach($featuredProperties as $prop)
                                <a href="{{ route('property.show', $prop->slug ?: $prop->id) }}" class="flex items-center gap-3 group p-2 rounded-xl hover:bg-slate-50 transition">
                                    <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden shrink-0 relative">
                                        <img src="{{ $prop->cover_url }}" alt="{{ $prop->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[10px] font-bold text-emerald-600 block">{{ $prop->type_label }} • {{ $prop->area ?: 'ঢাকা' }}</span>
                                        <h4 class="text-xs font-bold text-slate-800 truncate group-hover:text-emerald-600 transition-colors">{{ $prop->title }}</h4>
                                        <span class="text-xs font-black text-slate-900 mt-0.5 block">{{ $prop->formatted_price ?: 'আলোচনা সাপেক্ষে' }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Related Guides in Same Category -->
                @if($relatedGuides->count() > 0)
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-1.5">
                            <span>📖</span> সম্পর্কিত গাইড ও পরামর্শ
                        </h3>
                        <div class="space-y-4">
                            @foreach($relatedGuides as $rg)
                                <article class="border-b border-slate-100 last:border-b-0 pb-3 last:pb-0">
                                    <span class="text-[10px] font-semibold text-slate-400 block mb-1">
                                        {{ $rg->category_label }} • {{ $rg->reading_time_min }} মিনিট
                                    </span>
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-800 leading-snug hover:text-emerald-600 transition-colors">
                                        <a href="{{ route('guides.show', $rg->slug) }}">{{ $rg->title }}</a>
                                    </h4>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Buyer Demand Callout -->
                <div class="bg-slate-900 rounded-3xl p-6 text-white shadow-xs">
                    <span class="text-2xl block mb-2">🎯</span>
                    <h3 class="text-base font-black">কাঙ্ক্ষিত জমি বা ফ্ল্যাট খুঁজে পাচ্ছেন না?</h3>
                    <p class="mt-1.5 text-xs text-slate-300 leading-relaxed">
                        আপনার বাজেট ও পছন্দের চাহিদা সাবমিট করুন। সরাসরি মালিকের নতুন প্রপার্টি পেলেই তাৎক্ষণিক মেসেজ পাবেন।
                    </p>
                    <a href="{{ route('chahida.index') }}" class="mt-4 block w-full py-2.5 text-center rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">
                        চাহিদা জমা দিন (বিনামূল্যে)
                    </a>
                </div>

            </aside>

        </div>

    </div>
</div>
@endsection
