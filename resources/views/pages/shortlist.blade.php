@extends('layouts.app')

@section('title', $seo['title'])

@section('content')
<script>
window.shortlistManager = function() {
    return {
        properties: [],
        loading: true,

        init() {
            this.loadData();
            window.addEventListener('shortlist-updated', (e) => {
                if (e.detail && e.detail.source === 'shortlist-page') return;
                this.loadData();
            });
            window.addEventListener('storage', (e) => {
                if (e.key === 'aftabnagar_shortlist' || e.key === 'propertykoi_shortlist') {
                    this.loadData();
                }
            });
        },

        loadData() {
            var ids = [];
            try {
                var rawA = localStorage.getItem('aftabnagar_shortlist');
                var rawB = localStorage.getItem('propertykoi_shortlist');
                var listA = rawA ? JSON.parse(rawA) : [];
                var listB = rawB ? JSON.parse(rawB) : [];
                var combined = [
                    ...(Array.isArray(listA) ? listA : []),
                    ...(Array.isArray(listB) ? listB : [])
                ];
                ids = Array.from(new Set(combined.map(Number).filter(Boolean)));
            } catch(e) {
                ids = [];
            }

            this.loading = true;

            fetch('{{ route('shortlist.data') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(function(r) { return r.json(); })
            .then((res) => {
                this.loading = false;
                if (res && res.success) {
                    this.properties = res.properties || [];
                    // Sync active IDs back to both localStorage keys
                    var activeIds = (res.ids || this.properties.map(function(p) { return Number(p.id); })).filter(Boolean);
                    try {
                        localStorage.setItem('aftabnagar_shortlist', JSON.stringify(activeIds));
                        localStorage.setItem('propertykoi_shortlist', JSON.stringify(activeIds));
                    } catch(e) {}
                }
            })
            .catch((err) => {
                console.error('Error loading shortlist:', err);
                this.loading = false;
            });
        },

        removeItem(id) {
            var targetId = Number(id);
            try {
                var rawA = localStorage.getItem('aftabnagar_shortlist');
                var rawB = localStorage.getItem('propertykoi_shortlist');
                var listA = rawA ? JSON.parse(rawA) : [];
                var listB = rawB ? JSON.parse(rawB) : [];
                var combined = [
                    ...(Array.isArray(listA) ? listA : []),
                    ...(Array.isArray(listB) ? listB : [])
                ];
                var updated = Array.from(new Set(combined.map(Number).filter(Boolean))).filter(function(x) {
                    return x !== targetId;
                });
                localStorage.setItem('aftabnagar_shortlist', JSON.stringify(updated));
                localStorage.setItem('propertykoi_shortlist', JSON.stringify(updated));
            } catch(e) {}

            this.properties = this.properties.filter(function(p) {
                return Number(p.id) !== targetId;
            });
            window.dispatchEvent(new CustomEvent('shortlist-updated', { detail: { source: 'shortlist-page' } }));

            @auth
            fetch('{{ route('buyer.dashboard.fav.remove') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ property_id: targetId })
            }).catch(function() {});
            @endauth
        },

        clearAll() {
            if (!confirm('আপনি কি নিশ্চিত যে আপনার পুরো শর্টলিস্ট খালি করতে চান?')) return;
            try {
                localStorage.removeItem('aftabnagar_shortlist');
                localStorage.removeItem('propertykoi_shortlist');
                localStorage.setItem('aftabnagar_shortlist', '[]');
                localStorage.setItem('propertykoi_shortlist', '[]');
            } catch(e) {}
            this.properties = [];
            window.dispatchEvent(new CustomEvent('shortlist-updated', { detail: { source: 'shortlist-page' } }));

            @auth
            fetch('{{ route('buyer.dashboard.fav.remove') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ clear_all: true })
            }).catch(function() {});
            @endauth
        },

        compareAll(e) {
            if (this.properties.length < 2) {
                if (e) e.preventDefault();
                alert('তুলনা করার জন্য অন্তত ২টি প্রপার্টি প্রয়োজন।');
                return;
            }
            var ids = this.properties.slice(0, 4).map(function(p) { return p.id; }).join(',');
            var targetUrl = '{{ route('compare.index') }}?ids=' + ids;
            if (window.Turbo) {
                if (e) e.preventDefault();
                window.Turbo.visit(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
        }
    };
};

if (window.Alpine) {
    window.Alpine.data('shortlistManager', window.shortlistManager);
} else {
    document.addEventListener('alpine:init', function() {
        window.Alpine.data('shortlistManager', window.shortlistManager);
    });
}
</script>

<div class="aftabnagar-marketplace-wrapper font-sans text-slate-800 antialiased bg-slate-50 py-10 min-h-[80vh]"
     x-data="shortlistManager()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @auth
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-300 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🛒</span>
                <div>
                    <h3 class="text-xs font-extrabold text-emerald-950 m-0">আপনি ক্রেতা হিসেবে লগইন আছেন</h3>
                    <p class="text-[11px] text-emerald-800 m-0 mt-0.5">আপনার সংরক্ষিত পছন্দসমূহ ক্রেতা ড্যাশবোর্ডে স্বয়ংক্রিয়ভাবে ক্লাউডে সিঙ্ক করা আছে।</p>
                </div>
            </div>
            <a href="{{ route('buyer.dashboard') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-sm transition shrink-0 flex items-center gap-1.5">
                <span>ক্রেতা ড্যাশবোর্ড দেখুন</span>
                <span>&rarr;</span>
            </a>
        </div>
        @endauth

        <!-- Header -->
        <div class="bg-slate-900 text-white rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold m-0 flex items-center gap-2">
                    <span>❤️</span>
                    <span>আমার শর্টলিস্ট করা প্রপার্টিসমূহ</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1 m-0">আপনার পছন্দের সংরক্ষিত প্রপার্টি তালিকা (স্বয়ংক্রিয়ভাবে সংরক্ষিত)</p>
            </div>
            <div class="flex items-center gap-2.5">
                <button x-show="properties.length > 1" @click="compareAll($event)" type="button"
                        class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-1.5 cursor-pointer">
                    <span>⚖️ পাশাপাশি তুলনা করুন</span>
                </button>
                <button x-show="properties.length > 0" @click.stop.prevent="clearAll()" type="button"
                        class="px-4 py-2.5 bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-1.5 cursor-pointer">
                    <span>🗑️ খালি করুন</span>
                </button>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div x-show="loading" class="text-center py-16">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-emerald-600 border-t-transparent"></div>
            <div class="text-xs text-slate-500 mt-2 font-medium">পছন্দের প্রপার্টিসমূহ লোড হচ্ছে...</div>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && properties.length === 0" x-cloak class="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-4 shadow-sm max-w-lg mx-auto my-8">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto">
                ❤️
            </div>
            <h3 class="text-base font-extrabold text-slate-800 m-0">আপনার শর্টলিস্ট এখনো খালি!</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto m-0 leading-relaxed">
                মার্কেটপ্লেসের জমি ও ফ্ল্যাটের কার্ডে হার্ট আইকন (❤️) এ ক্লিক করে সহজেই পছন্দের তালিকায় যুক্ত করতে পারেন।
            </p>
            <div class="pt-2 flex justify-center gap-2.5">
                <a href="{{ route('properties.land') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-xs">
                    🏞️ জমি দেখুন
                </a>
                <a href="{{ route('properties.flat') }}" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition shadow-xs">
                    🏢 ফ্ল্যাট দেখুন
                </a>
            </div>
        </div>

        <!-- Shortlisted Properties Grid -->
        <div x-show="!loading && properties.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="p in properties" :key="p.id">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group relative">
                    <!-- Image Container -->
                    <div class="relative aspect-16/10 overflow-hidden bg-slate-100">
                        <a :href="p.url" class="block w-full h-full">
                            <img :src="p.image || p.cover_url" :alt="p.title"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </a>

                        <!-- Badges -->
                        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5 items-center">
                            <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-lg uppercase tracking-wider text-white shadow-xs"
                                  :class="p.type === 'land' ? 'bg-emerald-600' : (p.type === 'commercial' ? 'bg-indigo-600' : (p.type === 'land_share' ? 'bg-purple-600' : 'bg-teal-600'))"
                                  x-text="p.type_label"></span>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-900/80 text-white backdrop-blur"
                                  x-text="'ব্লক ' + (p.block || 'A')"></span>
                        </div>

                        <!-- Remove Button -->
                        <button type="button" @click.stop.prevent="removeItem(p.id)"
                                class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-white/95 hover:bg-rose-50 hover:text-rose-600 text-rose-500 flex items-center justify-center shadow-md transition cursor-pointer"
                                title="শর্টলিস্ট থেকে সরান">
                            ✕
                        </button>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="text-[11px] font-mono text-slate-400 font-semibold" x-text="'#' + (p.code || ('AF-' + p.id))"></div>
                            <h3 class="text-sm font-extrabold text-slate-900 mt-1 line-clamp-2 leading-snug group-hover:text-emerald-700 transition">
                                <a :href="p.url" x-text="p.title"></a>
                            </h3>
                            <div class="text-xs text-slate-500 mt-2 flex items-center gap-3">
                                <span>📍 আফতাবনগর, ব্লক: <strong x-text="p.block || 'A'"></strong></span>
                                <span x-show="p.road" x-text="'রোড: ' + p.road"></span>
                                <span x-show="p.size" class="text-emerald-700 font-semibold" x-text="'• ' + p.size"></span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase">মূল্য</div>
                                <div class="text-base font-black text-emerald-700 font-mono" x-text="p.price"></div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a :href="'https://wa.me/?text=' + encodeURIComponent('এই প্রপার্টিটি দেখুন: ' + p.url)"
                                   target="_blank"
                                   class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs transition font-bold"
                                   title="হোয়াটসঅ্যাপে শেয়ার করুন">
                                    💬
                                </a>
                                <a :href="p.url" class="px-3.5 py-2 bg-slate-900 hover:bg-emerald-600 text-white font-extrabold text-xs rounded-xl transition shadow-xs">
                                    বিস্তারিত &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>
</div>
@endsection
