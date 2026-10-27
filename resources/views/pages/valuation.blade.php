@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12"
     x-data="{
         area: '{{ $initialParams['area'] }}',
         block: '{{ $initialParams['block'] ?? '' }}',
         type: '{{ $initialParams['type'] }}',
         size: {{ $initialParams['size'] }},
         roadWidth: {{ $initialParams['road_width'] }},
         facing: '{{ $initialParams['facing'] }}',
         isCorner: false,
         hasLakeView: false,
         floorLevel: {{ $initialParams['floor_level'] }},
         hasParking: true,

         benchmarks: {{ json_encode($benchmarks) }},

         get currentBenchmark() {
             return this.benchmarks[this.area] || this.benchmarks['other'];
         },

         get multiplier() {
             let m = 1.0;

             // Block Bonus / Discount
             if (this.block && this.currentBenchmark.blocks && this.currentBenchmark.blocks[this.block]) {
                 m += (this.currentBenchmark.blocks[this.block].bonus || 0);
             }

             if (this.roadWidth >= 60) m += 0.18;
             else if (this.roadWidth >= 40) m += 0.12;
             else if (this.roadWidth >= 30) m += 0.06;

             if (this.isCorner) m += 0.08;
             if (this.hasLakeView) m += 0.10;
             if (this.facing === 'south') m += 0.04;

             if (this.type === 'flat') {
                 if (this.floorLevel === 1) m -= 0.03;
                 else if (this.floorLevel >= 6 && this.floorLevel <= 9) m += 0.03;
             }
             return m;
         },

         get baseRateMin() {
             return this.type === 'land'
                 ? this.currentBenchmark.land_min_katha
                 : this.currentBenchmark.flat_min_sqft;
         },

         get baseRateMax() {
             return this.type === 'land'
                 ? this.currentBenchmark.land_max_katha
                 : this.currentBenchmark.flat_max_sqft;
         },

         get minEstimate() {
             let val = this.baseRateMin * this.size * this.multiplier;
             if (this.type === 'flat' && this.hasParking) val += 500000;
             return Math.round(val);
         },

         get maxEstimate() {
             let val = this.baseRateMax * this.size * this.multiplier;
             if (this.type === 'flat' && this.hasParking) val += 500000;
             return Math.round(val);
         },

         get fairMarketEstimate() {
             return Math.round((this.minEstimate + this.maxEstimate) / 2);
         },

         get rateMin() {
             return Math.round(this.baseRateMin * this.multiplier);
         },

         get rateMax() {
             return Math.round(this.baseRateMax * this.multiplier);
         },

         setType(t) {
             this.type = t;
             if (t === 'land') {
                 this.size = 3;
             } else {
                 this.size = 1350;
             }
         },

         formatCurrency(val) {
             return new Intl.NumberFormat('bn-BD').format(Math.round(val)) + ' ৳';
         },
         formatRate(val) {
             if (this.type === 'land') {
                 let l = val / 100000;
                 if (l >= 100) {
                     return (l / 100).toFixed(2) + ' কোটি ৳/কাঠা';
                 }
                 return l.toFixed(2) + ' লাখ ৳/কাঠা';
             }
             return new Intl.NumberFormat('bn-BD').format(Math.round(val)) + ' ৳/বর্গফুট';
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
            <span class="text-slate-800 font-medium">স্মার্ট প্রপার্টি ভ্যালুয়েশন এস্টিমেটর</span>
        </nav>

        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto mb-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200 mb-3 shadow-2xs">
                <span>💡</span> এআই ও মার্কেট ডাটা ভিত্তিক এস্টিমেটর
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                জমি বা ফ্ল্যাটের বর্তমান <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600">ফেয়ার মার্কেট দর যাচাই করুন</span>
            </h1>
            <p class="mt-3 text-xs sm:text-base text-slate-600 leading-relaxed">
                ঢাকা, আফতাবনগর, বসুন্ধরা, গুলশান ও বনশ্রীতে অবস্থান, রাস্তার প্রশস্ততা, ফ্লোর লেভেল ও সুযোগ-সুবিধা অনুযায়ী যাচাইকৃত বাজারমূল্য রেঞ্জ জানুন সেকেন্ডেই।
            </p>
        </div>

        <!-- Valuation Main Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Inputs Card (5 Cols) -->
            <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-5">
                <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <span>📐</span> প্রপার্টির স্পেসিফিকেশন দিন
                </h3>

                <!-- Property Type Tabs -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">প্রপার্টির ধরন</label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl">
                        <button type="button"
                                @click="setType('flat')"
                                class="py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5"
                                :class="type === 'flat' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            <span>🏢</span> রেডি ফ্ল্যাট / অ্যাপার্টমেন্ট
                        </button>
                        <button type="button"
                                @click="setType('land')"
                                class="py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5"
                                :class="type === 'land' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            <span>🏡</span> আবাসিক জমি / প্লট
                        </button>
                    </div>
                </div>

                <!-- Area Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">এলাকা / লোকেশন *</label>
                    <select x-model="area" @change="block = ''" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold focus:outline-hidden focus:border-emerald-500 bg-white">
                        @foreach($benchmarks as $key => $b)
                            <option value="{{ $key }}">{{ $b['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Block Selection (if available for area) -->
                <div x-show="currentBenchmark.blocks && Object.keys(currentBenchmark.blocks).length > 0">
                    <label class="block text-xs font-bold text-slate-700 mb-1">নির্দিষ্ট ব্লক / সেক্টর</label>
                    <select x-model="block" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold focus:outline-hidden focus:border-emerald-500 bg-white">
                        <option value="">সকল ব্লক / পুরো এলাকার গড় দর</option>
                        <template x-for="(bData, bKey) in (currentBenchmark.blocks || {})" :key="bKey">
                            <option :value="bKey" x-text="bData.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Size (Sqft for Flat, Katha for Land) -->
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <span x-text="type === 'land' ? 'জমির পরিমাপ (কাঠা)' : 'ফ্ল্যাটের সাইজ (বর্গফুট)'"></span>
                        <span class="text-emerald-600 font-black text-sm" x-text="type === 'land' ? size + ' কাঠা' : size + ' sqft'"></span>
                    </div>
                    <template x-if="type === 'land'">
                        <div>
                            <input type="range" min="1.5" max="15" step="0.25" x-model.number="size" class="w-full accent-emerald-600 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>১.৫ কাঠা</span>
                                <span>৩ কাঠা</span>
                                <span>৫ কাঠা</span>
                                <span>১০ কাঠা</span>
                            </div>
                        </div>
                    </template>
                    <template x-if="type === 'flat'">
                        <div>
                            <input type="range" min="700" max="3500" step="25" x-model.number="size" class="w-full accent-emerald-600 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>৭০০ sqft</span>
                                <span>১২৫০ sqft</span>
                                <span>১৮০০ sqft</span>
                                <span>৩০০০ sqft</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Road Width -->
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <span>সম্মুখের রাস্তার প্রশস্ততা</span>
                        <span class="text-slate-900 font-bold" x-text="roadWidth + ' ফুট রাস্তা'"></span>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" @click="roadWidth = 20" class="py-2 text-xs font-bold rounded-xl border transition" :class="roadWidth === 20 ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">২০ ফুট</button>
                        <button type="button" @click="roadWidth = 25" class="py-2 text-xs font-bold rounded-xl border transition" :class="roadWidth === 25 ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">২৫ ফুট</button>
                        <button type="button" @click="roadWidth = 40" class="py-2 text-xs font-bold rounded-xl border transition" :class="roadWidth === 40 ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">৪০ ফুট</button>
                        <button type="button" @click="roadWidth = 60" class="py-2 text-xs font-bold rounded-xl border transition" :class="roadWidth === 60 ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">৬০+ ফুট</button>
                    </div>
                </div>

                <!-- Facing -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ফেসিং / মুখী</label>
                    <select x-model="facing" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-hidden bg-white">
                        <option value="south">দক্ষিণ মুখী (South Facing - আলো বাতাস সুবিধা)</option>
                        <option value="north">উত্তর মুখী (North Facing)</option>
                        <option value="east">পূর্ব মুখী (East Facing)</option>
                        <option value="west">পশ্চিম মুখী (West Facing)</option>
                    </select>
                </div>

                <!-- Corner & Lakeview Features -->
                <div class="space-y-2 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" x-model="isCorner" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span>কর্নার প্লট / দুই রাস্তার মোড়ে অবস্থিত (+৮% প্রিমিয়াম)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" x-model="hasLakeView" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span>লেকভিউ অথবা পার্ক মুখী অবস্থান (+১০% প্রিমিয়াম)</span>
                    </label>
                    <template x-if="type === 'flat'">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" x-model="hasParking" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                            <span>ডেডিকেটেড কার পার্কিং স্পেস অন্তর্ভুক্ত (+৫ লাখ ৳)</span>
                        </label>
                    </template>
                </div>

                <!-- Flat Specific: Floor Level -->
                <template x-if="type === 'flat'">
                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1">
                            <span>ফ্লোর লেভেল</span>
                            <span class="text-slate-900 font-bold" x-text="floorLevel + 'ম তলা'"></span>
                        </div>
                        <input type="range" min="1" max="15" step="1" x-model.number="floorLevel" class="w-full accent-emerald-600 cursor-pointer">
                    </div>
                </template>

            </div>

            <!-- Valuation Output Card (7 Cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">

                <!-- Valuation Primary Result Card -->
                <div class="bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-md relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs text-emerald-300 font-semibold mb-2">
                        <span class="flex items-center gap-1.5">
                            <span>🎯</span> বর্তমান আনুমানিক বাজারমূল্য (Fair Market Value)
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold text-[11px]">
                            ৯২% ডাটা নির্ভুলতা
                        </span>
                    </div>

                    <div class="text-3xl sm:text-5xl font-black text-white tracking-tight" x-text="formatLakhs(fairMarketEstimate)"></div>

                    <!-- Range Bar -->
                    <div class="mt-6 pt-5 border-t border-white/10 space-y-2">
                        <div class="flex items-center justify-between text-xs sm:text-sm">
                            <span class="text-slate-300">সম্ভাব্য সর্বনিম্ন দর:</span>
                            <strong class="text-emerald-400" x-text="formatLakhs(minEstimate)"></strong>
                        </div>
                        <div class="w-full h-3 bg-white/10 rounded-full overflow-hidden p-0.5">
                            <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-300 rounded-full w-full"></div>
                        </div>
                        <div class="flex items-center justify-between text-xs sm:text-sm">
                            <span class="text-slate-300">সম্ভাব্য সর্বোচ্চ দর:</span>
                            <strong class="text-teal-300" x-text="formatLakhs(maxEstimate)"></strong>
                        </div>
                    </div>

                    <!-- Unit Rate Analysis -->
                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs text-slate-300">
                        <span x-text="type === 'land' ? 'কাঠাপ্রতি রেঞ্জ:' : 'বর্গফুট প্রতি রেঞ্জ:'"></span>
                        <strong class="text-emerald-300 font-bold" x-text="formatRate(rateMin) + ' — ' + formatRate(rateMax)"></strong>
                    </div>

                    <!-- Applied Factors Summary Pills -->
                    <div class="mt-3 pt-3 border-t border-white/10 flex flex-wrap gap-1.5 text-[11px]">
                        <span class="text-slate-400 self-center mr-1">কার্যকরী ফ্যাক্টর:</span>
                        <template x-if="block && currentBenchmark.blocks && currentBenchmark.blocks[block]">
                            <span class="px-2 py-0.5 rounded-md bg-white/10 text-emerald-300" x-text="currentBenchmark.blocks[block].name"></span>
                        </template>
                        <span class="px-2 py-0.5 rounded-md bg-white/10 text-slate-200" x-text="roadWidth + ' ফুট রাস্তা'"></span>
                        <template x-if="facing === 'south'">
                            <span class="px-2 py-0.5 rounded-md bg-white/10 text-slate-200">দক্ষিণমুখী (+৪%)</span>
                        </template>
                        <template x-if="isCorner">
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">কর্নার প্লট (+৮%)</span>
                        </template>
                        <template x-if="hasLakeView">
                            <span class="px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-300 border border-sky-500/30">লেকভিউ (+১০%)</span>
                        </template>
                    </div>
                </div>

                <!-- Call to Actions -->
                <div class="grid grid-cols-1 {{ auth()->check() ? 'sm:grid-cols-2' : '' }} gap-3 pt-2">
                    @auth
                    <a href="{{ route('property.create') }}" class="py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs">
                        <span>📢</span> এই রেঞ্জে প্রপার্টি বিক্রি করুন
                    </a>
                    @endauth
                    <a href="{{ route('chahida.index') }}" class="py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs">
                        <span>🎯</span> এই বাজেটে প্রপার্টি চাহিদা দিন
                    </a>
                </div>

                <!-- Calculators & Guides Helper Links -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2 text-slate-700">
                        <span class="text-lg">🧮</span>
                        <span>এই মূল্যের জন্য রেজিস্ট্রেশন ফি কত টাকা হবে?</span>
                    </div>
                    <a href="{{ route('calculators.registration') }}" class="font-bold text-emerald-600 hover:text-emerald-700 shrink-0">
                        ক্যালকুলেটরে হিসাব দেখুন &rarr;
                    </a>
                </div>

            </div>

        </div>

        <!-- Real Market Matching Properties Live on PropertyKoi -->
        @if($matchingProperties->count() > 0)
            <div class="mt-16 bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">রিয়েল মার্কেট ডাটা</span>
                        <h3 class="text-xl font-black text-slate-900 mt-1">বর্তমানে PropertyKoi-তে থাকা ভেরিফাইড প্রপার্টিসমূহ</h3>
                    </div>
                    <a href="{{ route('properties.index') }}" class="text-xs font-bold text-emerald-600 hover:underline">সকল প্রপার্টি দেখুন &rarr;</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($matchingProperties as $prop)
                        <div class="rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg transition-all flex flex-col justify-between">
                            <div>
                                <div class="aspect-video bg-slate-100 relative">
                                    <img src="{{ $prop->cover_url }}" alt="{{ $prop->title }}" class="w-full h-full object-cover">
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 text-slate-800">
                                        {{ $prop->type_label }} • {{ $prop->area ?: 'ঢাকা' }}
                                    </span>
                                </div>
                                <div class="p-4">
                                    <h4 class="font-bold text-sm text-slate-900 line-clamp-1">{{ $prop->title }}</h4>
                                    <span class="text-xs text-emerald-600 font-black mt-1 block">{{ $prop->formatted_price ?: 'আলোচনা সাপেক্ষে' }}</span>
                                </div>
                            </div>
                            <div class="p-4 pt-0">
                                <a href="{{ route('property.show', $prop->slug ?: $prop->id) }}" class="block w-full py-2 text-center rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                                    সরাসরি মালিকের সাথে যোগাযোগ
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- AEO FAQ Section -->
        @if(!empty($ogMeta['faqs']))
            <section class="mt-16 bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">FAQ / প্রশ্নোত্তর</span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-2">প্রপার্টি ভ্যালুয়েশন ও বাজারদর সংক্রান্ত সাধারণ জিজ্ঞাসা</h2>
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
