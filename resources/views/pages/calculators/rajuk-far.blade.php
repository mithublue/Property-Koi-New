@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12"
     x-data="rajukFarCalculator()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 print:hidden" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">হোম</a>
            <span>/</span>
            <a href="{{ route('calculators.index') }}" class="hover:text-emerald-600 transition">ক্যালকুলেটর হাব</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">রাজউক ড্যাপ ও এফএআর ক্যালকুলেটর</span>
        </nav>

        <!-- Page Header -->
        <div class="text-center max-w-3xl mx-auto mb-10 print:mb-6">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 mb-3 shadow-2xs">
                <span>📐</span> রাজউক ড্যাপ (DAP 2022-2035) ও ইমারত বিধিমালা
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                রাজউক ড্যাপ ও <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">FAR ক্যালকুলেটর ২০২৬</span>
            </h1>
            <p class="mt-3 text-xs sm:text-base text-slate-600 leading-relaxed max-w-2xl mx-auto">
                প্লটের জমির মাপ, সামনের রাস্তার প্রশস্ততা ও এলাকার ওপর ভিত্তি করে রাজউকের নিয়মে আপনার জমিতে সর্বোচ্চ কত তলা ভবন, কত বর্গফুট ফ্লোর স্পেস এবং কয়টি ফ্ল্যাট অনুমোদন পাওয়া যাবে তা নিখুঁতভাবে যাচাই করুন।
            </p>
        </div>

        <!-- Calculator Main Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Left Column: Input Form Controls (7 cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-8 shadow-xs space-y-6 print:hidden">

                <!-- 1. Administrative Mode Selection (Toggles between RAJUK & Union Porishod) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                        ধাপ ১: এলাকার প্রশাসনিক ধরন নির্বাচন করুন *
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button"
                                @click="adminMode = 'rajuk'; updateCalculations()"
                                :class="adminMode === 'rajuk' ? 'border-indigo-600 bg-indigo-50/60 ring-2 ring-indigo-500/20 text-indigo-950 font-bold' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-100/70 text-slate-700 font-medium'"
                                class="p-3.5 rounded-2xl border text-left transition cursor-pointer flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg shrink-0">🏢</span>
                            <div>
                                <span class="block text-xs sm:text-sm font-bold">রাজউক ড্যাপ এলাকা</span>
                                <span class="block text-[11px] text-slate-500">ঢাকা সিটি ও প্রধান হাউজিং</span>
                            </div>
                        </button>

                        <button type="button"
                                @click="adminMode = 'union_porishod'; updateCalculations()"
                                :class="adminMode === 'union_porishod' ? 'border-purple-600 bg-purple-50/60 ring-2 ring-purple-500/20 text-purple-950 font-bold' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-100/70 text-slate-700 font-medium'"
                                class="p-3.5 rounded-2xl border text-left transition cursor-pointer flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg shrink-0">🏡</span>
                            <div>
                                <span class="block text-xs sm:text-sm font-bold">ইউনিয়ন পরিষদ এলাকা</span>
                                <span class="block text-[11px] text-slate-500">আটি হাউজিং, কেরানীগঞ্জ, সাভার</span>
                            </div>
                        </button>
                    </div>

                    <!-- Union Porishod Disclaimer Alert -->
                    <template x-if="adminMode === 'union_porishod'">
                        <div class="mt-3 p-3.5 rounded-xl bg-purple-50/70 border border-purple-200 text-purple-900 text-xs leading-relaxed flex items-start gap-2.5">
                            <span class="text-base shrink-0">💡</span>
                            <div>
                                <strong>ইউনিয়ন পরিষদ নোট:</strong> সিটি কর্পোরেশনের বাইরের ইউনিয়নগুলোতে মানুষ সাধারণত রাজউকের অনুমোদন ছাড়াই ইউনিয়ন পরিষদ বা স্থানীয় প্রকৌশলীর পরামর্শে ভবন নির্মাণ করে থাকে। এখানে কাঠামোগত নিরাপত্তা নিশ্চিত করে প্রস্তাবিত সেফটি লেআউট গণনা করা হবে।
                            </div>
                        </div>
                    </template>
                </div>

                <!-- 2. Specific Area / Zone Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        ধাপ ২: আপনার প্লটের নির্দিষ্ট এলাকা নির্বাচন করুন *
                    </label>

                    <template x-if="adminMode === 'rajuk'">
                        <select x-model="selectedArea" @change="updateCalculations()"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:border-indigo-500 bg-white font-medium">
                            <option value="planned_bashundhara">বসুন্ধরা আবাসিক এলাকা (পরিকল্পিত হাউজিং জোন)</option>
                            <option value="planned_aftabnagar">আফতাবনগর (পরিকল্পিত হাউজিং জোন)</option>
                            <option value="planned_banasree">বনশ্রী ও আফতাবনগর সংলগ্ন এলাকা</option>
                            <option value="central_gulshan_banani">গুলশান, বনানী, বারিধারা (সেন্ট্রাল প্রিমিয়াম জোন)</option>
                            <option value="central_dhanmondi">ধানমন্ডি ও লালমাটিয়া</option>
                            <option value="aviation_uttara">উত্তরা মডেল টাউন (সিভিল এভিয়েশন উচ্চতা সীমা প্রযোজ্য)</option>
                            <option value="urban_mirpur">মিরপুর, কল্যাণপুর ও মিরপুর ডিওএইচএস</option>
                            <option value="urban_badda_rampura">বাড্ডা, রামপুরা ও খিলগাঁও</option>
                            <option value="planned_purbachal">পূর্বাচল নতুন শহর (উচ্চতর প্ল্যানিং জোন)</option>
                            <option value="old_dhaka">পুরান ঢাকা (লালবাগ, সূত্রাপুর, ওয়ারী - ঘনবসতিপূর্ণ)</option>
                            <option value="suburban_savar_narayanganj">সাভার পৌরসভা / রূপগঞ্জ / কাঁচপুর (রাজউক জোন)</option>
                            <option value="other_rajuk">অন্যান্য রাজউক অনুমোদিত ড্যাপ এলাকা</option>
                        </select>
                    </template>

                    <template x-if="adminMode === 'union_porishod'">
                        <select x-model="selectedArea" @change="updateCalculations()"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:border-purple-500 bg-white font-medium">
                            <option value="up_ati_housing">আটি হাউজিং সোসাইটি (কেরানীগঞ্জ)</option>
                            <option value="up_keraniganj_general">কেরানীগঞ্জ ইউনিয়ন (তারানগর, শাক্তা, রুহিতপুর, কলাতিয়া)</option>
                            <option value="up_savar_union">সাভার উপজেলা ইউনিয়ন পরিষদ এলাকা</option>
                            <option value="up_dhaka_other">ঢাকার আশপাশের অন্যান্য ইউনিয়ন পরিষদ এলাকা</option>
                        </select>
                    </template>

                    <!-- Aviation Warning -->
                    <template x-if="selectedArea === 'aviation_uttara'">
                        <p class="mt-2 text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                            ✈️ <strong>সিভিল এভিয়েশন (CAAB) সতর্কতা:</strong> শাহজালাল আন্তর্জাতিক বিমানবন্দরের রানওয়ের সন্নিকটে হওয়ায় এই এলাকায় রাজউক FAR থাকলেও ভবন সাধারণত সর্বোচ্চ ৭ থেকে ৯ তলার ওপরে নেওয়া যায় না।
                        </p>
                    </template>
                </div>

                <!-- 3. Plot Size Input -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700">
                            ধাপ ৩: প্লটের জমির পরিমাণ *
                        </label>
                        <span class="text-[11px] text-slate-400 font-semibold" x-text="'মোট: ' + formatNumber(plotAreaSqft) + ' বর্গফুট'"></span>
                    </div>

                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-8 sm:col-span-9">
                            <input type="number"
                                   step="0.05"
                                   min="0.5"
                                   max="200"
                                   x-model.number="plotSize"
                                   @input="updateCalculations()"
                                   class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:border-indigo-500 font-bold text-slate-900 bg-white">
                        </div>
                        <div class="col-span-4 sm:col-span-3">
                            <select x-model="plotUnit" @change="updateCalculations()"
                                    class="w-full px-3 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:border-indigo-500 bg-slate-50 font-semibold text-slate-700">
                                <option value="katha">কাঠা (Katha)</option>
                                <option value="decimal">শতক (Decimal)</option>
                                <option value="sqft">বর্গফুট (Sqft)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Preset Chips for Plot Size -->
                    <div class="flex items-center gap-1.5 flex-wrap mt-2.5">
                        <span class="text-[11px] text-slate-400 font-semibold mr-1">দ্রুত পছন্দ:</span>
                        <button type="button" @click="setPlotSize(3, 'katha')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">৩ কাঠা</button>
                        <button type="button" @click="setPlotSize(5, 'katha')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition cursor-pointer">৫ কাঠা</button>
                        <button type="button" @click="setPlotSize(7.5, 'katha')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">৭.৫ কাঠা</button>
                        <button type="button" @click="setPlotSize(10, 'katha')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">১০ কাঠা</button>
                        <button type="button" @click="setPlotSize(20, 'katha')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">২০ কাঠা (১ বিঘা)</button>
                    </div>
                </div>

                <!-- 4. Front Road Width (Road ROW) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700">
                            ধাপ ৪: প্লটের সামনের রাস্তার প্রশস্ততা (Road Width) *
                        </label>
                        <span class="text-[11px] font-bold text-indigo-600" x-text="roadWidth + ' ফুট'"></span>
                    </div>

                    <div class="space-y-2">
                        <input type="range"
                               min="8"
                               max="100"
                               step="1"
                               x-model.number="roadWidth"
                               @input="updateCalculations()"
                               class="w-full accent-indigo-600 cursor-pointer">

                        <div class="grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-8 sm:col-span-9">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <button type="button" @click="setRoadWidth(12)" class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">১২ ফুট</button>
                                    <button type="button" @click="setRoadWidth(20)" class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">২০ ফুট</button>
                                    <button type="button" @click="setRoadWidth(25)" class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition cursor-pointer">২৫ ফুট</button>
                                    <button type="button" @click="setRoadWidth(30)" class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">৩০ ফুট</button>
                                    <button type="button" @click="setRoadWidth(40)" class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">৪০ ফুট</button>
                                    <button type="button" @click="setRoadWidth(60)" class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">৬০ ফুট+</button>
                                </div>
                            </div>
                            <div class="col-span-4 sm:col-span-3">
                                <div class="relative">
                                    <input type="number"
                                           min="8"
                                           max="200"
                                           x-model.number="roadWidth"
                                           @input="updateCalculations()"
                                           class="w-full px-3 py-1.5 text-xs text-right font-bold rounded-xl border border-slate-300 focus:outline-hidden focus:border-indigo-500 pr-8">
                                    <span class="absolute right-2.5 top-1.5 text-xs text-slate-400 font-bold">ft</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Flat Preferences & Unit Planning -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                    <span class="block text-xs font-bold text-slate-800">
                        ধাপ ৫: ফ্ল্যাট ইউনিট পরিকল্পনা ও সাইজ নির্বাচন
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">কাঙ্ক্ষিত প্রতি ফ্ল্যাটের গড় সাইজ</label>
                            <div class="relative">
                                <input type="number"
                                       step="50"
                                       min="500"
                                       max="5000"
                                       x-model.number="flatSizeSqft"
                                       @input="updateCalculations()"
                                       class="w-full px-3 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-white focus:outline-hidden focus:border-indigo-500 pr-12">
                                <span class="absolute right-3 top-2 text-[11px] text-slate-400 font-bold">sqft</span>
                            </div>
                            <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                                <button type="button" @click="flatSizeSqft = 1000; updateCalculations()" class="px-1.5 py-0.5 rounded text-[10px] bg-white border border-slate-200 hover:bg-slate-100">১০০০</button>
                                <button type="button" @click="flatSizeSqft = 1200; updateCalculations()" class="px-1.5 py-0.5 rounded text-[10px] bg-white border border-slate-200 hover:bg-slate-100">১২০০</button>
                                <button type="button" @click="flatSizeSqft = 1500; updateCalculations()" class="px-1.5 py-0.5 rounded text-[10px] bg-white border border-slate-200 hover:bg-slate-100">১৫০০</button>
                                <button type="button" @click="flatSizeSqft = 1800; updateCalculations()" class="px-1.5 py-0.5 rounded text-[10px] bg-white border border-slate-200 hover:bg-slate-100">১৮০০</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">ভবনের ব্যবহারের ধরন</label>
                            <select x-model="buildingUsage" @change="updateCalculations()"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white font-semibold">
                                <option value="residential">সম্পূর্ণ আবাসিক ভবন</option>
                                <option value="mixed">বাণিজ্যিক ও আবাসিক মিশ্র (G+1 কমার্শিয়াল)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Incentive Toggles -->
                    <div class="pt-2 border-t border-slate-200/80 space-y-2">
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 font-medium cursor-pointer">
                            <input type="checkbox" x-model="isNearMetro" @change="updateCalculations()"
                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span>🚆 মেট্রোরেল (MRT) স্টেশনের ৫০০ মিটারের মধ্যে (TOD প্রণোদনামূলক FAR +০.২৫)</span>
                        </label>
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 font-medium cursor-pointer">
                            <input type="checkbox" x-model="isCornerPlot" @change="updateCalculations()"
                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span>📐 কর্নার প্লট (উভয় দিকের রাস্তা থেকে সেটব্যাক ও আলোর সুবিধা)</span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Right Column: Results & Architectural Insights (5 cols) -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Primary Result Summary Card -->
                <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl"></div>

                    <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-5">
                        <span class="text-xs uppercase tracking-wider text-indigo-300 font-bold">
                            ফলাফল সারসংক্ষেপ
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-200 border border-indigo-400/30"
                              x-text="adminMode === 'rajuk' ? 'রাজউক ড্যাপ মানদণ্ড' : 'ইউনিয়ন পরিষদ গাইডলাইন'"></span>
                    </div>

                    <!-- Highlight: Permitted Floors -->
                    <div class="mb-6">
                        <span class="text-xs text-slate-400 font-medium block">অনুমোদনযোগ্য সম্ভাব্য তলা</span>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-indigo-300"
                                  x-text="permittedFloorsLabel"></span>
                        </div>
                        <span class="text-[11px] text-slate-400 mt-1 block" x-text="floorsSubNote"></span>
                    </div>

                    <!-- 3 Core Stats Badges -->
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-slate-800/80 rounded-2xl p-3 border border-slate-700/60">
                            <span class="text-[10px] text-slate-400 block font-medium">নির্ধারিত FAR মান</span>
                            <span class="text-xl font-black text-white" x-text="effectiveFar.toFixed(2)"></span>
                            <span class="text-[10px] text-indigo-300 block mt-0.5" x-text="adminMode === 'rajuk' ? 'ড্যাপ বেস গুণক' : 'প্রস্তাবিত লোড'"></span>
                        </div>

                        <div class="bg-slate-800/80 rounded-2xl p-3 border border-slate-700/60">
                            <span class="text-[10px] text-slate-400 block font-medium">সম্ভাব্য ফ্ল্যাট / ইউনিট</span>
                            <span class="text-xl font-black text-emerald-400" x-text="totalFlats + 'টি'"></span>
                            <span class="text-[10px] text-slate-300 block mt-0.5" x-text="'প্রতি ফ্লোরে ' + flatsPerFloor + 'টি'"></span>
                        </div>

                        <div class="bg-slate-800/80 rounded-2xl p-3 border border-slate-700/60 col-span-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-slate-400 block font-medium">মোট নির্মাণযোগ্য ফ্লোর স্পেস (Gross Area)</span>
                                    <span class="text-lg font-black text-white" x-text="formatNumber(totalBuiltAreaSqft) + ' বর্গফুট'"></span>
                                </div>
                                <span class="text-xs font-bold text-slate-400" x-text="(totalBuiltAreaSqft / 720).toFixed(1) + ' কাঠা সমপরিমাণ'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Ground Coverage & Open Space Visual Bar -->
                    <div class="space-y-2 border-t border-slate-800 pt-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300 font-semibold">গ্রাউন্ড কভারেজ (MGC): <span class="text-emerald-400 font-bold" x-text="mgcPercent + '%'"></span></span>
                            <span class="text-slate-400">খোলা সবুজ জায়গা: <span class="text-indigo-300 font-bold" x-text="(100 - mgcPercent) + '%'"></span></span>
                        </div>

                        <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden flex">
                            <div class="bg-emerald-500 h-full transition-all duration-300" :style="'width: ' + mgcPercent + '%'"></div>
                            <div class="bg-indigo-600 h-full transition-all duration-300" :style="'width: ' + (100 - mgcPercent) + '%'"></div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                            <span>বিল্ডিং ফুটপ্রিন্ট: <strong class="text-white" x-text="formatNumber(footprintSqft) + ' sqft'"></strong></span>
                            <span>বাধ্যতামূলক উন্মুক্ত: <strong class="text-white" x-text="formatNumber(openSpaceSqft) + ' sqft'"></strong></span>
                        </div>
                    </div>

                </div>

                <!-- Mandatory Setbacks Detail Card -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                            <span>📐</span> বাধ্যতামূলক সেটব্যাক (Setback Clearance)
                        </h3>
                        <span class="text-[11px] text-slate-400">আলো-বাতাস ও অগ্নি নিরাপত্তা</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5 text-center">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] text-slate-500 block font-bold">সম্মুখ (Front)</span>
                            <span class="text-sm font-black text-slate-900 mt-1 block" x-text="frontSetback + ' ফুট'"></span>
                            <span class="text-[9px] text-slate-400">১.৫ মিটার</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] text-slate-500 block font-bold">পশ্চাৎ (Rear)</span>
                            <span class="text-sm font-black text-slate-900 mt-1 block" x-text="rearSetback + ' ফুট'"></span>
                            <span class="text-[9px] text-slate-400">২.৫ - ৩ মিটার</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] text-slate-500 block font-bold">পার্শ্বদ্বয় (Sides)</span>
                            <span class="text-sm font-black text-slate-900 mt-1 block" x-text="sideSetback + ' ফুট'"></span>
                            <span class="text-[9px] text-slate-400">উভয় পাশে ১.২৫ মি.</span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500 leading-relaxed m-0">
                        * রাজউক বিধিমালা অনুযায়ী বাউন্ডারি ওয়াল ছুঁয়ে কোনো স্থায়ী ভবনের ছাদ বা দেওয়াল তোলা যায় না। পেছনের ও পাশের সেটব্যাক বাধ্যতামূলকভাবে খালি রাখতে হবে।
                    </p>
                </div>

                <!-- Print & Share Actions -->
                <div class="flex items-center gap-3 print:hidden">
                    <button type="button"
                            @click="window.print()"
                            class="flex-1 py-3 px-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center justify-center gap-2 shadow-md cursor-pointer">
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>রিপোর্ট প্রিন্ট / সেভ করুন</span>
                    </button>
                    <a href="{{ route('guides.show', 'rajuk-dap-far-rules-flat-buyers-rights-dhaka-2026') }}"
                       class="py-3 px-4 rounded-2xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition border border-emerald-200 flex items-center gap-1.5 shrink-0">
                        <span>আইনি গাইড পড়ুন</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <!-- Caution / Verification Notice Box -->
                <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-amber-950 text-xs leading-relaxed space-y-1.5">
                    <div class="font-bold flex items-center gap-1.5 text-amber-900">
                        <span>⚠️</span> সচেতনতা ও স্থপতি পরামর্শ:
                    </div>
                    <p class="text-[11px] text-amber-800 leading-normal m-0">
                        এই ক্যালকুলেটরটি রাজউক ড্যাপ ও ইমারত নির্মাণ বিধিমালার সাধারণ ফর্মুলার ওপর ভিত্তি করে তৈরি। প্লটের নিখুঁত ব্লু-প্রিন্ট প্রণয়নের পূর্বে রাজউক তালিকাভুক্ত প্রকৌশলী বা স্থপতির মাধ্যমে মাটির ভারবহন ক্ষমতা (Soil Test) ও চূড়ান্ত ছাড়পত্র যাচাই করুন।
                    </p>
                </div>

            </div>

        </div>

        <!-- Featured Verified Land Listings matching similar dimensions -->
        @if(isset($featuredPlots) && $featuredPlots->isNotEmpty())
            <div class="mt-16 print:hidden">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">যাচাইকৃত প্লট ও জমির লিস্টিং</h2>
                        <p class="text-xs text-slate-500 mt-0.5">রাজউক ড্যাপ ও সরাসরি মালিকের ভেরিফায়েড প্লটসমূহ</p>
                    </div>
                    <a href="{{ route('home') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                        <span>সকল প্লট দেখুন</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($featuredPlots as $plot)
                        <div class="bg-white rounded-3xl border border-slate-200/90 p-5 shadow-xs hover:shadow-md transition">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 mb-2 inline-block">
                                {{ $plot->area }} • {{ $plot->block ? 'ব্লক ' . $plot->block : '' }}
                            </span>
                            <h3 class="font-bold text-slate-900 text-sm line-clamp-1 mb-1">
                                <a href="{{ route('properties.show', $plot->slug) }}" class="hover:text-emerald-600 transition">
                                    {{ $plot->title }}
                                </a>
                            </h3>
                            <div class="flex items-center justify-between text-xs text-slate-600 mt-3 pt-3 border-t border-slate-100">
                                <span>সাইজ: <strong>{{ $plot->landDetail?->katha_size ? $plot->landDetail->katha_size . ' কাঠা' : 'জমির প্লট' }}</strong></span>
                                <span class="font-bold text-emerald-700">{{ $plot->landDetail?->price_total ? '৳ ' . number_format($plot->landDetail->price_total / 10000000, 2) . ' কোটি' : 'আলোচনা সাপেক্ষ' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- AEO FAQ Section for Google Schema & Rich Snippets -->
        @if(!empty($ogMeta['faqs']))
            <section class="mt-16 bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs max-w-4xl mx-auto print:hidden">
                <div class="text-center mb-8">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">FAQ / সাধারণ জিজ্ঞাসা</span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-2">রাজউক ড্যাপ, এফএআর ও অনুমোদন সংক্রান্ত প্রশ্নোত্তর</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">ভবন নির্মাণ বিধিমালা, তলা সংখ্যা ও ফ্ল্যাট অনুমোদনের আইনসম্মত ব্যাখ্যা</p>
                </div>
                <div class="space-y-3" x-data="{ active: null }">
                    @foreach($ogMeta['faqs'] as $index => $faq)
                        <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50">
                            <button type="button"
                                    @click="active = active === {{ $index }} ? null : {{ $index }}"
                                    class="w-full text-left p-4 sm:p-5 font-bold text-sm sm:text-base text-slate-900 flex items-center justify-between gap-4 cursor-pointer hover:text-indigo-700 transition">
                                <span>{{ $faq['question'] }}</span>
                                <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="active === {{ $index }} ? 'rotate-180 text-indigo-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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

<!-- Alpine.js Calculation Logic Engine -->
<script>
function rajukFarCalculator() {
    return {
        // Form States
        adminMode: 'rajuk', // 'rajuk' or 'union_porishod'
        selectedArea: 'planned_bashundhara',
        plotSize: 5,
        plotUnit: 'katha', // 'katha', 'decimal', 'sqft'
        roadWidth: 25,
        flatSizeSqft: 1200,
        buildingUsage: 'residential', // 'residential' or 'mixed'
        isNearMetro: false,
        isCornerPlot: false,

        // Calculated Reactive Results
        plotAreaSqft: 3600,
        plotKathaEquivalent: 5,
        effectiveFar: 3.15,
        mgcPercent: 60,
        footprintSqft: 2160,
        openSpaceSqft: 1440,
        totalBuiltAreaSqft: 11340,
        permittedFloorsLabel: 'জি+৭ তলা (৮ তলা ভবন)',
        floorsSubNote: 'গ্রাউন্ড ফ্লোর পার্কিং + ৭টি সম্পূর্ণ আবাসিক ফ্লোর',
        totalFlats: 14,
        flatsPerFloor: 2,
        frontSetback: 5,
        rearSetback: 8,
        sideSetback: 4,

        init() {
            this.updateCalculations();
        },

        setPlotSize(val, unit) {
            this.plotSize = val;
            this.plotUnit = unit;
            this.updateCalculations();
        },

        setRoadWidth(w) {
            this.roadWidth = w;
            this.updateCalculations();
        },

        formatNumber(num) {
            return Math.round(num).toLocaleString('en-US');
        },

        updateCalculations() {
            // 1. Calculate Plot Area in Sqft
            let sqft = 0;
            if (this.plotUnit === 'katha') {
                sqft = this.plotSize * 720;
            } else if (this.plotUnit === 'decimal') {
                sqft = this.plotSize * 435.6;
            } else {
                sqft = this.plotSize;
            }
            this.plotAreaSqft = Math.max(sqft, 300);
            this.plotKathaEquivalent = this.plotAreaSqft / 720;

            const katha = this.plotKathaEquivalent;
            const road = this.roadWidth;

            // 2. Base FAR and MGC Resolution Matrix
            let baseFar = 2.0;
            let mgc = 60;
            let maxStories = 5;

            if (this.adminMode === 'union_porishod') {
                // Union Porishod mode (e.g. Ati Housing, Keraniganj, Savar Rural)
                // Local safety rules: Road width still governs, but no strict FAR
                if (road < 12) {
                    baseFar = 1.6;
                    mgc = 65;
                    maxStories = 4;
                    this.permittedFloorsLabel = 'জি+৩ তলা (৪ তলা ভবন)';
                    this.floorsSubNote = 'সরু রাস্তা হওয়ায় নিরাপদ লোডিংয়ের জন্য সর্বোচ্চ ৪ তলার সুপারিশ';
                } else if (road < 20) {
                    baseFar = 2.2;
                    mgc = 65;
                    maxStories = 5;
                    this.permittedFloorsLabel = 'জি+৪ তলা (৫ তলা ভবন)';
                    this.floorsSubNote = 'ইউনিয়ন পরিষদ ও মাটির সেফটি অনুযায়ী সাধারণ ৫ তলা প্রস্তাবিত';
                } else if (road < 30) {
                    baseFar = 2.8;
                    mgc = 60;
                    maxStories = 6;
                    this.permittedFloorsLabel = 'জি+৫ তলা (৬ তলা ভবন)';
                    this.floorsSubNote = 'উপজেলা প্রকৌশলীর পরামর্শ ও উপযুক্ত সয়েল টেস্ট সাপেক্ষে ৬ তলা';
                } else {
                    baseFar = 3.2;
                    mgc = 60;
                    maxStories = 7;
                    this.permittedFloorsLabel = 'জি+৬ তলা (৭ তলা ভবন)';
                    this.floorsSubNote = 'প্রশস্ত রাস্তার সংযোগ থাকায় আধুনিক আবাসিক ভবন নির্মাণ সম্ভব';
                }
            } else {
                // Official RAJUK DAP (2022-2035) Matrix
                if (road < 12) {
                    baseFar = (katha < 3) ? 1.75 : 1.50;
                    mgc = 65;
                    maxStories = 4;
                    this.permittedFloorsLabel = 'জি+৩ তলা (৪ তলা ভবন)';
                    this.floorsSubNote = '১২ ফুটের নিচে রাজউক সাধারণত বহুতল অনুমোদন দেয় না';
                } else if (road < 20) {
                    if (katha < 3) {
                        baseFar = 2.00;
                        mgc = 65;
                        maxStories = 5;
                    } else if (katha <= 5) {
                        baseFar = 2.25;
                        mgc = 62.5;
                        maxStories = 6;
                    } else {
                        baseFar = 2.50;
                        mgc = 60;
                        maxStories = 6;
                    }
                    this.permittedFloorsLabel = (maxStories === 5) ? 'জি+৪ তলা (৫ তলা ভবন)' : 'জি+৫ তলা (৬ তলা ভবন)';
                    this.floorsSubNote = 'ড্যাপ অনুযায়ী রাস্তার মাপ সাপেক্ষে মাঝারি আবাসিক ভবন';
                } else if (road < 30) {
                    if (katha < 3) {
                        baseFar = 2.75;
                        mgc = 62.5;
                        maxStories = 6;
                    } else if (katha <= 5) {
                        baseFar = 3.15;
                        mgc = 60;
                        maxStories = 7;
                    } else {
                        baseFar = 3.50;
                        mgc = 57.5;
                        maxStories = 8;
                    }
                    this.permittedFloorsLabel = 'জি+' + (maxStories - 1) + ' তলা (' + maxStories + ' তলা ভবন)';
                    this.floorsSubNote = 'গ্রাউন্ড ফ্লোর পার্কিং + ' + (maxStories - 1) + 'টি সম্পূর্ণ আবাসিক ফ্লোর';
                } else if (road < 40) {
                    if (katha < 5) {
                        baseFar = 3.25;
                        mgc = 60;
                        maxStories = 7;
                    } else if (katha <= 10) {
                        baseFar = 3.75;
                        mgc = 55;
                        maxStories = 9;
                    } else {
                        baseFar = 4.00;
                        mgc = 52.5;
                        maxStories = 10;
                    }
                    this.permittedFloorsLabel = 'জি+' + (maxStories - 1) + ' তলা (' + maxStories + ' তলা ভবন)';
                    this.floorsSubNote = 'প্রশস্ত রাস্তার সুবিধা থাকায় উচ্চতর আবাসন অনুমোদন';
                } else if (road < 60) {
                    if (katha < 5) {
                        baseFar = 3.50;
                        mgc = 57.5;
                        maxStories = 8;
                    } else if (katha <= 10) {
                        baseFar = 4.00;
                        mgc = 55;
                        maxStories = 10;
                    } else {
                        baseFar = 4.50;
                        mgc = 50;
                        maxStories = 12;
                    }
                    this.permittedFloorsLabel = 'জি+' + (maxStories - 1) + ' তলা (' + maxStories + ' তলা ভবন)';
                    this.floorsSubNote = 'এভিনিউ রোডের সুবিধা নিয়ে হাইরাইজ আবাসিক সুবিধা';
                } else {
                    // 60ft+ Main Boulevard
                    baseFar = (katha >= 10) ? 5.00 : 4.25;
                    mgc = 50;
                    maxStories = (katha >= 10) ? 14 : 10;
                    this.permittedFloorsLabel = 'জি+' + (maxStories - 1) + ' তলা (' + maxStories + ' তলা বহুতল কমপ্লেক্স)';
                    this.floorsSubNote = 'প্রধান বুলেভার্ড রোড হওয়ায় সর্বোচ্চ ফার (FAR) ও উচ্চতা সুবিধা';
                }

                // CAAB Airport limitation check
                if (this.selectedArea === 'aviation_uttara' && maxStories > 8) {
                    maxStories = 8;
                    this.permittedFloorsLabel = 'জি+৭ তলা (৮ তলা - সিভিল এভিয়েশন ক্যাপ)';
                    this.floorsSubNote = 'বিমানবন্দরের ফ্লাইট পাথ নিয়মের কারণে সর্বোচ্চ উচ্চতা ৮ তলায় সীমাবদ্ধ';
                }
            }

            // 3. Apply Incentives (Metro Rail / Corner Plot)
            let totalFar = baseFar;
            if (this.isNearMetro) {
                totalFar += 0.25; // TOD bonus
            }
            if (this.isCornerPlot) {
                totalFar += 0.10; // Corner ventilation & setback adjustment
            }

            this.effectiveFar = totalFar;
            this.mgcPercent = mgc;

            // 4. Construction Areas
            this.footprintSqft = this.plotAreaSqft * (mgc / 100);
            this.openSpaceSqft = this.plotAreaSqft - this.footprintSqft;
            this.totalBuiltAreaSqft = this.plotAreaSqft * totalFar;

            // 5. Units & Flats Estimation
            // Ground floor is reserved for parking / reception (in mixed mode, G floor is shops)
            const residentialStories = Math.max(maxStories - 1, 1);
            const netResidentialArea = this.totalBuiltAreaSqft * 0.85; // 15% deducted for common stairs, lift, duct, core
            
            const targetFlatSize = Math.max(this.flatSizeSqft, 500);
            const rawFlats = Math.floor(netResidentialArea / targetFlatSize);
            this.totalFlats = Math.max(rawFlats, residentialStories);
            
            const perFloor = Math.max(Math.round(this.totalFlats / residentialStories), 1);
            this.flatsPerFloor = perFloor;

            // 6. Setback clearances based on plot size and road width
            if (katha <= 3) {
                this.frontSetback = 5;
                this.rearSetback = 6.5;
                this.sideSetback = 4;
            } else if (katha <= 6) {
                this.frontSetback = 5;
                this.rearSetback = 8;
                this.sideSetback = 4;
            } else if (katha <= 10) {
                this.frontSetback = 6.5;
                this.rearSetback = 10;
                this.sideSetback = 5;
            } else {
                this.frontSetback = 10;
                this.rearSetback = 12;
                this.sideSetback = 6.5;
            }

            if (this.isCornerPlot) {
                // Secondary road setback is lower
                this.sideSetback = Math.max(this.sideSetback - 1, 3.5);
            }
        }
    };
}
</script>

<style>
@media print {
    body {
        background: white !important;
        color: black !important;
    }
    header, footer, nav, .print\:hidden {
        display: none !important;
    }
}
</style>
@endsection
