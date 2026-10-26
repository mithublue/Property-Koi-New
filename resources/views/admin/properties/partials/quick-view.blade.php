@php
    $p = $property;
    $photos = $p->media->map(function($m) {
        if (!empty($m->file_path)) {
            return \Illuminate\Support\Str::startsWith($m->file_path, ['http://', 'https://'])
                ? $m->file_path
                : asset($m->file_path);
        }
        return null;
    })->filter()->values()->all();

    if (empty($photos)) {
        $photos = [$p->cover_url];
    }

    $primarySource = $p->directSources->first();
    $ownerName = $primarySource?->owner_name ?: ($p->creator?->name ?: 'প্রপার্টি মালিক');
    $ownerPhone = $primarySource?->owner_phone ?: ($p->creator?->phone ?: '');
    $cleanWa = preg_replace('/[^0-9]/', '', (string)($primarySource?->owner_phone ?: ($p->creator?->whatsapp_number ?: $p->creator?->phone)));
    if ($cleanWa && str_starts_with($cleanWa, '0')) {
        $cleanWa = '88' . $cleanWa;
    }

    $assignedAgent = ($p->creator && in_array($p->creator->role, ['agent', 'owner']))
        ? $p->creator
        : ($primarySource?->sourceUser && in_array($primarySource->sourceUser->role, ['agent', 'owner']) ? $primarySource->sourceUser : null);

    $land = $p->landDetail ?: $p->land;
    $flat = $p->flatDetail ?: $p->flat;
    $comm = $p->commercialDetail ?: $p->commercial;
    $share = $p->shareDetail ?: $p->share;
@endphp

<div x-data="{ activeQuickPhoto: '{{ $photos[0] ?? '' }}' }" class="space-y-6 text-xs text-slate-700">

    <!-- Top Action & Status Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2.5 py-1 rounded-lg font-mono font-black text-xs bg-slate-900 text-amber-400">
                #{{ $p->property_code }}
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold text-xs
                @if($p->status === 'live') bg-emerald-100 text-emerald-800 border border-emerald-300
                @elseif($p->status === 'draft') bg-amber-100 text-amber-800 border border-amber-300
                @elseif($p->status === 'sold') bg-slate-800 text-white
                @elseif($p->status === 'booked') bg-blue-100 text-blue-800 border border-blue-300
                @else bg-rose-100 text-rose-800 border border-rose-300 @endif">
                {{ strtoupper($p->status) }}
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold text-xs bg-white text-slate-700 border border-slate-200">
                {{ $p->type_label }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('property.edit', $p) }}" target="_blank"
               class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <span>✏️</span> এডিট করুন ↗
            </a>
            @if($p->status === 'live')
                <a href="{{ route('property.show', $p->slug ?: $p->property_code) }}" target="_blank"
                   class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <span>🌐</span> পাবলিক পেজ ↗
                </a>
            @else
                <form action="{{ route('admin.properties.publish-live', $p) }}" method="POST" class="inline" onsubmit="return confirm('প্রপার্টিটি কি এখনই পাবলিক সাইটে লাইভ লিস্টিং করতে চান?');">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <span>🚀</span> পাবলিশ লাইভ
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Image Gallery Section -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="relative aspect-16/10 bg-slate-900 overflow-hidden group">
            <img :src="activeQuickPhoto" src="{{ $photos[0] ?? '' }}" alt="{{ $p->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
            <div class="absolute bottom-3 left-3 bg-slate-900/80 backdrop-blur-xs text-white text-[11px] font-bold px-2.5 py-1 rounded-lg">
                📸 মোট ছবি: {{ count($photos) }} টি
            </div>
        </div>

        @if(count($photos) > 1)
            <div class="p-3 bg-slate-50 border-t border-slate-200 flex items-center gap-2 overflow-x-auto no-scrollbar">
                @foreach($photos as $idx => $photo)
                    <button type="button" @click="activeQuickPhoto = '{{ $photo }}'"
                            :class="activeQuickPhoto === '{{ $photo }}' ? 'ring-2 ring-indigo-500 scale-95 opacity-100' : 'opacity-70 hover:opacity-100'"
                            class="w-16 h-12 rounded-lg overflow-hidden shrink-0 border border-slate-300 transition-all cursor-pointer">
                        <img src="{{ $photo }}" alt="" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Title, Area & Price Header -->
    <div class="space-y-2">
        <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <span>📍 {{ $p->area ?: 'আফতাবনগর' }}</span>
            @if($p->block) <span>• ব্লক {{ $p->block }}</span> @endif
            @if($p->sector) <span>• সেক্টর {{ $p->sector }}</span> @endif
            @if($p->road_number) <span>• রোড #{{ $p->road_number }}</span> @endif
            @if($p->plot_number) <span>• প্লট #{{ $p->plot_number }}</span> @endif
        </div>

        <h2 class="text-lg font-black text-slate-900 leading-snug">
            {{ $p->title }}
        </h2>

        <div class="flex flex-wrap items-baseline gap-3 p-4 bg-emerald-50/80 rounded-2xl border border-emerald-200">
            <div class="text-2xl font-black text-emerald-800 font-mono">
                {{ $p->formatted_price }}
            </div>
            @if($p->price_per_unit)
                <div class="text-xs font-bold text-emerald-700 bg-white px-2 py-0.5 rounded-md border border-emerald-200">
                    {{ $p->price_per_unit }}
                </div>
            @endif
            @if($p->is_negotiable)
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md">
                    ✓ দাম আলোচনা সাপেক্ষ
                </span>
            @endif
        </div>
    </div>

    <!-- Contact & Sourcing Info (Crucial for Admin Vault) -->
    <div class="p-4 bg-indigo-50/70 rounded-2xl border border-indigo-200 space-y-3">
        <div class="flex items-center justify-between border-b border-indigo-100 pb-2">
            <span class="font-bold text-indigo-950 text-xs flex items-center gap-1.5">
                <span>🔐</span> প্রাইভেট সোর্স ও যোগাযোগের তথ্য (Vault Exclusive)
            </span>
            <span class="text-[10px] font-semibold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                {{ $primarySource?->source_type === 'direct_agent' ? 'এজেন্ট লিস্টিং' : 'সরাসরি মালিক' }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1 bg-white p-3 rounded-xl border border-indigo-100">
                <span class="text-[10px] text-slate-400 font-bold block uppercase">মালিক / প্রাইমারি সোর্স</span>
                <div class="font-bold text-slate-900 text-sm">{{ $ownerName }}</div>
                @if($ownerPhone)
                    <div class="font-mono text-slate-700 flex items-center gap-1 pt-0.5">
                        <span>📞</span> {{ $ownerPhone }}
                    </div>
                    @if($cleanWa)
                        <div class="pt-1">
                            <a href="https://wa.me/{{ $cleanWa }}?text={{ urlencode('আসসালামু আলাইকুম, PropertyKoi প্রাইভেট ভল্ট থেকে প্রপার্টি #' . $p->property_code . ' (' . $p->title . ') প্রসঙ্গে জানতে যোগাযোগ করছি।') }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                                <span>💬</span> হোয়াটসঅ্যাপ চ্যাট
                            </a>
                        </div>
                    @endif
                @else
                    <span class="text-slate-400 italic">ফোন নম্বর নেই</span>
                @endif
            </div>

            <div class="space-y-1 bg-white p-3 rounded-xl border border-indigo-100">
                <span class="text-[10px] text-slate-400 font-bold block uppercase">দায়িত্বপ্রাপ্ত এজেন্ট / ইনসার্টার</span>
                @if($assignedAgent)
                    <div class="font-bold text-slate-900 text-sm flex items-center gap-1">
                        <span>💼</span> {{ $assignedAgent->name }}
                    </div>
                    <div class="text-[11px] text-slate-500 font-mono">
                        📞 {{ $assignedAgent->whatsapp_number ?: $assignedAgent->phone }}
                    </div>
                    @if($assignedAgent->agentProfile?->agency_name)
                        <div class="text-[11px] text-indigo-600 font-medium">
                            🏢 {{ $assignedAgent->agentProfile->agency_name }}
                        </div>
                    @endif
                @else
                    <div class="text-slate-500">কোনো নির্দিষ্ট এজেন্ট অ্যাসাইন করা নেই</div>
                    <div class="text-[10px] text-slate-400">তৈরি করেছেন: {{ $p->creator?->name ?: 'Admin' }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Complete Technical Specifications Grid -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
        <h3 class="font-bold text-slate-900 text-xs border-b border-slate-100 pb-2 flex items-center gap-1.5">
            <span>📋</span> বিস্তারিত টেকনিক্যাল স্পেসিফিকেশন
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] text-slate-400 block">প্রপার্টির ধরন</span>
                <span class="font-bold text-slate-800">{{ $p->type_label }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] text-slate-400 block">প্রধান এলাকা</span>
                <span class="font-bold text-slate-800">{{ $p->area ?: 'আফতাবনগর' }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] text-slate-400 block">আয়তন (Size)</span>
                <span class="font-bold text-emerald-700">{{ $p->formatted_size ?: '-' }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] text-slate-400 block">সামনের রাস্তা</span>
                <span class="font-bold text-slate-800">{{ $p->road_width_ft ? $p->road_width_ft . ' ফুট' : '-' }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] text-slate-400 block">ফেসিং (Facing)</span>
                <span class="font-bold text-slate-800 capitalize">{{ $p->facing ? $p->facing . ' মুখী' : '-' }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] text-slate-400 block">প্লট নম্বর</span>
                <span class="font-bold text-slate-800">{{ $p->plot_number ?: '-' }}</span>
            </div>

            <!-- LAND SPECIFICS -->
            @if($p->type === 'land' && $land)
                <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                    <span class="text-[10px] text-emerald-600 block">জমির আকার</span>
                    <span class="font-black text-slate-900">{{ $land->katha_size }} কাঠা</span>
                </div>
                @if((float)$land->price_per_katha > 0)
                    <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                        <span class="text-[10px] text-emerald-600 block">কাঠা প্রতি মূল্য</span>
                        <span class="font-black text-emerald-800">৳{{ number_format((float)$land->price_per_katha, 0) }}</span>
                    </div>
                @endif
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">মাটি ভরাট</span>
                    <span class="font-bold text-slate-800">{{ $land->soil_filling_status === 'filled' ? '✅ ভরাটকৃত' : 'অভরাট' }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">কর্নার প্লট?</span>
                    <span class="font-bold {{ $land->is_corner ? 'text-emerald-700' : 'text-slate-600' }}">{{ $land->is_corner ? '✅ হ্যাঁ (Corner)' : 'না' }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">লেক ভিউ</span>
                    <span class="font-bold text-slate-800">{{ $land->is_lake_facing ? '🌊 লেক ভিউ' : 'সাধারণ' }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">বাউন্ডারি দেয়াল</span>
                    <span class="font-bold text-slate-800">{{ $land->boundary_wall_done ? '🧱 সম্পন্ন' : 'না' }}</span>
                </div>
            @endif

            <!-- FLAT SPECIFICS -->
            @if($p->type === 'flat' && $flat)
                <div class="p-2.5 rounded-xl bg-teal-50/60 border border-teal-100">
                    <span class="text-[10px] text-teal-600 block">ফ্ল্যাটের আকার</span>
                    <span class="font-black text-slate-900">{{ (int)$flat->flat_size_sqft }} Sq.Ft</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">বেড ও বাথ</span>
                    <span class="font-bold text-slate-800">🛏️ {{ $flat->bedrooms }} বেড • 🚿 {{ $flat->bathrooms }} বাথ</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">বারান্দা</span>
                    <span class="font-bold text-slate-800">{{ $flat->balconies }} টি</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">ফ্লোর নম্বর</span>
                    <span class="font-bold text-slate-800">{{ $flat->floor_number }} তলা (মোট {{ $flat->total_floors }})</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">অবস্থা</span>
                    <span class="font-bold text-emerald-700">{{ $flat->completion_status === 'ready' ? '✅ রেডি' : '🏗️ নির্মাণাধীন' }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">পার্কিং</span>
                    <span class="font-bold text-slate-800">{{ $flat->parking_available ? '🚗 আছে' : 'নেই' }}</span>
                </div>
            @endif

            <!-- COMMERCIAL SPECIFICS -->
            @if($p->type === 'commercial' && $comm)
                <div class="p-2.5 rounded-xl bg-indigo-50/60 border border-indigo-100">
                    <span class="text-[10px] text-indigo-600 block">কমার্শিয়াল ধরন</span>
                    <span class="font-bold text-slate-900 capitalize">{{ $comm->commercial_type ?: 'Shop' }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-indigo-50/60 border border-indigo-100">
                    <span class="text-[10px] text-indigo-600 block">সাইজ</span>
                    <span class="font-bold text-slate-900">{{ (int)$comm->size_sqft }} Sq.Ft</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">মার্কেটের নাম</span>
                    <span class="font-bold text-slate-800">{{ $comm->market_name ?: '-' }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">দোকান নম্বর</span>
                    <span class="font-bold text-slate-800">{{ $comm->shop_number ?: '-' }}</span>
                </div>
            @endif

            <!-- LAND SHARE SPECIFICS -->
            @if($p->type === 'land_share' && $share)
                <div class="p-2.5 rounded-xl bg-purple-50/60 border border-purple-100">
                    <span class="text-[10px] text-purple-600 block">প্রজেক্টের নাম</span>
                    <span class="font-bold text-slate-900">{{ $share->project_name ?: '-' }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-purple-50/60 border border-purple-100">
                    <span class="text-[10px] text-purple-600 block">শেয়ার মূল্য</span>
                    <span class="font-bold text-slate-900">৳{{ number_format((float)$share->share_price, 0) }}</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] text-slate-400 block">মোট শেয়ার</span>
                    <span class="font-bold text-slate-800">{{ $share->total_shares }} টি (অবশিষ্ট {{ $share->available_shares }})</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Custom Fields / Mouza / Registry Details -->
    @if(!empty($p->custom_fields) && is_array($p->custom_fields) && count($p->custom_fields) > 0)
        <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
            <h3 class="font-bold text-slate-900 text-xs border-b border-slate-100 pb-2 flex items-center gap-1.5">
                <span>🏷️</span> অতিরিক্ত তথ্য ও রেকর্ড (Custom Keys / মৌজা / খতিয়ান)
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                @foreach($p->custom_fields as $cf)
                    @if(!empty($cf['key']) && isset($cf['value']) && trim((string)$cf['value']) !== '')
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] text-slate-400 block font-medium">{{ $cf['key'] }}</span>
                            <span class="font-bold text-slate-800">{{ $cf['value'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Other Features Badges -->
    @php
        $normalizedFeatures = [];
        if (!empty($p->other_features) && is_array($p->other_features)) {
            foreach ($p->other_features as $fKey => $feat) {
                if (is_array($feat)) {
                    if (isset($feat['checked']) && !$feat['checked']) continue;
                    $fText = trim((string) ($feat['label'] ?? ($feat['name'] ?? '')));
                    if ($fText !== '') $normalizedFeatures[] = $fText;
                } elseif (is_bool($feat) && $feat && is_string($fKey)) {
                    $normalizedFeatures[] = $fKey;
                } elseif (is_scalar($feat) && trim((string)$feat) !== '') {
                    $normalizedFeatures[] = trim((string)$feat);
                }
            }
        }
    @endphp
    @if(!empty($normalizedFeatures))
        <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2">
            <h3 class="font-bold text-slate-900 text-xs border-b border-slate-100 pb-2 flex items-center gap-1.5">
                <span>✨</span> অন্যান্য সুবিধাসমূহ (Features)
            </h3>
            <div class="flex flex-wrap gap-2 pt-1">
                @foreach($normalizedFeatures as $featLabel)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold">
                        <span>✓</span> {{ $featLabel }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Description -->
    @if(!empty($p->description))
        <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2">
            <h3 class="font-bold text-slate-900 text-xs border-b border-slate-100 pb-2 flex items-center gap-1.5">
                <span>📝</span> প্রপার্টির বিস্তারিত বিবরণ
            </h3>
            <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line pt-1">
                {{ $p->description }}
            </div>
        </div>
    @endif

    <!-- Facebook Caption (if available) -->
    @if(!empty($p->fb_caption))
        <div class="bg-blue-50/60 rounded-2xl border border-blue-200 p-4 space-y-2">
            <div class="flex items-center justify-between border-b border-blue-100 pb-2">
                <span class="font-bold text-blue-950 text-xs flex items-center gap-1.5">
                    <span>📱</span> ফেসবুক ও সোশ্যাল মিডিয়া ক্যাপশন
                </span>
                <button type="button"
                        @click="navigator.clipboard.writeText(`{{ addslashes($p->fb_caption) }}`).then(() => alert('ফেসবুক ক্যাপশন কপি করা হয়েছে!'))"
                        class="text-[11px] font-bold text-blue-700 hover:text-blue-900 underline cursor-pointer">
                    কপি করুন
                </button>
            </div>
            <div class="text-[11px] text-slate-700 whitespace-pre-line font-mono bg-white p-3 rounded-xl border border-blue-100">
                {{ $p->fb_caption }}
            </div>
        </div>
    @endif

</div>
