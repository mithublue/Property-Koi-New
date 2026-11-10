@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">হোম</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">প্রপার্টি ক্যালকুলেটর হাব</span>
        </nav>

        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-3 shadow-2xs">
                <span>🧮</span> ইন্টারেক্টিভ ফাইন্যান্সিয়াল টুলস
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                জমি ও ফ্ল্যাটের সরকারি খরচ ও <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600">সাশ্রয় ক্যালকুলেটর</span>
            </h1>
            <p class="mt-3 text-sm sm:text-base text-slate-600 leading-relaxed">
                ২০২৬ সালের সরকারি সর্বশেষ স্ট্যাম্প ডিউটি ও ট্যাক্স আইন অনুযায়ী জমি ও ফ্ল্যাট রেজিস্ট্রেশন খরচ বের করুন এবং ল্যান্ড শেয়ার প্রকল্পের সম্ভাব্য লাভ যাচাই করুন সেকেন্ডের মধ্যে।
            </p>
        </div>

        <!-- 4 Feature Tool Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">

            <!-- Card 1: Registration Fee Calculator -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs hover:shadow-xl transition-all flex flex-col justify-between group">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-xs">
                        📜
                    </div>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase tracking-wider block w-max mb-2">
                        সরকারি ২০২৬ আপডেট
                    </span>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-emerald-600 transition-colors">
                        রেজিস্ট্রেশন ফি ও ট্যাক্স ক্যালকুলেটর
                    </h2>
                    <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                        স্ট্যাম্প শুল্ক (১.৫%), নিবন্ধন ফি (১%), স্থানীয় সরকার কর (২-৩%) ও উৎসে কর (AIT - ধারা ১২৫/৫৩এইচ) সহ মোট সরকারি খরচের পুঙ্খানুপুঙ্খ হিসাব করুন।
                    </p>
                    <ul class="mt-4 space-y-1.5 text-xs text-slate-600">
                        <li class="flex items-center gap-1.5">
                            <span class="text-emerald-600 font-bold text-[11px]">✓</span> ঢাকা ও চট্টগ্রাম সিটি কর্পোরেশন
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-emerald-600 font-bold text-[11px]">✓</span> জেলা পৌরসভা ও ইউনিয়ন পরিষদ
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-emerald-600 font-bold text-[11px]">✓</span> তাৎক্ষণিক প্রিন্ট ও ডাউনলোড সুবিধা
                        </li>
                    </ul>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <a href="{{ route('calculators.registration') }}" class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md transition flex items-center justify-center gap-1.5">
                        <span>খরচ গণনা করুন</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Card 2: RAJUK DAP & FAR Calculator -->
            <div class="bg-white rounded-3xl border border-indigo-200/90 p-5 sm:p-6 shadow-xs hover:shadow-xl transition-all flex flex-col justify-between group relative overflow-hidden">
                <div class="absolute -right-6 -top-6 w-20 h-20 bg-indigo-50 rounded-full blur-xl"></div>
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-800 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-xs">
                        📐
                    </div>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-200 uppercase tracking-wider block w-max mb-2">
                        নতুন ড্যাপ বিধিমালা
                    </span>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-indigo-600 transition-colors">
                        রাজউক ড্যাপ ও FAR ক্যালকুলেটর
                    </h2>
                    <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                        প্লটের মাপ, রাস্তার চওড়া ও এলাকার ওপর ভিত্তি করে ভবনের অনুমোদনযোগ্য তলা, মোট ফ্লোর স্পেস ও ফ্ল্যাট ইউনিট সংখ্যা যাচাই করুন।
                    </p>
                    <ul class="mt-4 space-y-1.5 text-xs text-slate-600">
                        <li class="flex items-center gap-1.5">
                            <span class="text-indigo-600 font-bold text-[11px]">✓</span> বসুন্ধরা, আফতাবনগর ও গুলশান জোন
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-indigo-600 font-bold text-[11px]">✓</span> ইউনিয়ন পরিষদ মোড (আটি হাউজিং)
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-indigo-600 font-bold text-[11px]">✓</span> গ্রাউন্ড কভারেজ ও সেটব্যাক হিসাব
                        </li>
                    </ul>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <a href="{{ route('calculators.rajuk-far') }}" class="w-full py-2.5 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition flex items-center justify-center gap-1.5">
                        <span>FAR ও তলা হিসাব করুন</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Card 3: Land Share ROI Calculator -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs hover:shadow-xl transition-all flex flex-col justify-between group">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-800 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-xs">
                        📊
                    </div>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200 uppercase tracking-wider block w-max mb-2">
                        বিনিয়োগ ও সাশ্রয়
                    </span>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-sky-600 transition-colors">
                        ল্যান্ড শেয়ার ROI ও সেভিংস ক্যালকুলেটর
                    </h2>
                    <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                        রেডিমেড ফ্ল্যাটের সাথে যৌথ ল্যান্ড শেয়ার প্রকল্পে ফ্ল্যাট নির্মাণের ব্যয় তুলনা করে কত লাখ টাকা সাশ্রয় ও কত শতাংশ লাভ হবে তা বের করুন।
                    </p>
                    <ul class="mt-4 space-y-1.5 text-xs text-slate-600">
                        <li class="flex items-center gap-1.5">
                            <span class="text-sky-600 font-bold text-[11px]">✓</span> ফ্ল্যাটের সাইজ ও শেয়ার মূল্য সমন্বয়
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-sky-600 font-bold text-[11px]">✓</span> নির্মাণ ব্যয় ও ইউটিলিটি খরচ বিশ্লেষণ
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-sky-600 font-bold text-[11px]">✓</span> গড় ২৫% থেকে ৩৫% সম্ভাব্য সেভিংস
                        </li>
                    </ul>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <a href="{{ route('calculators.land-share') }}" class="w-full py-2.5 px-3 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md transition flex items-center justify-center gap-1.5">
                        <span>সাশ্রয় হিসাব করুন</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Card 4: Property Valuation Estimator -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs hover:shadow-xl transition-all flex flex-col justify-between group">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-xs">
                        💡
                    </div>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 uppercase tracking-wider block w-max mb-2">
                        স্মার্ট এস্টিমেটর
                    </span>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-amber-600 transition-colors">
                        স্মার্ট প্রপার্টি ভ্যালুয়েশন এস্টিমেটর
                    </h2>
                    <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                        আফতাবনগর, বসুন্ধরা, গুলশান ও ঢাকার বিভিন্ন ব্লকে রাস্তা, ফ্লোর লেভেল ও ফেসিং অনুযায়ী জমি বা ফ্ল্যাটের বর্তমান ফেয়ার মার্কেট মূল্য রেঞ্জ জানুন।
                    </p>
                    <ul class="mt-4 space-y-1.5 text-xs text-slate-600">
                        <li class="flex items-center gap-1.5">
                            <span class="text-amber-600 font-bold text-[11px]">✓</span> ২০-৬০ ফুট রোড ও কর্নার ফ্যাক্টর
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-amber-600 font-bold text-[11px]">✓</span> ফ্ল্যাটের ফ্লোর ও পার্কিং প্রিমিয়াম
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-amber-600 font-bold text-[11px]">✓</span> শতভাগ স্বচ্ছ ও দালালমুক্ত প্রাইসিং
                        </li>
                    </ul>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <a href="{{ route('valuation.index') }}" class="w-full py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md transition flex items-center justify-center gap-1.5">
                        <span>মার্কেট দর যাচাই করুন</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- AEO FAQ Section -->
        @if(!empty($ogMeta['faqs']))
            <section class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">FAQ / প্রশ্নোত্তর</span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-2">প্রপার্টি ক্যালকুলেটর সংক্রান্ত সাধারণ জিজ্ঞাসা</h2>
                </div>
                <div class="space-y-3" x-data="{ active: null }">
                    @foreach($ogMeta['faqs'] as $index => $faq)
                        <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50">
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
