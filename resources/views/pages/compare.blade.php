@extends('layouts.app')

@section('title', $seo['title'] ?? 'প্রপার্টি তুলনা ম্যাট্রিক্স - PropertyKoi')

@section('content')
<script>
window.compareManager = function() {
    return {
        properties: @json($formattedProperties) || [],
        loading: false,

        init() {
            var urlParams = new URLSearchParams(window.location.search);
            var urlIds = urlParams.get('ids');

            if (!urlIds) {
                // If no URL IDs given, check localStorage
                var localList = [];
                try {
                    localList = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number).filter(Boolean);
                } catch(e) {}

                if (localList.length > 0) {
                    this.loading = true;
                    fetch('{{ route('compare.data') }}?ids=' + localList.join(','))
                        .then(function(r) { return r.json(); })
                        .then((data) => {
                            if (data.properties && data.properties.length > 0) {
                                this.properties = data.properties;
                            }
                            if (window.history && window.history.replaceState) {
                                window.history.replaceState(null, '', '{{ route('compare.index') }}?ids=' + localList.join(','));
                            }
                        })
                        .catch(function() {})
                        .finally(() => {
                            this.loading = false;
                        });
                }
            } else {
                // Keep localStorage in sync with URL
                var parsed = urlIds.split(',').map(Number).filter(Boolean);
                try {
                    localStorage.setItem('aftabnagar_compare', JSON.stringify(parsed));
                } catch(e) {}
            }
        },

        removeCompare(id) {
            var targetId = Number(id);
            var list = [];
            try {
                list = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number).filter(Boolean);
            } catch(e) {}
            list = list.filter(function(item) { return item !== targetId; });

            try {
                localStorage.setItem('aftabnagar_compare', JSON.stringify(list));
            } catch(e) {}

            this.properties = this.properties.filter(function(p) {
                return Number(p.id) !== targetId;
            });

            // Update URL without page reload
            if (window.history && window.history.replaceState) {
                var newUrl = list.length > 0
                    ? '{{ route('compare.index') }}?ids=' + list.join(',')
                    : '{{ route('compare.index') }}';
                window.history.replaceState(null, '', newUrl);
            }

            window.dispatchEvent(new CustomEvent('compare-updated'));
        },

        clearAll() {
            if (!confirm('আপনি কি নিশ্চিত যে তুলনার পুরো তালিকা খালি করতে চান?')) return;
            try {
                localStorage.removeItem('aftabnagar_compare');
                localStorage.setItem('aftabnagar_compare', '[]');
            } catch(e) {}
            this.properties = [];

            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '{{ route('compare.index') }}');
            }

            window.dispatchEvent(new CustomEvent('compare-updated'));
        }
    };
};

if (window.Alpine) {
    window.Alpine.data('compareManager', window.compareManager);
} else {
    document.addEventListener('alpine:init', function() {
        window.Alpine.data('compareManager', window.compareManager);
    });
}
</script>

<div class="aftabnagar-marketplace-wrapper font-sans text-slate-800 antialiased bg-slate-50 py-10 min-h-screen"
     x-data="compareManager()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div class="bg-slate-900 text-white rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold m-0 flex items-center gap-2.5">
                    <span>⚖️</span>
                    <span>প্রপার্টি তুলনা ম্যাট্রিক্স (Compare Matrix)</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1 m-0">
                    পাশাপাশি একাধিক প্রপার্টির মূল্য, সাইজ, অবস্থান ও সুযোগ-সুবিধা যাচাই করুন (সর্বোচ্চ ৪টি)
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('properties.index') }}"
                   class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold rounded-xl transition border border-slate-700 flex items-center gap-1.5">
                    <span>+ আরও যোগ করুন</span>
                </a>
                <button x-show="!loading && properties.length > 0"
                        @click="clearAll()"
                        type="button"
                        class="px-4 py-2.5 bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-1.5 cursor-pointer">
                    <span>🗑️ তালিকা খালি করুন</span>
                </button>
            </div>
        </div>

        <!-- Loading State Spinner -->
        <div x-show="loading" class="text-center py-16 bg-white rounded-3xl border border-slate-200 shadow-sm">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-emerald-600 border-t-transparent"></div>
            <div class="text-xs text-slate-500 mt-2 font-medium">তুলনার তথ্য লোড হচ্ছে...</div>
        </div>

        <!-- Mobile Horizontal Scroll Hint -->
        <div x-show="!loading && properties.length > 0"
             class="sm:hidden flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200/80 rounded-2xl text-emerald-900 text-xs font-bold shadow-xs">
            <span class="flex items-center gap-2">
                <span class="text-base">👉</span>
                <span>তুলনার সব প্রপার্টি দেখতে ডানে স্ক্রোল করুন</span>
            </span>
            <span class="px-2 py-0.5 bg-emerald-600 text-white text-[10px] rounded-md font-extrabold uppercase">Scroll &rarr;</span>
        </div>

        <!-- Comparison Matrix Table -->
        <div x-show="!loading && properties.length > 0" class="overflow-x-auto pb-4 -mx-4 sm:mx-0 px-4 sm:px-0">
            <div class="inline-block min-w-full align-middle">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
                    <table class="min-w-full divide-y divide-slate-200 text-xs border-collapse">
                        <tbody class="divide-y divide-slate-100 bg-white">

                            <!-- Top Header Row: Images, Title, Remove Button -->
                            <tr>
                                <td class="p-4 bg-slate-900 text-white font-extrabold text-sm w-48 min-w-[190px] sticky left-0 z-20 shadow-md align-top">
                                    <div class="space-y-2 p-3 rounded-2xl bg-slate-800/95 border border-slate-700/80 shadow-inner">
                                        <div class="font-mono text-xs uppercase font-extrabold tracking-wider text-teal-400">
                                            ⚖️ প্রপার্টি তুলনা
                                        </div>
                                        <div class="text-xs font-bold text-slate-100" x-text="properties.length + 'টি প্রপার্টি নির্বাচিত'"></div>
                                        <p class="text-[11px] text-slate-400 leading-snug m-0">প্রতিটি কার্ডের ক্রসে (✕) ক্লিক করে এককভাবে তালিকা থেকে বাদ দিতে পারেন।</p>
                                    </div>
                                </td>

                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-4 w-72 min-w-[280px] max-w-[320px] align-top bg-white border-r border-slate-100">
                                        <div class="space-y-3 relative group">
                                            <!-- Individual Remove Button -->
                                            <button @click="removeCompare(p.id)"
                                                    type="button"
                                                    class="absolute top-2.5 right-2.5 z-20 w-8 h-8 rounded-full bg-slate-900/85 hover:bg-rose-600 text-white flex items-center justify-center text-xs font-black transition-all shadow-md hover:scale-110 cursor-pointer"
                                                    title="এই প্রপার্টি তুলনা তালিকা থেকে সরান">
                                                ✕
                                            </button>

                                            <!-- Thumbnail Image -->
                                            <div class="aspect-16/10 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 relative group-hover:shadow-md transition">
                                                <a :href="p.detail_url" class="block w-full h-full">
                                                    <img :src="p.cover_url"
                                                         :alt="p.title"
                                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                                </a>
                                            </div>

                                            <div>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700"
                                                      x-text="'#' + p.property_code"></span>
                                                <h4 class="font-extrabold text-slate-900 text-sm mt-1.5 leading-snug line-clamp-2 group-hover:text-emerald-700 transition">
                                                    <a :href="p.detail_url" x-text="p.title"></a>
                                                </h4>
                                            </div>

                                            <div class="text-lg font-black text-emerald-600 font-mono tracking-tight"
                                                 x-text="p.formatted_price"></div>
                                        </div>
                                    </td>
                                </template>
                            </tr>

                            <!-- Row: Type -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    প্রপার্টির ধরন
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100">
                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-extrabold text-white shadow-xs"
                                              :class="p.type === 'land' ? 'bg-emerald-600' : (p.type === 'commercial' ? 'bg-indigo-600' : (p.type === 'land_share' ? 'bg-purple-600' : 'bg-teal-600'))"
                                              x-text="p.type_label"></span>
                                    </td>
                                </template>
                            </tr>

                            <!-- Row: Location -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    অবস্থান (ব্লক ও রোড)
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100 font-semibold text-slate-900">
                                        <span x-text="p.area"></span>
                                        <span x-show="p.block" x-text="' • ব্লক ' + p.block"></span>
                                        <span x-show="p.sector" x-text="' • সেক্টর ' + p.sector"></span>
                                        <span x-show="p.road_number" x-text="' • রোড #' + p.road_number"></span>
                                        <span x-show="p.plot_number" x-text="' • প্লট #' + p.plot_number"></span>
                                    </td>
                                </template>
                            </tr>

                            <!-- Row: Road Width -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    সামনের রাস্তার চওড়া
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100 font-bold text-slate-800"
                                        x-text="p.road_width_ft ? p.road_width_ft + ' ফুট রাস্তা' : 'আলোচনা সাপেক্ষে'"></td>
                                </template>
                            </tr>

                            <!-- Row: Size -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    আকার / সাইজ
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100 font-black text-slate-900 text-sm"
                                        x-text="p.size_label"></td>
                                </template>
                            </tr>

                            <!-- Row: Per Unit Rate -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    ইউনিট রেট (কাঠা/বর্গফুট)
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100 text-teal-800 font-bold font-mono"
                                        x-text="p.unit_rate || 'আলোচনা সাপেক্ষে'"></td>
                                </template>
                            </tr>

                            <!-- Row: Price Negotiable Status -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    মূল্য শর্ত (Fixed vs Bargain)
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100">
                                        <span class="px-2 py-0.5 rounded-md font-semibold text-xs"
                                              :class="p.is_negotiable ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700'"
                                              x-text="p.is_negotiable ? '💬 আলোচনা সাপেক্ষ' : '🔒 ফিক্সড প্রাইস'"></span>
                                    </td>
                                </template>
                            </tr>

                            <!-- Row: Facing Direction -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    দিক / ফেসিং
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100 capitalize font-medium text-slate-800"
                                        x-text="p.facing ? p.facing + ' মুখী' : 'N/A'"></td>
                                </template>
                            </tr>

                            <!-- Row: Listing Source -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    সরাসরি উৎস (Source)
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100">
                                        <div class="space-y-1">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white inline-block"
                                                  :class="p.source_type === 'direct_owner' ? 'bg-emerald-600' : 'bg-blue-600'"
                                                  x-text="p.source_type === 'direct_owner' ? '🟢 Direct Owner' : '🔵 Direct Agent'"></span>
                                            <div x-show="p.owner_name" class="text-[11px] font-bold text-slate-900" x-text="p.owner_name"></div>
                                        </div>
                                    </td>
                                </template>
                            </tr>

                            <!-- Row: Key Features & Amenities -->
                            <tr>
                                <td class="p-3.5 bg-slate-50 font-bold text-slate-700 sticky left-0 z-10 border-r border-slate-200">
                                    বিশেষ সুবিধা ও ফিচার
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-3.5 border-r border-slate-100">
                                        <!-- Land Specifics -->
                                        <template x-if="p.type === 'land' && p.land">
                                            <div class="space-y-1 text-[11px]">
                                                <div x-text="p.land.is_corner ? '✅ কর্নার প্লট (Corner)' : '❌ কর্নার নয়'"></div>
                                                <div x-text="p.land.is_lake_facing ? '✅ লেক ভিউ (Lake View)' : '❌ লেক ভিউ নেই'"></div>
                                                <div x-text="p.land.boundary_wall_done ? '✅ বাউন্ডারি ওয়াল সম্পন্ন' : '❌ বাউন্ডারি ওয়াল নেই'"></div>
                                            </div>
                                        </template>

                                        <!-- Flat Specifics -->
                                        <template x-if="p.type === 'flat' && p.flat">
                                            <div class="space-y-1 text-[11px]">
                                                <div class="font-bold text-slate-800" x-text="p.flat.bedrooms + ' বেড • ' + p.flat.bathrooms + ' বাথ'"></div>
                                                <div x-text="p.flat.has_lift ? '✅ আধুনিক লিফট' : '❌ লিফট নেই'"></div>
                                                <div x-text="p.flat.parking_available ? '🚗 কার পার্কিং সুবিধা' : '❌ পার্কিং নেই'"></div>
                                                <div x-text="p.flat.has_gas ? '🔥 গ্যাস কানেকশন' : '❌ গ্যাস কানেকশন নেই'"></div>
                                            </div>
                                        </template>

                                        <!-- Commercial Specifics -->
                                        <template x-if="p.type === 'commercial' && p.commercial">
                                            <div class="space-y-1 text-[11px]">
                                                <div class="font-bold text-slate-800" x-text="(p.commercial.market_name || 'মার্কেট') + (p.commercial.shop_number ? ' • #' + p.commercial.shop_number : '')"></div>
                                                <div x-text="p.commercial.has_lift ? '✅ লিফট সুবিধা' : '❌ লিফট নেই'"></div>
                                                <div x-text="p.commercial.has_escalator ? '✅ এস্কেলেটর' : '❌ এস্কেলেটর নেই'"></div>
                                                <div x-text="p.commercial.has_central_ac ? '❄️ সেন্ট্রাল এসি' : '❌ এসি নেই'"></div>
                                            </div>
                                        </template>

                                        <!-- Share Specifics -->
                                        <template x-if="p.type === 'land_share' && p.share">
                                            <div class="space-y-1 text-[11px]">
                                                <div x-text="'ফ্ল্যাট শেয়ার: ' + p.share.flat_size_sqft + ' sqft'"></div>
                                                <div x-show="p.share.land_share_katha" x-text="'ভূমির অংশ: ' + p.share.land_share_katha + ' কাঠা'"></div>
                                            </div>
                                        </template>
                                    </td>
                                </template>
                            </tr>

                            <!-- Bottom Row: Action Links -->
                            <tr>
                                <td class="p-4 bg-slate-900 text-white font-bold sticky left-0 z-10 border-r border-slate-800">
                                    যোগাযোগ ও বিবরণ
                                </td>
                                <template x-for="p in properties" :key="p.id">
                                    <td class="p-4 border-r border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <a :href="p.detail_url"
                                               class="flex-1 text-center py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition">
                                                বিস্তারিত দেখুন &rarr;
                                            </a>
                                            <a :href="'https://wa.me/?text=' + encodeURIComponent('এই প্রপার্টিটি দেখুন: ' + p.detail_url)"
                                               target="_blank"
                                               class="p-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs transition font-bold"
                                               title="হোয়াটসঅ্যাপে শেয়ার করুন">
                                                💬
                                            </a>
                                        </div>
                                    </td>
                                </template>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && properties.length === 0"
             x-cloak
             class="bg-white rounded-3xl p-12 border border-slate-200 text-center space-y-4 shadow-sm max-w-lg mx-auto">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto">
                ⚖️
            </div>
            <h2 class="font-extrabold text-slate-900 text-lg m-0">আপনার তুলনার তালিকায় কোনো প্রপার্টি নেই</h2>
            <p class="text-xs text-slate-500 max-w-md mx-auto m-0 leading-relaxed">
                মার্কেটপ্লেসের জমি ও ফ্ল্যাটের কার্ডে স্কেল (⚖️) আইকনে ক্লিক করে সর্বোচ্চ ৪টি প্রপার্টি পাশাপাশি তুলনা করতে পারেন।
            </p>
            <div class="flex flex-wrap justify-center gap-2.5 pt-2">
                <a href="{{ route('properties.land') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                    🏞️ জমি দেখুন &rarr;
                </a>
                <a href="{{ route('properties.flat') }}" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                    🏢 ফ্ল্যাট দেখুন &rarr;
                </a>
                <a href="{{ route('properties.share') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                    🤝 ল্যান্ড শেয়ার &rarr;
                </a>
                <a href="{{ route('properties.commercial') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                    🏪 দোকান/স্পেস &rarr;
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
