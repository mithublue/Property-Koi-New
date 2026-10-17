@extends('layouts.app')

@section('content')
@php
    $p = $property;
    $typeLabel = $p->type_label;
    $primarySource = $p->directSources->first();
    $isOwner = $primarySource && $primarySource->source_type === 'direct_owner';

    // Platform coordinator WhatsApp (never exposing owner's personal number on frontend)
    $adminWa = \App\Models\Setting::get('adm_admin_whatsapp_number', \App\Models\Setting::get('admin_whatsapp_number', '01811556677'));
    $cleanWa = preg_replace('/[^0-9]/', '', (string)$adminWa);
    $intlWa = str_starts_with($cleanWa, '0') ? ('88' . $cleanWa) : $cleanWa;
    $waText = urlencode("হ্যালো, আমি '{$p->title}' (কোড: {$p->property_code}) প্রপার্টিটি সম্পর্কে জানতে আগ্রহী।");
    $waUrl = "https://wa.me/{$intlWa}?text={$waText}";

    $user = auth()->user();
    $prefillName = $user ? $user->name : '';
    $prefillPhone = $user ? ($user->whatsapp_number ?: $user->phone) : '';

    // Media separation: Photos vs Video Embed
    $photoUrls = $p->media->where('media_type', '!=', 'video_embed')->map(fn($m) => $m->url)->filter()->values()->all();
    if (empty($photoUrls)) {
        $photoUrls = [$p->cover_url];
    }
    $videoMedia = $p->media->firstWhere('media_type', 'video_embed');
    $videoEmbedUrl = null;
    if ($videoMedia && !empty($videoMedia->file_path)) {
        $rawVid = trim($videoMedia->file_path);
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{6,15})/', $rawVid, $m)) {
            $videoEmbedUrl = 'https://www.youtube.com/embed/' . $m[1];
        } elseif (str_contains($rawVid, 'facebook.com') || str_contains($rawVid, 'fb.watch')) {
            $videoEmbedUrl = 'https://www.facebook.com/plugins/video.php?href=' . urlencode($rawVid) . '&show_text=false';
        }
    }

    // Social Share URLs
    $shareUrl = route('property.show', $p->slug);
    $shareTitle = $p->title . ' | ' . $p->formatted_price . ' | PropertyKoi.com';
    $encodedShareUrl = urlencode($shareUrl);
    $encodedShareText = urlencode($shareTitle . ' - ' . $shareUrl);

    $initialReviews = isset($reviews) ? $reviews->map(fn($r) => [
        'id'            => $r->id,
        'reviewer_name' => $r->reviewer_name,
        'rating'        => (int) $r->rating,
        'review_text'   => $r->review_text,
        'created_at'    => $r->created_at ? $r->created_at->diffForHumans() : 'সম্প্রতি',
    ])->values()->all() : [];
@endphp

<script>
    (function() {
        function recordPropertyView() {
            if (window.trackClarityEvent) {
                window.trackClarityEvent('property_view', {
                    property_id: '{{ $p->id }}',
                    property_code: '{{ $p->property_code }}',
                    property_type: '{{ $p->type }}',
                    block: '{{ $p->block }}',
                    price: '{{ $p->raw_price }}'
                });
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', recordPropertyView);
        } else {
            recordPropertyView();
        }
    })();
</script>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8 space-y-8 relative"
     x-data="{
         galleryImages: {{ json_encode($photoUrls) }},
         activeImage: '{{ $photoUrls[0] ?? $p->cover_url }}',
         lightboxOpen: false,
         lightboxIndex: 0,
         lightboxZoom: false,
         copiedLink: false,
         contactDrawerOpen: false,
         safetyTipsModalOpen: false,

         // Review Modal State
         reviewModalOpen: false,
         reviewSubmitting: false,
         reviewSubmitted: false,
         reviewMessage: '',
         reviewsList: {{ json_encode($initialReviews) }},
         reviewForm: {
             reviewer_name: '{{ addslashes($prefillName) }}',
             reviewer_phone: '{{ addslashes($prefillPhone) }}',
             rating: 5,
             review_text: '',
             target_user_id: '{{ $primarySource?->source_user_id ?? ($p->created_by_user_id ?? 1) }}',
             target_type: '{{ $isOwner ? 'owner' : 'agent' }}'
         },

         openLightbox(url) {
             const idx = this.galleryImages.indexOf(url);
             this.lightboxIndex = idx >= 0 ? idx : 0;
             this.lightboxZoom = false;
             this.lightboxOpen = true;
         },
         nextLightbox() {
             this.lightboxZoom = false;
             this.lightboxIndex = (this.lightboxIndex + 1) % this.galleryImages.length;
             this.activeImage = this.galleryImages[this.lightboxIndex];
         },
         prevLightbox() {
             this.lightboxZoom = false;
             this.lightboxIndex = (this.lightboxIndex - 1 + this.galleryImages.length) % this.galleryImages.length;
             this.activeImage = this.galleryImages[this.lightboxIndex];
         },

         trackShare(platform) {
             if (window.trackClarityEvent) {
                 window.trackClarityEvent('property_shared', {
                     platform: platform,
                     property_id: '{{ $p->id }}',
                     property_code: '{{ $p->property_code }}'
                 });
             }
         },
         copyPropertyLink() {
             navigator.clipboard.writeText('{{ $shareUrl }}');
             this.copiedLink = true;
             this.trackShare('copy_link');
             setTimeout(() => { this.copiedLink = false; }, 2500);
         },
         nativeShareProperty() {
             this.trackShare('native_share');
             if (navigator.share) {
                 navigator.share({
                     title: '{{ addslashes($shareTitle) }}',
                     text: '{{ addslashes($p->title . ' - ' . $p->formatted_price) }}',
                     url: '{{ $shareUrl }}'
                 }).catch(() => {});
             } else {
                 this.copyPropertyLink();
             }
         },

         openReviewModal(userId, type) {
             if (userId) this.reviewForm.target_user_id = userId;
             if (type) this.reviewForm.target_type = type;
             this.reviewSubmitted = false;
             this.reviewModalOpen = true;
         },
         submitReviewForm() {
             if (!this.reviewForm.reviewer_name || !this.reviewForm.reviewer_phone || !this.reviewForm.review_text) {
                 alert('অনুগ্রহ করে আপনার নাম, ফোন নম্বর এবং মন্তব্য লিখুন।');
                 return;
             }
             this.reviewSubmitting = true;
             fetch('{{ route('property.review', $p->slug) }}', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 },
                 body: JSON.stringify(this.reviewForm)
             })
             .then(r => r.json())
             .then(res => {
                 this.reviewSubmitting = false;
                 if (res.success) {
                     this.reviewSubmitted = true;
                     this.reviewMessage = res.message;
                     if (res.data) {
                         this.reviewsList.unshift(res.data);
                     }
                     this.reviewForm.review_text = '';
                 } else {
                     alert(res.message || 'রিভিউ জমা দেওয়া যায়নি।');
                 }
             })
             .catch(() => {
                 this.reviewSubmitting = false;
                 alert('সার্ভারে যোগাযোগ করা যায়নি।');
             });
         },

         // Scheduler State
         schedulerModalOpen: false,
         schedLoading: false,
         schedMonth: {{ (int) now()->month }},
         schedYear: {{ (int) now()->year }},
         selectedDate: '',
         selectedSlot: '',
         slotsByDate: {},
         buyerName: '{{ addslashes($prefillName) }}',
         buyerPhone: '{{ addslashes($prefillPhone) }}',
         buyerMessage: '',
         bookingSubmitting: false,
         bookingSubmitted: false,
         bookingStatus: '',
         bookingMessage: '',

         // Enquiry Modal (more_info / make_offer)
         visitModalOpen: false,
         modalTitle: 'আরও তথ্য জানুন',
         modalSubtitle: 'আপনার প্রশ্ন লিখুন, আমাদের টিম দ্রুত বিস্তারিত জানাবে।',
         leadSubmitting: false,
         leadSubmitted: false,
         leadMessage: '',
         formData: {
             buyer_name: '{{ addslashes($prefillName) }}',
             buyer_phone: '{{ addslashes($prefillPhone) }}',
             enquiry_type: 'more_info',
             preferred_date: '',
             preferred_time_slot: 'morning',
             offered_price: '',
             message: ''
         },

         trackWa() {
             if (window.trackClarityEvent) {
                 window.trackClarityEvent('whatsapp_click', { property_id: '{{ $p->id }}', property_code: '{{ $p->property_code }}' });
             }
             fetch('{{ route('property.track-whatsapp', $p->slug) }}', {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 }
             });
         },

         openScheduler() {
             this.schedulerModalOpen = true;
             this.selectedDate = '';
             this.selectedSlot = '';
             this.bookingSubmitted = false;
             this.fetchAvailableSlots();
         },

         fetchAvailableSlots() {
             this.schedLoading = true;
             fetch('{{ route('property.slots.available') }}?month=' + this.schedMonth + '&year=' + this.schedYear)
                 .then(r => r.json())
                 .then(res => {
                     this.schedLoading = false;
                     if (res.success && res.data) {
                         this.slotsByDate = res.data.slots_by_date || {};
                     }
                 })
                 .catch(() => { this.schedLoading = false; });
         },

         prevMonth() {
             const now = new Date();
             if (this.schedYear === now.getFullYear() && this.schedMonth <= (now.getMonth() + 1)) return;
             this.schedMonth--;
             if (this.schedMonth < 1) {
                 this.schedMonth = 12;
                 this.schedYear--;
             }
             this.selectedDate = '';
             this.selectedSlot = '';
             this.fetchAvailableSlots();
         },

         nextMonth() {
             this.schedMonth++;
             if (this.schedMonth > 12) {
                 this.schedMonth = 1;
                 this.schedYear++;
             }
             this.selectedDate = '';
             this.selectedSlot = '';
             this.fetchAvailableSlots();
         },

         getMonthName(m) {
             const names = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
             return names[m - 1] || '';
         },

         getCalendarDays() {
             const days = [];
             const firstDay = new Date(this.schedYear, this.schedMonth - 1, 1).getDay();
             const offset = (firstDay + 6) % 7;
             for (let i = 0; i < offset; i++) {
                 days.push({ blank: true });
             }
             const totalDays = new Date(this.schedYear, this.schedMonth, 0).getDate();
             const today = new Date().toISOString().slice(0, 10);
             for (let d = 1; d <= totalDays; d++) {
                 const dateStr = this.schedYear + '-' + String(this.schedMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                 const slots = this.slotsByDate[dateStr] || [];
                 days.push({
                     blank: false,
                     day: d,
                     dateStr: dateStr,
                     isPast: dateStr < today,
                     hasSlots: slots.length > 0,
                     slotsCount: slots.length
                 });
             }
             return days;
         },

         selectDate(dateStr) {
             if (!this.slotsByDate[dateStr] || this.slotsByDate[dateStr].length === 0) return;
             this.selectedDate = dateStr;
             this.selectedSlot = '';
         },

         submitBooking() {
             if (!this.selectedDate || !this.selectedSlot || !this.buyerName || !this.buyerPhone) {
                 alert('অনুগ্রহ করে তারিখ, সময়, আপনার নাম ও ফোন নম্বর পূরণ করুন');
                 return;
             }
             this.bookingSubmitting = true;
             fetch('{{ route('property.book-visit', $p->slug) }}', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 },
                 body: JSON.stringify({
                     slot_date: this.selectedDate,
                     slot_time: this.selectedSlot,
                     buyer_name: this.buyerName,
                     buyer_phone: this.buyerPhone,
                     message: this.buyerMessage
                 })
             })
             .then(r => r.json())
             .then(res => {
                 this.bookingSubmitting = false;
                 if (res.success) {
                     this.bookingSubmitted = true;
                     this.bookingStatus = res.data?.status || 'pending';
                     this.bookingMessage = res.message || 'আপনার সাইট ভিজিট রিকোয়েস্ট সফলভাবে গ্রহণ করা হয়েছে!';
                     this.fetchAvailableSlots();
                 } else {
                     alert(res.message || 'একটি সমস্যা হয়েছে, অনুগ্রহ করে আবার চেষ্টা করুন।');
                 }
             })
             .catch(() => {
                 this.bookingSubmitting = false;
                 alert('সার্ভারে যোগাযোগ করা যায়নি।');
             });
         },

         openModal(type) {
             if (type === 'visit_request') { this.openScheduler(); return; }
             this.formData.enquiry_type = type;
             this.leadSubmitted = false;
             if (type === 'more_info') {
                 this.modalTitle = 'আরও তথ্য জানুন';
                 this.modalSubtitle = 'আপনার প্রশ্ন লিখুন, আমাদের টিম দ্রুত বিস্তারিত জানাবে।';
             } else if (type === 'make_offer') {
                 this.modalTitle = 'দর প্রস্তাব বা অফার দিন';
                 this.modalSubtitle = 'আপনার প্রস্তাবিত মূল্য উল্লেখ করুন। আমাদের কোঅর্ডিনেশন টিম পর্যালোচনা করবে।';
             }
             this.visitModalOpen = true;
         },

         submitLead() {
             if (!this.formData.buyer_name || !this.formData.buyer_phone) {
                 alert('অনুগ্রহ করে আপনার নাম ও ফোন নম্বর প্রদান করুন');
                 return;
             }
             this.leadSubmitting = true;
             fetch('{{ route('property.lead', $p->slug) }}', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 },
                 body: JSON.stringify(this.formData)
             })
             .then(r => r.json())
             .then(res => {
                 this.leadSubmitting = false;
                 if (res.success) {
                     this.leadSubmitted = true;
                     this.leadMessage = res.message || 'আপনার রিকোয়েস্ট সফলভাবে গ্রহণ করা হয়েছে!';
                 } else {
                     alert(res.message || 'একটি সমস্যা হয়েছে, পুনরায় চেষ্টা করুন।');
                 }
             })
             .catch(() => {
                 this.leadSubmitting = false;
                 alert('সার্ভারে যোগাযোগ করা যায়নি।');
             });
         }
     }">

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 overflow-x-auto py-1">
        <a href="{{ route('home') }}" class="hover:text-emerald-600 transition flex items-center gap-1 font-medium">
            <span>🏠</span> <span>হোম</span>
        </a>
        <span>/</span>
        <a href="{{ route('properties.index') }}?type={{ $p->type }}" class="hover:text-emerald-600 transition font-medium">
            {{ $p->type === 'land' ? 'জমি' : ($p->type === 'land_share' ? 'ল্যান্ড শেয়ার' : ($p->type === 'commercial' ? 'দোকান / কমার্শিয়াল' : 'ফ্ল্যাট')) }}
        </a>
        @if(!empty($p->block) && $p->isAddressVisible('block'))
            <span>/</span>
            <span class="text-slate-600 font-medium">ব্লক {{ $p->block }}</span>
        @endif
        <span>/</span>
        <span class="text-slate-900 font-bold truncate max-w-xs sm:max-w-md">{{ $p->title }}</span>
    </nav>

    <!-- Top Header Card (WP Matching) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-2 text-xs font-semibold text-slate-500">
                <span class="px-2.5 py-1 text-xs font-bold rounded-lg text-white {{ $p->type === 'land' ? 'bg-emerald-600' : ($p->type === 'land_share' ? 'bg-purple-600' : ($p->type === 'commercial' ? 'bg-indigo-600' : 'bg-teal-700')) }}">
                    {{ $p->type === 'land' ? '🏞️ জমি' : ($p->type === 'land_share' ? '🏢🤝 ল্যান্ড শেয়ার প্রজেক্ট' : ($p->type === 'commercial' ? '🏬 দোকান / কমার্শিয়াল স্পেস' : '🏢 ফ্ল্যাট')) }}
                </span>
                <span class="font-mono text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-300 font-bold">{{ $p->property_code }}</span>
                <span>•</span>
                <span class="bg-emerald-800 text-white font-bold px-2.5 py-1 rounded-md">📍 {{ !empty($p->area) ? $p->area : 'Bashundhara' }}</span>
                @if(!empty($p->sub_area))
                    <span>•</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-md text-slate-700 font-semibold">{{ $p->sub_area }}</span>
                @endif
                @if(!empty($p->block) && $p->isAddressVisible('block'))
                    <span>•</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-md text-slate-700 font-semibold">ব্লক {{ $p->block }}</span>
                @endif
                @if(!empty($p->sector) && $p->isAddressVisible('sector'))
                    <span>•</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-md text-slate-700 font-semibold">সেক্টর {{ $p->sector }}</span>
                @endif
                @if(!empty($p->road_number) && $p->isAddressVisible('road_number'))
                    <span>•</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-md text-slate-700 font-semibold">রোড #{{ $p->road_number }}</span>
                @endif
                @if(!empty($p->road_width_ft) && $p->isAddressVisible('road_width_ft'))
                    <span>•</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-md text-slate-700 font-semibold">{{ $p->road_width_ft }} ফুট রাস্তা</span>
                @endif
                @if(!empty($p->plot_number) && $p->isAddressVisible('plot_number'))
                    <span>•</span>
                    <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-md font-bold">প্লট #{{ $p->plot_number }}</span>
                @endif
                @if(!empty($p->views_count) && (int) $p->views_count > 0)
                    <span>•</span>
                    <!-- Views Count Badge -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gradient-to-r from-rose-500/10 via-amber-500/15 to-orange-500/10 border border-rose-400/40 rounded-full shadow-xs text-rose-800 font-extrabold text-xs">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                        </span>
                        <span class="text-xs">👁️</span>
                        <span>{{ \App\Models\Property::toBnNumber($p->views_count) }} বার দেখা হয়েছে</span>
                    </span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight m-0 tracking-tight">
                {{ $p->title }}
            </h1>
        </div>

        <div class="text-left md:text-right shrink-0">
            <div class="text-3xl sm:text-4xl font-black text-emerald-700 tracking-tight">
                {{ $p->formatted_price }}
            </div>
            <div class="text-xs font-semibold text-slate-500 mt-1 flex flex-wrap items-center justify-start md:justify-end gap-2">
                @if($p->type === 'land' && !empty($p->land))
                    <span class="font-bold text-slate-700">৳{{ number_format((float)($p->land->price_per_katha ?? 0), 0) }} / কাঠা</span>
                @elseif($p->type === 'commercial' && !empty($p->commercial))
                    <span class="font-bold text-indigo-700">৳{{ number_format((float)($p->commercial->price_per_sqft ?? 0), 0, '.', ',') }} / বর্গফুট</span>
                    @if(!empty($p->commercial->monthly_rent_yield))
                        <span>•</span>
                        <span class="text-slate-700 font-bold">ভাড়া আয়: ৳{{ number_format((float)$p->commercial->monthly_rent_yield, 0, '.', ',') }}/মাস</span>
                    @endif
                @elseif($p->type === 'flat' && !empty($p->flat))
                    <span class="font-bold text-slate-700">৳{{ number_format((float)($p->flat->price_per_sqft ?? 0), 0, '.', ',') }} / বর্গফুট</span>
                @elseif($p->type === 'land_share' && !empty($p->share))
                    <span class="font-bold text-purple-700">শেয়ার প্রজেক্ট (Land Share)</span>
                    @if(!empty($p->share->land_size_katha))
                        <span>•</span>
                        <span class="text-slate-700 font-bold">{{ $p->share->land_size_katha }} কাঠা জমি</span>
                    @endif
                @endif
                @if((float)$p->raw_price > 0)
                    <span>•</span>
                    <span class="{{ !empty($p->is_negotiable) ? 'text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-300 font-bold' : 'text-slate-700 bg-slate-100 px-2 py-0.5 rounded font-bold' }}">
                        {{ !empty($p->is_negotiable) ? '💬 আলোচনা সাপেক্ষ' : '🔒 ফিক্সড প্রাইস' }}
                    </span>
                @endif
            </div>
            <!-- Quick Social Share Strip in Header -->
            <div class="mt-3 flex flex-wrap items-center justify-start md:justify-end gap-1.5">
                <span class="text-[11px] font-bold text-slate-400 mr-1">শেয়ার করুন:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedShareUrl }}"
                   target="_blank" rel="noopener noreferrer"
                   @click="trackShare('facebook')"
                   title="Facebook-এ শেয়ার করুন"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#1877F2]/10 hover:bg-[#1877F2] text-[#1877F2] hover:text-white text-[11px] font-extrabold transition">
                    <span>📘 Facebook</span>
                </a>
                <a href="https://api.whatsapp.com/send?text={{ $encodedShareText }}"
                   target="_blank" rel="noopener noreferrer"
                   @click="trackShare('whatsapp')"
                   title="WhatsApp-এ শেয়ার করুন"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#25D366]/10 hover:bg-[#25D366] text-[#128C7E] hover:text-white text-[11px] font-extrabold transition">
                    <span>💬 WhatsApp</span>
                </a>
                <a href="https://t.me/share/url?url={{ $encodedShareUrl }}&text={{ urlencode($shareTitle) }}"
                   target="_blank" rel="noopener noreferrer"
                   @click="trackShare('telegram')"
                   title="Telegram-এ শেয়ার করুন"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#0088cc]/10 hover:bg-[#0088cc] text-[#0088cc] hover:text-white text-[11px] font-extrabold transition">
                    <span>✈️ Telegram</span>
                </a>
                <button type="button"
                        @click="copyPropertyLink()"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-800 text-slate-700 hover:text-white text-[11px] font-extrabold transition cursor-pointer">
                    <span x-text="copiedLink ? '✅ কপি হয়েছে!' : '🔗 লিংক কপি'"></span>
                </button>
                <button type="button"
                        @click="nativeShareProperty()"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white text-[11px] font-extrabold transition cursor-pointer">
                    <span>📤 আরও</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Grid: Left Media & Specs (8 Cols), Right Contact & Booking Card (4 Cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Column -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Image Gallery with Fullscreen Lightbox Trigger -->
            <div class="bg-white rounded-3xl border border-slate-200 p-2 sm:p-3 overflow-hidden shadow-xs space-y-3">
                <div class="aspect-16/10 rounded-2xl overflow-hidden bg-slate-100 relative group cursor-zoom-in"
                     @click="openLightbox(activeImage)">
                    <img :src="activeImage"
                         alt="{{ $p->title }}"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.02]">

                    <!-- Photo Counter & Fullscreen Button Overlay -->
                    <div class="absolute bottom-3 right-3 flex items-center gap-2">
                        <span class="px-3 py-1.5 rounded-xl bg-slate-900/75 backdrop-blur-xs text-white text-xs font-bold flex items-center gap-1.5 shadow-md">
                            <span>🔍</span>
                            <span>বড় করে দেখুন ({{ count($photoUrls) }}টি ছবি)</span>
                        </span>
                    </div>
                </div>

                <!-- Thumbnail Scroller -->
                @if(count($photoUrls) > 1)
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
                        @foreach($photoUrls as $idx => $imgUrl)
                            <button type="button"
                                    @click="activeImage = '{{ $imgUrl }}'; lightboxIndex = {{ $idx }}"
                                    :class="activeImage === '{{ $imgUrl }}' ? 'ring-2 ring-emerald-500 opacity-100' : 'opacity-70 hover:opacity-100'"
                                    class="w-20 h-14 sm:w-24 sm:h-16 rounded-xl overflow-hidden shrink-0 transition-all border border-slate-200 cursor-pointer">
                                <img src="{{ $imgUrl }}" alt="{{ $p->title }} photo {{ $idx + 1 }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Video Tour Embed (if available) -->
            @if($videoEmbedUrl)
                <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-3">
                    <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span>🎬</span>
                        <span>প্রপার্টি ভিডিও ট্যুর (Video Tour)</span>
                    </h3>
                    <div class="aspect-video rounded-2xl overflow-hidden bg-slate-900 border border-slate-200">
                        <iframe src="{{ $videoEmbedUrl }}"
                                title="{{ $p->title }} Video Tour"
                                class="w-full h-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                loading="lazy"></iframe>
                    </div>
                </div>
            @endif

            <!-- Complete Property Technical Specifications Grid -->
            <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2 m-0">
                        <span>📋</span>
                        <span>বিস্তারিত টেকনিক্যাল স্পেসিফিকেশন</span>
                    </h2>
                    <span class="text-xs font-mono font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                        কোড: #{{ $p->property_code }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-4 gap-x-6 text-sm">
                    <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 block font-medium">প্রপার্টি ধরন</span>
                        <span class="font-bold text-slate-800">{{ $typeLabel }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                        <span class="text-xs text-slate-400 block font-medium">প্রধান এলাকা</span>
                        <span class="font-bold text-slate-800">{{ $p->area ?: 'আফতাবনগর' }}</span>
                    </div>
                    @if(!empty($p->sub_area))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">সাব-এরিয়া / জোন</span>
                            <span class="font-bold text-slate-800">{{ $p->sub_area }}</span>
                        </div>
                    @endif
                    @if($p->block && $p->isAddressVisible('block'))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">ব্লক</span>
                            <span class="font-bold text-slate-800">ব্লক {{ $p->block }}</span>
                        </div>
                    @endif
                    @if($p->sector && $p->isAddressVisible('sector'))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">সেক্টর</span>
                            <span class="font-bold text-slate-800">সেক্টর {{ $p->sector }}</span>
                        </div>
                    @endif
                    @if($p->road_number && $p->isAddressVisible('road_number'))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">রোড নম্বর</span>
                            <span class="font-bold text-slate-800">রোড #{{ $p->road_number }}</span>
                        </div>
                    @endif
                    @if($p->road_width_ft && $p->isAddressVisible('road_width_ft'))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">সামনের রাস্তা</span>
                            <span class="font-bold text-slate-800">{{ $p->road_width_ft }} ফুট প্রশস্ত</span>
                        </div>
                    @endif
                    @if($p->plot_number && $p->isAddressVisible('plot_number'))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">প্লট নম্বর</span>
                            <span class="font-bold text-emerald-700">প্লট #{{ $p->plot_number }}</span>
                        </div>
                    @endif
                    @if($p->facing)
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">ফেসিং / মুখ</span>
                            <span class="font-bold text-slate-800 capitalize">{{ $p->facing }} মুখী</span>
                        </div>
                    @endif

                    <!-- 1. LAND SPECIFICS -->
                    @if($p->type === 'land' && $p->land)
                        <div class="p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                            <span class="text-xs text-emerald-600 block font-medium">জমির আকার</span>
                            <span class="font-extrabold text-slate-900">{{ $p->land->katha_size }} কাঠা</span>
                        </div>
                        @if((float)$p->land->price_per_katha > 0)
                        <div class="p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                            <span class="text-xs text-emerald-600 block font-medium">কাঠা প্রতি মূল্য</span>
                            <span class="font-extrabold text-emerald-700">৳{{ number_format((float)$p->land->price_per_katha, 0) }}</span>
                        </div>
                        @endif
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">মাটি ভরাট স্ট্যাটাস</span>
                            <span class="font-bold text-slate-800 capitalize">
                                {{ $p->land->soil_filling_status === 'filled' ? '✅ মাটি ভরাটকৃত' : ($p->land->soil_filling_status === 'partial' ? '⏳ আংশিক ভরাট' : 'অভরাটকৃত') }}
                            </span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">কর্নার প্লট?</span>
                            <span class="font-bold {{ $p->land->is_corner ? 'text-emerald-600' : 'text-slate-600' }}">{{ $p->land->is_corner ? '✅ হ্যাঁ (Corner Plot)' : 'না' }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">লেক ভিউ / পার্ক ফেসিং</span>
                            <span class="font-bold text-slate-800">
                                {{ $p->land->is_lake_facing ? '🌊 লেক ভিউ' : ($p->land->is_park_facing ? '🌳 পার্ক ফেসিং' : 'সাধারণ ভিউ') }}
                            </span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">বাউন্ডারি দেয়াল</span>
                            <span class="font-bold {{ $p->land->boundary_wall_done ? 'text-emerald-600' : 'text-slate-600' }}">{{ $p->land->boundary_wall_done ? '🧱 সম্পন্ন' : 'করা হয়নি' }}</span>
                        </div>
                    @endif

                    <!-- 2. FLAT SPECIFICS -->
                    @if($p->type === 'flat' && $p->flat)
                        <div class="p-3 rounded-2xl bg-teal-50/50 border border-teal-100">
                            <span class="text-xs text-teal-600 block font-medium">ফ্ল্যাটের আকার</span>
                            <span class="font-extrabold text-slate-900">{{ (int)$p->flat->flat_size_sqft }} বর্গফুট (Sq.Ft)</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">বেডরুম ও বাথরুম</span>
                            <span class="font-bold text-slate-800">🛏️ {{ $p->flat->bedrooms }} বেড • 🚿 {{ $p->flat->bathrooms }} বাথ</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">বারান্দা (Balcony)</span>
                            <span class="font-bold text-slate-800">{{ $p->flat->balconies }}টি বারান্দা</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">ফ্লোর অবস্থান</span>
                            <span class="font-bold text-slate-800">{{ $p->flat->floor_number }}ম তলা (মোট {{ $p->flat->total_floors }} তলা)</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">নির্মাণ অবস্থা</span>
                            <span class="font-bold text-emerald-600">{{ $p->flat->completion_status === 'ready' ? '✅ রেডি ফ্ল্যাট (Ready)' : '🏗️ নির্মাণাধীন (Ongoing)' }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">কার পার্কিং</span>
                            <span class="font-bold text-slate-800">{{ $p->flat->parking_available ? '🚗 পার্কিং আছে' : 'পার্কিং নেই' }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">লিফট, জেনারেটর ও গ্যাস</span>
                            <span class="font-bold text-slate-800">
                                {{ $p->flat->has_lift ? '🛗 লিফট' : '' }}
                                {{ $p->flat->has_generator ? ' • ⚡ জেনারেটর' : '' }}
                                {{ $p->flat->has_gas ? ' • 🔥 গ্যাস' : '' }}
                            </span>
                        </div>
                        @if(!empty($p->flat->developer_name) || !empty($p->flat->project_name))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">প্রজেক্ট / ডেভেলপার</span>
                            <span class="font-bold text-slate-800">{{ $p->flat->project_name ?: $p->flat->developer_name }}</span>
                        </div>
                        @endif
                    @endif

                    <!-- 3. COMMERCIAL SPECIFICS -->
                    @if($p->type === 'commercial' && $p->commercial)
                        <div class="p-3 rounded-2xl bg-indigo-50/50 border border-indigo-100">
                            <span class="text-xs text-indigo-600 block font-medium">কমার্শিয়াল ধরন</span>
                            <span class="font-extrabold text-slate-900 capitalize">{{ $p->commercial->commercial_type ?: 'Shop / Space' }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-indigo-50/50 border border-indigo-100">
                            <span class="text-xs text-indigo-600 block font-medium">স্পেসের আকার</span>
                            <span class="font-extrabold text-slate-900">{{ (int)$p->commercial->size_sqft }} বর্গফুট (Sq.Ft)</span>
                        </div>
                        @if(!empty($p->commercial->market_name))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">মার্কেট / ভবনের নাম</span>
                            <span class="font-bold text-slate-800">{{ $p->commercial->market_name }}</span>
                        </div>
                        @endif
                        @if(!empty($p->commercial->shop_number))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">শপ / ইউনিট নম্বর</span>
                            <span class="font-bold text-slate-800">#{{ $p->commercial->shop_number }}</span>
                        </div>
                        @endif
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">ফ্লোর অবস্থান</span>
                            <span class="font-bold text-slate-800">{{ $p->commercial->floor_number }}ম তলা (মোট {{ $p->commercial->total_floors }} তলা)</span>
                        </div>
                        @if((float)$p->commercial->frontage_ft > 0 || (float)$p->commercial->ceiling_height_ft > 0)
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">ফ্রন্টেজ ও সিলিং হাইট</span>
                            <span class="font-bold text-slate-800">
                                {{ (float)$p->commercial->frontage_ft > 0 ? $p->commercial->frontage_ft . ' ফুট ফ্রন্ট' : '' }}
                                {{ (float)$p->commercial->ceiling_height_ft > 0 ? ' • ' . $p->commercial->ceiling_height_ft . ' ফুট উচ্চতা' : '' }}
                            </span>
                        </div>
                        @endif
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">কমার্শিয়াল সুবিধাসমূহ</span>
                            <span class="font-bold text-slate-800 text-xs">
                                {{ $p->commercial->has_lift ? '🛗 লিফট ' : '' }}
                                {{ $p->commercial->has_escalator ? '• 🎢 এস্কেলেটর ' : '' }}
                                {{ $p->commercial->has_central_ac ? '• ❄️ সেন্ট্রাল এসি ' : '' }}
                                {{ $p->commercial->has_fire_safety ? '• 🧯 ফায়ার সেফটি ' : '' }}
                                {{ $p->commercial->has_parking ? '• 🚗 পার্কিং' : '' }}
                            </span>
                        </div>
                    @endif

                    <!-- 4. LAND SHARE SPECIFICS -->
                    @if($p->type === 'land_share' && $p->share)
                        @if(!empty($p->share->project_name))
                        <div class="p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                            <span class="text-xs text-purple-600 block font-medium">প্রজেক্টের নাম</span>
                            <span class="font-extrabold text-slate-900">{{ $p->share->project_name }}</span>
                        </div>
                        @endif
                        <div class="p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                            <span class="text-xs text-purple-600 block font-medium">মোট জমির পরিমাণ</span>
                            <span class="font-extrabold text-slate-900">{{ $p->share->land_size_katha }} কাঠা</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                            <span class="text-xs text-purple-600 block font-medium">প্রাপ্ত ফ্ল্যাটের আকার</span>
                            <span class="font-extrabold text-slate-900">{{ (int)$p->share->flat_size_sqft }} বর্গফুট</span>
                        </div>
                        @if(!empty($p->share->building_stories))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">ভবনের উচ্চতা</span>
                            <span class="font-bold text-slate-800">{{ $p->share->building_stories }}</span>
                        </div>
                        @endif
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">রুম বিন্যাস</span>
                            <span class="font-bold text-slate-800">🛏️ {{ $p->share->bedrooms }} বেড • 🚿 {{ $p->share->bathrooms }} বাথ • {{ $p->share->balconies }} বারান্দা</span>
                        </div>
                        @if((int)$p->share->total_shares > 0)
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">শেয়ার সংখ্যা</span>
                            <span class="font-bold text-emerald-700">মোট {{ $p->share->total_shares }}টি (বাকি {{ $p->share->available_shares }}টি)</span>
                        </div>
                        @endif
                        @if(!empty($p->share->current_progress))
                        <div class="p-3 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <span class="text-xs text-slate-400 block font-medium">বর্তমান অগ্রগতি</span>
                            <span class="font-bold text-slate-800">{{ $p->share->current_progress }}</span>
                        </div>
                        @endif
                    @endif
                </div>

                <!-- Other Features Pills -->
                @php
                    $normalizedFeatures = [];
                    if (!empty($p->other_features) && is_array($p->other_features)) {
                        foreach ($p->other_features as $fKey => $feat) {
                            if (is_array($feat)) {
                                if (isset($feat['checked']) && !$feat['checked']) {
                                    continue;
                                }
                                $fText = trim((string) ($feat['label'] ?? ($feat['name'] ?? ($feat['title'] ?? ''))));
                                if ($fText !== '') {
                                    $normalizedFeatures[] = $fText;
                                }
                            } elseif (is_bool($feat)) {
                                if ($feat && is_string($fKey)) {
                                    $normalizedFeatures[] = $fKey;
                                }
                            } elseif (is_scalar($feat) && trim((string) $feat) !== '') {
                                $normalizedFeatures[] = trim((string) $feat);
                            }
                        }
                    }
                @endphp
                @if(!empty($normalizedFeatures))
                    <div class="pt-4 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2.5">✨ অতিরিক্ত সুবিধাসমূহ (Other Features)</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($normalizedFeatures as $featLabel)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold">
                                    <span class="text-emerald-600">✓</span>
                                    <span>{{ $featLabel }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- 4-Point Physical & Document Verification Checklist Card (WP Parity) -->
            @php
                $ver = $p->verification;
                $ownerChecked = $ver ? (bool)$ver->owner_identity_checked : true;
                $locChecked   = $ver ? (bool)$ver->location_checked : true;
                $infoChecked  = $ver ? (bool)$ver->basic_info_checked : true;
                $docsChecked  = $ver ? (bool)$ver->basic_documents_checked : ($p->is_verified ?? false);
                $rawDocsList  = ($ver && is_array($ver->documents_available)) ? $ver->documents_available : [];
                $docsList     = [];
                foreach ($rawDocsList as $dKey => $docItem) {
                    if (is_array($docItem)) {
                        $dLabel = trim((string) ($docItem['label'] ?? ($docItem['name'] ?? '')));
                        if ($dLabel !== '') $docsList[] = $dLabel;
                    } elseif (is_bool($docItem)) {
                        if ($docItem && is_string($dKey)) $docsList[] = $dKey;
                    } elseif (is_scalar($docItem) && trim((string) $docItem) !== '') {
                        $docsList[] = trim((string) $docItem);
                    }
                }
            @endphp
            <div class="bg-gradient-to-br from-emerald-50/90 via-white to-teal-50/60 rounded-3xl border border-emerald-200 p-5 sm:p-6 shadow-xs space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-lg shadow-xs">🛡️</span>
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 m-0">৪-পয়েন্ট ফিজিক্যাল ও ডকুমেন্ট ভেরিফিকেশন রিপোর্ট</h3>
                            <p class="text-xs text-slate-500 m-0">PropertyKoi Field & Legal Verification Checklist</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-extrabold shadow-2xs">
                        ✓ প্ল্যাটফর্ম যাচাইকৃত
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border {{ $ownerChecked ? 'border-emerald-200' : 'border-slate-200' }}">
                        <span class="w-7 h-7 rounded-xl {{ $ownerChecked ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center font-black shrink-0">
                            {{ $ownerChecked ? '✓' : '⏳' }}
                        </span>
                        <div>
                            <div class="font-extrabold text-slate-900">১. মালিকানা ও পরিচয় যাচাই (Owner Identity)</div>
                            <div class="text-xs text-slate-500">{{ $ownerChecked ? 'সরাসরি মালিক/প্রতিনিধির পরিচয় নিশ্চিতকৃত' : 'প্রাথমিক যাচাই প্রক্রিয়াধীন' }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border {{ $locChecked ? 'border-emerald-200' : 'border-slate-200' }}">
                        <span class="w-7 h-7 rounded-xl {{ $locChecked ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center font-black shrink-0">
                            {{ $locChecked ? '✓' : '⏳' }}
                        </span>
                        <div>
                            <div class="font-extrabold text-slate-900">২. সরজমিনে লোকেশন পরিদর্শন (Site Location)</div>
                            <div class="text-xs text-slate-500">{{ $locChecked ? 'ব্লক, রোড ও প্লট অবস্থান ম্যাপ ও ফিল্ডে মিলানো হয়েছে' : 'ফিল্ড ভেরিফিকেশন প্রক্রিয়াধীন' }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border {{ $infoChecked ? 'border-emerald-200' : 'border-slate-200' }}">
                        <span class="w-7 h-7 rounded-xl {{ $infoChecked ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center font-black shrink-0">
                            {{ $infoChecked ? '✓' : '⏳' }}
                        </span>
                        <div>
                            <div class="font-extrabold text-slate-900">৩. মাপ ও বাজারমূল্য যাচাই (Specs & Pricing)</div>
                            <div class="text-xs text-slate-500">{{ $infoChecked ? 'জমির/ফ্ল্যাটের আকার ও আস্কিং প্রাইস যাচাইকৃত' : 'তথ্য যাচাই প্রক্রিয়াধীন' }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border {{ $docsChecked ? 'border-emerald-200' : 'border-slate-200' }}">
                        <span class="w-7 h-7 rounded-xl {{ $docsChecked ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center font-black shrink-0">
                            {{ $docsChecked ? '✓' : '⏳' }}
                        </span>
                        <div>
                            <div class="font-extrabold text-slate-900">৪. প্রাথমিক কাগজপত্র যাচাই (Basic Documents)</div>
                            <div class="text-xs text-slate-500">{{ $docsChecked ? 'মূল দলিল/বায়া দলিল ও নামজারি প্রাথমিক নিরীক্ষিত' : 'সাইট ভিজিটের সময় মূল কাগজ প্রদর্শিত হবে' }}</div>
                        </div>
                    </div>
                </div>

                @if(!empty($docsList))
                    <div class="pt-2 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-slate-600">📄 প্রাপ্ত ডকুমেন্টসমূহ:</span>
                        @foreach($docsList as $doc)
                            <span class="px-2.5 py-1 rounded-lg bg-white border border-emerald-200 text-emerald-800 text-xs font-bold">{{ $doc }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Description -->
            @if($p->description)
                <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-3">
                    <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span>📝</span>
                        <span>বিবরণ ও অন্যান্য তথ্য</span>
                    </h3>
                    <div class="text-sm sm:text-base text-slate-700 leading-relaxed whitespace-pre-line">
                        {{ $p->description }}
                    </div>
                </div>
            @endif

            <!-- Verified Direct Sources & Buyer Reviews Section (WP Parity) -->
            <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 m-0">
                            <span>🤝</span>
                            <span>ভেরিফাইড ডিরেক্ট সোর্স ও ক্রেতা রিভিউ</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5 m-0">দালাল বা চেইন ব্রোকার মুক্ত সরাসরি মালিক ও ভেরিফাইড সোর্স তথ্য</p>
                    </div>
                    <button type="button"
                            @click="openReviewModal('{{ $primarySource?->source_user_id ?? 1 }}', '{{ $isOwner ? 'owner' : 'agent' }}')"
                            class="px-4 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 font-extrabold text-xs transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                        <span>⭐</span>
                        <span>রেটিং ও রিভিউ দিন</span>
                    </button>
                </div>

                <!-- Direct Sources Cards -->
                @if($p->directSources->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($p->directSources as $src)
                            @php
                                $srcUser = $src->sourceUser;
                                $agentProf = $srcUser?->agentProfile;
                                $rating = $agentProf?->admin_fixed_rating ?? ($srcUser?->average_rating ?: 4.9);
                                $revCount = $srcUser?->review_count ?: max(1, count($initialReviews));
                                $penaltyStatus = $agentProf?->penalty_status ?? 'verified';
                            @endphp
                            <div class="p-4 rounded-2xl border {{ $penaltyStatus === 'blacklisted' ? 'bg-rose-50/50 border-rose-300' : 'bg-slate-50/80 border-slate-200' }} flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-extrabold {{ $src->source_type === 'direct_owner' ? 'bg-emerald-600 text-white' : 'bg-teal-700 text-white' }}">
                                            {{ $src->source_type === 'direct_owner' ? '👤 সরাসরি মালিক (Direct Owner)' : '🧑‍💼 ডিরেক্ট এজেন্ট (Verified Agent)' }}
                                        </span>
                                        @if($penaltyStatus === 'verified')
                                            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md">✓ Verified</span>
                                        @elseif($penaltyStatus === 'warning')
                                            <span class="text-[11px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-md">⚠️ পর্যবেক্ষণাধীন</span>
                                        @endif
                                    </div>
                                    <div class="font-extrabold text-slate-900 text-sm pt-1">
                                        {{ $src->relationship_declaration ?: 'সরাসরি প্রপার্টি সোর্স' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        🔒 নম্বর প্ল্যাটফর্ম কোঅর্ডিনেটর দ্বারা সুরক্ষিত
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 font-black text-xs">
                                        <span>⭐</span>
                                        <span>{{ number_format((float)$rating, 1) }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-semibold mt-1">{{ $revCount }}টি রিভিউ</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Buyer Reviews List -->
                <div class="space-y-3 pt-2">
                    <template x-if="reviewsList.length === 0">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center text-xs text-slate-500">
                            এখনো কোনো ক্রেতা রিভিউ জমা পড়েনি। সাইট ভিজিট করে থাকলে প্রথম রিভিউ দিন!
                        </div>
                    </template>
                    <template x-for="rev in reviewsList" :key="rev.id">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="font-extrabold text-slate-900 text-xs sm:text-sm flex items-center gap-2">
                                    <span>👤</span>
                                    <span x-text="rev.reviewer_name"></span>
                                </div>
                                <div class="flex items-center gap-1 text-amber-500 text-xs font-bold">
                                    <template x-for="i in 5" :key="i">
                                        <span x-text="i <= rev.rating ? '★' : '☆'"></span>
                                    </template>
                                    <span class="text-slate-400 text-[11px] ml-1" x-text="rev.created_at"></span>
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-600 m-0 leading-relaxed" x-text="rev.review_text"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- AEO (Answer Engine Optimization) & Property FAQ Accordion -->
            @if(!empty($seo['faqs']) && count($seo['faqs']) > 0)
                <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            🤖 AEO Instant Answers
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 mt-1.5 mb-0">
                            এই প্রপার্টি সম্পর্কে সচরাচর জিজ্ঞাসিত প্রশ্নোত্তর (FAQ)
                        </h3>
                    </div>

                    <div class="space-y-2.5" x-data="{ openFaq: 0 }">
                        @foreach($seo['faqs'] as $fIdx => $faqItem)
                            <div class="border border-slate-200 rounded-2xl overflow-hidden transition"
                                 :class="openFaq === {{ $fIdx }} ? 'bg-emerald-50/30 border-emerald-300' : 'bg-slate-50/60'">
                                <button type="button"
                                        @click="openFaq = openFaq === {{ $fIdx }} ? null : {{ $fIdx }}"
                                        class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left font-extrabold text-xs sm:text-sm text-slate-900 cursor-pointer">
                                    <span>{{ $faqItem['question'] ?? ($faqItem['q'] ?? '') }}</span>
                                    <span class="text-slate-400 font-bold transition-transform" :class="openFaq === {{ $fIdx }} ? 'rotate-180 text-emerald-600' : ''">⌄</span>
                                </button>
                                <div x-show="openFaq === {{ $fIdx }}" x-cloak class="px-4 pb-3.5 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                                    {{ $faqItem['answer'] ?? ($faqItem['a'] ?? '') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column: Lead CTA Card & Safety Tips -->
        <div class="hidden lg:block lg:col-span-4 space-y-6 lg:sticky lg:top-24">

            <!-- 4 Actions Card (Exact match with WP / User screenshot) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xl space-y-4">
                @if($p->views_count > 0)
                    <!-- Views Count Alert in Sidebar -->
                    <div class="flex items-center justify-center gap-2 py-2.5 px-3.5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl text-amber-950 text-xs font-bold shadow-2xs">
                        <span class="text-base">🔥</span>
                        <span>জনপ্রিয় লিস্টিং — </span>
                        <span class="font-extrabold text-rose-700 bg-white px-2 py-0.5 rounded-lg border border-amber-200 shadow-2xs">{{ $p->views_count }} বার দেখা হয়েছে</span>
                    </div>
                @endif

                <div class="text-center pb-2 border-b border-slate-100">
                    <div class="text-xs text-slate-500 font-medium">সরাসরি সাইট ভিজিট ও ডিল কোঅর্ডিনেশন</div>
                    <div class="text-xl font-black text-slate-900 mt-1">প্রপার্টিটি সরাসরি দেখতে চান?</div>
                </div>

                <!-- 1. Site Visit Scheduler Button -->
                <button type="button" @click="openScheduler()"
                        class="w-full py-3.5 text-white font-extrabold text-sm rounded-2xl shadow-lg transition flex items-center justify-center gap-1.5 cursor-pointer hover:brightness-105"
                        style="background-color: #00875a; color: #ffffff;">
                    <span>সাইট ভিজিট শিডিউল করুন</span>
                    <span>&rarr;</span>
                </button>

                <!-- 2. More Info Enquiry Button -->
                <button type="button" @click="openModal('more_info')"
                        class="w-full py-3 font-extrabold text-xs rounded-2xl transition cursor-pointer shadow-2xs hover:opacity-90"
                        style="background-color: #fdf2f7; border: 1.5px solid #d63384; color: #1e293b;">
                    আরও তথ্য জানতে চান?
                </button>

                <!-- 3. Make Offer Button -->
                @if(!empty($p->is_negotiable))
                <button type="button" @click="openModal('make_offer')"
                        class="w-full py-3 font-extrabold text-xs rounded-2xl transition cursor-pointer shadow-2xs hover:opacity-90"
                        style="background-color: #e6fffa; border: 1.5px solid #2dd4bf; color: #006655;">
                    দর প্রস্তাব বা অফার দিন
                </button>
                @endif

                <!-- 4. WhatsApp Direct Coordination Button -->
                <a href="{{ $waUrl }}" target="_blank" @click="trackWa()"
                   class="w-full flex items-center justify-center gap-2 py-3 text-white font-black text-xs rounded-2xl transition shadow-md cursor-pointer hover:brightness-105"
                   style="background-color: #25D366; color: #ffffff;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#ffffff" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                    <span style="color: #ffffff;">হোয়াটসঅ্যাপে যোগাযোগ করুন</span>
                </a>

                <div class="pt-3 border-t border-slate-100 text-center text-xs text-slate-400 space-y-1">
                    <div>🔒 মালিক বা এজেন্টের নম্বর চেইন ব্রোকার স্প্যাম মুক্ত রাখতে প্ল্যাটফর্ম সুরক্ষিত রাখে।</div>
                </div>

                <!-- Favorite / Shortlist & Compare Buttons -->
                @php
                    $isFavDb = auth()->check() ? auth()->user()->favorites()->where('property_id', $p->id)->exists() : false;
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                    <div x-data="{
                        isFav: {{ $isFavDb ? 'true' : 'false' }},
                        loadingFav: false,
                        init() {
                            const targetId = {{ (int) $p->id }};
                            let list = [];
                            try {
                                const a = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]');
                                const b = JSON.parse(localStorage.getItem('propertykoi_shortlist') || '[]');
                                list = Array.from(new Set([...a, ...b].map(Number).filter(Boolean)));
                            } catch(e) {}
                            if (list.includes(targetId)) {
                                this.isFav = true;
                            }
                            window.addEventListener('shortlist-updated', () => {
                                let cur = [];
                                try {
                                    const a = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]');
                                    const b = JSON.parse(localStorage.getItem('propertykoi_shortlist') || '[]');
                                    cur = Array.from(new Set([...a, ...b].map(Number).filter(Boolean)));
                                } catch(e) {}
                                this.isFav = cur.includes(targetId);
                            });
                        },
                        toggleFav() {
                            const targetId = {{ (int) $p->id }};
                            let list = [];
                            try {
                                const a = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]');
                                const b = JSON.parse(localStorage.getItem('propertykoi_shortlist') || '[]');
                                list = Array.from(new Set([...a, ...b].map(Number).filter(Boolean)));
                            } catch(e) {}
                            if (this.isFav) {
                                list = list.filter(id => id !== targetId);
                                this.isFav = false;
                            } else {
                                if (!list.includes(targetId)) list.push(targetId);
                                this.isFav = true;
                            }
                            try {
                                localStorage.setItem('aftabnagar_shortlist', JSON.stringify(list));
                                localStorage.setItem('propertykoi_shortlist', JSON.stringify(list));
                            } catch(e) {}
                            window.dispatchEvent(new CustomEvent('shortlist-updated'));

                            @auth
                            this.loadingFav = true;
                            fetch('{{ route('buyer.dashboard.fav.toggle') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ property_id: targetId })
                            })
                            .then(r => r.json())
                            .then(res => {
                                this.loadingFav = false;
                                if (res.success) this.isFav = res.is_favorite;
                            })
                            .catch(() => { this.loadingFav = false; });
                            @endauth
                        }
                    }">
                        <button type="button"
                                @click="toggleFav()"
                                :class="isFav ? 'bg-rose-50 border-rose-300 text-rose-600' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                class="w-full py-2.5 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                            <span x-text="isFav ? '❤️' : '🤍'"></span>
                            <span x-text="isFav ? 'শর্টলিস্টে আছে' : 'শর্টলিস্ট করুন'"></span>
                        </button>
                    </div>

                    <!-- Compare Button -->
                    <div x-data="{
                        isComp: false,
                        init() {
                            const targetId = {{ (int) $p->id }};
                            const check = () => {
                                let list = [];
                                try { list = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
                                this.isComp = list.includes(targetId);
                            };
                            check();
                            window.addEventListener('compare-updated', check);
                        },
                        toggleComp() {
                            const targetId = {{ (int) $p->id }};
                            let list = [];
                            try { list = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number); } catch(e) {}
                            if (this.isComp) {
                                list = list.filter(id => id !== targetId);
                                this.isComp = false;
                            } else {
                                if (list.length >= 4) {
                                    alert('সর্বোচ্চ ৪টি প্রপার্টি একসাথে তুলনা করা যাবে');
                                    return;
                                }
                                if (!list.includes(targetId)) list.push(targetId);
                                this.isComp = true;
                            }
                            try { localStorage.setItem('aftabnagar_compare', JSON.stringify(list)); } catch(e) {}
                            window.dispatchEvent(new CustomEvent('compare-updated'));
                        }
                    }">
                        <button type="button"
                                @click="toggleComp()"
                                :class="isComp ? 'bg-teal-50 border-teal-300 text-teal-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                class="w-full py-2.5 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                            <span>⚖️</span>
                            <span x-text="isComp ? 'তুলনায় যুক্ত আছে' : 'তুলনা করুন'"></span>
                        </button>
                    </div>
                </div>

                <!-- Sidebar Social Share Box -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <div class="text-[11px] font-bold text-slate-500 text-center">প্রপার্টিটি বন্ধু বা পরিবারের সাথে শেয়ার করুন</div>
                    <div class="grid grid-cols-4 gap-1.5">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedShareUrl }}"
                           target="_blank" rel="noopener noreferrer"
                           @click="trackShare('facebook')"
                           class="py-2 rounded-xl bg-[#1877F2]/10 hover:bg-[#1877F2] text-[#1877F2] hover:text-white text-[11px] font-bold text-center transition">
                            Facebook
                        </a>
                        <a href="https://api.whatsapp.com/send?text={{ $encodedShareText }}"
                           target="_blank" rel="noopener noreferrer"
                           @click="trackShare('whatsapp')"
                           class="py-2 rounded-xl bg-[#25D366]/10 hover:bg-[#25D366] text-[#128C7E] hover:text-white text-[11px] font-bold text-center transition">
                            WhatsApp
                        </a>
                        <a href="https://t.me/share/url?url={{ $encodedShareUrl }}&text={{ urlencode($shareTitle) }}"
                           target="_blank" rel="noopener noreferrer"
                           @click="trackShare('telegram')"
                           class="py-2 rounded-xl bg-[#0088cc]/10 hover:bg-[#0088cc] text-[#0088cc] hover:text-white text-[11px] font-bold text-center transition">
                            Telegram
                        </a>
                        <button type="button"
                                @click="copyPropertyLink()"
                                class="py-2 rounded-xl bg-slate-100 hover:bg-slate-800 text-slate-700 hover:text-white text-[11px] font-bold text-center transition cursor-pointer">
                            <span x-text="copiedLink ? 'কপিড!' : 'Copy'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Public Safety Tips Card (Connected to Settings Tab 5 adm_safety_tips) -->
            <div class="bg-gradient-to-br from-amber-50 to-orange-50/40 rounded-3xl p-5 border border-amber-200/80 shadow-xs space-y-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛡️</span>
                    <h3 class="font-extrabold text-slate-900 text-sm m-0">নিরাপদ লেনদেনের টিপস</h3>
                </div>
                <ul class="text-xs text-slate-600 space-y-2 list-none p-0 m-0">
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-600 font-bold shrink-0">✓</span>
                        <span>সরাসরি সাইট পরিদর্শন ও মালিকানার মূল দলিল যাচাই করুন।</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-600 font-bold shrink-0">✓</span>
                        <span>অগ্রিম কোনো আর্থিক লেনদেন করা থেকে বিরত থাকুন।</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-600 font-bold shrink-0">✓</span>
                        <span>পাবলিক স্থানে বা আমাদের ভেরিফাইড চ্যানেলে মিটিং করুন।</span>
                    </li>
                </ul>
                <button type="button" @click="safetyTipsModalOpen = true" class="w-full mt-1 py-2.5 px-3 bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer">
                    <span>📖 সকল নিরাপত্তা নির্দেশিকা পড়ুন</span>
                </button>
            </div>

        </div>

        <!-- Mobile Floating Contact Drawer Button (Right Edge) -->
        <div class="fixed right-0 top-[35%] -translate-y-1/2 z-40 lg:hidden">
            <button type="button" 
                    @click="contactDrawerOpen = true" 
                    class="flex flex-col items-center justify-center gap-1.5 bg-gradient-to-b from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold text-xs py-3.5 px-2 rounded-l-2xl shadow-2xl transition-all duration-300 hover:pl-3 cursor-pointer border-y border-l border-emerald-400/30">
                <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span style="writing-mode: vertical-rl; text-orientation: mixed;" class="tracking-wider text-[11px] font-bold py-1">যোগাযোগ করুন</span>
            </button>
        </div>

        <!-- Mobile Contact Modal Dialog (Drawer) -->
        <div x-show="contactDrawerOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="contactDrawerOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs lg:hidden" 
             style="display: none;">
            <div @click.away="contactDrawerOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="scale-95 opacity-0"
                 x-transition:enter-end="scale-100 opacity-100"
                 class="bg-white rounded-3xl max-w-sm w-full p-5 space-y-4 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📞</span>
                        <span class="font-extrabold text-slate-900 text-sm">যোগাযোগ ও কোঅর্ডিনেশন</span>
                    </div>
                    <button type="button" @click="contactDrawerOpen = false" class="text-slate-400 hover:text-slate-800 text-2xl font-bold leading-none p-1 cursor-pointer">&times;</button>
                </div>

                @if($p->views_count > 0)
                    <!-- Views Count Alert in Drawer -->
                    <div class="flex items-center justify-center gap-2 py-2 px-3 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl text-amber-950 text-xs font-bold shadow-2xs">
                        <span class="text-sm">🔥</span>
                        <span>জনপ্রিয় লিস্টিং — </span>
                        <span class="font-extrabold text-rose-700 bg-white px-1.5 py-0.5 rounded-lg border border-amber-200 shadow-2xs">{{ $p->views_count }} বার দেখা হয়েছে</span>
                    </div>
                @endif

                <div class="text-center pb-2">
                    <div class="text-[11px] text-slate-500 font-medium">সরাসরি সাইট ভিজিট ও ডিল কোঅর্ডিনেশন</div>
                    <div class="text-lg font-black text-slate-900 mt-0.5">প্রপার্টিটি সরাসরি দেখতে চান?</div>
                </div>

                <!-- 1. Site Visit Scheduler Button -->
                <button type="button" @click="contactDrawerOpen = false; openScheduler()"
                        class="w-full py-3.5 text-white font-extrabold text-sm rounded-2xl shadow-lg transition flex items-center justify-center gap-1.5 cursor-pointer hover:brightness-105"
                        style="background-color: #00875a; color: #ffffff;">
                    <span>সাইট ভিজিট শিডিউল করুন</span>
                    <span>&rarr;</span>
                </button>

                <!-- 2. More Info Enquiry Button -->
                <button type="button" @click="contactDrawerOpen = false; openModal('more_info')"
                        class="w-full py-3 font-extrabold text-xs rounded-2xl transition cursor-pointer shadow-2xs hover:opacity-90"
                        style="background-color: #fdf2f7; border: 1.5px solid #d63384; color: #1e293b;">
                    আরও তথ্য জানতে চান?
                </button>

                <!-- 3. Make Offer Button -->
                @if(!empty($p->is_negotiable))
                <button type="button" @click="contactDrawerOpen = false; openModal('make_offer')"
                        class="w-full py-3 font-extrabold text-xs rounded-2xl transition cursor-pointer shadow-2xs hover:opacity-90"
                        style="background-color: #e6fffa; border: 1.5px solid #2dd4bf; color: #006655;">
                    দর প্রস্তাব বা অফার দিন
                </button>
                @endif

                <!-- 4. WhatsApp Direct Coordination Button -->
                <a href="{{ $waUrl }}" target="_blank" @click="trackWa()"
                   class="w-full flex items-center justify-center gap-2 py-3 text-white font-black text-xs rounded-2xl transition shadow-md cursor-pointer hover:brightness-105"
                   style="background-color: #25D366; color: #ffffff;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#ffffff" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                    <span style="color: #ffffff;">হোয়াটসঅ্যাপে যোগাযোগ করুন</span>
                </a>

                <div class="pt-3 border-t border-slate-100 text-center text-[11px] text-slate-400">
                    🔒 মালিক বা এজেন্টের নম্বর চেইন ব্রোকার স্প্যাম মুক্ত রাখতে প্ল্যাটফর্ম সুরক্ষিত রাখে।
                </div>
            </div>
        </div>

        <!-- Appointment Scheduler Modal -->
        <div x-show="schedulerModalOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="schedulerModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" 
             style="display: none;">
            <div @click.away="schedulerModalOpen = false"
                 class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 space-y-5 shadow-2xl relative max-h-[92vh] overflow-y-auto">
                <button type="button" @click="schedulerModalOpen = false" class="absolute top-5 right-5 text-slate-400 hover:text-slate-800 text-2xl font-bold cursor-pointer">&times;</button>

                <!-- Pre-submission UI -->
                <div x-show="!bookingSubmitted" class="space-y-5">
                    <div class="border-b pb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📅</span>
                            <h3 class="font-black text-slate-900 text-lg m-0">সাইট ভিজিট শিডিউল করুন</h3>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 m-0">
                            আপনার সুবিধাজনক তারিখ ও সময় স্লট (৩০ মিনিট) বেছে নিন। আমাদের টিম ভিজিট সমন্বয় করবে।
                        </p>
                        <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 inline-block">
                            📍 প্রপার্টি: {{ $p->title }} (কোড: {{ $p->property_code }})
                        </div>
                    </div>

                    <!-- Month Calendar -->
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="prevMonth()" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer">
                                &larr; পূর্ববর্তী
                            </button>
                            <h4 class="font-extrabold text-sm text-slate-900 m-0" x-text="getMonthName(schedMonth) + ' ' + schedYear"></h4>
                            <button type="button" @click="nextMonth()" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer">
                                পরবর্তী &rarr;
                            </button>
                        </div>

                        <!-- Days of Week -->
                        <div class="grid grid-cols-7 text-center text-[11px] font-bold text-slate-400 uppercase">
                            <div>সোম</div>
                            <div>মঙ্গল</div>
                            <div>বুধ</div>
                            <div>বৃহঃ</div>
                            <div>শুক্র</div>
                            <div>শনি</div>
                            <div>রবি</div>
                        </div>

                        <!-- Loading State -->
                        <div x-show="schedLoading" class="text-center py-6 text-xs text-slate-500 font-semibold">
                            স্লট তথ্য লোড হচ্ছে...
                        </div>

                        <!-- Calendar Grid -->
                        <div x-show="!schedLoading" class="grid grid-cols-7 gap-1 text-center text-xs">
                            <template x-for="(d, idx) in getCalendarDays()" :key="idx">
                                <div>
                                    <template x-if="d.blank">
                                        <div class="h-11"></div>
                                    </template>
                                    <template x-if="!d.blank">
                                        <button type="button"
                                                @click="selectDate(d.dateStr)"
                                                :disabled="d.isPast || !d.hasSlots"
                                                :class="{
                                                    'bg-slate-900 text-white font-extrabold ring-2 ring-emerald-500 shadow-md': selectedDate === d.dateStr,
                                                    'bg-emerald-100 text-emerald-900 font-extrabold hover:bg-emerald-200 border border-emerald-300 cursor-pointer': selectedDate !== d.dateStr && d.hasSlots,
                                                    'bg-slate-100 text-slate-300 cursor-not-allowed': d.isPast || !d.hasSlots
                                                }"
                                                class="w-full h-11 rounded-xl flex flex-col items-center justify-center transition p-0.5">
                                            <span class="text-xs" x-text="d.day"></span>
                                            <template x-if="d.hasSlots">
                                                <span class="text-[8px] font-bold" :class="selectedDate === d.dateStr ? 'text-emerald-300' : 'text-emerald-700'" x-text="d.slotsCount + ' স্লট'"></span>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Legend -->
                        <div class="flex flex-wrap items-center justify-center gap-3 text-[10px] text-slate-500 pt-1">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-200 border border-emerald-400"></span> ফাঁকা স্লট আছে</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-slate-900"></span> নির্বাচিত তারিখ</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span> অনুপলব্ধ</span>
                        </div>
                    </div>

                    <!-- Step 2: Available 30-min Slots for Selected Date -->
                    <div x-show="selectedDate" class="space-y-2">
                        <label class="block font-extrabold text-slate-800 text-xs uppercase">
                            ⏰ সময় স্লট বেছে নিন (তারিখ: <span class="text-emerald-600 font-mono" x-text="selectedDate"></span>):
                        </label>
                        <template x-if="slotsByDate[selectedDate] && slotsByDate[selectedDate].length > 0">
                            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                                <template x-for="(slot, sIdx) in slotsByDate[selectedDate]" :key="sIdx">
                                    <button type="button"
                                            @click="selectedSlot = slot"
                                            :class="selectedSlot === slot ? 'bg-emerald-600 text-white font-extrabold shadow-sm' : 'bg-white hover:bg-emerald-50 text-slate-800 border border-slate-200'"
                                            class="py-2 px-2 text-xs rounded-xl font-mono transition text-center cursor-pointer">
                                        <span x-text="slot"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                        <template x-if="!slotsByDate[selectedDate] || slotsByDate[selectedDate].length === 0">
                            <div class="text-xs text-rose-500 font-bold p-3 bg-rose-50 rounded-xl">
                                এই তারিখে কোনো স্লট ফাঁকা নেই। অন্য কোনো তারিখ বেছে নিন।
                            </div>
                        </template>
                    </div>

                    <!-- Step 3: Buyer Contact Form -->
                    <div x-show="selectedDate && selectedSlot" class="space-y-3 pt-2 border-t border-slate-100">
                        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-950 font-bold flex items-center justify-between">
                            <span>নির্বাচিত শিডিউল:</span>
                            <span class="font-mono text-emerald-800" x-text="selectedDate + ' (' + selectedSlot + ')'"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 text-xs uppercase mb-1">আপনার নাম *</label>
                                <input type="text" x-model="buyerName" placeholder="যেমন: তানভীর আহমেদ" class="w-full border rounded-xl px-3 py-2 text-xs bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 text-xs uppercase mb-1">ফোন নম্বর *</label>
                                <input type="tel" x-model="buyerPhone" placeholder="০১৭১১-০০০০০" class="w-full border rounded-xl px-3 py-2 text-xs bg-white">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-xs uppercase mb-1">বিশেষ নোট বা বার্তা (ঐচ্ছিক)</label>
                            <textarea x-model="buyerMessage" placeholder="কোনো বিশেষ চাওয়া বা গাড়ি পার্কিং লাগবে কিনা লিখুন..." class="w-full border rounded-xl px-3 py-2 text-xs h-16 bg-white"></textarea>
                        </div>

                        <button type="button"
                                @click="submitBooking()"
                                :disabled="bookingSubmitting"
                                class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-sm rounded-2xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
                            <span x-show="!bookingSubmitting">শিডিউল নিশ্চিত করুন &rarr;</span>
                            <span x-show="bookingSubmitting">প্রসেসিং হচ্ছে...</span>
                        </button>
                    </div>
                </div>

                <!-- Post-submission Success State -->
                <div x-show="bookingSubmitted" class="text-center py-6 space-y-4" style="display: none;">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl font-black">✓</div>
                    <h3 class="font-black text-slate-900 text-xl m-0">সাইট ভিজিট রিকোয়েস্ট গৃহীত হয়েছে!</h3>
                    <p class="text-xs text-slate-600 leading-relaxed max-w-sm mx-auto" x-text="bookingMessage"></p>
                    <div class="pt-2">
                        <button type="button" @click="schedulerModalOpen = false; bookingSubmitted = false" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                            বন্ধ করুন
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enquiry Modal (More Info / Make Offer) -->
        <div x-show="visitModalOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="visitModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" 
             style="display: none;">
            <div @click.away="visitModalOpen = false"
                 class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
                <button type="button" @click="visitModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 text-xl font-bold cursor-pointer">&times;</button>

                <div x-show="!leadSubmitted">
                    <h3 class="font-extrabold text-slate-900 text-lg mb-1" x-text="modalTitle"></h3>
                    <p class="text-xs text-slate-500 mb-4" x-text="modalSubtitle"></p>

                    <form @submit.prevent="submitLead()" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">আপনার নাম *</label>
                            <input type="text" x-model="formData.buyer_name" required placeholder="যেমন: তানভীর আহমেদ" class="w-full border rounded-xl px-3 py-2 bg-white text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">ফোন নম্বর *</label>
                            <input type="tel" x-model="formData.buyer_phone" required placeholder="০১৭১১-০০০০০" class="w-full border rounded-xl px-3 py-2 bg-white text-xs">
                        </div>

                        <!-- MORE INFO: specific question -->
                        <template x-if="formData.enquiry_type === 'more_info'">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">আপনার প্রশ্ন বা জিজ্ঞাসা *</label>
                                <textarea x-model="formData.message" required placeholder="যেমন: ফ্লোর প্ল্যান পাওয়া যাবে? গ্যাস/বিদ্যুৎ সুবিধা কেমন?" class="w-full border rounded-xl px-3 py-2 h-24 bg-white text-xs"></textarea>
                            </div>
                        </template>

                        <!-- MAKE OFFER: offered price + optional note -->
                        <template x-if="formData.enquiry_type === 'make_offer'">
                            <div class="space-y-3">
                                <div>
                                    <label class="block font-bold text-slate-700 uppercase mb-1">প্রস্তাবিত মূল্য (৳) *</label>
                                    <input type="number" x-model="formData.offered_price" required placeholder="যেমন: ৩৫০০০০০" min="1" class="w-full border rounded-xl px-3 py-2 bg-white text-xs">
                                    <p class="text-slate-400 mt-1 text-[10px]">টাকায় সংখ্যা লিখুন, কমা ছাড়া।</p>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 uppercase mb-1">অতিরিক্ত মন্তব্য (ঐচ্ছিক)</label>
                                    <textarea x-model="formData.message" placeholder="যেমন: সম্পূর্ণ ডাউনপেমেন্টে নিতে আগ্রহী..." class="w-full border rounded-xl px-3 py-2 h-20 bg-white text-xs"></textarea>
                                </div>
                            </div>
                        </template>

                        <button type="submit"
                                :disabled="leadSubmitting"
                                :class="formData.enquiry_type === 'make_offer' ? 'bg-teal-600 hover:bg-teal-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                                class="w-full py-3 text-white font-extrabold text-sm rounded-xl shadow-md transition cursor-pointer">
                            <span x-show="formData.enquiry_type === 'more_info'">প্রশ্ন পাঠান &rarr;</span>
                            <span x-show="formData.enquiry_type === 'make_offer'">অফার পাঠান &rarr;</span>
                        </button>
                    </form>
                </div>

                <div x-show="leadSubmitted" class="text-center py-6 space-y-4" style="display: none;">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl font-black">✓</div>
                    <h3 class="font-extrabold text-slate-900 text-lg m-0">রিকোয়েস্ট গ্রহণ করা হয়েছে!</h3>
                    <p class="text-xs text-slate-600 leading-relaxed" x-text="leadMessage"></p>
                    <div class="pt-2">
                        <button type="button" @click="visitModalOpen = false; leadSubmitted = false" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                            বন্ধ করুন
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Similar Properties in this Block/Area -->
    @if($similar->isNotEmpty())
        <div class="pt-8 border-t border-slate-200 space-y-4">
            <h3 class="text-xl font-black text-slate-900">
                একই এলাকায় আরও কিছু প্রপার্টি
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($similar as $simProp)
                    <x-property-card :property="$simProp" />
                @endforeach
            </div>
        </div>
    @endif

    <!-- Safety Tips Modal (Full Modal Connected to Settings Tab 5 adm_safety_tips) -->
    <div x-show="safetyTipsModalOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="safetyTipsModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative max-h-[90vh] flex flex-col"
             @click.outside="safetyTipsModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🛡️</span>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base m-0">নিরাপদ রিয়েল এস্টেট লেনদেন নির্দেশিকা</h3>
                        <p class="text-[11px] text-slate-500 m-0">Property Safety & Transaction Guidelines</p>
                    </div>
                </div>
                <button type="button" @click="safetyTipsModalOpen = false" class="text-slate-400 hover:text-slate-800 text-2xl font-bold leading-none cursor-pointer">&times;</button>
            </div>

            <div class="overflow-y-auto space-y-4 text-xs text-slate-700 leading-relaxed pr-1">
                <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200 text-amber-900 font-medium leading-relaxed">
                    ⚠️ যেকোনো জমি বা প্রোপার্টি ক্রয়ের পূর্বে কোনো প্রকার অনাকাঙ্ক্ষিত প্রতারণা বা জটিলতা এড়াতে নিচের নিরাপত্তা পরামর্শগুলো যত্নসহকারে অনুসরণ করুন।
                </div>
                <div class="space-y-3 whitespace-pre-line text-slate-800 font-normal leading-loose">
                    {!! nl2br(e(\App\Models\Setting::get('adm_safety_tips', "১. সরজমিনে প্রপার্টি পরিদর্শন করুন:\n   • চুক্তি চূড়ান্ত করার আগে নিজে বা পরিবারের অভিজ্ঞ কাউকে নিয়ে সরাসরি ফ্ল্যাট/প্লট ভিজিট করুন。\n\n২. মালিকানা ও আইনি কাগজপত্র যাচাই করুন:\n   • মূল দলিল, বায়া দলিল, খতিয়ান ও মিউটেশন পর্চা অভিজ্ঞ আইনজীবী দিয়ে যাচাই করিয়ে নিন。\n\n৩. যাচাই ব্যতীত অগ্রিম টাকা দেওয়া থেকে বিরত থাকুন:\n   • রেজিস্ট্রিকৃত আইনসম্মত বায়না চুক্তি ছাড়া কোনো প্রকার টাকা ট্রান্সফার করবেন না。\n\n৪. অবাস্তব কম দাম বা চটকদার অফার থেকে সতর্ক থাকুন。\n\n৫. ব্যক্তিগত ও আর্থিক তথ্যের নিরাপত্তা রক্ষা করুন。\n\n৬. প্ল্যাটফর্মের সহায়তা নিন ও সন্দেহজনক কিছু দেখলে জানান।"))) !!}
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end">
                <button type="button" @click="safetyTipsModalOpen = false" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer">
                    বুঝেছি, ধন্যবাদ
                </button>
            </div>
        </div>
    </div>

    <!-- Agent / Owner Star Rating & Review Submission Modal (WP Parity) -->
    <div x-show="reviewModalOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="reviewModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         style="display: none;">
        <div @click.away="reviewModalOpen = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
            <button type="button" @click="reviewModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 text-2xl font-bold cursor-pointer">&times;</button>

            <div x-show="!reviewSubmitted" class="space-y-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⭐</span>
                        <h3 class="font-extrabold text-slate-900 text-lg m-0">সোর্স রেটিং ও রিভিউ দিন</h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 m-0">
                        প্রপার্টি পরিদর্শন বা ডিল কোঅর্ডিনেশন নিয়ে আপনার অভিজ্ঞতা শেয়ার করুন।
                    </p>
                </div>

                <!-- Star Rating Selector -->
                <div class="p-3 rounded-2xl bg-amber-50/70 border border-amber-200 text-center space-y-1">
                    <span class="text-xs font-bold text-amber-900 block">রেটিং নির্বাচন করুন</span>
                    <div class="flex items-center justify-center gap-2 text-2xl">
                        <template x-for="star in [1,2,3,4,5]" :key="star">
                            <button type="button"
                                    @click="reviewForm.rating = star"
                                    class="transition-transform hover:scale-110 cursor-pointer"
                                    :class="star <= reviewForm.rating ? 'text-amber-500' : 'text-slate-300'">
                                ★
                            </button>
                        </template>
                    </div>
                    <span class="text-[11px] font-bold text-amber-700" x-text="reviewForm.rating + ' / 5 স্টার'"></span>
                </div>

                <form @submit.prevent="submitReviewForm()" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">আপনার নাম *</label>
                        <input type="text" x-model="reviewForm.reviewer_name" required placeholder="যেমন: মো: রাশেদুল ইসলাম" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-white text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">ফোন নম্বর (যাচাইয়ের জন্য, গোপন থাকবে) *</label>
                        <input type="tel" x-model="reviewForm.reviewer_phone" required placeholder="০১৭১১-০০০০০০" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-white text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">আপনার অভিজ্ঞতা ও মন্তব্য *</label>
                        <textarea x-model="reviewForm.review_text" required placeholder="প্রপার্টির অবস্থান, তথ্যের সত্যতা ও আচরণ কেমন ছিল লিখুন..." class="w-full border border-slate-200 rounded-xl px-3 py-2 h-24 bg-white text-xs"></textarea>
                    </div>
                    <button type="submit"
                            :disabled="reviewSubmitting"
                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-sm rounded-xl shadow-md transition cursor-pointer">
                        <span x-show="!reviewSubmitting">রিভিউ জমা দিন &rarr;</span>
                        <span x-show="reviewSubmitting">জমা হচ্ছে...</span>
                    </button>
                </form>
            </div>

            <div x-show="reviewSubmitted" class="text-center py-6 space-y-4" style="display: none;">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-2xl font-black">✓</div>
                <h3 class="font-extrabold text-slate-900 text-lg m-0">ধন্যবাদ!</h3>
                <p class="text-xs text-slate-600" x-text="reviewMessage"></p>
                <button type="button" @click="reviewModalOpen = false" class="px-6 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl cursor-pointer">
                    বন্ধ করুন
                </button>
            </div>
        </div>
    </div>

    <!-- Fullscreen Image Lightbox Modal (WP Parity) -->
    <div x-show="lightboxOpen" x-cloak
         @keydown.escape.window="lightboxOpen = false"
         @keydown.arrow-right.window="if(lightboxOpen) nextLightbox()"
         @keydown.arrow-left.window="if(lightboxOpen) prevLightbox()"
         class="fixed inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex flex-col justify-between p-4 select-none"
         style="display: none;">

        <!-- Top Controls -->
        <div class="flex items-center justify-between text-white max-w-6xl w-full mx-auto py-2">
            <div class="text-xs sm:text-sm font-bold flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-white/10 font-mono" x-text="(lightboxIndex + 1) + ' / ' + galleryImages.length"></span>
                <span class="truncate max-w-xs sm:max-w-lg">{{ $p->title }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="lightboxZoom = !lightboxZoom"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold transition cursor-pointer">
                    <span x-text="lightboxZoom ? '🔎 জুম আউট' : '🔍 জুম ইন'"></span>
                </button>
                <button type="button"
                        @click="lightboxOpen = false"
                        class="w-9 h-9 rounded-xl bg-white/10 hover:bg-rose-600 text-white text-xl font-bold flex items-center justify-center transition cursor-pointer">
                    &times;
                </button>
            </div>
        </div>

        <!-- Center Image & Prev/Next Arrows -->
        <div class="relative flex-1 flex items-center justify-center overflow-hidden my-2">
            <template x-if="galleryImages.length > 1">
                <button type="button"
                        @click="prevLightbox()"
                        class="absolute left-2 sm:left-6 z-10 w-11 h-11 rounded-full bg-slate-900/80 hover:bg-emerald-600 text-white text-xl font-black flex items-center justify-center shadow-lg transition cursor-pointer">
                    &#10094;
                </button>
            </template>

            <img :src="galleryImages[lightboxIndex]"
                 alt="{{ $p->title }}"
                 @click="lightboxZoom = !lightboxZoom"
                 :class="lightboxZoom ? 'scale-150 cursor-zoom-out' : 'scale-100 cursor-zoom-in'"
                 class="max-h-[75vh] max-w-[92vw] object-contain rounded-xl transition-transform duration-300 shadow-2xl">

            <template x-if="galleryImages.length > 1">
                <button type="button"
                        @click="nextLightbox()"
                        class="absolute right-2 sm:right-6 z-10 w-11 h-11 rounded-full bg-slate-900/80 hover:bg-emerald-600 text-white text-xl font-black flex items-center justify-center shadow-lg transition cursor-pointer">
                    &#10095;
                </button>
            </template>
        </div>

        <!-- Bottom Thumbnail Strip -->
        <div class="flex items-center justify-center gap-2 overflow-x-auto py-2 max-w-4xl mx-auto w-full no-scrollbar">
            <template x-for="(img, idx) in galleryImages" :key="idx">
                <button type="button"
                        @click="lightboxIndex = idx; activeImage = img; lightboxZoom = false"
                        :class="lightboxIndex === idx ? 'ring-2 ring-emerald-400 opacity-100 scale-105' : 'opacity-50 hover:opacity-90'"
                        class="w-16 h-12 rounded-lg overflow-hidden shrink-0 transition border border-white/20 cursor-pointer">
                    <img :src="img" class="w-full h-full object-cover">
                </button>
            </template>
        </div>
    </div>

</div>
@endsection
