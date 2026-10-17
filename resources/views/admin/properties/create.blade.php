@extends('layouts.admin')

@section('title', 'নতুন প্রপার্টি লিস্টিং')
@section('page_title', 'নতুন প্রপার্টি লিস্টিং তৈরি করুন')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="propertyForm()">

    <!-- Form Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 bg-white p-2 rounded-2xl shadow-sm text-xs font-semibold">
        <button type="button" @click="activeTab = 'basic'" :class="activeTab === 'basic' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2.5 rounded-xl transition">
            1. মৌলিক তথ্য ও মূল্য
        </button>
        <button type="button" @click="activeTab = 'specs'" :class="activeTab === 'specs' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2.5 rounded-xl transition">
            2. স্পেসিফিকেশন ও বিবরণ
        </button>
        <button type="button" @click="activeTab = 'source'" :class="activeTab === 'source' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="px-4 py-2.5 rounded-xl transition">
            3. সরাসরি মালিক ও সোর্স
        </button>
        <button type="button" @click="activeTab = 'assistant'" :class="activeTab === 'assistant' ? 'bg-indigo-600 text-white shadow-sm' : 'text-indigo-600 hover:bg-indigo-50 border border-indigo-200'" class="px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 ml-auto">
            <span>🤖</span> স্মার্ট টেক্সট পার্সার
        </button>
    </div>

    <!-- Smart Assistant Tab (Extracts raw WhatsApp / Facebook post) -->
    <div x-show="activeTab === 'assistant'" x-cloak class="bg-indigo-50/60 border border-indigo-200 p-6 rounded-2xl space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-sm font-bold text-indigo-950 flex items-center gap-2">
                    <span>🤖</span> AI স্মার্ট টেক্সট পার্সার (Smart Text Assistant)
                </h4>
                <p class="text-xs text-indigo-700 mt-0.5">হোয়াটসঅ্যাপ মেসেজ বা ফেসবুক পোস্টের বর্ণনা এখানে পেস্ট করুন। সিস্টেম স্বয়ংক্রিয়ভাবে ব্লক, রোড, প্লট, কাঠা ও প্রাইস বের করে দেবে।</p>
            </div>
        </div>
        <textarea x-model="rawText" rows="6" placeholder="এখানে প্রপার্টির বাংলা মেসেজটি পেস্ট করুন... (যেমন: আফতাবনগর সি ব্লক ৫ কাঠা প্লট রোড ৩ মূল্য প্রতি কাঠা ৮৫ লাখ টাকা...)" class="w-full p-4 border border-indigo-300 rounded-xl text-xs bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" @click="parseRawText()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-2">
                <span>⚡</span> পার্স করে ফিল্ডে বসান
            </button>
        </div>
    </div>

    <!-- Main Creation Form -->
    <form action="{{ route('admin.properties.store') }}" method="POST" class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
        @csrf

        <!-- Tab 1: Basic Information -->
        <div x-show="activeTab === 'basic'" class="space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">মৌলিক তথ্য ও লোকেশন</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">লিস্টিং শিরোনাম (Title) *</label>
                    <input type="text" name="title" x-model="form.title" required placeholder="যেমন: আফতাবনগর ব্লক সি দক্ষিণমুখী ৫ কাঠা প্লট বিক্রি" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">প্রপার্টি ধরন (Type) *</label>
                    <select name="type" x-model="form.type" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="land">জমি / প্লট (Land)</option>
                        <option value="flat">রেডি ফ্ল্যাট (Flat)</option>
                        <option value="commercial">কমার্শিয়াল স্পেস (Commercial)</option>
                        <option value="land_share">ল্যান্ড শেয়ার (Share)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">এরিয়া (Area)</label>
                    <input type="text" name="area" x-model="form.area" value="আফতাবনগর" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">ব্লক (Block)</label>
                        <button type="button" 
                                @click="address_visibility.block = !address_visibility.block"
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                :class="address_visibility.block ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                :title="address_visibility.block ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                            <span x-text="address_visibility.block ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[block]" :value="address_visibility.block ? 1 : 0">
                    <input type="text" name="block" x-model="form.block" placeholder="যেমন: C" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">রোড নম্বর (Road)</label>
                        <button type="button" 
                                @click="address_visibility.road_number = !address_visibility.road_number"
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                :class="address_visibility.road_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                :title="address_visibility.road_number ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                            <span x-text="address_visibility.road_number ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[road_number]" :value="address_visibility.road_number ? 1 : 0">
                    <input type="text" name="road_number" x-model="form.road_number" placeholder="যেমন: 3" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">প্লট নম্বর (Plot)</label>
                        <button type="button" 
                                @click="address_visibility.plot_number = !address_visibility.plot_number"
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                :class="address_visibility.plot_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                :title="address_visibility.plot_number ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                            <span x-text="address_visibility.plot_number ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[plot_number]" :value="address_visibility.plot_number ? 1 : 0">
                    <input type="text" name="plot_number" x-model="form.plot_number" placeholder="যেমন: 45" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফেসিং (Facing)</label>
                    <select name="facing" x-model="form.facing" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                        <option value="">নির্বাচন করুন</option>
                        <option value="south">দক্ষিণমুখী (South)</option>
                        <option value="north">উত্তরমুখী (North)</option>
                        <option value="east">পূর্বমুখী (East)</option>
                        <option value="west">পশ্চিমমুখী (West)</option>
                        <option value="south_east">দক্ষিণ-পূর্ব (South-East)</option>
                        <option value="north_east">উত্তর-পূর্ব (North-East)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">সাইজ (কাঠা / স্কয়ারফিট)</label>
                    <input type="number" step="0.01" name="size" x-model="form.size" placeholder="যেমন: 5" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">মোট মূল্য (Asking Price ৳)</label>
                    <input type="number" name="asking_price" x-model="form.asking_price" placeholder="যেমন: 45000000" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs font-bold text-emerald-700">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">প্রতি কাঠা মূল্য (৳)</label>
                    <input type="number" name="price_per_katha" x-model="form.price_per_katha" placeholder="যেমন: 9000000" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ইনসার্টার / মালিক অ্যাসাইন</label>
                    <select name="created_by_user_id" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                        <option value="">-- অনির্ধারিত (Unassigned) --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">স্ট্যাটাস (Status) *</label>
                    <select name="status" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs font-bold">
                        <option value="live">🟢 Live (অনুমোদিত)</option>
                        <option value="pending" selected>🟡 Pending (পেন্ডিং)</option>
                        <option value="sold">⚫ Sold (বিক্রিত)</option>
                        <option value="booked">🔵 Booked (বুকড)</option>
                        <option value="draft">⚪ Draft (ড্রাফট)</option>
                        <option value="archived">🔴 Archived (আর্কাইভ)</option>
                    </select>
                </div>
                <div class="flex items-center gap-4 pt-5">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="is_negotiable" value="1" class="rounded text-emerald-600">
                        <span>আলোচনা সাপেক্ষ</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-amber-700">
                        <input type="checkbox" name="is_featured" value="1" class="rounded text-amber-600">
                        <span>ফিচার্ড প্রপার্টি</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Tab 2: Specs & Details -->
        <div x-show="activeTab === 'specs'" x-cloak class="space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">স্পেসিফিকেশন ও বিবরণ</h3>

            <!-- Land Specs -->
            <div x-show="form.type === 'land'" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 m-0">সামনের রাস্তার প্রশস্ততা (ফিট)</label>
                        <button type="button" 
                                @click="address_visibility.road_width_ft = !address_visibility.road_width_ft"
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                :class="address_visibility.road_width_ft ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                :title="address_visibility.road_width_ft ? 'বর্তমানে সবার জন্য দৃশ্যমান (হাইড করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                            <span x-text="address_visibility.road_width_ft ? '🔒 হাইড' : '👁️ শো'"></span>
                        </button>
                    </div>
                    <input type="hidden" name="address_visibility[road_width_ft]" :value="address_visibility.road_width_ft ? 1 : 0">
                    <input type="number" name="road_width_ft" placeholder="যেমন: 25" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">প্লটের ক্যাটাগরি</label>
                    <select name="plot_category" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                        <option value="regular">রেগুলার প্লট</option>
                        <option value="corner">কর্নার প্লট</option>
                        <option value="lake_view">লেক ভিউ প্লট</option>
                        <option value="main_road">মেইন রোড ফেসিং</option>
                    </select>
                </div>
                <div class="flex items-center pt-5">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="boundary_wall" value="1" class="rounded text-emerald-600">
                        <span>বাউন্ডারি ওয়াল করা আছে</span>
                    </label>
                </div>
            </div>

            <!-- Flat Specs -->
            <div x-show="form.type === 'flat'" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফ্ল্যাট সাইজ (স্কয়ারফিট)</label>
                    <input type="number" name="flat_size_sqft" placeholder="যেমন: 1550" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">বেডরুম</label>
                    <input type="number" name="bedrooms" value="3" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">বাথরুম</label>
                    <input type="number" name="bathrooms" value="3" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফ্লোর নম্বর</label>
                    <input type="text" name="floor_no" placeholder="যেমন: 4th" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <!-- Description & Notes -->
            <div class="space-y-4 pt-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">বিস্তারিত বিবরণ (Description)</label>
                    <textarea name="description" x-model="form.description" rows="4" placeholder="প্রপার্টির সুযোগ-সুবিধা এবং আশেপাশের বিবরণ..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফেসবুক ক্যাপশন ওভাররাইড (Facebook Custom Caption)</label>
                    <textarea name="fb_caption" rows="3" placeholder="ফেসবুকে বিশেষ কোনো ক্যাপশনে পোস্ট করতে চাইলে এখানে লিখুন..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>
            </div>
        </div>

        <!-- Tab 3: Direct Sources & Owner Information -->
        <div x-show="activeTab === 'source'" x-cloak class="space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">সরাসরি মালিক ও সোর্স তথ্য (Direct Owner / Source)</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">সোর্স টাইপ</label>
                    <select name="source_type" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                        <option value="owner">সরাসরি মালিক (Direct Owner)</option>
                        <option value="developer">ডেভেলপার (Developer)</option>
                        <option value="agent">এজেন্ট নেটওয়ার্ক (Agent)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">যোগাযোগকারীর নাম</label>
                    <input type="text" name="source_name" placeholder="যেমন: ইঞ্জিনিয়ার রশীদ সাহেব" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ফোন নম্বর</label>
                    <input type="text" name="source_phone" placeholder="017XXXXXXXX" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">হোয়াটসঅ্যাপ নম্বর (WhatsApp)</label>
                    <input type="text" name="source_whatsapp" placeholder="018XXXXXXXX" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">অভ্যন্তরীণ নোট (Internal Notes)</label>
                    <input type="text" name="source_notes" placeholder="যেমন: কাগজ পত্র ফুল ফ্রেশ, খাজনা পরিশোধিত" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <a href="{{ route('admin.properties.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                &larr; ক্যাটালগে ফিরে যান
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-2">
                <span>💾</span> প্রপার্টি সংরক্ষণ করুন
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
function propertyForm() {
    return {
        activeTab: 'basic',
        rawText: '',
        address_visibility: {
            block: true,
            sector: true,
            road_number: true,
            road_width_ft: true,
            plot_number: true,
            holding_number: true
        },
        form: {
            title: '',
            type: 'land',
            area: 'আফতাবনগর',
            block: '',
            road_number: '',
            plot_number: '',
            facing: '',
            size: '',
            asking_price: '',
            price_per_katha: '',
            description: ''
        },

        parseRawText() {
            if (!this.rawText.trim()) return;
            const text = this.rawText;

            // Block regex
            const blockMatch = text.match(/(?:ব্লক|block)\s*[:\-]?\s*([A-Za-z])/i);
            if (blockMatch) this.form.block = blockMatch[1].toUpperCase();

            // Road regex
            const roadMatch = text.match(/(?:রোড|road)\s*[:\-]?\s*([0-9A-Za-z]+)/i);
            if (roadMatch) this.form.road_number = roadMatch[1];

            // Plot regex
            const plotMatch = text.match(/(?:প্লট|plot)\s*[:\-]?\s*([0-9]+)/i);
            if (plotMatch) this.form.plot_number = plotMatch[1];

            // Size regex
            const sizeMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*(?:কাঠা|katha)/i);
            if (sizeMatch) this.form.size = sizeMatch[1];

            // Price regex (lakh)
            const priceMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*(?:লাখ|lakh|lac)/i);
            if (priceMatch) {
                const totalLakh = parseFloat(priceMatch[1]);
                this.form.asking_price = totalLakh * 100000;
            }

            // Facing
            if (text.includes('দক্ষিণ')) this.form.facing = 'south';
            else if (text.includes('উত্তর')) this.form.facing = 'north';
            else if (text.includes('পূর্ব')) this.form.facing = 'east';
            else if (text.includes('পশ্চিম')) this.form.facing = 'west';

            this.form.description = text;
            this.form.title = `আফতাবনগর ব্লক ${this.form.block || ''} ${this.form.size ? this.form.size + ' কাঠা' : ''} প্লট`;

            this.activeTab = 'basic';
            alert('টেক্সট সফলভাবে পার্স করা হয়েছে! ফর্ম ফিল্ডগুলো চেক করুন।');
        }
    }
}
</script>
@endpush
