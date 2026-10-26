@extends('layouts.admin')

@section('title', 'প্রপার্টি মাস্টার ক্যাটালগ')
@section('page_title', 'প্রপার্টি মাস্টার ক্যাটালগ')

@section('content')
<div class="space-y-5" x-data="propertyCatalog()">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 m-0 flex items-center gap-2.5">
                <span>🏢</span>
                <span>সকল প্রপার্টি লিস্টিং (Master Catalog)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1 m-0">আফতাবনগরের সকল জমি ও ফ্ল্যাটের মাস্টার ক্যাটালগ — এডিট, ডিলিট ও রিয়েল-টাইম স্ট্যাটাস পরিবর্তন</p>
        </div>
        <div class="flex items-center gap-3">
            <template x-if="isFiltered || selectedIds.length > 0">
                <button type="button" 
                        @click="openAutoAssignModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-extrabold text-xs rounded-xl shadow-md transition cursor-pointer">
                    <span>⚡</span>
                    <span>ইনসার্টার অটো-অ্যাসাইন</span>
                    <span class="px-2 py-0.5 rounded-full bg-white/20 text-[10px]" 
                          x-text="selectedIds.length > 0 ? selectedIds.length + 'টি নির্বাচিত' : '{{ $properties->total() }}টি ফিল্টার্ড'"></span>
                </button>
            </template>

            <template x-if="!isFiltered && selectedIds.length === 0">
                <button type="button" 
                        @click="openAutoAssignModal()"
                        title="শুধুমাত্র ফিল্টার্ড অথবা নির্বাচিত প্রপার্টিতে কাজ করবে। অনুগ্রহ করে আগে ফিল্টার করুন বা আইটেম সিলেক্ট করুন।"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-500 font-extrabold text-xs rounded-xl border border-slate-300 transition cursor-pointer">
                    <span>⚡</span>
                    <span>ইনসার্টার অটো-অ্যাসাইন</span>
                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">ফিল্টার আবশ্যক</span>
                </button>
            </template>

            <span class="px-3.5 py-1.5 bg-emerald-100 text-emerald-800 text-xs font-extrabold rounded-full border border-emerald-300 shadow-xs">
                মোট {{ $stats['total'] }}টি প্রপার্টি
            </span>

            <a href="{{ route('admin.properties.directory') }}" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-amber-400 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm border border-slate-700">
                <span>🗄️</span> প্রাইভেট ভল্ট
            </a>

            <a href="{{ route('property.create') }}" target="_blank" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm" title="নতুন ট্যাবে ফর্ম খুলুন">
                <span>➕</span> নতুন লিস্টিং ↗
            </a>
        </div>
    </div>

    <!-- Stats Filter Pills Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <a href="{{ route('admin.properties.index') }}" class="px-3 py-1.5 rounded-xl border {{ !$isFiltered ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                মোট: <span x-text="stats.total">{{ $stats['total'] }}</span>
            </a>
            <a href="{{ route('admin.properties.index', ['prop_status' => 'live']) }}" class="px-3 py-1.5 rounded-xl border {{ $filterStatus === 'live' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' }}">
                🟢 লাইভ: <span x-text="stats.live">{{ $stats['live'] }}</span>
            </a>
            <a href="{{ route('admin.properties.index', ['prop_status' => 'pending']) }}" class="px-3 py-1.5 rounded-xl border {{ in_array($filterStatus, ['pending', 'pending_verification'], true) ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' }}">
                🟡 পেন্ডিং: <span x-text="stats.pending">{{ $stats['pending'] }}</span>
            </a>
            <a href="{{ route('admin.properties.index', ['prop_status' => 'booked']) }}" class="px-3 py-1.5 rounded-xl border {{ $filterStatus === 'booked' ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' }}">
                🔵 বুকড: <span x-text="stats.booked">{{ $stats['booked'] }}</span>
            </a>
            <a href="{{ route('admin.properties.index', ['prop_status' => 'sold']) }}" class="px-3 py-1.5 rounded-xl border {{ $filterStatus === 'sold' ? 'bg-slate-800 text-white border-slate-800' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200' }}">
                ⚫ বিক্রিত: <span x-text="stats.sold">{{ $stats['sold'] }}</span>
            </a>
            <a href="{{ route('admin.properties.index', ['prop_status' => 'archived']) }}" class="px-3 py-1.5 rounded-xl border {{ $filterStatus === 'archived' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}">
                🔴 আর্কাইভ: <span x-text="stats.archived">{{ $stats['archived'] }}</span>
            </a>
            <a href="{{ route('admin.properties.index', ['prop_source' => 'unassigned']) }}" class="px-3 py-1.5 rounded-xl border {{ $filterSource === 'unassigned' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' }}">
                ⚠️ অনির্ধারিত: <span x-text="stats.unassigned">{{ $stats['unassigned'] }}</span>
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 mb-2">
        <form method="get" action="{{ route('admin.properties.index') }}" class="flex flex-wrap items-end gap-4">
            
            <div class="flex-1 min-w-[190px]">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">প্রপার্টি কোড দিয়ে খুঁজুন</label>
                <input type="text" name="property_code" value="{{ $filterCode }}" placeholder="যেমন: AF-LAND-..." class="w-full border border-slate-300 rounded-xl px-3.5 py-2 bg-slate-50 font-mono font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1 flex items-center gap-1">
                    <span class="text-emerald-600 font-bold">🟢</span>
                    <span>ইনসার্টার WhatsApp / মোবাইল</span>
                </label>
                <input type="text" name="inserter_phone" value="{{ $filterInserterPhone }}" placeholder="যেমন: 017xxxxxxxx" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 bg-slate-50 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
            </div>

            <div class="w-full sm:w-48">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">এলাকা (Area)</label>
                <select name="prop_area" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 bg-slate-50 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
                    <option value="">সব এলাকা (All)</option>
                    @foreach ($availableAreas as $ar)
                        <option value="{{ $ar }}" {{ $filterArea === $ar ? 'selected' : '' }}>{{ $ar }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-48">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">প্রপার্টি টাইপ</label>
                <select name="prop_type" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 bg-slate-50 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
                    <option value="">সব টাইপ (All)</option>
                    <option value="land" {{ $filterType === 'land' ? 'selected' : '' }}>🏞️ Land (জমি)</option>
                    <option value="flat" {{ $filterType === 'flat' ? 'selected' : '' }}>🏢 Flat (ফ্ল্যাট)</option>
                    <option value="land_share" {{ $filterType === 'land_share' ? 'selected' : '' }}>🤝 Land Share (ল্যান্ড শেয়ার)</option>
                    <option value="commercial" {{ $filterType === 'commercial' ? 'selected' : '' }}>🏬 Commercial (দোকান/কমার্শিয়াল)</option>
                </select>
            </div>

            <div class="w-full sm:w-48">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">স্ট্যাটাস</label>
                <select name="prop_status" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 bg-slate-50 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
                    <option value="any" {{ $filterStatus === 'any' || empty($filterStatus) ? 'selected' : '' }}>সব স্ট্যাটাস (Any)</option>
                    <option value="live" {{ $filterStatus === 'live' ? 'selected' : '' }}>🟢 Live (লাইভ)</option>
                    <option value="pending" {{ in_array($filterStatus, ['pending', 'pending_verification'], true) ? 'selected' : '' }}>🟡 Pending (পেন্ডিং)</option>
                    <option value="sold" {{ $filterStatus === 'sold' ? 'selected' : '' }}>⚫ Sold (বিক্রিত)</option>
                    <option value="booked" {{ $filterStatus === 'booked' ? 'selected' : '' }}>🔵 Booked (বুকড)</option>
                    <option value="draft" {{ $filterStatus === 'draft' ? 'selected' : '' }}>⚪ Draft (ড্রাফট)</option>
                    <option value="archived" {{ $filterStatus === 'archived' ? 'selected' : '' }}>🔴 Archived (আর্কাইভ)</option>
                </select>
            </div>

            <div class="w-full sm:w-56">
                <label class="block text-[11px] font-extrabold text-slate-600 uppercase mb-1">ডাটা সোর্স / ইনসার্ট সোর্স</label>
                <select name="prop_source" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 bg-slate-50 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
                    <option value="">সব সোর্স (All Sources)</option>
                    <option value="sheet_import" {{ $filterSource === 'sheet_import' ? 'selected' : '' }}>📊 গুগল শিট / বাল্ক (Sheets Sync)</option>
                    <option value="direct" {{ $filterSource === 'direct' ? 'selected' : '' }}>👤 ডিরেক্ট লিস্টিং (Direct Post)</option>
                    <option value="unassigned" {{ $filterSource === 'unassigned' ? 'selected' : '' }}>⚠️ আন-অ্যাসাইনড / ইনসার্টারহীন (Unassigned)</option>
                </select>
            </div>

            <div class="w-full sm:w-auto flex flex-wrap items-center gap-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition cursor-pointer">
                    ফিল্টার করুন
                </button>

                @if($filterSource !== 'unassigned')
                <a href="{{ route('admin.properties.index', ['prop_source' => 'unassigned']) }}" 
                   title="শুধুমাত্র ইনসার্টারবিহীন আন-অ্যাসাইনড প্রপার্টিগুলো ফিল্টার করুন"
                   class="px-3.5 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-extrabold text-xs rounded-xl transition inline-flex items-center gap-1.5 shadow-2xs">
                    <span>⚠️</span>
                    <span>আন-অ্যাসাইনড ফিল্টার</span>
                </a>
                @endif

                @if($isFiltered)
                <a href="{{ route('admin.properties.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold text-xs rounded-xl transition inline-block">
                    রিসেট
                </a>
                @endif
            </div>
        </form>

        @if($isFiltered && !empty($filterSummary))
        <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500">
            <div>
                <span class="font-bold text-slate-700">সক্রিয় ফিল্টার:</span> {{ $filterSummary }}
            </div>
            <span class="font-semibold text-emerald-600">খুঁজে পাওয়া গেছে: {{ $properties->total() }}টি প্রপার্টি</span>
        </div>
        @endif
    </div>

    <!-- Bulk Action Bar -->
    <div x-show="selectedIds.length > 0"
         x-cloak
         class="bg-slate-900 text-white rounded-2xl p-3.5 mb-4 shadow-xl flex items-center justify-between gap-4 border border-slate-700">
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-sm flex-shrink-0">✓</span>
            <div>
                <span class="font-extrabold text-sm" x-text="selectedIds.length + 'টি প্রপার্টি নির্বাচিত'"></span>
                <span class="text-xs text-slate-400 block sm:inline sm:ml-2">একক বা বাল্ক অ্যাকশন চালাতে পারেন</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="selectedIds = []"
                    class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold text-xs rounded-xl transition cursor-pointer">
                সিলেকশন মুছুন
            </button>
            <button type="button" @click="openAutoAssignModal(selectedIds)"
                    class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                <span>⚡</span>
                <span x-text="'ইনসার্টার অ্যাসাইন (' + selectedIds.length + ')'"></span>
            </button>
            <button type="button" @click="executeBulkDelete()"
                    class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                <span>🗑️</span>
                <span>বাল্ক ডিলিট</span>
            </button>
        </div>
    </div>

    <!-- Master Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs min-w-[1100px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] tracking-wider font-extrabold">
                        <th class="p-3.5 w-10 text-center">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="w-4 h-4 rounded accent-emerald-600 cursor-pointer">
                        </th>
                        <th class="p-3.5">কোড ও প্রপার্টি শিরোনাম</th>
                        <th class="p-3.5">টাইপ ও অবস্থান</th>
                        <th class="p-3.5">সাইজ ও রাস্তা</th>
                        <th class="p-3.5">মূল্য (BDT)</th>
                        <th class="p-3.5">ডাইরেক্ট সোর্স</th>
                        <th class="p-3.5">হোয়াটসঅ্যাপ / যোগাযোগ</th>
                        <th class="p-3.5">স্ট্যাটাস পরিবর্তন</th>
                        <th class="p-3.5 text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($properties as $property)
                        @php
                            $primarySource = $property->directSources?->firstWhere('is_primary_source', true) ?? $property->directSources?->first();
                            $isSheetSync = $primarySource && (
                                str_contains((string)$primarySource->relationship_declaration, 'Google Sheets') ||
                                str_contains((string)$primarySource->relationship_declaration, 'Sheets sync') ||
                                str_contains((string)$primarySource->relationship_declaration, 'sheet') ||
                                $primarySource->source_type === 'google_sheet'
                            );

                            $creator = $property->creator;
                            $isUnassigned = empty($property->created_by_user_id) || !$creator;
                            $inserterName = $creator ? ($creator->name ?: $creator->phone) : null;
                            $sourcesCount = $property->directSources?->count() ?? 0;

                            $contactName = $primarySource?->owner_name ?: ($creator?->name ?? 'মালিক / এজেন্ট');
                            $rawContactPhone = $primarySource?->owner_phone ?: ($creator?->whatsapp_number ?: ($creator?->phone ?? ''));
                            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$rawContactPhone);
                            if ($cleanPhone && str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '88' . $cleanPhone;
                            }

                            $sourceTagText = $isSheetSync ? 'Google Sheets সিঙ্কের মাধ্যমে' : 'ওয়েবসাইট প্ল্যাটফর্মের মাধ্যমে';
                            $locationStr = array_filter([
                                $property->area,
                                $property->sub_area ?: ($property->block ? 'ব্লক ' . $property->block : '')
                            ]);
                            $locJoined = implode(', ', $locationStr);
                            $pricePart = ($property->raw_price > 0 || $property->price > 0) ? (' | মূল্য: ' . $property->formatted_price) : '';

                            $listingMsg = \App\Services\MessageTemplateService::render('property_listing_notification', [
                                '%name%'             => $contactName ?: 'স্যার',
                                '%property_code%'    => $property->property_code,
                                '%property_title%'   => $property->title,
                                '%property_url%'     => route('property.show', $property->slug ?: $property->property_code),
                                '%price%'            => $pricePart,
                                '%location%'         => $locJoined ? (' | ' . $locJoined) : '',
                                '%source_tag%'       => $sourceTagText,
                                '%login_magic_link%' => $creator?->magic_token ? url('/auth/magic/' . $creator->magic_token) : url('/agent-portal'),
                                '%phone%'            => $rawContactPhone ?? '',
                            ]);

                            $typeConfig = match($property->type) {
                                'land' => ['label' => '🏞️ জমি', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
                                'land_share' => ['label' => '🤝 ল্যান্ড শেয়ার', 'class' => 'bg-purple-100 text-purple-800 border-purple-300'],
                                'commercial' => ['label' => '🏬 কমার্শিয়াল', 'class' => 'bg-indigo-100 text-indigo-800 border-indigo-300'],
                                default => ['label' => '🏢 ফ্ল্যাট', 'class' => 'bg-blue-100 text-blue-800 border-blue-300']
                            };

                            $sizeStr = null;
                            if ($property->type === 'land' && $property->landDetail) {
                                $sizeStr = $property->landDetail->katha_size . ' কাঠা';
                            } elseif ($property->type === 'flat' && $property->flatDetail) {
                                $sizeStr = $property->flatDetail->flat_size_sqft . ' sqft';
                            } elseif ($property->type === 'commercial' && $property->commercialDetail) {
                                $sizeStr = $property->commercialDetail->size_sqft . ' sqft';
                            } elseif ($property->type === 'land_share' && $property->shareDetail) {
                                $sizeStr = $property->shareDetail->land_size_katha . ' কাঠা';
                            } elseif ($property->size) {
                                $sizeStr = $property->size . ' ' . (in_array($property->type, ['land', 'land_share']) ? 'কাঠা' : 'sqft');
                            }
                        @endphp
                        <tr id="property-row-{{ $property->id }}"
                            :class="selectedIds.includes({{ $property->id }}) ? 'bg-emerald-50/60' : 'hover:bg-slate-50/80'"
                            class="transition-colors">
                            <!-- Checkbox -->
                            <td class="p-3.5 text-center">
                                <input type="checkbox" value="{{ $property->id }}" x-model.number="selectedIds" class="w-4 h-4 rounded accent-emerald-600 cursor-pointer">
                            </td>

                            <!-- Code & Title -->
                            <td class="p-3.5">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="font-mono text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        {{ $property->property_code }}
                                    </span>
                                    @if($property->views_count > 0)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded" title="মোট ভিউ সংখ্যা">
                                            <span>👁️</span>
                                            <span>{{ \App\Models\Property::toBnNumber($property->views_count) }}</span>
                                        </span>
                                    @endif
                                    @if($property->is_featured)
                                        <span class="bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded text-[9px] border border-amber-300">FEATURED</span>
                                    @endif
                                </div>
                                <a href="{{ route('admin.properties.edit', $property) }}" class="font-bold text-slate-900 text-sm leading-snug line-clamp-1 hover:text-emerald-600 transition" title="{{ $property->title }}">
                                    {{ $property->title }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    লিস্টিং: {{ $property->created_at ? $property->created_at->format('d M, Y') : 'N/A' }}
                                </div>
                            </td>

                            <!-- Type & Location -->
                            <td class="p-3.5">
                                <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $property->area ?? 'Aftabnagar' }}
                                    </span>
                                    <span class="uppercase text-[10px] font-extrabold px-2 py-0.5 rounded border {{ $typeConfig['class'] }}">
                                        {{ $typeConfig['label'] }}
                                    </span>
                                </div>
                                <div class="text-xs font-semibold text-slate-700">
                                    @php
                                        $locParts = array_filter([
                                            $property->sub_area,
                                            $property->block ? ('ব্লক ' . $property->block) : null,
                                            $property->sector ? ('সেক্টর ' . $property->sector) : null
                                        ]);
                                    @endphp
                                    {{ !empty($locParts) ? implode(', ', $locParts) : ($property->location_name ?? 'লোকেশন তথ্য নেই') }}
                                </div>
                            </td>

                            <!-- Size & Road -->
                            <td class="p-3.5">
                                <div class="text-xs font-bold text-slate-900">
                                    @if($property->road_width_ft)
                                        <span>{{ $property->road_width_ft }} ফুট রাস্তা</span>
                                    @elseif($sizeStr)
                                        <span>{{ $sizeStr }}</span>
                                    @else
                                        <span class="text-slate-400">তথ্য নেই</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    @if($property->plot_number)
                                        <span>প্লট #{{ $property->plot_number }}</span>
                                    @endif
                                    @if($property->facing)
                                        <span>{{ $property->plot_number ? ' • ' : '' }}{{ $property->facing }} মুখী</span>
                                    @endif
                                    @if(!$property->plot_number && !$property->facing && $sizeStr && $property->road_width_ft)
                                        <span>{{ $sizeStr }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Price -->
                            <td class="p-3.5">
                                <div class="font-black text-emerald-700 text-sm">
                                    {{ $property->formatted_price }}
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 mt-0.5">
                                    {{ $property->is_negotiable ? 'আলোচনা সাপেক্ষ' : 'ফিক্সড প্রাইস' }}
                                </div>
                            </td>

                            <!-- Direct Sources -->
                            <td class="p-3.5">
                                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-[11px]">
                                    <span>👤</span>
                                    <span>{{ $sourcesCount > 0 ? $sourcesCount : 1 }} Direct Sources</span>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-1 items-center">
                                    @if($isSheetSync)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[10px]" title="Google Sheets বা Bulk Import থেকে সিঙ্ককৃত">
                                            <span>📊</span> Sheets Sync
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-50 text-slate-600 border border-slate-200 font-medium text-[10px]" title="সরাসরি ওয়েবসাইটে তালিকাভুক্ত">
                                            <span>👤</span> Direct Post
                                        </span>
                                    @endif

                                    @if($isUnassigned)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[10px]" title="কোনো নির্দিষ্ট ম্যানুয়াল ইউজার/ইনসার্টার লিঙ্ক করা নেই">
                                            <span>⚠️</span> Unassigned
                                        </span>
                                    @elseif($inserterName)
                                        <span class="text-[10px] text-slate-500 font-semibold truncate max-w-[110px]" title="ইনসার্টার: {{ $inserterName }}">
                                            by {{ $inserterName }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- WhatsApp / Contact Action -->
                            <td class="p-3.5">
                                <div class="relative inline-block text-left" x-data="{ contactOpen: false }">
                                    @if($cleanPhone)
                                        <div>
                                            <button type="button"
                                                    @click="contactOpen = !contactOpen"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold transition shadow-2xs cursor-pointer">
                                                <span class="text-sm">💬</span>
                                                <span class="truncate max-w-[95px] font-mono text-[11px]">{{ $rawContactPhone }}</span>
                                                <span class="text-[9px] text-emerald-600">▼</span>
                                            </button>
                                            <div x-show="contactOpen"
                                                 @click.outside="contactOpen = false"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="transform opacity-0 scale-95"
                                                 x-transition:enter-end="transform opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="transform opacity-100 scale-100"
                                                 x-transition:leave-end="transform opacity-0 scale-95"
                                                 class="absolute left-0 mt-1.5 w-64 rounded-2xl bg-white shadow-xl border border-slate-200 py-2 z-50 divide-y divide-slate-100"
                                                 style="display: none;">
                                                <div class="px-3.5 py-2 bg-slate-50/70">
                                                    <div class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400">যোগাযোগের ব্যক্তি</div>
                                                    <div class="text-xs font-bold text-slate-800 flex items-center justify-between mt-0.5">
                                                        <span class="truncate max-w-[130px]">{{ $contactName ?: 'মালিক / এজেন্ট' }}</span>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-extrabold {{ $isSheetSync ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                                            {{ $isSheetSync ? 'Google Sheet' : 'ওয়েবসাইট লিস্টিং' }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] font-mono text-slate-500 mt-0.5">{{ $rawContactPhone }}</div>
                                                </div>
                                                <div class="p-1.5 space-y-1">
                                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" @click="contactOpen = false"
                                                       class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl flex items-center gap-2 transition cursor-pointer">
                                                        <span class="text-base">💬</span>
                                                        <div>
                                                            <div class="font-bold">কন্টাক্ট ওপেন করুন</div>
                                                            <div class="text-[10px] text-slate-400">হোয়াটসঅ্যাপে সরাসরি চ্যাট খুলুন</div>
                                                        </div>
                                                    </a>
                                                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode($listingMsg) }}" target="_blank" @click="contactOpen = false"
                                                       class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 rounded-xl flex items-center gap-2 transition cursor-pointer">
                                                        <span class="text-base">📢</span>
                                                        <div>
                                                            <div class="font-bold text-emerald-700">তাকে এই প্রপার্টি লিস্টিং সম্পর্কে জানান</div>
                                                            <div class="text-[10px] text-slate-400">প্ল্যাটফর্মে লিস্টিং সংক্রান্ত রেডি মেসেজ পাঠান</div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">নম্বর নেই</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Select Dropdown (Instant Inline Change) -->
                            <td class="p-3.5">
                                <div class="relative inline-flex items-center">
                                    <select 
                                        :disabled="loadingStatuses[{{ $property->id }}]"
                                        :value="statuses[{{ $property->id }}]"
                                        @change="changeStatus({{ $property->id }}, '{{ $property->property_code }}', $event.target.value, $event.target)"
                                        :class="getStatusBadgeClass(statuses[{{ $property->id }}]) + (loadingStatuses[{{ $property->id }}] ? ' opacity-50 cursor-wait' : '')"
                                        class="border rounded-xl px-2.5 py-1.5 text-xs font-black cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all shadow-xs pr-7">
                                        <option value="live">🟢 Live (অনুমোদিত)</option>
                                        <option value="pending">🟡 Pending (পেন্ডিং)</option>
                                        <option value="sold">⚫ Sold (বিক্রিত)</option>
                                        <option value="booked">🔵 Booked (বুকড)</option>
                                        <option value="draft">⚪ Draft (ড্রাফট)</option>
                                        <option value="archived">🔴 Archived (আর্কাইভ)</option>
                                    </select>
                                    <template x-if="loadingStatuses[{{ $property->id }}]">
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">
                                            <svg class="animate-spin h-3.5 w-3.5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- View Page -->
                                    <a href="{{ route('property.show', $property->slug ?: $property->property_code) }}" target="_blank"
                                       class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition shadow-xs inline-flex items-center gap-1"
                                       title="ফ্রন্টএন্ড পেজ দেখুন">
                                        <span>পেজ ↗</span>
                                    </a>

                                    <!-- Facebook Post Button -->
                                    <button type="button"
                                            @click="openFbModal({{ $property->toJson() }})"
                                            class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white rounded-xl text-xs font-bold transition border border-blue-200 shadow-xs inline-flex items-center gap-1 cursor-pointer"
                                            title="ফেসবুক পেজে পোস্ট করুন">
                                        <span>📱 FB পোস্ট</span>
                                        @if($property->fb_post_id)
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" title="পোস্ট করা হয়েছে"></span>
                                        @endif
                                    </button>

                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.properties.edit', $property) }}"
                                       class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs inline-flex items-center gap-1 cursor-pointer"
                                       title="প্রপার্টি বিবরণ ও মিডিয়া এডিট করুন">
                                        <span>✏️ এডিট</span>
                                    </a>

                                    @if($property->status === 'live')
                                        <!-- Unpublish to Private Vault -->
                                        <form action="{{ route('admin.properties.unpublish', $property) }}" method="POST" onsubmit="return confirm('প্রপার্টিটি কি পাবলিক সাইট থেকে সরিয়ে প্রাইভেট ডিরেক্টরিতে অফলাইন করতে চান?');" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white rounded-xl text-xs font-bold transition border border-amber-200 shadow-xs inline-flex items-center gap-1 cursor-pointer" title="প্রাইভেট ভল্টে স্থানান্তর (অফলাইন করুন)">
                                                <span>🗄️</span>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete Button (SPA) -->
                                    <button type="button"
                                            @click="deleteProperty({{ $property->id }}, '{{ $property->property_code }}')"
                                            class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white rounded-xl text-xs font-bold transition border border-rose-200 shadow-xs inline-flex items-center gap-1 cursor-pointer"
                                            title="প্রপার্টি মুছে ফেলুন">
                                        <span>🗑️</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-12 text-center text-xs text-slate-400">
                                কোনো প্রপার্টি খুঁজে পাওয়া যায়নি।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $properties->links() }}
        </div>
    </div>

    <!-- Auto-Assign Modal -->
    <div x-show="showAutoAssignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.away="showAutoAssignModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-2 flex items-center gap-2">
                <span>⚡</span> ইনসার্টার অটো-অ্যাসাইন
            </h3>
            <p class="text-xs text-slate-500 mb-4" x-text="autoAssignDesc || 'নির্বাচিত প্রপার্টিগুলোতে একজন ইনসার্টার বা এজেন্ট অ্যাসাইন করুন।'"></p>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">ইনসার্টার / এজেন্ট নির্বাচন করুন</label>
                    <select x-model="selectedAgentId" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">-- এজেন্ট সিলেক্ট করুন --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->phone ?? $agent->whatsapp_number ?? 'No Phone' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAutoAssignModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">
                        বাতিল
                    </button>
                    <button type="button" @click="executeAutoAssign()" :disabled="!selectedAgentId || assigning" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-sm flex items-center gap-1.5 cursor-pointer">
                        <span x-text="assigning ? 'প্রসেসিং হচ্ছে...' : 'অ্যাসাইন সম্পন্ন করুন'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- FB Share Modal (SPA) -->
    <div x-show="showFbModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200" @click.away="showFbModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-2 flex items-center gap-2">
                <span>🌐</span> ফেসবুক পেজে পোস্ট করুন
            </h3>
            <p class="text-xs text-slate-500 mb-3">পোস্টের ক্যাপশনটি নিচে প্রিভিউ করা হয়েছে। প্রয়োজনে এডিট করে সরাসরি ফেসবুক পেজে পোস্ট করুন।</p>
            <form @submit.prevent="submitFbPost()">
                <div class="mb-4">
                    <textarea name="caption" rows="6" x-model="fbCaption" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showFbModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">
                        বাতিল
                    </button>
                    <button type="submit" :disabled="postingFb" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-sm flex items-center gap-1.5">
                        <span x-text="postingFb ? 'পোস্ট হচ্ছে...' : '🚀 পেজে পাবলিশ করুন'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function propertyCatalog() {
    return {
        stats: {
            total: {{ $stats['total'] }},
            live: {{ $stats['live'] }},
            pending: {{ $stats['pending'] }},
            booked: {{ $stats['booked'] }},
            sold: {{ $stats['sold'] }},
            archived: {{ $stats['archived'] }},
            unassigned: {{ $stats['unassigned'] }}
        },
        statuses: {
            @foreach($properties as $property)
                {{ $property->id }}: '{{ $property->status === "pending_verification" ? "pending" : $property->status }}',
            @endforeach
        },
        loadingStatuses: {},
        selectedIds: [],
        isFiltered: {{ $isFiltered ? 'true' : 'false' }},
        filterLabel: '{{ addslashes($filterSummary) }}',
        autoAssignDesc: '',
        showAutoAssignModal: false,
        selectedAgentId: '',
        assigning: false,
        showFbModal: false,
        currentFbProperty: null,
        fbCaption: '',
        postingFb: false,

        openAutoAssignModal(ids = null) {
            if (ids && ids.length > 0) {
                this.selectedIds = ids;
                this.autoAssignDesc = `${this.selectedIds.length}টি নির্দিষ্ট প্রপার্টি নির্বাচন করা হয়েছে।`;
                this.showAutoAssignModal = true;
            } else if (this.selectedIds && this.selectedIds.length > 0) {
                this.autoAssignDesc = `${this.selectedIds.length}টি নির্বাচিত প্রপার্টিতে ইনসার্টার অ্যাসাইন করা হবে।`;
                this.showAutoAssignModal = true;
            } else if (this.isFiltered) {
                const label = this.filterLabel ? this.filterLabel : 'ফিল্টার্ড প্রপার্টি';
                this.autoAssignDesc = `সক্রিয় ফিল্টার (${label}) অনুযায়ী বর্তমান পেজের প্রপার্টিগুলোতে ইনসার্টার অ্যাসাইন করা হবে।`;
                this.showAutoAssignModal = true;
            } else {
                if (window.showToast) {
                    window.showToast('⚠️ শুধুমাত্র ফিল্টার্ড বা নির্বাচিত আইটেমে কাজ করবে। দয়া করে আগে ফিল্টার করুন অথবা চেকবক্স দিয়ে আইটেম সিলেক্ট করুন।', 'error');
                } else {
                    alert('⚠️ শুধুমাত্র ফিল্টার্ড বা নির্বাচিত আইটেমে কাজ করবে। দয়া করে আগে ফিল্টার করুন অথবা চেকবক্স দিয়ে আইটেম সিলেক্ট করুন।');
                }
            }
        },

        getStatusBadgeClass(status) {
            switch (status) {
                case 'live':
                    return 'bg-emerald-100 text-emerald-800 border-emerald-300';
                case 'pending':
                case 'pending_verification':
                    return 'bg-amber-100 text-amber-800 border-amber-300';
                case 'sold':
                    return 'bg-slate-800 text-white border-slate-700';
                case 'booked':
                    return 'bg-blue-100 text-blue-800 border-blue-300';
                case 'draft':
                    return 'bg-gray-100 text-gray-700 border-gray-300';
                case 'archived':
                    return 'bg-rose-100 text-rose-800 border-rose-300';
                default:
                    return 'bg-slate-100 text-slate-700 border-slate-200';
            }
        },

        getStatusLabel(status) {
            switch (status) {
                case 'live': return 'LIVE (অনুমোদিত)';
                case 'pending':
                case 'pending_verification': return 'PENDING (পেন্ডিং)';
                case 'sold': return 'SOLD (বিক্রিত)';
                case 'booked': return 'BOOKED (বুকড)';
                case 'draft': return 'DRAFT (ড্রাফট)';
                case 'archived': return 'ARCHIVED (আর্কাইভ)';
                default: return (status || '').toUpperCase();
            }
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedIds = @json($properties->pluck('id'));
            } else {
                this.selectedIds = [];
            }
        },

        async changeStatus(propertyId, propertyCode, newStatus, selectEl) {
            const prevStatus = this.statuses[propertyId];
            if (newStatus === prevStatus) return;

            this.loadingStatuses[propertyId] = true;

            try {
                const res = await fetch(`{{ route('admin.properties.index') }}/${propertyId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                const data = await res.json();
                if (data.success) {
                    this.statuses[propertyId] = newStatus;

                    // Dynamically update counter badges without reload
                    if (this.stats[prevStatus] !== undefined) {
                        this.stats[prevStatus] = Math.max(0, this.stats[prevStatus] - 1);
                    }
                    if (this.stats[newStatus] !== undefined) {
                        this.stats[newStatus]++;
                    }

                    if (window.showToast) {
                        window.showToast(`প্রপার্টি #${propertyCode} এর স্ট্যাটাস '${this.getStatusLabel(newStatus)}' করা হয়েছে!`, 'success');
                    }
                } else {
                    this.statuses[propertyId] = prevStatus;
                    if (selectEl) selectEl.value = prevStatus;
                    if (window.showToast) {
                        window.showToast(data.error || 'স্ট্যাটাস আপডেট ব্যর্থ হয়েছে।', 'error');
                    }
                }
            } catch (err) {
                this.statuses[propertyId] = prevStatus;
                if (selectEl) selectEl.value = prevStatus;
                if (window.showToast) {
                    window.showToast('সার্ভার এরর: স্ট্যাটাস পরিবর্তন করা সম্ভব হয়নি।', 'error');
                }
            } finally {
                this.loadingStatuses[propertyId] = false;
            }
        },

        async deleteProperty(propertyId, propertyCode) {
            if (!confirm(`আপনি কি নিশ্চিত প্রপার্টি #${propertyCode} ডিলিট করতে চান?`)) return;

            try {
                const res = await fetch(`{{ route('admin.properties.index') }}/${propertyId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    const row = document.getElementById(`property-row-${propertyId}`);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.95)';
                        setTimeout(() => row.remove(), 300);
                    }
                    this.stats.total = Math.max(0, this.stats.total - 1);
                    const currentStatus = this.statuses[propertyId];
                    if (currentStatus === 'live') this.stats.live = Math.max(0, this.stats.live - 1);
                    if (currentStatus === 'pending') this.stats.pending = Math.max(0, this.stats.pending - 1);
                    delete this.statuses[propertyId];
                    this.selectedIds = this.selectedIds.filter(id => id != propertyId);

                    if (window.showToast) {
                        window.showToast(data.message || `প্রপার্টি #${propertyCode} ডিলিট হয়েছে!`, 'success');
                    }
                } else {
                    if (window.showToast) {
                        window.showToast(data.message || 'ডিলিট ব্যর্থ হয়েছে।', 'error');
                    }
                }
            } catch (err) {
                if (window.showToast) {
                    window.showToast('সার্ভার এরর: ডিলিট সম্পন্ন করা যায়নি।', 'error');
                }
            }
        },

        async executeBulkDelete() {
            if (!confirm(`আপনি কি নিশ্চিত নির্বাচিত ${this.selectedIds.length} টি প্রপার্টি ডিলিট করতে চান?`)) return;
            try {
                const res = await fetch('{{ route('admin.properties.bulk-delete') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: this.selectedIds })
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedIds.forEach(id => {
                        const row = document.getElementById(`property-row-${id}`);
                        if (row) {
                            row.style.transition = 'all 0.3s ease';
                            row.style.opacity = '0';
                            row.style.transform = 'scale(0.95)';
                            setTimeout(() => row.remove(), 300);
                        }
                        const curStatus = this.statuses[id];
                        if (curStatus === 'live') this.stats.live = Math.max(0, this.stats.live - 1);
                        if (curStatus === 'pending') this.stats.pending = Math.max(0, this.stats.pending - 1);
                        delete this.statuses[id];
                    });
                    this.stats.total = Math.max(0, this.stats.total - data.count);
                    if (window.showToast) {
                        window.showToast(`সফলভাবে ${data.count} টি প্রপার্টি ডিলিট করা হয়েছে!`, 'success');
                    }
                    this.selectedIds = [];
                } else {
                    if (window.showToast) {
                        window.showToast(data.error || 'ডিলিট ব্যর্থ হয়েছে', 'error');
                    }
                }
            } catch (err) {
                if (window.showToast) {
                    window.showToast('সার্ভার এরর: ডিলিট সম্পন্ন করা যায়নি।', 'error');
                }
            }
        },

        async executeAutoAssign() {
            if (!this.selectedAgentId) return;
            this.assigning = true;
            try {
                const targetIds = this.selectedIds.length > 0 ? this.selectedIds : Object.keys(this.statuses);
                const res = await fetch('{{ route('admin.properties.auto-assign') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ 
                        agent_id: this.selectedAgentId,
                        property_ids: targetIds
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showAutoAssignModal = false;
                    this.stats.unassigned = Math.max(0, this.stats.unassigned - data.assigned_count);
                    if (window.showToast) {
                        window.showToast(data.message || `সফলভাবে ${data.assigned_count} টি প্রপার্টি ${data.agent_name} এর সাথে অ্যাসাইন করা হয়েছে!`, 'success');
                    }
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (window.showToast) {
                        window.showToast(data.error || 'অ্যাসাইন ব্যর্থ হয়েছে', 'error');
                    }
                }
            } catch (err) {
                if (window.showToast) {
                    window.showToast('সার্ভার এরর', 'error');
                }
            } finally {
                this.assigning = false;
            }
        },

        openFbModal(property) {
            this.currentFbProperty = property;
            const price = property.asking_price || (property.flat_detail ? property.flat_detail.price_total : (property.land_detail ? property.land_detail.price_total : 0));
            this.fbCaption = `🏡 [নতুন লিস্টিং] ${property.title}\n🆔 কোড: #${property.property_code}\n📍 লোকেশন: আফতাবনগর, ব্লক: ${property.block || ''}\n💰 মূল্য: ৳ ${Number(price).toLocaleString()}\n\n🔗 বিস্তারিত ও ছবি দেখতে লিংকে যান:\n{{ url('property') }}/${property.slug}\n\n#PropertyKoi #Aftabnagar #RealEstate`;
            this.showFbModal = true;
        },

        async submitFbPost() {
            if (!this.currentFbProperty) return;
            this.postingFb = true;
            try {
                const res = await fetch(`{{ route('admin.properties.index') }}/${this.currentFbProperty.id}/fb-post`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ caption: this.fbCaption })
                });
                const data = await res.json();
                if (data.success) {
                    if (window.showToast) {
                        window.showToast(`ফেসবুকে সফলভাবে পোস্ট করা হয়েছে! (Post ID: ${data.post_id || 'OK'})`, 'success');
                    }
                    this.showFbModal = false;
                } else {
                    if (window.showToast) {
                        window.showToast(`ফেসবুক পোস্ট ব্যর্থ: ${data.error || 'ত্রুটি'}`, 'error');
                    }
                }
            } catch (err) {
                if (window.showToast) {
                    window.showToast('ফেসবুক এপিআই কল ব্যর্থ হয়েছে।', 'error');
                }
            } finally {
                this.postingFb = false;
            }
        }
    }
}
</script>
@endpush
