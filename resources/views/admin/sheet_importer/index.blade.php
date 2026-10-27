@extends('layouts.admin')

@section('title', 'গুগল শিট বাল্ক ইমপোর্টার (প্রপার্টি, এজেন্ট ও বায়ার)')
@section('page_title', 'গুগল শিট বাল্ক ইমপোর্টার')

@section('page_badge')
    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-1.5 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        মাল্টি-এনটিটি কলাম ম্যাপার ও রিইউজেবল প্রিসেট
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
                    <span>📊 গুগল শিট অটোমেশন হাব</span>
                    <span>•</span>
                    <span x-text="activeTabLabel()"></span>
                </div>
                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
                    ডায়নামিক শিট কলাম ডিটেকশন ও বাল্ক ডেটা ইমপোর্ট
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    যেকোনো গুগল শিটের লিংক থেকে প্রথম সারির কলাম রিড করে প্রপার্টি, এজেন্ট ও বায়ার ডিরেক্টরিতে বাল্ক ইমপোর্ট করুন। পছন্দমতো ফিল্ড ম্যাপিং প্রিসেট হিসেবে সেভ করে রাখুন এবং পরবর্তীতে ১-ক্লিকেই শিটের সম্পূর্ণ ডেটা ডাটাবেসে সিঙ্ক করুন।
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.properties.directory') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 border border-amber-400/40 font-bold text-xs rounded-xl shadow-xs transition">
                    <span>🗄️</span> প্রপার্টি ভল্ট
                </a>
                <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-200 border border-indigo-400/40 font-bold text-xs rounded-xl shadow-xs transition">
                    <span>👥</span> এজেন্ট ডিরেক্টরি
                </a>
                <a href="{{ route('admin.buyers.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs rounded-xl border border-slate-600/60 transition">
                    <span>👤</span> বায়ার ডিরেক্টরি
                </a>
            </div>
        </div>
    </div>

    <!-- Entity Navigation Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-2 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="switchTab('property')"
                    :class="activeTab === 'property' ? 'bg-emerald-600 text-white shadow-md font-bold' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 font-semibold'"
                    class="flex-1 sm:flex-none px-5 py-3 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-2.5 cursor-pointer">
                <span class="text-base">🏞️</span>
                <span>জমি ও প্লট ইমপোর্টার (Plots)</span>
                <span class="text-[11px] px-2 py-0.5 rounded-full"
                      :class="activeTab === 'property' ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700'"
                      x-text="getTabPresetsCount('property') + ' প্রিসেট'"></span>
            </button>

            <button type="button" @click="switchTab('flat')"
                    :class="activeTab === 'flat' ? 'bg-emerald-600 text-white shadow-md font-bold' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 font-semibold'"
                    class="flex-1 sm:flex-none px-5 py-3 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-2.5 cursor-pointer">
                <span class="text-base">🏢</span>
                <span>ফ্ল্যাট ও অ্যাপার্টমেন্ট ইমপোর্টার (Flats)</span>
                <span class="text-[11px] px-2 py-0.5 rounded-full"
                      :class="activeTab === 'flat' ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700'"
                      x-text="getTabPresetsCount('flat') + ' প্রিসেট'"></span>
            </button>

            <button type="button" @click="switchTab('agent')"
                    :class="activeTab === 'agent' ? 'bg-emerald-600 text-white shadow-md font-bold' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 font-semibold'"
                    class="flex-1 sm:flex-none px-5 py-3 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-2.5 cursor-pointer">
                <span class="text-base">👥</span>
                <span>এজেন্ট ও মালিক ইমপোর্টার (Agents &amp; Owners)</span>
                <span class="text-[11px] px-2 py-0.5 rounded-full"
                      :class="activeTab === 'agent' ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700'"
                      x-text="getTabPresetsCount('agent') + ' প্রিসেট'"></span>
            </button>

            <button type="button" @click="switchTab('buyer')"
                    :class="activeTab === 'buyer' ? 'bg-emerald-600 text-white shadow-md font-bold' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 font-semibold'"
                    class="flex-1 sm:flex-none px-5 py-3 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-2.5 cursor-pointer">
                <span class="text-base">👤</span>
                <span>বায়ার ও চাহিদা ইমপোর্টার (Buyers &amp; Demands)</span>
                <span class="text-[11px] px-2 py-0.5 rounded-full"
                      :class="activeTab === 'buyer' ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700'"
                      x-text="getTabPresetsCount('buyer') + ' প্রিসেট'"></span>
            </button>
        </div>
    </div>

    <!-- Presets Selector & Action Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">🗂️</span>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">
                        সংরক্ষিত ম্যাপিং প্রিসেটসমূহ (<span x-text="activeTabLabel()"></span>)
                    </h3>
                    <p class="text-xs text-slate-500 m-0">আগে থেকে সেভ করা প্রিসেট নির্বাচন করুন অথবা নিচে নতুন শিট লিংক দিয়ে কলাম রিড করুন।</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="resetForm()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
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
                        <template x-for="p in filteredPresets()" :key="p.id">
                            <option :value="p.id" x-text="p.name + ' (' + Object.keys(p.field_mappings || {}).length + 'টি কলাম)'"></option>
                        </template>
                    </select>
                    <button type="button" x-show="selectedPresetId" @click="deleteSelectedPreset()" class="px-3 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center gap-1 whitespace-nowrap cursor-pointer" title="প্রিসেটটি মুছে ফেলুন">
                        <span>🗑️</span> মুছুন
                    </button>
                </div>
            </div>
            <div>
                <div class="text-[11px] text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                    <span class="font-bold text-slate-700">টিপস:</span> কলাম ম্যাপিং পরিবর্তন করে নিচে প্রিসেটের নাম দিয়ে সেভ করলে ভবিষ্যতের জন্য তা সংরক্ষিত হয়ে যাবে।
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
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">
                        কলাম ও ফিল্ড ম্যাপিং (<span x-text="activeTabLabel()"></span>)
                    </h3>
                    <p class="text-xs text-slate-500 m-0">শিটের কোন কলামটি কোন ফিল্ডে সংরক্ষিত হবে তা নির্বাচন করুন।</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="autoSuggestMapping()" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                    <span>🧠</span> অটো স্মার্ট সাজেশন
                </button>
                <button type="button" @click="setAllSkip()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer">
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
                        <th class="py-3 px-3.5">টার্গেট ফিল্ড (Target Field)</th>
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
                                <!-- Property Target Fields -->
                                <template x-if="activeTab === 'property'">
                                    <select x-model="mappings[col]" @change="onFieldChange(col)" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-emerald-500">
                                        <option value="_skip">✕ এই কলামটি বাদ দিন (Ignore / Skip)</option>
                                        @foreach($propertyTargetFields as $groupLabel => $fields)
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach($fields as $fKey => $fLabel)
                                                    <option value="{{ $fKey }}">{{ $fLabel }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </template>

                                <!-- Flat Target Fields -->
                                <template x-if="activeTab === 'flat'">
                                    <select x-model="mappings[col]" @change="onFieldChange(col)" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-emerald-500">
                                        <option value="_skip">✕ এই কলামটি বাদ দিন (Ignore / Skip)</option>
                                        @foreach($flatTargetFields as $groupLabel => $fields)
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach($fields as $fKey => $fLabel)
                                                    <option value="{{ $fKey }}">{{ $fLabel }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </template>

                                <!-- Agent Target Fields -->
                                <template x-if="activeTab === 'agent'">
                                    <select x-model="mappings[col]" @change="onFieldChange(col)" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-emerald-500">
                                        <option value="_skip">✕ এই কলামটি বাদ দিন (Ignore / Skip)</option>
                                        @foreach($agentTargetFields as $groupLabel => $fields)
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach($fields as $fKey => $fLabel)
                                                    <option value="{{ $fKey }}">{{ $fLabel }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </template>

                                <!-- Buyer Target Fields -->
                                <template x-if="activeTab === 'buyer'">
                                    <select x-model="mappings[col]" @change="onFieldChange(col)" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-emerald-500">
                                        <option value="_skip">✕ এই কলামটি বাদ দিন (Ignore / Skip)</option>
                                        @foreach($buyerTargetFields as $groupLabel => $fields)
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach($fields as $fKey => $fLabel)
                                                    <option value="{{ $fKey }}">{{ $fLabel }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </template>
                            </td>
                            <td class="py-3 px-3.5">
                                <template x-if="mappings[col] === 'custom_field' || (mappings[col] && mappings[col].startsWith('custom_field:'))">
                                    <input type="text" x-model="customFieldKeys[col]" placeholder="ফিল্ডের নাম (যেমন: দাগ, লাইসেন্স)"
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

        <!-- Explanations and Smart Rules by Tab -->
        <template x-if="activeTab === 'property'">
            <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-3.5 text-indigo-950 text-xs space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-indigo-900">
                    <span>🧠</span> জমি ও প্লট স্মার্ট ইন্টেলিজেন্ট পার্সার রুলস:
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
                    <div>• <strong>Extra কলাম:</strong> মৌজা, CS/RS/SA দাগ, হস্তান্তর কাস্টম ফিল্ডে সেভ হবে।</div>
                    <div>• <strong>গাণিতিক সাইজ:</strong> <code class="bg-white px-1 rounded text-indigo-800">2.5+2.5</code> যোগ হয়ে ৫ কাঠা সেভ হবে।</div>
                    <div>• <strong>মূল্য রূপান্তর:</strong> <code class="bg-white px-1 rounded text-indigo-800">95 lac</code> বা <code class="bg-white px-1 rounded text-indigo-800">1.25 cr</code> পূর্ণ টাকায় সেভ হবে।</div>
                    <div>• <strong>সোর্স ও এজেন্ট:</strong> এজেন্ট কন্টাক্ট ডিরেক্ট সোর্স হিসেবে অটো-লিংক হবে।</div>
                </div>
            </div>
        </template>

        <template x-if="activeTab === 'flat'">
            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 text-blue-950 text-xs space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-blue-900">
                    <span>🏢</span> ফ্ল্যাট ও অ্যাপার্টমেন্ট স্মার্ট পার্সার রুলস:
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
                    <div>• <strong>সাইজ ও ফ্লোর:</strong> <code class="bg-white px-1 rounded text-blue-800">1350 sqft</code>, <code class="bg-white px-1 rounded text-blue-800">4th floor</code> স্বয়ংক্রিয়ভাবে পার্স হয়ে সেভ হবে।</div>
                    <div>• <strong>বেড, বাথ ও বারান্দা:</strong> কলাম থেকে সংখ্যা (<code class="bg-white px-1 rounded text-blue-800">3 Bed</code>, <code class="bg-white px-1 rounded text-blue-800">2 Bath</code>) সরাসরি ফ্ল্যাট ডিটেইলসে যাবে।</div>
                    <div>• <strong>সুযোগ-সুবিধা:</strong> লিফট, জেনারেটর, গ্যাস ও কার পার্কিং সুবিধা <code class="bg-white px-1 rounded text-blue-800">Yes / No</code> হিসেবে সংরক্ষিত হবে।</div>
                    <div>• <strong>ভল্ট ও গোপন নোট:</strong> ডিফল্টভাবে ড্রাফট স্ট্যাটাসে প্রপার্টি ভল্টে সংরক্ষিত হবে এবং গোপন নোট শুধুমাত্র ব্যাকএন্ডেই থাকবে।</div>
                </div>
            </div>
        </template>

        <template x-if="activeTab === 'agent'">
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3.5 text-emerald-950 text-xs space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-emerald-900">
                    <span>👥</span> এজেন্ট ও মালিক স্মার্ট সিস্টেম রুলস:
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
                    <div>• <strong>মোবাইল নম্বর:</strong> ইউনিক আইডেন্টিফায়ার হিসেবে কাজ করে (ডুপ্লিকেট চেক)।</div>
                    <div>• <strong>ম্যাজিক টোকেন:</strong> প্রতিটি এজেন্টের জন্য স্বয়ংক্রিয় ১-ক্লিক ম্যাজিক লগইন লিংক তৈরি হবে।</div>
                    <div>• <strong>হোয়াটসঅ্যাপ নম্বর:</strong> খালি থাকলে স্বয়ংক্রিয়ভাবে মোবাইল নম্বর ব্যবহার করা হবে।</div>
                    <div>• <strong>প্রোফাইল ও রেটিং:</strong> রেটিং এবং ভেরিফাইড ব্যাজ স্বয়ংক্রিয়ভাবে সেট হবে।</div>
                </div>
            </div>
        </template>

        <template x-if="activeTab === 'buyer'">
            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 text-blue-950 text-xs space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-blue-900">
                    <span>👤</span> বায়ার ও চাহিদা স্মার্ট সিস্টেম রুলস:
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
                    <div>• <strong>অটো ডিমান্ড ক্রিয়েশন:</strong> বায়ার তৈরির সাথে সাথে তার পছন্দ অনুযায়ী CRM ডিমান্ড তৈরি হয়ে যাবে।</div>
                    <div>• <strong>স্মার্ট ম্যাচিং রেডি:</strong> সেভ হওয়া চাহিদাগুলো পরবর্তী প্রপার্টি লিস্টিংয়ের সাথে স্বয়ংক্রিয়ভাবে ম্যাচ করবে।</div>
                    <div>• <strong>ম্যাজিক লগইন:</strong> বায়ারের জন্য পোর্টাল অ্যাক্সেসের ম্যাজিক টোকেন জেনারেট হবে।</div>
                    <div>• <strong>বাজেট ও ব্লক:</strong> বাজেট এবং পছন্দের ব্লক সিআরএম ফিল্টারিংয়ে ব্যবহারের জন্য প্রসেস হবে।</div>
                </div>
            </div>
        </template>
    </div>

    <!-- Step 3: Save as Preset -->
    <div x-show="headers.length > 0" class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4" style="display: none;">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center">৩</div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">
                        এই ম্যাপিং প্রিসেট হিসেবে সংরক্ষণ করুন (<span x-text="activeTabLabel()"></span>)
                    </h3>
                    <p class="text-xs text-slate-500 m-0">ভবিষ্যতে একই ফরম্যাটের গুগল শিটের জন্য এই ম্যাপিংটি ১-ক্লিকে রিইউজ করতে পারবেন।</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">প্রিসেটের নাম *</label>
                <input type="text" x-model="presetName" placeholder="যেমন: স্ট্যান্ডার্ড শিট ফরম্যাট"
                       class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">সংক্ষিপ্ত বিবরণ (ঐচ্ছিক)</label>
                <input type="text" x-model="presetDesc" placeholder="যেমন: কলাম: name, phone, whatsapp..."
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
                    <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">
                        ইমপোর্ট অপশন ও এক্সিকিউশন (<span x-text="activeTabLabel()"></span>)
                    </h3>
                    <p class="text-xs text-slate-500 m-0">ডেটাবেসে সংরক্ষণ ও ডুপ্লিকেট রেকর্ড কীভাবে হ্যান্ডেল হবে নির্ধারণ করুন।</p>
                </div>
            </div>
        </div>

        <!-- Property Specific Options -->
        <template x-if="activeTab === 'property'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">১. ডিফল্ট প্রপার্টি স্ট্যাটাস (Status)</label>
                    <select x-model="defaultStatus" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-500">
                        <option value="draft">⚪ Draft (ড্রাফট / প্রাইভেট ভল্ট)</option>
                        <option value="live">🟢 Live (সরাসরি পাবলিক ক্যাটালগ)</option>
                        <option value="pending">🟡 Pending (পেন্ডিং যাচাই)</option>
                        <option value="sold">⚫ Sold (বিক্রিত)</option>
                        <option value="booked">🔵 Booked (বুকড)</option>
                        <option value="archived">🔴 Archived (আর্কাইভ)</option>
                    </select>
                    <div class="text-[11px] text-slate-500">
                        শিটে স্ট্যাটাস কলাম না থাকলে প্রপার্টি এই স্ট্যাটাসে সেভ হবে। (যেমন: ড্রাফট রাখলে ভল্টে যাবে, লাইভ রাখলে ক্যাটালগে যাবে)।
                    </div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">২. ডুপ্লিকেট হ্যান্ডলিং (Duplicates)</label>
                    <div class="space-y-2 pt-1 text-xs">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="duplicate_mode_prop" value="skip" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-slate-900">🛡️ ডুপ্লিকেট বাদ দিন (Skip)</span>
                                <div class="text-[11px] text-slate-500">একই এরিয়া, ব্লক, রোড ও প্লট থাকলে স্কিপ করবে।</div>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="duplicate_mode_prop" value="update" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-indigo-700">🔄 আপডেট / রিপ্লেস করুন</span>
                                <div class="text-[11px] text-slate-500">বিদ্যমান রেকর্ড পেলে নতুন তথ্য দিয়ে আপডেট করবে।</div>
                            </div>
                        </label>
                    </div>
                </div>

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

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">৪. ডিফল্ট এলাকা / এরিয়া</label>
                    <input type="text" x-model="defaultArea" placeholder="যেমন: Aftabnagar, Bashundhara"
                           class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                    <div class="text-[11px] text-slate-500">শিটের সারিতে এরিয়া ফাঁকা থাকলে এটি ব্যবহার হবে।</div>
                </div>
            </div>
        </template>

        <!-- Flat Specific Options -->
        <template x-if="activeTab === 'flat'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">১. ডিফল্ট স্ট্যাটাস (Status)</label>
                    <select x-model="defaultStatus" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-500">
                        <option value="draft">⚪ Draft (ড্রাফট / প্রাইভেট ভল্ট)</option>
                        <option value="live">🟢 Live (সরাসরি পাবলিক ক্যাটালগ)</option>
                        <option value="pending">🟡 Pending (পেন্ডিং যাচাই)</option>
                        <option value="sold">⚫ Sold (বিক্রিত)</option>
                        <option value="booked">🔵 Booked (বুকড)</option>
                    </select>
                    <div class="text-[11px] text-slate-500">
                        শিটে স্ট্যাটাস না থাকলে এই স্ট্যাটাসে সেভ হবে। (ড্রাফট রাখলে অভ্যন্তরীণ ভল্টে থাকবে)।
                    </div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">২. ডুপ্লিকেট হ্যান্ডলিং (Duplicates)</label>
                    <div class="space-y-2 pt-1 text-xs">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="duplicate_mode_flat" value="skip" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-slate-900">🛡️ ডুপ্লিকেট বাদ দিন (Skip)</span>
                                <div class="text-[11px] text-slate-500">একই বিল্ডিং, ফ্লোর ও সাইজের ফ্ল্যাট থাকলে স্কিপ করবে।</div>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="duplicate_mode_flat" value="update" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-indigo-700">🔄 আপডেট / রিপ্লেস করুন</span>
                                <div class="text-[11px] text-slate-500">বিদ্যমান ফ্ল্যাট পেলে নতুন তথ্য দিয়ে আপডেট করবে।</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">৩. ডিফল্ট এরিয়া / এলাকা</label>
                    <input type="text" x-model="defaultArea" placeholder="যেমন: Aftabnagar, Bashundhara"
                           class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                    <div class="text-[11px] text-slate-500">শিটের সারিতে এরিয়া ফাঁকা থাকলে এটি ব্যবহার হবে।</div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">৪. নির্মাণ / হস্তান্তর অবস্থা</label>
                    <select x-model="flatDefaultCompletion" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                        <option value="ready">🏢 রেডি ফ্ল্যাট (Ready)</option>
                        <option value="under_construction">🏗️ নির্মাণাধীন (Under Construction)</option>
                        <option value="almost_ready">⏳ প্রায় রেডি (Almost Ready)</option>
                    </select>
                    <div class="text-[11px] text-slate-500">শিটে কনস্ট্রাকশন স্ট্যাটাস কলাম না থাকলে প্রযোজ্য হবে।</div>
                </div>
            </div>
        </template>

        <!-- Agent Specific Options -->
        <template x-if="activeTab === 'agent'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">১. ডিফল্ট পদবী (Default Role)</label>
                    <div class="space-y-2 pt-1 text-xs">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="agent_role" value="agent" x-model="agentDefaultRole" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-slate-900">👥 এজেন্ট (Direct Agent)</span>
                                <div class="text-[11px] text-slate-500">প্রপার্টি ব্রোকার বা এজেন্ট ক্যাটাগরি।</div>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="agent_role" value="owner" x-model="agentDefaultRole" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-indigo-700">👑 সরাসরি মালিক (Property Owner)</span>
                                <div class="text-[11px] text-slate-500">সরাসরি জমির/ফ্ল্যাটের মালিক ক্যাটাগরি।</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">২. ডুপ্লিকেট হ্যান্ডলিং (মোবাইল নম্বর)</label>
                    <div class="space-y-2 pt-1 text-xs">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="agent_dup" value="skip" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-slate-900">🛡️ ডুপ্লিকেট বাদ দিন (Skip)</span>
                                <div class="text-[11px] text-slate-500">নম্বর আগে থাকলে স্কিপ করবে (সুপারিশকৃত)।</div>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="agent_dup" value="update" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-indigo-700">🔄 তথ্য আপডেট করুন (Update)</span>
                                <div class="text-[11px] text-slate-500">বিদ্যমান এজেন্টের প্রোফাইল ও নোট আপডেট করবে।</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">৩. ডিফল্ট রেটিং (Star Rating)</label>
                    <select x-model="agentDefaultRating" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                        <option value="5.0">⭐⭐⭐⭐⭐ ৫.০ (উৎকৃষ্ট)</option>
                        <option value="4.8">⭐⭐⭐⭐ ৪.৮ (খুব ভালো)</option>
                        <option value="4.5">⭐⭐⭐⭐ ৪.৫ (ভালো)</option>
                        <option value="4.0">⭐⭐⭐⭐ ৪.০ (সাধারণ)</option>
                    </select>
                    <div class="text-[11px] text-slate-500">শিটে রেটিং না থাকলে এটি প্রয়োগ হবে।</div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">৪. ভেরিফাইড ব্যাজ (Verification)</label>
                    <label class="flex items-center gap-2 pt-2 cursor-pointer text-xs">
                        <input type="checkbox" x-model="agentIsVerified" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-800">✓ সরাসরি ভেরিফাইড এজেন্ট হিসেবে মার্ক করুন</span>
                    </label>
                    <div class="text-[11px] text-slate-500">অ্যাডমিন কর্তৃক ভেরিফাইড গ্রিন টিক ব্যাজ পাবে।</div>
                </div>
            </div>
        </template>

        <!-- Buyer Specific Options -->
        <template x-if="activeTab === 'buyer'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">১. ডিফল্ট প্রপার্টি পছন্দ</label>
                    <select x-model="buyerDefaultType" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                        <option value="land">🏞️ জমি / প্লট (Land)</option>
                        <option value="flat">🏢 ফ্ল্যাট / অ্যাপার্টমেন্ট (Flat)</option>
                        <option value="commercial">🏬 কমার্শিয়াল (Commercial)</option>
                        <option value="land_share">🤝 ল্যান্ড শেয়ার (Land Share)</option>
                    </select>
                    <div class="text-[11px] text-slate-500">শিটে ধরণ না থাকলে এটি ব্যবহার হবে।</div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">২. ডুপ্লিকেট হ্যান্ডলিং (মোবাইল নম্বর)</label>
                    <div class="space-y-2 pt-1 text-xs">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="buyer_dup" value="skip" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-slate-900">🛡️ ডুপ্লিকেট বাদ দিন (Skip)</span>
                                <div class="text-[11px] text-slate-500">বায়ারের নম্বর আগে থাকলে বাদ দেবে।</div>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="buyer_dup" value="update" x-model="duplicateMode" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-indigo-700">🔄 তথ্য আপডেট করুন (Update)</span>
                                <div class="text-[11px] text-slate-500">পছন্দ ও চাহিদা আপডেট করবে।</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">৩. ডিফল্ট এলাকা (Area)</label>
                    <input type="text" x-model="buyerDefaultArea" placeholder="যেমন: Aftabnagar"
                           class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-emerald-500">
                    <div class="text-[11px] text-slate-500">এলাকা ফাঁকা থাকলে এটি বসবে।</div>
                </div>

                <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-xs font-bold text-slate-800 uppercase">৪. স্বয়ংক্রিয় সিআরএম চাহিদা (Demand)</label>
                    <label class="flex items-center gap-2 pt-2 cursor-pointer text-xs">
                        <input type="checkbox" x-model="buyerAutoCreateDemand" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-800">📋 অ্যাক্টিভ বায়ার চাহিদা তৈরি করুন</span>
                    </label>
                    <div class="text-[11px] text-slate-500">অটো-ম্যাচিং ইঞ্জিনের সাথে যুক্ত হবে।</div>
                </div>
            </div>
        </template>

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
                        <span x-text="'বাল্ক ' + activeTabLabel() + ' শুরু করুন'"></span>
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

        <!-- Quick Access Buttons by Tab -->
        <div class="pt-2 flex flex-wrap items-center gap-3">
            <template x-if="activeTab === 'property'">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.properties.directory') }}" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>🗄️</span> প্রাইভেট ভল্ট দেখুন
                    </a>
                    <a href="{{ route('admin.properties.index') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>🏠</span> পাবলিক ক্যাটালগ দেখুন
                    </a>
                </div>
            </template>

            <template x-if="activeTab === 'flat'">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.properties.directory', ['types' => ['flat']]) }}" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>🗄️</span> প্রাইভেট ভল্ট (ফ্ল্যাট) দেখুন
                    </a>
                    <a href="{{ route('admin.properties.index', ['type' => 'flat']) }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>🏠</span> পাবলিক ক্যাটালগ দেখুন
                    </a>
                </div>
            </template>

            <template x-if="activeTab === 'agent'">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.agents.index') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>👥</span> এজেন্ট ও মালিক ডিরেক্টরি দেখুন
                    </a>
                </div>
            </template>

            <template x-if="activeTab === 'buyer'">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.buyers.index') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>👤</span> বায়ার ডিরেক্টরি দেখুন
                    </a>
                    <a href="{{ route('admin.demands.index') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>📋</span> বায়ার চাহিদা তালিকা দেখুন
                    </a>
                </div>
            </template>
        </div>
    </div>

</div>

@push('scripts')
<script>
function sheetImporterApp() {
    return {
        activeTab: '{{ $activeTab ?? "property" }}',
        presets: {!! json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        selectedPresetId: '',
        sheetUrl: '',
        headers: [],
        samples: [],
        mappings: {},
        customFieldKeys: {},

        // Property options
        destination: 'draft',
        defaultStatus: 'draft',
        duplicateMode: 'skip',
        defaultType: 'land',
        defaultArea: 'Aftabnagar',

        // Flat options
        flatDefaultCompletion: 'ready',

        // Agent options
        agentDefaultRole: 'agent',
        agentDefaultRating: 5.0,
        agentIsVerified: true,

        // Buyer options
        buyerDefaultType: 'land',
        buyerDefaultArea: 'Aftabnagar',
        buyerAutoCreateDemand: true,

        presetName: '',
        presetDesc: '',
        isFetchingHeaders: false,
        isSavingPreset: false,
        isImporting: false,
        fetchError: null,
        importResult: null,

        init() {
            this.switchTab(this.activeTab);
        },

        activeTabLabel() {
            if (this.activeTab === 'flat') return 'ফ্ল্যাট ও অ্যাপার্টমেন্ট ইমপোর্ট';
            if (this.activeTab === 'agent') return 'এজেন্ট ও মালিক ইমপোর্ট';
            if (this.activeTab === 'buyer') return 'বায়ার ও চাহিদা ইমপোর্ট';
            return 'জমি ও প্লট ইমপোর্ট';
        },

        getTabPresetsCount(tab) {
            return this.presets.filter(p => (p.entity_type || 'property') === tab).length;
        },

        filteredPresets() {
            return this.presets.filter(p => (p.entity_type || 'property') === this.activeTab);
        },

        switchTab(tab) {
            this.activeTab = tab;
            this.fetchError = null;
            this.importResult = null;

            const tabPresets = this.filteredPresets();
            if (tabPresets.length > 0) {
                this.selectedPresetId = tabPresets[0].id;
                this.onPresetSelected();
            } else {
                this.resetForm();
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
                this.duplicateMode = preset.options.duplicate_mode || 'skip';

                if (this.activeTab === 'property') {
                    this.defaultStatus = preset.options.default_status || preset.options.destination || 'draft';
                    this.destination = this.defaultStatus === 'live' ? 'live' : 'draft';
                    this.defaultType = preset.options.default_type || 'land';
                    this.defaultArea = preset.options.default_area || 'Aftabnagar';
                } else if (this.activeTab === 'flat') {
                    this.defaultStatus = preset.options.default_status || preset.options.destination || 'draft';
                    this.destination = this.defaultStatus === 'live' ? 'live' : 'draft';
                    this.defaultArea = preset.options.default_area || 'Aftabnagar';
                    this.flatDefaultCompletion = preset.options.default_completion_status || 'ready';
                } else if (this.activeTab === 'agent') {
                    this.agentDefaultRole = preset.options.default_role || 'agent';
                    this.agentDefaultRating = preset.options.default_rating || 5.0;
                    this.agentIsVerified = preset.options.is_verified ?? true;
                } else if (this.activeTab === 'buyer') {
                    this.buyerDefaultType = preset.options.default_type || 'land';
                    this.buyerDefaultArea = preset.options.default_area || 'Aftabnagar';
                    this.buyerAutoCreateDemand = preset.options.auto_create_demand ?? true;
                }
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
                    body: JSON.stringify({
                        sheet_url: this.sheetUrl.trim(),
                        entity_type: this.activeTab,
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    this.fetchError = data.error || 'শিটের তথ্য ফেচ করতে ব্যর্থ হয়েছে।';
                    return;
                }

                this.headers = data.headers || [];
                this.samples = data.preview_rows || data.samples || [];

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
                    } else if (data.suggested_mappings && data.suggested_mappings[h]) {
                        const sug = data.suggested_mappings[h];
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
                    this.presetName = this.activeTabLabel() + ' প্রিসেট (' + this.headers.length + ' কলাম)';
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

                if (this.activeTab === 'property') {
                    if (lower.includes('place') || lower.includes('area') || lower.includes('এলাকা')) this.mappings[col] = 'area';
                    else if (lower.includes('block') || lower.includes('ব্লক')) this.mappings[col] = 'block';
                    else if (lower.includes('sector') || lower.includes('সেক্টর')) this.mappings[col] = 'sector';
                    else if (lower.includes('road') || lower.includes('রাস্তা')) this.mappings[col] = 'road_number';
                    else if (lower.includes('plot') || lower.includes('প্লট')) this.mappings[col] = 'plot_number';
                    else if (lower.includes('katha') || lower.includes('কাঠা') || lower.includes('size')) this.mappings[col] = 'size';
                    else if (lower.includes('price') || lower.includes('দাম') || lower.includes('মূল্য')) this.mappings[col] = 'asking_price';
                    else if (lower.includes('agent') || lower.includes('মালিক') || lower.includes('owner')) this.mappings[col] = 'owner_name';
                    else if (lower.includes('contact') || lower.includes('phone') || lower.includes('mobile')) this.mappings[col] = 'owner_phone';
                    else if (lower.includes('extra') || lower.includes('details')) this.mappings[col] = 'parse_extra';
                    else if (lower.includes('time') || lower.includes('line')) { this.mappings[col] = 'custom_field'; this.customFieldKeys[col] = 'হস্তান্তর সময়সীমা'; }
                    else if (lower.includes('messenger')) { this.mappings[col] = 'custom_field'; this.customFieldKeys[col] = 'মেসেঞ্জার লিংক'; }
                } else if (this.activeTab === 'flat') {
                    if (lower.includes('project') || lower.includes('ভবন') || lower.includes('প্রজেক্ট') || lower.includes('building')) this.mappings[col] = 'project_name';
                    else if (lower.includes('dev') || lower.includes('কোম্পানি') || lower.includes('developer')) this.mappings[col] = 'developer_name';
                    else if (lower.includes('place') || lower.includes('area') || lower.includes('এলাকা') || lower.includes('location')) this.mappings[col] = 'area';
                    else if (lower.includes('block') || lower.includes('ব্লক')) this.mappings[col] = 'block';
                    else if (lower.includes('sector') || lower.includes('সেক্টর')) this.mappings[col] = 'sector';
                    else if (lower.includes('road') || lower.includes('রাস্তা')) this.mappings[col] = 'road_number';
                    else if (lower.includes('plot') || lower.includes('holding') || lower.includes('হোল্ডিং')) this.mappings[col] = 'plot_number';
                    else if (lower.includes('sqft') || lower.includes('size') || lower.includes('সাইজ')) this.mappings[col] = 'flat_size_sqft';
                    else if (lower.includes('floor') || lower.includes('তলা') || lower.includes('ফ্লোর')) this.mappings[col] = 'floor_number';
                    else if (lower.includes('bed') || lower.includes('বেড')) this.mappings[col] = 'bedrooms';
                    else if (lower.includes('bath') || lower.includes('বাথ')) this.mappings[col] = 'bathrooms';
                    else if (lower.includes('balcony') || lower.includes('বারান্দা')) this.mappings[col] = 'balconies';
                    else if (lower.includes('facing') || lower.includes('দিক')) this.mappings[col] = 'facing';
                    else if (lower.includes('parking') || lower.includes('গ্যারেজ') || lower.includes('পার্কিং')) this.mappings[col] = 'parking_available';
                    else if (lower.includes('lift') || lower.includes('লিফট')) this.mappings[col] = 'has_lift';
                    else if (lower.includes('generator') || lower.includes('জেনারেটর')) this.mappings[col] = 'has_generator';
                    else if (lower.includes('gas') || lower.includes('গ্যাস')) this.mappings[col] = 'has_gas';
                    else if (lower.includes('price') || lower.includes('দাম') || lower.includes('মূল্য')) this.mappings[col] = 'asking_price';
                    else if (lower.includes('rate') || lower.includes('রেট')) this.mappings[col] = 'price_per_sqft';
                    else if (lower.includes('handover') || lower.includes('status') || lower.includes('হস্তান্তর')) this.mappings[col] = 'completion_status';
                    else if (lower.includes('agent') || lower.includes('মালিক') || lower.includes('owner')) this.mappings[col] = 'owner_name';
                    else if (lower.includes('contact') || lower.includes('phone') || lower.includes('mobile')) this.mappings[col] = 'owner_phone';
                    else if (lower.includes('note') || lower.includes('নোট') || lower.includes('hidden') || lower.includes('মন্তব্য')) this.mappings[col] = 'hidden_note';
                } else if (this.activeTab === 'agent') {
                    if (lower.includes('name') || lower.includes('নাম') || lower.includes('agent') || lower.includes('owner')) this.mappings[col] = 'name';
                    else if (lower.includes('whatsapp') || lower.includes('wa')) this.mappings[col] = 'whatsapp_number';
                    else if (lower.includes('phone') || lower.includes('mobile') || lower.includes('contact') || lower.includes('ফোন')) this.mappings[col] = 'phone';
                    else if (lower.includes('email') || lower.includes('mail') || lower.includes('ইমেইল')) this.mappings[col] = 'email';
                    else if (lower.includes('role') || lower.includes('পদবী')) this.mappings[col] = 'role';
                    else if (lower.includes('agency') || lower.includes('company') || lower.includes('প্রতিষ্ঠান')) this.mappings[col] = 'agency_name';
                    else if (lower.includes('rating') || lower.includes('star') || lower.includes('রেটিং')) this.mappings[col] = 'rating';
                    else if (lower.includes('note') || lower.includes('মন্তব্য')) this.mappings[col] = 'notes';
                } else if (this.activeTab === 'buyer') {
                    if (lower.includes('name') || lower.includes('buyer') || lower.includes('নাম') || lower.includes('ক্রেতা')) this.mappings[col] = 'name';
                    else if (lower.includes('whatsapp') || lower.includes('wa')) this.mappings[col] = 'whatsapp_number';
                    else if (lower.includes('phone') || lower.includes('mobile') || lower.includes('contact') || lower.includes('ফোন')) this.mappings[col] = 'phone';
                    else if (lower.includes('email') || lower.includes('mail')) this.mappings[col] = 'email';
                    else if (lower.includes('type') || lower.includes('ধরণ')) this.mappings[col] = 'preferred_type';
                    else if (lower.includes('area') || lower.includes('location') || lower.includes('এলাকা')) this.mappings[col] = 'preferred_areas';
                    else if (lower.includes('block') || lower.includes('ব্লক')) this.mappings[col] = 'preferred_blocks';
                    else if (lower.includes('budget') || lower.includes('price') || lower.includes('বাজেট')) this.mappings[col] = 'budget_range';
                    else if (lower.includes('time') || lower.includes('সময়')) this.mappings[col] = 'purchase_timeline';
                    else if (lower.includes('note') || lower.includes('demand') || lower.includes('চাহিদা')) this.mappings[col] = 'notes';
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
                let options = {
                    duplicate_mode: this.duplicateMode,
                };

                if (this.activeTab === 'property') {
                    options.destination = this.defaultStatus === 'live' ? 'live' : 'draft';
                    options.default_status = this.defaultStatus;
                    options.default_type = this.defaultType;
                    options.default_area = this.defaultArea;
                } else if (this.activeTab === 'flat') {
                    options.destination = this.defaultStatus === 'live' ? 'live' : 'draft';
                    options.default_status = this.defaultStatus;
                    options.default_type = 'flat';
                    options.default_area = this.defaultArea;
                    options.default_completion_status = this.flatDefaultCompletion;
                } else if (this.activeTab === 'agent') {
                    options.default_role = this.agentDefaultRole;
                    options.default_rating = this.agentDefaultRating;
                    options.is_verified = this.agentIsVerified;
                } else if (this.activeTab === 'buyer') {
                    options.default_type = this.buyerDefaultType;
                    options.default_area = this.buyerDefaultArea;
                    options.auto_create_demand = this.buyerAutoCreateDemand;
                }

                const payload = {
                    entity_type: this.activeTab,
                    preset_id: this.selectedPresetId || null,
                    name: this.presetName.trim(),
                    description: this.presetDesc.trim(),
                    sample_sheet_url: this.sheetUrl.trim(),
                    field_mappings: finalMappings,
                    options: options,
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
                    entity_type: this.activeTab,
                    sheet_url: this.sheetUrl.trim(),
                    mappings: finalMappings,
                    duplicate_mode: this.duplicateMode,
                };

                if (this.activeTab === 'property') {
                    payload.destination = this.defaultStatus === 'live' ? 'live' : 'draft';
                    payload.default_status = this.defaultStatus;
                    payload.default_type = this.defaultType;
                    payload.default_area = this.defaultArea;
                } else if (this.activeTab === 'flat') {
                    payload.destination = this.defaultStatus === 'live' ? 'live' : 'draft';
                    payload.default_status = this.defaultStatus;
                    payload.default_type = 'flat';
                    payload.default_area = this.defaultArea;
                    payload.default_completion_status = this.flatDefaultCompletion;
                } else if (this.activeTab === 'agent') {
                    payload.default_role = this.agentDefaultRole;
                    payload.default_rating = this.agentDefaultRating;
                    payload.is_verified = this.agentIsVerified;
                } else if (this.activeTab === 'buyer') {
                    payload.default_type = this.buyerDefaultType;
                    payload.default_area = this.buyerDefaultArea;
                    payload.auto_create_demand = this.buyerAutoCreateDemand;
                }

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
