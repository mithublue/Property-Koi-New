@php
    $vis = is_array($property->address_visibility) ? $property->address_visibility : (json_decode($property->address_visibility, true) ?: []);
    $visBlock = isset($vis['block']) ? filter_var($vis['block'], FILTER_VALIDATE_BOOLEAN) : true;
    $visRoad = isset($vis['road_number']) ? filter_var($vis['road_number'], FILTER_VALIDATE_BOOLEAN) : true;
    $visPlot = isset($vis['plot_number']) ? filter_var($vis['plot_number'], FILTER_VALIDATE_BOOLEAN) : true;
    $visRoadWidth = isset($vis['road_width_ft']) ? filter_var($vis['road_width_ft'], FILTER_VALIDATE_BOOLEAN) : true;

    $primarySource = $property->directSources?->firstWhere('is_primary_source', true) ?? $property->directSources?->first();
    $initialOwnerName = old('owner_name', $primarySource?->owner_name ?? ($property->creator?->name ?? ''));
    $initialOwnerPhone = old('owner_phone', $primarySource?->owner_phone ?? ($property->creator?->whatsapp_number ?? ($property->creator?->phone ?? '')));
@endphp

<div class="space-y-5 text-xs text-slate-700"
     x-data="{
         activeEditTab: 'basic',
         saving: false,
         saveSuccess: false,
         saveError: '',
         address_visibility: {
             block: @json((bool)$visBlock),
             road_number: @json((bool)$visRoad),
             plot_number: @json((bool)$visPlot),
             road_width_ft: @json((bool)$visRoadWidth)
         },
         async submitDrawerEdit(e) {
             e.preventDefault();
             this.saving = true;
             this.saveSuccess = false;
             this.saveError = '';
             const form = e.target;
             const fd = new FormData(form);

             try {
                 const res = await fetch('{{ route('admin.properties.update', $property) }}', {
                     method: 'POST',
                     headers: {
                         'X-Requested-With': 'XMLHttpRequest',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}'
                     },
                     body: fd
                 });

                 const data = await res.json();
                 this.saving = false;

                 if (res.ok && data.success) {
                     this.saveSuccess = true;
                     // Trigger page reload or live DOM update so single page updates immediately
                     setTimeout(() => {
                         window.location.reload();
                     }, 600);
                 } else {
                     let errMsg = data.message || 'আপডেট ব্যর্থ হয়েছে। অনুগ্রহ করে ফিল্ডগুলো সঠিকভাবে পূরণ করুন।';
                     if (data.errors && typeof data.errors === 'object') {
                         const detailed = Object.values(data.errors).flat().join(' • ');
                         if (detailed) errMsg += ': ' + detailed;
                     }
                     this.saveError = errMsg;
                 }
             } catch (err) {
                 this.saving = false;
                 this.saveError = 'সার্ভার যোগাযোগে সমস্যা হয়েছে: ' + err.message;
             }
         }
     }">

    <!-- Notification Alerts -->
    <div x-show="saveSuccess" x-cloak class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-800 font-bold flex items-center gap-2">
        <span>✅</span>
        <span>প্রপার্টি সফলভাবে আপডেট হয়েছে! পেজ রিলোড হচ্ছে...</span>
    </div>

    <div x-show="saveError" x-cloak class="p-3 bg-rose-50 border border-rose-300 rounded-xl text-rose-800 font-bold flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span>❌</span>
            <span x-text="saveError"></span>
        </div>
        <button type="button" @click="saveError=''" class="text-rose-500 hover:text-rose-700 text-lg leading-none cursor-pointer">&times;</button>
    </div>

    <!-- Navigation Sub-tabs -->
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2">
        <button type="button"
                @click="activeEditTab = 'basic'"
                :class="activeEditTab === 'basic' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer">
            1. মৌলিক ও প্রাইস
        </button>
        <button type="button"
                @click="activeEditTab = 'specs'"
                :class="activeEditTab === 'specs' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer">
            2. স্পেসিফিকেশন
        </button>
        <button type="button"
                @click="activeEditTab = 'sources'"
                :class="activeEditTab === 'sources' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer">
            3. মালিক/এজেন্ট ও হিডেন নোট
        </button>
    </div>

    <form @submit="submitDrawerEdit($event)" class="space-y-4">
        @csrf
        <input type="hidden" name="_method" value="PUT">
        <input type="hidden" name="active_tab" :value="activeEditTab">

        <!-- TAB 1: BASIC INFORMATION & LOCATION -->
        <div x-show="activeEditTab === 'basic'" class="space-y-3.5">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">লিস্টিং শিরোনাম *</label>
                <input type="text" name="title" value="{{ $property->title }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">প্রপার্টি ধরন *</label>
                    <select name="type" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold bg-white focus:ring-2 focus:ring-emerald-500">
                        <option value="land" {{ $property->type === 'land' ? 'selected' : '' }}>🏞️ জমি / প্লট (Land)</option>
                        <option value="flat" {{ $property->type === 'flat' ? 'selected' : '' }}>🏢 রেডি ফ্ল্যাট (Flat)</option>
                        <option value="commercial" {{ $property->type === 'commercial' ? 'selected' : '' }}>🏬 কমার্শিয়াল (Commercial)</option>
                        <option value="land_share" {{ $property->type === 'land_share' ? 'selected' : '' }}>🤝 ল্যান্ড শেয়ার (Share)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">স্ট্যাটাস *</label>
                    <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-black bg-white">
                        <option value="live" {{ $property->status === 'live' ? 'selected' : '' }}>🟢 Live (পাবলিক)</option>
                        <option value="draft" {{ $property->status === 'draft' ? 'selected' : '' }}>⚪ Draft (ভল্ট/ড্রাফট)</option>
                        <option value="pending" {{ $property->status === 'pending' || $property->status === 'pending_verification' ? 'selected' : '' }}>🟡 Pending (পেন্ডিং)</option>
                        <option value="sold" {{ $property->status === 'sold' ? 'selected' : '' }}>⚫ Sold (বিক্রিত)</option>
                        <option value="booked" {{ $property->status === 'booked' ? 'selected' : '' }}>🔵 Booked (বুকড)</option>
                        <option value="archived" {{ $property->status === 'archived' ? 'selected' : '' }}>🔴 Archived (আর্কাইভ)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">এরিয়া</label>
                    <input type="text" name="area" value="{{ $property->area }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">ব্লক</label>
                        <button type="button" @click="address_visibility.block = !address_visibility.block"
                                class="px-1.5 py-0.5 rounded text-[10px] font-bold border cursor-pointer"
                                :class="address_visibility.block ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-300'">
                            <span x-text="address_visibility.block ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[block]" :value="address_visibility.block ? 1 : 0">
                    <input type="text" name="block" value="{{ $property->block }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">রোড নম্বর</label>
                        <button type="button" @click="address_visibility.road_number = !address_visibility.road_number"
                                class="px-1.5 py-0.5 rounded text-[10px] font-bold border cursor-pointer"
                                :class="address_visibility.road_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-300'">
                            <span x-text="address_visibility.road_number ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[road_number]" :value="address_visibility.road_number ? 1 : 0">
                    <input type="text" name="road_number" value="{{ $property->road_number }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">প্লট নম্বর</label>
                        <button type="button" @click="address_visibility.plot_number = !address_visibility.plot_number"
                                class="px-1.5 py-0.5 rounded text-[10px] font-bold border cursor-pointer"
                                :class="address_visibility.plot_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-300'">
                            <span x-text="address_visibility.plot_number ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[plot_number]" :value="address_visibility.plot_number ? 1 : 0">
                    <input type="text" name="plot_number" value="{{ $property->plot_number }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">মোট মূল্য (Asking Price ৳) *</label>
                    <input type="number" name="asking_price" value="{{ (int)$property->asking_price }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-black text-emerald-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">প্রতি কাঠা / বর্গফুট মূল্য (৳)</label>
                    <input type="number" name="price_per_katha" value="{{ (int)$property->price_per_katha }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">সাইজ (কাঠা/স্কয়ারফুট)</label>
                    <input type="number" step="0.01" name="size" value="{{ $property->size }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফেসিং</label>
                    <select name="facing" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white">
                        <option value="">ফেসিং নির্বাচন</option>
                        <option value="south" {{ $property->facing === 'south' ? 'selected' : '' }}>দক্ষিণমুখী</option>
                        <option value="north" {{ $property->facing === 'north' ? 'selected' : '' }}>উত্তরমুখী</option>
                        <option value="east" {{ $property->facing === 'east' ? 'selected' : '' }}>পূর্বমুখী</option>
                        <option value="west" {{ $property->facing === 'west' ? 'selected' : '' }}>পশ্চিমমুখী</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-6 pt-1">
                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                    <input type="checkbox" name="is_negotiable" value="1" {{ $property->is_negotiable ? 'checked' : '' }} class="rounded text-emerald-600">
                    <span>আলোচনা সাপেক্ষ</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer font-bold text-amber-700">
                    <input type="checkbox" name="is_featured" value="1" {{ $property->is_featured ? 'checked' : '' }} class="rounded text-amber-600">
                    <span>ফিচার্ড প্রপার্টি</span>
                </label>
            </div>
        </div>

        <!-- TAB 2: DETAILED SPECS -->
        <div x-show="activeEditTab === 'specs'" x-cloak class="space-y-3.5">
            @if($property->type === 'land')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 m-0">রাস্তার মাপ (ফুট)</label>
                            <button type="button" @click="address_visibility.road_width_ft = !address_visibility.road_width_ft"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold border cursor-pointer"
                                    :class="address_visibility.road_width_ft ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-300'">
                                <span x-text="address_visibility.road_width_ft ? '🔒 হাইড' : '👁️ শো'"></span>
                            </button>
                        </div>
                        <input type="hidden" name="address_visibility[road_width_ft]" :value="address_visibility.road_width_ft ? 1 : 0">
                        <input type="number" name="road_width_ft" value="{{ $property->road_width_ft ?? 20 }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ক্যাটাগরি</label>
                        <select name="plot_category" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white">
                            <option value="regular" {{ !$property->landDetail?->is_corner && !$property->landDetail?->is_lake_facing ? 'selected' : '' }}>রেগুলার প্লট</option>
                            <option value="corner" {{ $property->landDetail?->is_corner ? 'selected' : '' }}>কর্নার প্লট</option>
                            <option value="lake_view" {{ $property->landDetail?->is_lake_facing ? 'selected' : '' }}>লেক ভিউ প্লট</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-4 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer font-semibold text-slate-700">
                        <input type="checkbox" name="boundary_wall" value="1" {{ $property->landDetail?->boundary_wall_done ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>বাউন্ডারি ওয়াল আছে</span>
                    </label>
                </div>
            @elseif($property->type === 'flat')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ফ্ল্যাট সাইজ (Sq.Ft)</label>
                        <input type="number" name="flat_size_sqft" value="{{ $property->flatDetail?->flat_size_sqft }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ফ্লোর নম্বর</label>
                        <input type="text" name="floor_no" value="{{ $property->flatDetail?->floor_number }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">বেডরুম</label>
                        <input type="number" name="bedrooms" value="{{ $property->flatDetail?->bedrooms ?? 3 }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">বাথরুম</label>
                        <input type="number" name="bathrooms" value="{{ $property->flatDetail?->bathrooms ?? 3 }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1 font-semibold text-[11px]">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="lift" value="1" {{ $property->flatDetail?->has_lift ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>লিফট (Lift)</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="parking" value="1" {{ $property->flatDetail?->parking_available ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>পার্কিং (Parking)</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="generator" value="1" {{ $property->flatDetail?->has_generator ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>জেনারেটর</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="has_gas" value="1" {{ $property->flatDetail?->has_gas ? 'checked' : '' }} class="rounded text-emerald-600">
                        <span>গ্যাস সংযোগ</span>
                    </label>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">বিস্তারিত বিবরণ (Description)</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">{{ $property->description }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">ফেসবুক ও সোশ্যাল ক্যাপশন</label>
                <textarea name="fb_caption" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono">{{ $property->fb_caption }}</textarea>
            </div>
        </div>

        <!-- TAB 3: OWNER, AGENT & HIDDEN NOTE -->
        <div x-show="activeEditTab === 'sources'" x-cloak class="space-y-3.5">
            <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-3">
                <div class="flex items-center gap-2">
                    <span class="text-base">👤</span>
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wide">মালিকের সরাসরি যোগাযোগ তথ্য</h4>
                </div>

                <div class="space-y-2.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">মালিকের নাম</label>
                        <input type="text" name="owner_name" value="{{ $initialOwnerName }}" placeholder="প্রপার্টি মালিকের নাম" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-bold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">মালিকের ফোন নম্বর / WhatsApp *</label>
                        <input type="text" name="owner_phone" value="{{ $initialOwnerPhone }}" placeholder="যেমন: 01711223344" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-mono font-bold text-emerald-700">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">সোর্স সম্পর্ক / ধরণ</label>
                        <select name="source_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-semibold">
                            <option value="direct_owner" {{ ($primarySource?->source_type === 'direct_owner' || $primarySource?->source_type === 'owner') ? 'selected' : '' }}>সরাসরি প্রপার্টির মালিক (Direct Owner)</option>
                            <option value="direct_agent" {{ ($primarySource?->source_type === 'direct_agent' || $primarySource?->source_type === 'agent') ? 'selected' : '' }}>এজেন্ট / মিডিয়া (Agent)</option>
                            <option value="developer" {{ $primarySource?->source_type === 'developer' ? 'selected' : '' }}>ডেভেলপার (Developer)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Inserter / Assigned Agent -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">দায়িত্বপ্রাপ্ত এজেন্ট / ইনসার্টার</label>
                <select name="created_by_user_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white">
                    <option value="">-- অনির্ধারিত (Unassigned) --</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ $property->created_by_user_id == $agent->id ? 'selected' : '' }}>{{ $agent->name }} ({{ $agent->role }})</option>
                    @endforeach
                </select>
            </div>

            <!-- 🔒 Hidden Note (Admin Exclusive) -->
            <div class="p-3.5 bg-amber-50/70 border-2 border-amber-300 rounded-2xl space-y-2">
                <label class="block text-xs font-black text-amber-950 flex items-center gap-1.5">
                    <span>🔒</span> গোপন নোট (Hidden Note - শুধুমাত্র অ্যাডমিনের জন্য)
                </label>
                <textarea name="hidden_note" rows="3" placeholder="মালিকের একান্ত তথ্য, প্লটের ভেতরের হিসাব বা গোপন তথ্য..." class="w-full px-3 py-2 border border-amber-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">{{ $property->hidden_note }}</textarea>
                <span class="text-[10px] text-amber-800">এই তথ্যটি সাধারণ ইউজাররা কখনই দেখতে পাবে না।</span>
            </div>
        </div>

        <!-- Drawer Submit Buttons -->
        <div class="pt-3 border-t border-slate-200 flex items-center justify-between gap-3">
            <span class="text-[11px] text-slate-400">#{{ $property->property_code }}</span>
            <button type="submit"
                    :disabled="saving"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                <span x-show="!saving">💾 সেভ ও আপডেট করুন</span>
                <span x-show="saving">সংরক্ষণ হচ্ছে...</span>
            </button>
        </div>
    </form>
</div>
