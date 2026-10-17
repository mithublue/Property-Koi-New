@props(['property'])

@php
    $p = $property;
    $typeLabel = $p->type_label;
    $verifiedCount = $p->directSources->count();
    $primarySource = $p->directSources->first();
    $isOwner = $primarySource && $primarySource->source_type === 'direct_owner';
    $coverUrl = $p->cover_url;
    $detailUrl = route('property.show', $p->slug);

    $locParts = [$p->area ?: 'Aftabnagar'];
    if (!empty($p->sub_area)) {
        $locParts[] = $p->sub_area;
    } elseif (!empty($p->block) && $p->isAddressVisible('block')) {
        $locParts[] = 'ব্লক ' . $p->block;
    }
    $locationStr = implode(' • ', $locParts);
@endphp

<div class="adm-property-card bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group"
     x-data="{
         id: {{ (int) $p->id }},
         isShortlisted: false,
         isCompared: false,
         shareOpen: false,
         copied: false,
         shareUrl: @js($detailUrl),
         shareTitle: @js($p->title . ' | ' . $p->formatted_price . ' (' . $locationStr . ')'),
         init() {
             let list = [];
             try {
                 const a = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]');
                 const b = JSON.parse(localStorage.getItem('propertykoi_shortlist') || '[]');
                 list = Array.from(new Set([...a, ...b].map(Number).filter(Boolean)));
             } catch(e) {}
             this.isShortlisted = list.includes(Number(this.id));
             let comp = [];
             try { comp = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
             this.isCompared = comp.includes(Number(this.id));

             window.addEventListener('shortlist-updated', () => {
                 let cur = [];
                 try {
                     const a = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]');
                     const b = JSON.parse(localStorage.getItem('propertykoi_shortlist') || '[]');
                     cur = Array.from(new Set([...a, ...b].map(Number).filter(Boolean)));
                 } catch(e) {}
                 this.isShortlisted = cur.includes(Number(this.id));
             });
             window.addEventListener('compare-updated', () => {
                 let curC = [];
                 try { curC = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
                 this.isCompared = curC.includes(Number(this.id));
             });
         },
         toggleShortlist() {
             const targetId = Number(this.id);
             let list = [];
             try {
                 const a = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]');
                 const b = JSON.parse(localStorage.getItem('propertykoi_shortlist') || '[]');
                 list = Array.from(new Set([...a, ...b].map(Number).filter(Boolean)));
             } catch(e) {}
             if (this.isShortlisted) {
                 list = list.filter(item => item !== targetId);
                 this.isShortlisted = false;
             } else {
                 if (!list.includes(targetId)) list.push(targetId);
                 this.isShortlisted = true;
             }
             try {
                 localStorage.setItem('aftabnagar_shortlist', JSON.stringify(list));
                 localStorage.setItem('propertykoi_shortlist', JSON.stringify(list));
             } catch(e) {}
             if (window.trackClarityEvent) {
                 window.trackClarityEvent('property_favorited', { property_id: targetId, active: this.isShortlisted ? '1' : '0' });
             }
             window.dispatchEvent(new CustomEvent('shortlist-updated'));
             @auth
             fetch('{{ route('buyer.dashboard.fav.toggle') }}', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                 body: JSON.stringify({ property_id: targetId })
             }).catch(() => {});
             @endauth
         },
         toggleCompare() {
             const targetId = Number(this.id);
             let comp = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number);
             if (this.isCompared) {
                 comp = comp.filter(item => item !== targetId);
                 this.isCompared = false;
             } else {
                 if (comp.length >= 4) {
                     alert('সর্বোচ্চ ৪টি প্রপার্টি একসাথে তুলনা করা যাবে');
                     return;
                 }
                 if (!comp.includes(targetId)) comp.push(targetId);
                 this.isCompared = true;
             }
             localStorage.setItem('aftabnagar_compare', JSON.stringify(comp));
             if (window.trackClarityEvent) {
                 window.trackClarityEvent('property_compared', { property_id: targetId, active: this.isCompared ? '1' : '0' });
             }
             window.dispatchEvent(new CustomEvent('compare-updated'));
         },
         shareTo(platform) {
             if (window.trackClarityEvent) {
                 window.trackClarityEvent('property_shared', { property_id: this.id, platform: platform });
             }
             const encodedUrl = encodeURIComponent(this.shareUrl);
             const encodedText = encodeURIComponent(this.shareTitle + ' — ' + this.shareUrl);
             if (platform === 'facebook') {
                 window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl, '_blank', 'width=600,height=450');
             } else if (platform === 'whatsapp') {
                 window.open('https://api.whatsapp.com/send?text=' + encodedText, '_blank');
             } else if (platform === 'telegram') {
                 window.open('https://t.me/share/url?url=' + encodedUrl + '&text=' + encodeURIComponent(this.shareTitle), '_blank');
             } else if (platform === 'copy') {
                 navigator.clipboard.writeText(this.shareUrl);
                 this.copied = true;
                 setTimeout(() => { this.copied = false; this.shareOpen = false; }, 1500);
                 return;
             } else if (platform === 'native' && navigator.share) {
                 navigator.share({ title: this.shareTitle, text: this.shareTitle, url: this.shareUrl }).catch(() => {});
             }
             this.shareOpen = false;
         }
     }">

    <!-- Image Container -->
    <div class="relative aspect-16/10 overflow-hidden bg-slate-100">
        <a href="{{ $detailUrl }}" class="block w-full h-full">
            <img src="{{ $coverUrl }}"
                 alt="{{ $p->title }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        </a>

        <!-- Top Badges -->
        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5 items-center">
            <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg uppercase tracking-wider text-white shadow-xs {{ $p->type === 'land' ? 'bg-emerald-600' : ($p->type === 'land_share' ? 'bg-purple-600' : ($p->type === 'commercial' ? 'bg-indigo-600' : 'bg-teal-600')) }}">
                {{ $typeLabel }}
            </span>
            <span class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-900/80 backdrop-blur-xs text-white shadow-xs">
                {{ $locationStr }}
            </span>
        </div>

        <!-- Top Right Actions (Share, Compare, Shortlist) -->
        <div class="absolute top-3 right-3 flex items-center gap-1.5 z-20" @click.outside="shareOpen = false">
            <!-- Share Button & Popover -->
            <div class="relative">
                <button @click.prevent="shareOpen = !shareOpen"
                        type="button"
                        :class="shareOpen ? 'bg-emerald-600 text-white' : 'bg-white/90 text-slate-700 hover:text-emerald-600'"
                        class="p-2 rounded-xl backdrop-blur-xs shadow-xs transition-all cursor-pointer"
                        title="শেয়ার করুন">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </button>
                <div x-show="shareOpen" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute right-0 mt-1.5 w-44 bg-white rounded-2xl shadow-xl border border-slate-200 p-1.5 text-xs font-bold text-slate-700 space-y-1 z-30">
                    <button type="button" @click.prevent="shareTo('facebook')" class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl hover:bg-blue-50 text-blue-700 transition cursor-pointer">
                        <span>📘</span> <span>Facebook-এ শেয়ার</span>
                    </button>
                    <button type="button" @click.prevent="shareTo('whatsapp')" class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl hover:bg-emerald-50 text-emerald-700 transition cursor-pointer">
                        <span>🟢</span> <span>WhatsApp-এ শেয়ার</span>
                    </button>
                    <button type="button" @click.prevent="shareTo('telegram')" class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl hover:bg-sky-50 text-sky-700 transition cursor-pointer">
                        <span>✈️</span> <span>Telegram-এ শেয়ার</span>
                    </button>
                    <button type="button" @click.prevent="shareTo('copy')" class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl hover:bg-slate-100 text-slate-800 transition cursor-pointer">
                        <span x-text="copied ? '✅' : '🔗'"></span>
                        <span x-text="copied ? 'লিঙ্ক কপি হয়েছে!' : 'লিঙ্ক কপি করুন'"></span>
                    </button>
                </div>
            </div>
            <button @click.prevent="toggleCompare()"
                    type="button"
                    :class="isCompared ? 'bg-teal-600 text-white' : 'bg-white/90 text-slate-700 hover:text-teal-600'"
                    class="p-2 rounded-xl backdrop-blur-xs shadow-xs transition-all cursor-pointer"
                    title="তুলনা করুন">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </button>
            <button @click.prevent="toggleShortlist()"
                    type="button"
                    :class="isShortlisted ? 'bg-red-500 text-white' : 'bg-white/90 text-slate-700 hover:text-red-500'"
                    class="p-2 rounded-xl backdrop-blur-xs shadow-xs transition-all cursor-pointer"
                    title="শর্টলিস্ট করুন">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            </button>
        </div>

        <!-- Direct Source Badge Overlay -->
        <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between">
            @if($isOwner)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500 text-white shadow-md">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    Direct Owner
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-600 text-white shadow-md">
                    <span class="w-2 h-2 rounded-full bg-white"></span>
                    Direct Agent
                </span>
            @endif

            @if($verifiedCount > 1)
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-900/80 text-emerald-400 backdrop-blur-xs">
                    {{ $verifiedCount }} Verified Sources
                </span>
            @endif
        </div>
    </div>

    <!-- Content Body -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
        <div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-slate-400">{{ $p->property_code }}</span>
                    @if(!empty($p->views_count) && (int) $p->views_count > 0)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded" title="মোট ভিউ সংখ্যা">
                            <span>👁️</span>
                            <span>{{ $p->views_count }}</span>
                        </span>
                    @endif
                </div>
                @if(!empty($p->road_width_ft) && $p->isAddressVisible('road_width_ft'))
                    <span class="font-medium bg-slate-100 px-2 py-0.5 rounded-md text-slate-700">{{ $p->road_width_ft }} ফুট রাস্তা</span>
                @endif
            </div>

            <h3 class="font-bold text-slate-900 text-base leading-snug line-clamp-2 hover:text-emerald-600 transition">
                <a href="{{ $detailUrl }}">
                    {{ $p->title }}
                </a>
            </h3>

            <!-- Specs line -->
            <div class="mt-3 flex flex-wrap items-center gap-y-1 gap-x-3 text-xs text-slate-600 border-t border-slate-100 pt-3">
                @if($p->type === 'land' && !empty($p->land))
                    <span class="font-semibold text-slate-900">{{ $p->land->katha_size }} কাঠা</span>
                    @if(!empty($p->facing))
                        <span>•</span>
                        <span class="capitalize">{{ $p->facing }} মুখী</span>
                    @endif
                    @if(!empty($p->land->is_corner))
                        <span>•</span>
                        <span class="text-amber-600 font-medium">কর্নার প্লট</span>
                    @endif
                    @if(!empty($p->land->is_lake_facing))
                        <span>•</span>
                        <span class="text-teal-600 font-medium">লেক ভিউ</span>
                    @endif
                @elseif($p->type === 'flat' && !empty($p->flat))
                    <span class="font-semibold text-slate-900">{{ (int) $p->flat->flat_size_sqft }} বর্গফুট</span>
                    <span>•</span>
                    <span>{{ $p->flat->bedrooms }} বেড</span>
                    <span>•</span>
                    <span>{{ $p->flat->floor_number }}ম তলা</span>
                    <span>•</span>
                    <span class="text-emerald-600 font-medium">{{ $p->flat->completion_status === 'ready' ? 'রেডি' : 'চলমান' }}</span>
                @elseif($p->type === 'land_share' && !empty($p->share))
                    <span class="font-semibold text-slate-900">{{ $p->share->land_size_katha ?: 'N/A' }} কাঠা</span>
                    @if(!empty($p->share->flat_size_sqft))
                        <span>•</span>
                        <span>{{ (int) $p->share->flat_size_sqft }} sqft</span>
                    @endif
                    @if(!empty($p->share->building_stories))
                        <span>•</span>
                        <span class="text-indigo-600 font-bold">{{ $p->share->building_stories }}</span>
                    @endif
                @elseif($p->type === 'commercial' && !empty($p->commercial))
                    <span class="font-semibold text-slate-900">{{ (int) $p->commercial->size_sqft }} বর্গফুট</span>
                    <span>•</span>
                    <span class="text-indigo-600 font-medium">{{ $p->commercial->commercial_type }}</span>
                    @if(!empty($p->commercial->floor_number))
                        <span>•</span>
                        <span>{{ $p->commercial->floor_number }}</span>
                    @endif
                @endif
            </div>
        </div>

        <!-- Price & Action Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <div>
                <div class="text-lg font-extrabold text-emerald-600 tracking-tight">
                    {{ $p->formatted_price }}
                </div>
            </div>

            <a href="{{ $detailUrl }}"
               class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-800 bg-slate-100 hover:bg-emerald-600 hover:text-white transition-colors duration-200">
                <span>বিস্তারিত</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</div>
