@extends('layouts.app')

@section('content')
<div class="aftabnagar-marketplace-wrapper font-sans text-slate-800 antialiased bg-slate-50 py-10 min-h-screen"
     x-data="admSellProperty()"
     @paste.window="handleGlobalPaste($event)">

    <!-- Include pure client-side regex/schema parser script -->
    <script src="{{ asset('assets/js/property-text-parser.js') }}"></script>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Top Role Mode Banner Card -->
        <div class="@if((isset($is_agent_user) && $is_agent_user) || (isset($is_owner_user) && $is_owner_user)) bg-gradient-to-r from-emerald-950 via-slate-900 to-slate-900 border border-emerald-500/30 @else bg-slate-900 @endif text-white rounded-3xl p-6 sm:p-8 text-center space-y-3 shadow-xl"
             style="background-color: #0f172a;">
            @if(isset($is_agent_user) && $is_agent_user)
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-400 text-slate-950 font-extrabold text-[11px] rounded-full uppercase shadow-sm mb-1">
                    🤝 ডিরেক্ট এজেন্ট মোড (Direct Agent Mode)
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold m-0">নতুন প্রপার্টি লিস্টিং যোগ করুন (এজেন্ট হিসেবে)</h1>
                <div class="pt-1 flex justify-center">
                    <button type="button" @click="policyModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-extrabold text-xs rounded-xl border border-emerald-400/40 transition shadow-xs cursor-pointer">
                        <span>📜 পলিসি ও শর্তাবলী দেখুন</span>
                    </button>
                </div>
            @elseif(isset($is_owner_user) && $is_owner_user)
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-400 text-slate-950 font-extrabold text-[11px] rounded-full uppercase shadow-sm mb-1">
                    🏠 প্রপার্টি মালিক মোড (Direct Owner Mode)
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold m-0">নিজের প্রপার্টি পোস্ট করুন (মালিক হিসেবে)</h1>
                <div class="pt-1 flex justify-center">
                    <button type="button" @click="policyModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-extrabold text-xs rounded-xl border border-emerald-400/40 transition shadow-xs cursor-pointer">
                        <span>📜 পলিসি ও শর্তাবলী দেখুন</span>
                    </button>
                </div>
            @else
                <h1 class="text-2xl sm:text-3xl font-extrabold m-0">আপনার প্রপার্টি পোস্ট করুন (Direct Owner / Direct Agent)</h1>
                <div class="pt-1 flex justify-center">
                    <button type="button" @click="policyModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-extrabold text-xs rounded-xl border border-emerald-400/40 transition shadow-xs cursor-pointer">
                        <span>📜 পলিসি ও শর্তাবলী দেখুন</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- MAIN SUBMISSION CONTAINER -->
        <div x-show="!submitted" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xl space-y-6">
            
            <!-- Developer Mode Banner & 1-Click Demo Populate Button -->
            <div x-show="isDevMode"
                class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-2 text-amber-900 text-xs font-bold">
                    <span class="text-lg">🛠️</span>
                    <div>
                        <div>ডিভেলপার মোড সক্রিয় রয়েছে (Developer Mode Active)</div>
                        <div class="text-[10px] text-amber-700 font-normal">টেস্ট করার জন্য এক ক্লিকেই সম্পূর্ণ ফর্ম পূরণ করুন।</div>
                    </div>
                </div>
                <button type="button" @click="populateDemoData()"
                    class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer shrink-0">
                    <span>⚡ ১-ক্লিকে ডেমো ডাটা ফিলআপ (Fill Demo Data)</span>
                </button>
            </div>

            <!-- ⚡ Smart Assistant Box (Free Text & JSON Schema / Importer) -->
            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-slate-50 border border-emerald-300 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-lg shadow-sm">⚡</span>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm sm:text-base m-0">স্মার্ট ফর্ম অ্যাসিস্ট্যান্ট (Smart Auto-Fill & JSON Schema)</h3>
                            <p class="text-[11px] text-slate-500 m-0">Facebook/WhatsApp টেক্সট পার্স করুন অথবা ফর্মের ফিল্ডগুলো স্ক্যান করে JSON তৈরি ও ইমপোর্ট করুন</p>
                        </div>
                    </div>
                    <button type="button" @click="showSmartBox = !showSmartBox"
                        class="text-xs font-bold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 px-3 py-1.5 rounded-xl transition cursor-pointer">
                        <span x-text="showSmartBox ? 'সংকোচন করুন ▲' : 'অ্যাসিস্ট্যান্ট বক্স খুলুন ▼'"></span>
                    </button>
                </div>

                <div x-show="showSmartBox" class="space-y-4 pt-1">
                    <!-- Navigation Tabs: Free Text vs JSON Mode -->
                    <div class="flex items-center gap-2 border-b border-emerald-200/80 pb-2.5">
                        <button type="button" @click="activeSmartTab = 'text'"
                            :class="activeSmartTab === 'text' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-emerald-800 hover:bg-emerald-100 border border-emerald-200'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                            <span>📝 বাংলা/ইংরেজি টেক্সট পার্সার</span>
                        </button>
                        <button type="button" @click="activeSmartTab = 'json'"
                            :class="activeSmartTab === 'json' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-emerald-800 hover:bg-emerald-100 border border-emerald-200'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                            <span>⚙️ JSON স্কিমা ও ইমপোর্ট (JSON Engine)</span>
                        </button>
                    </div>

                    <!-- TAB 1: Free Text Extractor -->
                    <div x-show="activeSmartTab === 'text'" class="space-y-3">
                        <textarea x-model="smartRawText"
                            placeholder="যেমন: আফতাবনগর এফ ব্লকে ৫ কাঠার দক্ষিণমুখী প্লট বিক্রয়। ৩০ ফিট রোড, বাউন্ডারি ও মাটি ভরাট রেডি। কাঠা প্রতি ১ কোটি ৪৫ লাখ টাকা..."
                            class="w-full border-2 border-emerald-300 focus:border-emerald-500 focus:ring-0 rounded-2xl p-3.5 text-xs sm:text-sm bg-white min-h-[95px] leading-relaxed"></textarea>

                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="applySmartExtract()"
                                    :disabled="!smartRawText.trim()"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                    <span>⚡ তথ্য শনাক্ত ও ফর্ম পূরণ করুন</span>
                                </button>
                                <button type="button" x-show="smartRawText.trim()" @click="smartRawText = ''; smartExtractedSummary = []"
                                    class="px-3 py-2 text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer">
                                    ✕ পরিষ্কার
                                </button>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium">বাংলা ও ইংরেজি উভয় ভাষা সাপোর্ট করে (0ms Latency)</span>
                        </div>

                        <!-- Extraction Result Badges -->
                        <div x-show="smartExtractedSummary.length > 0" class="pt-2 border-t border-emerald-200/60 space-y-1.5">
                            <div class="text-[11px] font-bold text-emerald-800">✅ সফলভাবে শনাক্ত করা তথ্য:</div>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(badge, bIdx) in smartExtractedSummary" :key="bIdx">
                                    <span class="inline-flex items-center px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-bold" x-text="badge"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: JSON Schema Generator & Importer -->
                    <div x-show="activeSmartTab === 'json'" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <!-- Section A: 1-Click Scan & Extract Schema -->
                            <div class="bg-white p-4 rounded-2xl border border-emerald-200/90 shadow-2xs space-y-3 flex flex-col justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 text-xs font-extrabold text-slate-900">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">১</span>
                                        <span>ফর্ম স্ক্যান করে JSON টেমপ্লেট সংগ্রহ (Export Schema)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 m-0">ফর্মের সব ফিল্ডের কী (Keys) স্বয়ংক্রিয়ভাবে স্ক্যান করে খালি ভ্যালুর JSON তৈরি করুন ও কপি করুন।</p>
                                </div>

                                <div class="space-y-2">
                                    <button type="button" @click="generateJsonTemplate()"
                                        class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                                        <span>📋 ফিল্ডগুলো স্ক্যান করে JSON তৈরি করুন</span>
                                    </button>

                                    <div x-show="jsonTemplateText" class="space-y-2 pt-1">
                                        <div class="relative">
                                            <textarea readonly x-model="jsonTemplateText"
                                                class="w-full h-36 font-mono text-[11px] bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-800 leading-normal select-all focus:outline-none"></textarea>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 flex-wrap">
                                            <button type="button" @click="copyJsonTemplate()"
                                                :class="jsonCopySuccess ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'"
                                                class="px-4 py-1.5 font-extrabold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                                                <span x-text="jsonCopySuccess ? '✓ সফলভাবে কপি হয়েছে!' : '📋 ১-ক্লিকে কপি করুন'"></span>
                                            </button>
                                            <button type="button" @click="downloadJsonTemplate()"
                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                                                ⬇️ .json ডাউনলোড
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section B: Paste Filled JSON & Populate Form -->
                            <div class="bg-white p-4 rounded-2xl border border-emerald-200/90 shadow-2xs space-y-3 flex flex-col justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 text-xs font-extrabold text-slate-900">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">২</span>
                                        <span>পূরণকৃত JSON পেস্ট করে ফর্ম ফিলআপ (Import Data)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 m-0">ভ্যালু দিয়ে পূরণ করা JSON নিচে পেস্ট করে বাটনে চাপ দিন, সম্পূর্ণ ফর্ম নিমিষেই পূরণ হয়ে যাবে।</p>
                                </div>

                                <div class="space-y-2">
                                    <textarea x-model="jsonInputText"
                                        placeholder='যেমন: { "property_type": "land", "area": "Aftabnagar", "block": "F", "size": 5, "price_per_katha": 14500000, "is_corner": true }'
                                        class="w-full h-32 font-mono text-[11px] bg-white border-2 border-emerald-200 focus:border-emerald-500 rounded-xl p-2.5 text-slate-800 leading-normal focus:outline-none"></textarea>

                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <button type="button" @click="applyJsonImport()"
                                            :disabled="!jsonInputText.trim()"
                                            class="py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                            <span>⚡ JSON থেকে ফর্ম পূরণ করুন</span>
                                        </button>
                                        <button type="button" x-show="jsonInputText.trim()" @click="jsonInputText = ''; jsonImportMsg = ''; jsonImportedSummary = []"
                                            class="text-xs text-slate-400 hover:text-slate-600 font-bold cursor-pointer">
                                            ✕ মুছুন
                                        </button>
                                    </div>

                                    <!-- Feedback Message & Badges -->
                                    <div x-show="jsonImportMsg"
                                        :class="jsonImportSuccess ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-rose-50 text-rose-800 border-rose-300'"
                                        class="p-2.5 rounded-xl border text-[11px] font-bold flex items-center gap-2">
                                        <span x-text="jsonImportSuccess ? '✅' : '⚠️'"></span>
                                        <span x-text="jsonImportMsg"></span>
                                    </div>
                                    <div x-show="jsonImportedSummary.length > 0" class="flex flex-wrap gap-1 pt-1">
                                        <template x-for="(kName, kIdx) in jsonImportedSummary" :key="kIdx">
                                            <span class="inline-flex items-center px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-mono font-bold" x-text="kName"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- FORM START -->
            <form novalidate @submit.prevent="submitProperty()" class="space-y-5 text-xs">
                @csrf

                <!-- Top Error Banner -->
                <div x-show="errorMessage" x-cloak id="adm-form-error-banner"
                    class="p-4 bg-rose-50 border-2 border-rose-300 rounded-2xl text-rose-800 text-xs font-bold flex items-center justify-between gap-3 shadow-sm transition">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base">⚠️</span>
                        <span x-text="errorMessage"></span>
                    </div>
                    <button type="button" @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700 font-extrabold text-sm cursor-pointer p-1">✕</button>
                </div>

                <!-- Owner Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50/80 p-4 rounded-2xl border border-slate-200">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">মালিকের নাম <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                        <input type="text" x-model="formData.owner_name" placeholder="যেমন: আলহাজ্ব মোস্তফা"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block font-bold text-emerald-800 uppercase mb-1">মালিকের ফোন নম্বর / WhatsApp *</label>
                        <input type="tel" id="owner-phone-input" x-model="formData.owner_phone" required placeholder="০১৭১১-০০০০০"
                            class="w-full border border-emerald-300 rounded-xl px-3 py-2.5 text-sm bg-white focus:ring-emerald-500">
                        @if (isset($is_agent_user) && $is_agent_user)
                            <span class="text-[10px] text-emerald-600 font-bold mt-1 block">ℹ️ আপনি এজেন্ট হিসেবে পোস্ট করছেন: এখানে প্রপার্টির মূল মালিকের যোগাযোগের তথ্য প্রদান করুন।</span>
                        @else
                            <span class="text-[10px] text-slate-500 mt-1 block">ℹ️ সরাসরি মালিক হিসেবে আপনার যোগাযোগের তথ্য প্রদান করুন।</span>
                        @endif
                    </div>
                </div>

                <!-- Area, Type, Block/Sub-Area & Sector -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block font-bold text-emerald-800 uppercase mb-1">এলাকা / এরিয়া (Area) *</label>
                        <select x-model="formData.area"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-emerald-700">
                            @foreach (['Aftabnagar' => 'আফতাবনগর (Aftabnagar)', 'Bashundhara R/A' => 'বসুন্ধরা আ/এ', 'Purbachal' => 'পূর্বাচল', 'Banasree' => 'বনশ্রী', 'Badda' => 'বাড্ডা', 'Gulshan' => 'গুলশান', 'Uttara' => 'উত্তরা', 'Dhanmondi' => 'ধানমন্ডি', 'Mirpur' => 'মিরপুর', 'Mohammadpur' => 'মোহাম্মদপুর'] as $aKey => $aLabel)
                                <option value="{{ $aKey }}">{{ $aLabel }}</option>
                            @endforeach
                            <option value="__other__">➕ অন্যান্য এলাকা (Other)</option>
                        </select>
                        <input x-show="formData.area === '__other__'" type="text" x-model="formData.custom_area" placeholder="এলাকার নাম লিখুন..." class="mt-2 w-full border border-emerald-300 rounded-xl px-3 py-2 text-xs bg-white">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">প্রপার্টির ধরন</label>
                        <select x-model="formData.property_type"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-slate-800">
                            <option value="land">জমি / প্লট (Land)</option>
                            <option value="flat">ফ্ল্যাট / অ্যাপার্টমেন্ট (Flat)</option>
                            <option value="land_share">🤝 ল্যান্ড শেয়ার প্রজেক্ট (Land Share)</option>
                            <option value="commercial">🏬 দোকান / কমার্শিয়াল স্পেস (Commercial / Shop)</option>
                        </select>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-slate-700 uppercase m-0 text-xs">
                                <span x-show="formData.area === 'Aftabnagar'">ব্লক (Block)</span>
                                <span x-show="formData.area !== 'Aftabnagar'">ব্লক / সেক্টর / ফেজ</span>
                                <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span>
                            </label>
                            <button type="button" 
                                    @click="formData.address_visibility.block = !formData.address_visibility.block"
                                    class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                    :class="formData.address_visibility.block ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                    :title="formData.address_visibility.block ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                <span x-text="formData.address_visibility.block ? '🔒 হাইড' : '👁️ শো'"></span>
                            </button>
                        </div>
                        <!-- Aftabnagar Block Dropdown -->
                        <div x-show="formData.area === 'Aftabnagar'">
                            <select x-model="formData.block" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                                <option value="">-- ব্লক নির্বাচন করুন --</option>
                                @foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'] as $b)
                                    <option value="{{ $b }}">ব্লক {{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Other Area Free Input -->
                        <div x-show="formData.area !== 'Aftabnagar'">
                            <input type="text" x-model="formData.block" placeholder="যেমন: Block-C / Sector-11"
                                class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-slate-700 uppercase m-0 text-xs">সেক্টর / সাব-এরিয়া <span
                                    class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                            <button type="button" 
                                    @click="formData.address_visibility.sector = !formData.address_visibility.sector"
                                    class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                    :class="formData.address_visibility.sector ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                    :title="formData.address_visibility.sector ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                <span x-text="formData.address_visibility.sector ? '🔒 হাইড' : '👁️ শো'"></span>
                            </button>
                        </div>
                        <input type="text" x-model="formData.sector" placeholder="যেমন: ১ বা ফেজ-২"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                    </div>
                </div>

                <!-- Title (Title is the ONLY required field) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">শিরোনাম / Title *</label>
                    <input type="text" id="prop-title-input" x-model="formData.title"
                        placeholder="যেমন: ব্লক ই-তে ২৫ ফুট রাস্তার ১৬৫০ বর্গফুটের ফ্ল্যাট"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                </div>

                <!-- Location Details: Plot No, Road No, Road Width, Facing -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-slate-700 uppercase m-0 text-xs">
                                প্লট নম্বর <span class="text-slate-400 text-[10px] font-normal">(ঐচ্ছিক)</span>
                            </label>
                            <button type="button" 
                                    @click="formData.address_visibility.plot_number = !formData.address_visibility.plot_number"
                                    class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                    :class="formData.address_visibility.plot_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                    :title="formData.address_visibility.plot_number ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                <span x-text="formData.address_visibility.plot_number ? '🔒 হাইড' : '👁️ শো'"></span>
                            </button>
                        </div>
                        <input type="text" x-model="formData.plot_number"
                            placeholder="যেমন: 42" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-slate-700 uppercase m-0 text-xs">রোড নম্বর <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                            <button type="button" 
                                    @click="formData.address_visibility.road_number = !formData.address_visibility.road_number"
                                    class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                    :class="formData.address_visibility.road_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                    :title="formData.address_visibility.road_number ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                <span x-text="formData.address_visibility.road_number ? '🔒 হাইড' : '👁️ শো'"></span>
                            </button>
                        </div>
                        <input type="text" x-model="formData.road_number" placeholder="যেমন: 3 বা রোড-৩"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-slate-700 uppercase m-0 text-xs">রাস্তার চওড়া (ফুট) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                            <button type="button" 
                                    @click="formData.address_visibility.road_width_ft = !formData.address_visibility.road_width_ft"
                                    class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                    :class="formData.address_visibility.road_width_ft ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                    :title="formData.address_visibility.road_width_ft ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                <span x-text="formData.address_visibility.road_width_ft ? '🔒 হাইড' : '👁️ শো'"></span>
                            </button>
                        </div>
                        <input type="number" x-model.number="formData.road_width_ft" placeholder="20"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">দিক / ফেসিং <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                        <select x-model="formData.facing" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            <option value="">-- দিক নির্বাচন করুন (ঐচ্ছিক) --</option>
                            <option value="south">দক্ষিণমুখী (South)</option>
                            <option value="north">উত্তরমুখী (North)</option>
                            <option value="east">পূর্বমুখী (East)</option>
                            <option value="west">পশ্চিমমুখী (West)</option>
                            <option value="south-east">দক্ষিণ-পূর্ব (South-East)</option>
                            <option value="north-east">উত্তর-পূর্ব (North-East)</option>
                            <option value="south-west">দক্ষিণ-পশ্চিম (South-West)</option>
                            <option value="north-west">উত্তর-পশ্চিম (North-West)</option>
                        </select>
                    </div>
                </div>

                <!-- Map Location -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <label class="block font-bold text-slate-800 uppercase mb-2">🗺️ গুগল ম্যাপ লোকেশন (ঐচ্ছিক)</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-3">
                            <label class="block font-bold text-slate-600 text-[10px] uppercase mb-1">গুগল ম্যাপ পিন লিংক (Google Map Link)</label>
                            <input type="url" x-model="formData.google_map_pin_url" placeholder="https://maps.app.goo.gl/..."
                                class="w-full border rounded-xl px-3 py-2 text-sm bg-white font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 text-[10px] uppercase mb-1">অক্ষাংশ (Latitude)</label>
                            <input type="text" x-model="formData.latitude" placeholder="23.766850"
                                class="w-full border rounded-xl px-3 py-2 text-sm bg-white font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 text-[10px] uppercase mb-1">দ্রাঘিমাংশ (Longitude)</label>
                            <input type="text" x-model="formData.longitude" placeholder="90.435200"
                                class="w-full border rounded-xl px-3 py-2 text-sm bg-white font-mono">
                        </div>
                        <div class="flex items-end">
                            <a href="https://maps.google.com" target="_blank" class="w-full py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl text-center transition">
                                📍 ম্যাপ খুলুন
                            </a>
                        </div>
                    </div>
                </div>

                <!-- LAND SPECIFIC INPUTS -->
                <template x-if="formData.property_type === 'land'">
                    <div class="space-y-4">
                        <!-- Size & Per Katha Price (Auto Calculating Total) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-emerald-50/50 p-4 rounded-2xl border border-emerald-200">
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">সাইজ (কাঠা) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" step="0.01" x-model.number="formData.size"
                                    placeholder="যেমন: 5.0"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-slate-900">
                                <span class="text-[10px] text-slate-500 mt-1 block">জমির পরিমাণ কেবল কাঠায় ইনপুট দিন।</span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">কাঠা প্রতি দাম (টাকা) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" step="10000" x-model.number="formData.price_per_katha"
                                    placeholder="যেমন: 13500000"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-emerald-800">
                                <span class="text-[10px] text-slate-500 mt-1 block">প্রতি কাঠার আস্কিং প্রাইস লিখুন।</span>
                            </div>
                        </div>

                        <!-- Auto Calculated Total Price Box -->
                        <div class="p-4 bg-gradient-to-r from-emerald-900 to-slate-900 rounded-2xl text-white shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] uppercase font-bold text-emerald-300 tracking-wider">মোট আস্কিং দাম (স্বয়ংক্রিয় হিসাবকৃত)</div>
                                <div class="text-xl sm:text-2xl font-black text-white mt-0.5"
                                    x-text="formatPriceBDT(calculateTotal())"></div>
                            </div>
                            <div class="text-xs bg-emerald-700/60 px-3 py-1.5 rounded-xl border border-emerald-400/30 text-emerald-100 font-mono">
                                <span x-text="formData.size || 0"></span> কাঠা × ৳<span
                                    x-text="Number(formData.price_per_katha || 0).toLocaleString('bn-BD')"></span> /কাঠা
                            </div>
                        </div>

                        <!-- Soil Filling Status & Extra Land Characteristics -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">মাটি ভরাট স্ট্যাটাস <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <select x-model="formData.soil_filling_status"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white">
                                    <option value="">-- নির্বাচন করুন (ঐচ্ছিক) --</option>
                                    <option value="completed">ভরাট সম্পন্ন (Soil Filling Completed)</option>
                                    <option value="in_progress">ভরাট চলমান (Filling In Progress)</option>
                                    <option value="needed">ভরাট প্রয়োজন (Soil Filling Needed)</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-200">
                                <label class="flex items-center gap-2 cursor-pointer font-medium text-xs">
                                    <input type="checkbox" x-model="formData.is_corner"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>কর্নার প্লট (Corner Plot)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium text-xs">
                                    <input type="checkbox" x-model="formData.is_lake_facing"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>লেক ভিউ / লেক সংলগ্ন</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium text-xs">
                                    <input type="checkbox" x-model="formData.boundary_wall_done"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>বাউন্ডারি ওয়াল সম্পন্ন</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- FLAT SPECIFIC INPUTS -->
                <template x-if="formData.property_type === 'flat'">
                    <div class="space-y-4">
                        <!-- Flat Size, Per Sqft Rate, Utility Price, Down Payment -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-teal-50/50 p-4 rounded-2xl border border-teal-200">
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">ফ্ল্যাটের সাইজ (বর্গফুট) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.flat_size_sqft"
                                    @input="recalculateFlatFromPerSqft()" placeholder="যেমন: 1650"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">স্কয়ার ফিট প্রতি দাম (টাকা) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.price_per_sqft"
                                    @input="recalculateFlatFromPerSqft()" placeholder="যেমন: 5500"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-teal-800">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">ইউটিলিটি চার্জ (টাকা)</label>
                                <input type="number" x-model.number="formData.utility_price"
                                    @input="recalculateFlatFromPerSqft()" placeholder="যেমন: 200000"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                                <span class="text-[10px] text-slate-500 mt-0.5 block">মোট দামে যুক্ত হবে।</span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">ডাউন পেমেন্ট (টাকা)</label>
                                <input type="number" x-model.number="formData.down_payment"
                                    placeholder="যেমন: 500000"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-emerald-700">
                                <span class="text-[10px] text-slate-500 mt-0.5 block">ঐচ্ছিক বুকিং মানি।</span>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                            <label class="block font-bold text-slate-700 uppercase">বিল্ডিংয়ের সুযোগ সুবিধা (Amenities)</label>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="formData.has_lift" class="rounded text-teal-600">
                                    <span>লিফট (Lift)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="formData.parking_available"
                                        @change="recalculateFlatFromPerSqft()" class="rounded text-teal-600">
                                    <span>কার পার্কিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="formData.has_gas" class="rounded text-teal-600">
                                    <span>গ্যাস কানেকশন</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="formData.has_generator"
                                        class="rounded text-teal-600">
                                    <span>জেনারেটর</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="formData.has_substation"
                                        class="rounded text-teal-600">
                                    <span>সাবস্টেশন</span>
                                </label>
                            </div>

                            <!-- Conditional Garage Price Input Field -->
                            <div x-show="formData.parking_available"
                                class="pt-3 border-t border-slate-200">
                                <div class="max-w-xs">
                                    <label class="block font-bold text-teal-800 uppercase mb-1">🚗 গ্যারেজ / পার্কিং প্রাইস (টাকা)</label>
                                    <input type="number" x-model.number="formData.garage_price"
                                        @input="recalculateFlatFromPerSqft()" placeholder="যেমন: 300000"
                                        class="w-full border border-teal-300 rounded-xl px-3 py-2 text-sm bg-white focus:ring-teal-500 font-semibold">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">কার পার্কিং সেলেক্ট করায় গ্যারেজের দাম মোট দামে যোগ হবে।</span>
                                </div>
                            </div>
                        </div>

                        <!-- Flat Total Asking Price Input & Auto Calculation Box -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center bg-slate-900 p-4 rounded-2xl text-white">
                            <div>
                                <label class="block font-bold text-teal-300 uppercase mb-1 text-[11px]">মোট আস্কিং দাম (টাকা) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.asking_price"
                                    @input="recalculateFlatFromTotal()" placeholder="যেমন: 8500000"
                                    class="w-full border border-teal-500/40 rounded-xl px-3 py-2.5 text-base bg-slate-800 font-extrabold text-teal-200 focus:ring-teal-400">
                                <span class="text-[10px] text-slate-400 mt-1 block">💡 টিপস: আপনি চাইলে এখানে সরাসরি মোট দাম বসাতে পারেন, স্কয়ার ফিট প্রতি দাম স্বয়ংক্রিয় হিসাব হয়ে যাবে।</span>
                            </div>
                            <div class="text-right space-y-1">
                                <div class="text-[11px] font-bold text-slate-400 uppercase">হিসাবের সারসংক্ষেপ:</div>
                                <div class="text-lg font-black text-emerald-400"
                                    x-text="formatPriceBDT(calculateTotal())"></div>
                                <div class="text-[10px] text-slate-300 font-mono">
                                    <span x-text="formData.flat_size_sqft || 0"></span> sqft × ৳<span
                                        x-text="formData.price_per_sqft || 0"></span>/sqft
                                    <template x-if="formData.parking_available && formData.garage_price > 0">
                                        <span> + ৳<span
                                                x-text="Number(formData.garage_price).toLocaleString('bn-BD')"></span> (পার্কিং)</span>
                                    </template>
                                    <template x-if="formData.utility_price > 0">
                                        <span> + ৳<span
                                                x-text="Number(formData.utility_price).toLocaleString('bn-BD')"></span> (ইউটিলিটি)</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Completion status, Bedrooms, Bathrooms, Floor Number -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">প্রকল্পের অবস্থা <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <select x-model="formData.completion_status"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                                    <option value="">-- নির্বাচন করুন (ঐচ্ছিক) --</option>
                                    <option value="ready">১০০% রেডি (Ready)</option>
                                    <option value="ongoing">চলমান (Ongoing Construction)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">বেডরুম <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.bedrooms" min="0"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">বাথরুম <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.bathrooms" min="0"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">তলা / Floor Number <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.floor_number" min="0"
                                    placeholder="যেমন: 4" class="w-full border rounded-xl px-3 py-2 text-sm bg-white">
                            </div>
                        </div>
                    </div>
                </template>

                <!-- LAND SHARE SPECIFIC INPUTS -->
                <template x-if="formData.property_type === 'land_share'">
                    <div class="space-y-4">
                        <!-- Project & Developer Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-purple-50/60 p-4 rounded-2xl border border-purple-200">
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">প্রকল্পের নাম (Project Name) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.project_name" placeholder="যেমন: মায়াস এভারগ্রিন / MAAYA ASSETS"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-purple-900">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">ডেভেলপার কোম্পানি (Developer) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.developer_name" placeholder="যেমন: মায়াস এসেটস অ্যান্ড ইনভেস্টমেন্ট"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-slate-800">
                            </div>
                        </div>

                        <!-- Land Size, Building Plan, Flat Size, Asking Share Price -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-purple-50/30 p-4 rounded-2xl border border-purple-200">
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">জমির পরিমাণ (কাঠা) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" step="0.01" x-model="formData.land_size_katha" placeholder="যেমন: 3.25"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-purple-800">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">ভবনের প্ল্যান / অনুমোদন <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.building_stories" placeholder="যেমন: G+8 (৯ তলা)"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">সম্ভাব্য ফ্ল্যাট সাইজ (বর্গফুট) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.flat_size_sqft" placeholder="যেমন: 1350"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-purple-900">
                                <span class="text-[10px] text-slate-500 mt-0.5 block">কমন স্পেস সহ।</span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-900 uppercase mb-1">শেয়ার প্রতি আস্কিং মূল্য (টাকা) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.share_price" placeholder="যেমন: 6200000"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-emerald-700">
                                <span class="text-[10px] text-slate-500 mt-0.5 block">১টি শেয়ারের মোট দাম।</span>
                            </div>
                        </div>

                        <!-- Bed, Bath, Balcony, Units per floor, Total & Available shares -->
                        <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">বেডরুম <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.bedrooms" placeholder="যেমন: 3"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white font-bold">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">বাথরুম <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.bathrooms" placeholder="যেমন: 3"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white font-bold">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">বারান্দা <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.balconies" placeholder="যেমন: 3"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white font-bold">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">প্রতি ফ্লোরে ইউনিট <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.units_per_floor" placeholder="যেমন: 1 বা 2"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">মোট শেয়ার <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.total_shares" placeholder="যেমন: 8"
                                    class="w-full border rounded-xl px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-purple-800 uppercase mb-1">অবশিষ্ট শেয়ার <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="number" x-model.number="formData.available_shares" placeholder="যেমন: 3"
                                    class="w-full border border-purple-300 rounded-xl px-3 py-2 text-sm bg-white font-bold text-purple-700">
                            </div>
                        </div>

                        <!-- Amenities Checkboxes -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                            <label class="block font-bold text-slate-700 uppercase">প্রকল্পের সুযোগ-সুবিধা ও বৈশিষ্ট্য (Amenities)</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 text-xs">
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_parking" class="rounded text-purple-600 focus:ring-purple-500">
                                    <span>১টি পার্কিং (Parking)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_drawing_dining" class="rounded text-purple-600 focus:ring-purple-500">
                                    <span>ড্রয়িং ও ডাইনিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_family_living" class="rounded text-purple-600 focus:ring-purple-500">
                                    <span>ফ্যামিলি লিভিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_lift" class="rounded text-purple-600 focus:ring-purple-500">
                                    <span>লিফট সুবিধা (Lift)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_generator" class="rounded text-purple-600 focus:ring-purple-500">
                                    <span>জেনারেটর ব্যাকআপ</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_substation" class="rounded text-purple-600 focus:ring-purple-500">
                                    <span>সাবস্টেশন</span>
                                </label>
                            </div>
                        </div>

                        <!-- Construction Progress & Timeline -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">কাজের বর্তমান অগ্রগতি <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.current_progress" placeholder="যেমন: পাইলিং সম্পন্ন / বেজমেন্ট চলমান"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-purple-900">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">নির্মাণ শুরুর সময় <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.construction_start_date" placeholder="যেমন: ডিসেম্বর ২০২৪"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">নির্মাণ সময়কাল <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.construction_duration" placeholder="যেমন: ২৪ মাস / ২ বছর"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                        </div>

                        <!-- Location Highlights -->
                        <div class="bg-purple-50/40 p-4 rounded-2xl border border-purple-200">
                            <label class="block font-bold text-purple-950 uppercase mb-1">📍 লোকেশনের বিশেষত্ব ও সুবিধা (ঐচ্ছিক)</label>
                            <textarea x-model="formData.location_highlights" rows="2"
                                placeholder="যেমন: লেক ও খেলার মাঠ সংলগ্ন, মাদরাসা ও মসজিদ কাছাকাছি, নিরিবিলি ও মনোরম পরিবেশ..."
                                class="w-full border rounded-xl px-3 py-2 text-sm bg-white leading-relaxed"></textarea>
                        </div>
                    </div>
                </template>

                <!-- COMMERCIAL SPECIFICATIONS FORM SECTION -->
                <template x-if="formData.property_type === 'commercial'">
                    <div class="space-y-6 border-t border-slate-200 pt-6">
                        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex items-center gap-3">
                            <span class="text-2xl">🏬</span>
                            <div>
                                <h3 class="font-extrabold text-indigo-950 text-sm m-0">দোকান ও কমার্শিয়াল স্পেসিফিকেশন (Commercial Details)</h3>
                                <p class="text-xs text-indigo-700 m-0">দোকান, অফিস, শোরুম বা কমার্শিয়াল ফ্লোরের তথ্য নিখুঁতভাবে প্রদান করুন।</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">স্পেসের ধরন (Type) <span class="text-rose-500">*</span></label>
                                <select x-model="formData.commercial_type" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-slate-800">
                                    <option value="shop">দোকান (Shop)</option>
                                    <option value="office">অফিস স্পেস (Office Space)</option>
                                    <option value="showroom">শোরুম (Showroom)</option>
                                    <option value="open_floor">ওপেন ফ্লোর (Open Floor)</option>
                                    <option value="restaurant">রেস্তোরাঁ / ফুড কোর্ট (Restaurant)</option>
                                    <option value="warehouse">গুদাম / ওয়্যারহাউস (Warehouse)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">মার্কেট / প্লাজার নাম <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.market_name" placeholder="যেমন: ইম্পেরিয়াল প্লাজা"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">দোকান / স্পেস নম্বর <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                                <input type="text" x-model="formData.shop_number" placeholder="যেমন: শপ # ১০৪"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">সাইজ (বর্গফুট / Sqft) <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" x-model="formData.size_sqft" @input="recalculateCommercialFromPerSqft()"
                                    placeholder="যেমন: ৩৫০" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-slate-900">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">প্রতি বর্গফুট দাম (টাকা) <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" x-model="formData.price_per_sqft" @input="recalculateCommercialFromPerSqft()"
                                    placeholder="যেমন: ১৫০০০" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-slate-900">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">মোট আস্কিং মূল্য (টাকা) <span class="text-rose-500">*</span></label>
                                <input type="number" step="any" x-model="formData.asking_price" @input="recalculateCommercialFromTotal()"
                                    placeholder="যেমন: ৫২৫০০০০" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-bold text-emerald-700">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">ফ্লোর নম্বর (Floor)</label>
                                <input type="number" x-model="formData.floor_number" placeholder="যেমন: 1 (নিচতলা হলে 0 বা 1)"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">ভবনের মোট ফ্লোর</label>
                                <input type="number" x-model="formData.total_floors" placeholder="যেমন: 10"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">ফ্রন্টেজ / মুখ চওড়া (ফুট)</label>
                                <input type="number" step="any" x-model="formData.frontage_ft" placeholder="যেমন: 15"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">সিলিং হাইট (ফুট)</label>
                                <input type="number" step="any" x-model="formData.ceiling_height_ft" placeholder="যেমন: 10"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">প্রকল্পের অবস্থা</label>
                                <select x-model="formData.completion_status" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                                    <option value="ready">১০০% রেডি (Ready)</option>
                                    <option value="under_construction">নির্মাণাধীন (Under Construction)</option>
                                    <option value="upcoming">আসন্ন (Upcoming)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">সম্ভাব্য মাসিক ভাড়া আয় (টাকা)</label>
                                <input type="number" step="any" x-model="formData.monthly_rent_yield" placeholder="যেমন: ৩৫০০০"
                                    class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">ওয়াশরুম ব্যবস্থা</label>
                                <select x-model="formData.washroom_type" class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white">
                                    <option value="common">কমন ওয়াশরুম (Common)</option>
                                    <option value="attached">অ্যাটাচড ওয়াশরুম (Attached)</option>
                                    <option value="none">নেই (None)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                            <label class="block font-bold text-slate-700 uppercase">মার্কেট বা ভবনের সুবিধাসমূহ (Features)</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 text-xs">
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_lift" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>লিফট (Lift)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_escalator" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>এস্কেলেটর (Escalator)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_generator" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>জেনারেটর ব্যাকআপ</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_parking" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>কার পার্কিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_central_ac" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>সেন্ট্রাল এসি (Central AC)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_cctv" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>CCTV নজরদারি</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.has_fire_safety" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>ফায়ার সেফটি ব্যবস্থা</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.is_corner" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>কর্নার স্পেস (Corner)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-medium">
                                    <input type="checkbox" x-model="formData.is_main_road_facing" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span>মেইন রোড ফেসিং</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Price Negotiable Switch (Fixed vs Bargainable) -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <label class="block font-bold text-slate-900 uppercase mb-2">দাম কি ফিক্সড নাকি আলোচনা সাপেক্ষ? <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label
                            class="flex items-center gap-3 p-3 bg-white rounded-xl border cursor-pointer transition shadow-xs"
                            :class="formData.is_negotiable == 1 ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/30' : 'border-slate-200'">
                            <input type="radio" name="is_negotiable" value="1" x-model.number="formData.is_negotiable"
                                class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="font-bold text-slate-900 text-xs">💬 আলোচনা সাপেক্ষ (Negotiable)</div>
                                <div class="text-[11px] text-slate-500">ক্রেতার সাথে আলোচনা সাপেক্ষে কিছুটা ছাড় দেওয়া যাবে।</div>
                            </div>
                        </label>

                        <label
                            class="flex items-center gap-3 p-3 bg-white rounded-xl border cursor-pointer transition shadow-xs"
                            :class="formData.is_negotiable == 0 ? 'border-slate-800 ring-2 ring-slate-800/20 bg-slate-100/50' : 'border-slate-200'">
                            <input type="radio" name="is_negotiable" value="0" x-model.number="formData.is_negotiable"
                                class="text-slate-800 focus:ring-slate-800">
                            <div>
                                <div class="font-bold text-slate-900 text-xs">🔒 ফিক্সড প্রাইস (Fixed Price)</div>
                                <div class="text-[11px] text-slate-500">দাম ফিক্সড, কোনো প্রকার বার্গেনিং করা যাবে না।</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Property Images Upload Section -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block font-bold text-slate-800 uppercase">📷 প্রপার্টি ছবি আপলোড (Property Images)</label>
                    </div>
                    <p class="text-[11px] text-slate-500 m-0">জমি/প্লট/ফ্ল্যাটের ছবি আপলোড করুন। আপনার আপলোডকৃত ছবি সার্ভারে স্বয়ংক্রিয়ভাবে পয়েন্ট-টু-পয়েন্ট <strong>WebP</strong> ফরম্যাটে কনভার্ট হয়ে মূল ভারী র-ফাইল ডিলিট হয়ে যাবে।</p>

                    <!-- Upload Drop Zone (Click, Drag & Drop, or Ctrl+V Paste) -->
                    <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center bg-white hover:bg-emerald-50/30 hover:border-emerald-400 transition cursor-pointer relative focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        tabindex="0"
                        @click="$refs.fileInput.click()"
                        @paste="handlePasteImage($event)"
                        @dragover.prevent="$event.currentTarget.classList.add('border-emerald-500', 'bg-emerald-50/40')"
                        @dragleave.prevent="$event.currentTarget.classList.remove('border-emerald-500', 'bg-emerald-50/40')"
                        @drop.prevent="$event.currentTarget.classList.remove('border-emerald-500', 'bg-emerald-50/40'); handleDropFiles($event)">
                        <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" multiple
                            accept="image/*" class="hidden">
                        <div class="space-y-1.5 select-none">
                            <div class="text-3xl">🖼️</div>
                            <div class="font-bold text-slate-800 text-xs sm:text-sm">ছবি নির্বাচন করতে এখানে ক্লিক করুন, ড্র্যাগ করুন অথবা <kbd class="px-2 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono text-[11px] text-emerald-800 font-bold shadow-2xs">Ctrl + V</kbd> দিয়ে পেস্ট করুন</div>
                            <div class="text-[11px] text-slate-500">ব্রাউজার বা যেকোনো জায়গা থেকে ছবি <strong>Copy Image</strong> করে এখানে সরাসরি পেস্ট (Paste) করতে পারবেন</div>
                            <div class="text-[10px] text-slate-400">JPG, PNG, WEBP (একাধিক ছবি একই সাথে আপলোড সম্ভব)</div>
                        </div>
                    </div>

                    <!-- Thumbnails Preview Grid -->
                    <div x-show="images.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                        <template x-for="(img, index) in images" :key="index">
                            <div
                                class="relative group rounded-xl overflow-hidden border border-slate-200 bg-white shadow-xs aspect-[4/3]">
                                <img :src="img.previewUrl" :alt="img.name" class="w-full h-full object-cover">

                                <!-- Cover Badge / Action -->
                                <button type="button" @click.stop="setCover(index)"
                                    class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold transition shadow-xs"
                                    :class="coverIndex === index ? 'bg-emerald-600 text-white' : 'bg-slate-900/80 text-white hover:bg-emerald-600'">
                                    <span x-text="coverIndex === index ? '⭐ কভার ছবি' : 'কভার সেট করুন'"></span>
                                </button>

                                <!-- Remove Button -->
                                <button type="button" @click.stop="removeImage(index)"
                                    class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs hover:bg-rose-700 transition">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- YouTube Video Link Input -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <label class="block font-bold text-slate-800 uppercase mb-1">🎥 ইউটিউব ভিডিও ওয়াচ/ট্যুর লিঙ্ক (YouTube Video Link)</label>
                    <input type="url" x-model="formData.youtube_video_url"
                        placeholder="যেমন: https://www.youtube.com/watch?v=dQw4w9WgXcQ"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm bg-white font-mono">
                    <span class="text-[10px] text-slate-500 mt-1 block">প্রপার্টির কোনো ভিডিও ট্যুর থাকলে ইউটিউব ইউআরএল যুক্ত করতে পারেন।</span>
                </div>

                <!-- অন্যান্য বৈশিষ্ট্য (Other Features - Repeatable Checkbox Field) -->
                <div class="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <label class="block font-bold text-slate-900 uppercase text-xs sm:text-sm m-0 flex items-center gap-1.5">
                                <span>✨</span> অন্যান্য বৈশিষ্ট্য ও সুবিধা (ঐচ্ছিক)
                            </label>
                            <p class="text-[11px] text-slate-500 m-0 mt-0.5">প্রপার্টির বিশেষ বৈশিষ্ট্যগুলো টিক দিন বা নিজের লেবেল লিখে নতুন বৈশিষ্ট্য যোগ করুন।</p>
                        </div>
                        <button type="button" @click="addOtherFeature()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer self-start sm:self-auto">
                            <span>+ নতুন বৈশিষ্ট্য যোগ করুন</span>
                        </button>
                    </div>

                    <!-- Repeatable Checkbox List -->
                    <div class="space-y-2 pt-1">
                        <template x-for="(feat, idx) in formData.other_features" :key="'feat_' + idx + '_' + (typeof feat === 'object' ? (feat.label || '') : feat)">
                            <div class="flex items-center gap-3 bg-white p-2.5 sm:px-3 rounded-xl border border-slate-200 shadow-2xs hover:border-emerald-300 transition">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox"
                                        :checked="typeof feat === 'object' ? (feat.checked !== false) : true"
                                        @change="if (typeof feat === 'object') { feat.checked = $event.target.checked; } else { formData.other_features[idx] = { label: feat, checked: $event.target.checked }; }"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                </label>
                                <input type="text"
                                    :value="typeof feat === 'object' ? (feat.label || '') : feat"
                                    @input="if (typeof feat === 'object') { feat.label = $event.target.value; } else { formData.other_features[idx] = { label: $event.target.value, checked: true }; }"
                                    placeholder="বৈশিষ্ট্যের বিবরণ লিখুন (যেমন: ওয়াইড ড্রেনেজ, বিদ্যুৎ সংযোগ, কর্নার প্লট...)"
                                    class="flex-1 border-0 bg-transparent text-xs text-slate-800 focus:ring-0 focus:outline-none font-medium placeholder:text-slate-400">
                                <button type="button" @click="removeOtherFeature(idx)" title="মুছে ফেলুন"
                                    class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold transition cursor-pointer shrink-0">
                                    ✕
                                </button>
                            </div>
                        </template>

                        <div x-show="!formData.other_features || formData.other_features.length === 0"
                            class="text-center py-4 text-slate-400 text-xs border border-dashed border-slate-300 rounded-xl bg-white">
                            কোনো অতিরিক্ত বৈশিষ্ট্য নেই। উপরের "+ নতুন বৈশিষ্ট্য যোগ করুন" বাটনে ক্লিক করুন।
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">বিস্তারিত বর্ণনা (ঐচ্ছিক)</label>
                    <textarea x-model="formData.description"
                        placeholder="জমির বিশেষ সুবিধা, বাউন্ডারি ওয়াল, বা আশেপাশের পরিবেশের বিবরণ..."
                        class="w-full border rounded-xl px-3 py-2.5 h-24 text-sm bg-white"></textarea>
                </div>

                <!-- Facebook Post Caption (All Users can provide) -->
                <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-2xl space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📱</span>
                            <label class="block font-bold text-slate-800 text-xs uppercase m-0">Facebook পোস্টের ক্যাপশন (সোশ্যাল মিডিয়া - ঐচ্ছিক)</label>
                        </div>
                        <button type="button" @click="generateFbCaption()"
                            class="text-[11px] font-bold text-blue-700 hover:text-blue-900 cursor-pointer">
                            🪄 ক্যাপশন অটো-জেনারেট
                        </button>
                    </div>
                    <textarea x-model="formData.fb_caption"
                        placeholder="ফেসবুক পেজে পোস্ট করার জন্য বিশেষ কোনো ক্যাপশন থাকলে লিখুন (খালি রাখলে স্বয়ংক্রিয় বাংলা ক্যাপশন তৈরি হবে)..."
                        class="w-full border border-blue-200 rounded-xl px-3 py-2 text-xs h-20 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none leading-relaxed"></textarea>
                    
                    @if (auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->is_admin))
                    <!-- Admin Only Facebook Post Option -->
                    <div class="pt-2 border-t border-blue-200/60 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-xs text-blue-950">👑 Facebook পেজে সরাসরি পোস্ট করুন (Admin Only)</span>
                            <p class="text-[11px] text-blue-800 m-0">প্রপার্টি সাবমিট হওয়ার সাথে সাথে ফেসবুক পেজে ছবি ও ক্যাপশনসহ পোস্ট হবে।</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="formData.post_to_facebook" class="sr-only peer">
                            <div class="w-10 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    @endif
                </div>

                <!-- Error Banner Above Submit Button -->
                <div x-show="errorMessage" x-cloak
                    class="p-4 bg-rose-50 border-2 border-rose-300 rounded-2xl text-rose-800 text-xs font-bold flex items-center justify-between gap-3 shadow-xs transition">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base">⚠️</span>
                        <span x-text="errorMessage"></span>
                    </div>
                    <button type="button" @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700 font-extrabold text-sm cursor-pointer p-1">✕</button>
                </div>

                <!-- Submit Button with Animation -->
                <button type="submit" :disabled="isSubmitting"
                    class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-500/80 text-white font-extrabold text-sm rounded-2xl shadow-xl transition flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed">
                    <template x-if="isSubmitting">
                        <span class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>প্রপার্টি সাবমিট হচ্ছে, অনুগ্রহ করে অপেক্ষা করুন...</span>
                        </span>
                    </template>
                    <template x-if="!isSubmitting">
                        <span class="inline-flex items-center gap-2">
                            <span>প্রপার্টি সাবমিট করুন</span>
                            <span class="text-base">&rarr;</span>
                        </span>
                    </template>
                </button>
            </form>
        </div>

        <!-- Submitting Loading Modal Overlay -->
        <div x-show="isSubmitting" class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-xs" style="display: none;" x-cloak>
            <div class="bg-white rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl flex flex-col items-center text-center space-y-4 border border-slate-100">
                <div class="relative w-14 h-14">
                    <div class="w-14 h-14 rounded-full border-4 border-emerald-100 border-t-emerald-600 animate-spin"></div>
                </div>
                <div>
                    <h4 class="font-black text-base text-slate-900 m-0">প্রপার্টি প্রসেসিং হচ্ছে...</h4>
                    <p class="text-xs text-slate-500 m-0 mt-1.5">ছবি রূপান্তর ও তথ্য ডাটাবেজে সংরক্ষণ করা হচ্ছে। দয়া করে অপেক্ষা করুন।</p>
                </div>
            </div>
        </div>

        <!-- Success Screen -->
        <div x-show="submitted" class="bg-white rounded-3xl p-8 border border-slate-200 text-center space-y-4 shadow-xl" style="display: none;" x-cloak>
            <div class="text-5xl">🎉</div>
            <h3 class="font-black text-slate-900 text-xl">ধন্যবাদ! সাবমিশন গ্রহণ করা হয়েছে।</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto" x-text="message"></p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-3">
                <a :href="successRedirectUrl" x-show="successRedirectUrl"
                   class="inline-flex px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow">
                    প্রপার্টি প্রিভিউ দেখুন &rarr;
                </a>
                <a href="{{ auth()->check() ? route('buyer.dashboard') : route('home') }}"
                   class="inline-flex px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow">
                    ড্যাশবোর্ডে যান &rarr;
                </a>
            </div>
        </div>

    </div>

    <!-- Policy & Terms Popup Modal -->
    <div x-show="policyModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs"
        style="display:none;" x-cloak>
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-5 shadow-2xl relative border border-slate-200">
            <button type="button" @click="policyModalOpen = false"
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 text-2xl font-bold cursor-pointer">&times;</button>

            <div class="flex items-center gap-3 border-b pb-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-bold shrink-0">📜</div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-lg m-0">প্ল্যাটফর্ম পলিসি ও শর্তাবলী</h3>
                    <p class="text-xs text-slate-500 m-0 mt-0.5">PropertyKoi.com ডিরেক্ট মার্কেটপ্লেস পার্টনার গাইডলাইন</p>
                </div>
            </div>

            <div class="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-700 leading-relaxed max-h-80 overflow-y-auto whitespace-pre-line space-y-2">
স্বাগতম! PropertyKoi.com ডিরেক্ট মার্কেটপ্লেসে আপনার সম্পত্তি তালিকাভুক্ত করার পূর্বে আমাদের কিছু গুরুত্বপূর্ণ নীতিমালা ও চার্জ পলিসি জেনে নিন:

১. সাকসেস ফি ও সার্ভিস চার্জ:
   • জমি বা ফ্ল্যাট চূড়ান্ত বিক্রয় সম্পন্ন হলে সফল ডিলের উপর প্ল্যাটফর্ম কোঅর্ডিনেশন ফি প্রযোজ্য হতে পারে।
   • তালিকাভুক্তি ও প্রাথমিক ভেরিফিকেশন সম্পূর্ণ বিনামূল্যে।

২. ডিরেক্ট সোর্স ও স্বচ্ছতা:
   • প্ল্যাটফর্মে কোনো ভুয়া বা অসত্য তথ্য প্রদান করা যাবে না।
   • সকল ডিরেক্ট ওনার ও এজেন্টের পরিচিতি প্ল্যাটফর্ম অডিট টিম দ্বারা যাচাই করা হয়।

৩. অননুমোদিত মধ্যস্বত্ব ও বাইপাস নিষেধাজ্ঞা:
   • প্ল্যাটফর্মের সমন্বিত গ্রাহকের সাথে গোপনে যোগাযোগ করে প্ল্যাটফর্ম বাইপাস করার চেষ্টা করলে আইডি স্থায়ীভাবে পেনাল্টি ফ্ল্যাগ করা হবে।

৪. হোয়াটসঅ্যাপ ও ফোন ভেরিফিকেশন:
   • জরুরি প্রয়োজনে আমাদের ভেরিফিকেশন টিম আপনার সাথে যোগাযোগ করবে।
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-[11px] text-slate-400">
                    🔒 প্ল্যাটফর্ম স্বচ্ছতা ও জিরো-স্প্যাম সুরক্ষার জন্য এই নীতিমালা প্রযোজ্য।
                </div>
                <button type="button" @click="policyModalOpen = false"
                    class="w-full sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-lg transition cursor-pointer">
                    ✓ আমি বুঝতে পেরেছি
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        function initAdmSellProperty() {
            if (!window.Alpine) return;
            Alpine.data('admSellProperty', function () {
                return {
                    submitted: false,
                    isSubmitting: false,
                    message: '',
                    successRedirectUrl: '',
                    policyModalOpen: false,
                    lastEditedPriceField: 'per_sqft',
                    showSmartBox: true,
                    activeSmartTab: 'text',
                    smartRawText: '',
                    smartExtractedSummary: [],
                    jsonTemplateText: '',
                    jsonInputText: '',
                    jsonCopySuccess: false,
                    jsonImportMsg: '',
                    jsonImportSuccess: true,
                    jsonImportedSummary: [],
                    images: [],
                    coverIndex: 0,
                    isDevMode: @json($dev_mode ?? true),
                    errorMessage: '',

                    formData: {
                        owner_name: @json($user_display_name ?: ''),
                        owner_phone: @json($user_phone ?: ''),
                        source_type: @json(isset($is_agent_user) && $is_agent_user ? 'direct_agent' : 'direct_owner'),
                        relationship_declaration: @json(isset($is_agent_user) && $is_agent_user ? 'অনুমোদিত ডিরেক্ট এজেন্ট (Direct Agent)' : 'সরাসরি প্রপার্টির মালিক (Direct Owner)'),
                        property_type: 'land',
                        area: 'Aftabnagar',
                        custom_area: '',
                        sub_area: '',
                        title: '',
                        block: 'C',
                        sector: '',
                        road_number: '',
                        road_width_ft: 25,
                        plot_number: '',
                        holding_number: '',
                        address_visibility: {
                            block: true,
                            sector: true,
                            road_number: true,
                            road_width_ft: true,
                            plot_number: true,
                            holding_number: true
                        },
                        facing: 'south',
                        size: '',
                        price_per_katha: '',
                        flat_size_sqft: '',
                        price_per_sqft: '',
                        garage_price: '',
                        utility_price: '',
                        down_payment: '',
                        asking_price: '',
                        is_negotiable: 1,
                        description: '',
                        fb_caption: '',
                        post_to_facebook: false,
                        youtube_video_url: '',
                        google_map_pin_url: '',
                        latitude: '',
                        longitude: '',
                        is_corner: false,
                        is_lake_facing: false,
                        boundary_wall_done: false,
                        soil_filling_status: 'completed',
                        bedrooms: 3,
                        bathrooms: 3,
                        balconies: 2,
                        floor_number: 2,
                        completion_status: 'ready',
                        has_lift: true,
                        parking_available: true,
                        has_generator: true,
                        has_substation: false,
                        has_gas: true,
                        project_name: '',
                        developer_name: '',
                        land_size_katha: '',
                        building_stories: '',
                        units_per_floor: '',
                        total_shares: '',
                        available_shares: '',
                        share_price: '',
                        has_drawing_dining: true,
                        has_family_living: true,
                        has_parking: true,
                        construction_start_date: '',
                        construction_duration: '',
                        current_progress: '',
                        location_highlights: '',
                        commercial_type: 'shop',
                        market_name: '',
                        shop_number: '',
                        size_sqft: '',
                        frontage_ft: '',
                        ceiling_height_ft: '',
                        monthly_rent_yield: '',
                        washroom_type: 'common',
                        has_escalator: false,
                        has_central_ac: false,
                        has_cctv: true,
                        has_fire_safety: true,
                        is_main_road_facing: false,
                        other_features: [
                            { label: 'বিদ্যুৎ সংযোগ', checked: true },
                            { label: 'ওয়াইড ড্রেনেজ ব্যবস্থা', checked: true },
                            { label: 'নিরাপদ আবাসিক পরিবেশ', checked: true }
                        ]
                    },

                    init: function () {
                        var self = this;
                    },

                    addOtherFeature: function () {
                        if (!this.formData.other_features) this.formData.other_features = [];
                        this.formData.other_features.push({ label: '', checked: true });
                    },

                    removeOtherFeature: function (index) {
                        this.formData.other_features.splice(index, 1);
                    },

                    handleGlobalPaste: function (e) {
                        var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                        var isInput = tag === 'input' || tag === 'textarea' || (e.target && e.target.isContentEditable);
                        var hasImage = false;
                        if (e.clipboardData && e.clipboardData.items) {
                            for (var i = 0; i < e.clipboardData.items.length; i++) {
                                if (e.clipboardData.items[i].kind === 'file' && e.clipboardData.items[i].type.startsWith('image/')) {
                                    hasImage = true;
                                    break;
                                }
                            }
                        }
                        if (hasImage && (!isInput || !e.clipboardData.getData('text'))) {
                            this.handlePasteImage(e);
                        }
                    },

                    generateJsonTemplate: function () {
                        if (typeof window.AdmPropertyTextParser === 'undefined' || !window.AdmPropertyTextParser.generateSchema) {
                            alert('পার্সার ইঞ্জিন লোড হয়নি। পেজ রিফ্রেশ করুন।');
                            return;
                        }
                        var formEl = document.querySelector('form') || document.querySelector('.aftabnagar-marketplace-wrapper');
                        var schema = window.AdmPropertyTextParser.generateSchema(formEl, this.formData);
                        this.jsonTemplateText = JSON.stringify(schema, null, 2);
                        this.jsonCopySuccess = false;
                    },

                    copyJsonTemplate: function () {
                        if (!this.jsonTemplateText) return;
                        var self = this;
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(this.jsonTemplateText).then(function () {
                                self.jsonCopySuccess = true;
                                setTimeout(function () { self.jsonCopySuccess = false; }, 2500);
                            });
                        } else {
                            var ta = document.createElement('textarea');
                            ta.value = this.jsonTemplateText;
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            this.jsonCopySuccess = true;
                            setTimeout(function () { self.jsonCopySuccess = false; }, 2500);
                        }
                    },

                    downloadJsonTemplate: function () {
                        if (!this.jsonTemplateText) return;
                        var blob = new Blob([this.jsonTemplateText], { type: 'application/json' });
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'property_fields_template.json';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    },

                    applyJsonImport: function () {
                        if (!this.jsonInputText || !this.jsonInputText.trim()) {
                            this.jsonImportMsg = 'অনুগ্রহ করে পূরণকৃত JSON কোড পেস্ট করুন।';
                            this.jsonImportSuccess = false;
                            return;
                        }
                        if (typeof window.AdmPropertyTextParser === 'undefined' || !window.AdmPropertyTextParser.populateFromJSON) {
                            alert('পার্সার ইঞ্জিন লোড হয়নি। পেজ রিফ্রেশ করুন।');
                            return;
                        }
                        try {
                            var res = window.AdmPropertyTextParser.populateFromJSON(this.jsonInputText, this.formData);
                            this.jsonImportSuccess = true;
                            this.jsonImportMsg = 'সফলভাবে ' + res.count + 'টি ফিল্ড ফর্মটিতে পূরণ করা হয়েছে!';
                            this.jsonImportedSummary = res.populatedKeys || [];

                            if (this.formData.property_type === 'land') {
                                this.calculateTotal();
                            } else if (this.formData.property_type === 'flat') {
                                if (this.formData.price_per_sqft) {
                                    this.recalculateFlatFromPerSqft();
                                } else if (this.formData.asking_price) {
                                    this.recalculateFlatFromTotal();
                                }
                            } else if (this.formData.property_type === 'commercial') {
                                if (this.formData.price_per_sqft) {
                                    this.recalculateCommercialFromPerSqft();
                                } else if (this.formData.asking_price) {
                                    this.recalculateCommercialFromTotal();
                                }
                            }
                        } catch (err) {
                            this.jsonImportSuccess = false;
                            this.jsonImportMsg = err.message || 'JSON পার্স করতে সমস্যা হয়েছে!';
                            this.jsonImportedSummary = [];
                        }
                    },

                    applySmartExtract: function () {
                        if (!this.smartRawText || !this.smartRawText.trim()) return;
                        if (typeof window.AdmPropertyTextParser === 'undefined' || !window.AdmPropertyTextParser.parse) {
                            alert('টেক্সট পার্সার ইঞ্জিন পাওয়া যায়নি। অনুগ্রহ করে পেজটি রিফ্রেশ করুন।');
                            return;
                        }
                        var res = window.AdmPropertyTextParser.parse(this.smartRawText);
                        if (!res || !res.detected) return;

                        var d = res.detected;
                        this.smartExtractedSummary = res.summaryList || [];

                        if (d.property_type) this.formData.property_type = d.property_type;
                        if (d.area) this.formData.area = d.area;
                        if (d.block) this.formData.block = d.block;
                        if (d.sector) this.formData.sector = d.sector;
                        if (d.road_width_ft) this.formData.road_width_ft = d.road_width_ft;
                        if (d.road_number) this.formData.road_number = d.road_number;
                        if (d.plot_number) this.formData.plot_number = d.plot_number;
                        if (d.facing) this.formData.facing = d.facing;

                        if (d.size) this.formData.size = d.size;
                        if (d.flat_size_sqft) this.formData.flat_size_sqft = d.flat_size_sqft;
                        if (d.size_sqft) this.formData.size_sqft = d.size_sqft;

                        if (d.price_per_katha) this.formData.price_per_katha = d.price_per_katha;
                        if (d.price_per_sqft) this.formData.price_per_sqft = d.price_per_sqft;
                        if (d.asking_price) this.formData.asking_price = d.asking_price;

                        if (d.bedrooms) this.formData.bedrooms = d.bedrooms;
                        if (d.bathrooms) this.formData.bathrooms = d.bathrooms;
                        if (d.balconies) this.formData.balconies = d.balconies;
                        if (d.floor_number) this.formData.floor_number = d.floor_number;

                        if (d.is_corner) this.formData.is_corner = true;
                        if (d.is_lake_facing) this.formData.is_lake_facing = true;
                        if (d.boundary_wall_done) this.formData.boundary_wall_done = true;
                        if (d.soil_filling_status) this.formData.soil_filling_status = d.soil_filling_status;
                        if (d.has_lift) this.formData.has_lift = true;
                        if (d.has_generator) this.formData.has_generator = true;
                        if (d.has_gas) this.formData.has_gas = true;
                        if (d.parking_available) this.formData.parking_available = true;
                        if (d.completion_status) this.formData.completion_status = d.completion_status;
                        if (d.other_features && Array.isArray(d.other_features) && d.other_features.length > 0) {
                            this.formData.other_features = d.other_features;
                        }

                        if (d.title && !this.formData.title) {
                            this.formData.title = d.title;
                        } else if (!this.formData.title) {
                            this.formData.title = 'আফতাবনগর ব্লক ' + (d.block || 'সি') + '-তে ' + (d.size || d.flat_size_sqft || '') + ' ' + (d.property_type === 'flat' ? 'বর্গফুটের ফ্ল্যাট' : 'কাঠার প্লট');
                        }

                        if (d.description && !this.formData.description) {
                            this.formData.description = d.description;
                        }
                        if (d.fb_caption && !this.formData.fb_caption) {
                            this.formData.fb_caption = d.fb_caption;
                        }

                        if (this.formData.property_type === 'land') {
                            this.calculateTotal();
                        } else if (this.formData.property_type === 'flat') {
                            if (this.formData.price_per_sqft) {
                                this.recalculateFlatFromPerSqft();
                            } else if (this.formData.asking_price) {
                                this.recalculateFlatFromTotal();
                            }
                        } else if (this.formData.property_type === 'commercial') {
                            if (this.formData.price_per_sqft) {
                                this.recalculateCommercialFromPerSqft();
                            } else if (this.formData.asking_price) {
                                this.recalculateCommercialFromTotal();
                            }
                        }
                    },

                    generateFbCaption: function () {
                        var t = this.formData.title || '';
                        var typeBn = this.formData.property_type === 'flat' ? 'ফ্ল্যাট' : (this.formData.property_type === 'commercial' ? 'দোকান/স্পেস' : 'জমি');
                        var cap = '🏡 ' + (t || (typeBn + ' বিক্রয়')) + '\n';
                        cap += '📍 লোকেশন: ' + (this.formData.area || 'আফতাবনগর') + ', ব্লক: ' + (this.formData.block || '-') + '\n';
                        if (this.formData.size) cap += '📐 সাইজ: ' + this.formData.size + ' কাঠা\n';
                        if (this.formData.flat_size_sqft) cap += '📐 সাইজ: ' + this.formData.flat_size_sqft + ' বর্গফুট\n';
                        if (this.formData.road_width_ft) cap += '🛣️ রোড: ' + this.formData.road_width_ft + ' ফিট\n';
                        if (this.formData.asking_price) cap += '💰 মূল্য: ৳' + Number(this.formData.asking_price).toLocaleString('bn-BD') + ' টাকা (' + (this.formData.is_negotiable == 1 ? 'আলোচনা সাপেক্ষ' : 'ফিক্সড') + ')\n';
                        if (this.formData.owner_phone) cap += '📞 সরাসরি যোগাযোগ: ' + this.formData.owner_phone + '\n';
                        cap += '#RealEstate #PropertyKoi #Aftabnagar #' + typeBn;
                        this.formData.fb_caption = cap;
                    },

                    populateDemoData: function () {
                        if (this.formData.property_type === 'land') {
                            this.formData.owner_name = 'আলহাজ্ব গোলাম রসূল';
                            this.formData.owner_phone = '01711223344';
                            this.formData.title = 'ব্লক এফ-এ ৩০ ফুট রাস্তার ৫.৫ কাঠার দক্ষিণমুখী কর্নার প্লট';
                            this.formData.block = 'F';
                            this.formData.sector = 'সেক্টর-৩';
                            this.formData.plot_number = Math.floor(Math.random() * 800 + 100).toString();
                            this.formData.road_number = '7/A';
                            this.formData.road_width_ft = 30;
                            this.formData.facing = 'south';
                            this.formData.size = 5.5;
                            this.formData.price_per_katha = 14500000;
                            this.formData.is_negotiable = 1;
                            this.formData.is_corner = true;
                            this.formData.is_lake_facing = true;
                            this.formData.boundary_wall_done = true;
                            this.formData.soil_filling_status = 'completed';
                            this.formData.description = 'আফতাবনগর এফ ব্লকের ৭/এ নম্বর রোডের অতি আকর্ষণীয় দক্ষিণমুখী প্লট। প্লটের সামনে ৩০ ফুট পাকা রাস্তা। এক হ্যান্ড মালিকানার নিষ্কণ্টক জমি।';
                            this.formData.youtube_video_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                            this.calculateTotal();
                        } else if (this.formData.property_type === 'land_share') {
                            this.formData.owner_name = 'মায়া এসেটস অ্যান্ড ইনভেস্টমেন্ট';
                            this.formData.owner_phone = '01711000000';
                            this.formData.title = 'আফতাবনগর এম ব্লকে লেক সংলগ্ন ৩.২৫ কাঠায় জি+৮ ল্যান্ড শেয়ার প্রজেক্ট';
                            this.formData.block = 'M';
                            this.formData.sector = 'সেক্টর-৩';
                            this.formData.plot_number = '24';
                            this.formData.road_number = '6';
                            this.formData.road_width_ft = 25;
                            this.formData.facing = 'south';
                            this.formData.project_name = 'মায়া গার্ডেনিয়া (MAAYA GARDENIA)';
                            this.formData.developer_name = 'মায়া এসেটস অ্যান্ড ইনভেস্টমেন্ট';
                            this.formData.land_size_katha = 3.25;
                            this.formData.building_stories = 'G+8 (৯ তলা রাজউক অনুমোদিত)';
                            this.formData.flat_size_sqft = 1350;
                            this.formData.share_price = 6200000;
                            this.formData.asking_price = 6200000;
                            this.formData.bedrooms = '3';
                            this.formData.bathrooms = '3';
                            this.formData.balconies = '3';
                            this.formData.units_per_floor = '1';
                            this.formData.total_shares = 8;
                            this.formData.available_shares = 3;
                            this.formData.has_parking = true;
                            this.formData.has_drawing_dining = true;
                            this.formData.has_family_living = true;
                            this.formData.has_lift = true;
                            this.formData.has_generator = true;
                            this.formData.has_substation = true;
                            this.formData.construction_start_date = 'ডিসেম্বর ২০২৪';
                            this.formData.construction_duration = '২৪ মাস';
                            this.formData.current_progress = 'পাইলিং সম্পন্ন';
                            this.formData.location_highlights = 'লেক ও খেলার মাঠ সংলগ্ন, মাদরাসা ও মসজিদ কাছাকাছি, নিরিবিলি ও মনোরম পরিবেশ';
                            this.formData.is_negotiable = 1;
                            this.formData.description = 'আফতাবনগর এম ব্লকে চমৎকার লোকেশনে মায়া এসেটসের আকর্ষণীয় ল্যান্ড শেয়ার প্রজেক্ট। ৩.২৫ কাঠা জমিতে জি+৮ রাজউক অনুমোদিত দৃষ্টিনন্দন ভবন।';
                            this.formData.youtube_video_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                            this.calculateTotal();
                        } else if (this.formData.property_type === 'commercial') {
                            this.formData.owner_name = 'হাজী মোহাম্মদ ইউনুস';
                            this.formData.owner_phone = '01912345678';
                            this.formData.title = 'আফতাবনগর মেইন রোডের রাজউক অনুমোদিত মার্কেটে নিচতলার কর্নার দোকান বিক্রয়';
                            this.formData.block = 'B';
                            this.formData.commercial_type = 'shop';
                            this.formData.market_name = 'আফতাবনগর প্লাজা মার্কেট';
                            this.formData.shop_number = 'G-12';
                            this.formData.size_sqft = 280;
                            this.formData.price_per_sqft = 25000;
                            this.formData.asking_price = 7000000;
                            this.formData.floor_number = 1;
                            this.formData.total_floors = 6;
                            this.formData.frontage_ft = 14;
                            this.formData.ceiling_height_ft = 11;
                            this.formData.completion_status = 'ready';
                            this.formData.monthly_rent_yield = 40000;
                            this.formData.washroom_type = 'common';
                            this.formData.has_lift = true;
                            this.formData.has_escalator = true;
                            this.formData.has_generator = true;
                            this.formData.has_parking = true;
                            this.formData.has_central_ac = true;
                            this.formData.has_cctv = true;
                            this.formData.has_fire_safety = true;
                            this.formData.is_corner = true;
                            this.formData.is_main_road_facing = true;
                            this.formData.is_negotiable = 1;
                            this.formData.description = 'আফতাবনগর প্রধান সড়ক সংলগ্ন জমজমাট মার্কেটের নিচতলার প্রাইম কর্নার দোকান। বর্তমানে চালু ও নিয়মিত ভালো ভাড়া নিশ্চিত।';
                            this.formData.youtube_video_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                            this.recalculateCommercialFromPerSqft();
                        } else {
                            this.formData.owner_name = 'ইঞ্জিনিয়ার রেজওয়ান আহমেদ';
                            this.formData.owner_phone = '01819998877';
                            this.formData.title = 'ব্লক ই-তে ২৫ ফুট রাস্তার ১৭৫০ বর্গফুটের ১০০% রেডি ফ্ল্যাট';
                            this.formData.block = 'E';
                            this.formData.sector = 'সেক্টর-২';
                            this.formData.plot_number = Math.floor(Math.random() * 800 + 100).toString();
                            this.formData.road_number = '4';
                            this.formData.road_width_ft = 25;
                            this.formData.facing = 'south';
                            this.formData.flat_size_sqft = 1750;
                            this.formData.price_per_sqft = 6200;
                            this.formData.garage_price = 350000;
                            this.formData.utility_price = 250000;
                            this.formData.bedrooms = 3;
                            this.formData.bathrooms = 3;
                            this.formData.balconies = 2;
                            this.formData.floor_number = 4;
                            this.formData.completion_status = 'ready';
                            this.formData.has_lift = true;
                            this.formData.parking_available = true;
                            this.formData.has_generator = true;
                            this.formData.has_substation = true;
                            this.formData.has_gas = true;
                            this.formData.is_negotiable = 1;
                            this.formData.description = 'আফতাবনগর ই ব্লকের বিলাসবহুল ৩ বেডরুমের ফ্ল্যাট। পার্কিং, জেনারেটর ও লিফট সুবিধা রয়েছে।';
                            this.formData.youtube_video_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                            this.recalculateFlatFromPerSqft();
                        }
                    },

                    recalculateCommercialFromPerSqft: function () {
                        this.lastEditedPriceField = 'per_sqft';
                        var sqft = parseFloat(this.formData.size_sqft) || 0;
                        var rate = parseFloat(this.formData.price_per_sqft) || 0;
                        var total = sqft * rate;
                        this.formData.asking_price = total > 0 ? total : '';
                    },

                    recalculateCommercialFromTotal: function () {
                        this.lastEditedPriceField = 'total';
                        var total = parseFloat(this.formData.asking_price) || 0;
                        var sqft = parseFloat(this.formData.size_sqft) || 0;
                        if (sqft > 0 && total >= 0) {
                            this.formData.price_per_sqft = (total / sqft).toFixed(2);
                        }
                    },

                    recalculateFlatFromPerSqft: function () {
                        this.lastEditedPriceField = 'per_sqft';
                        var sqft = parseFloat(this.formData.flat_size_sqft) || 0;
                        var rate = parseFloat(this.formData.price_per_sqft) || 0;
                        var garage = this.formData.parking_available ? (parseFloat(this.formData.garage_price) || 0) : 0;
                        var utility = parseFloat(this.formData.utility_price) || 0;
                        var total = (sqft * rate) + garage + utility;
                        this.formData.asking_price = total > 0 ? total : '';
                    },

                    recalculateFlatFromTotal: function () {
                        this.lastEditedPriceField = 'total';
                        var total = parseFloat(this.formData.asking_price) || 0;
                        var sqft = parseFloat(this.formData.flat_size_sqft) || 0;
                        var garage = this.formData.parking_available ? (parseFloat(this.formData.garage_price) || 0) : 0;
                        var utility = parseFloat(this.formData.utility_price) || 0;
                        var baseFlatPrice = Math.max(0, total - garage - utility);
                        if (sqft > 0 && baseFlatPrice >= 0) {
                            this.formData.price_per_sqft = (baseFlatPrice / sqft).toFixed(2);
                        }
                    },

                    calculateTotal: function () {
                        if (this.formData.property_type === 'land') {
                            var s = parseFloat(this.formData.size) || 0;
                            var p = parseFloat(this.formData.price_per_katha) || 0;
                            var total = s * p;
                            if (total > 0 && !this.formData.asking_price) {
                                this.formData.asking_price = total;
                            }
                            return total > 0 ? total : (this.formData.asking_price || 0);
                        } else if (this.formData.property_type === 'land_share') {
                            return parseFloat(this.formData.share_price) || parseFloat(this.formData.asking_price) || 0;
                        } else if (this.formData.property_type === 'commercial') {
                            if (this.lastEditedPriceField === 'per_sqft') {
                                var sqft = parseFloat(this.formData.size_sqft) || 0;
                                var rate = parseFloat(this.formData.price_per_sqft) || 0;
                                var total = sqft * rate;
                                if (total > 0 && !this.formData.asking_price) this.formData.asking_price = total;
                                return total > 0 ? total : (this.formData.asking_price || 0);
                            }
                            return parseFloat(this.formData.asking_price) || 0;
                        } else {
                            if (this.lastEditedPriceField === 'per_sqft') {
                                var sqft = parseFloat(this.formData.flat_size_sqft) || 0;
                                var rate = parseFloat(this.formData.price_per_sqft) || 0;
                                var garage = this.formData.parking_available ? (parseFloat(this.formData.garage_price) || 0) : 0;
                                var utility = parseFloat(this.formData.utility_price) || 0;
                                var total = (sqft * rate) + garage + utility;
                                if (total > 0 && !this.formData.asking_price) this.formData.asking_price = total;
                                return total > 0 ? total : (this.formData.asking_price || 0);
                            }
                            return parseFloat(this.formData.asking_price) || 0;
                        }
                    },

                    formatPriceBDT: function (amount) {
                        if (!amount || amount <= 0) return '৳০ (দাম দিলে হিসাব হবে)';
                        if (amount >= 10000000) {
                            return '৳' + (amount / 10000000).toFixed(2) + ' কোটি (' + Number(amount).toLocaleString('bn-BD') + ' টাকা)';
                        } else if (amount >= 100000) {
                            return '৳' + (amount / 100000).toFixed(2) + ' লাখ (' + Number(amount).toLocaleString('bn-BD') + ' টাকা)';
                        }
                        return '৳' + Number(amount).toLocaleString('bn-BD') + ' টাকা';
                    },

                    addImagesFromFiles: function (fileList) {
                        var self = this;
                        var files = Array.from(fileList);
                        files.forEach(function (file) {
                            if (file && file.type && file.type.startsWith('image/')) {
                                var reader = new FileReader();
                                reader.onload = function (e) {
                                    self.images.push({
                                        file: file,
                                        previewUrl: e.target.result,
                                        name: file.name || ('pasted_image_' + Date.now() + '.png')
                                    });
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    },

                    handleFileSelect: function (event) {
                        this.addImagesFromFiles(event.target.files);
                    },

                    handleDropFiles: function (event) {
                        if (event.dataTransfer && event.dataTransfer.files) {
                            this.addImagesFromFiles(event.dataTransfer.files);
                        }
                    },

                    handlePasteImage: function (event) {
                        var self = this;
                        var clipboardData = event.clipboardData || window.clipboardData;
                        if (!clipboardData) return;

                        var filesToProcess = [];
                        if (clipboardData.items) {
                            for (var i = 0; i < clipboardData.items.length; i++) {
                                var item = clipboardData.items[i];
                                if (item.kind === 'file' && item.type.startsWith('image/')) {
                                    var blob = item.getAsFile();
                                    if (blob) {
                                        var fileName = blob.name;
                                        if (!fileName || fileName === 'image.png' || fileName === 'blob') {
                                            var ext = item.type.split('/')[1] || 'png';
                                            fileName = 'clipboard_image_' + (self.images.length + 1) + '_' + Date.now() + '.' + ext;
                                        }
                                        var fileObj = new File([blob], fileName, { type: item.type });
                                        filesToProcess.push(fileObj);
                                    }
                                }
                            }
                        }

                        if (filesToProcess.length === 0 && clipboardData.files && clipboardData.files.length > 0) {
                            for (var j = 0; j < clipboardData.files.length; j++) {
                                var f = clipboardData.files[j];
                                if (f.type.startsWith('image/')) {
                                    filesToProcess.push(f);
                                }
                            }
                        }

                        if (filesToProcess.length > 0) {
                            event.preventDefault();
                            self.addImagesFromFiles(filesToProcess);
                        }
                    },

                    removeImage: function (index) {
                        this.images.splice(index, 1);
                        if (this.coverIndex >= this.images.length) {
                            this.coverIndex = Math.max(0, this.images.length - 1);
                        }
                    },

                    setCover: function (index) {
                        this.coverIndex = index;
                    },

                    submitProperty: function () {
                        var self = this;
                        self.errorMessage = '';
                        if (self.isSubmitting) return;

                        if (!self.formData.title || !self.formData.title.trim()) {
                            self.errorMessage = 'অনুগ্রহ করে প্রপার্টির শিরোনাম (Title) প্রদান করুন।';
                            var el = document.getElementById('prop-title-input') || document.getElementById('adm-form-error-banner');
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                if (el.focus) el.focus();
                            }
                            return;
                        }

                        if (!self.formData.owner_phone || !self.formData.owner_phone.trim()) {
                            self.errorMessage = 'অনুগ্রহ করে মালিকের ফোন নম্বর / WhatsApp প্রদান করুন।';
                            var phoneEl = document.getElementById('owner-phone-input') || document.getElementById('adm-form-error-banner');
                            if (phoneEl) {
                                phoneEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                if (phoneEl.focus) phoneEl.focus();
                            }
                            return;
                        }

                        self.isSubmitting = true;
                        var total = self.calculateTotal();
                        var data = new FormData();
                        
                        var selArea = (self.formData.area === '__other__') ? (self.formData.custom_area || 'Other') : (self.formData.area || 'Aftabnagar');
                        data.append('area', selArea);
                        data.append('custom_area', self.formData.custom_area || '');
                        data.append('sub_area', self.formData.sub_area || self.formData.block || '');
                        data.append('owner_name', self.formData.owner_name || '');
                        data.append('owner_phone', self.formData.owner_phone || '');
                        data.append('property_type', self.formData.property_type);
                        data.append('type', self.formData.property_type);
                        data.append('title', self.formData.title);
                        data.append('block', self.formData.block || '');
                        data.append('sector', self.formData.sector || '');
                        data.append('road_number', self.formData.road_number || '');
                        data.append('road_width_ft', self.formData.road_width_ft || 20);
                        data.append('plot_number', self.formData.plot_number || '');
                        data.append('holding_number', self.formData.holding_number || '');
                        data.append('address_visibility', JSON.stringify(self.formData.address_visibility || {}));
                        data.append('facing', self.formData.facing || '');
                        data.append('size', self.formData.size || '');
                        data.append('asking_price', total);
                        data.append('is_negotiable', self.formData.is_negotiable);
                        data.append('source_type', self.formData.source_type || 'direct_owner');
                        data.append('relationship_declaration', self.formData.relationship_declaration || 'সরাসরি প্রপার্টির মালিক (Direct Owner)');
                        data.append('description', self.formData.description || '');
                        data.append('fb_caption', self.formData.fb_caption || '');
                        data.append('post_to_facebook', self.formData.post_to_facebook ? '1' : '0');
                        data.append('youtube_video_url', self.formData.youtube_video_url || '');
                        data.append('google_map_pin_url', self.formData.google_map_pin_url || '');
                        data.append('latitude', self.formData.latitude || '');
                        data.append('longitude', self.formData.longitude || '');
                        data.append('other_features', JSON.stringify(self.formData.other_features || []));

                        // Uploaded Images
                        self.images.forEach(function (imgObj) {
                            data.append('property_images[]', imgObj.file);
                        });
                        data.append('cover_index', self.coverIndex);

                        // Land Specific
                        if (self.formData.property_type === 'land') {
                            data.append('price_per_katha', self.formData.price_per_katha || '');
                            if (self.formData.is_corner) data.append('is_corner', '1');
                            if (self.formData.is_lake_facing) data.append('is_lake_facing', '1');
                            if (self.formData.boundary_wall_done) data.append('boundary_wall_done', '1');
                            data.append('soil_filling_status', self.formData.soil_filling_status || 'completed');
                        }

                        // Flat Specific
                        if (self.formData.property_type === 'flat') {
                            data.append('flat_size_sqft', self.formData.flat_size_sqft || '');
                            data.append('price_per_sqft', self.formData.price_per_sqft || '');
                            data.append('garage_price', self.formData.parking_available ? (self.formData.garage_price || '0') : '0');
                            data.append('utility_price', self.formData.utility_price || '0');
                            data.append('down_payment', self.formData.down_payment || '0');
                            data.append('bedrooms', self.formData.bedrooms || '3');
                            data.append('bathrooms', self.formData.bathrooms || '3');
                            data.append('balconies', self.formData.balconies || '2');
                            data.append('floor_number', self.formData.floor_number || '1');
                            data.append('completion_status', self.formData.completion_status || 'ready');
                            if (self.formData.has_lift) data.append('has_lift', '1');
                            if (self.formData.parking_available) data.append('parking_available', '1');
                            if (self.formData.has_generator) data.append('has_generator', '1');
                            if (self.formData.has_substation) data.append('has_substation', '1');
                            if (self.formData.has_gas) data.append('has_gas', '1');
                        }

                        // Land Share Specific
                        if (self.formData.property_type === 'land_share') {
                            data.append('project_name', self.formData.project_name || '');
                            data.append('developer_name', self.formData.developer_name || '');
                            data.append('land_size_katha', self.formData.land_size_katha || '');
                            data.append('building_stories', self.formData.building_stories || '');
                            data.append('flat_size_sqft', self.formData.flat_size_sqft || '');
                            data.append('bedrooms', self.formData.bedrooms || '');
                            data.append('bathrooms', self.formData.bathrooms || '');
                            data.append('balconies', self.formData.balconies || '');
                            data.append('units_per_floor', self.formData.units_per_floor || '');
                            data.append('total_shares', self.formData.total_shares || '');
                            data.append('available_shares', self.formData.available_shares || '');
                            data.append('share_price', total);
                            if (self.formData.has_parking) data.append('has_parking', '1');
                            if (self.formData.has_drawing_dining) data.append('has_drawing_dining', '1');
                            if (self.formData.has_family_living) data.append('has_family_living', '1');
                            if (self.formData.has_lift) data.append('has_lift', '1');
                            if (self.formData.has_generator) data.append('has_generator', '1');
                            if (self.formData.has_substation) data.append('has_substation', '1');
                            data.append('construction_start_date', self.formData.construction_start_date || '');
                            data.append('construction_duration', self.formData.construction_duration || '');
                            data.append('current_progress', self.formData.current_progress || '');
                            data.append('location_highlights', self.formData.location_highlights || '');
                        }

                        // Commercial Specific
                        if (self.formData.property_type === 'commercial') {
                            data.append('commercial_type', self.formData.commercial_type || 'shop');
                            data.append('market_name', self.formData.market_name || '');
                            data.append('shop_number', self.formData.shop_number || '');
                            data.append('size_sqft', self.formData.size_sqft || '');
                            data.append('price_per_sqft', self.formData.price_per_sqft || '');
                            data.append('floor_number', self.formData.floor_number || '');
                            data.append('frontage_ft', self.formData.frontage_ft || '');
                            data.append('ceiling_height_ft', self.formData.ceiling_height_ft || '');
                            data.append('completion_status', self.formData.completion_status || 'ready');
                            data.append('monthly_rent_yield', self.formData.monthly_rent_yield || '');
                            data.append('washroom_type', self.formData.washroom_type || 'common');
                            if (self.formData.has_lift) data.append('has_lift', '1');
                            if (self.formData.has_escalator) data.append('has_escalator', '1');
                            if (self.formData.has_generator) data.append('has_generator', '1');
                            if (self.formData.has_parking) data.append('has_parking', '1');
                            if (self.formData.has_central_ac) data.append('has_central_ac', '1');
                            if (self.formData.has_cctv) data.append('has_cctv', '1');
                            if (self.formData.has_fire_safety) data.append('has_fire_safety', '1');
                            if (self.formData.is_corner) data.append('is_corner', '1');
                            if (self.formData.is_main_road_facing) data.append('is_main_road_facing', '1');
                        }

                        var submitUrl = '{{ route("property.store") }}';
                        if (window.location && window.location.pathname && window.location.pathname.indexOf('sell-property') !== -1) {
                            submitUrl = window.location.pathname;
                        }

                        fetch(submitUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: data
                        })
                        .then(function (r) {
                            return r.json().then(function (body) {
                                return { ok: r.ok, status: r.status, body: body };
                            }).catch(function () {
                                return { ok: r.ok, status: r.status, body: {} };
                            });
                        })
                        .then(function (result) {
                            self.isSubmitting = false;
                            var res = result.body || {};
                            if (result.ok && res.success) {
                                self.submitted = true;
                                self.message = res.message || 'বিজ্ঞাপনটি সফলভাবে জমা হয়েছে!';
                                if (res.data && res.data.redirect_url) {
                                    self.successRedirectUrl = res.data.redirect_url;
                                }
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            } else {
                                var errText = res.message || 'বিজ্ঞাপন জমা দেওয়া সম্ভব হয়নি।';
                                if (res.errors) {
                                    var errList = Object.values(res.errors).flat();
                                    if (errList.length > 0) {
                                        errText += ' (' + errList.join(', ') + ')';
                                    }
                                }
                                self.errorMessage = errText;
                                var banner = document.getElementById('adm-form-error-banner');
                                if (banner) {
                                    banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }
                        })
                        .catch(function (err) {
                            self.isSubmitting = false;
                            self.errorMessage = 'সার্ভারে যোগাযোগ করা যায়নি অথবা নেটওয়ার্ক সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
                            var banner = document.getElementById('adm-form-error-banner');
                            if (banner) {
                                banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    }
                };
            });
        }

        if (window.Alpine) {
            initAdmSellProperty();
        } else {
            document.addEventListener('alpine:init', initAdmSellProperty);
        }
    })();
</script>
@endpush
@endsection
