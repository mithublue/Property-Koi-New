@extends('layouts.app')

@section('content')
@php
    $user = auth()->user();
    $defaultName = $user ? $user->name : old('buyer_name', '');
    $defaultPhone = $user ? ($user->whatsapp_number ?: $user->phone) : old('buyer_phone', '');
    $defaultEmail = $user ? $user->email : old('buyer_email', '');
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 space-y-8"
     x-data="{
        propertyType: '{{ old('property_type', 'land') }}',
        preferredAreas: {{ json_encode(old('preferred_areas', ['Aftabnagar'])) }},
        preferredBlocks: {{ json_encode(old('preferred_blocks', [])) }},
        preferredFacings: {{ json_encode(old('preferred_facings', ['Any'])) }},
        
        // Plot specifics
        kathaSizeChoice: '{{ old('katha_size_choice', '') }}',
        minRoadWidthFt: '{{ old('min_road_width_ft', '') }}',
        cornerPlot: '{{ old('corner_plot', '') }}',
        landStatus: '{{ old('land_status', '') }}',
        
        // Flat specifics
        flatSizePreset: '{{ old('flat_size_preset', '') }}',
        bedCount: '{{ old('bed_count', '') }}',
        bathCount: '{{ old('bath_count', '') }}',
        floorPreference: '{{ old('floor_preference', '') }}',
        handoverStatus: '{{ old('handover_status', '') }}',
        parkingNeeded: '{{ old('parking_needed', '') }}',
        amenities: {{ json_encode(old('amenities', [])) }},
        
        // Size & Budget
        minSize: '{{ old('min_size', '') }}',
        maxSize: '{{ old('max_size', '') }}',
        minBudget: '{{ old('min_budget', '') }}',
        maxBudget: '{{ old('max_budget', '') }}',
        budgetPreset: '{{ old('budget_preset', '') }}',
        
        // Channels
        channels: {{ json_encode(old('notify_channels', ['push', 'email'])) }},
        
        hasEmailChannel() {
            return this.channels.includes('email');
        },
        hasTelegramChannel() {
            return this.channels.includes('telegram');
        },
        
        toggleArea(area) {
            const idx = this.preferredAreas.indexOf(area);
            if (idx > -1) {
                this.preferredAreas.splice(idx, 1);
            } else {
                this.preferredAreas.push(area);
            }
        },
        
        toggleBlock(b) {
            const idx = this.preferredBlocks.indexOf(b);
            if (idx > -1) {
                this.preferredBlocks.splice(idx, 1);
            } else {
                this.preferredBlocks.push(b);
            }
        },
        
        toggleFacing(f) {
            if (f === 'Any') {
                if (this.preferredFacings.includes('Any')) {
                    this.preferredFacings = [];
                } else {
                    this.preferredFacings = ['Any'];
                }
                return;
            }
            const anyIdx = this.preferredFacings.indexOf('Any');
            if (anyIdx > -1) {
                this.preferredFacings.splice(anyIdx, 1);
            }
            const idx = this.preferredFacings.indexOf(f);
            if (idx > -1) {
                this.preferredFacings.splice(idx, 1);
            } else {
                this.preferredFacings.push(f);
            }
        },
        
        setKathaChoice(sz) {
            if (this.kathaSizeChoice === sz) {
                this.kathaSizeChoice = '';
                this.minSize = '';
                this.maxSize = '';
            } else {
                this.kathaSizeChoice = sz;
                if (sz !== 'custom') {
                    this.minSize = sz;
                    this.maxSize = sz;
                }
            }
        },
        
        setRoadWidth(w) {
            this.minRoadWidthFt = (this.minRoadWidthFt === w) ? '' : w;
        },
        
        setCornerPlot(val) {
            this.cornerPlot = (this.cornerPlot === val) ? '' : val;
        },
        
        setLandStatus(val) {
            this.landStatus = (this.landStatus === val) ? '' : val;
        },
        
        setFlatPreset(val) {
            if (this.flatSizePreset === val) {
                this.flatSizePreset = '';
                this.minSize = '';
                this.maxSize = '';
            } else {
                this.flatSizePreset = val;
                if (val === '1000_1200') {
                    this.minSize = '1000';
                    this.maxSize = '1200';
                } else if (val === '1201_1400') {
                    this.minSize = '1201';
                    this.maxSize = '1400';
                } else if (val === '1401_1600') {
                    this.minSize = '1401';
                    this.maxSize = '1600';
                } else if (val === '1601_2000') {
                    this.minSize = '1601';
                    this.maxSize = '2000';
                } else if (val === '2000_plus') {
                    this.minSize = '2000';
                    this.maxSize = '3500';
                }
            }
        },
        
        setBedCount(val) {
            this.bedCount = (this.bedCount === val) ? '' : val;
        },
        
        setBathCount(val) {
            this.bathCount = (this.bathCount === val) ? '' : val;
        },
        
        setFloorPreference(val) {
            this.floorPreference = (this.floorPreference === val) ? '' : val;
        },
        
        setHandoverStatus(val) {
            this.handoverStatus = (this.handoverStatus === val) ? '' : val;
        },
        
        setParkingNeeded(val) {
            this.parkingNeeded = (this.parkingNeeded === val) ? '' : val;
        },
        
        toggleAmenity(am) {
            const idx = this.amenities.indexOf(am);
            if (idx > -1) {
                this.amenities.splice(idx, 1);
            } else {
                this.amenities.push(am);
            }
        },
        
        setBudgetPreset(preset, min, max) {
            if (this.budgetPreset === preset) {
                this.budgetPreset = '';
                this.minBudget = '';
                this.maxBudget = '';
            } else {
                this.budgetPreset = preset;
                this.minBudget = min;
                this.maxBudget = max;
            }
        }
     }">

    <!-- Header Section -->
    <div class="rounded-3xl p-6 sm:p-10 text-white shadow-xl text-center space-y-3 relative overflow-hidden"
         style="background-color: #064e3b; background-image: linear-gradient(135deg, #064e3b 0%, #134e4a 50%, #0f172a 100%);">
        <div class="absolute -right-10 -bottom-10 w-52 h-52 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-44 h-44 bg-teal-500/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-emerald-500/25 border border-emerald-400/30 rounded-full text-xs font-bold uppercase tracking-wider text-emerald-200">
            <span>🤖</span>
            <span>স্মার্ট বায়্যার ডিমান্ড ও অটো-ম্যাচিং ইঞ্জিন</span>
        </div>

        <h1 class="text-2xl sm:text-4xl font-black tracking-tight">আপনার প্রপার্টি চাহিদা রেকর্ড করুন</h1>
        <p class="text-xs sm:text-sm text-emerald-100/90 max-w-2xl mx-auto leading-relaxed">
            আপনার কাঙ্ক্ষিত বাজেট, এলাকা ও সুনির্দিষ্ট শর্ত অনুযায়ী চাহিদা জমা দিন। আমাদের সিস্টেম নতুন ও ডিরেক্ট লিস্টিং যাচাই করে তাৎক্ষণিক এবং দৈনিক শিডিউলের মাধ্যমে পুশ নোটিফিকেশন, ইমেইল এবং হোয়াটসঅ্যাপে আপডেট পাঠাবে।
        </p>

        <div class="pt-3 flex flex-wrap items-center justify-center gap-3 text-[11px] text-emerald-200/90 font-medium">
            <span class="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full border border-white/10">
                <span class="text-emerald-400">✓</span> ১০০% ব্রোকার মুক্ত ও ডিরেক্ট লিস্টিং
            </span>
            <span class="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full border border-white/10">
                <span class="text-emerald-400">✓</span> ±১৫% বাজেট টলারেন্স ম্যাচিং
            </span>
            <span class="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full border border-white/10">
                <span class="text-emerald-400">✓</span> যেকোনো অপশন ক্লিক করে সিলেক্ট ও ডিসিলেক্ট করা যাবে
            </span>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-bold flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('info'))
        <div class="p-4 rounded-2xl bg-sky-50 border border-sky-300 text-sky-900 text-xs font-bold flex items-center gap-2">
            <span>ℹ️</span>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if (session('demand_limit_reached'))
        <div class="p-6 rounded-3xl bg-amber-50 border-2 border-amber-400 text-amber-950 shadow-md space-y-3">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-2xl bg-amber-200 text-amber-800 flex items-center justify-center text-xl shrink-0">
                    ⚠️
                </div>
                <div class="space-y-1.5 flex-1">
                    <h3 class="text-base font-black text-amber-950">
                        চাহিদা সাবমিশনের সর্বোচ্চ সীমা ({{ session('max_demands_limit', 3) }}টি) পূর্ণ হয়েছে!
                    </h3>
                    <p class="text-xs text-amber-900 leading-relaxed font-medium">
                        আপনি ইতিমধ্যে সর্বোচ্চ <strong>{{ session('max_demands_limit', 3) }}টি</strong> চাহিদা সাবমিট করেছেন। নতুন চাহিদা জমা দিতে হলে পূর্বে জমা দেওয়া যেকোনো একটি চাহিদা মুছে ফেলতে হবে।
                    </p>
                    <div class="pt-2 flex flex-wrap gap-2.5">
                        @auth
                            <a href="{{ route('buyer.dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs rounded-xl shadow transition">
                                <span>📋 বায়ার ড্যাশবোর্ড থেকে চাহিদা মুছুন</span>
                                <span>&rarr;</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs rounded-xl shadow transition">
                                <span>🔐 লগইন করে আগের চাহিদা মুছুন</span>
                                <span>&rarr;</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any() && !session('demand_limit_reached'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xl space-y-7">
        <form action="{{ route('chahida.store') }}" method="POST" class="space-y-7 text-xs"
              onsubmit="if(window.trackClarityEvent) window.trackClarityEvent('chahida_submitted', { path: window.location.pathname });">
            @csrf

            <!-- 1. Contact Information -->
            <div class="bg-slate-50/70 p-4 sm:p-5 rounded-2xl border border-slate-200/60">
                <h3 class="text-sm font-extrabold text-slate-900 mb-3 flex items-center gap-2">
                    <span>👤</span>
                    <span>আপনার যোগাযোগের তথ্য</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1.5">আপনার নাম <span class="text-rose-500">*</span></label>
                        <input type="text" name="buyer_name" value="{{ $defaultName }}" required placeholder="যেমন: তানভীর আহমেদ"
                               data-clarity-mask="true"
                               class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1.5">ফোন নম্বর (WhatsApp) <span class="text-rose-500">*</span></label>
                        <input type="tel" name="buyer_phone" value="{{ $defaultPhone }}" required placeholder="০১৭১১-০০০০০০"
                               data-clarity-mask="true"
                               class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1.5 flex items-center justify-between">
                            <span>ইমেইল ঠিকানা</span>
                            <span x-show="hasEmailChannel()" class="text-rose-500 font-extrabold text-[11px] lowercase">* (ইমেইল এলার্ট পেতে আবশ্যক)</span>
                            <span x-show="!hasEmailChannel()" class="text-slate-400 font-normal text-[11px] lowercase">(ঐচ্ছিক)</span>
                        </label>
                        <input type="email" name="buyer_email" value="{{ $defaultEmail }}" :required="hasEmailChannel()" placeholder="example@gmail.com"
                               class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-medium">
                    </div>
                </div>
            </div>

            <!-- 2. Property Type Selection (Primary Switcher) -->
            <div>
                <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2.5">
                    প্রপার্টির ধরন নির্বাচন করুন <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    <button type="button" 
                            @click="propertyType = 'land'"
                            :class="propertyType === 'land' ? 'bg-emerald-700 text-white border-emerald-700 shadow-md ring-2 ring-emerald-500/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                            class="p-3.5 rounded-2xl border text-center transition cursor-pointer flex flex-col items-center justify-center gap-1.5">
                        <span class="text-2xl">🏞️</span>
                        <span class="font-extrabold text-sm">জমি / প্লট</span>
                        <span class="text-[10px] opacity-80">কাঠা হিসেবে প্লটের চাহিদা</span>
                    </button>

                    <button type="button" 
                            @click="propertyType = 'flat'"
                            :class="propertyType === 'flat' ? 'bg-teal-700 text-white border-teal-700 shadow-md ring-2 ring-teal-500/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                            class="p-3.5 rounded-2xl border text-center transition cursor-pointer flex flex-col items-center justify-center gap-1.5">
                        <span class="text-2xl">🏢</span>
                        <span class="font-extrabold text-sm">আবাসিক ফ্ল্যাট</span>
                        <span class="text-[10px] opacity-80">স্কয়ারফিট ও বেডরুম ভিত্তিক</span>
                    </button>

                    <button type="button" 
                            @click="propertyType = 'commercial'"
                            :class="propertyType === 'commercial' ? 'bg-slate-900 text-white border-slate-900 shadow-md ring-2 ring-slate-800/20' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                            class="p-3.5 rounded-2xl border text-center transition cursor-pointer flex flex-col items-center justify-center gap-1.5">
                        <span class="text-2xl">🏪</span>
                        <span class="font-extrabold text-sm">দোকান / কমার্শিয়াল</span>
                        <span class="text-[10px] opacity-80">মার্কেট বা বাণিজ্যিক স্পেস</span>
                    </button>
                </div>
                <input type="hidden" name="property_type" :value="propertyType">
            </div>

            <!-- 3. Preferred Location & Blocks -->
            <div class="pt-2 border-t border-slate-100 space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block font-bold text-slate-800 uppercase">
                            পছন্দের এলাকা (ক্লিক করে সিলেক্ট/ডিসিলেক্ট করুন)
                        </label>
                        <span class="text-[11px] text-slate-400">একাধিক নির্বাচনযোগ্য</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $areaOptions = [
                                'Aftabnagar'      => 'আফতাবনগর',
                                'Bashundhara R/A' => 'বসুন্ধরা আ/এ',
                                'Purbachal'       => 'পূর্বাচল নতুন শহর',
                                'Banasree'        => 'বনশ্রী',
                                'Badda'           => 'বাড্ডা / মেরুল',
                                'Gulshan'         => 'গুলশান / বারিধারা',
                                'Uttara'          => 'উত্তরা',
                                'Dhanmondi'       => 'ধানমন্ডি'
                            ];
                        @endphp
                        @foreach ($areaOptions as $eng => $bng)
                            <button type="button" 
                                    @click="toggleArea('{{ $eng }}')"
                                    :class="preferredAreas.includes('{{ $eng }}') 
                                        ? 'bg-emerald-600 text-white border-emerald-600 font-extrabold shadow-xs' 
                                        : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 font-semibold'"
                                    class="px-3.5 py-2 rounded-xl border text-xs transition cursor-pointer flex items-center gap-1.5">
                                <span x-show="preferredAreas.includes('{{ $eng }}')">✓</span>
                                <span>{{ $bng }}</span>
                            </button>
                        @endforeach
                    </div>
                    <!-- Hidden inputs for preferred_areas -->
                    <template x-for="area in preferredAreas" :key="area">
                        <input type="hidden" name="preferred_areas[]" :value="area">
                    </template>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block font-bold text-slate-800 uppercase">
                            পছন্দের নির্দিষ্ট ব্লক (ঐচ্ছিক - ক্লিক করে সিলেক্ট/ডিসিলেক্ট করুন)
                        </label>
                        <span x-show="preferredBlocks.length > 0" class="text-[11px] text-emerald-600 font-bold" x-text="preferredBlocks.length + ' টি ব্লক সিলেক্টেড'"></span>
                    </div>
                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                        @foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'P', 'Q'] as $b)
                            <button type="button" 
                                    @click="toggleBlock('{{ $b }}')"
                                    :class="preferredBlocks.includes('{{ $b }}') 
                                        ? 'bg-emerald-600 text-white border-emerald-600 font-extrabold shadow-xs' 
                                        : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100 font-medium'"
                                    class="p-2 text-center rounded-xl border text-xs transition cursor-pointer">
                                ব্লক {{ $b }}
                            </button>
                        @endforeach
                    </div>
                    <!-- Hidden inputs for preferred_blocks -->
                    <template x-for="blk in preferredBlocks" :key="blk">
                        <input type="hidden" name="preferred_blocks[]" :value="blk">
                    </template>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 4A. PLOT / LAND SPECIFIC CRITERIA SECTION  -->
            <!-- ========================================== -->
            <div x-show="propertyType === 'land'" class="pt-4 border-t-2 border-emerald-100 space-y-5 bg-emerald-50/30 p-4 sm:p-6 rounded-2xl">
                <div class="flex items-center gap-2 text-emerald-900 font-black text-sm">
                    <span>🏞️</span>
                    <span>জমি ও প্লটের সাধারণ ক্রাইটেরিয়া (Plot Specifications)</span>
                </div>

                <!-- Katha Size Presets -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block font-bold text-slate-700 uppercase">
                            কাঙ্ক্ষিত প্লটের সাইজ (কাঠা - ক্লিক করে সিলেক্ট/ডিসিলেক্ট)
                        </label>
                    </div>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                        <template x-for="sz in ['2.5', '3', '4', '5', '7.5', '10']" :key="sz">
                            <button type="button" 
                                    @click="setKathaChoice(sz)"
                                    :class="kathaSizeChoice === sz 
                                        ? 'bg-emerald-700 text-white border-emerald-700 font-black shadow-xs' 
                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-semibold'"
                                    class="py-2.5 px-3 rounded-xl border text-center transition cursor-pointer">
                                <span x-text="sz + ' কাঠা'"></span>
                            </button>
                        </template>
                    </div>
                    <div class="mt-2.5 flex items-center gap-3">
                        <button type="button" 
                                @click="setKathaChoice('custom')"
                                :class="kathaSizeChoice === 'custom' ? 'text-emerald-700 font-bold underline' : 'text-slate-500 font-medium'"
                                class="text-xs cursor-pointer">
                            ✏️ কাস্টম কাঠা রেঞ্জ দিন
                        </button>
                        <div x-show="kathaSizeChoice === 'custom'" class="flex items-center gap-2">
                            <input type="number" step="0.1" x-model="minSize" placeholder="মিনিমাম" class="w-20 border rounded-lg px-2.5 py-1.5 bg-white text-xs">
                            <span>থেকে</span>
                            <input type="number" step="0.1" x-model="maxSize" placeholder="ম্যাক্সিমাম" class="w-20 border rounded-lg px-2.5 py-1.5 bg-white text-xs">
                            <span>কাঠা</span>
                        </div>
                    </div>
                    <input type="hidden" name="katha_size_choice" :value="kathaSizeChoice">
                </div>

                <!-- Facing with 'যেকোনো মুখী' Option -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">
                        প্লটের ফেসিং / মুখ (ক্লিক করে সিলেক্ট/ডিসিলেক্ট করুন)
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <button type="button" @click="toggleFacing('Any')"
                                :class="preferredFacings.includes('Any') ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer col-span-2 sm:col-span-1">
                            ✨ যেকোনো মুখী
                        </button>
                        <button type="button" @click="toggleFacing('South')"
                                :class="preferredFacings.includes('South') ? 'bg-emerald-600 text-white border-emerald-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            দক্ষিণমুখী (South)
                        </button>
                        <button type="button" @click="toggleFacing('East')"
                                :class="preferredFacings.includes('East') ? 'bg-emerald-600 text-white border-emerald-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            পূর্বমুখী (East)
                        </button>
                        <button type="button" @click="toggleFacing('North')"
                                :class="preferredFacings.includes('North') ? 'bg-emerald-600 text-white border-emerald-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            উত্তরমুখী (North)
                        </button>
                        <button type="button" @click="toggleFacing('West')"
                                :class="preferredFacings.includes('West') ? 'bg-emerald-600 text-white border-emerald-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            পশ্চিমমুখী (West)
                        </button>
                    </div>
                </div>

                <!-- Road Width Toggleable Chips -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">
                        সম্মুখ রাস্তার ন্যূনতম প্রশস্ততা (ক্লিক করে সিলেক্ট/ডিসিলেক্ট)
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" @click="setRoadWidth('20')"
                                :class="minRoadWidthFt === '20' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 text-center rounded-xl border transition cursor-pointer">
                            ২০ ফিট রাস্তা
                        </button>
                        <button type="button" @click="setRoadWidth('25')"
                                :class="minRoadWidthFt === '25' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 text-center rounded-xl border transition cursor-pointer">
                            ২৫ ফিট রাস্তা
                        </button>
                        <button type="button" @click="setRoadWidth('30')"
                                :class="minRoadWidthFt === '30' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 text-center rounded-xl border transition cursor-pointer">
                            ৩০ ফিট এভিনিউ
                        </button>
                        <button type="button" @click="setRoadWidth('40')"
                                :class="minRoadWidthFt === '40' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 text-center rounded-xl border transition cursor-pointer">
                            ৪০+ ফিট মেইন রোড
                        </button>
                    </div>
                    <input type="hidden" name="min_road_width_ft" :value="minRoadWidthFt">
                </div>

                <!-- Corner Plot & Development Status -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-2">
                            কর্নার প্লট অগ্রাধিকার (ক্লিক করে সিলেক্ট/ডিসিলেক্ট)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <button type="button" @click="setCornerPlot('regular')"
                                    :class="cornerPlot === 'regular' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                                সাধারণ প্লট / যেকোনো
                            </button>
                            <button type="button" @click="setCornerPlot('corner_2_side')"
                                    :class="cornerPlot === 'corner_2_side' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                                ২ দিক খোলা কর্নার প্লট
                            </button>
                            <button type="button" @click="setCornerPlot('corner_3_side')"
                                    :class="cornerPlot === 'corner_3_side' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                                ৩ দিক খোলা কর্নার প্লট
                            </button>
                        </div>
                        <input type="hidden" name="corner_plot" :value="cornerPlot">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-2">
                            প্লটের বর্তমান অবস্থা ও ভরাট (ক্লিক করে সিলেক্ট/ডিসিলেক্ট)
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button type="button" @click="setLandStatus('ready_plot')"
                                    :class="landStatus === 'ready_plot' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                                ১০০% ভরাট ও রেডি প্লট
                            </button>
                            <button type="button" @click="setLandStatus('boundary_done')"
                                    :class="landStatus === 'boundary_done' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                                বাউন্ডারি করা প্লট
                            </button>
                            <button type="button" @click="setLandStatus('filling_partial')"
                                    :class="landStatus === 'filling_partial' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                                বালু ভরাট চলমান
                            </button>
                            <button type="button" @click="setLandStatus('lake_view')"
                                    :class="landStatus === 'lake_view' ? 'bg-emerald-700 text-white border-emerald-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                                লেকভিউ / পার্ক সংলগ্ন
                            </button>
                        </div>
                        <input type="hidden" name="land_status" :value="landStatus">
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 4B. FLAT SPECIFIC CRITERIA SECTION         -->
            <!-- ========================================== -->
            <div x-show="propertyType === 'flat'" class="pt-4 border-t-2 border-teal-100 space-y-5 bg-teal-50/30 p-4 sm:p-6 rounded-2xl" style="display: none;">
                <div class="flex items-center gap-2 text-teal-900 font-black text-sm">
                    <span>🏢</span>
                    <span>ফ্ল্যাটের সাধারণ ক্রাইটেরিয়া ও সুবিধা (Flat Specifications)</span>
                </div>

                <!-- Flat Size Presets -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">
                        কাঙ্ক্ষিত ফ্ল্যাটের সাইজ (বর্গফুট - ক্লিক করে সিলেক্ট/ডিসিলেক্ট)
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <button type="button" @click="setFlatPreset('1000_1200')"
                                :class="flatSizePreset === '1000_1200' ? 'bg-teal-700 text-white border-teal-700 font-black shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-semibold'"
                                class="py-2.5 px-3 rounded-xl border text-center transition cursor-pointer">
                            <div>১০০০ - ১২০০ sft</div>
                            <div class="text-[10px] opacity-75">কমপ্যাক্ট ৩/২ বেড</div>
                        </button>
                        <button type="button" @click="setFlatPreset('1201_1400')"
                                :class="flatSizePreset === '1201_1400' ? 'bg-teal-700 text-white border-teal-700 font-black shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-semibold'"
                                class="py-2.5 px-3 rounded-xl border text-center transition cursor-pointer">
                            <div>১২০১ - ১৪০০ sft</div>
                            <div class="text-[10px] opacity-75">স্ট্যান্ডার্ড ৩ বেড</div>
                        </button>
                        <button type="button" @click="setFlatPreset('1401_1600')"
                                :class="flatSizePreset === '1401_1600' ? 'bg-teal-700 text-white border-teal-700 font-black shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-semibold'"
                                class="py-2.5 px-3 rounded-xl border text-center transition cursor-pointer">
                            <div>১৪০১ - ১৬০০ sft</div>
                            <div class="text-[10px] opacity-75">প্রশস্ত ৩ বেড</div>
                        </button>
                        <button type="button" @click="setFlatPreset('1601_2000')"
                                :class="flatSizePreset === '1601_2000' ? 'bg-teal-700 text-white border-teal-700 font-black shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-semibold'"
                                class="py-2.5 px-3 rounded-xl border text-center transition cursor-pointer">
                            <div>১৬০১ - ২০০০ sft</div>
                            <div class="text-[10px] opacity-75">লাক্সারি ৩/৪ বেড</div>
                        </button>
                        <button type="button" @click="setFlatPreset('2000_plus')"
                                :class="flatSizePreset === '2000_plus' ? 'bg-teal-700 text-white border-teal-700 font-black shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-semibold'"
                                class="py-2.5 px-3 rounded-xl border text-center transition cursor-pointer">
                            <div>২০০০+ sft</div>
                            <div class="text-[10px] opacity-75">ডুপ্লেক্স / পেন্টহাউস</div>
                        </button>
                    </div>
                    <input type="hidden" name="flat_size_preset" :value="flatSizePreset">
                </div>

                <!-- Flat Facing with 'যেকোনো মুখী' Option -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">
                        ফ্ল্যাটের ফেসিং / মুখ (ক্লিক করে সিলেক্ট/ডিসিলেক্ট করুন)
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <button type="button" @click="toggleFacing('Any')"
                                :class="preferredFacings.includes('Any') ? 'bg-teal-800 text-white border-teal-800 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer col-span-2 sm:col-span-1">
                            ✨ যেকোনো মুখী
                        </button>
                        <button type="button" @click="toggleFacing('South')"
                                :class="preferredFacings.includes('South') ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            দক্ষিণমুখী (South)
                        </button>
                        <button type="button" @click="toggleFacing('East')"
                                :class="preferredFacings.includes('East') ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            পূর্বমুখী (East)
                        </button>
                        <button type="button" @click="toggleFacing('North')"
                                :class="preferredFacings.includes('North') ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            উত্তরমুখী (North)
                        </button>
                        <button type="button" @click="toggleFacing('West')"
                                :class="preferredFacings.includes('West') ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            পশ্চিমমুখী (West)
                        </button>
                    </div>
                </div>

                <!-- Beds, Baths (Clickable Chips) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-2">বেডরুম সংখ্যা (সিলেক্ট/ডিসিলেক্ট)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="setBedCount('2')"
                                    :class="bedCount === '2' ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2 rounded-xl border text-center transition cursor-pointer">২ বেড</button>
                            <button type="button" @click="setBedCount('3')"
                                    :class="bedCount === '3' ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2 rounded-xl border text-center transition cursor-pointer">৩ বেড</button>
                            <button type="button" @click="setBedCount('4')"
                                    :class="bedCount === '4' ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2 rounded-xl border text-center transition cursor-pointer">৪+ বেড</button>
                        </div>
                        <input type="hidden" name="bed_count" :value="bedCount">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-2">বাথরুম সংখ্যা (সিলেক্ট/ডিসিলেক্ট)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="setBathCount('2')"
                                    :class="bathCount === '2' ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2 rounded-xl border text-center transition cursor-pointer">২ বাথ</button>
                            <button type="button" @click="setBathCount('3')"
                                    :class="bathCount === '3' ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2 rounded-xl border text-center transition cursor-pointer">৩ বাথ</button>
                            <button type="button" @click="setBathCount('4')"
                                    :class="bathCount === '4' ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                    class="p-2 rounded-xl border text-center transition cursor-pointer">৪ বাথ</button>
                        </div>
                        <input type="hidden" name="bath_count" :value="bathCount">
                    </div>
                </div>

                <!-- Floor Level (Clickable Chips) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">পছন্দের তলা / ফ্লোর (সিলেক্ট/ডিসিলেক্ট)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        <button type="button" @click="setFloorPreference('middle')"
                                :class="floorPreference === 'middle' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2 rounded-xl border text-center transition cursor-pointer">
                            মাঝামাঝি (৪র্থ-৬ষ্ঠ)
                        </button>
                        <button type="button" @click="setFloorPreference('lower')"
                                :class="floorPreference === 'lower' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2 rounded-xl border text-center transition cursor-pointer">
                            নিম্ন ফ্লোর (২য়-৩য়)
                        </button>
                        <button type="button" @click="setFloorPreference('upper')"
                                :class="floorPreference === 'upper' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2 rounded-xl border text-center transition cursor-pointer">
                            উচ্চ ফ্লোর (৭ম-৯ম)
                        </button>
                        <button type="button" @click="setFloorPreference('top')"
                                :class="floorPreference === 'top' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2 rounded-xl border text-center transition cursor-pointer">
                            টপ ফ্লোর
                        </button>
                        <button type="button" @click="setFloorPreference('any')"
                                :class="floorPreference === 'any' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2 rounded-xl border text-center transition cursor-pointer">
                            যেকোনো ফ্লোর
                        </button>
                    </div>
                    <input type="hidden" name="floor_preference" :value="floorPreference">
                </div>

                <!-- Handover Status (Clickable Chips) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">ফ্ল্যাটের হ্যান্ডওভার স্ট্যাটাস (সিলেক্ট/ডিসিলেক্ট)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" @click="setHandoverStatus('ready')"
                                :class="handoverStatus === 'ready' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            ১০০% রেডি ফ্ল্যাট
                        </button>
                        <button type="button" @click="setHandoverStatus('upcoming_3_6m')"
                                :class="handoverStatus === 'upcoming_3_6m' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            ৩-৬ মাসে হ্যান্ডওভার
                        </button>
                        <button type="button" @click="setHandoverStatus('under_construction')"
                                :class="handoverStatus === 'under_construction' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            নির্মাণাধীন / কিস্তিতে
                        </button>
                        <button type="button" @click="setHandoverStatus('brand_new')"
                                :class="handoverStatus === 'brand_new' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer">
                            সম্পূর্ণ নতুন / আনইউজড
                        </button>
                    </div>
                    <input type="hidden" name="handover_status" :value="handoverStatus">
                </div>

                <!-- Parking (Toggle Buttons) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">কার পার্কিং প্রয়োজন কি? (সিলেক্ট/ডিসিলেক্ট)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="setParkingNeeded('yes')"
                                :class="parkingNeeded === 'yes' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 text-center rounded-xl border transition cursor-pointer">
                            🚗 ডেডিকেটেড পার্কিং আবশ্যক
                        </button>
                        <button type="button" @click="setParkingNeeded('no')"
                                :class="parkingNeeded === 'no' ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 text-center rounded-xl border transition cursor-pointer">
                            ঐচ্ছিক / পার্কিং ছাড়া চলবে
                        </button>
                    </div>
                    <input type="hidden" name="parking_needed" :value="parkingNeeded">
                </div>

                <!-- Essential Amenities Checklist -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-2">অপরিহার্য নাগরিক সুবিধা (ক্লিক করে সিলেক্ট/ডিসিলেক্ট)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" @click="toggleAmenity('lift')"
                                :class="amenities.includes('lift') ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>🏢 লিফট (Lift)</span>
                        </button>
                        <button type="button" @click="toggleAmenity('generator')"
                                :class="amenities.includes('generator') ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>⚡ জেনারেটর ব্যাকআপ</span>
                        </button>
                        <button type="button" @click="toggleAmenity('gas')"
                                :class="amenities.includes('gas') ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>🔥 তিতাস গ্যাস / সিলিন্ডার</span>
                        </button>
                        <button type="button" @click="toggleAmenity('substation')"
                                :class="amenities.includes('substation') ? 'bg-teal-700 text-white border-teal-700 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer flex items-center justify-center gap-1.5">
                            <span>🔌 নিজস্ব সাবস্টেশন</span>
                        </button>
                    </div>
                    <template x-for="am in amenities" :key="am">
                        <input type="hidden" name="amenities[]" :value="am">
                    </template>
                </div>
            </div>

            <!-- Global Hidden Inputs for Facings & Sizes -->
            <template x-for="f in preferredFacings" :key="f">
                <input type="hidden" name="preferred_facings[]" :value="f">
            </template>
            <input type="hidden" name="min_size" :value="minSize">
            <input type="hidden" name="max_size" :value="maxSize">

            <!-- 5. Budget Range (with Quick Presets) -->
            <div class="border-t border-slate-100 pt-5 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="block font-bold text-slate-800 uppercase">
                        বাজেট পরিসীমা (টাকা)
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="setBudgetPreset('50l_1cr', 5000000, 10000000)"
                                :class="budgetPreset === '50l_1cr' ? 'bg-rose-700 text-white font-bold border-rose-700' : 'bg-rose-50 text-rose-800 hover:bg-rose-100 border-rose-200'"
                                class="px-2.5 py-1 rounded-lg border text-[11px] transition cursor-pointer">
                            ৫০ লাখ - ১ কোটি
                        </button>
                        <button type="button" @click="setBudgetPreset('1cr_2cr', 10000000, 20000000)"
                                :class="budgetPreset === '1cr_2cr' ? 'bg-rose-700 text-white font-bold border-rose-700' : 'bg-rose-50 text-rose-800 hover:bg-rose-100 border-rose-200'"
                                class="px-2.5 py-1 rounded-lg border text-[11px] transition cursor-pointer">
                            ১ কোটি - ২ কোটি
                        </button>
                        <button type="button" @click="setBudgetPreset('2cr_4cr', 20000000, 40000000)"
                                :class="budgetPreset === '2cr_4cr' ? 'bg-rose-700 text-white font-bold border-rose-700' : 'bg-rose-50 text-rose-800 hover:bg-rose-100 border-rose-200'"
                                class="px-2.5 py-1 rounded-lg border text-[11px] transition cursor-pointer">
                            ২ কোটি - ৪ কোটি
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <input type="number" step="any" name="min_budget" x-model="minBudget" placeholder="যেমন: ৫০,০০,০০০ (সর্বনিম্ন বাজেট)"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 font-medium">
                    </div>
                    <div>
                        <input type="number" step="any" name="max_budget" x-model="maxBudget" placeholder="যেমন: ১,৫০,০০,০০০ (সর্বোচ্চ বাজেট)"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 font-medium">
                    </div>
                </div>
                <input type="hidden" name="budget_preset" :value="budgetPreset">

                <p class="text-[11px] text-slate-500 flex items-center gap-1.5 pt-1">
                    <span>💡</span>
                    <span>আমাদের অটোম্যাচিং ইঞ্জিন আপনার বাজেটের ±১৫% টলারেন্স রেঞ্জ ধরে প্রপার্টি ফিল্টার করে যেন সামান্য কম-বেশির ভালো ডিল মিস না হয়।</span>
                </p>
            </div>

            <!-- 6. Notification Channels -->
            <div class="border-t border-slate-100 pt-5 space-y-3">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                    কোন মাধ্যমে ম্যাচিং নোটিফিকেশন পেতে চান?
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Web Push -->
                    <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="notify_channels[]" value="push" x-model="channels" class="mt-1 rounded text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 block">🔔 ওয়েব পুশ (Web Push)</span>
                            <span class="text-slate-500 text-[11px]">ব্রাউজার বা মোবাইল স্ক্রিনে তাৎক্ষণিক অ্যালার্ট</span>
                        </div>
                    </label>

                    <!-- Email -->
                    <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="notify_channels[]" value="email" x-model="channels" class="mt-1 rounded text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 block">📧 ইমেইল অ্যালার্ট</span>
                            <span class="text-slate-500 text-[11px]">ম্যাচ হওয়া প্রপার্টির বিস্তারিত ছবিসহ ইমেইল</span>
                        </div>
                    </label>

                    <!-- Telegram -->
                    <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="notify_channels[]" value="telegram" x-model="channels" class="mt-1 rounded text-emerald-600 focus:ring-emerald-500">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 block">🤖 টেলিগ্রাম বট (Telegram)</span>
                            <span class="text-slate-500 text-[11px]">বট চ্যাটে দ্রুত ইন্টারেক্টিভ বাটনে অ্যালার্ট</span>
                        </div>
                    </label>
                </div>

                <!-- Telegram Connect Info & Action -->
                <div x-show="hasTelegramChannel()" class="p-4 sm:p-5 rounded-2xl bg-sky-50/90 border border-sky-200 space-y-3 mt-3 shadow-xs" style="display: none;">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="space-y-1">
                            <span class="text-xs font-black text-sky-950 flex items-center gap-1.5">
                                <span>✈️</span> আপনার টেলিগ্রাম ইউজারনেম বা চ্যাট আইডি দিন
                            </span>
                            <p class="text-[11px] text-sky-800 leading-relaxed m-0">
                                আপনার Telegram Username (যেমন: <code>@your_username</code>) বা Chat ID লিখুন। অথবা ফর্ম সাবমিট করার পর পরবর্তী পেজে ১-ক্লিকেই সংযোগ করতে পারবেন।
                            </p>
                        </div>
                        <a href="https://t.me/{{ $botUsername }}?start=chahida" target="_blank" rel="noopener"
                           style="background-color: #0284c7 !important; background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%) !important; color: #ffffff !important;"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold shadow hover:opacity-95 transition shrink-0 cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" style="fill: #ffffff !important;" viewBox="0 0 24 24">
                                <path fill="#ffffff" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            <span style="color: #ffffff !important;">বট ওপেন করুন{{ !empty($botUsername) ? ' (@' . $botUsername . ')' : '' }}</span>
                        </a>
                    </div>
                    <div>
                        <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id') }}"
                               placeholder="যেমন: @your_username অথবা 6891457989 (ঐচ্ছিক)"
                               class="w-full px-3.5 py-2 rounded-xl border border-sky-300 bg-white text-xs focus:ring-2 focus:ring-sky-500 font-mono">
                    </div>
                </div>
            </div>

            <!-- 7. Additional Notes -->
            <div class="border-t border-slate-100 pt-5 space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">বিশেষ কোনো চাহিদা বা নির্দেশনা (ঐচ্ছিক)</label>
                <textarea name="notes" rows="2" placeholder="যেমন: দক্ষিণমুখী কর্নার প্লট হলে অগ্রাধিকার, বা জরুরি রেজিস্ট্রেশন সুবিধা প্রয়োজন।"
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500">{{ old('notes') }}</textarea>
            </div>

            <!-- 8. Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-400 m-0">
                    🔒 সম্পূর্ণ গোপনীয়তা বজায় রাখা হবে। আপনি যেকোনো সময় এক ক্লিকে নোটিফিকেশন আনসাবস্ক্রাইব করতে পারবেন।
                </p>

                <button type="submit"
                        class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-lg transition-all hover:scale-105 cursor-pointer">
                    🚀 আপনার চাহিদা সাবমিট করুন
                </button>
            </div>
        </form>
    </div>

    <!-- AEO & Buyer Demand Guide FAQ Section -->
    @if(!empty($seo['faqs']) && count($seo['faqs']) > 0)
        <section class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xl space-y-5" aria-labelledby="chahida-faq-heading">
            <div class="border-b border-slate-100 pb-4">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                    🤖 AEO Buyer Demand Guide & FAQs
                </span>
                <h2 id="chahida-faq-heading" class="text-xl sm:text-2xl font-black text-slate-900 mt-2">
                    প্রপার্টি চাহিদা ও অটো-ম্যাচিং সংক্রান্ত সাধারণ জিজ্ঞাসা (FAQ)
                </h2>
                <p class="text-xs text-slate-600 mt-1">
                    কীভাবে চাহিদা সাবমিট করবেন, ম্যাচিং অ্যালার্ট পাবেন এবং নিরাপদভাবে লেনদেন করবেন তার নির্দেশিকা।
                </p>
            </div>

            <div class="space-y-3" x-data="{ chahidaFaq: 0 }">
                @foreach($seo['faqs'] as $idx => $faq)
                    <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all"
                         :class="chahidaFaq === {{ $idx }} ? 'bg-emerald-50/30 border-emerald-300' : 'bg-slate-50/60'">
                        <button type="button"
                                @click="chahidaFaq = chahidaFaq === {{ $idx }} ? null : {{ $idx }}"
                                class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-extrabold text-sm text-slate-900 cursor-pointer">
                            <span class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs flex items-center justify-center shrink-0">?</span>
                                <span>{{ $faq['question'] ?? ($faq['q'] ?? '') }}</span>
                            </span>
                            <span class="text-slate-400 font-bold text-lg transition-transform"
                                  :class="chahidaFaq === {{ $idx }} ? 'rotate-180 text-emerald-600' : ''">⌄</span>
                        </button>
                        <div x-show="chahidaFaq === {{ $idx }}" x-cloak class="px-5 pb-4 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                            {{ $faq['answer'] ?? ($faq['a'] ?? '') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
