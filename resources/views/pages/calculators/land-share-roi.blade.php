@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12"
     x-data="{
         flatSize: 1400,
         landSharePriceLakh: 26,
         constructionRate: {{ (int) \App\Models\Setting::get('calc_landshare_construction_rate', 2800) }},
         utilityLakh: 5.5,
         marketRate: {{ (int) \App\Models\Setting::get('calc_landshare_market_rate', 7500) }},

         // Calculations
         get landShareCost() {
             return this.landSharePriceLakh * 100000;
         },
         get constructionCost() {
             return this.flatSize * this.constructionRate;
         },
         get utilityCost() {
             return this.utilityLakh * 100000;
         },
         get totalLandShareProjectCost() {
             return this.landShareCost + this.constructionCost + this.utilityCost;
         },
         get readyFlatMarketValue() {
             return this.flatSize * this.marketRate;
         },
         get netSavings() {
             return Math.max(0, this.readyFlatMarketValue - this.totalLandShareProjectCost);
         },
         get savingsPercent() {
             return this.readyFlatMarketValue > 0
                 ? Math.max(0, ((this.netSavings / this.readyFlatMarketValue) * 100)).toFixed(1)
                 : 0;
         },
         get effectiveCostPerSqft() {
             return this.flatSize > 0
                 ? Math.round(this.totalLandShareProjectCost / this.flatSize)
                 : 0;
         },

         formatCurrency(val) {
             return new Intl.NumberFormat('bn-BD').format(Math.round(val)) + ' ৳';
         },
         formatLakhs(val) {
             let l = val / 100000;
             if (l >= 100) {
                 return (l / 100).toFixed(2) + ' কোটি টাকা';
             }
             return l.toFixed(2) + ' লাখ টাকা';
         }
     }">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">হোম</a>
            <span>/</span>
            <a href="{{ route('calculators.index') }}" class="hover:text-emerald-600 transition">ক্যালকুলেটর</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">ল্যান্ড শেয়ার ROI ও সেভিংস ক্যালকুলেটর</span>
        </nav>

        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto mb-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200 mb-3 shadow-2xs">
                <span>📊</span> স্মার্ট রিয়েল এস্টেট বিনিয়োগ টুল
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                ল্যান্ড শেয়ার প্রকল্পে <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-600 to-teal-600">কত টাকা সাশ্রয় হবে?</span>
            </h1>
            <p class="mt-3 text-xs sm:text-base text-slate-600 leading-relaxed">
                ডেভেলপারের রেডি ফ্ল্যাটের সাথে যৌথ ল্যান্ড শেয়ার প্রকল্পের খরচ তুলনা করে কত লক্ষ টাকা সাশ্রয় ও কত শতাংশ ক্যাপিটাল গেইন হবে তা তাৎক্ষণিক হিসাব করুন।
            </p>
        </div>

        <!-- Calculator Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Inputs Card (5 Cols) -->
            <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">
                <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <span>⚙️</span> প্রজেক্টের প্যারামিটার দিন
                </h3>

                <!-- Flat Size Slider & Input -->
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <span>কাঙ্ক্ষিত ফ্ল্যাটের সাইজ (বর্গফুট)</span>
                        <span class="text-sky-600 font-black text-sm" x-text="flatSize + ' sqft'"></span>
                    </div>
                    <input type="range"
                           min="800"
                           max="3000"
                           step="50"
                           x-model.number="flatSize"
                           class="w-full accent-sky-600 cursor-pointer">
                    <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                        <span>৮০০ sqft</span>
                        <span>১৪০০ sqft</span>
                        <span>২০০০ sqft</span>
                        <span>৩০০০ sqft</span>
                    </div>
                </div>

                <!-- Land Share Price -->
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <span>জমির শেয়ার বাবদ খরচ (লাখ টাকায়)</span>
                        <span class="text-sky-600 font-black text-sm" x-text="landSharePriceLakh + ' লাখ ৳'"></span>
                    </div>
                    <input type="range"
                           min="10"
                           max="80"
                           step="0.5"
                           x-model.number="landSharePriceLakh"
                           class="w-full accent-sky-600 cursor-pointer">
                    <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                        <span>১০ লাখ</span>
                        <span>২৫ লাখ</span>
                        <span>৫০ লাখ</span>
                        <span>৮০ লাখ</span>
                    </div>
                </div>

                <!-- Estimated Construction Cost / sqft -->
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <span>নির্মাণ খরচ প্রতি বর্গফুট (টাকায়)</span>
                        <span class="text-slate-900 font-bold" x-text="constructionRate + ' ৳/sqft'"></span>
                    </div>
                    <input type="range"
                           min="2000"
                           max="4500"
                           step="100"
                           x-model.number="constructionRate"
                           class="w-full accent-sky-600 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">গড় রেসিডেন্সিয়াল স্ট্যান্ডার্ড: ২,৬০০ - ৩,২০০ টাকা/sqft (রাজউক গ্রেড)।</p>
                </div>

                <!-- Utility, Lift & Generator Cost -->
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <span>ইউটিলিটি, লিফট ও বিবিধ (লাখ টাকায়)</span>
                        <span class="text-slate-900 font-bold" x-text="utilityLakh + ' লাখ ৳'"></span>
                    </div>
                    <input type="range"
                           min="2"
                           max="15"
                           step="0.5"
                           x-model.number="utilityLakh"
                           class="w-full accent-sky-600 cursor-pointer">
                </div>

                <!-- Ready Flat Market Rate in Same Area -->
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <span>একই এলাকায় রেডি ফ্ল্যাটের বর্তমান দর</span>
                        <span class="text-rose-600 font-black text-sm" x-text="marketRate + ' ৳/sqft'"></span>
                    </div>
                    <input type="range"
                           min="4500"
                           max="16000"
                           step="250"
                           x-model.number="marketRate"
                           class="w-full accent-rose-600 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">আফতাবনগর ও বসুন্ধরা রেডি ফ্ল্যাট: ৭,০০০ - ৯,৫০০ টাকা/sqft</p>
                </div>

                <!-- Guide CTA -->
                <div class="p-4 rounded-2xl bg-sky-50 border border-sky-100 text-xs text-sky-900">
                    <span class="font-bold block mb-1">📖 ল্যান্ড শেয়ারে নিরাপদ থাকার নিয়ম:</span>
                    <p>যৌথ প্রকল্পে জমি রেজিস্ট্রি ও পাওয়ার অব অ্যাটর্নির নিয়ম জানতে আমাদের ডেডিকেটেড গাইডটি পড়ুন।</p>
                    <a href="{{ route('guides.show', 'land-share-project-investment-rules-and-safety') }}" class="font-bold text-sky-700 hover:underline block mt-1">
                        ল্যান্ড শেয়ার গাইড দেখুন &rarr;
                    </a>
                </div>

            </div>

            <!-- Savings Results Card (7 Cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">

                <!-- Grand Savings Banner -->
                <div class="bg-gradient-to-br from-slate-900 via-sky-950 to-slate-900 rounded-2xl p-6 text-white shadow-md relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs text-sky-300 font-semibold mb-1">
                        <span>আপনার মোট আনুমানিক সাশ্রয়</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold" x-text="savingsPercent + '% সাশ্রয়'"></span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-emerald-400 tracking-tight" x-text="formatLakhs(netSavings)"></div>
                    <div class="mt-3 text-xs text-slate-300 flex items-center justify-between flex-wrap gap-2 pt-2 border-t border-white/10">
                        <span>কার্যকরী দর: <strong class="text-white" x-text="effectiveCostPerSqft + ' ৳/sqft'"></strong> (ল্যান্ড শেয়ারে)</span>
                        <span>বাজার দর: <span class="text-rose-400 line-through" x-text="marketRate + ' ৳/sqft'"></span></span>
                    </div>
                </div>

                <!-- Comparison Visual Bar -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">খরচ তুলনা ম্যাট্রিক্স</h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-700">ল্যান্ড শেয়ার প্রকল্পে ফ্ল্যাটের মোট খরচ:</span>
                                <span class="font-bold text-sky-600" x-text="formatCurrency(totalLandShareProjectCost) + ' (' + formatLakhs(totalLandShareProjectCost) + ')'"></span>
                            </div>
                            <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-sky-500 to-teal-500 rounded-full transition-all duration-300"
                                     :style="'width: ' + ((totalLandShareProjectCost / readyFlatMarketValue) * 100) + '%'"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-700">একই সাইজের ডেভলপার রেডি ফ্ল্যাটের মূল্য:</span>
                                <span class="font-bold text-rose-600" x-text="formatCurrency(readyFlatMarketValue) + ' (' + formatLakhs(readyFlatMarketValue) + ')'"></span>
                            </div>
                            <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-rose-500 rounded-full transition-all duration-300 w-full"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cost Breakdown Table -->
                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-3.5">ব্যয়ের খাত</th>
                                <th class="p-3.5 text-right">টাকার পরিমাণ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">১. জমির শেয়ার মূল্য</strong>
                                    <span class="text-[11px] text-slate-400">জমির রেজিস্ট্রি ও নামজারি সহ আনুপাতিক অংশ</span>
                                </td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(landShareCost)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">২. ফ্ল্যাট নির্মাণ খরচ</strong>
                                    <span class="text-[11px] text-slate-400" x-text="flatSize + ' sqft × ' + constructionRate + ' ৳'"></span>
                                </td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(constructionCost)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">৩. ইউটিলিটি, লিফট ও সাবস্টেশন</strong>
                                    <span class="text-[11px] text-slate-400">বিদ্যুৎ, জেনারেটর, কমন স্পেস ও অনুমোদন</span>
                                </td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(utilityCost)"></td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-sky-50/70 border-t-2 border-sky-500/30 text-sky-950 font-bold">
                            <tr>
                                <td class="p-4 text-sm font-black">ল্যান্ড শেয়ারে আপনার মোট ফ্ল্যাট খরচ:</td>
                                <td class="p-4 text-right text-base font-black text-sky-900" x-text="formatCurrency(totalLandShareProjectCost)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- CTA to explore live Land Share listings -->
                <div class="flex items-center justify-between flex-wrap gap-3 pt-2">
                    <a href="{{ route('properties.share') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 shadow-xs">
                        <span>🏢</span> লাইভ ল্যান্ড শেয়ার প্রজেক্টগুলো দেখুন &rarr;
                    </a>
                    <a href="{{ route('chahida.index') }}" class="w-full sm:w-auto px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-bold transition text-center">
                        চাহিদা জানান
                    </a>
                </div>

            </div>

        </div>

        <!-- Featured Land Share Properties Carousel/List if available -->
        @if($featuredShares->count() > 0)
            <div class="mt-16 bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">ভেরিফাইড প্রজেক্ট</span>
                        <h3 class="text-xl font-black text-slate-900 mt-1">বর্তমানে বুকিং চলমান ল্যান্ড শেয়ার প্রকল্পসমূহ</h3>
                    </div>
                    <a href="{{ route('properties.share') }}" class="text-xs font-bold text-emerald-600 hover:underline">সকল প্রজেক্ট দেখুন &rarr;</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($featuredShares as $share)
                        <div class="rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg transition-all flex flex-col justify-between">
                            <div>
                                <div class="aspect-video bg-slate-100 relative">
                                    <img src="{{ $share->cover_url }}" alt="{{ $share->title }}" class="w-full h-full object-cover">
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 text-slate-800">
                                        {{ $share->area ?: 'ঢাকা' }}
                                    </span>
                                </div>
                                <div class="p-4">
                                    <h4 class="font-bold text-sm text-slate-900 line-clamp-1">{{ $share->title }}</h4>
                                    <span class="text-xs text-emerald-600 font-black mt-1 block">{{ $share->formatted_price }}</span>
                                </div>
                            </div>
                            <div class="p-4 pt-0">
                                <a href="{{ route('property.show', $share->slug ?: $share->id) }}" class="block w-full py-2 text-center rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                                    বিস্তারিত দেখুন
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
