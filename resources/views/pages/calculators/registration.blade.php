@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12"
     x-data="{
         deedValue: 5000000,
         areaType: 'dhaka_ctg_cantonment',
         propertyType: 'residential_flat',
         
         // Presets
         setPreset(val) {
             this.deedValue = val;
         },

         // Percentages based on area (configured from backend Settings with 2026 defaults)
         get stampDutyRate() { return {{ (float) \App\Models\Setting::get('calc_stamp_duty_rate', 1.5) }} / 100; },
         get registrationFeeRate() { return {{ (float) \App\Models\Setting::get('calc_reg_fee_rate', 1.0) }} / 100; },
         get localGovtRate() {
             if (this.areaType === 'pourashabha') return {{ (float) \App\Models\Setting::get('calc_local_govt_pourashabha', 3.0) }} / 100;
             if (this.areaType === 'union_parishad') return {{ (float) \App\Models\Setting::get('calc_local_govt_union', 1.0) }} / 100;
             return {{ (float) \App\Models\Setting::get('calc_local_govt_dhaka', 2.0) }} / 100;
         },
         get aitRate() {
             if (this.areaType === 'dhaka_ctg_cantonment') return {{ (float) \App\Models\Setting::get('calc_ait_dhaka', 8.0) }} / 100;
             if (this.areaType === 'other_city_corp') return {{ (float) \App\Models\Setting::get('calc_ait_other_city', 6.0) }} / 100;
             if (this.areaType === 'pourashabha') return {{ (float) \App\Models\Setting::get('calc_ait_pourashabha', 4.0) }} / 100;
             return {{ (float) \App\Models\Setting::get('calc_ait_union', 2.0) }} / 100;
         },
         get affidavitFee() { return {{ (int) \App\Models\Setting::get('calc_affidavit_fee', 300) }}; },
         get miscGovtFees() { return {{ (int) \App\Models\Setting::get('calc_misc_govt_fees', 300) }}; },

         // Calculated values
         get stampDutyAmount() { return Math.round(this.deedValue * this.stampDutyRate); },
         get registrationFeeAmount() { return Math.round(this.deedValue * this.registrationFeeRate); },
         get localGovtAmount() { return Math.round(this.deedValue * this.localGovtRate); },
         get aitAmount() { return Math.round(this.deedValue * this.aitRate); },
         get totalGovtCost() {
             return this.stampDutyAmount + this.registrationFeeAmount + this.localGovtAmount + this.aitAmount + this.affidavitFee + this.miscGovtFees;
         },
         get totalPercent() {
             return this.deedValue > 0 ? ((this.totalGovtCost / this.deedValue) * 100).toFixed(2) : 0;
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
        <div class="text-center max-w-3xl mx-auto mb-10 print:mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-3 shadow-2xs print:hidden">
                <span>⚖️</span> ২০২৬ সালের সরকারি সার্কুলার অনুযায়ী
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                জমি ও ফ্ল্যাট <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-600">রেজিস্ট্রেশন খরচ ক্যালকুলেটর</span>
            </h1>
            <p class="mt-3 text-xs sm:text-base text-slate-600 leading-relaxed print:hidden">
                সাব-রেজিস্ট্রি অফিসে জমি, প্লট বা ফ্ল্যাট দলিলের জন্য প্রয়োজনীয় স্ট্যাম্প শুল্ক, নিবন্ধন ফি, স্থানীয় সরকার কর ও উৎসে আয়করের শতভাগ নির্ভুল হিসাব করুন।
            </p>
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
                               min="100000"
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

                <!-- Area / Location Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্রপার্টির ভৌগোলিক এলাকা *</label>
                    <select x-model="areaType" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold focus:outline-hidden focus:border-emerald-500 bg-white">
                        <option value="dhaka_ctg_cantonment">ঢাকা, চট্টগ্রাম সিটি কর্পোরেশন ও ক্যান্টনমেন্ট (AIT 8%)</option>
                        <option value="other_city_corp">গাজীপুর, নারায়ণগঞ্জ ও অন্যান্য সিটি কর্পোরেশন (AIT 6%)</option>
                        <option value="pourashabha">জেলা/উপজেলা পৌরসভা এলাকা (AIT 4%)</option>
                        <option value="union_parishad">ইউনিয়ন পরিষদ ও গ্রামীণ এলাকা (AIT 2%)</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">জাতীয় রাজস্ব বোর্ড (NBR) আয়কর ধারা ৫৩এইচ এলাকা অনুযায়ী করের হার নির্ধারিত হয়।</p>
                </div>

                <!-- Property Type -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">প্রপার্টির ধরন</label>
                    <div class="grid grid-cols-3 gap-2">
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
                    </div>
                </div>

                <!-- Helpful Quick Guide Link -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1.5">
                    <span class="font-bold text-slate-800 flex items-center gap-1">
                        <span>💡</span> সহায়িকা:
                    </span>
                    <p>সরকারি নিয়মানুযায়ী সাব-রেজিস্ট্রি অফিসে সরকার নির্ধারিত সর্বনিম্ন মৌজা মূল্যের চেয়ে কম মূল্যে দলিল রেজিস্ট্রি করা যায় না।</p>
                    <a href="{{ route('guides.show', 'jomir-dolil-o-namjari-khotian-jachai-guide-2026') }}" class="font-bold text-emerald-600 hover:underline inline-block mt-1">
                        দলিল যাচাই ও মৌজা মূল্য সংক্রান্ত গাইড পড়ুন &rarr;
                    </a>
                </div>

            </div>

            <!-- Calculation Output Receipt Card (7 Cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-6">

                <!-- Grand Total Banner -->
                <div class="bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 rounded-2xl p-6 text-white shadow-md relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs text-emerald-300 font-semibold mb-1">
                        <span>সর্বমোট সরকারি রেজিস্ট্রেশন খরচ</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/30 font-bold" x-text="'দলিল মূল্যের ' + totalPercent + '%'"></span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-white tracking-tight" x-text="formatCurrency(totalGovtCost)"></div>
                    <div class="mt-2 text-xs text-slate-300 flex items-center gap-2">
                        <span>আনুমানিক: <strong class="text-emerald-400" x-text="formatLakhs(totalGovtCost)"></strong></span>
                        <span>•</span>
                        <span>দলিল মূল্য: <span x-text="formatCurrency(deedValue)"></span></span>
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
                                    <span class="text-[11px] text-slate-400">১৮৯৯ সালের স্ট্যাম্প আইনের তালিকা অনুযায়ী</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="(stampDutyRate * 100).toFixed(2) + '%'">১.৫০%</td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(stampDutyAmount)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">নিবন্ধন ফি (Registration Fee)</strong>
                                    <span class="text-[11px] text-slate-400">নিবন্ধন আইন ১৯০৮ অনুযায়ী সাব-রেজিস্ট্রার ফি</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="(registrationFeeRate * 100).toFixed(2) + '%'">১.০০%</td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(registrationFeeAmount)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">স্থানীয় সরকার কর (Local Govt Tax)</strong>
                                    <span class="text-[11px] text-slate-400">সিটি কর্পোরেশন/পৌরসভা/ইউনিয়ন উন্নয়ন কর</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="(localGovtRate * 100).toFixed(1) + '%'"></td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(localGovtAmount)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">উৎসে আয়কর (AIT - ধারা ৫৩এইচ)</strong>
                                    <span class="text-[11px] text-slate-400">আয়কর আইন ২০২৩ এলাকাভিত্তিক হার</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-600" x-text="(aitRate * 100).toFixed(1) + '%'"></td>
                                <td class="p-3.5 text-right font-bold text-slate-900" x-text="formatCurrency(aitAmount)"></td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">হলফনামা স্ট্যাম্প (Affidavit)</strong>
                                    <span class="text-[11px] text-slate-400">দলিলদাতার হলফনামা সরকারি স্ট্যাম্প ফি</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-400">নির্দিষ্ট</td>
                                <td class="p-3.5 text-right font-bold text-slate-900">৩০০ ৳</td>
                            </tr>
                            <tr>
                                <td class="p-3.5">
                                    <strong class="font-bold text-slate-900 block">ই-ফি ও এন-ফি (E-fee, N-fee & Court fees)</strong>
                                    <span class="text-[11px] text-slate-400">ডিজিটাল নিবন্ধন তল্লাশ ও নকল ফিস</span>
                                </td>
                                <td class="p-3.5 text-center font-semibold text-slate-400">নির্দিষ্ট</td>
                                <td class="p-3.5 text-right font-bold text-slate-900">৩০০ ৳</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-emerald-50/70 border-t-2 border-emerald-500/30 text-emerald-950 font-bold">
                            <tr>
                                <td class="p-4 text-sm font-black">সর্বমোট সম্ভাব্য খরচ:</td>
                                <td class="p-4 text-center text-xs font-bold" x-text="totalPercent + '%'"></td>
                                <td class="p-4 text-right text-base font-black text-emerald-800" x-text="formatCurrency(totalGovtCost)"></td>
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
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 text-[11px] leading-relaxed">
                    * <strong>দৃষ্টি আকর্ষণ:</strong> এই ক্যালকুলেটরটি জাতীয় রাজস্ব বোর্ড ও নিবন্ধন অধিদপ্তরের সাধারণ রেজিস্ট্রেশন ফি বিধিমালার ভিত্তিতে তৈরি। সাব-রেজিস্ট্রারের নির্ধারিত দলিল লেখক ফি, পে-অর্ডার ব্যাংক চার্জ বা দলিলের পৃষ্ঠা সংখ্যা অনুযায়ী সামান্য কম-বেশি হতে পারে।
                </div>

            </div>

        </div>

        <!-- AEO FAQ Section -->
        @if(!empty($ogMeta['faqs']))
            <section class="mt-16 bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-xs print:hidden">
                <div class="text-center max-w-2xl mx-auto mb-8">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">FAQ / প্রশ্নোত্তর</span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-2">রেজিস্ট্রেশন ফি ও সরকারি কর সংক্রান্ত প্রশ্নাবলী</h2>
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
