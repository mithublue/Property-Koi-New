@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12"
     x-data="{
         deedValue: 5000000,
         areaType: 'dhaka_ctg_cantonment',
         propertyType: 'residential_flat',
         flatType: 'secondary', // 'secondary', 'new_developer_small', 'new_developer_large'
         whoPaysAit: 'buyer_pays', // 'buyer_pays', 'seller_pays'
         
         // Presets
         setPreset(val) {
             this.deedValue = val;
         },

         // Check if Heba deed selected
         get isHeba() {
             return this.propertyType === 'heba';
         },

         // Percentages based on area & property type (2026 statutory rules)
         get stampDutyRate() {
             if (this.isHeba) return 0;
             return {{ (float) \App\Models\Setting::get('calc_stamp_duty_rate', 1.5) }} / 100;
         },

         get registrationFeeRate() {
             if (this.isHeba) return 0;
             return {{ (float) \App\Models\Setting::get('calc_reg_fee_rate', 1.0) }} / 100;
         },

         get localGovtRate() {
             if (this.isHeba) return 0;
             if (this.areaType === 'pourashabha') return {{ (float) \App\Models\Setting::get('calc_local_govt_pourashabha', 3.0) }} / 100;
             if (this.areaType === 'union_parishad') return {{ (float) \App\Models\Setting::get('calc_local_govt_union', 3.0) }} / 100;
             return {{ (float) \App\Models\Setting::get('calc_local_govt_dhaka', 2.0) }} / 100;
         },

         get aitRate() {
             if (this.isHeba) return 0;
             if (this.areaType === 'dhaka_ctg_cantonment') return {{ (float) \App\Models\Setting::get('calc_ait_dhaka', 8.0) }} / 100;
             if (this.areaType === 'other_city_corp') return {{ (float) \App\Models\Setting::get('calc_ait_other_city', 6.0) }} / 100;
             if (this.areaType === 'pourashabha') return {{ (float) \App\Models\Setting::get('calc_ait_pourashabha', 4.0) }} / 100;
             return {{ (float) \App\Models\Setting::get('calc_ait_union', 2.0) }} / 100;
         },

         get vatRate() {
             if (this.isHeba || this.propertyType === 'residential_plot') return 0;
             if (this.propertyType === 'residential_flat') {
                 if (this.flatType === 'new_developer_small') return 0.02; // 2% for flats <= 1600 sqft
                 if (this.flatType === 'new_developer_large') return 0.045; // 4.5% for flats > 1600 sqft
                 return 0; // secondary / resale flat
             }
             if (this.propertyType === 'commercial') {
                 return 0.075; // 7.5% commercial developer VAT (applicable when buying from developer)
             }
             return 0;
         },

         get affidavitFee() {
             return {{ (int) \App\Models\Setting::get('calc_affidavit_fee', 300) }};
         },

         get miscGovtFees() {
             if (this.isHeba) return 100;
             return {{ (int) \App\Models\Setting::get('calc_misc_govt_fees', 300) }};
         },

         // Calculated values
         get stampDutyAmount() {
             if (this.isHeba) return 200; // Fixed nominal 200 tk stamp for Heba
             return Math.round(this.deedValue * this.stampDutyRate);
         },

         get registrationFeeAmount() {
             if (this.isHeba) return 100; // Fixed nominal 100 tk registration fee for Heba
             return Math.max(100, Math.round(this.deedValue * this.registrationFeeRate));
         },

         get localGovtAmount() {
             return Math.round(this.deedValue * this.localGovtRate);
         },

         get aitAmount() {
             return Math.round(this.deedValue * this.aitRate);
         },

         get vatAmount() {
             return Math.round(this.deedValue * this.vatRate);
         },

         // Cost without seller AIT
         get buyerCostExcludingAit() {
             return this.stampDutyAmount + this.registrationFeeAmount + this.localGovtAmount + this.vatAmount + this.affidavitFee + this.miscGovtFees;
         },

         // Total payable depending on who pays AIT
         get totalPayable() {
             if (this.whoPaysAit === 'seller_pays') {
                 return this.buyerCostExcludingAit;
             }
             return this.buyerCostExcludingAit + this.aitAmount;
         },

         // Full government revenue (regardless of who pays)
         get totalGovtRevenue() {
             return this.buyerCostExcludingAit + this.aitAmount;
         },

         get totalPercent() {
             return this.deedValue > 0 ? ((this.totalPayable / this.deedValue) * 100).toFixed(2) : 0;
         },

         formatCurrency(val) {
             return new Intl.NumberFormat('bn-BD').format(val) + ' ৳';
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
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 print:hidden" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">হোম</a>
            <span>/</span>
            <a href="{{ route('calculators.index') }}" class="hover:text-emerald-600 transition">ক্যালকুলেটর</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">রেজিস্ট্রেশন ফি ও কর ক্যালকুলেটর</span>
        </nav>

        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto mb-8 print:mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-3 shadow-2xs print:hidden">
                <span>⚖️</span> ২০২৬ সালের সর্বশেষ সরকারি সার্কুলার অনুযায়ী
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                জমি ও ফ্ল্যাট <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600">রেজিস্ট্রেশন খরচ ক্যালকুলেটর</span>
            </h1>
            <p class="mt-3 text-xs sm:text-base text-slate-600 leading-relaxed print:hidden">
                সাব-রেজিস্ট্রি অফিসে জমি, প্লট বা ফ্ল্যাট দলিলের জন্য প্রয়োজনীয় স্ট্যাম্প শুল্ক, নিবন্ধন ফি, স্থানীয় সরকার কর, ভ্যাট ও উৎসে আয়করের শতভাগ নির্ভুল হিসাব করুন।
            </p>
        </div>

        <!-- Statutory Clarification Alert (Cost Reduction Reality Check) -->
        <div class="max-w-4xl mx-auto mb-8 bg-sky-50 border border-sky-200 rounded-2xl p-4 sm:p-5 text-sky-950 text-xs sm:text-sm leading-relaxed shadow-xs print:hidden">
            <div class="flex items-start gap-3">
                <span class="text-2xl shrink-0">📢</span>
                <div class="space-y-1">
                    <strong class="font-bold text-sky-900 block text-sm sm:text-base">রেজিস্ট্রেশন খরচ কি সম্প্রতি কমেছে? জেনে নিন সত্যতা:</strong>
                    <p class="text-sky-800">
                        সম্প্রতি <strong>জাতীয় কর কাঠামো পুনর্বিন্যাস কমিটি</strong> জমি ও ফ্ল্যাট রেজিস্ট্রেশনের উচ্চ ব্যয় ৫০% কমিয়ে আনার সুপারিশ সরকারের কাছে জমা দিয়েছে। তবে <strong>এখনও কোনো নতুন সরকারি প্রজ্ঞাপন বা গেজেট জারি হয়নি</strong>। ফলে বর্তমানে পূর্বের আইনসম্মত হারেই (স্ট্যাম্প ডিউটি ১.৫%, নিবন্ধন ফি ১%, স্থানীয় সরকার কর ২%-৩% এবং উৎসে আয়কর ২%-৮%) সাব-রেজিস্ট্রি অফিসে দলিল সম্পাদিত হচ্ছে। সরকারের নতুন কোনো গেজেট প্রকাশ হওয়া মাত্রই এই ক্যালকুলেটর স্বয়ংক্রিয়ভাবে আপডেট করা হবে।
                    </p>
                </div>
            </div>
        </div>

        <!-- Calculator Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Input Controls Card (5 Cols) -->
            <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6 print:hidden">
                <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <span>⚙️</span> প্রপার্টি ও দলিলের তথ্য দিন
                </h3>

                <!-- Deed Value Input & Quick Presets -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        দলিল বা বায়না মূল্য (টাকায়) *
                    </label>
                    <div class="relative">
                        <input type="number"
                               x-model.number="deedValue"
                               step="50000"
                               min="50000"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 text-base font-bold text-slate-900 focus:outline-hidden focus:border-emerald-500 pr-12">
                        <span class="absolute right-4 top-3.5 text-xs font-bold text-slate-400">BDT</span>
                    </div>
                    <div class="mt-2 text-xs font-semibold text-emerald-600 flex items-center justify-between">
                        <span x-text="formatLakhs(deedValue)"></span>
                        <span class="text-slate-400 font-normal">দ্রুত নির্বাচন:</span>
                    </div>

                    <!-- Quick buttons -->
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button type="button" @click="setPreset(2000000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition">২০ লাখ</button>
                        <button type="button" @click="setPreset(3500000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition">৩৫ লাখ</button>
                        <button type="button" @click="setPreset(5000000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition">৫০ লাখ</button>
                        <button type="button" @click="setPreset(8000000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition">৮০ লাখ</button>
                        <button type="button" @click="setPreset(12000000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition">১.২ কোটি</button>
                    </div>
                </div>

                <!-- Property Type Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্রপার্টির ধরন *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="p-2.5 rounded-xl border text-center cursor-pointer transition text-xs font-bold"
                               :class="propertyType === 'residential_flat' ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" x-model="propertyType" value="residential_flat" class="sr-only">
                            🏢 ফ্ল্যাট
                        </label>
                        <label class="p-2.5 rounded-xl border text-center cursor-pointer transition text-xs font-bold"
                               :class="propertyType === 'residential_plot' ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" x-model="propertyType" value="residential_plot" class="sr-only">
                            🏡 জমি / প্লট
                        </label>
                        <label class="p-2.5 rounded-xl border text-center cursor-pointer transition text-xs font-bold"
                               :class="propertyType === 'commercial' ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" x-model="propertyType" value="commercial" class="sr-only">
                            🏪 কমার্শিয়াল
                        </label>
                        <label class="p-2.5 rounded-xl border text-center cursor-pointer transition text-xs font-bold"
                               :class="propertyType === 'heba' ? 'bg-emerald-50 border-emerald-500 text-emerald-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" x-model="propertyType" value="heba" class="sr-only">
                            🎁 হেবা দলিল (রক্তের দান)
                        </label>
                    </div>
                </div>

                <!-- Conditional: Flat Type / Developer VAT details -->
                <div x-show="propertyType === 'residential_flat'" class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                    <label class="block text-xs font-bold text-slate-700">ফ্ল্যাটের প্রকারভেদ ও ভ্যাট (VAT) *</label>
                    <select x-model="flatType" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold bg-white focus:border-emerald-500 focus:outline-hidden">
                        <option value="secondary">ব্যক্তিগত / সেকেন্ডারি ফ্ল্যাট (ভ্যাট ০%)</option>
                        <option value="new_developer_small">নতুন ডেভেলপার ফ্ল্যাট: ১,৬০০ বর্গফুট পর্যন্ত (২% মূসক)</option>
                        <option value="new_developer_large">নতুন ডেভেলপার ফ্ল্যাট: ১,৬০০ বর্গফুটের বেশি (৪.৫% মূসক)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 leading-tight">
                        * ডেভেলপার কোম্পানির কাছ থেকে নতুন ফ্ল্যাট কেনায় জাতীয় রাজস্ব বোর্ডের নির্ধারিত হারে ভ্যাট প্রযোজ্য হয়। ব্যক্তিগত বিক্রয়ে কোনো ভ্যাট নেই।
                    </p>
                </div>

                <!-- Area / Location Selection -->
                <div x-show="!isHeba">
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্রপার্টির ভৌগোলিক এলাকা *</label>
                    <select x-model="areaType" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold focus:outline-hidden focus:border-emerald-500 bg-white">
                        <option value="dhaka_ctg_cantonment">ঢাকা, চট্টগ্রাম সিটি কর্পোরেশন ও ক্যান্টনমেন্ট (AIT 8%, স্থানীয় কর 2%)</option>
                        <option value="other_city_corp">গাজীপুর, নারায়ণগঞ্জ ও অন্যান্য সিটি কর্পোরেশন (AIT 6%, স্থানীয় কর 2%)</option>
                        <option value="pourashabha">জেলা/উপজেলা পৌরসভা এলাকা (AIT 4%, স্থানীয় কর 3%)</option>
                        <option value="union_parishad">ইউনিয়ন পরিষদ ও গ্রামীণ এলাকা (AIT 2%, স্থানীয় কর 3%)</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">জাতীয় রাজস্ব বোর্ড (NBR) আয়কর আইন ২০২৩-এর ধারা ১২৫ (সাবেক ৫৩এইচ) এলাকা অনুযায়ী করের হার নির্ধারিত হয়।</p>
                </div>

                <!-- AIT Liability Choice (Who pays Source Tax?) -->
                <div x-show="!isHeba" class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                    <label class="block text-xs font-bold text-slate-700">উৎসে আয়কর বা গেইন ট্যাক্স (AIT) কে প্রদান করবেন? *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold flex items-center gap-2"
                               :class="whoPaysAit === 'buyer_pays' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 font-bold' : 'border-slate-200 text-slate-600 bg-white hover:bg-slate-100'">
                            <input type="radio" x-model="whoPaysAit" value="buyer_pays" class="sr-only">
                            <span>🤝 ক্রেতা বহন করবে</span>
                        </label>
                        <label class="p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold flex items-center gap-2"
                               :class="whoPaysAit === 'seller_pays' ? 'bg-emerald-50 border-emerald-500 text-emerald-800 font-bold' : 'border-slate-200 text-slate-600 bg-white hover:bg-slate-100'">
                            <input type="radio" x-model="whoPaysAit" value="seller_pays" class="sr-only">
                            <span>⚖️ বিক্রেতা দেবেন (আইনসম্মত)</span>
                        </label>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-tight">
                        আইন অনুযায়ী উৎসে আয়কর বিক্রেতার দায় হলেও বাংলাদেশে বায়না চুক্তির শর্তে অধিকাংশ ক্ষেত্রে ক্রেতাকে বহন করতে হয়।
                    </p>
                </div>

                <!-- Helpful Quick Guide Links -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2">
                    <span class="font-bold text-slate-800 flex items-center gap-1">
                        <span>💡</span> সহায়িকা ও প্রপার্টি গাইড:
                    </span>
                    <ul class="space-y-1.5 list-disc list-inside text-slate-600">
                        <li>
                            <a href="{{ route('guides.show', 'jomir-dolil-o-namjari-khotian-jachai-guide-2026') }}" class="font-bold text-emerald-600 hover:underline">
                                দলিল ও নামজারি খতিয়ান যাচাই নির্দেশিকা &rarr;
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('guides.show', 'heba-dolil-rules-sub-registry-fees-process-bangladesh-2026') }}" class="font-bold text-teal-600 hover:underline">
                                হেবা দলিলের রক্তের সম্পর্ক ও নামমাত্র খরচের নিয়ম &rarr;
                            </a>
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Calculation Output Receipt Card (7 Cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">

                <!-- Grand Total Banner -->
                <div class="bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 rounded-2xl p-6 text-white shadow-md relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs text-emerald-300 font-semibold mb-1">
                        <span x-text="whoPaysAit === 'seller_pays' && !isHeba ? 'ক্রেতার নিট প্রদেয় রেজিস্ট্রেশন খরচ' : 'সর্বমোট সরকারি রেজিস্ট্রেশন খরচ'"></span>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/30 font-bold" x-text="'দলিল মূল্যের ' + totalPercent + '%'"></span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-white tracking-tight" x-text="formatCurrency(totalPayable)"></div>
                    <div class="mt-2 text-xs text-slate-300 flex items-center gap-2 flex-wrap">
                        <span>আনুমানিক: <strong class="text-emerald-400" x-text="formatLakhs(totalPayable)"></strong></span>
                        <span>•</span>
                        <span>দলিল মূল্য: <span x-text="formatCurrency(deedValue)"></span></span>
                        <template x-if="whoPaysAit === 'seller_pays' && !isHeba">
                            <span class="text-amber-300">(+ বিক্রেতার পৃথক উৎসে কর: <span x-text="formatCurrency(aitAmount)"></span>)</span>
                        </template>
                    </div>
                </div>

                <!-- Summary Breakdown Pills -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[11px] text-slate-500 block">স্ট্যাম্প ও নিবন্ধন ফি</span>
                        <strong class="text-sm font-bold text-slate-900" x-text="formatCurrency(stampDutyAmount + registrationFeeAmount)"></strong>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[11px] text-slate-500 block">স্থানীয় সরকার কর</span>
                        <strong class="text-sm font-bold text-slate-900" x-text="formatCurrency(localGovtAmount)"></strong>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 col-span-2 sm:col-span-1">
                        <span class="text-[11px] text-slate-500 block">উৎসে আয়কর (AIT)</span>
                        <strong class="text-sm font-bold text-slate-900" x-text="formatCurrency(aitAmount)"></strong>
                    </div>
                </div>

                <!-- Detailed Breakdown Table -->
                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-3.5">খাত / ফি বিবরণ</th>
                                <th class="p-3.5 text-center">হার (%)</th>
                                <th class="p-3.5 text-right">খরচ (টাকা)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">স্ট্যাম্প শুল্ক (Stamp Duty)</strong>
                                    <span class="text-[11px] text-slate-400">১৮৯৯ সালের স্ট্যাম্প আইন (হেবায় নির্দিষ্ট ২০০ টাকা)</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="isHeba ? 'নির্দিষ্ট' : (stampDutyRate * 100).toFixed(2) + '%'"></td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(stampDutyAmount)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">নিবন্ধন ফি (Registration Fee)</strong>
                                    <span class="text-[11px] text-slate-400">নিবন্ধন আইন ১৯০৮ অনুযায়ী ফি (হেবায় নির্দিষ্ট ১০০ টাকা)</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="isHeba ? 'নির্দিষ্ট' : (registrationFeeRate * 100).toFixed(2) + '%'"></td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(registrationFeeAmount)"></td>
                            </tr>
                            <tr x-show="!isHeba">
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">স্থানীয় সরকার কর (Local Govt Tax)</strong>
                                    <span class="text-[11px] text-slate-400" x-text="areaType === 'dhaka_ctg_cantonment' || areaType === 'other_city_corp' ? 'সিটি কর্পোরেশন কর (২%)' : (areaType === 'union_parishad' ? 'ইউনিয়ন, উপজেলা ও জেলা পরিষদ কর (৩%)' : 'পৌরসভা ও জেলা পরিষদ কর (৩%)')"></span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="(localGovtRate * 100).toFixed(1) + '%'"></td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(localGovtAmount)"></td>
                            </tr>
                            <tr x-show="!isHeba">
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">
                                        উৎসে আয়কর (AIT - ধারা ৫৩এইচ)
                                        <span class="block text-[11px] text-slate-400 font-normal">নতুন আয়কর আইন ২০২৩-এর ধারা ১২৫ (সাবেক ৫৩এইচ)</span>
                                        <template x-if="whoPaysAit === 'seller_pays'">
                                            <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">বিক্রেতার দায়</span>
                                        </template>
                                    </strong>
                                    <span class="text-[11px] text-slate-400">আয়কর আইন ২০২৩ এলাকাভিত্তিক হার (সাবেক ৫৩এইচ)</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="(aitRate * 100).toFixed(1) + '%'"></td>
                                <td class="p-3.5 text-right font-bold" :class="whoPaysAit === 'seller_pays' ? 'text-amber-700 italic' : 'text-slate-900'" x-text="formatCurrency(aitAmount) + (whoPaysAit === 'seller_pays' ? ' *' : '')"></td>
                            </tr>
                            <tr x-show="vatRate > 0">
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">ডেভেলপার মূসক / ভ্যাট (VAT)</strong>
                                    <span class="text-[11px] text-slate-400">জাতীয় রাজস্ব বোর্ড অনুমোদিত ডেভেলপার ফ্ল্যাট/স্পেস মূসক</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="(vatRate * 100).toFixed(1) + '%'"></td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(vatAmount)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">হলফনামা স্ট্যাম্প (Affidavit)</strong>
                                    <span class="text-[11px] text-slate-400">দলিলদাতার হলফনামা সরকারি নন-জুডিশিয়াল স্ট্যাম্প</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-400">নির্দিষ্ট</td>
                                <td class="p-3.5 text-right font-bold text-slate-900">৩০০ ৳</td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">ই-ফি ও এন-ফি (E-fee, N-fee & Court fees)</strong>
                                    <span class="text-[11px] text-slate-400">ডিজিটাল নিবন্ধন তল্লাশ ও সরকারি নকল ফিস</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-400">নির্দিষ্ট</td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(miscGovtFees)"></td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-emerald-50/70 border-t-2 border-emerald-500/30 text-emerald-950 font-bold">
                            <tr>
                                <td class="p-4 text-sm font-black" x-text="whoPaysAit === 'seller_pays' && !isHeba ? 'ক্রেতার প্রদেয় নিট খরচ:' : 'সর্বমোট সরকারি খরচ:'"></td>
                                <td class="p-4 text-center text-xs font-bold" x-text="totalPercent + '%'"></td>
                                <td class="p-4 text-right text-base font-black text-emerald-800" x-text="formatCurrency(totalPayable)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Action Buttons: Print & Contact -->
                <div class="flex items-center justify-between flex-wrap gap-3 pt-2 print:hidden">
                    <button type="button"
                            onclick="window.print()"
                            class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <span>🖨️</span> রশিদ প্রিন্ট / PDF সংরক্ষণ
                    </button>
                    <a href="{{ route('properties.index') }}" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                        <span>🏢</span> ভেরিফাইড প্রপার্টি খুঁজুন
                    </a>
                </div>

                <!-- Important Legal Notice -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 text-[11px] leading-relaxed space-y-1">
                    <p>
                        * <strong>দৃষ্টি আকর্ষণ:</strong> এই ক্যালকুলেটরটি জাতীয় রাজস্ব বোর্ড ও নিবন্ধন অধিদপ্তরের ২০২৬ সালের সর্বশেষ সাধারণ রেজিস্ট্রেশন বিধিমালা অনুযায়ী প্রোগ্রাম করা।
                    </p>
                    <p>
                        * সাব-রেজিস্ট্রারের নির্ধারিত দলিল লেখক (মুহুরি) ফি, পে-অর্ডার ব্যাংক চার্জ বা দলিলের পৃষ্ঠা সংখ্যা অনুযায়ী সামান্য কিছু খরচ পৃথক হতে পারে। এছাড়া মৌজা মূল্যের তারতম্য সাপেক্ষে উৎস করের ন্যূনতম সীমা প্রযোজ্য হতে পারে।
                    </p>
                </div>

            </div>

        </div>

        <!-- AEO FAQ Section -->
        @if(!empty($ogMeta['faqs']))
            <section class="mt-16 bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs print:hidden">
                <div class="text-center max-w-2xl mx-auto mb-8">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">FAQ / প্রশ্নোত্তর</span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-2">রেজিস্ট্রেশন ফি ও সরকারি কর সংক্রান্ত সাধারণ জিজ্ঞাসা</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">জমি ও ফ্ল্যাট ক্রয়ের সরকারি ফি, কর হ্রাস গুজব ও আইনসম্মত নিয়মের স্পষ্ট উত্তর</p>
                </div>
                <div class="max-w-3xl mx-auto space-y-3" x-data="{ active: null }">
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
