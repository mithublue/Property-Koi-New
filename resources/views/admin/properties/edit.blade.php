@extends('layouts.admin')

@section('title', 'প্রপার্টি এডিট #' . $property->property_code)
@section('page_title', 'প্রপার্টি এডিট #' . $property->property_code)

@section('content')
@php
    $vis = is_array($property->address_visibility) ? $property->address_visibility : (json_decode($property->address_visibility, true) ?: []);
    $visBlock = isset($vis['block']) ? filter_var($vis['block'], FILTER_VALIDATE_BOOLEAN) : true;
    $visRoad = isset($vis['road_number']) ? filter_var($vis['road_number'], FILTER_VALIDATE_BOOLEAN) : true;
    $visPlot = isset($vis['plot_number']) ? filter_var($vis['plot_number'], FILTER_VALIDATE_BOOLEAN) : true;
    $visRoadWidth = isset($vis['road_width_ft']) ? filter_var($vis['road_width_ft'], FILTER_VALIDATE_BOOLEAN) : true;
@endphp

<div class="max-w-5xl mx-auto space-y-6" x-data="{ activeTab: '{{ request('tab', 'basic') }}', address_visibility: { block: @json((bool)$visBlock), road_number: @json((bool)$visRoad), plot_number: @json((bool)$visPlot), road_width_ft: @json((bool)$visRoadWidth) } }">

    <!-- Header Actions -->
    <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 bg-slate-900 text-white font-mono rounded-lg text-xs font-bold">#{{ $property->property_code }}</span>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $property->status === 'live' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                {{ strtoupper($property->status) }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('property.show', $property->slug) }}" target="_blank" class="px-3.5 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold rounded-xl transition flex items-center gap-1">
                <span>👁️</span> লাইভ দেখুন
            </a>
            <form action="{{ route('admin.properties.fb-post', $property) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold rounded-xl transition flex items-center gap-1">
                    <span>🚀</span> ফেসবুকে পোস্ট
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 bg-white p-2 rounded-2xl shadow-sm text-xs font-semibold">
        <button type="button" @click="activeTab = 'basic'" :class="activeTab === 'basic' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2.5 rounded-xl transition">
            1. মৌলিক তথ্য ও মূল্য
        </button>
        <button type="button" @click="activeTab = 'specs'" :class="activeTab === 'specs' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2.5 rounded-xl transition">
            2. স্পেসিফিকেশন ও টাইপ
        </button>
        <button type="button" @click="activeTab = 'verification'" :class="activeTab === 'verification' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2.5 rounded-xl transition">
            3. ৪-দফা ভেরিফিকেশন চেকলিস্ট
        </button>
        <button type="button" @click="activeTab = 'media'" :class="activeTab === 'media' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2.5 rounded-xl transition">
            4. মিডিয়া ও সরাসরি সোর্স
        </button>
    </div>

    <!-- Main Update Form -->
    <form action="{{ route('admin.properties.update', $property) }}" method="POST" class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" :value="activeTab">

        <!-- Tab 1: Basic Information -->
        <div x-show="activeTab === 'basic'" class="space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">মৌলিক তথ্য ও লোকেশন</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">লিস্টিং শিরোনাম *</label>
                    <input type="text" name="title" value="{{ old('title', $property->title) }}" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">প্রপার্টি ধরন *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="land" {{ $property->type === 'land' ? 'selected' : '' }}>জমি / প্লট (Land)</option>
                        <option value="flat" {{ $property->type === 'flat' ? 'selected' : '' }}>রেডি ফ্ল্যাট (Flat)</option>
                        <option value="commercial" {{ $property->type === 'commercial' ? 'selected' : '' }}>কমার্শিয়াল স্পেস (Commercial)</option>
                        <option value="land_share" {{ $property->type === 'land_share' ? 'selected' : '' }}>ল্যান্ড শেয়ার (Share)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">এরিয়া</label>
                    <input type="text" name="area" value="{{ old('area', $property->area) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">ব্লক</label>
                        <button type="button" 
                                @click="address_visibility.block = !address_visibility.block"
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                :class="address_visibility.block ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                :title="address_visibility.block ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                            <span x-text="address_visibility.block ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[block]" :value="address_visibility.block ? 1 : 0">
                    <input type="text" name="block" value="{{ old('block', $property->block) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">রোড নম্বর</label>
                        <button type="button" 
                                @click="address_visibility.road_number = !address_visibility.road_number"
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                :class="address_visibility.road_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                :title="address_visibility.road_number ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                            <span x-text="address_visibility.road_number ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[road_number]" :value="address_visibility.road_number ? 1 : 0">
                    <input type="text" name="road_number" value="{{ old('road_number', $property->road_number) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">প্লট নম্বর</label>
                        <button type="button" 
                                @click="address_visibility.plot_number = !address_visibility.plot_number"
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                :class="address_visibility.plot_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                :title="address_visibility.plot_number ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                            <span x-text="address_visibility.plot_number ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[plot_number]" :value="address_visibility.plot_number ? 1 : 0">
                    <input type="text" name="plot_number" value="{{ old('plot_number', $property->plot_number) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফেসিং</label>
                    <select name="facing" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                        <option value="">নির্বাচন করুন</option>
                        <option value="south" {{ $property->facing === 'south' ? 'selected' : '' }}>দক্ষিণমুখী</option>
                        <option value="north" {{ $property->facing === 'north' ? 'selected' : '' }}>উত্তরমুখী</option>
                        <option value="east" {{ $property->facing === 'east' ? 'selected' : '' }}>পূর্বমুখী</option>
                        <option value="west" {{ $property->facing === 'west' ? 'selected' : '' }}>পশ্চিমমুখী</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">সাইজ (কাঠা/স্কয়ারফিট)</label>
                    <input type="number" step="0.01" name="size" value="{{ old('size', $property->size) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">মোট মূল্য (Asking Price ৳)</label>
                    <input type="number" name="asking_price" value="{{ old('asking_price', $property->asking_price) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs font-bold text-emerald-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">প্রতি কাঠা মূল্য (৳)</label>
                    <input type="number" name="price_per_katha" value="{{ old('price_per_katha', $property->price_per_katha) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ইনসার্টার / মালিক</label>
                    <select name="created_by_user_id" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                        <option value="">-- অনির্ধারিত (Unassigned) --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ $property->created_by_user_id == $agent->id ? 'selected' : '' }}>{{ $agent->name }} ({{ $agent->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">স্ট্যাটাস *</label>
                    <select name="status" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs font-bold">
                        <option value="live" {{ $property->status === 'live' ? 'selected' : '' }}>🟢 Live (অনুমোদিত)</option>
                        <option value="pending" {{ $property->status === 'pending' || $property->status === 'pending_verification' ? 'selected' : '' }}>🟡 Pending (পেন্ডিং)</option>
                        <option value="sold" {{ $property->status === 'sold' ? 'selected' : '' }}>⚫ Sold (বিক্রিত)</option>
                        <option value="booked" {{ $property->status === 'booked' ? 'selected' : '' }}>🔵 Booked (বুকড)</option>
                        <option value="draft" {{ $property->status === 'draft' ? 'selected' : '' }}>⚪ Draft (ড্রাফট)</option>
                        <option value="archived" {{ $property->status === 'archived' ? 'selected' : '' }}>🔴 Archived (আর্কাইভ)</option>
                    </select>
                </div>
                <div class="flex items-center gap-4 pt-5">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="is_negotiable" value="1" {{ $property->is_negotiable ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>আলোচনা সাপেক্ষ</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-amber-700">
                        <input type="checkbox" name="is_featured" value="1" {{ $property->is_featured ? 'checked' : '' }} class="rounded text-amber-600">
                        <span>ফিচার্ড</span>
                    </label>
                </div>
            </div>

            <!-- Direct Owner / WhatsApp Contact Information -->
            @php
                $primarySource = $property->directSources?->firstWhere('is_primary_source', true) ?? $property->directSources?->first();
                $initialOwnerName = old('owner_name', $primarySource?->owner_name ?? ($property->creator?->name ?? ''));
                $initialOwnerPhone = old('owner_phone', $primarySource?->owner_phone ?? ($property->creator?->whatsapp_number ?? ($property->creator?->phone ?? '')));
            @endphp
            <div class="p-4 bg-emerald-50/60 border border-emerald-200/80 rounded-2xl space-y-3 mt-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-base">💬</span>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                            সরাসরি মালিক / হোয়াটসঅ্যাপ যোগাযোগ তথ্য (Owner Phone & WhatsApp)
                        </h4>
                    </div>
                    @if($primarySource && $primarySource->relationship_declaration)
                        <span class="text-[10px] px-2 py-0.5 rounded font-bold bg-white text-emerald-800 border border-emerald-300">
                            {{ $primarySource->relationship_declaration }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">মালিকের নাম (Owner Name)</label>
                        <input type="text" name="owner_name" value="{{ $initialOwnerName }}" placeholder="প্রপার্টির মূল মালিকের নাম" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs bg-white font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">মালিকের ফোন নম্বর / WhatsApp *</label>
                        <div class="relative">
                            <input type="text" name="owner_phone" value="{{ $initialOwnerPhone }}" placeholder="যেমন: 01711223344" class="w-full pl-9 pr-3.5 py-2.5 border border-slate-300 rounded-xl text-xs bg-white font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-emerald-600">💬</span>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">ক্যাটালগ লিস্টের হোয়াটসঅ্যাপ বাটন ও নোটিফিকেশনে এই নম্বরটি ব্যবহৃত হয়।</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">সোর্স সম্পর্ক / ধরণ (Source Type)</label>
                        <select name="source_type" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs bg-white font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="direct_owner" {{ ($primarySource?->source_type === 'direct_owner' || $primarySource?->source_type === 'owner') ? 'selected' : '' }}>সরাসরি প্রপার্টির মালিক (Direct Owner)</option>
                            <option value="direct_agent" {{ ($primarySource?->source_type === 'direct_agent' || $primarySource?->source_type === 'agent') ? 'selected' : '' }}>এজেন্ট / মিডিয়া (Agent)</option>
                            <option value="developer" {{ $primarySource?->source_type === 'developer' ? 'selected' : '' }}>ডেভেলপার (Developer)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Specs & Details -->
        <div x-show="activeTab === 'specs'" x-cloak class="space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">স্পেসিফিকেশন ও বিবরণ</h3>

            @if($property->type === 'land')
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 m-0">রাস্তার প্রশস্ততা (ফিট)</label>
                            <button type="button" 
                                    @click="address_visibility.road_width_ft = !address_visibility.road_width_ft"
                                    class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                    :class="address_visibility.road_width_ft ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                    :title="address_visibility.road_width_ft ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                <span x-text="address_visibility.road_width_ft ? '🔒 হাইড' : '👁️ শো'"></span>
                            </button>
                        </div>
                        <input type="hidden" name="address_visibility[road_width_ft]" :value="address_visibility.road_width_ft ? 1 : 0">
                        <input type="number" name="road_width_ft" value="{{ $property->road_width_ft ?? 20 }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ক্যাটাগরি</label>
                        <select name="plot_category" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                            <option value="regular" {{ !$property->landDetail?->is_corner && !$property->landDetail?->is_lake_facing ? 'selected' : '' }}>রেগুলার প্লট</option>
                            <option value="corner" {{ $property->landDetail?->is_corner ? 'selected' : '' }}>কর্নার প্লট</option>
                            <option value="lake_view" {{ $property->landDetail?->is_lake_facing ? 'selected' : '' }}>লেক ভিউ প্লট</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="boundary_wall" value="1" {{ $property->landDetail?->boundary_wall_done ? 'checked' : '' }} class="rounded text-emerald-600">
                            <span>বাউন্ডারি ওয়াল আছে</span>
                        </label>
                    </div>
                </div>
            @elseif($property->type === 'flat')
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ফ্ল্যাট সাইজ (স্কয়ারফিট)</label>
                        <input type="number" name="flat_size_sqft" value="{{ old('flat_size_sqft', $property->flatDetail?->flat_size_sqft) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">বেডরুম</label>
                        <input type="number" name="bedrooms" value="{{ old('bedrooms', $property->flatDetail?->bedrooms ?? 3) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">বাথরুম</label>
                        <input type="number" name="bathrooms" value="{{ old('bathrooms', $property->flatDetail?->bathrooms ?? 3) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ফ্লোর নম্বর</label>
                        <input type="text" name="floor_no" value="{{ old('floor_no', $property->flatDetail?->floor_number) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="lift" value="1" {{ old('lift', $property->flatDetail?->has_lift) ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>লিফট সুবিধা (Lift)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="generator" value="1" {{ old('generator', $property->flatDetail?->has_generator) ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>জেনারেটর ব্যাকআপ</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="parking" value="1" {{ old('parking', $property->flatDetail?->parking_available) ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>গাড়ি পার্কিং সুবিধা</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="has_gas" value="1" {{ old('has_gas', $property->flatDetail?->has_gas) ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>গ্যাস সংযোগ</span>
                    </label>
                </div>
            @elseif($property->type === 'commercial')
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">স্পেস সাইজ (স্কয়ারফিট)</label>
                        <input type="number" name="commercial_size_sqft" value="{{ old('commercial_size_sqft', $property->commercialDetail?->size_sqft) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">কমার্শিয়াল ধরন</label>
                        <select name="commercial_type" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                            <option value="shop" {{ old('commercial_type', $property->commercialDetail?->commercial_type) === 'shop' ? 'selected' : '' }}>দোকান / শপ</option>
                            <option value="office" {{ old('commercial_type', $property->commercialDetail?->commercial_type) === 'office' ? 'selected' : '' }}>অফিস স্পেস</option>
                            <option value="floor" {{ old('commercial_type', $property->commercialDetail?->commercial_type) === 'floor' ? 'selected' : '' }}>পুরো ফ্লোর</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ফ্লোর নম্বর</label>
                        <input type="text" name="commercial_floor_no" value="{{ old('commercial_floor_no', $property->commercialDetail?->floor_number) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>
            @elseif($property->type === 'land_share')
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">মোট শেয়ার</label>
                        <input type="number" name="total_shares" value="{{ old('total_shares', $property->shareDetail?->total_shares ?? 1) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">অবশিষ্ট শেয়ার</label>
                        <input type="number" name="available_shares" value="{{ old('available_shares', $property->shareDetail?->available_shares ?? 1) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">প্রতি শেয়ার মূল্য (৳)</label>
                        <input type="number" name="price_per_share" value="{{ old('price_per_share', $property->shareDetail?->share_price) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">জমির পরিমাণ (কাঠা)</label>
                        <input type="number" step="0.01" name="share_land_size_katha" value="{{ old('share_land_size_katha', $property->shareDetail?->land_size_katha) }}" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>
            @endif

            <div class="space-y-4 pt-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">বিস্তারিত বিবরণ (Description)</label>
                    <textarea name="description" rows="4" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">{{ old('description', $property->description) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফেসবুক ক্যাপশন ওভাররাইড</label>
                    <textarea name="fb_caption" rows="3" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">{{ old('fb_caption', $property->fb_caption) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Tab 3: 4-Point Verification Checklist -->
        <div x-show="activeTab === 'verification'" x-cloak class="space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">৪-দফা নিরাপত্তা ও ভেরিফিকেশন চেকলিস্ট</h3>
            <p class="text-xs text-slate-500 mb-2">এই ৪টি তথ্য নিশ্চিত হলে সিস্টেম স্বয়ংক্রিয়ভাবে প্রপার্টিকে লাইভ স্ট্যাটাসে নিয়ে যাবে এবং সার্চ ইঞ্জিনে IndexNow পিং পাঠাবে।</p>

            <div class="space-y-3 bg-slate-50 p-5 rounded-2xl border border-slate-200">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="checklist_owner_identity" value="1" {{ $property->verification?->checklist_owner_identity ? 'checked' : '' }} class="mt-0.5 rounded text-emerald-600">
                    <div>
                        <div class="text-xs font-bold text-slate-800">১. প্রপার্টি মালিকের এনআইডি / পরিচয়পত্র যাচাই (Owner Identity Checked)</div>
                        <div class="text-[11px] text-slate-500">মালিকের এনআইডি কার্ড অথবা ডেভেলপার অথোরাইজেশন লেটার চেক করা হয়েছে।</div>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="checklist_location" value="1" {{ $property->verification?->checklist_location ? 'checked' : '' }} class="mt-0.5 rounded text-emerald-600">
                    <div>
                        <div class="text-xs font-bold text-slate-800">২. ফিজিক্যাল লোকেশন ও প্লট স্পট যাচাই (Location Checked)</div>
                        <div class="text-[11px] text-slate-500">আফতাবনগরের ব্লক ও রোডে ফিল্ড প্রতিনিধি গিয়ে বাস্তব লোকেশন নিশ্চিত করেছেন।</div>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="checklist_basic_info" value="1" {{ $property->verification?->checklist_basic_info ? 'checked' : '' }} class="mt-0.5 rounded text-emerald-600">
                    <div>
                        <div class="text-xs font-bold text-slate-800">৩. সাইজ, ফেসিং ও রাস্তা যাচাই (Basic Info Checked)</div>
                        <div class="text-[11px] text-slate-500">কাঠা সাইজ, সামনের রাস্তা কত ফিট এবং ফেসিং সঠিক কিনা নিশ্চিত করা হয়েছে।</div>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="checklist_documents" value="1" {{ $property->verification?->checklist_documents ? 'checked' : '' }} class="mt-0.5 rounded text-emerald-600">
                    <div>
                        <div class="text-xs font-bold text-slate-800">৪. প্রাথমিক দলিল ও পরচা কপি যাচাই (Basic Documents Checked)</div>
                        <div class="text-[11px] text-slate-500">খাজনা দাখিলা, মিউটেশন অথবা বরাদ্দপত্র কপি যাচাই করা হয়েছে।</div>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">ভেরিফিকেশন নোট</label>
                <textarea name="verification_notes" rows="2" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">{{ $property->verification?->verification_notes }}</textarea>
            </div>
        </div>

        <!-- Tab 4: Media & Direct Sources -->
        <div x-show="activeTab === 'media'" x-cloak class="space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">মিডিয়া গ্যালারি ও সরাসরি সোর্স রেকর্ড</h3>

            <!-- Existing Media Grid -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 mb-2">সংযুক্ত ছবিসমূহ (Photos)</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @forelse($property->media as $media)
                        <div class="relative group rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100">
                            <img src="{{ $media->file_path }}" class="w-full h-full object-cover">
                            @if($media->is_cover)
                                <span class="absolute top-1 left-1 bg-emerald-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">COVER</span>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-4 p-4 text-center text-xs text-slate-400 bg-slate-50 rounded-xl">কোনো ছবি আপলোড করা নেই।</div>
                    @endforelse
                </div>
            </div>

            <!-- Existing Direct Sources -->
            <div class="pt-4 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-700 mb-2">সরাসরি মালিক / সোর্স হিস্ট্রি</h4>
                <div class="divide-y divide-slate-100 bg-slate-50 rounded-xl border border-slate-200 p-3">
                    @forelse($property->directSources as $src)
                        @php
                            $cleanSrcPhone = preg_replace('/[^0-9]/', '', (string)$src->owner_phone);
                            if ($cleanSrcPhone && str_starts_with($cleanSrcPhone, '0')) {
                                $cleanSrcPhone = '88' . $cleanSrcPhone;
                            }
                        @endphp
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800">{{ $src->owner_name ?: 'নাম নেই' }}</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold bg-slate-200 text-slate-700">
                                        {{ $src->relationship_declaration ?: $src->source_type }}
                                    </span>
                                    @if($src->is_primary_source)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">PRIMARY</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-600 mt-1 flex items-center gap-3">
                                    <span class="font-mono">📞 {{ $src->owner_phone ?: 'নম্বর নেই' }}</span>
                                    @if($cleanSrcPhone)
                                        <a href="https://wa.me/{{ $cleanSrcPhone }}" target="_blank" class="text-emerald-700 hover:text-emerald-900 font-bold flex items-center gap-1 transition">
                                            <span>💬 WhatsApp</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                @if($src->asking_price > 0)
                                    <div class="font-bold text-emerald-700">৳ {{ number_format($src->asking_price) }}</div>
                                @endif
                                <div class="text-[10px] text-slate-400">{{ $src->created_at ? $src->created_at->format('d M, Y') : '' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-xs text-slate-400 py-2">কোনো সোর্স রেকর্ড পাওয়া যায়নি।</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="{{ route('admin.properties.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                &larr; ক্যাটালগে ফিরে যান
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-2">
                <span>💾</span> পরিবর্তন সংরক্ষণ করুন
            </button>
        </div>
    </form>

</div>
@endsection
