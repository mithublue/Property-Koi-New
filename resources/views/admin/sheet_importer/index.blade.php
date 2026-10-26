@extends('layouts.admin')

@section('title', 'গুগল শিট বাল্ক ইমপোর্টার ও প্রিসেট ম্যাপার')
@section('page_title', 'গুগল শিট বাল্ক ইমপোর্টার')

@section('page_badge')
    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-1.5 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        স্মার্ট কলাম ম্যাপার ও রিইউজেবল প্রিসেট
    </span>
@endsection

@section('content')
<div class="space-y-6" x-data="sheetImporterApp()">

    <!-- Hero / Feature Introduction Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-700/60 relative overflow-hidden">
        <div class="absolute -right-12 -bottom-12 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2 max-w-3xl">
                <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold tracking-wide uppercase">
                    <span>📊 গুগল শিট অটোমেশন ইঞ্জিন</span>
                    <span>•</span>
                    <span>যেকোনো ফরম্যাটের শিট সাপোর্ট</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
                    ডায়নামিক শিট কলাম ডিটেকশন, ফিল্ড ম্যাপিং ও প্রিসেট সিস্টেম
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    আপনার যেকোনো গুগল শিটের লিংক দিলে সিস্টেম স্বয়ংক্রিয়ভাবে প্রথম সারির কলামগুলোর নাম রিড করবে। আপনি পছন্দমতো প্রপার্টি ফিল্ডের সাথে কলামগুলো ম্যাপ করে <strong class="text-emerald-300">প্রিসেট</strong> হিসেবে সংরক্ষণ করতে পারবেন। পরবর্তীতে একই ধরনের শিট ইম্পোর্ট করতে এক ক্লিকেই প্রিসেট নির্বাচন করলেই চলবে!
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.properties.directory') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 border border-amber-400/40 font-bold text-xs rounded-xl shadow-xs transition">
                    <span>🗄️</span> প্রাইভেট ভল্ট
                </a>
                <a href="{{ route('admin.properties.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs rounded-xl border border-slate-600/60 transition">
                    <span>🏠</span> লাইভ ক্যাটালগ
                </a>
            </div>
        </div>
    </div>

    <!-- Presets Selector & Action Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">🗂️</span>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">সংরক্ষিত ম্যাপিং প্রিসেটসমূহ (Saved Presets)</h3>
                    <p class="text-xs text-slate-500 m-0">আগে থেকে সেভ করা যেকোনো ম্যাপিং প্রিসেট সিলেক্ট করুন অথবা নতুন কলাম রিড করুন।</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="resetForm()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center gap-1">
                    <span>➕</span> নতুন ম্যাপিং শুরু করুন
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">বিদ্যমান প্রিসেট নির্বাচন করুন:</label>
                <div class="flex items-center gap-2">
                    <select x-model="selectedPresetId" @change="onPresetSelected()" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                        <option value="">-- কোনো প্রিসেট নির্বাচন করা নেই (Custom Mapping) --</option>
                        <template x-for="p in presets" :key="p.id">
                            <option :value="p.id" x-text="p.name + ' (' + Object.keys(p.field_mappings || {}).length + 'টি কলাম)'"></option>
                        </template>
                    </select>
                    <button type="button" x-show="selectedPresetId" @click="deleteSelectedPreset()" class="px-3 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center gap-1 whitespace-nowrap" title="প্রিসেটটি মুছে ফেলুন">
                        <span>🗑️</span> মুছুন
                    </button>
                </div>
            </div>
            <div>
                <div class="text-[11px] text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                    <span class="font-bold text-slate-700">টিপস:</span> একবার একটি শিটের জন্য কলাম ম্যাপিং করে নিচে নাম দিয়ে সেভ করলেই তা এই তালিকায় যোগ হয়ে যাবে।
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Google Sheet URL & Column Header Fetcher -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center">১</div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">গুগল শিট লিংক ও কলাম রিড করুন</h3>
                    <p class="text-xs text-slate-500 m-0">পাবলিক বা 'Anyone with the link can view' করা শিটের লিংক প্রদান করুন।</p>
                </div>
            </div>
            <div x-show="headers.length > 0" class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full flex items-center gap-1">
                <span>✓</span> <span x-text="headers.length"></span>টি কলাম পাওয়া গেছে
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">গুগল শিটের ইউআরএল (Google Sheet URL)</label>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <input type="url" x-model="sheetUrl" placeholder="https://docs.google.com/spreadsheets/d/1XWYmjcrBCIwIpBSnpCzvkH7sdtVPeIOSP9XoxmvH6r8/edit?gid=0#gid=0"
                           class="flex-1 bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono text-slate-900 focus:outline-none focus:border-emerald-500 transition shadow-inner">
                    <button type="button" @click="fetchHeaders()" :disabled="isFetchingHeaders || !sheetUrl.trim()"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2 whitespace-nowrap cursor-pointer">
                        <template x-if="!isFetchingHeaders">
                            <span>🔍 কলাম রিড করুন (Fetch Headers)</span>
                        </template>
                        <template x-if="isFetchingHeaders">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>কলাম ফেচ হচ্ছে...</span>
                            </span>
                        </template>
                    </button>
                </div>
                <div class="flex flex-wrap items-center justify-between text-[11px] text-slate-500 mt-1.5 px-1">
                    <span>* সাধারণ ব্রাউজার লিঙ্ক পেস্ট করলেই সিস্টেম নিজে থেকে CSV ফরম্যাটে রিড করবে।</span>
                    <button type="button" @click="loadSampleUrl()" class="text-emerald-700 hover:text-emerald-800 underline font-semibold">
                        নমুনা শিট লিঙ্ক লোড করুন
                    </button>
                </div>
            </div>

            <!-- Fetch Error Notice -->
            <div x-show="fetchError" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start gap-2" style="display: none;">
                <span class="text-base">⚠️</span>
                <div class="flex-1">
                    <div class="font-bold">কলাম রিড করতে সমস্যা হয়েছে:</div>
                    <div x-text="fetchError"></div>
                    <div class="text-[11px] text-rose-600 mt-1">দয়া করে নিশ্চিত করুন শিটটি 'Anyone with the link can view' পারমিশনে শেয়ার করা আছে।</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Visual Mapping Table -->
    <div x-show="headers.length > 0" class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4" style="display: none;">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center">২</div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">কলাম ও ফিল্ড ম্যাপিং (Column Mapping)</h3>
                    <p class="text-xs text-slate-500 m-0">শিটের কোন কলামটি প্রপার্টির কোন ফিল্ডে সংরক্ষিত হবে তা নির্বাচন করুন।</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="autoSuggestMapping()" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center gap-1">
                    <span>🧠</span> অটো স্মার্ট সাজেশন
                </button>
                <button type="button" @click="setAllSkip()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                    সব কলাম স্কিপ করুন
                </button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-white font-bold">
                        <th class="py-3 px-3.5 w-12 text-center">#</th>
                        <th class="py-3 px-3.5 w-44">শিটের কলামের নাম</th>
                        <th class="py-3 px-3.5 w-56">নমুনা ডেটা প্রিভিউ (Sample Data)</th>
                        <th class="py-3 px-3.5">টার্গেট প্রপার্টি ফিল্ড (Target Field)</th>
                        <th class="py-3 px-3.5 w-60">কাস্টম ফিল্ডের কী (যদি প্রযোজ্য)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <template x-for="(col, idx) in headers" :key="col">
                        <tr class="hover:bg-slate-50/80 transition" :class="mappings[col] === '_skip' ? 'bg-slate-50/40 text-slate-400 opacity-75' : ''">
                            <td class="py-3 px-3.5 text-center font-mono text-slate-400 font-semibold" x-text="idx + 1"></td>
                            <td class="py-3 px-3.5 font-bold text-slate-900">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" :class="mappings[col] && mappings[col] !== '_skip' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                    <span class="font-mono text-slate-800 text-xs" x-text="col"></span>
                                </div>
                            </td>
                            <td class="py-3 px-3.5 text-slate-600 font-mono text-[11px]">
                                <div class="max-w-xs truncate" :title="getSampleValue(col)">
                                    <template x-if="getSampleValue(col)">
                                        <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700" x-text="getSampleValue(col)"></span>
                                    </template>
                                    <template x-if="!getSampleValue(col)">
                                        <span class="text-slate-400 italic">খালি (Empty)</span>
                                    </template>
                                </div>
                            </td>
                            <td class="py-3 px-3.5">
                                <select x-model="mappings[col]" @change="onFieldChange(col)" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-emerald-500">
                                    <option value="_skip">✕ এই কলামটি বাদ দিন (Ignore / Skip)</option>
                                    @foreach($targetFields as $groupLabel => $fields)
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach($fields as $fKey => $fLabel)
                                                <option value="{{ $fKey }}">{{ $fLabel }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 px-3.5">
                                <template x-if="mappings[col] === 'custom_field' || (mappings[col] && mappings[col].startsWith('custom_field:'))">
                                    <input type="text" x-model="customFieldKeys[col]" placeholder="ফিল্ডের নাম (যেমন: দাগ, মেসেঞ্জার)"
                                           class="w-full bg-amber-50 border border-amber-300 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-900 focus:outline-none focus:border-amber-500 shadow-inner">
                                </template>
                                <template x-if="!(mappings[col] === 'custom_field' || (mappings[col] && mappings[col].startsWith('custom_field:')))">
                                    <span class="text-slate-400 text-[11px]">—</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Smart parser explanation box -->
        <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-3.5 text-indigo-950 text-xs space-y-1.5">
            <div class="font-bold flex items-center gap-1.5 text-indigo-900">
                <span>🧠</span> স্মার্ট ইন্টেলিজেন্ট পার্সার রুলস:
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
                <div>• <strong>Extra কলাম পার্সিং:</strong> "মৌজা: মেরাদিয়া, দাগ নং: RS 124, CS 56, ২০ ফুট রোড" থাকলে মৌজা ও দাগ আলাদা কাস্টম ফিল্ডে সেভ হবে।</div>
                <div>• <strong>গাণিতিক সাইজ সমাধান:</strong> কাঠার ঘরে <code class="bg-white px-1 rounded text-indigo-800">2.5+2.5</code> বা <code class="bg-white px-1 rounded text-indigo-800">10*20</code> থাকলে স্বয়ংক্রিয়ভাবে গাণিতিক হিসাব করে চূড়ান্ত মান সংরক্ষণ করবে।</div>
                <div>• <strong>মূল্য রূপান্তর:</strong> <code class="bg-white px-1 rounded text-indigo-800">95 lac</code>, <code class="bg-white px-1 rounded text-indigo-800">1.25 cr</code>, <code class="bg-white px-1 rounded text-indigo-800">৮৫ লাখ</code> সরাসরি সম্পূর্ণ টাকায় (যেমন: 9,500,000) রূপান্তর হবে।</div>
                <div>• <strong>কাস্টম ফিল্ডস:</strong> আপনি যেকোনো কলামকে কাস্টম ফিল্ড হিসেবে ডিফাইন করতে পারেন, যা ফিল্টার ও সার্চে পাওয়া যাবে।</div>
            </div>
        </div>
    </div>

    <!-- Step 3: Save as Preset -->
    <div x-show="headers.length > 0" class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4" style="display: none;">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center">৩</div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">এই ম্যাপিং প্রিসেট হিসেবে সংরক্ষণ করুন (Optional)</h3>
                    <p class="text-xs text-slate-500 m-0">ভবিষ্যতে একই ফরম্যাটের গুগল শিটের জন্য এই ম্যাপিংটি ১-ক্লিকে রিইউজ করতে পারবেন।</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">প্রিসেটের নাম *</label>
                <input type="text" x-model="presetName" placeholder="যেমন: প্লট স্ট্যান্ডার্ড শিট (Plots Standard)"
                       class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">সংক্ষিপ্ত বিবরণ (ঐচ্ছিক)</label>
                <input type="text" x-model="presetDesc" placeholder="যেমন: কলাম: place, block, road, plot..."
                       class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <button type="button" @click="savePreset()" :disabled="isSavingPreset || !presetName.trim()"
                        class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <template x-if="!isSavingPreset">
                        <span>💾 প্রিসেট সেভ করুন</span>
                    </template>
                    <template x-if="isSavingPreset">
                        <span>সংরক্ষণ হচ্ছে...</span>
                    </template>
                </button>
            </div>
        </div>
    </div>

    <!-- Step 4: Import Options & Execution -->
    <div x-show="headers.length > 0" class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-5" style="display: none;">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center">৪</div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">ইমপোর্ট অপশন ও এক্সিকিউশন</h3>
                    <p class="text-xs text-slate-500 m-0">ডাটা কোন ডেস্টিনেশনে যাবে এবং ডুপ্লিকেট কীভাবে হ্যান্ডেল হবে নির্ধারণ করুন।</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Option 1: Destination -->
            <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <label class="block text-xs font-bold text-slate-800 uppercase">১. সংরক্ষণের গন্তব্য (Destination)</label>
                <div class="space-y-2 pt-1 text-xs">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="radio" name="destination" value="draft" x-model="destination" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-slate-900">🗄️ প্রাইভেট ভল্ট (ড্রাফট)</span>
                            <div class="text-[11px] text-slate-500">ডাটাবেসে সুরক্ষিত থাকবে, প্রকাশ্যে আসবে না (সুপারিশকৃত)।</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="radio" name="destination" value="live" x-model="destination" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-emerald-700">🌐 সরাসরি পাবলিক ক্যাটালগ</span>
                            <div class="text-[11px] text-slate-500">SEO ও AEO মেটাডাটা সহ সাইটে লাইভ লিস্টিং হয়ে যাবে।</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Option 2: Duplicate Handling -->
            <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <label class="block text-xs font-bold text-slate-800 uppercase">২. ডুপ্লিকেট হ্যান্ডলিং (Duplicates)</label>
                <div class="space-y-2 pt-1 text-xs">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="radio" name="duplicate_mode" value="skip" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-slate-900">🛡️ ডুপ্লিকেট বাদ দিন (Skip)</span>
                            <div class="text-[11px] text-slate-500">একই এরিয়া, ব্লক, রোড ও প্লট থাকলে স্কিপ করবে।</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="radio" name="duplicate_mode" value="update" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-indigo-700">🔄 আপডেট / রিপ্লেস করুন</span>
                            <div class="text-[11px] text-slate-500">বিদ্যমান রেকর্ড পেলে নতুন তথ্য দিয়ে আপডেট করবে।</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="radio" name="duplicate_mode" value="all" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-slate-700">➕ সব নতুন রেকর্ড বানান</span>
                            <div class="text-[11px] text-slate-500">ডুপ্লিকেট চেক না করে প্রতি সারির জন্য নতুন রেকর্ড বানাবে।</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Option 3: Default Property Type -->
            <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <label class="block text-xs font-bold text-slate-800 uppercase">৩. ডিফল্ট প্রপার্টি টাইপ</label>
                <select x-model="defaultType" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                    <option value="land">🏞️ জমি / প্লট (Land / Plot)</option>
                    <option value="flat">🏢 ফ্ল্যাট / অ্যাপার্টমেন্ট (Flat)</option>
                    <option value="commercial">🏬 কমার্শিয়াল স্পেস (Commercial)</option>
                    <option value="land_share">🤝 ল্যান্ড শেয়ার (Land Share)</option>
                </select>
                <div class="text-[11px] text-slate-500">শিটে টাইপ কলাম না থাকলে এই টাইপ প্রযোজ্য হবে।</div>
            </div>

            <!-- Option 4: Default Area -->
            <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <label class="block text-xs font-bold text-slate-800 uppercase">৪. ডিফল্ট এলাকা / এরিয়া</label>
                <input type="text" x-model="defaultArea" placeholder="যেমন: Aftabnagar, Bashundhara"
                       class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                <div class="text-[11px] text-slate-500">শিটের সারিতে এরিয়া ফাঁকা থাকলে এটি ব্যবহার হবে।</div>
            </div>
        </div>

        <!-- Execute Action Section -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-600">
                মোট <strong class="text-slate-900" x-text="getActiveMappedCount()"></strong>টি কলাম ম্যাপ করা হয়েছে।
            </div>
            <button type="button" @click="runImport()" :disabled="isImporting || getActiveMappedCount() === 0"
                    class="w-full sm:w-auto px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-sm rounded-xl shadow-lg transition transform active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
                <template x-if="!isImporting">
                    <span class="flex items-center gap-2">
                        <span>🚀</span>
                        <span>বাল্ক ইমপোর্ট শুরু করুন (Start Bulk Import)</span>
                    </span>
                </template>
                <template x-if="isImporting">
                    <span class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>শিটের ডেটা প্রসেস হচ্ছে, অনুগ্রহ করে অপেক্ষা করুন...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- Step 5: Import Result Report Card -->
    <div x-show="importResult" class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-7 shadow-lg space-y-5" style="display: none;">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <span class="text-3xl" x-text="importResult?.success ? '🎉' : '⚠️'"></span>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 m-0" x-text="importResult?.success ? 'বাল্ক ইমপোর্ট সফলভাবে সম্পন্ন হয়েছে!' : 'ইমপোর্টে কিছু ত্রুটি হয়েছে'"></h3>
                    <p class="text-xs text-slate-500 m-0" x-text="importResult?.message"></p>
                </div>
            </div>
            <button type="button" @click="importResult = null" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <!-- Summary Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-center">
                <div class="text-xs font-semibold text-slate-500">মোট সারি প্রসেসড</div>
                <div class="text-2xl font-black text-slate-900 mt-1" x-text="importResult?.processed || 0"></div>
            </div>
            <div class="bg-emerald-50 p-4 rounded-2xl border border-emerald-200 text-center">
                <div class="text-xs font-semibold text-emerald-700">নতুন তৈরি হয়েছে</div>
                <div class="text-2xl font-black text-emerald-800 mt-1" x-text="importResult?.created || 0"></div>
            </div>
            <div class="bg-indigo-50 p-4 rounded-2xl border border-indigo-200 text-center">
                <div class="text-xs font-semibold text-indigo-700">আপডেট হয়েছে</div>
                <div class="text-2xl font-black text-indigo-800 mt-1" x-text="importResult?.updated || 0"></div>
            </div>
            <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200 text-center">
                <div class="text-xs font-semibold text-amber-700">বাদ পড়েছে (ডুপ্লিকেট)</div>
                <div class="text-2xl font-black text-amber-800 mt-1" x-text="importResult?.skipped || 0"></div>
            </div>
        </div>

        <!-- Errors List if any -->
        <div x-show="importResult?.errors && importResult.errors.length > 0" class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs text-rose-900 space-y-2">
            <div class="font-bold flex items-center gap-1.5 text-rose-800">
                <span>⚠️</span> ত্রুটিযুক্ত সারিগুলো (<span x-text="importResult.errors.length"></span>টি):
            </div>
            <div class="max-h-40 overflow-y-auto space-y-1">
                <template x-for="(err, i) in importResult.errors" :key="i">
                    <div class="bg-white/80 p-2 rounded border border-rose-100 font-mono text-[11px]" x-text="err"></div>
                </template>
            </div>
        </div>

        <!-- Quick Access Buttons -->
        <div class="pt-2 flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.properties.directory') }}" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                <span>🗄️</span> প্রাইভেট ভল্ট দেখুন
            </a>
            <a href="{{ route('admin.properties.index') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                <span>🏠</span> পাবলিক ক্যাটালগ দেখুন
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
function sheetImporterApp() {
    return {
        presets: {!! json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        selectedPresetId: '{{ $presets->first()?->id ?? "" }}',
        sheetUrl: '{{ $presets->first()?->sample_sheet_url ?? "https://docs.google.com/spreadsheets/d/1XWYmjcrBCIwIpBSnpCzvkH7sdtVPeIOSP9XoxmvH6r8/edit?gid=0#gid=0" }}',
        headers: [],
        samples: [],
        mappings: {},
        customFieldKeys: {},
        destination: 'draft',
        duplicateMode: 'skip',
        defaultType: 'land',
        defaultArea: 'Aftabnagar',
        presetName: '',
        presetDesc: '',
        isFetchingHeaders: false,
        isSavingPreset: false,
        isImporting: false,
        fetchError: null,
        importResult: null,

        init() {
            // If preset is selected by default, load its configuration
            if (this.selectedPresetId) {
                this.onPresetSelected();
            }
        },

        loadSampleUrl() {
            this.sheetUrl = 'https://docs.google.com/spreadsheets/d/1XWYmjcrBCIwIpBSnpCzvkH7sdtVPeIOSP9XoxmvH6r8/edit?gid=0#gid=0';
        },

        onPresetSelected() {
            if (!this.selectedPresetId) {
                return;
            }
            const preset = this.presets.find(p => String(p.id) === String(this.selectedPresetId));
            if (!preset) return;

            this.presetName = preset.name || '';
            this.presetDesc = preset.description || '';
            if (preset.sample_sheet_url) {
                this.sheetUrl = preset.sample_sheet_url;
            }
            if (preset.options) {
                this.destination = preset.options.destination || 'draft';
                this.duplicateMode = preset.options.duplicate_mode || 'skip';
                this.defaultType = preset.options.default_type || 'land';
                this.defaultArea = preset.options.default_area || 'Aftabnagar';
            }

            // If we have field mappings, initialize them
            if (preset.field_mappings && Object.keys(preset.field_mappings).length > 0) {
                const keys = Object.keys(preset.field_mappings);
                this.headers = keys;
                this.mappings = {};
                this.customFieldKeys = {};

                for (const col of keys) {
                    const target = preset.field_mappings[col];
                    if (target.startsWith('custom_field:')) {
                        this.mappings[col] = 'custom_field';
                        this.customFieldKeys[col] = target.replace('custom_field:', '');
                    } else {
                        this.mappings[col] = target;
                    }
                }
            }
        },

        async fetchHeaders() {
            if (!this.sheetUrl.trim()) return;

            this.isFetchingHeaders = true;
            this.fetchError = null;

            try {
                const response = await fetch('{{ route("admin.sheet-importer.fetch-headers") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ sheet_url: this.sheetUrl.trim() })
                });

                const data = await response.json();
                if (!data.success) {
                    this.fetchError = data.error || 'শিটের তথ্য ফেচ করতে ব্যর্থ হয়েছে।';
                    return;
                }

                this.headers = data.headers || [];
                this.samples = data.samples || [];

                // Check if current preset matches
                const preset = this.presets.find(p => String(p.id) === String(this.selectedPresetId));
                const presetMappings = preset?.field_mappings || {};

                this.mappings = {};
                this.customFieldKeys = {};

                this.headers.forEach(h => {
                    if (presetMappings[h]) {
                        const target = presetMappings[h];
                        if (target.startsWith('custom_field:')) {
                            this.mappings[h] = 'custom_field';
                            this.customFieldKeys[h] = target.replace('custom_field:', '');
                        } else {
                            this.mappings[h] = target;
                        }
                    } else if (data.suggestions && data.suggestions[h]) {
                        const sug = data.suggestions[h];
                        if (sug.startsWith('custom_field:')) {
                            this.mappings[h] = 'custom_field';
                            this.customFieldKeys[h] = sug.replace('custom_field:', '');
                        } else {
                            this.mappings[h] = sug;
                        }
                    } else {
                        this.mappings[h] = '_skip';
                    }
                });

                if (!this.presetName) {
                    this.presetName = 'শিট প্রিসেট (' + this.headers.length + ' কলাম)';
                }

            } catch (err) {
                this.fetchError = err.message || 'নেটওয়ার্ক বা সার্ভার ত্রুটি।';
            } finally {
                this.isFetchingHeaders = false;
            }
        },

        getSampleValue(col) {
            for (const row of this.samples) {
                if (row[col] && String(row[col]).trim() !== '') {
                    return String(row[col]).trim();
                }
            }
            return '';
        },

        onFieldChange(col) {
            if (this.mappings[col] === 'custom_field' && !this.customFieldKeys[col]) {
                this.customFieldKeys[col] = col;
            }
        },

        autoSuggestMapping() {
            this.headers.forEach(col => {
                const lower = col.toLowerCase().trim();
                if (lower.includes('place') || lower.includes('area') || lower.includes('এলাকা')) {
                    this.mappings[col] = 'area';
                } else if (lower.includes('block') || lower.includes('ব্লক')) {
                    this.mappings[col] = 'block';
                } else if (lower.includes('sector') || lower.includes('সেক্টর')) {
                    this.mappings[col] = 'sector';
                } else if (lower.includes('road') || lower.includes('রাস্তা')) {
                    this.mappings[col] = 'road_number';
                } else if (lower.includes('plot') || lower.includes('প্লট')) {
                    this.mappings[col] = 'plot_number';
                } else if (lower.includes('katha') || lower.includes('কাঠা') || lower.includes('size')) {
                    this.mappings[col] = 'size';
                } else if (lower.includes('price') || lower.includes('দাম') || lower.includes('মূল্য')) {
                    this.mappings[col] = 'asking_price';
                } else if (lower.includes('agent') || lower.includes('মালিক') || lower.includes('owner')) {
                    this.mappings[col] = 'owner_name';
                } else if (lower.includes('contact') || lower.includes('phone') || lower.includes('mobile')) {
                    this.mappings[col] = 'owner_phone';
                } else if (lower.includes('extra') || lower.includes('details')) {
                    this.mappings[col] = 'parse_extra';
                } else if (lower.includes('time') || lower.includes('line')) {
                    this.mappings[col] = 'custom_field';
                    this.customFieldKeys[col] = 'হস্তান্তর সময়সীমা';
                } else if (lower.includes('messenger')) {
                    this.mappings[col] = 'custom_field';
                    this.customFieldKeys[col] = 'মেসেঞ্জার লিংক';
                }
            });
        },

        setAllSkip() {
            this.headers.forEach(col => {
                this.mappings[col] = '_skip';
            });
        },

        getActiveMappedCount() {
            let count = 0;
            for (const col of this.headers) {
                if (this.mappings[col] && this.mappings[col] !== '_skip') {
                    count++;
                }
            }
            return count;
        },

        buildFinalMappings() {
            const final = {};
            for (const col of this.headers) {
                const target = this.mappings[col] || '_skip';
                if (target === 'custom_field') {
                    const key = (this.customFieldKeys[col] || col).trim();
                    final[col] = 'custom_field:' + key;
                } else {
                    final[col] = target;
                }
            }
            return final;
        },

        async savePreset() {
            if (!this.presetName.trim()) {
                alert('অনুগ্রহ করে প্রিসেটের নাম দিন।');
                return;
            }

            this.isSavingPreset = true;
            try {
                const finalMappings = this.buildFinalMappings();
                const payload = {
                    preset_id: this.selectedPresetId || null,
                    name: this.presetName.trim(),
                    description: this.presetDesc.trim(),
                    sample_sheet_url: this.sheetUrl.trim(),
                    field_mappings: finalMappings,
                    options: {
                        destination: this.destination,
                        duplicate_mode: this.duplicateMode,
                        default_type: this.defaultType,
                        default_area: this.defaultArea,
                    }
                };

                const res = await fetch('{{ route("admin.sheet-importer.presets.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    alert(data.message || 'প্রিসেট সফলভাবে সংরক্ষিত হয়েছে!');
                    // Update presets list
                    const existingIndex = this.presets.findIndex(p => p.id === data.preset.id);
                    if (existingIndex >= 0) {
                        this.presets[existingIndex] = data.preset;
                    } else {
                        this.presets.unshift(data.preset);
                    }
                    this.selectedPresetId = data.preset.id;
                } else {
                    alert('ত্রুটি: ' + (data.message || 'সংরক্ষণ ব্যর্থ হয়েছে।'));
                }
            } catch (err) {
                alert('নেটওয়ার্ক ত্রুটি: ' + err.message);
            } finally {
                this.isSavingPreset = false;
            }
        },

        async deleteSelectedPreset() {
            if (!this.selectedPresetId) return;
            if (!confirm('আপনি কি নিশ্চিত যে এই প্রিসেটটি মুছে ফেলতে চান?')) return;

            try {
                const url = '{{ route("admin.sheet-importer.presets.destroy", ":id") }}'.replace(':id', this.selectedPresetId);
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.presets = this.presets.filter(p => String(p.id) !== String(this.selectedPresetId));
                    this.selectedPresetId = '';
                    alert(data.message);
                }
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        },

        async runImport() {
            if (!this.sheetUrl.trim()) {
                alert('দয়া করে শিটের লিংক প্রদান করুন।');
                return;
            }

            if (this.getActiveMappedCount() === 0) {
                alert('অনুগ্রহ করে অন্তত একটি কলাম প্রপার্টি ফিল্ডের সাথে ম্যাপ করুন।');
                return;
            }

            this.isImporting = true;
            this.importResult = null;

            try {
                const finalMappings = this.buildFinalMappings();
                const payload = {
                    sheet_url: this.sheetUrl.trim(),
                    mappings: finalMappings,
                    destination: this.destination,
                    duplicate_mode: this.duplicateMode,
                    default_type: this.defaultType,
                    default_area: this.defaultArea,
                };

                const res = await fetch('{{ route("admin.sheet-importer.import") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                this.importResult = data;
                if (!data.success) {
                    alert(data.error || 'ইমপোর্টে কিছু ত্রুটি হয়েছে।');
                }
            } catch (err) {
                this.importResult = {
                    success: false,
                    message: 'নেটওয়ার্ক বা সার্ভার ত্রুটি: ' + err.message,
                    processed: 0,
                    created: 0,
                    updated: 0,
                    skipped: 0,
                    errors: [err.message]
                };
            } finally {
                this.isImporting = false;
            }
        },

        resetForm() {
            this.selectedPresetId = '';
            this.presetName = '';
            this.presetDesc = '';
            this.headers = [];
            this.samples = [];
            this.mappings = {};
            this.customFieldKeys = {};
            this.importResult = null;
            this.fetchError = null;
        }
    };
}
</script>
@endpush
@endsection
