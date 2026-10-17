@extends('layouts.app')

@section('content')
@php
    $activeFilterCount = collect(request()->except(['page', 'sort', 'type']))
        ->filter(fn($v) => $v !== null && $v !== '' && $v !== 'all')
        ->count();
    $effectiveType = $currentType !== 'all' ? $currentType : request('type', 'all');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <!-- Category Header & Tabs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold mb-2">
                <span>🛡️ ১০০% ভেরিফাইড ডিরেক্ট সোর্স</span>
                <span>•</span>
                <span>আফতাবনগর ও ঢাকা</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                @if($currentType === 'land')
                    🏞️ আফতাবনগর ও ঢাকায় জমি ও প্লট বিক্রয়
                @elseif($currentType === 'flat')
                    🏢 রেডি ও নির্মাণাধীন ফ্ল্যাট ও অ্যাপার্টমেন্ট বিক্রয়
                @elseif($currentType === 'commercial')
                    🏬 দোকান, শোরুম ও কমার্শিয়াল স্পেস বিক্রয়
                @elseif($currentType === 'land_share')
                    🤝 যৌথ ল্যান্ড শেয়ার প্রকল্প ও ফ্ল্যাট নির্মাণ
                @else
                    🏡 সকল ভেরিফাইড প্রপার্টি লিস্টিং
                @endif
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                মোট <strong class="text-emerald-700 font-extrabold">{{ \App\Models\Property::toBnNumber($properties->total()) }}টি</strong> যাচাইকৃত প্রপার্টি পাওয়া গেছে
            </p>
        </div>

        <!-- Category Switching Buttons -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            <a href="{{ route('properties.index') }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ $currentType === 'all' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                <span>🏡</span> <span>সকল</span>
            </a>
            <a href="{{ route('properties.land') }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ $currentType === 'land' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                <span>🏞️</span> <span>জমি</span>
            </a>
            <a href="{{ route('properties.flat') }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ $currentType === 'flat' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                <span>🏢</span> <span>ফ্ল্যাট</span>
            </a>
            <a href="{{ route('properties.share') }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ $currentType === 'land_share' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                <span>🤝</span> <span>ল্যান্ড শেয়ার</span>
            </a>
            <a href="{{ route('properties.commercial') }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ $currentType === 'commercial' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                <span>🏬</span> <span>কমার্শিয়াল</span>
            </a>
            <a href="{{ route('map.view') }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all flex items-center gap-1.5 bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-100">
                <span>🗺️</span> <span>ম্যাপ ভিউ</span>
            </a>
        </div>
    </div>

    <!-- Quick Block Selector Strip (WP Parity) -->
    <div class="bg-white rounded-2xl px-4 py-3 border border-slate-200 shadow-2xs flex items-center gap-2 overflow-x-auto no-scrollbar">
        <span class="text-xs font-extrabold text-slate-500 whitespace-nowrap shrink-0 flex items-center gap-1">
            <span>📍 কুইক ব্লক:</span>
        </span>
        <a href="{{ request()->fullUrlWithQuery(['block' => 'all', 'page' => null]) }}"
           class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition {{ !request('block') || request('block') === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            সব ব্লক
        </a>
        @foreach(['A','B','C','D','E','F','G','H','I','J','K','L','M','N'] as $b)
            <a href="{{ request()->fullUrlWithQuery(['block' => $b, 'page' => null]) }}"
               class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition {{ request('block') === $b ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">
                ব্লক {{ $b }}
            </a>
        @endforeach
    </div>

    <!-- Advanced Category-Aware Filter Engine -->
    <div class="bg-white rounded-3xl p-4 sm:p-6 border border-slate-200 shadow-xs"
         x-data="{
             showAdvanced: {{ $activeFilterCount > 2 ? 'true' : 'false' }},
             selectedType: '{{ $effectiveType }}'
         }">
        <form action="{{ url()->current() }}" method="GET" class="space-y-4"
              @submit="if(window.trackClarityEvent) window.trackClarityEvent('property_filter_search', { category: '{{ $effectiveType }}', area: $el.area?.value || '', block: $el.block?.value || '' })">

            <!-- Primary Filter Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- Keyword Search -->
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">কীওয়ার্ড / প্রপার্টি কোড / রোড</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search', request('keyword')) }}"
                               placeholder="যেমন: PK-..., ব্লক সি, কর্নার, রেডি ফ্ল্যাট..."
                               class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>
                </div>

                @if($currentType === 'all')
                <!-- Property Type selector when on /properties -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">প্রপার্টির ধরন</label>
                    <select name="type" x-model="selectedType" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-500">
                        <option value="all">সকল ধরন</option>
                        <option value="land" {{ request('type') === 'land' ? 'selected' : '' }}>🏞️ জমি / প্লট</option>
                        <option value="flat" {{ request('type') === 'flat' ? 'selected' : '' }}>🏢 ফ্ল্যাট / অ্যাপার্টমেন্ট</option>
                        <option value="commercial" {{ request('type') === 'commercial' ? 'selected' : '' }}>🏬 কমার্শিয়াল / দোকান</option>
                        <option value="land_share" {{ request('type') === 'land_share' ? 'selected' : '' }}>🤝 ল্যান্ড শেয়ার</option>
                    </select>
                </div>
                @endif

                <!-- Area -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">এলাকা</label>
                    <select name="area" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-500">
                        <option value="all">সকল এলাকা</option>
                        <option value="Aftabnagar" {{ request('area') === 'Aftabnagar' ? 'selected' : '' }}>আফতাবনগর (Aftabnagar)</option>
                        <option value="Bashundhara R/A" {{ request('area') === 'Bashundhara R/A' ? 'selected' : '' }}>বসুন্ধরা আবাসিক</option>
                        <option value="Purbachal" {{ request('area') === 'Purbachal' ? 'selected' : '' }}>পূর্বাচল</option>
                        <option value="Banasree" {{ request('area') === 'Banasree' ? 'selected' : '' }}>বনশ্রী</option>
                        <option value="Uttara" {{ request('area') === 'Uttara' ? 'selected' : '' }}>উত্তরা</option>
                        <option value="Dhanmondi" {{ request('area') === 'Dhanmondi' ? 'selected' : '' }}>ধানমন্ডি</option>
                    </select>
                </div>

                <!-- Block -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">ব্লক</label>
                    <select name="block" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-500">
                        <option value="all">সকল ব্লক</option>
                        @foreach(['A','B','C','D','E','F','G','H','I','J','K','L','M','N'] as $b)
                            <option value="{{ $b }}" {{ request('block') === $b ? 'selected' : '' }}>ব্লক {{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Road Width -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">ন্যূনতম রাস্তা</label>
                    <select name="road_width" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-500">
                        <option value="">যেকোনো রাস্তা</option>
                        <option value="16" {{ request('road_width') == '16' ? 'selected' : '' }}>১৬+ ফুট</option>
                        <option value="20" {{ request('road_width') == '20' ? 'selected' : '' }}>২০+ ফুট</option>
                        <option value="25" {{ request('road_width') == '25' ? 'selected' : '' }}>২৫+ ফুট</option>
                        <option value="30" {{ request('road_width') == '30' ? 'selected' : '' }}>৩০+ ফুট</option>
                        <option value="40" {{ request('road_width') == '40' ? 'selected' : '' }}>৪০+ ফুট</option>
                        <option value="60" {{ request('road_width') == '60' ? 'selected' : '' }}>৬০+ ফুট (মেইন রোড)</option>
                    </select>
                </div>

                @if($currentType !== 'all')
                <!-- Facing -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">ফেসিং / দিক</label>
                    <select name="facing" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-500">
                        <option value="all">যেকোনো দিক</option>
                        <option value="south" {{ request('facing') === 'south' ? 'selected' : '' }}>দক্ষিণমুখী (South)</option>
                        <option value="north" {{ request('facing') === 'north' ? 'selected' : '' }}>উত্তরমুখী (North)</option>
                        <option value="east" {{ request('facing') === 'east' ? 'selected' : '' }}>পূর্বমুখী (East)</option>
                        <option value="west" {{ request('facing') === 'west' ? 'selected' : '' }}>পশ্চিমমুখী (West)</option>
                    </select>
                </div>
                @endif
            </div>

            <!-- Category-Specific Quick Filter Row -->
            @if($effectiveType === 'land')
                <div class="pt-3 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 items-end">
                    <div>
                        <label class="block text-[11px] font-bold text-emerald-700 uppercase mb-1">সর্বনিম্ন কাঠা</label>
                        <input type="number" step="0.5" name="katha_min" value="{{ request('katha_min') }}" placeholder="যেমন: 3"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-emerald-700 uppercase mb-1">সর্বোচ্চ কাঠা</label>
                        <input type="number" step="0.5" name="katha_max" value="{{ request('katha_max') }}" placeholder="যেমন: 10"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-emerald-700 uppercase mb-1">মাটি ভরাট অবস্থা</label>
                        <select name="soil_filling_status" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                            <option value="all">সকল অবস্থা</option>
                            <option value="filled" {{ request('soil_filling_status') === 'filled' ? 'selected' : '' }}>সম্পূর্ণ মাটি ভরাটকৃত</option>
                            <option value="partial" {{ request('soil_filling_status') === 'partial' ? 'selected' : '' }}>আংশিক ভরাট</option>
                            <option value="unfilled" {{ request('soil_filling_status') === 'unfilled' ? 'selected' : '' }}>অভরাটকৃত</option>
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-3 flex flex-wrap items-center gap-2 pt-1">
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('is_corner') ? 'bg-emerald-50 border-emerald-400 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="is_corner" value="1" {{ request()->boolean('is_corner') ? 'checked' : '' }} class="rounded text-emerald-600">
                            <span>📐 কর্নার প্লট</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('is_lake_facing') ? 'bg-teal-50 border-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="is_lake_facing" value="1" {{ request()->boolean('is_lake_facing') ? 'checked' : '' }} class="rounded text-teal-600">
                            <span>🌊 লেক ভিউ</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('is_park_facing') ? 'bg-green-50 border-green-400 text-green-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="is_park_facing" value="1" {{ request()->boolean('is_park_facing') ? 'checked' : '' }} class="rounded text-green-600">
                            <span>🌳 পার্ক ফেসিং</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('boundary_wall_done') ? 'bg-amber-50 border-amber-400 text-amber-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="boundary_wall_done" value="1" {{ request()->boolean('boundary_wall_done') ? 'checked' : '' }} class="rounded text-amber-600">
                            <span>🧱 বাউন্ডারি করা</span>
                        </label>
                    </div>
                </div>
            @elseif($effectiveType === 'flat')
                <div class="pt-3 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 items-end">
                    <div>
                        <label class="block text-[11px] font-bold text-teal-700 uppercase mb-1">ফ্ল্যাটের অবস্থা</label>
                        <select name="completion_status" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                            <option value="all">সকল ফ্ল্যাট</option>
                            <option value="ready" {{ request('completion_status') === 'ready' ? 'selected' : '' }}>✅ রেডি ফ্ল্যাট (Ready)</option>
                            <option value="ongoing" {{ request('completion_status') === 'ongoing' ? 'selected' : '' }}>🏗️ নির্মাণাধীন (Ongoing)</option>
                            <option value="upcoming" {{ request('completion_status') === 'upcoming' ? 'selected' : '' }}>📅 আপকামিং</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-teal-700 uppercase mb-1">বেডরুম</label>
                        <select name="bedrooms" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                            <option value="all">যেকোনো</option>
                            <option value="2" {{ request('bedrooms') == '2' ? 'selected' : '' }}>২+ বেডরুম</option>
                            <option value="3" {{ request('bedrooms') == '3' ? 'selected' : '' }}>৩+ বেডরুম</option>
                            <option value="4" {{ request('bedrooms') == '4' ? 'selected' : '' }}>৪+ বেডরুম</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-teal-700 uppercase mb-1">সর্বনিম্ন বর্গফুট</label>
                        <input type="number" name="sqft_min" value="{{ request('sqft_min') }}" placeholder="যেমন: 1200"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-teal-700 uppercase mb-1">সর্বোচ্চ বর্গফুট</label>
                        <input type="number" name="sqft_max" value="{{ request('sqft_max') }}" placeholder="যেমন: 2500"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div class="col-span-2 flex flex-wrap items-center gap-2 pt-1">
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_lift') ? 'bg-teal-50 border-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_lift" value="1" {{ request()->boolean('has_lift') ? 'checked' : '' }} class="rounded text-teal-600">
                            <span>🛗 লিফট</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('parking_available') ? 'bg-teal-50 border-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="parking_available" value="1" {{ request()->boolean('parking_available') ? 'checked' : '' }} class="rounded text-teal-600">
                            <span>🚗 পার্কিং</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_gas') ? 'bg-teal-50 border-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_gas" value="1" {{ request()->boolean('has_gas') ? 'checked' : '' }} class="rounded text-teal-600">
                            <span>🔥 গ্যাস</span>
                        </label>
                    </div>
                </div>
            @elseif($effectiveType === 'commercial')
                <div class="pt-3 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 items-end">
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">কমার্শিয়াল ধরন</label>
                        <select name="commercial_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                            <option value="all">সকল কমার্শিয়াল</option>
                            <option value="shop" {{ request('commercial_type') === 'shop' ? 'selected' : '' }}>🛍️ দোকান (Shop)</option>
                            <option value="showroom" {{ request('commercial_type') === 'showroom' ? 'selected' : '' }}>🏪 শোরুম (Showroom)</option>
                            <option value="office" {{ request('commercial_type') === 'office' ? 'selected' : '' }}>💼 অফিস স্পেস</option>
                            <option value="restaurant" {{ request('commercial_type') === 'restaurant' ? 'selected' : '' }}>🍽️ রেস্তোরাঁ / ক্যাফে</option>
                            <option value="bank_insurance" {{ request('commercial_type') === 'bank_insurance' ? 'selected' : '' }}>🏦 ব্যাংক / বীমা</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">মার্কেট / ভবনের নাম</label>
                        <input type="text" name="market_name" value="{{ request('market_name') }}" placeholder="মার্কেটের নাম..."
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">সর্বনিম্ন বর্গফুট</label>
                        <input type="number" name="sqft_min" value="{{ request('sqft_min') }}" placeholder="যেমন: 200"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">সর্বোচ্চ বর্গফুট</label>
                        <input type="number" name="sqft_max" value="{{ request('sqft_max') }}" placeholder="যেমন: 5000"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div class="col-span-2 flex flex-wrap items-center gap-2 pt-1">
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_lift') ? 'bg-indigo-50 border-indigo-400 text-indigo-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_lift" value="1" {{ request()->boolean('has_lift') ? 'checked' : '' }} class="rounded text-indigo-600">
                            <span>🛗 লিফট</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_parking') ? 'bg-indigo-50 border-indigo-400 text-indigo-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_parking" value="1" {{ request()->boolean('has_parking') ? 'checked' : '' }} class="rounded text-indigo-600">
                            <span>🚗 পার্কিং</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_fire_safety') ? 'bg-indigo-50 border-indigo-400 text-indigo-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_fire_safety" value="1" {{ request()->boolean('has_fire_safety') ? 'checked' : '' }} class="rounded text-indigo-600">
                            <span>🧯 ফায়ার সেফটি</span>
                        </label>
                    </div>
                </div>
            @elseif($effectiveType === 'land_share')
                <div class="pt-3 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 items-end">
                    <div>
                        <label class="block text-[11px] font-bold text-purple-700 uppercase mb-1">সর্বনিম্ন ফ্ল্যাট বর্গফুট</label>
                        <input type="number" name="sqft_min" value="{{ request('sqft_min') }}" placeholder="যেমন: 1350"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-purple-700 uppercase mb-1">জমির আকার (কাঠা+)</label>
                        <input type="number" step="0.5" name="katha_min" value="{{ request('katha_min') }}" placeholder="যেমন: 5"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-purple-700 uppercase mb-1">বেডরুম</label>
                        <select name="bedrooms" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                            <option value="all">যেকোনো</option>
                            <option value="3" {{ request('bedrooms') == '3' ? 'selected' : '' }}>৩+ বেডরুম</option>
                            <option value="4" {{ request('bedrooms') == '4' ? 'selected' : '' }}>৪+ বেডরুম</option>
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-3 flex flex-wrap items-center gap-2 pt-1">
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_parking') ? 'bg-purple-50 border-purple-400 text-purple-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_parking" value="1" {{ request()->boolean('has_parking') ? 'checked' : '' }} class="rounded text-purple-600">
                            <span>🚗 কার পার্কিং সহ</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_lift') ? 'bg-purple-50 border-purple-400 text-purple-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_lift" value="1" {{ request()->boolean('has_lift') ? 'checked' : '' }} class="rounded text-purple-600">
                            <span>🛗 লিফট</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold cursor-pointer transition {{ request()->boolean('has_generator') ? 'bg-purple-50 border-purple-400 text-purple-800' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                            <input type="checkbox" name="has_generator" value="1" {{ request()->boolean('has_generator') ? 'checked' : '' }} class="rounded text-purple-600">
                            <span>⚡ জেনারেটর</span>
                        </label>
                    </div>
                </div>
            @endif

            <!-- Expandable More Filters (Budget, Source Type, Sector, Road Number, Sort) -->
            <div x-show="showAdvanced" x-cloak class="pt-3 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">সর্বনিম্ন বাজেট (৳)</label>
                    <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="যেমন: 3000000"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">সর্বোচ্চ বাজেট (৳)</label>
                    <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="যেমন: 20000000"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">সেক্টর / প্রজেক্ট</label>
                    <input type="text" name="sector" value="{{ request('sector') }}" placeholder="যেমন: Sector 2"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">রোড নম্বর</label>
                    <input type="text" name="road_number" value="{{ request('road_number') }}" placeholder="যেমন: 5"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">সোর্স ধরন</label>
                    <select name="source_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                        <option value="all">সকল সোর্স</option>
                        <option value="direct_owner" {{ request('source_type') === 'direct_owner' ? 'selected' : '' }}>👤 সরাসরি মালিক</option>
                        <option value="direct_agent" {{ request('source_type') === 'direct_agent' ? 'selected' : '' }}>🤝 ভেরিফাইড এজেন্ট</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">দরদাম</label>
                    <select name="negotiable" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs sm:text-sm">
                        <option value="all">সকল</option>
                        <option value="1" {{ request('negotiable') === '1' ? 'selected' : '' }}>💬 আলোচনা সাপেক্ষ</option>
                        <option value="0" {{ request('negotiable') === '0' ? 'selected' : '' }}>🔒 ফিক্সড প্রাইস</option>
                    </select>
                </div>
            </div>

            <!-- Action & Sort Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                <div class="flex items-center gap-3">
                    <button type="button" @click="showAdvanced = !showAdvanced"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition cursor-pointer">
                        <span x-text="showAdvanced ? '▲ কম ফিল্টার দেখুন' : '⚙️ আরও অ্যাডভান্সড ফিল্টার'"></span>
                    </button>
                    @if($activeFilterCount > 0)
                        <a href="{{ url()->current() }}" class="text-rose-600 hover:text-rose-700 font-bold flex items-center gap-1">
                            <span>✕</span> <span>ফিল্টার রিসেট ({{ $activeFilterCount }})</span>
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-2.5 ml-auto">
                    <select name="sort" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700">
                        <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>🕒 সর্বশেষ পোস্ট</option>
                        <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>🔥 জনপ্রিয় / বেশি ভিউ</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>📅 পুরোনো পোস্ট</option>
                    </select>

                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-sm transition cursor-pointer flex items-center gap-1.5">
                        <span>🔍</span> <span>খুঁজুন</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Properties Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($properties as $prop)
            <x-property-card :property="$prop" />
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-slate-200 shadow-xs space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto text-xl">
                    🔍
                </div>
                <h3 class="text-base font-bold text-slate-800">কোনো প্রপার্টি পাওয়া যায়নি</h3>
                <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                    আপনার নির্বাচিত ফিল্টারের সাথে মিলে এমন কোনো প্রপার্টি এই মুহূর্তে নেই। ফিল্টার রিসেট করে পুনরায় চেষ্টা করুন অথবা আপনার প্রপার্টি চাহিদা আমাদের জানান।
                </p>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <a href="{{ url()->current() }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                        সকল ফিল্টার রিসেট
                    </a>
                    <a href="{{ route('chahida.index') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs">
                        📝 প্রপার্টি চাহিদা জমা দিন
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $properties->links() }}
    </div>

    <!-- AEO (Answer Engine Optimization) & Buyer Guide FAQ Section -->
    @if(!empty($seo['faqs']) && count($seo['faqs']) > 0)
        <section class="mt-12 bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-5" aria-labelledby="hub-faq-heading">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                        🤖 AEO & Buyer Guide
                    </span>
                    <h2 id="hub-faq-heading" class="text-lg sm:text-xl font-black text-slate-900 mt-2">
                        প্রপার্টি ক্রয় সংক্রান্ত সচরাচর জিজ্ঞাসিত প্রশ্নোত্তর (FAQ)
                    </h2>
                </div>
                <a href="{{ route('chahida.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 underline">
                    পছন্দের প্রপার্টি পাচ্ছেন না? চাহিদা জানান &rarr;
                </a>
            </div>

            <div class="space-y-3" x-data="{ activeFaq: 0 }">
                @foreach($seo['faqs'] as $idx => $faq)
                    <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all"
                         :class="activeFaq === {{ $idx }} ? 'bg-emerald-50/30 border-emerald-300' : 'bg-slate-50/60'">
                        <button type="button"
                                @click="activeFaq = activeFaq === {{ $idx }} ? null : {{ $idx }}"
                                class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-extrabold text-sm text-slate-900 cursor-pointer">
                            <span class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs flex items-center justify-center shrink-0">প্র</span>
                                <span>{{ $faq['question'] ?? ($faq['q'] ?? '') }}</span>
                            </span>
                            <span class="text-slate-400 font-bold text-lg transition-transform"
                                  :class="activeFaq === {{ $idx }} ? 'rotate-180 text-emerald-600' : ''">⌄</span>
                        </button>
                        <div x-show="activeFaq === {{ $idx }}" x-cloak class="px-5 pb-4 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                            {{ $faq['answer'] ?? ($faq['a'] ?? '') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
