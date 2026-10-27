@extends('layouts.admin')

@section('title', 'প্রাইভেট প্রপার্টি ডিরেক্টরি (Internal Vault)')
@section('page_title', 'প্রাইভেট প্রপার্টি ডিরেক্টরি')

@section('page_badge')
    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1.5 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
        অভ্যন্তরীণ ডাটাবেস ভল্ট (Non-Public)
    </span>
@endsection

@section('content')
<div class="space-y-6" x-data="propertyDirectory()">

    <!-- Hero / Information Notice -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-3xl p-6 shadow-sm border border-slate-700/60 relative overflow-hidden">
        <div class="absolute -right-12 -bottom-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2 max-w-3xl">
                <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold tracking-wide uppercase">
                    <span>🔒 প্রাইভেট ডাটাবেস রিপোজিটরি</span>
                    <span>•</span>
                    <span>100% অফলাইন ও সার্চ ইঞ্জিন সুরক্ষিত</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
                    অভ্যন্তরীণ প্রপার্টি ভল্ট &amp; ১-ক্লিক SEO/AEO লঞ্চপ্যাড
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    এই তালিকার প্রপার্টিগুলো সরাসরি জনসাধারণের কাছে দৃশ্যমান নয়—শুধুমাত্র আপনার ব্যাকএন্ড ডাটাবেসে সংরক্ষিত। যেকোনো প্রপার্টি বাজারে রিলিজ করতে চাইলে <strong class="text-emerald-300">"১-ক্লিকে পাবলিক লিস্টিং"</strong> চাপুন। স্বয়ংক্রিয়ভাবে ক্লিন স্লাগ, মেটাডাটা, OpenGraph, Schema.org LD-JSON গ্রাফ এবং AI রেজাল্ট ফ্রেন্ডলি FAQ সহ সাইটে লাইভ হয়ে যাবে এবং সার্চ ইঞ্জিন ও বায়ারদের অ্যালার্ট চলে যাবে।
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('property.create', ['destination' => 'vault']) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition transform active:scale-95">
                    <span>➕</span> নতুন প্রপার্টি (ভল্ট)
                </a>
                <a href="{{ route('admin.sheet-importer.index', ['tab' => 'property']) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md transition">
                    <span>🏞️</span> ল্যান্ড শিট ইমপোর্ট
                </a>
                <a href="{{ route('admin.sheet-importer.index', ['tab' => 'flat']) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-md transition">
                    <span>🏢</span> ফ্ল্যাট শিট ইমপোর্ট
                </a>
                <a href="{{ route('admin.properties.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800/80 hover:bg-slate-700 text-white font-semibold text-xs rounded-xl border border-slate-600/60 transition">
                    <span>🏠</span> লাইভ ক্যাটালগ
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">মোট প্রাইভেট</div>
            <div class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $stats['total_private'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-amber-600">ড্রাফট (Draft)</div>
            <div class="text-xl font-extrabold text-amber-700 mt-0.5">{{ $stats['draft'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-indigo-600">পেন্ডিং যাচাই</div>
            <div class="text-xl font-extrabold text-indigo-700 mt-0.5">{{ $stats['pending'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-emerald-600">জমি / প্লট</div>
            <div class="text-xl font-extrabold text-emerald-700 mt-0.5">{{ $stats['plots'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs group">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold text-blue-600">রেডি ফ্ল্যাট</div>
                <a href="{{ route('admin.sheet-importer.index', ['tab' => 'flat']) }}" class="text-[10px] text-blue-600 hover:underline font-bold" title="শিট থেকে ফ্ল্যাট ইমপোর্ট করুন">
                    📥 ইমপোর্ট
                </a>
            </div>
            <div class="text-xl font-extrabold text-blue-700 mt-0.5">{{ $stats['flats'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-purple-600">কমার্শিয়াল</div>
            <div class="text-xl font-extrabold text-purple-700 mt-0.5">{{ $stats['commercial'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-rose-600">ল্যান্ড শেয়ার</div>
            <div class="text-xl font-extrabold text-rose-700 mt-0.5">{{ $stats['share'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Search & Filters (With Bilingual Search, Keyword, Hidden Note & Multiselect) -->
    <div class="bg-white p-4.5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
        <form method="GET" action="{{ route('admin.properties.directory') }}" class="space-y-3 text-xs">
            <!-- Row 1: 3 Search Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- 1. Main Search (Bilingual) -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1 text-[11px]">
                        🔍 মূল অনুসন্ধান (Main Search)
                    </label>
                    <input type="text" name="search" value="{{ $filterCode }}"
                           placeholder="কোড, টাইটেল, ব্লক (এইচ ব্লক / Block H), রোড, প্লট বা ফোন..."
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-slate-50/50">
                </div>

                <!-- 2. Keyword / Tag Search -->
                <div>
                    <label class="block font-bold text-amber-900 mb-1 text-[11px] flex items-center gap-1">
                        <span>🏷️</span> কিওয়ার্ড / ট্যাগ অনুসন্ধান (Keyword / Tag)
                    </label>
                    <input type="text" name="keyword" value="{{ $filterKeyword }}"
                           placeholder="ট্যাগ দিয়ে খুঁজুন (যেমন: জরুরী বিক্রয়, বায়া দলিল, লোন)..."
                           class="w-full px-3.5 py-2.5 border border-amber-300/80 bg-amber-50/40 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none text-amber-950 font-medium">
                </div>

                <!-- 3. Hidden Note Text Search -->
                <div>
                    <label class="block font-bold text-indigo-950 mb-1 text-[11px] flex items-center gap-1">
                        <span>🔒</span> গোপন নোট অনুসন্ধান (Hidden Note Text)
                    </label>
                    <input type="text" name="note" value="{{ $filterNote }}"
                           placeholder="গোপন নোটের ভেতরের যে কোনো বাক্য/শব্দ খুঁজুন..."
                           class="w-full px-3.5 py-2.5 border border-indigo-200 bg-indigo-50/40 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none text-indigo-950 font-medium">
                </div>
            </div>

            <!-- Row 2: Multiselect Type, Multiselect Status, Area & Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-4 md:grid-cols-7 gap-3 pt-1">
                <!-- Multiselect Type (2 cols) -->
                <div class="sm:col-span-2 relative" @click.outside="typeDropdownOpen = false">
                    <label class="block font-bold text-slate-700 mb-1 text-[11px]">
                        প্রপার্টির ধরন (Types - Multiselect)
                    </label>
                    <button type="button" @click="typeDropdownOpen = !typeDropdownOpen"
                            class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl bg-white hover:bg-slate-50 flex items-center justify-between text-left focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-2xs font-semibold">
                        <span class="truncate" x-text="getTypeButtonLabel()"></span>
                        <span class="text-slate-400 ml-1.5 text-[10px]">▼</span>
                    </button>

                    <!-- Type Dropdown Panel -->
                    <div x-show="typeDropdownOpen" x-cloak
                         class="absolute left-0 top-full mt-1.5 w-72 bg-white rounded-2xl shadow-xl border border-slate-200 p-3 z-40 space-y-2 animate-fade-in text-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 font-bold text-slate-700">
                            <span>ধরন নির্বাচন করুন:</span>
                            <div class="flex items-center gap-2 text-[10px]">
                                <button type="button" @click="selectAllTypes()" class="text-emerald-600 hover:underline">সব</button>
                                <span>|</span>
                                <button type="button" @click="clearAllTypes()" class="text-slate-400 hover:underline">রিসেট</button>
                            </div>
                        </div>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto">
                            <template x-for="opt in typeOptions" :key="opt.val">
                                <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" name="types[]" :value="opt.val"
                                           :checked="selectedTypes.includes(opt.val)"
                                           @change="toggleType(opt.val)"
                                           class="w-4 h-4 rounded accent-emerald-600 cursor-pointer">
                                    <span class="font-medium text-slate-800" x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Multiselect Status (2 cols) -->
                <div class="sm:col-span-2 relative" @click.outside="statusDropdownOpen = false">
                    <label class="block font-bold text-slate-700 mb-1 text-[11px]">
                        স্ট্যাটাস ফিল্টার (Status - Multiselect)
                    </label>
                    <button type="button" @click="statusDropdownOpen = !statusDropdownOpen"
                            class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl bg-white hover:bg-slate-50 flex items-center justify-between text-left focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-2xs font-semibold">
                        <span class="truncate" x-text="getStatusButtonLabel()"></span>
                        <span class="text-slate-400 ml-1.5 text-[10px]">▼</span>
                    </button>

                    <!-- Status Dropdown Panel -->
                    <div x-show="statusDropdownOpen" x-cloak
                         class="absolute left-0 top-full mt-1.5 w-72 bg-white rounded-2xl shadow-xl border border-slate-200 p-3 z-40 space-y-2 animate-fade-in text-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 font-bold text-slate-700">
                            <span>স্ট্যাটাস নির্বাচন করুন:</span>
                            <div class="flex items-center gap-2 text-[10px]">
                                <button type="button" @click="selectAllStatuses()" class="text-emerald-600 hover:underline">সব</button>
                                <span>|</span>
                                <button type="button" @click="clearAllStatuses()" class="text-slate-400 hover:underline">রিসেট</button>
                            </div>
                        </div>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto">
                            <template x-for="opt in statusOptions" :key="opt.val">
                                <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" name="statuses[]" :value="opt.val"
                                           :checked="selectedStatuses.includes(opt.val)"
                                           @change="toggleStatus(opt.val)"
                                           class="w-4 h-4 rounded accent-emerald-600 cursor-pointer">
                                    <span class="font-medium text-slate-800" x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Area Dropdown (1 col) -->
                <div class="sm:col-span-1 md:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1 text-[11px]">
                        এলাকা (Area)
                    </label>
                    <select name="area" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-white font-medium">
                        <option value="all">সকল এলাকা</option>
                        <option value="Aftabnagar" {{ request('area') === 'Aftabnagar' ? 'selected' : '' }}>আফতাবনগর</option>
                        <option value="Bashundhara" {{ request('area') === 'Bashundhara' ? 'selected' : '' }}>বসুন্ধরা</option>
                        <option value="Banasree" {{ request('area') === 'Banasree' ? 'selected' : '' }}>বনশ্রী</option>
                        <option value="Uttara" {{ request('area') === 'Uttara' ? 'selected' : '' }}>উত্তরা</option>
                        <option value="Gulshan" {{ request('area') === 'Gulshan' ? 'selected' : '' }}>গুলশান</option>
                        <option value="Mirpur" {{ request('area') === 'Mirpur' ? 'selected' : '' }}>মিরপুর</option>
                    </select>
                </div>

                <!-- Filter & Reset Buttons (2 cols) -->
                <div class="sm:col-span-2 md:col-span-2 flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition cursor-pointer shadow-sm text-center">
                        ফিল্টার করুন
                    </button>
                    @if(request()->hasAny(['search', 'keyword', 'note', 'types', 'type', 'statuses', 'status', 'area']))
                        <a href="{{ route('admin.properties.directory') }}" class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition text-center" title="সব ফিল্টার রিসেট">
                            ✕
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Sticky Floating Bulk Action Bar -->
    <div x-show="selectedIds.length > 0" x-cloak
         class="sticky top-4 z-30 bg-slate-900 text-white p-3.5 rounded-2xl shadow-2xl border border-slate-700 flex flex-wrap items-center justify-between gap-3 animate-fade-in">
        <div class="flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="font-black text-xs sm:text-sm text-emerald-300">
                <span x-text="selectedIds.length"></span>টি প্রপার্টি নির্বাচিত হয়েছে
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Bulk Status Selector -->
            <div class="flex items-center gap-1.5 bg-slate-800 p-1 rounded-xl border border-slate-700">
                <span class="text-[11px] text-slate-300 font-bold px-2">বাল্ক স্ট্যাটাস:</span>
                <select x-model="selectedBulkStatus" class="bg-slate-900 text-white text-xs font-semibold rounded-lg px-2.5 py-1.5 border border-slate-600 focus:outline-none focus:border-emerald-500">
                    <option value="draft">⚪ Draft (ড্রাফট / ভল্ট)</option>
                    <option value="live">🟢 Live (পাবলিক ক্যাটালগ)</option>
                    <option value="pending">🟡 Pending (পেন্ডিং যাচাই)</option>
                    <option value="sold">⚫ Sold (বিক্রিত)</option>
                    <option value="booked">🔵 Booked (বুকড)</option>
                    <option value="archived">🔴 Archived (আর্কাইভ)</option>
                </select>
                <button type="button" @click="executeBulkStatus()" :disabled="isUpdatingBulk" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition cursor-pointer">
                    <template x-if="!isUpdatingBulk"><span>প্রয়োগ করুন</span></template>
                    <template x-if="isUpdatingBulk"><span>আপডেট হচ্ছে...</span></template>
                </button>
            </div>

            <!-- Bulk Delete Button -->
            <button type="button" @click="executeBulkDelete()" :disabled="isDeletingBulk" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                <span>🗑️</span>
                <template x-if="!isDeletingBulk"><span>নির্বাচিত প্রপার্টি মুছুন (Bulk Delete)</span></template>
                <template x-if="isDeletingBulk"><span>মুছে ফেলা হচ্ছে...</span></template>
            </button>

            <!-- Deselect All -->
            <button type="button" @click="selectedIds = []" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition cursor-pointer">
                বাতিল
            </button>
        </div>
    </div>

    <!-- Properties Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox" @change="toggleSelectAll($event)" :checked="selectedIds.length === totalRowsOnPage && totalRowsOnPage > 0" class="w-4 h-4 rounded accent-emerald-600 cursor-pointer">
                        </th>
                        <th class="p-4">প্রপার্টি বিবরণ</th>
                        <th class="p-4">লোকেশন ও স্পেক্স</th>
                        <th class="p-4">মূল্য বিবরণ</th>
                        <th class="p-4">সরাসরি মালিক / সোর্স</th>
                        <th class="p-4 text-center">স্ট্যাটাস পরিবর্তন</th>
                        <th class="p-4 text-right">লঞ্চ / অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($properties as $property)
                        @php
                            $primarySource = $property->directSources->first();
                            $ownerPhone = $primarySource?->owner_phone ?: ($property->creator?->phone ?: '');
                            $ownerName = $primarySource?->owner_name ?: ($property->creator?->name ?: 'অজানা');
                            $cleanPhone = preg_replace('/[^0-9]/', '', $ownerPhone);
                            $waPhone = str_starts_with($cleanPhone, '0') ? ('88' . $cleanPhone) : $cleanPhone;
                            $thumb = $property->media->first()?->thumbnail_url ?: ($property->media->first()?->file_url ?: '');
                        @endphp
                        <tr id="property-row-{{ $property->id }}" class="hover:bg-slate-50/80 transition group" :class="selectedIds.includes('{{ $property->id }}') ? 'bg-emerald-50/40' : ''">
                            <!-- Checkbox -->
                            <td class="p-4 text-center">
                                <input type="checkbox" value="{{ $property->id }}" x-model="selectedIds" class="w-4 h-4 rounded accent-emerald-600 cursor-pointer">
                            </td>

                            <!-- Property Info -->
                            <td class="p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-14 h-14 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center text-slate-400 cursor-pointer hover:opacity-85 transition group"
                                         @click="openViewDrawer('{{ $property->id }}')"
                                         title="ক্লিক করে সম্পূর্ণ ডিটেইলস ভিউ ড্রয়ার দেখুন">
                                        @if(!empty($thumb))
                                            <img src="{{ $thumb }}" alt="{{ $property->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        @else
                                            <span class="text-xl">
                                                @if($property->type === 'land') 🏞️
                                                @elseif($property->type === 'flat') 🏢
                                                @elseif($property->type === 'commercial') 🏬
                                                @else 🤝 @endif
                                            </span>
                                        @endif
                                    </div>
                                    <div class="space-y-1 max-w-xs sm:max-w-sm">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-900 text-amber-400 cursor-pointer"
                                                  @click="openViewDrawer('{{ $property->id }}')">
                                                #{{ $property->property_code }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-slate-100 text-slate-700">
                                                @if($property->type === 'land') জমি / প্লট
                                                @elseif($property->type === 'flat') রেডি ফ্ল্যাট
                                                @elseif($property->type === 'commercial') কমার্শিয়াল
                                                @else ল্যান্ড শেয়ার @endif
                                            </span>
                                        </div>
                                        <div class="font-bold text-slate-900 line-clamp-1 leading-snug hover:text-emerald-700 cursor-pointer transition"
                                             @click="openViewDrawer('{{ $property->id }}')"
                                             title="ক্লিক করে সম্পূর্ণ ডিটেইলস ভিউ ড্রয়ার দেখুন">
                                            {{ $property->title }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            যোগ হয়েছে: {{ $property->created_at->format('d M, Y') }} ({{ $property->created_at->diffForHumans() }})
                                        </div>

                                        <!-- Extracted Tags Pills -->
                                        <template x-if="notesData['{{ $property->id }}'] && notesData['{{ $property->id }}'].tags && notesData['{{ $property->id }}'].tags.length > 0">
                                            <div class="flex items-center gap-1 flex-wrap pt-0.5">
                                                <template x-for="t in notesData['{{ $property->id }}'].tags" :key="t">
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                                        <span class="text-[9px]">🏷️</span> <span x-text="t"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Hidden Note Snippet & Edit Trigger -->
                                        <div class="flex items-center gap-1.5 pt-0.5">
                                            <button type="button"
                                                    @click="openNoteModal('{{ $property->id }}', '{{ $property->property_code }}', '{{ addslashes($property->title) }}')"
                                                    :class="notesData['{{ $property->id }}'] && notesData['{{ $property->id }}'].note ? 'bg-amber-50 text-amber-950 border-amber-300 hover:bg-amber-100' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[11px] font-bold cursor-pointer transition shadow-2xs">
                                                <span>🔒</span>
                                                <span x-text="notesData['{{ $property->id }}'] && notesData['{{ $property->id }}'].note ? 'গোপন নোট: ' + truncate(notesData['{{ $property->id }}'].note, 26) : '+ গোপন নোট লিখুন'"></span>
                                                <span class="text-[10px] opacity-75">✏️</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Location & Specs -->
                            <td class="p-4">
                                <div class="space-y-1">
                                    <div class="font-semibold text-slate-800 flex items-center gap-1">
                                        <span>📍</span> {{ $property->area ?: 'আফতাবনগর' }}
                                        @if($property->block)
                                            <span class="px-1.5 py-0.2 bg-slate-100 text-slate-700 rounded font-bold">ব্লক {{ $property->block }}</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        রোড: {{ $property->road_number ?: '-' }} | প্লট: {{ $property->plot_number ?: '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        আয়তন: <strong class="text-slate-700">{{ $property->formatted_size ?: '-' }}</strong>
                                        @if($property->facing) | ফেসিং: {{ ucfirst($property->facing) }} @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Price -->
                            <td class="p-4">
                                <div class="space-y-0.5">
                                    <div class="text-sm font-extrabold text-emerald-700 font-mono">
                                        {{ $property->formatted_price }}
                                    </div>
                                    @if($property->price_per_unit)
                                        <div class="text-[10px] text-slate-500">
                                            {{ $property->price_per_unit }}
                                        </div>
                                    @endif
                                    @if($property->is_negotiable)
                                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">দাম আলোচনা সাপেক্ষ</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Owner / Direct Source -->
                            <td class="p-4">
                                <div class="space-y-1">
                                    <div class="font-bold text-slate-800 flex items-center gap-1">
                                        <span>👤</span> {{ $ownerName }}
                                    </div>
                                    @if(!empty($ownerPhone))
                                        <div class="font-mono text-[11px] text-slate-600 flex items-center gap-1.5">
                                            <span>📞</span> {{ $ownerPhone }}
                                        </div>
                                        <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode('আসসালামু আলাইকুম, PropertyKoi প্রাইভেট ভল্ট থেকে প্রপার্টি #' . $property->property_code . ' (' . $property->title . ') প্রসঙ্গে জানতে যোগাযোগ করছি।') }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                            <span>💬</span> হোয়াটসঅ্যাপ চ্যাট
                                        </a>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">নম্বর নেই</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Property Status Selector (Real-Time Change) -->
                            <td class="p-4 text-center">
                                <div class="relative inline-block text-left">
                                    <select @change="changePropertyStatus('{{ $property->id }}', $event.target.value)"
                                            :disabled="loadingStatuses['{{ $property->id }}']"
                                            :class="{
                                                'bg-amber-100 text-amber-900 border-amber-300': statuses['{{ $property->id }}'] === 'draft',
                                                'bg-emerald-100 text-emerald-900 border-emerald-300': statuses['{{ $property->id }}'] === 'live',
                                                'bg-indigo-100 text-indigo-900 border-indigo-300': statuses['{{ $property->id }}'] === 'pending',
                                                'bg-slate-800 text-white border-slate-700': statuses['{{ $property->id }}'] === 'sold',
                                                'bg-blue-100 text-blue-900 border-blue-300': statuses['{{ $property->id }}'] === 'booked',
                                                'bg-rose-100 text-rose-900 border-rose-300': statuses['{{ $property->id }}'] === 'archived',
                                            }"
                                            class="px-2.5 py-1 rounded-xl text-xs font-black border cursor-pointer focus:outline-none shadow-xs">
                                        <option value="draft" :selected="statuses['{{ $property->id }}'] === 'draft'">⚪ Draft (ড্রাফট)</option>
                                        <option value="live" :selected="statuses['{{ $property->id }}'] === 'live'">🟢 Live (পাবলিক)</option>
                                        <option value="pending" :selected="statuses['{{ $property->id }}'] === 'pending' || statuses['{{ $property->id }}'] === 'pending_verification'">🟡 Pending (পেন্ডিং)</option>
                                        <option value="sold" :selected="statuses['{{ $property->id }}'] === 'sold'">⚫ Sold (বিক্রিত)</option>
                                        <option value="booked" :selected="statuses['{{ $property->id }}'] === 'booked'">🔵 Booked (বুকড)</option>
                                        <option value="archived" :selected="statuses['{{ $property->id }}'] === 'archived'">🔴 Archived (আর্কাইভ)</option>
                                    </select>
                                    <template x-if="loadingStatuses['{{ $property->id }}']">
                                        <div class="absolute -right-5 top-1/2 -translate-y-1/2">
                                            <svg class="animate-spin h-3.5 w-3.5 text-slate-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <!-- Actions & 1-Click Launch -->
                            <td class="p-4 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <!-- View in Drawer Button -->
                                        <button type="button"
                                                @click="openViewDrawer('{{ $property->id }}')"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-amber-400 hover:text-amber-300 font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
                                                title="পাবলিক ভিউয়ের মতো ড্রয়ারে প্রপার্টি দেখুন">
                                            <span>👁️</span> ভিউ
                                        </button>

                                        <!-- 1-Click Publish Button (if not live) -->
                                        <template x-if="statuses['{{ $property->id }}'] !== 'live'">
                                            <form action="{{ route('admin.properties.publish-live', $property) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে প্রপার্টি #{{ $property->property_code }} সম্পূর্ণ SEO, AEO ও AI FAQ সহ তাৎক্ষণিকভাবে পাবলিক সাইটে লাইভ লিস্টিং করতে চান?');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-xs rounded-xl shadow-sm transition transform active:scale-95 whitespace-nowrap cursor-pointer">
                                                    <span>🚀</span> পাবলিশ
                                                </button>
                                            </form>
                                        </template>
                                        <template x-if="statuses['{{ $property->id }}'] === 'live'">
                                            <a href="{{ route('property.show', $property->slug ?: $property->property_code) }}" target="_blank"
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold text-xs rounded-xl shadow-xs transition hover:bg-emerald-100">
                                                <span>🌐</span> লাইভ ↗
                                            </a>
                                        </template>
                                    </div>

                                    <!-- Quick Utility Links -->
                                    <div class="flex items-center gap-2 text-xs">
                                        <a href="{{ route('property.edit', $property) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-0.5">
                                            ✏️ এডিট
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <form action="{{ route('admin.properties.destroy', $property) }}" method="POST" onsubmit="return confirm('প্রপার্টিটি ডিলিট করতে চান?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold flex items-center gap-0.5 cursor-pointer">
                                                🗑️ মুছুন
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-500">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="text-4xl">🗄️</div>
                                    <div class="text-base font-bold text-slate-800">কোনো প্রপার্টি পাওয়া যায়নি</div>
                                    <p class="text-xs text-slate-500">
                                        আপনি নতুন প্রপার্টি তৈরি করার সময় "প্রাইভেট ডিরেক্টরিতে সংরক্ষণ" নির্বাচন করতে পারেন অথবা গুগল শিট বাল্ক ইমপোর্টার ব্যবহার করে একযোগে বহু প্রপার্টি ভল্টে যোগ করতে পারেন।
                                    </p>
                                    <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                                        <a href="{{ route('property.create', ['destination' => 'vault']) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                                            <span>➕</span> নতুন প্রপার্টি যোগ করুন
                                        </a>
                                        <a href="{{ route('admin.sheet-importer.index', ['tab' => 'property']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-sm transition">
                                            <span>🏞️</span> ল্যান্ড শিট ইমপোর্ট
                                        </a>
                                        <a href="{{ route('admin.sheet-importer.index', ['tab' => 'flat']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                                            <span>🏢</span> ফ্ল্যাট শিট ইমপোর্ট
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($properties->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $properties->links() }}
            </div>
        @endif
    </div>

    <!-- Right-Side Drawer: Property Details Quick View -->
    <div x-show="viewDrawerOpen" x-cloak
         class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex justify-end"
         @keydown.window.escape="closeViewDrawer()">
        <div class="w-full max-w-2xl bg-white h-full shadow-2xl flex flex-col justify-between"
             x-show="viewDrawerOpen"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             @click.outside="closeViewDrawer()">

            <!-- Drawer Header -->
            <div class="p-4 px-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/95 sticky top-0 z-20 backdrop-blur-md">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-slate-900 text-amber-400 flex items-center justify-center font-bold text-sm shadow-xs">
                        👁️
                    </span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">
                            প্রপার্টি ডিটেইলস ভিউ (Property Quick View)
                        </h3>
                        <p class="text-[11px] text-slate-500">পাবলিক ভিউয়ের মতো পূর্ণাঙ্গ বিবরণ ও ডাটা</p>
                    </div>
                </div>
                <button type="button" @click="closeViewDrawer()" class="text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer p-1">&times;</button>
            </div>

            <!-- Drawer Content (Scrollable) -->
            <div class="p-5 flex-1 overflow-y-auto">
                <!-- Loading Skeleton -->
                <div x-show="loadingDrawer" class="space-y-4 animate-pulse">
                    <div class="aspect-16/10 bg-slate-200 rounded-2xl"></div>
                    <div class="h-6 bg-slate-200 rounded-xl w-3/4"></div>
                    <div class="h-10 bg-slate-200 rounded-xl w-1/2"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="h-16 bg-slate-200 rounded-xl"></div>
                        <div class="h-16 bg-slate-200 rounded-xl"></div>
                    </div>
                    <div class="h-24 bg-slate-200 rounded-xl"></div>
                </div>

                <!-- Dynamic HTML Injection -->
                <div id="property-quickview-content" x-show="!loadingDrawer"></div>
            </div>

            <!-- Drawer Footer -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs sticky bottom-0 z-20">
                <span class="text-slate-500 font-semibold">🔒 PropertyKoi Private Vault</span>
                <button type="button" @click="closeViewDrawer()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded-xl transition cursor-pointer">
                    বন্ধ করুন (Close)
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Hidden Note Modal -->
    <div x-show="noteModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="closeNoteModal()"
             class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 animate-scale-up">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-lg shadow-xs">
                        🔒
                    </span>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-sm">
                            গোপন নোট ও স্মার্ট ট্যাগ (Hidden Note)
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            প্রপার্টি #<span class="font-mono font-bold text-amber-700" x-text="activeNotePropCode"></span> - শুধুমাত্র অ্যাডমিনদের জন্য
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeNoteModal()" class="text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer p-1">&times;</button>
            </div>

            <div class="bg-amber-50/70 p-3 rounded-2xl border border-amber-200 text-amber-900 text-[11px] space-y-1">
                <span class="font-bold flex items-center gap-1">
                    <span>💡</span> স্মার্ট কি-ওয়ার্ড এক্সট্রাকশন:
                </span>
                <p class="m-0 leading-relaxed text-amber-800">
                    এখানে লেখা গোপন নোট কখনোই পাবলিক সাইটে প্রকাশ পাবে না। সিস্টেম স্বয়ংক্রিয়ভাবে জরুরি বিষয় (যেমন: মালিক বিদেশে, ১ কোটি ফিক্সড, বায়া দলিল ওকে, ব্যাংক লোন সুবিধা) এক্সট্র্যাক্ট করে searchable ট্যাগ বানিয়ে রাখবে।
                </p>
            </div>

            <div class="space-y-1.5">
                <label class="block font-bold text-slate-800 text-xs">
                    গোপন নোটের বিস্তারিত টেক্সট:
                </label>
                <textarea x-model="activeNoteText" rows="5"
                          placeholder="মালিকের গোপন তথ্য, বিশেষ শর্ত, আসল দাম বা অভ্যন্তরীণ আইনি নোট লিখুন..."
                          class="w-full p-3.5 border border-slate-300 rounded-2xl text-xs text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none leading-relaxed font-sans"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                <button type="button" @click="closeNoteModal()"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                    বাতিল
                </button>
                <button type="button" @click="saveHiddenNote()" :disabled="savingNote"
                        class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                    <span x-show="!savingNote">💾 সংরক্ষণ করুন</span>
                    <span x-show="savingNote">সংরক্ষণ হচ্ছে...</span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function propertyDirectory() {
    return {
        selectedIds: [],
        selectedBulkStatus: 'draft',
        isDeletingBulk: false,
        isUpdatingBulk: false,
        loadingStatuses: {},
        viewDrawerOpen: false,
        loadingDrawer: false,
        activePropertyId: null,
        totalRowsOnPage: {{ $properties->count() }},
        statuses: {
            @foreach($properties as $p)
                '{{ $p->id }}': '{{ $p->status }}',
            @endforeach
        },

        // Multiselect Types
        typeDropdownOpen: false,
        selectedTypes: @json($filterTypes ?? []),
        typeOptions: [
            { val: 'land', label: '🏞️ জমি / প্লট' },
            { val: 'flat', label: '🏢 রেডি ফ্ল্যাট' },
            { val: 'commercial', label: '🏬 কমার্শিয়াল' },
            { val: 'land_share', label: '🤝 ল্যান্ড শেয়ার' }
        ],

        getTypeButtonLabel() {
            if (!this.selectedTypes || this.selectedTypes.length === 0) return 'সকল ধরন (All Types)';
            if (this.selectedTypes.length === 1) {
                const opt = this.typeOptions.find(o => o.val === this.selectedTypes[0]);
                return opt ? opt.label : this.selectedTypes[0];
            }
            return `${this.selectedTypes.length}টি ধরন নির্বাচিত`;
        },

        toggleType(val) {
            const idx = this.selectedTypes.indexOf(val);
            if (idx > -1) {
                this.selectedTypes.splice(idx, 1);
            } else {
                this.selectedTypes.push(val);
            }
        },

        selectAllTypes() {
            this.selectedTypes = this.typeOptions.map(o => o.val);
        },

        clearAllTypes() {
            this.selectedTypes = [];
        },

        // Multiselect Statuses
        statusDropdownOpen: false,
        selectedStatuses: @json($filterStatuses ?? []),
        statusOptions: [
            { val: 'draft', label: '⚪ Draft (ড্রাফট / ভল্ট)' },
            { val: 'pending', label: '🟡 Pending (পেন্ডিং যাচাই)' },
            { val: 'live', label: '🟢 Live (পাবলিক ক্যাটালগ)' },
            { val: 'sold', label: '⚫ Sold (বিক্রিত)' },
            { val: 'booked', label: '🔵 Booked (বুকড)' },
            { val: 'archived', label: '🔴 Archived (আর্কাইভ)' },
            { val: 'visit_requested', label: '🟣 Visit Requested (ভিজিট)' },
            { val: 'negotiation', label: '🟠 Negotiation (দর কষাকষি)' }
        ],

        getStatusButtonLabel() {
            if (!this.selectedStatuses || this.selectedStatuses.length === 0) return 'সকল স্ট্যাটাস (All Status)';
            if (this.selectedStatuses.length === 1) {
                const opt = this.statusOptions.find(o => o.val === this.selectedStatuses[0]);
                return opt ? opt.label : this.selectedStatuses[0];
            }
            return `${this.selectedStatuses.length}টি স্ট্যাটাস নির্বাচিত`;
        },

        toggleStatus(val) {
            const idx = this.selectedStatuses.indexOf(val);
            if (idx > -1) {
                this.selectedStatuses.splice(idx, 1);
            } else {
                this.selectedStatuses.push(val);
            }
        },

        selectAllStatuses() {
            this.selectedStatuses = this.statusOptions.map(o => o.val);
        },

        clearAllStatuses() {
            this.selectedStatuses = [];
        },

        // Hidden Notes
        noteModalOpen: false,
        savingNote: false,
        activeNotePropId: null,
        activeNotePropCode: '',
        activeNotePropTitle: '',
        activeNoteText: '',
        notesData: {
            @foreach($properties as $p)
                '{{ $p->id }}': {
                    note: @json($p->hidden_note ?? ''),
                    tags: @json($p->hidden_tags ?? [])
                },
            @endforeach
        },

        openNoteModal(id, code, title) {
            this.activeNotePropId = id;
            this.activeNotePropCode = code;
            this.activeNotePropTitle = title;
            this.activeNoteText = this.notesData[id] ? (this.notesData[id].note || '') : '';
            this.noteModalOpen = true;
        },

        closeNoteModal() {
            this.noteModalOpen = false;
            this.activeNotePropId = null;
            this.activeNoteText = '';
        },

        truncate(str, len) {
            if (!str) return '';
            return str.length > len ? str.substring(0, len) + '...' : str;
        },

        async saveHiddenNote() {
            if (!this.activeNotePropId) return;
            this.savingNote = true;
            try {
                const res = await fetch(`{{ url('/backend/properties') }}/${this.activeNotePropId}/hidden-note`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ hidden_note: this.activeNoteText })
                });
                const data = await res.json();
                if (data.success) {
                    this.notesData[this.activeNotePropId] = {
                        note: data.hidden_note || '',
                        tags: data.hidden_tags || []
                    };
                    this.closeNoteModal();
                } else {
                    alert(data.error || 'সংরক্ষণ ব্যর্থ হয়েছে।');
                }
            } catch(e) {
                alert('ত্রুটি: ' + e.message);
            } finally {
                this.savingNote = false;
            }
        },

        async openViewDrawer(id) {
            this.activePropertyId = id;
            this.viewDrawerOpen = true;
            this.loadingDrawer = true;
            const contentEl = document.getElementById('property-quickview-content');
            if (contentEl) contentEl.innerHTML = '';

            try {
                const res = await fetch(`{{ url('/backend/properties') }}/${id}/quick-view`, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) throw new Error('প্রপার্টি ডাটা লোড করা যায়নি।');
                const html = await res.text();
                if (contentEl) contentEl.innerHTML = html;
            } catch(e) {
                if (contentEl) {
                    contentEl.innerHTML = `<div class="p-6 text-center text-rose-600 font-bold bg-rose-50 rounded-2xl border border-rose-200">ত্রুটি: ${e.message}</div>`;
                }
            } finally {
                this.loadingDrawer = false;
            }
        },

        closeViewDrawer() {
            this.viewDrawerOpen = false;
            this.activePropertyId = null;
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedIds = [
                    @foreach($properties as $p)
                        '{{ $p->id }}',
                    @endforeach
                ];
            } else {
                this.selectedIds = [];
            }
        },

        async changePropertyStatus(id, newStatus) {
            this.loadingStatuses[id] = true;
            try {
                const res = await fetch(`{{ url('/backend/properties') }}/${id}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                const data = await res.json();
                if (data.success) {
                    this.statuses[id] = newStatus;
                } else {
                    alert(data.error || 'স্ট্যাটাস আপডেট ব্যর্থ হয়েছে।');
                }
            } catch(e) {
                alert('নেটওয়ার্ক ত্রুটি: ' + e.message);
            } finally {
                this.loadingStatuses[id] = false;
            }
        },

        async executeBulkDelete() {
            if (this.selectedIds.length === 0) return;
            if (!confirm(`আপনি কি নিশ্চিত যে নির্বাচিত ${this.selectedIds.length}টি প্রপার্টি ডিলিট করতে চান? এটি পুনরুদ্ধার করা যাবে না।`)) return;

            this.isDeletingBulk = true;
            try {
                const res = await fetch('{{ route("admin.properties.bulk-delete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: this.selectedIds })
                });

                const data = await res.json();
                if (data.success) {
                    alert(`সফলভাবে ${data.count}টি প্রপার্টি ডিলিট করা হয়েছে!`);
                    window.location.reload();
                } else {
                    alert(data.error || 'ডিলিট ব্যর্থ হয়েছে।');
                }
            } catch(e) {
                alert('নেটওয়ার্ক ত্রুটি: ' + e.message);
            } finally {
                this.isDeletingBulk = false;
            }
        },

        async executeBulkStatus() {
            if (this.selectedIds.length === 0) return;
            if (!confirm(`নির্বাচিত ${this.selectedIds.length}টি প্রপার্টির স্ট্যাটাস '${this.selectedBulkStatus}' এ পরিবর্তন করতে চান?`)) return;

            this.isUpdatingBulk = true;
            try {
                const res = await fetch('{{ route("admin.properties.bulk-status") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: this.selectedIds, status: this.selectedBulkStatus })
                });

                const data = await res.json();
                if (data.success) {
                    alert(`সফলভাবে ${data.count}টি প্রপার্টির স্ট্যাটাস আপডেট হয়েছে!`);
                    window.location.reload();
                } else {
                    alert(data.error || 'স্ট্যাটাস আপডেট ব্যর্থ হয়েছে।');
                }
            } catch(e) {
                alert('নেটওয়ার্ক ত্রুটি: ' + e.message);
            } finally {
                this.isUpdatingBulk = false;
            }
        }
    };
}
</script>
@endpush
@endsection
