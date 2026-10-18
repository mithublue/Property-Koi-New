@extends('layouts.admin')

@section('title', 'আফতাবনগর ডিরেক্ট — প্লাগইন সেটিংস')
@section('page_title', 'প্লাগইন মাস্টার সেটিংস')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .adm-tab-pill {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<script>
window.admSettingsConfig = {
    csrfToken: "{{ csrf_token() }}",
    routes: {
        testWatermark: "{{ route('admin.settings.test-watermark') }}",
        testCloudinary: "{{ route('admin.settings.test-cloudinary') }}",
        previewSheet: "{{ route('admin.settings.sheet.preview') }}",
        syncSheet: "{{ route('admin.settings.sheet.sync') }}",
        linkWhatsapp: "{{ route('admin.settings.sheet.link-whatsapp') }}",
        testPush: "{{ route('admin.settings.test-push') }}",
        testTelegram: "{{ route('admin.settings.test-telegram') }}",
        testEmail: "{{ route('admin.settings.test-email') }}",
        triggerCron: "{{ route('admin.settings.trigger-demand-cron') }}",
        pingIndexNow: "{{ route('admin.settings.ping-indexnow') }}",
        testFacebook: "{{ route('admin.settings.test-facebook') }}",
        testWpImport: "{{ route('admin.settings.wp-import.test') }}",
        startWpImport: "{{ route('admin.settings.wp-import.start') }}",
        downloadBridge: "{{ route('admin.settings.wp-import.download-bridge') }}"
    }
};

function admSettingsRoot() {
    var validTabs = ['general', 'media', 'sheets', 'notifications', 'access', 'messages', 'seo', 'facebook', 'penalty', 'wpimport'];
    var hashTab = window.location.hash ? window.location.hash.replace('#', '') : '';
    var urlTab = new URLSearchParams(window.location.search).get('tab');
    var defaultTab = '{{ $currentTab }}' || 'general';
    var chosen = (urlTab && validTabs.indexOf(urlTab) !== -1) ? urlTab : ((hashTab && validTabs.indexOf(hashTab) !== -1) ? hashTab : defaultTab);
    return {
        activeTab: chosen,
        setTab: function(tab) {
            this.activeTab = tab;
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, null, '#' + tab);
            }
        },
        init: function() {
            var self = this;
            window.addEventListener('hashchange', function() {
                var h = window.location.hash ? window.location.hash.replace('#', '') : '';
                if (validTabs.indexOf(h) !== -1) {
                    self.activeTab = h;
                }
            });
        }
    };
}
</script>

<div class="adm-admin-wrap wrap font-sans text-slate-800 antialiased p-2 sm:p-6 max-w-5xl mx-auto"
     x-data="admSettingsRoot()">

    <!-- Header Banner -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between bg-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white m-0">আফতাবনগর ডিরেক্ট — প্লাগইন সেটিংস</h1>
            <p class="text-xs text-slate-400 mt-1 m-0">ডিভেলপার মোড এবং প্লাগইন ডায়াগনস্টিকস কনফিগারেশন</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <button type="submit" form="adm-settings-form"
                    x-show="activeTab !== 'penalty' && activeTab !== 'wpimport'"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-lg transition flex items-center gap-2 cursor-pointer">
                <span>💾</span>
                <span>সেটিংস সেভ করুন</span>
            </button>
            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-xl text-xs font-mono font-bold shrink-0">ADM v1.3.0</span>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-2xl text-xs font-bold shadow-xs flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 font-bold ml-4">✕</button>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 p-4 bg-rose-100 border border-rose-300 text-rose-800 rounded-2xl text-xs font-bold shadow-xs flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-700 hover:text-rose-950 font-bold ml-4">✕</button>
        </div>
    @endif

    <!-- Settings Navigation Tabs Bar -->
    <div class="mb-6 flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200 scrollbar-thin">
        <button type="button"
                @click="setTab('general')"
                :class="activeTab === 'general' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>⚙️</span>
            <span>সাধারণ (General)</span>
        </button>
        <button type="button"
                @click="setTab('media')"
                :class="activeTab === 'media' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>🖼️</span>
            <span>ইমেজ ও মিডিয়া</span>
        </button>
        <button type="button"
                @click="setTab('sheets')"
                :class="activeTab === 'sheets' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>📊</span>
            <span>গুগল শিট সিঙ্ক</span>
        </button>
        <button type="button"
                @click="setTab('notifications')"
                :class="activeTab === 'notifications' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>🔔</span>
            <span>নোটিফিকেশন ও ফায়ারবেস</span>
        </button>
        <button type="button"
                @click="setTab('access')"
                :class="activeTab === 'access' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>🔑</span>
            <span>এক্সেস কন্ট্রোল ও গুগল লগইন</span>
        </button>
        <button type="button"
                @click="setTab('messages')"
                :class="activeTab === 'messages' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>💬</span>
            <span>মেসেজ টেমপ্লেট</span>
        </button>
        <button type="button"
                @click="setTab('seo')"
                :class="activeTab === 'seo' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>🔍</span>
            <span>SEO ও AEO (AI সার্চ)</span>
        </button>
        <button type="button"
                @click="setTab('facebook')"
                :class="activeTab === 'facebook' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>📱</span>
            <span>Facebook অটো-পোস্ট</span>
        </button>
        <button type="button"
                @click="setTab('penalty')"
                :class="activeTab === 'penalty' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>🛡️</span>
            <span>এজেন্ট পেনাল্টি</span>
        </button>
        <button type="button"
                @click="setTab('wpimport')"
                :class="activeTab === 'wpimport' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-indigo-700 border border-indigo-200'"
                class="adm-tab-pill px-4 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap flex items-center gap-2 cursor-pointer">
            <span>📥</span>
            <span>Live WP Import</span>
        </button>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl p-6 sm:p-8 space-y-6">
        <form id="adm-settings-form" method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="tab" :value="activeTab">

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 1: GENERAL SETTINGS -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'general'" x-cloak class="space-y-6">

                <!-- ══════════════════════════════════════════════════════════════════════════ -->
                <!-- 1.1 SITE BRANDING, LOGO & FAVICON CARD -->
                <!-- ══════════════════════════════════════════════════════════════════════════ -->
                <div class="p-6 bg-gradient-to-br from-emerald-50/70 via-white to-teal-50/40 rounded-3xl border border-emerald-200/90 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-emerald-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🎨</span>
                            <div>
                                <h2 class="text-base font-black text-slate-900 m-0">সাইট পরিচিতি, লোগো ও ফেভিকন (Site Branding)</h2>
                                <p class="text-[11px] text-slate-600 m-0 mt-0.5">সাইটের ব্র্যান্ড নাম, ট্যাগলাইন, হেডার লোগো, ফুটার লোগো ও ব্রাউজার ফেভিকন সরাসরি ব্যাকএন্ড থেকে আপলোড ও পরিবর্তন করুন।</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold shrink-0">
                            লাইভ কাস্টমাইজেশন
                        </span>
                    </div>

                    <!-- Site Title & Tagline -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase">সাইটের নাম / টাইটেল (Site Title):</label>
                            <input type="text" name="adm_site_name" value="{{ $site_name }}" placeholder="যেমন: PropertyKoi.com"
                                   class="w-full border rounded-xl px-3.5 py-2.5 bg-slate-50 font-bold text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                            <p class="text-[10px] text-slate-500 m-0">হেডার, ফুটার এবং ব্রাউজার ট্যাবে প্রদর্শিত হবে।</p>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase">সাইট ট্যাগলাইন (Tagline / Subtitle):</label>
                            <input type="text" name="adm_site_tagline" value="{{ $site_tagline }}" placeholder="যেমন: সরাসরি প্রপার্টি মার্কেটপ্লেস"
                                   class="w-full border rounded-xl px-3.5 py-2.5 bg-slate-50 font-bold text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                            <p class="text-[10px] text-slate-500 m-0">লোগোর নিচে এবং মেটা বর্ণনায় প্রদর্শিত হবে।</p>
                        </div>
                    </div>

                    <!-- Logos & Favicon Upload 3-Column Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                        <!-- 1. Primary Header Logo -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4 flex flex-col justify-between"
                             x-data="{ preview: '{{ $site_logo ? (str_starts_with($site_logo, 'http') ? $site_logo : asset($site_logo)) : '' }}' }">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-slate-900 uppercase flex items-center gap-1.5">
                                        <span>🖼️</span> হেডার লোগো (Header Logo)
                                    </span>
                                </div>

                                <!-- Preview Area -->
                                <div class="w-full h-24 bg-slate-100 rounded-xl border-2 border-dashed border-slate-300 flex items-center justify-center p-3 relative overflow-hidden group">
                                    <template x-if="preview">
                                        <img :src="preview" alt="Header Logo Preview" class="max-h-full max-w-full object-contain">
                                    </template>
                                    <template x-if="!preview">
                                        <div class="text-center text-slate-400 text-xs">
                                            <span class="block text-2xl mb-1">🏢</span>
                                            <span>কোনো লোগো নেই (ডিফল্ট আইকন ব্যবহৃত হবে)</span>
                                        </div>
                                    </template>
                                </div>

                                <!-- File Upload -->
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700">নতুন লোগো আপলোড করুন:</label>
                                    <input type="file" name="adm_site_logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                           @change="const f = $event.target.files[0]; if(f) preview = URL.createObjectURL(f)"
                                           class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                                </div>

                                <!-- Or URL Input -->
                                <div class="space-y-1 pt-1">
                                    <label class="block text-[10px] text-slate-500 font-bold">অথবা সরাসরি লোগো URL:</label>
                                    <input type="text" name="adm_site_logo" value="{{ $site_logo }}" x-model="preview" placeholder="https://.../logo.png"
                                           class="w-full border rounded-lg px-2.5 py-1.5 text-xs bg-slate-50 font-mono text-slate-800">
                                </div>
                            </div>

                            @if($site_logo)
                                <label class="flex items-center gap-2 pt-2 border-t border-slate-100 text-xs text-rose-600 font-bold cursor-pointer hover:text-rose-800">
                                    <input type="checkbox" name="adm_remove_site_logo" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                    <span>এই লোগো মুছে ডিফল্ট ফিরিয়ে নিন</span>
                                </label>
                            @endif
                        </div>

                        <!-- 2. Dark / Footer Logo -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4 flex flex-col justify-between"
                             x-data="{ previewDark: '{{ $site_logo_dark ? (str_starts_with($site_logo_dark, 'http') ? $site_logo_dark : asset($site_logo_dark)) : '' }}' }">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-slate-900 uppercase flex items-center gap-1.5">
                                        <span>🌙</span> ডার্ক/ফুটার লোগো (Dark Logo)
                                    </span>
                                </div>

                                <!-- Preview Area (Dark background for white logos) -->
                                <div class="w-full h-24 bg-slate-950 rounded-xl border-2 border-dashed border-slate-800 flex items-center justify-center p-3 relative overflow-hidden group">
                                    <template x-if="previewDark">
                                        <img :src="previewDark" alt="Footer Logo Preview" class="max-h-full max-w-full object-contain">
                                    </template>
                                    <template x-if="!previewDark">
                                        <div class="text-center text-slate-500 text-xs">
                                            <span class="block text-2xl mb-1">✨</span>
                                            <span>খালি থাকলে প্রাইমারি লোগো ব্যবহৃত হবে</span>
                                        </div>
                                    </template>
                                </div>

                                <!-- File Upload -->
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700">ফুটার লোগো আপলোড:</label>
                                    <input type="file" name="adm_site_logo_dark_file" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                           @change="const f = $event.target.files[0]; if(f) previewDark = URL.createObjectURL(f)"
                                           class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 cursor-pointer">
                                </div>

                                <!-- Or URL Input -->
                                <div class="space-y-1 pt-1">
                                    <label class="block text-[10px] text-slate-500 font-bold">অথবা সরাসরি ডার্ক লোগো URL:</label>
                                    <input type="text" name="adm_site_logo_dark" value="{{ $site_logo_dark }}" x-model="previewDark" placeholder="https://.../logo-white.png"
                                           class="w-full border rounded-lg px-2.5 py-1.5 text-xs bg-slate-50 font-mono text-slate-800">
                                </div>
                            </div>

                            @if($site_logo_dark)
                                <label class="flex items-center gap-2 pt-2 border-t border-slate-100 text-xs text-rose-600 font-bold cursor-pointer hover:text-rose-800">
                                    <input type="checkbox" name="adm_remove_site_logo_dark" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                    <span>ডার্ক লোগো মুছে ফেলুন</span>
                                </label>
                            @endif
                        </div>

                        <!-- 3. Site Favicon -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4 flex flex-col justify-between"
                             x-data="{ previewFav: '{{ $site_favicon ? (str_starts_with($site_favicon, 'http') ? $site_favicon : asset($site_favicon)) : asset('assets/icons/icon-192x192.png') }}' }">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-slate-900 uppercase flex items-center gap-1.5">
                                        <span>🌐</span> ব্রাউজার ফেভিকন (Favicon)
                                    </span>
                                </div>

                                <!-- Browser Tab Mockup Preview -->
                                <div class="w-full h-24 bg-slate-100 rounded-xl border border-slate-200 flex flex-col justify-center px-4 py-2">
                                    <div class="bg-white rounded-t-lg border-t border-x border-slate-300 px-3 py-1.5 flex items-center gap-2 shadow-xs max-w-full">
                                        <img :src="previewFav" alt="Favicon Preview" class="w-4 h-4 object-contain rounded shrink-0">
                                        <span class="text-[11px] font-bold text-slate-800 truncate" x-text="'{{ $site_name }}'"></span>
                                        <span class="text-slate-400 text-xs ml-auto">✕</span>
                                    </div>
                                    <div class="h-2 bg-white border-b border-x border-slate-300 rounded-b-lg"></div>
                                </div>

                                <!-- File Upload -->
                                <div class="space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700">ফেভিকন আপলোড (.ico, .png, .svg):</label>
                                    <input type="file" name="adm_site_favicon_file" accept=".ico,image/png,image/svg+xml,image/webp"
                                           @change="const f = $event.target.files[0]; if(f) previewFav = URL.createObjectURL(f)"
                                           class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer">
                                </div>

                                <!-- Or URL Input -->
                                <div class="space-y-1 pt-1">
                                    <label class="block text-[10px] text-slate-500 font-bold">অথবা ফেভিকন URL:</label>
                                    <input type="text" name="adm_site_favicon" value="{{ $site_favicon }}" x-model="previewFav" placeholder="https://.../favicon.png"
                                           class="w-full border rounded-lg px-2.5 py-1.5 text-xs bg-slate-50 font-mono text-slate-800">
                                </div>
                            </div>

                            @if($site_favicon)
                                <label class="flex items-center gap-2 pt-2 border-t border-slate-100 text-xs text-rose-600 font-bold cursor-pointer hover:text-rose-800">
                                    <input type="checkbox" name="adm_remove_site_favicon" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                    <span>কাস্টম ফেভিকন মুছে ফেলুন</span>
                                </label>
                            @endif
                        </div>

                    </div>

                    <!-- Contact Email, Phone & Footer About -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase">সাপোর্ট ইমেইল (Support Email):</label>
                            <input type="email" name="adm_site_email" value="{{ $site_email }}" placeholder="support@propertykoi.com"
                                   class="w-full border rounded-xl px-3.5 py-2 bg-slate-50 font-bold text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                            <p class="text-[10px] text-slate-500 m-0">ফুটার কন্টাক্ট সেকশনে প্রদর্শিত হবে।</p>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase">সাপোর্ট হটলাইন / ফোন (Hotline Phone):</label>
                            <input type="text" name="adm_site_phone" value="{{ $site_phone }}" placeholder="যেমন: +880 1711-000000"
                                   class="w-full border rounded-xl px-3.5 py-2 bg-slate-50 font-bold text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                            <p class="text-[10px] text-slate-500 m-0">ফুটার এবং হেল্পলাইন বাটনে শো করবে।</p>
                        </div>
                    </div>

                    <!-- Footer Description & Copyright Text -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase">ফুটার সম্পর্কে সংক্ষিপ্ত লেখা (Footer About Text):</label>
                            <textarea name="adm_site_footer_about" rows="3"
                                      class="w-full border rounded-xl p-3 bg-slate-50 text-xs text-slate-800 leading-relaxed focus:bg-white focus:ring-2 focus:ring-emerald-500">{{ $site_footer_about }}</textarea>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase">ফুটার কপিরাইট টেক্সট (Copyright Notice):</label>
                            <input type="text" name="adm_site_copyright" value="{{ $site_copyright }}" placeholder="© 2026 PropertyKoi.com. All rights reserved."
                                   class="w-full border rounded-xl px-3.5 py-2.5 bg-slate-50 font-medium text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500">
                            <p class="text-[10px] text-slate-500 m-0">ফুটারের সর্বনিম্নে কপিরাইট লাইনে প্রদর্শিত হবে।</p>
                        </div>
                    </div>
                </div>

                <!-- Developer Mode Toggle Card -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🛠️</span>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">ডিভেলপার মোড (Developer Mode)</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            ডিভেলপার মোড চালু রাখলে ফ্রন্টএন্ড প্রপার্টি পোস্ট ফর্মে <strong>"⚡ ১-ক্লিকে ডেমো ডাটা ফিলআপ"</strong> বাটনটি সক্রিয় হবে। এতে টেস্ট করার সময় এক ক্লিকেই জমি বা ফ্ল্যাটের সমস্ত টেস্ট ডাটা অটো-পপুলেট হয়ে যাবে।
                        </p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0" x-data="{ devMode: {{ $dev_mode ? 'true' : 'false' }} }">
                        <input type="checkbox" name="adm_developer_mode" value="1" x-model="devMode" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-3 text-xs font-bold text-slate-800" x-text="devMode ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)'"></span>
                    </label>
                </div>

                <!-- Admin WhatsApp Number Card -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">💬</span>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">অ্যাডমিন হোয়াটসঅ্যাপ (Admin WhatsApp)</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            এই নম্বরে প্রপার্টি বিস্তারিত পেজ থেকে ইউজাররা সরাসরি <strong>"যোগাযোগ করুন"</strong> বাটনে ক্লিক করে অ্যাডমিনের সাথে মেসেজে কথা বলতে পারবে। (যেমন: 01712345678)
                        </p>
                    </div>
                    <div class="shrink-0 w-full sm:w-64">
                        <input type="text" name="adm_admin_whatsapp_number" value="{{ $admin_wa }}" placeholder="e.g. 017xxxxxxxx" class="w-full border rounded-xl px-4 py-2 bg-white font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>
                </div>

                <!-- WhatsApp Login / Registration Toggle Card -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🟢</span>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">WhatsApp ৪-ডিজিট পিন লগইন ও সাইনআপ (WhatsApp Auth)</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            সক্রিয় থাকলে লগইন ও রেজিস্ট্রেশন পেজে ব্যবহারকারীরা WhatsApp নম্বর এবং ৪-ডিজিটের সহজ গোপন পিন (PIN) দিয়ে দ্রুত সাইনআপ বা লগইন করতে পারবেন। নিষ্ক্রিয় করলে শুধু সাধারণ ইমেইল ও পাসওয়ার্ড ব্যবস্থা কার্যকর থাকবে।
                        </p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0" x-data="{ waAuth: {{ $enable_wa_auth ? 'true' : 'false' }} }">
                        <input type="checkbox" name="adm_enable_whatsapp_auth" value="yes" x-model="waAuth" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-3 text-xs font-bold text-slate-800" x-text="waAuth ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)'"></span>
                    </label>
                </div>

                <!-- Auto Approve Site Visits Card -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📅</span>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">সাইট ভিজিট অটো-অনুমোদন (Auto Approve Site Visits)</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            এটি সক্রিয় থাকলে ব্যবহারকারী কোনো ফাঁকা স্লটে সাইট ভিজিট শিডিউল করলে তা অ্যাডমিনের ম্যানুয়াল অনুমোদন ছাড়াই সরাসরি নিশ্চিত (Approved) হয়ে যাবে এবং স্লটটি লক হয়ে যাবে। নিষ্ক্রিয় থাকলে অ্যাডমিন ম্যানুয়ালি অনুমোদন না করা পর্যন্ত তা Pending থাকবে।
                        </p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0" x-data="{ autoApprove: {{ $auto_approve_visits ? 'true' : 'false' }} }">
                        <input type="checkbox" name="adm_auto_approve_visits" value="yes" x-model="autoApprove" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-3 text-xs font-bold text-slate-800" x-text="autoApprove ? 'সক্রিয় (Auto Approve)' : 'নিষ্ক্রিয় (Manual Approval)'"></span>
                    </label>
                </div>

                <!-- Owner Identity Display Toggle Card -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">👤</span>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">মালিকের নাম ও পরিচয় প্রদর্শন (Show Owner Identity)</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            নিষ্ক্রিয় থাকলে ফ্রন্টএন্ড প্রপার্টি পেজের <strong>"Property Added By"</strong> সেকশনে মালিকের নাম ও পরিচয় সম্পূর্ণ গোপন থাকবে (শুধু যিনি পোস্ট করেছেন তার নাম ও রেটিং প্রদর্শিত হবে)। সক্রিয় করলে ব্রোকার/এজেন্টের নামের পাশে মালিকের নামও প্রদর্শিত হবে।
                        </p>
                    </div>

                    <label class="relative inline-flex items-center cursor-pointer shrink-0" x-data="{ showOwner: {{ $show_owner_identity ? 'true' : 'false' }} }">
                        <input type="checkbox" name="adm_show_owner_identity" value="yes" x-model="showOwner" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-3 text-xs font-bold text-slate-800" x-text="showOwner ? 'প্রদর্শন করুন (Show)' : 'গোপন রাখুন (Hidden - Recommended)'"></span>
                    </label>
                </div>

                <!-- Max Demands per User Card -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1 max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📋</span>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">চাহিদা সাবমিশন সর্বোচ্চ লিমিট (Max Demands per User)</h2>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed m-0">
                            একজন ব্যবহারকারী বা ক্রেতা সর্বোচ্চ কয়টি চাহিদা (Buyer Demand) সাবমিট করতে পারবেন। লগইন করা ইউজারের ক্ষেত্রে আইডি দিয়ে এবং লগআউট থাকা ইউজারদের ক্ষেত্রে ফোন/হোয়াটসঅ্যাপ, ইমেইল বা টেলিগ্রাম দিয়ে স্বয়ংক্রিয়ভাবে শনাক্ত করা হবে। নির্ধারিত সীমার অতিরিক্ত চাহিদা দিতে চাইলে পুরাতন কোনো চাহিদা ডিলিট করতে হবে।
                        </p>
                    </div>

                    <div class="shrink-0 w-full sm:w-44">
                        <div class="flex items-center gap-2">
                            <input type="number" name="adm_max_demands_per_user" min="1" max="100" value="{{ $max_demands_per_user ?? 3 }}"
                                   class="w-full border rounded-xl px-3.5 py-2.5 bg-white font-black text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 text-center shadow-sm">
                            <span class="text-xs font-bold text-slate-500 shrink-0">টি</span>
                        </div>
                    </div>
                </div>

                <!-- Additional System Diagnostics -->
                <div class="p-6 bg-slate-900 text-white rounded-2xl space-y-3 text-xs font-mono">
                    <div class="font-bold text-emerald-400 uppercase tracking-wider text-[11px]">সার্ভার এনভায়রনমেন্ট ডায়াগনস্টিকস:</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-300">
                        <div>• WebP সাপোর্টেড: <span class="text-emerald-400 font-bold">{{ function_exists('imagewebp') ? 'YES (Active)' : 'NO' }}</span></div>
                        <div>• ফাইল আপলোড সাইজ: <span class="text-amber-300">{{ ini_get('upload_max_filesize') }}</span></div>
                        <div>• মিডিয়া অটো-কনভার্সন: <span class="text-emerald-400 font-bold">WebP + Raw Auto-Delete</span></div>
                        <div>• ডাটাবেজ সংযোগ: <span class="text-emerald-400 font-bold">MySQL Live (Laravel Eloquent)</span></div>
                    </div>
                </div>

            </div><!-- /activeTab === 'general' -->

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 2: MEDIA & WATERMARK -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'media'" x-cloak class="space-y-6">

                <!-- Auto Image Watermark Protection Card -->
                <div class="p-6 bg-emerald-50/60 rounded-2xl border border-emerald-200 space-y-4" x-data="admWatermarkSettings()">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1 max-w-xl">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🛡️</span>
                                <h2 class="text-base font-extrabold text-slate-900 m-0">অটো ইমেজ ওয়াটারমার্ক (Auto Image Watermark Protection)</h2>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed m-0">
                                প্রপার্টি আপলোডের সাথে সাথে ছবিতে স্বয়ংক্রিয়ভাবে আধুনিক পিল-ব্যাজ ওয়াটারমার্ক যুক্ত হবে, যা অন্য ব্রোকারদের ছবি চুরি ও কপি করা থেকে রক্ষা করবে। এটি ক্লাউডিনারি ও লোকাল উভয় স্টোরেজেই কাজ করে।
                            </p>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="adm_watermark_enabled" value="yes" x-model="enabled" class="sr-only peer">
                            <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-3 text-xs font-bold text-slate-800" x-text="enabled ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)'"></span>
                        </label>
                    </div>

                    <div x-show="enabled" x-collapse class="pt-3 border-t border-emerald-200/60 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">ওয়াটারমার্ক টেক্সট (Text)</label>
                                <input type="text" name="adm_watermark_text" x-model="text" class="w-full border rounded-xl px-3.5 py-2 bg-white font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">অবস্থান (Position)</label>
                                <select name="adm_watermark_position" x-model="position" class="w-full border rounded-xl px-3.5 py-2 bg-white font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs">
                                    <option value="bottom-right">নিচে ডানপাশে (Bottom-Right - Recommended)</option>
                                    <option value="bottom-left">নিচে বামপাশে (Bottom-Left)</option>
                                    <option value="center">মাঝখানে (Center)</option>
                                    <option value="top-right">উপরে ডানপাশে (Top-Right)</option>
                                    <option value="top-left">উপরে বামপাশে (Top-Left)</option>
                                </select>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-bold text-slate-700 uppercase">স্বচ্ছতা / ভিজিবিলিটি (Opacity)</label>
                                    <span class="font-mono text-xs font-bold text-emerald-800" x-text="opacity + '%'"></span>
                                </div>
                                <input type="range" name="adm_watermark_opacity" min="30" max="100" step="5" x-model="opacity" class="w-full h-2 bg-emerald-200 rounded-lg appearance-none cursor-pointer accent-emerald-600 mt-2">
                            </div>
                        </div>

                        <!-- Live Visual Preview Box -->
                        <div class="p-3 bg-white/80 rounded-xl border border-emerald-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="text-xs text-slate-600">
                                <span class="font-bold text-slate-800">ওয়াটারমার্ক লাইভ প্রিভিউ:</span>
                                <span>আপলোডকৃত ছবিতে ওয়াটারমার্কটি যেভাবে ফুটে উঠবে।</span>
                            </div>
                            <div class="h-12 w-64 bg-slate-200 rounded-lg overflow-hidden relative border border-slate-300 shadow-inner flex items-center justify-center">
                                <div class="absolute inset-0 bg-cover bg-center opacity-40" style="background-image: repeating-linear-gradient(45deg, #cbd5e1 25%, transparent 25%, transparent 75%, #cbd5e1 75%, #cbd5e1), repeating-linear-gradient(45deg, #cbd5e1 25%, #f1f5f9 25%, #f1f5f9 75%, #cbd5e1 75%, #cbd5e1); background-position: 0 0, 10px 10px; background-size: 20px 20px;"></div>
                                <div class="absolute py-1 px-3 rounded-full flex items-center gap-1.5 shadow-sm transition-all"
                                     :class="{
                                         'bottom-1.5 right-1.5': position === 'bottom-right',
                                         'bottom-1.5 left-1.5': position === 'bottom-left',
                                         'top-1.5 right-1.5': position === 'top-right',
                                         'top-1.5 left-1.5': position === 'top-left',
                                         'inset-auto': position === 'center'
                                     }"
                                     :style="`background-color: rgba(15, 23, 42, ${opacity/100}); color: rgba(255, 255, 255, ${opacity/100});`">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="font-bold font-mono text-[10px]" x-text="text || 'PropertyKoi.com'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Real Test Action & Output -->
                        <div class="pt-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <button type="button" @click="testWm()" :disabled="testingWm" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                    <span x-show="!testingWm">⚡ টেস্ট ওয়াটারমার্ক ইমেজ জেনারেট করুন</span>
                                    <span x-show="testingWm">জেনারেট হচ্ছে...</span>
                                </button>
                                <span x-show="testWmMsg" class="text-xs font-bold" :class="testWmSuccess ? 'text-emerald-700' : 'text-rose-600'" x-text="testWmMsg"></span>
                            </div>
                        </div>

                        <!-- Rendered Image Preview when tested -->
                        <template x-if="testWmUrl">
                            <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-2">
                                <span class="text-xs font-bold text-slate-700 block">টেস্ট জেনারেটেড ইমেজ আউটপুট (Real GD Render):</span>
                                <div class="rounded-lg overflow-hidden border border-slate-200 max-w-md">
                                    <img :src="testWmUrl" alt="Watermark Preview" class="w-full h-auto block">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Cloudinary Media Storage Configuration Card -->
                <div class="p-6 bg-sky-50/70 rounded-2xl border border-sky-200 space-y-4" x-data="admCloudinarySettings()">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-sky-200/80 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">☁️</span>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 m-0">ক্লাউডিনারি ক্লাউড মিডিয়া স্টোরেজ (Cloudinary Media Storage)</h2>
                                <p class="text-xs text-slate-600 m-0 mt-0.5">
                                    আপলোডকৃত প্রপার্টি ছবিগুলো স্বয়ংক্রিয়ভাবে WebP ফরম্যাটে রূপান্তর হয়ে সরাসরি Cloudinary ক্লাউডে সংরক্ষিত হবে, যা হোস্টিং স্টোরেজ খালি রাখবে।
                                </p>
                            </div>
                        </div>
                        <div>
                            @if ($is_cld_set)
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full text-xs font-bold">
                                    🟢 ক্লাউডিনারি সক্রিয়
                                </span>
                            @else
                                <span class="px-3 py-1 bg-amber-100 text-amber-800 border border-amber-300 rounded-full text-xs font-bold">
                                    🟡 লোকাল ফলব্যাক সক্রিয়
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">Cloud Name *</label>
                            <input type="text" name="adm_cloudinary_cloud_name" value="{{ $cld_name }}" placeholder="e.g. dxyz123ab" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">API Key *</label>
                            <input type="text" name="adm_cloudinary_api_key" value="{{ $cld_key }}" placeholder="e.g. 123456789012345" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">API Secret *</label>
                            <input type="password" name="adm_cloudinary_api_secret" value="{{ $cld_secret }}" placeholder="••••••••••••••••••••" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">Upload Folder</label>
                            <input type="text" name="adm_cloudinary_folder" value="{{ $cld_folder }}" placeholder="e.g. aftabnagar_marketplace" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                        </div>
                    </div>

                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <button type="button" @click="testCld()" :disabled="testing" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                            <span x-text="testing ? 'টেস্ট করা হচ্ছে...' : '⚡ টেস্ট ক্লাউডিনারি কানেকশন'"></span>
                        </button>
                        <span x-show="testResult" :class="testSuccess ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200'" class="px-3 py-1.5 rounded-xl border text-xs font-bold" x-text="testResult" style="display:none;"></span>
                    </div>
                </div>

            </div><!-- /activeTab === 'media' -->

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 3: GOOGLE SHEETS SYNC -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'sheets'" x-cloak class="space-y-6">

                <!-- Google Sheets Live Sync Configuration Card -->
                <div class="p-6 bg-gradient-to-br from-emerald-50/70 to-teal-50/40 rounded-2xl border border-emerald-200/90 shadow-sm space-y-5" x-data="admGoogleSheetSync()">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-emerald-200/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">📗</span>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 m-0">গুগল শিট লাইভ সিঙ্ক ও অ্যান্টি-ডুপ্লিকেশন ইঞ্জিন (Google Sheets Live Sync)</h2>
                                <p class="text-xs text-slate-600 m-0 mt-0.5">
                                    গুগল শিট থেকে সকল এলাকার জমি, ফ্ল্যাট ও শেয়ার প্রপার্টি এক ক্লিকে লাইভ সিঙ্ক করুন। কোনো ডুপ্লিকেট হবে না — বিদ্যমান প্রপার্টিতে ডিরেক্ট সোর্স হিসেবে মার্জ হবে।
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="guideOpen = true" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                <span>📖</span>
                                <span>কলাম গাইড</span>
                            </button>
                        </div>
                    </div>

                    <!-- Input Grid -->
                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">গুগল শিট পাবলিশড CSV বা স্প্রেডশিট লিঙ্ক *</label>
                            <input type="url" name="adm_google_sheet_url" x-model="sheetUrl" value="{{ $google_sheet_url }}" placeholder="https://docs.google.com/spreadsheets/d/.../export?format=csv অথবা সাধারণ শিট URL" class="w-full border rounded-xl px-3.5 py-2.5 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs shadow-inner">
                            <span class="text-[11px] text-slate-500 mt-1 block">
                                💡 টিপস: শিট থেকে <strong>File &rarr; Share &rarr; Publish to web &rarr; Comma-separated values (.csv)</strong> লিংক অথবা সাধারণ শিট শেয়ার লিংক (Anyone with the link can view) দিন।
                            </span>
                        </div>

                        <!-- Direct Live Toggle Switch -->
                        <div class="p-4 bg-white/80 rounded-xl border border-emerald-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                    <span>⚡</span>
                                    <span>সিঙ্ক করার পর সরাসরি লাইভ/পাবলিশ করুন (Direct Live)</span>
                                </div>
                                <p class="text-[11px] text-slate-600 m-0">
                                    সক্রিয় থাকলে সিঙ্ক হওয়া প্রপার্টিগুলো সাথে সাথে ফ্রন্টএন্ডে প্রদর্শিত হবে। নিষ্ক্রিয় থাকলে প্রপার্টিগুলো <span class="font-bold text-amber-700">"ভেরিফিকেশন অপেক্ষমান"</span> হিসেবে জমা হবে।
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" name="adm_google_sheet_direct_live" value="yes" x-model="directLive" @change="defaultStatus = directLive ? 'live' : 'pending_verification'" class="sr-only peer">
                                <div class="w-12 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                <span class="ml-2.5 text-xs font-bold text-slate-800" x-text="directLive ? 'সরাসরি লাইভ' : 'ম্যানুয়াল রিভিউ'"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons Bar -->
                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <button type="button" @click="previewSheet()" :disabled="previewing || syncing" class="px-4 py-2 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-800 border border-slate-300 rounded-xl font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <span x-show="!previewing">🔍</span>
                            <span x-show="previewing" class="inline-block animate-spin">⏳</span>
                            <span x-text="previewing ? 'যাচাই করা হচ্ছে...' : 'প্রিভিউ দেখুন ও কলাম চেক করুন'"></span>
                        </button>

                        <button type="button" @click="syncNow()" :disabled="syncing || previewing" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl font-extrabold text-xs shadow-md transition flex items-center gap-2 cursor-pointer">
                            <span x-show="!syncing">🚀</span>
                            <span x-show="syncing" class="inline-block animate-spin">⏳</span>
                            <span x-text="syncing ? 'সিঙ্ক চলছে, অনুগ্রহ করে অপেক্ষা করুন...' : 'এখনই গুগল শিট সিঙ্ক করুন'"></span>
                        </button>
                    </div>

                    <!-- Last Sync Status Report -->
                    <template x-if="syncResult">
                        <div class="p-4 bg-emerald-100/90 border border-emerald-300 rounded-xl space-y-2 text-xs">
                            <div class="font-bold text-emerald-950 flex items-center gap-1.5">
                                <span>✅</span>
                                <span x-text="syncMsg || 'সিঙ্ক সফলভাবে সম্পন্ন হয়েছে!'"></span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 font-mono text-[11px] text-emerald-900">
                                <div class="bg-white/80 p-2 rounded-lg border border-emerald-200">
                                    মোট রেকর্ড: <strong class="text-slate-900" x-text="syncResult.total_rows || 0"></strong>
                                </div>
                                <div class="bg-white/80 p-2 rounded-lg border border-emerald-200">
                                    নতুন তৈরি: <strong class="text-emerald-700" x-text="syncResult.inserted || 0"></strong>
                                </div>
                                <div class="bg-white/80 p-2 rounded-lg border border-emerald-200">
                                    আপডেট/মার্জ: <strong class="text-blue-700" x-text="syncResult.updated || 0"></strong>
                                </div>
                                <div class="bg-white/80 p-2 rounded-lg border border-emerald-200">
                                    বাদ পড়েছে: <strong class="text-slate-600" x-text="syncResult.skipped || 0"></strong>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Column Guide Modal -->
                    <div x-show="guideOpen" x-cloak class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="guideOpen = false">
                        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto shadow-2xl">
                            <div class="flex items-center justify-between border-b pb-3">
                                <h3 class="text-base font-extrabold text-slate-900 m-0">📖 গুগল শিট কলাম ফরম্যাট নির্দেশিকা</h3>
                                <button type="button" @click="guideOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
                            </div>
                            <div class="text-xs text-slate-700 space-y-3 leading-relaxed">
                                <p>গুগল শিটের প্রথম সারিতে (Header Row) নিচের যেকোনো নামের কলাম থাকতে পারে (বাংলা বা ইংরেজি উভয়ই সমর্থিত):</p>
                                <div class="space-y-1.5 font-mono text-[11px] bg-slate-50 p-3 rounded-xl border border-slate-200">
                                    <div>• <strong>Type/ধরণ:</strong> land, flat, share, commercial (অথবা জমি, ফ্ল্যাট)</div>
                                    <div>• <strong>Area/এলাকা:</strong> Aftabnagar, Banasree, Bashundhara ইত্যাদি</div>
                                    <div>• <strong>Block/ব্লক:</strong> A, B, C, D, L, M ইত্যাদি</div>
                                    <div>• <strong>Sector/সেক্টর:</strong> ১, ২, ৩ ইত্যাদি</div>
                                    <div>• <strong>Plot/প্লট:</strong> প্লট নম্বর</div>
                                    <div>• <strong>Road/রাস্তা:</strong> রোডের নম্বর ও প্রস্থ (যেমন: 25 ft)</div>
                                    <div>• <strong>Size/সাইজ:</strong> ৩ কাঠা বা ১৫৫০ স্কয়ারফিট</div>
                                    <div>• <strong>Facing/ফেসিং:</strong> South, North, East, West</div>
                                    <div>• <strong>Price/মূল্য:</strong> মোট মূল্য বা কাঠাপ্রতি মূল্য</div>
                                    <div>• <strong>Phone/ফোন:</strong> মালিক বা এজেন্টের মোবাইল নম্বর</div>
                                    <div>• <strong>Name/নাম:</strong> মালিক বা কন্টাক্ট পার্সনের নাম</div>
                                </div>
                            </div>
                            <div class="pt-2 text-right">
                                <button type="button" @click="guideOpen = false" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold">বন্ধ করুন</button>
                            </div>
                        </div>
                    </div>

                    <!-- Sheet Preview Modal -->
                    <div x-show="previewOpen" x-cloak class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="previewOpen = false">
                        <div class="bg-white rounded-3xl max-w-4xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto shadow-2xl">
                            <div class="flex items-center justify-between border-b pb-3">
                                <h3 class="text-base font-extrabold text-slate-900 m-0">🔍 গুগল শিট ডেটা প্রিভিউ</h3>
                                <button type="button" @click="previewOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
                            </div>
                            <template x-if="previewData">
                                <div class="space-y-3 text-xs">
                                    <div class="flex items-center gap-4 text-slate-600 font-medium">
                                        <span>মোট সারি: <strong class="text-slate-900" x-text="previewData.total_rows || 0"></strong></span>
                                        <span>কলাম সংখ্যা: <strong class="text-slate-900" x-text="(previewData.headers || []).length"></strong></span>
                                    </div>
                                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                                        <table class="w-full text-left text-[11px]">
                                            <thead class="bg-slate-100 text-slate-700 font-bold">
                                                <tr>
                                                    <template x-for="h in (previewData.headers || [])" :key="h">
                                                        <th class="p-2 border-b" x-text="h"></th>
                                                    </template>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <template x-for="(row, idx) in (previewData.rows || []).slice(0, 10)" :key="idx">
                                                    <tr class="hover:bg-slate-50">
                                                        <template x-for="h in (previewData.headers || [])" :key="h">
                                                            <td class="p-2 whitespace-nowrap" x-text="row[h] || '-'"></td>
                                                        </template>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
                            <div class="pt-2 text-right">
                                <button type="button" @click="previewOpen = false" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold">বন্ধ করুন</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Parentless Linking Engine Card -->
                <div class="p-6 bg-gradient-to-br from-indigo-50/70 to-blue-50/40 rounded-2xl border border-indigo-200/90 shadow-sm space-y-4" x-data="admLinkParentlessProps()">
                    <div class="flex items-center gap-2.5 border-b border-indigo-200/80 pb-3">
                        <span class="text-2xl">🔗</span>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">হোয়াটসঅ্যাপ প্যারেন্টলেস প্রপার্টি লিঙ্কিং (WhatsApp Property Linking)</h2>
                            <p class="text-xs text-slate-600 m-0 mt-0.5">
                                শিট থেকে ইমপোর্ট হওয়া যে প্রপার্টিগুলোতে কোনো নির্দিষ্ট ইউজার অ্যাকাউন্ট লিংক নেই, সেগুলোকে সংশ্লিষ্ট WhatsApp নম্বরের ইউজারের সাথে ১-ক্লিকে লিংক করুন।
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 text-xs">
                        <input type="text" x-model="whatsappNumber" placeholder="WhatsApp নম্বর দিন (যেমন: 01712345678)" class="w-full sm:w-80 border rounded-xl px-3.5 py-2.5 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="button" @click="linkProperties()" :disabled="loading" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                            <span x-show="!loading">🔗</span>
                            <span x-show="loading" class="inline-block animate-spin">⏳</span>
                            <span x-text="loading ? 'লিঙ্ক করা হচ্ছে...' : 'প্রপার্টিগুলো লিঙ্ক করুন'"></span>
                        </button>
                    </div>

                    <div x-show="alertMsg" x-cloak class="p-3.5 rounded-xl text-xs font-bold"
                         :class="alertSuccess ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-rose-100 text-rose-900 border border-rose-300'"
                         x-text="alertMsg"></div>
                </div>

            </div><!-- /activeTab === 'sheets' -->

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 4: NOTIFICATIONS & FIREBASE / TELEGRAM / CRON -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-6">

                <!-- Firebase & PWA Push Notifications Card -->
                <div class="p-6 bg-gradient-to-br from-amber-50/90 to-orange-50/40 rounded-2xl border border-amber-200/90 shadow-sm space-y-5" x-data="admFcmSettings()">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-amber-200/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-3xl">🔔</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-black text-slate-900 m-0">Firebase & PWA পুশ নোটিফিকেশন কনফিগারেশন</h2>
                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $is_fb_set ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                                        {{ $is_fb_set ? 'সক্রিয় (Active)' : 'কনফিগারেশন বাকি' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 m-0 mt-0.5">নতুন প্রপার্টি লিস্টিং, সাইট ভিজিট শিডিউল ও দর প্রস্তাব আসলে এডমিন ও সংশ্লিষ্ট এজেন্টের ডিভাইসে সরাসরি পুশ বার্তা যাবে।</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="adm-fcm-device-badge px-2.5 py-1 bg-white border border-amber-200 rounded-xl text-[11px] font-bold text-amber-900">
                                📱 {{ $fcm_devices_count }}টি ডিভাইস সংযুক্ত
                            </span>
                        </div>
                    </div>

                    <!-- Permission & Test Push Row -->
                    <div class="p-4 bg-white/90 rounded-xl border border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <div class="text-slate-700">
                            <strong>নোটিফিকেশন পারমিশন ও টেস্ট:</strong> এই ব্রাউজারে নোটিফিকেশন চালু আছে কিনা তা পরীক্ষা করতে টেস্ট পুশ পাঠান।
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="requestPermission()" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>🔔</span>
                                <span>পারমিশন দিন</span>
                            </button>
                            <button type="button" @click="sendTestPush()" :disabled="testingFcm" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white rounded-xl font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <span x-show="!testingFcm">🚀</span>
                                <span x-show="testingFcm" class="inline-block animate-spin">⏳</span>
                                <span x-text="testingFcm ? 'পাঠানো হচ্ছে...' : 'টেস্ট পুশ পাঠান'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="testFcmMsg" x-cloak class="p-3 rounded-xl text-xs font-bold"
                         :class="testFcmSuccess ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-rose-100 text-rose-900 border border-rose-300'"
                         x-text="testFcmMsg"></div>

                    <!-- Push Event Toggles Grid -->
                    <div class="space-y-3 pt-2">
                        <div class="font-bold text-xs text-slate-800 uppercase tracking-wider">পুশ নোটিফিকেশন ইভেন্টসমূহ:</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notifications_enabled" value="yes" {{ $fcm_enabled ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">🔔 মাস্টার পুশ নোটিফিকেশন সক্রিয় রাখুন</span>
                                    <span class="text-[11px] text-slate-500">সকল ব্যাকগ্রাউন্ড ব্রাউজার ও মোবাইল নোটিফিকেশন ইঞ্জিন।</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notify_property_submit" value="yes" {{ $fcm_notify_property ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">🏡 নতুন প্রপার্টি লিস্টিং সাবমিট হলে জানান</span>
                                    <span class="text-[11px] text-slate-500">মালিক বা এজেন্ট ফ্রন্টএন্ড থেকে নতুন পোস্ট করলে।</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notify_visit_booking" value="yes" {{ $fcm_notify_visit ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">📅 নতুন সাইট ভিজিট বুকিং হলে জানান</span>
                                    <span class="text-[11px] text-slate-500">ক্রেতা কোনো প্রপার্টিতে ভিজিট স্লট বুক করলে।</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notify_offer" value="yes" {{ $fcm_notify_offer ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">💰 দর প্রস্তাব বা অফার প্রাইস আসলে জানান</span>
                                    <span class="text-[11px] text-slate-500">কোনো প্রপার্টিতে ক্রেতা দাম অফার করলে।</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notify_more_info" value="yes" {{ $fcm_notify_more_info ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">ℹ️ বিস্তারিত তথ্য অনুসন্ধানের অনুরোধ আসলে</span>
                                    <span class="text-[11px] text-slate-500">ক্রেতা পেজ থেকে আরো তথ্য জানতে চাইলে।</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notify_whatsapp_click" value="yes" {{ $fcm_notify_whatsapp ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">💬 হোয়াটসঅ্যাপ যোগাযোগ ক্লিক করলে জানান</span>
                                    <span class="text-[11px] text-slate-500">প্রপার্টি পেজ থেকে ক্রেতা হোয়াটসঅ্যাপ বাটনে ক্লিক করলে।</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notify_lead_submit" value="yes" {{ $fcm_notify_lead ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">📋 সাধারণ কাস্টমার লিড ও রিকোয়ারমেন্ট অ্যালার্ট</span>
                                    <span class="text-[11px] text-slate-500">প্ল্যাটফর্মে সাধারণ প্রপার্টি রিকোয়ারমেন্ট আসলে।</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 bg-white/80 rounded-xl border border-amber-100 cursor-pointer hover:bg-white transition">
                                <input type="checkbox" name="adm_fcm_notify_agent_enabled" value="yes" {{ $fcm_notify_agent ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                <div>
                                    <span class="font-bold text-slate-800 block">🎯 সংশ্লিষ্ট প্রপার্টির ভেরিফাইড এজেন্টকেও পুশ পাঠান</span>
                                    <span class="text-[11px] text-slate-500">অ্যাডমিনের পাশাপাশি উক্ত প্রপার্টির রেজিস্টার্ড এজেন্টও নোটিফিকেশন পাবেন।</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Firebase Web API Credentials Grid -->
                    <div class="pt-2 border-t border-amber-200/60">
                        <div class="text-xs font-bold text-slate-800 mb-2">Firebase প্রজেক্ট ক্রেডেনশিয়ালস:</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-bold text-slate-800 uppercase mb-1">Firebase Web API Key *</label>
                                <input type="text" name="adm_firebase_api_key" value="{{ $fb_api_key }}" placeholder="e.g. AIzaSy..." class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-800 uppercase mb-1">Auth Domain</label>
                                <input type="text" name="adm_firebase_auth_domain" value="{{ $fb_auth_domain }}" placeholder="e.g. your-project.firebaseapp.com" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-800 uppercase mb-1">Project ID *</label>
                                <input type="text" name="adm_firebase_project_id" value="{{ $fb_project_id }}" placeholder="e.g. aftabnagar-direct" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-800 uppercase mb-1">App ID</label>
                                <input type="text" name="adm_firebase_app_id" value="{{ $fb_app_id }}" placeholder="e.g. 1:123456789:web:abcdef" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 text-xs">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-800 uppercase mb-1">Messaging Sender ID</label>
                                <input type="text" name="adm_firebase_messaging_sender_id" value="{{ $fb_sender_id }}" placeholder="e.g. 123456789012" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-amber-100/70 border border-amber-300/80 rounded-xl text-[11px] text-amber-900 leading-relaxed">
                        💡 <strong>সার্ভিস অ্যাকাউন্ট ভেরিফাইড:</strong> Google Firebase HTTP v1 API এবং Service Account প্রাইভেট কি দিয়ে সার্ভার কনফিগারেশন সক্রিয় আছে। ব্রাউজার বন্ধ থাকলেও ব্যাকগ্রাউন্ডে Android এবং Windows/Mac ডিভাইসে তাৎক্ষণিক পুশ নোটিফিকেশন রিসিভ হবে।
                    </div>
                </div>

                <!-- Telegram Bot Notifications Configuration Card -->
                <div class="p-6 bg-gradient-to-br from-sky-50/90 to-blue-50/40 rounded-2xl border border-sky-200/90 shadow-sm space-y-5" x-data="admTelegramSettings()">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-sky-200/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-3xl">✈️</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-black text-slate-900 m-0">টেলিগ্রাম বট নোটিফিকেশন কনফিগারেশন</h2>
                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $is_tele_ready ? 'bg-sky-100 text-sky-800 border border-sky-300' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $is_tele_ready ? 'সক্রিয় (Active)' : 'কনফিগারেশন বাকি' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 m-0 mt-0.5">টেলিগ্রাম বট এপিআই ১০০% ফ্রি — কোনো এসএমএস বা গেটওয়ে চার্জ নেই। ক্রেতা এবং এডমিন তাৎক্ষণিক নোটিফিকেশন পাবেন।</p>
                            </div>
                        </div>
                        <button type="button" @click="testBot()" :disabled="testing" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <span x-show="!testing">বট কানেকশন টেস্ট করুন</span>
                            <span x-show="testing" class="flex items-center gap-1">
                                <span class="inline-block animate-spin">⏳</span>
                                <span>যাচাই হচ্ছে...</span>
                            </span>
                        </button>
                    </div>

                    <!-- Test Result Alert -->
                    <div x-show="testResult" x-cloak class="p-3.5 bg-emerald-100 border border-emerald-300 text-emerald-900 rounded-xl text-xs font-bold" x-text="testResult"></div>
                    <div x-show="testError" x-cloak class="p-3.5 bg-rose-100 border border-rose-300 text-rose-900 rounded-xl text-xs font-bold" x-text="testError"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">Telegram Bot Token <span class="text-rose-500">*</span></label>
                            <input type="text" name="adm_telegram_bot_token" x-model="botToken" value="{{ $tele_bot_token }}" placeholder="e.g. 123456789:ABCdefGHIjklMNOpqr..." class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                            <span class="text-[10px] text-slate-500 mt-1 block">টেলিগ্রামে <code>@BotFather</code> এ গিয়ে <code>/newbot</code> কমান্ড দিয়ে টোকেনটি সংগ্রহ করুন।</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">Bot Username (without @) <span class="text-rose-500">*</span></label>
                            <input type="text" name="adm_telegram_bot_username" x-model="botUsername" value="{{ $tele_bot_username }}" placeholder="e.g. AftabnagarPropertyBot" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                            <span class="text-[10px] text-slate-500 mt-1 block">বটের ইউজারনেম (যেমন: <code>AftabnagarPropertyBot</code>)।</span>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-800 uppercase mb-1">Admin Chat ID বা Channel Username (এডমিন এলার্টের জন্য)</label>
                            <input type="text" name="adm_telegram_admin_chat_id" x-model="adminChatId" value="{{ $tele_admin_chat_id }}" placeholder="e.g. 6891457989 বা @my_channel_username" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                            <span class="text-[10px] text-slate-600 mt-1 block leading-relaxed">
                                💡 <strong>জরুরি নিয়ম:</strong> ব্যক্তিগত টেলিগ্রাম একাউন্টে এডমিন নোটিফিকেশন পেতে <code>@username</code> দিলে টেলিগ্রাম বট মেসেজ পাঠাতে পারে না। আপনার <strong>নিউমেরিক চ্যাট আইডি</strong> দিন (যেমন: <code>6891457989</code>)। চ্যাট আইডি জানতে টেলিগ্রামে <code>@userinfobot</code> কে মেসেজ দিন। এছাড়াও নোটিফিকেশন পাওয়ার পূর্বে আপনার তৈরিকৃত বটে ঢুকে <strong>Start</strong> চেপে চালু করুন। (পাবলিক চ্যানেলের ক্ষেত্রে <code>@channel_username</code> দিতে পারেন এবং বটকে চ্যানেলের এডমিন বানাতে হবে)।
                            </span>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-800 uppercase mb-1">পাবলিক টেলিগ্রাম চ্যানেল লিঙ্ক (১-ক্লিক জয়েন বোতামের জন্য)</label>
                            <input type="url" name="adm_telegram_channel_url" value="{{ $tele_channel_url }}" placeholder="e.g. https://t.me/propertykoi_deals" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 text-xs">
                            <span class="text-[10px] text-slate-500 mt-1 block">গ্রাহকরা যাতে ১-ক্লিকে টেলিগ্রাম চ্যানেলে জয়েন করে হট ডিল ও অ্যালার্ট পেতে পারে, তার জন্য পাবলিক চ্যানেল লিঙ্ক দিন।</span>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-sky-200/60 text-xs">
                        <label class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="adm_telegram_notify_new_demand" value="yes" {{ $tele_notify_demand ? 'checked' : '' }} class="rounded text-sky-600 focus:ring-sky-500 w-4 h-4">
                            <div>
                                <span class="font-bold text-slate-900 block">নতুন ক্রেতা চাহিদা জমা হলে টেলিগ্রামে এডমিনকে নোটিফাই করুন</span>
                                <span class="text-[11px] text-slate-500">যেকোনো ক্রেতা বা গেস্ট চাহিদা জমা দিলে এডমিন চ্যাটে প্রপার্টি টাইপ ও বাজেটসহ বার্তা যাবে।</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="adm_telegram_notify_new_property" value="yes" {{ $tele_notify_property ? 'checked' : '' }} class="rounded text-sky-600 focus:ring-sky-500 w-4 h-4">
                            <div>
                                <span class="font-bold text-slate-900 block">নতুন প্রপার্টি লিস্টিং সাবমিট হলে টেলিগ্রামে এডমিনকে নোটিফাই করুন</span>
                                <span class="text-[11px] text-slate-500">মালিক বা এজেন্ট নতুন প্রপার্টি সাবমিট করলে ভেরিফিকেশনের জন্য সরাসরি টেলিগ্রামে মেসেজ আসবে।</span>
                            </div>
                        </label>
                    </div>

                    <div class="p-3 bg-sky-100/80 border border-sky-300 rounded-xl text-[11px] text-sky-950 leading-relaxed">
                        🤖 <strong>ওয়েবহুক কানেকশন লিঙ্ক:</strong> আপনি চাইলে বটের সাথে আপনার সাইটের সরাসরি ওয়েবহুক লিঙ্ক করতে পারেন: <code>{{ url('/api/telegram/webhook') }}</code>
                    </div>
                </div>

                <!-- Email & SMTP Server Configuration Card -->
                <div class="p-6 bg-gradient-to-br from-indigo-50/90 to-purple-50/40 rounded-2xl border border-indigo-200/90 shadow-sm space-y-5" x-data="admEmailSmtpSettings()">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-indigo-200/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-3xl">📧</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-black text-slate-900 m-0">ইমেইল ও SMTP সার্ভার কনফিগারেশন</h2>
                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $mail_mailer === 'smtp' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                                        {{ $mail_mailer === 'smtp' ? 'SMTP লাইভ ডেলিভারি' : 'Log ড্রাইভার (লোকাল লগ)' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 m-0 mt-0.5">ক্রেতা ও বিক্রেতাদের ম্যাচিং এলার্ট এবং চাহিদা গ্রহণের নিশ্চিতকরণ ইমেইল সরাসরি ইনবক্সে পাঠানোর জন্য SMTP কনফিগার করুন।</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="testEmail()" :disabled="testing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                <span x-show="!testing">✉️ টেস্ট ইমেইল পাঠান</span>
                                <span x-show="testing" class="flex items-center gap-1">
                                    <span class="inline-block animate-spin">⏳</span> পাঠানো হচ্ছে...
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Test Result Alert -->
                    <div x-show="testResult" x-cloak class="p-3.5 bg-emerald-100 border border-emerald-300 text-emerald-900 rounded-xl text-xs font-bold" x-text="testResult"></div>
                    <div x-show="testError" x-cloak class="p-3.5 bg-rose-100 border border-rose-300 text-rose-900 rounded-xl text-xs font-bold" x-text="testError"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">মেইল ড্রাইভার (Mail Driver)</label>
                            <select name="adm_mail_mailer" x-model="mailer" class="w-full border rounded-xl px-3 py-2 bg-white font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                                <option value="smtp">SMTP (আসল ইমেইল ইনবক্সে পাঠানো)</option>
                                <option value="log">Log (শুধুমাত্র storage/logs/laravel.log এ লেখা)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">SMTP Host</label>
                            <input type="text" name="adm_mail_host" x-model="host" value="{{ $mail_host }}" placeholder="e.g. smtp.gmail.com" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">SMTP Port</label>
                            <input type="number" name="adm_mail_port" x-model="port" value="{{ $mail_port }}" placeholder="587 বা 465" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">Encryption</label>
                            <select name="adm_mail_encryption" x-model="encryption" class="w-full border rounded-xl px-3 py-2 bg-white font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                                <option value="tls">TLS (Port 587 - Gmail / cPanel রিকমেন্ডেড)</option>
                                <option value="ssl">SSL (Port 465)</option>
                                <option value="none">None</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">SMTP Username / Email</label>
                            <input type="text" name="adm_mail_username" x-model="username" value="{{ $mail_username }}" placeholder="your-email@gmail.com" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">SMTP Password / App Password</label>
                            <input type="password" name="adm_mail_password" x-model="password" value="{{ $mail_password }}" placeholder="16-character App Password" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">From Email Address</label>
                            <input type="email" name="adm_mail_from_address" x-model="fromAddress" value="{{ $mail_from_address }}" placeholder="noreply@propertykoi.com" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">From Name</label>
                            <input type="text" name="adm_mail_from_name" x-model="fromName" value="{{ $mail_from_name }}" placeholder="PropertyKoi" class="w-full border rounded-xl px-3 py-2 bg-white font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">টেস্ট পাঠানোর ইমেইল ঠিকানা</label>
                            <input type="email" x-model="testEmailAddr" placeholder="আপনার ব্যক্তিগত ইমেইল" class="w-full border rounded-xl px-3 py-2 bg-white font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500 text-xs">
                        </div>
                    </div>

                    <div class="p-3 bg-indigo-100/70 border border-indigo-200 rounded-xl text-[11px] text-indigo-950 leading-relaxed">
                        💡 <strong>Gmail ব্যবহার করলে:</strong> সাধারণ জিমেইল পাসওয়ার্ড কাজ করবে না। আপনার গুগল অ্যাকাউন্টে 2-Step Verification অন করে <strong>Security &gt; App Passwords</strong> থেকে ১৬ অক্ষরের একটি অ্যাপ পাসওয়ার্ড তৈরি করে SMTP Password ফিল্ডে দিন।
                    </div>
                </div>

                <!-- Daily Demand Matching & Cron Engine Card -->
                <div class="p-6 bg-gradient-to-br from-emerald-50/90 to-slate-50/60 rounded-2xl border border-emerald-200/90 shadow-sm space-y-5" x-data="admDemandCronSettings()">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-emerald-200/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-3xl">⏰</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-black text-slate-900 m-0">দৈনিক ডিমান্ড ম্যাচিং এবং সিআরকিউ / ক্রন ইঞ্জিন</h2>
                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        অটো-ম্যাচিং সক্রিয়
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 m-0 mt-0.5">প্রতিদিন স্বয়ংক্রিয়ভাবে লাইভ প্রপার্টিগুলোর সাথে সকল সক্রিয় ক্রেতা চাহিদার ±১৫% বাজেট টলারেন্স ম্যাচিং করে পুশ, ইমেইল ও টেলিগ্রামে নোটিফিকেশন পাঠায়।</p>
                            </div>
                        </div>
                        <button type="button" @click="runCronNow()" :disabled="running" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <span x-show="!running">🚀 এখনই ডিমান্ড ম্যাচিং চালান</span>
                            <span x-show="running" class="flex items-center gap-1">
                                <span class="inline-block animate-spin">⏳</span>
                                <span>ম্যাচিং প্রসেস হচ্ছে...</span>
                            </span>
                        </button>
                    </div>

                    <!-- Run Result Alert -->
                    <div x-show="runResult" x-cloak class="p-3.5 bg-emerald-100 border border-emerald-300 text-emerald-900 rounded-xl text-xs font-bold" x-text="runResult"></div>
                    <div x-show="runError" x-cloak class="p-3.5 bg-rose-100 border border-rose-300 text-rose-900 rounded-xl text-xs font-bold" x-text="runError"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                            <span class="text-[11px] text-slate-500 font-bold block uppercase">সার্ভার ক্রন পরবর্তী শিডিউল:</span>
                            <span class="text-sm font-black text-slate-800 mt-1 block">প্রতিদিন সকাল ৯:০০ টা</span>
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Laravel Scheduler ও রিয়েল সার্ভার ক্রনের মাধ্যমে পরিচালিত।</span>
                        </div>

                        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-500 font-bold block uppercase">সিক্রেট ক্রন কি (Secret Key):</span>
                                <span class="text-[10px] text-slate-400 font-mono">adm_cron_secret_key</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="text" name="adm_cron_secret_key" value="{{ $cron_secret_key }}" class="w-full border rounded-lg px-2.5 py-1.5 bg-slate-50 font-mono text-xs font-bold text-slate-800">
                                <button type="submit" form="adm-settings-form" title="সিক্রেট কি সেভ করুন" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shrink-0 cursor-pointer flex items-center gap-1 shadow-xs">
                                    <span>💾</span>
                                    <span>সেভ</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Real Server Cron Setup Instructions -->
                    <div class="bg-slate-900 text-white p-4 sm:p-5 rounded-2xl space-y-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base">⚡</span>
                            <span class="font-extrabold text-amber-300">কেন ব্যাকগ্রাউন্ড সার্ভার ক্রন জরুরি?</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed text-[11px] m-0">
                            নির্দিষ্ট সময়ে নির্ভরযোগ্যভাবে ক্রেতা ও এজেন্টদের কাছে নোটিফিকেশন পাঠাতে সার্ভার ক্রন ব্যবহার করুন:
                        </p>

                        <div class="space-y-2 pt-1 font-mono text-[11px]">
                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
                                <span class="text-emerald-400 font-bold font-sans block mb-1">পদ্ধতি ১: Laravel Schedule Cron (cPanel)</span>
                                <code class="text-slate-200 break-all select-all">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
                            </div>

                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800">
                                <span class="text-emerald-400 font-bold font-sans block mb-1">পদ্ধতি ২: ফ্রি ক্রন সার্ভিস (যেমন cron-job.org)</span>
                                <code class="text-sky-300 break-all select-all">{{ url('/api/cron/match-demands?key=' . $cron_secret_key) }}</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════════════════════ -->
                <!-- Microsoft Clarity Session Replay & Heatmaps Analytics Card -->
                <!-- ══════════════════════════════════════════════════════════════════════════ -->
                <div id="clarity-settings" class="p-6 bg-gradient-to-br from-indigo-50/80 via-white to-blue-50/60 rounded-3xl border border-indigo-200/90 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-indigo-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">📈</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-black text-slate-900 m-0">Microsoft Clarity সেশন রেকর্ডিং ও হিটম্যাপ</h2>
                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $is_clarity_ready && $clarity_enabled ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($is_clarity_ready ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-700 border border-slate-300') }}">
                                        {{ $is_clarity_ready && $clarity_enabled ? 'সক্রিয় (Active)' : ($is_clarity_ready ? 'পজ করা (Paused)' : 'কনফিগার করা হয়নি') }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 m-0 mt-0.5">WordPress সাইটের অফিসিয়াল Microsoft Clarity প্লাগইনের সম্পূর্ণ অনুরূপ সমাধান — লাইভ সেশন রিপ্লে, হিটম্যাপ ও কনভার্সন ট্র্যাকিং।</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.clarity') }}" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                                <span>📊</span>
                                <span>ক্ল্যারিটি ভিউয়ার</span>
                            </a>
                        </div>
                    </div>

                    <!-- Configuration Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Project ID -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-extrabold text-slate-800">ক্ল্যারিটি প্রজেক্ট আইডি (Project ID):</label>
                                <span class="text-[10px] text-indigo-600 font-mono font-bold">adm_clarity_project_id</span>
                            </div>
                            <input type="text" name="adm_clarity_project_id" value="{{ $clarity_project_id }}"
                                   placeholder="যেমন: p3k7x9zq1m (অথবা .env: MICROSOFT_CLARITY_ID)"
                                   class="w-full border rounded-xl px-3 py-2 bg-slate-50 font-mono text-xs font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                            <p class="text-[10px] text-slate-500 m-0">
                                <a href="https://clarity.microsoft.com" target="_blank" class="text-indigo-600 font-bold hover:underline">clarity.microsoft.com</a> থেকে আপনার প্রজেক্টের আইডি সংগ্রহ করুন।
                            </p>
                        </div>

                        <!-- Direct Launch Links -->
                        <div class="bg-indigo-950 text-white p-4 rounded-2xl border border-indigo-900 space-y-2 flex flex-col justify-between">
                            <div>
                                <span class="text-xs font-bold text-indigo-300 block mb-1">ক্ল্যারিটি অফিসিয়াল লিংকস:</span>
                                <p class="text-[11px] text-slate-300 m-0 leading-relaxed">
                                    রেকর্ডিং ও হিটম্যাপ সরাসরি মাইক্রোসফট ক্লাউড পোর্টালে সংরক্ষিত থাকে।
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <a href="{{ $clarity_recordings_url }}" target="_blank" class="px-3 py-1.5 bg-indigo-800 hover:bg-indigo-700 text-white rounded-lg text-[11px] font-bold transition flex items-center gap-1">
                                    <span>🎥 সেশন ভিডিও</span>
                                </a>
                                <a href="{{ $clarity_heatmaps_url }}" target="_blank" class="px-3 py-1.5 bg-emerald-800 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition flex items-center gap-1">
                                    <span>🗺️ হিটম্যাপ</span>
                                </a>
                                <a href="{{ $clarity_dashboard_url }}" target="_blank" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-bold transition flex items-center gap-1">
                                    <span>↗️ ড্যাশবোর্ড</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Toggles Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <!-- Toggle 1: Enabled -->
                        <label class="flex items-start gap-3 p-3.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-indigo-50/40 transition">
                            <input type="checkbox" name="adm_clarity_enabled" value="yes" {{ $clarity_enabled ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <div>
                                <span class="font-extrabold text-slate-900 block">Microsoft Clarity ট্র্যাকিং চালু রাখুন</span>
                                <span class="text-[11px] text-slate-500 block">সাইটের সকল ভিজিটরদের স্ক্রিন সেশন এবং হিটম্যাপ অ্যানালিটিক্স রেকর্ড করবে।</span>
                            </div>
                        </label>

                        <!-- Toggle 2: Exclude Admin -->
                        <label class="flex items-start gap-3 p-3.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-indigo-50/40 transition">
                            <input type="checkbox" name="adm_clarity_exclude_admin" value="yes" {{ $clarity_exclude_admin ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <div>
                                <span class="font-extrabold text-slate-900 block">অ্যাডমিন সেশন এক্সক্লুড করুন (Exclude Admin)</span>
                                <span class="text-[11px] text-slate-500 block">অ্যাডমিন ও এডিটরদের টেস্ট ভিজিট বাদ দেওয়া হবে, যাতে অ্যানালিটিক্স ও হিটম্যাপ নির্ভুল থাকে।</span>
                            </div>
                        </label>

                        <!-- Toggle 3: Track Roles -->
                        <label class="flex items-start gap-3 p-3.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-indigo-50/40 transition">
                            <input type="checkbox" name="adm_clarity_track_roles" value="yes" {{ $clarity_track_roles ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <div>
                                <span class="font-extrabold text-slate-900 block">বায়ার ও এজেন্ট রোল আইডেন্টিফিকেশন (Identify)</span>
                                <span class="text-[11px] text-slate-500 block">লগইন করা বায়ার ও এজেন্টদের ক্ল্যারিটিতে রোল ট্যাগ (<code class="font-mono text-[10px]">role: buyer|agent</code>) করবে।</span>
                            </div>
                        </label>

                        <!-- Toggle 4: Mask Inputs -->
                        <label class="flex items-start gap-3 p-3.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-indigo-50/40 transition">
                            <input type="checkbox" name="adm_clarity_mask_inputs" value="yes" {{ $clarity_mask_inputs ? 'checked' : '' }}
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <div>
                                <span class="font-extrabold text-slate-900 block">সংবেদনশীল ইনপুট মাস্কিং (Privacy Masking)</span>
                                <span class="text-[11px] text-slate-500 block">ক্রেতা ও মালিকের ফোন নাম্বার, পাসওয়ার্ড এবং ওটিপি রেকর্ডিংয়ে স্টার (*) চিহ্ন দিয়ে গোপন রাখবে।</span>
                            </div>
                        </label>
                    </div>

                    <!-- Auto-Tracked Events Documentation -->
                    <div class="bg-slate-900 text-white p-4 rounded-2xl text-xs space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-indigo-400 font-bold">🎯 স্বয়ংক্রিয় রিয়েল এস্টেট কনভার্সন ইভেন্টস:</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] font-mono">
                            <div class="bg-slate-800/80 p-2 rounded-lg border border-slate-700">
                                <span class="text-emerald-400 block font-bold">whatsapp_click</span>
                                <span class="text-slate-400 font-sans text-[10px]">হোয়াটসঅ্যাপ বাটনে ক্লিক</span>
                            </div>
                            <div class="bg-slate-800/80 p-2 rounded-lg border border-slate-700">
                                <span class="text-sky-400 block font-bold">phone_call_click</span>
                                <span class="text-slate-400 font-sans text-[10px]">সরাসরি কল বাটনে ক্লিক</span>
                            </div>
                            <div class="bg-slate-800/80 p-2 rounded-lg border border-slate-700">
                                <span class="text-amber-400 block font-bold">site_visit_click</span>
                                <span class="text-slate-400 font-sans text-[10px]">সাইট ভিজিট বুকিং ফর্ম</span>
                            </div>
                            <div class="bg-slate-800/80 p-2 rounded-lg border border-slate-700">
                                <span class="text-purple-400 block font-bold">chahida_submitted</span>
                                <span class="text-slate-400 font-sans text-[10px]">চাহিদা ফরম সফল সাবমিশন</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /activeTab === 'notifications' -->

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 5: REDIRECTION & POLICIES -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'access'" x-cloak class="space-y-6" x-data="admAccessControlSettings()">

                <!-- Role-wise Admin Top Bar & Dashboard Access Control Matrix -->
                <div class="p-6 bg-slate-900 text-white rounded-3xl shadow-xl space-y-5 border border-slate-800">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🛡️</span>
                            <div>
                                <h2 class="text-lg font-extrabold text-white m-0">রোল-ভিত্তিক এডমিন টপ বার ও ড্যাশবোর্ড এক্সেস কন্ট্রোল</h2>
                                <p class="text-xs text-slate-400 m-0 mt-0.5">
                                    কোন রোল ফ্রন্টএন্ডে ওপরের কালো এডমিন বার দেখতে পাবে এবং কারা সরাসরি <code>/admin</code> ড্যাশবোর্ডে প্রবেশ করতে পারবে তা এক টেবিলে নিয়ন্ত্রণ করুন।
                                </p>
                            </div>
                        </div>

                        <!-- Presets Button Group -->
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="applyAccessPreset('recommended')"
                                    class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
                                    title="এজেন্ট ও ওনারদের জন্য পোর্টাল এবং সাধারণ ইউজারদের জন্য ফ্রন্টএন্ড-অনলি কনফিগারেশন">
                                <span>⚡</span>
                                <span>প্রস্তাবিত ডিফল্ট</span>
                            </button>
                            <button type="button" @click="applyAccessPreset('strict')"
                                    class="px-3.5 py-1.5 bg-rose-700 hover:bg-rose-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
                                    title="শুধুমাত্র এডমিনিস্ট্রেটর ব্যতীত বাকি সকল রোলের জন্য ড্যাশবোর্ড ও এডমিন বার বন্ধ">
                                <span>🔒</span>
                                <span>কড়া নিরাপত্তা</span>
                            </button>
                        </div>
                    </div>

                    <!-- Matrix Table -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-800/80 text-slate-300 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-800">
                                    <th class="p-3.5">ব্যবহারকারী রোল (User Role)</th>
                                    <th class="p-3.5 text-center">টপ এডমিন বার (Admin Bar)</th>
                                    <th class="p-3.5 text-center">Admin ড্যাশবোর্ড এক্সেস</th>
                                    <th class="p-3.5">ব্লক হলে রিডাইরেক্ট গন্তব্য</th>
                                    <th class="p-3.5 text-center">স্ট্যাটাস প্রিভিউ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/70 text-slate-200">
                                <template x-for="role in rolesSummary" :key="role.key">
                                    <tr class="hover:bg-slate-800/40 transition-colors">
                                        <!-- Role Info -->
                                        <td class="p-3.5">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-white" x-text="role.name"></span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono"
                                                      :class="role.count > 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-500'"
                                                      x-text="role.count + ' জন'"></span>
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-400 mt-0.5" x-text="'স্লাগ: ' + role.key"></div>
                                        </td>

                                        <!-- Admin Bar Visibility Toggle -->
                                        <td class="p-3.5 text-center">
                                            <input type="hidden" :name="'adm_role_admin_bar[' + role.key + ']'" :value="accessBar[role.key] ? '1' : '0'">
                                            <div class="inline-flex rounded-xl p-1 bg-slate-900 border border-slate-800">
                                                <button type="button"
                                                        @click="accessBar[role.key] = 1"
                                                        :class="accessBar[role.key] == 1 ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                                                        class="px-2.5 py-1 rounded-lg text-[11px] transition flex items-center gap-1 cursor-pointer">
                                                    <span>👁️</span>
                                                    <span>দেখাবে</span>
                                                </button>
                                                <button type="button"
                                                        @click="accessBar[role.key] = 0"
                                                        :class="accessBar[role.key] == 0 ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                                                        class="px-2.5 py-1 rounded-lg text-[11px] transition flex items-center gap-1 cursor-pointer">
                                                    <span>🚫</span>
                                                    <span>লুকানো</span>
                                                </button>
                                            </div>
                                        </td>

                                        <!-- WP-Admin Dashboard Access Control -->
                                        <td class="p-3.5 text-center">
                                            <template x-if="role.key === 'administrator'">
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-xl text-[11px] font-bold">
                                                    <span>👑</span>
                                                    <span>সবসময় উন্মুক্ত (সুরক্ষিত)</span>
                                                </div>
                                            </template>
                                            <template x-if="role.key !== 'administrator'">
                                                <div>
                                                    <input type="hidden" :name="'adm_role_admin_panel[' + role.key + ']'" :value="accessPanel[role.key] ? '1' : '0'">
                                                    <div class="inline-flex rounded-xl p-1 bg-slate-900 border border-slate-800">
                                                        <button type="button"
                                                                @click="accessPanel[role.key] = 1"
                                                                :class="accessPanel[role.key] == 1 ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                                                                class="px-2.5 py-1 rounded-lg text-[11px] transition flex items-center gap-1 cursor-pointer">
                                                            <span>✅</span>
                                                            <span>অনুমোদিত</span>
                                                        </button>
                                                        <button type="button"
                                                                @click="accessPanel[role.key] = 0"
                                                                :class="accessPanel[role.key] == 0 ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                                                                class="px-2.5 py-1 rounded-lg text-[11px] transition flex items-center gap-1 cursor-pointer">
                                                            <span>⛔</span>
                                                            <span>ব্লকড</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </td>

                                        <!-- Blocked Redirect Target -->
                                        <td class="p-3.5">
                                            <template x-if="role.key === 'administrator'">
                                                <span class="text-slate-500 text-[11px] italic">প্রযোজ্য নয়</span>
                                            </template>
                                            <template x-if="role.key !== 'administrator'">
                                                <div>
                                                    <select :name="'adm_role_admin_redirects[' + role.key + ']'"
                                                            x-model="accessRedirects[role.key]"
                                                            :disabled="accessPanel[role.key] == 1"
                                                            :class="accessPanel[role.key] == 1 ? 'opacity-40 cursor-not-allowed bg-slate-900' : 'bg-slate-900 hover:border-slate-600 cursor-pointer'"
                                                            class="border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500 w-full max-w-[200px]">
                                                        <option value="auto">🎯 অটো (রোল ড্যাশবোর্ড / পোর্টাল)</option>
                                                        <option value="home">🏠 হোমপেজ (/)</option>
                                                        <option value="login">🔐 লগইন পেজ (/login-register/)</option>
                                                    </select>
                                                </div>
                                            </template>
                                        </td>

                                        <!-- Status Summary Badge -->
                                        <td class="p-3.5 text-center">
                                            <template x-if="role.key === 'administrator'">
                                                <span class="px-2.5 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-lg text-[10px] font-extrabold uppercase">
                                                    ফুল এডমিন
                                                </span>
                                            </template>
                                            <template x-if="role.key !== 'administrator' && accessPanel[role.key] == 1 && accessBar[role.key] == 1">
                                                <span class="px-2.5 py-1 bg-blue-500/20 text-blue-300 border border-blue-500/30 rounded-lg text-[10px] font-extrabold uppercase">
                                                    এডমিন বার + ড্যাশবোর্ড
                                                </span>
                                            </template>
                                            <template x-if="role.key !== 'administrator' && accessPanel[role.key] == 1 && accessBar[role.key] == 0">
                                                <span class="px-2.5 py-1 bg-teal-500/20 text-teal-300 border border-teal-500/30 rounded-lg text-[10px] font-extrabold uppercase">
                                                    ড্যাশবোর্ড অনলি (বার অফ)
                                                </span>
                                            </template>
                                            <template x-if="role.key !== 'administrator' && accessPanel[role.key] == 0">
                                                <span class="px-2.5 py-1 bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-lg text-[10px] font-extrabold uppercase">
                                                    ফ্রন্টএন্ড পোর্টাল অনলি
                                                </span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3.5 bg-slate-800/80 rounded-2xl border border-slate-700/80 flex items-start gap-2.5 text-xs text-slate-300">
                        <span class="text-lg">💡</span>
                        <div>
                            <strong>নিরাপত্তা ও স্মুথ অভিজ্ঞতা:</strong> বায়ার বা সাধারণ গ্রাহক রোলের ইউজাররা ব্যাকএন্ডের <code>/admin</code> এ প্রবেশের চেষ্টা করলে কোনো এরর দেখানোর পরিবর্তে স্বয়ংক্রিয়ভাবে তাদের ডেডিকেটেড ফ্রন্টএন্ড পোর্টাল বা ড্যাশবোর্ডে নিরাপদে পাঠিয়ে দেওয়া হবে। যেকোনো পরিবর্তন শেষে নিচের <strong>"সেটিংস সেভ করুন"</strong> বাটনে ক্লিক করুন।
                        </div>
                    </div>
                </div>

                <!-- Agent/Owner First Login Policy Notice Card -->
                <div class="p-6 bg-emerald-50/70 rounded-2xl border border-emerald-200 space-y-4">
                    <div class="flex items-center gap-2 border-b border-emerald-200/80 pb-3">
                        <span class="text-2xl">📜</span>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">এজেন্ট/ওনার চার্জ পলিসি ও প্রথমবার লগইন নোটিশ (Charge Policy & Terms)</h2>
                            <p class="text-xs text-slate-600 m-0 mt-0.5">
                                ভেরিফাইড এজেন্ট বা ওনার প্রথমবার ড্যাশবোর্ডে প্রবেশ করলে এই শর্তাবলী ও পলিসি পপআপ আকারে প্রদর্শিত হবে।
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1 text-xs">পলিসি ও শর্তাবলী টেক্সট (HTML / Text)</label>
                        <textarea name="adm_agent_policy_notice" rows="8" placeholder="এখানে প্ল্যাটফর্মের চার্জ পলিসি ও নিয়মাবলী লিখুন..." class="w-full border rounded-xl p-3 bg-white font-sans text-xs leading-relaxed text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ $agent_policy_notice ?: "স্বাগতম! আফতাবনগর ডিরেক্ট মার্কেটপ্লেসে আপনার সম্পত্তি তালিকাভুক্ত করার পূর্বে আমাদের কিছু গুরুত্বপূর্ণ নীতিমালা ও চার্জ পলিসি জেনে নিন:\n\n১. সাকসেস ফি ও সার্ভিস চার্জ:\n   • জমি বা ফ্ল্যাট চূড়ান্ত বিক্রয় সম্পন্ন হলে সফল ডিলের উপর নির্ধারিত ১% (বা চুক্তি অনুযায়ী) প্ল্যাটফর্ম কোঅর্ডিনেশন ফি প্রযোজ্য হবে।\n   • তালিকাভুক্তি ও প্রাথমিক ভেরিফিকেশন সম্পূর্ণ বিনামূল্যে।\n\n২. ডিরেক্ট সোর্স ও স্বচ্ছতা:\n   • প্ল্যাটফর্মে কোনো ভুয়া বা অসত্য তথ্য প্রদান করা যাবে না।\n   • সকল ডিরেক্ট ওনার ও এজেন্টের পরিচিতি প্ল্যাটফর্ম অডিট টিম দ্বারা যাচাই করা হয়।\n\n৩. চেইন ব্রোকার ও বাইপাস নিষেধাজ্ঞা:\n   • প্ল্যাটফর্মের সমন্বিত গ্রাহকের সাথে গোপনে যোগাযোগ করে প্ল্যাটফর্ম বাইপাস করার চেষ্টা করলে আইডি স্থায়ীভাবে পেনাল্টি ফ্ল্যাগ করা হবে।\n\n৪. হোয়াটসঅ্যাপ ও ফোন ভেরিফিকেশন:\n   • জরুরি প্রয়োজনে আমাদের ভেরিফিকেশন টিম আপনার সাথে যোগাযোগ করবে।" }}</textarea>
                    </div>
                </div>

                <!-- Public Safety Tips Settings Card -->
                <div class="p-6 bg-amber-50/70 rounded-2xl border border-amber-200 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🛡️</span>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900 m-0">নিরাপত্তা পরামর্শ সেটিংস (Public Safety Tips - aabason.com Style)</h2>
                            <p class="text-xs text-slate-600 m-0 mt-0.5">
                                প্রপার্টি ডিটেইল পেজের ডানপাশের সাইডবারে ক্রেতাদের সচেতনতার জন্য এই নিরাপত্তা নির্দেশনা ও টিপস প্রদর্শিত হবে।
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1 text-xs">নিরাপত্তা নির্দেশনা ও নির্দেশাবলী (Safety Tips Content)</label>
                        <textarea name="adm_safety_tips" rows="10" placeholder="এখানে প্রপার্টি ক্রয়-বিক্রয়ের নিরাপত্তা টিপস লিখুন..." class="w-full border rounded-xl p-3.5 bg-white font-sans text-xs leading-relaxed text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">{{ $safety_tips ?: "১. সরজমিনে প্রপার্টি পরিদর্শন করুন:\n   • চুক্তি চূড়ান্ত করার আগে নিজে বা পরিবারের অভিজ্ঞ কাউকে নিয়ে সরাসরি ফ্ল্যাট/প্লট ভিজিট করুন।\n   • দিনের আলোতে প্রপার্টির সার্বিক অবস্থা, রাস্তা, ইউটিলিটি (গ্যাস, বিদ্যুৎ, পানি) ও আশপাশের পরিবেশ যাচাই করুন。\n\n২. মালিকানা ও আইনি কাগজপত্র যাচাই করুন:\n   • মূল দলিল, বায়া দলিল, খতিয়ান (সিএস, এসএ, আরএস, বিএস), মিউটেশন (নামজারি) পর্চা ও হালনাগাদ ডিসিআর/খাজনা দাখিলা পরীক্ষা করুন।\n   • রাজউক/অনুমোদিত কর্তৃপক্ষের প্ল্যান অনুমোদন ও ছাড়পত্র অভিজ্ঞ আইনজীবী দিয়ে যাচাই করিয়ে নিন。\n\n৩. যাচাই ব্যতীত অগ্রিম টাকা দেওয়া থেকে বিরত থাকুন:\n   • কাগজপত্র পুঙ্খানুপুঙ্খভাবে যাচাই ও রেজিস্ট্রিকৃত আইনসম্মত বায়না চুক্তিপত্র ছাড়া কোনো প্রকার বড় অঙ্কের টাকা, ক্যাশ বা ব্যাংকে ট্রান্সফার করবেন না।\n   • সকল আর্থিক লেনদেনের লিখিত রসিদ ও ব্যাংকিং রেকর্ড সংরক্ষণ করুন。\n\n৪. অবাস্তব কম দাম বা চটকদার অফার থেকে সতর্ক থাকুন:\n   • বাজারমূল্যের চেয়ে অস্বাভাবিক কম মূল্যে প্রস্তাবিত প্রপার্টিতে কোনো মামলা-মোকদ্দমা, ঋণের দায় বা ওয়ারিশ সংক্রান্ত গোপন জটিলতা থাকতে পারে。\n\n৫. ব্যক্তিগত ও আর্থিক তথ্যের নিরাপত্তা রক্ষা করুন:\n   • অচেনা ব্যক্তির সাথে অপ্রয়োজনে জাতীয় পরিচয়পত্র, ব্যাংক অ্যাকাউন্ট বা ওটিপি (OTP) শেয়ার করবেন না。\n\n৬. রেজিস্ট্রিকৃত আইনি চুক্তি সম্পাদন করুন:\n   • প্রপার্টি ক্রয়ের প্রাথমিক বায়নাপত্র অবশ্যই আইন অনুযায়ী নোটারি বা সাব-রেজিস্ট্রি অফিসে রেজিস্ট্রি করুন, যাতে উভয় পক্ষের স্বার্থ সুরক্ষিত থাকে。\n\n৭. প্ল্যাটফর্মের সহায়তা নিন ও সন্দেহজনক কিছু দেখলে জানান:\n   • কোনো বিক্রেতা বা এজেন্টের তথ্য সন্দেহজনক বা বিভ্রান্তিকর মনে হলে অবিলম্বে আমাদের প্ল্যাটফর্ম সাপোর্ট টিমে অভিযোগ করুন।" }}</textarea>
                        <p class="text-[11px] text-slate-500 mt-1 m-0">প্রতিটি পয়েন্ট নতুন লাইনে লিখুন। এটি প্রপার্টি পেজে সুন্দর তালিকা আকারে স্বয়ংক্রিয়ভাবে উপস্থাপিত হবে。</p>
                    </div>
                </div>

                <!-- Role-wise Login & Registration Redirection Repeater Card -->
                <div class="p-6 bg-purple-50/70 rounded-2xl border border-purple-200 space-y-5">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-purple-200/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">🔀</span>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 m-0">রোল-ভিত্তিক লগইন ও রেজিস্ট্রেশন রিডাইরেকশন (Role-wise Redirection)</h2>
                                <p class="text-xs text-slate-600 m-0 mt-0.5">
                                    প্রতিটি রোলের জন্য আলাদা পেজ বা কাস্টম URL সেট করুন। লগইন/রেজিস্ট্রেশন শেষে ইউজার সরাসরি সেই পেজে রিডাইরেক্ট হবে।
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="addRule()"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-extrabold text-xs shadow-sm transition inline-flex items-center gap-1.5 shrink-0 cursor-pointer">
                            <span>+ নতুন রুল যোগ করুন</span>
                        </button>
                    </div>

                    <!-- Hidden Input with Serialized JSON for submission -->
                    <input type="hidden" name="adm_role_redirect_rules" :value="JSON.stringify(rules)">

                    <!-- Repeater Items List -->
                    <div class="space-y-3">
                        <template x-for="(rule, index) in rules" :key="index">
                            <div class="bg-white rounded-2xl border border-purple-200/70 p-4 shadow-xs hover:border-purple-300 transition-all flex flex-col md:flex-row items-start md:items-center gap-3 text-xs">
                                <!-- Index Badge -->
                                <div class="w-6 h-6 rounded-lg bg-purple-100 text-purple-800 font-mono font-black flex items-center justify-center text-[11px] shrink-0"
                                     x-text="index + 1">
                                </div>

                                <!-- Role Selector -->
                                <div class="w-full md:w-56 shrink-0">
                                    <label class="block font-bold text-slate-700 uppercase mb-1 text-[10px]">ইউজার রোল (User Role) *</label>
                                    <select x-model="rule.role" class="w-full border rounded-xl px-3 py-2 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500 text-xs">
                                        <template x-for="r in rolesList" :key="r.key">
                                            <option :value="r.key" x-text="r.label" :selected="rule.role === r.key"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Type Selector (Page vs Custom URL) -->
                                <div class="w-full md:w-44 shrink-0">
                                    <label class="block font-bold text-slate-700 uppercase mb-1 text-[10px]">রিডাইরেকশন টাইপ *</label>
                                    <select x-model="rule.type" class="w-full border rounded-xl px-3 py-2 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500 text-xs">
                                        <option value="page">📄 পেজ নির্বাচন (Page)</option>
                                        <option value="custom_url">🔗 কাস্টম URL (Custom URL)</option>
                                    </select>
                                </div>

                                <!-- Value Input (Page select or Custom URL input) -->
                                <div class="flex-1 w-full">
                                    <!-- When Page -->
                                    <div x-show="rule.type === 'page'">
                                        <label class="block font-bold text-slate-700 uppercase mb-1 text-[10px]">টার্গেট পেজ (Select Page) *</label>
                                        <select x-model="rule.page_id" class="w-full border rounded-xl px-3 py-2 bg-white font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 text-xs">
                                            <option value="">-- পেজ নির্বাচন করুন --</option>
                                            <template x-for="p in pages" :key="p.id">
                                                <option :value="p.id" x-text="p.title + ' (/' + p.slug + '/)'" :selected="String(rule.page_id) === String(p.id)"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- When Custom URL -->
                                    <div x-show="rule.type === 'custom_url'">
                                        <label class="block font-bold text-slate-700 uppercase mb-1 text-[10px]">কাস্টম URL (Path বা Full URL) *</label>
                                        <input type="text" x-model="rule.custom_url" placeholder="যেমন: /admin বা https://..."
                                               class="w-full border rounded-xl px-3.5 py-2 bg-white font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 text-xs">
                                    </div>
                                </div>

                                <!-- Delete Row Button -->
                                <div class="shrink-0 pt-4 md:pt-0 self-end md:self-center">
                                    <button type="button" @click="removeRule(index)"
                                            class="p-2 text-rose-500 hover:text-white hover:bg-rose-600 rounded-xl transition border border-rose-200 hover:border-rose-600 cursor-pointer"
                                            title="এই রুলটি মুছুন">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="pt-2 flex justify-start">
                        <button type="button" @click="addRule()"
                                class="px-4 py-2.5 bg-white hover:bg-purple-100 text-purple-700 border border-purple-300 rounded-xl font-bold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                            <span>+ আরেকটি রোল রুল যোগ করুন</span>
                        </button>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════════════════════ -->
                <!-- 5.4 GOOGLE OAUTH 2.0 LOGIN & AUTHENTICATION CARD -->
                <!-- ══════════════════════════════════════════════════════════════════════════ -->
                <div class="p-6 bg-gradient-to-br from-white via-slate-50 to-blue-50/30 rounded-3xl border border-slate-200 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white border border-slate-200 rounded-2xl flex items-center justify-center shadow-xs shrink-0">
                                <svg class="w-6 h-6" viewBox="0 0 24 24">
                                    <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.8 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
                                    <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
                                    <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 12.3 0 15s.7 5.3 1.9 7.7l3.7-2.9z"/>
                                    <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2-6.4-4.8L1.9 16.4C3.7 20.4 7.5 23 12 23z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base sm:text-lg font-black text-slate-900 m-0">গুগল সাইন-ইন ও অথেনটিকেশন (Google OAuth 2.0 Login)</h2>
                                    @if(!empty($google_client_id) && !empty($google_client_secret) && $google_login_enabled)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                            সক্রিয় (Active)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300">
                                            অসম্পূর্ণ (Setup Required)
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 m-0 mt-0.5">
                                    ব্যবহারকারীরা যেন ১-ক্লিকে তাদের Gmail / Google অ্যাকাউন্ট দিয়ে লগইন ও রেজিস্ট্রেশন করতে পারে তার কনফিগারেশন।
                                </p>
                            </div>
                        </div>

                        <!-- Toggle Switch -->
                        <label class="relative inline-flex items-center cursor-pointer select-none shrink-0">
                            <input type="checkbox" name="adm_google_login_enabled" value="yes" class="sr-only peer" {{ $google_login_enabled ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ms-2.5 text-xs font-bold text-slate-700">গুগল সাইন-ইন বাটন চালু রাখুন</span>
                        </label>
                    </div>

                    <!-- Credentials Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Client ID -->
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1 text-xs">
                                Google Client ID <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   name="adm_google_client_id"
                                   value="{{ $google_client_id }}"
                                   placeholder="যেমন: 1234567890-abcdefg...apps.googleusercontent.com"
                                   class="w-full border rounded-xl px-3.5 py-2.5 bg-white font-mono text-xs text-slate-900 border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-[11px] text-slate-500 mt-1 m-0">Google Cloud Console-এর OAuth 2.0 Client IDs সেকশন থেকে কপি করে বসান।</p>
                        </div>

                        <!-- Client Secret -->
                        <div x-data="{ showSecret: false }">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-800 uppercase text-xs">
                                    Google Client Secret <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="showSecret = !showSecret" class="text-[11px] text-blue-600 hover:text-blue-800 font-bold cursor-pointer">
                                    <span x-text="showSecret ? 'গোপন করুন' : 'দেখান'"></span>
                                </button>
                            </div>
                            <div class="relative">
                                <input :type="showSecret ? 'text' : 'password'"
                                       name="adm_google_client_secret"
                                       value="{{ $google_client_secret }}"
                                       placeholder="যেমন: GOCSPX-xxxxxxxxxxxxxxxxx"
                                       class="w-full border rounded-xl px-3.5 py-2.5 bg-white font-mono text-xs text-slate-900 border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                <button type="button" @click="showSecret = !showSecret" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <svg x-show="!showSecret" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showSecret" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1 m-0">গুগল থেকে প্রাপ্ত Client Secret এখানে নিরাপদে সংরক্ষণ করা থাকবে।</p>
                        </div>
                    </div>

                    <!-- Authorized Redirect URI with 1-Click Copy -->
                    <div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 text-white space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="text-xs font-black text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🔗</span> অনুমোদিত রিডাইরেক্ট ইউআরএল (Authorized Redirect URI)
                                </span>
                                <p class="text-[11px] text-slate-400 m-0 mt-0.5">
                                    Google Cloud Console-এর Credentials এ এই ইউআরএল-টি হুবহু পেস্ট করতে হবে (ভুল হলে <code>redirect_uri_mismatch</code> এরর আসবে):
                                </p>
                            </div>
                            <div x-data="{ copied: false }">
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $google_redirect_uri }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span x-text="copied ? '✓ লিংক কপি হয়েছে!' : 'URI কপি করুন'"></span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="text"
                                   readonly
                                   value="{{ $google_redirect_uri }}"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 font-mono text-xs font-bold text-emerald-300 focus:outline-none select-all">
                        </div>
                    </div>

                    <!-- Step-by-Step Setup Guide Accordion -->
                    <div x-data="{ openGuide: false }" class="border border-slate-200 rounded-2xl bg-white overflow-hidden">
                        <button type="button"
                                @click="openGuide = !openGuide"
                                class="w-full px-4 py-3 bg-slate-50 hover:bg-slate-100 flex items-center justify-between text-xs font-bold text-slate-800 transition cursor-pointer">
                            <span class="flex items-center gap-2">
                                <span>📘</span>
                                <span>গুগল ক্লাউড কনসোল (Google Cloud Console) সেটআপ নির্দেশিকা (কিভাবে Client ID ও Secret পাবেন?)</span>
                            </span>
                            <span x-text="openGuide ? '▲ বন্ধ করুন' : '▼ বিস্তারিত দেখুন'" class="text-blue-600 font-semibold"></span>
                        </button>

                        <div x-show="openGuide" x-cloak class="p-5 text-xs text-slate-700 space-y-3 bg-white border-t border-slate-200 leading-relaxed">
                            <ol class="list-decimal list-inside space-y-2 font-medium">
                                <li>
                                    প্রথমে ব্রাউজারে <a href="https://console.cloud.google.com" target="_blank" class="text-blue-600 underline font-bold">Google Cloud Console (console.cloud.google.com)</a> এ গিয়ে আপনার জিমেইল দিয়ে লগইন করুন এবং একটি নতুন প্রজেক্ট তৈরি করুন (বা পূর্বের প্রজেক্ট সিলেক্ট করুন)।
                                </li>
                                <li>
                                    বামপাশের মেনু থেকে <strong>APIs & Services</strong> &rarr; <strong>OAuth consent screen</strong> এ যান। User Type হিসেবে <strong>External</strong> নির্বাচন করে অ্যাপের নাম (যেমন: PropertyKoi) ও সাপোর্ট ইমেইল দিয়ে Save & Continue দিয়ে সম্পন্ন করুন।
                                </li>
                                <li>
                                    এরপর <strong>APIs & Services</strong> &rarr; <strong>Credentials</strong> এ ক্লিক করুন এবং ওপরের <strong>+ CREATE CREDENTIALS</strong> বাটনে ক্লিক করে <strong>OAuth client ID</strong> নির্বাচন করুন।
                                </li>
                                <li>
                                    <strong>Application type</strong> ড্রপডাউনে <strong>Web application</strong> সিলেক্ট করুন।
                                </li>
                                <li>
                                    <strong>Authorized redirect URIs</strong> সেকশনে <strong>+ ADD URI</strong> বাটনে ক্লিক করে ওপরের কপি করা লিংকটি (<code>{{ $google_redirect_uri }}</code>) পেস্ট করুন।
                                </li>
                                <li>
                                    <strong>CREATE</strong> বাটনে ক্লিক করলেই আপনার <strong>Client ID</strong> এবং <strong>Client Secret</strong> পপআপে প্রদর্শিত হবে। সেগুলো কপি করে এই পেজের যথাস্থানে পেস্ট করে নিচের <strong>"সেটিংস সেভ করুন"</strong> বাটনে ক্লিক করুন।
                                </li>
                            </ol>
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-[11px] font-medium flex items-start gap-2">
                                <span class="text-sm">💡</span>
                                <span><strong>টিপস:</strong> আপনি চাইলে সরাসরি প্রজেক্টের <code>.env</code> ফাইলে <code>GOOGLE_CLIENT_ID=your_id</code> এবং <code>GOOGLE_CLIENT_SECRET=your_secret</code> লিখেও কাজ চালাতে পারেন। তবে এডমিন প্যানেলের এই অপশনে সেভ করলে ডাটাবেজে সংরক্ষিত থাকবে এবং সাইট রিস্টার্ট বা .env এডিট ছাড়াই তাৎক্ষণিক কার্যকর হবে!</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /activeTab === 'access' -->

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 6: AGENT & OWNER MESSAGE TEMPLATES -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'messages'" x-cloak class="space-y-6" x-data="admMessagesSettings()">

                <!-- Header Banner -->
                <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-lg relative overflow-hidden">
                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl">💬</span>
                                <h3 class="text-lg sm:text-xl font-black text-white m-0">এজেন্ট ও ওনার মেসেজ টেমপ্লেট কাস্টমাইজেশন</h3>
                            </div>
                            <p class="text-xs text-emerald-200/90 mt-1.5 max-w-2xl leading-relaxed m-0">
                                এজেন্ট, ওনার ও কন্টাক্ট পার্সনদের হোয়াটসঅ্যাপ ও অন্যান্য মাধ্যমে প্রেরিত বিভিন্ন মেসেজ এখান থেকে কাস্টমাইজ করুন। প্রতিটি টেমপ্লেটে ডায়নামিক ভেরিয়েবল ট্যাগ (যেমন: <code class="bg-white/20 px-1.5 py-0.5 rounded text-emerald-100 font-mono">%name%</code>, <code class="bg-white/20 px-1.5 py-0.5 rounded text-emerald-100 font-mono">%login_magic_link%</code>) স্বয়ংক্রিয়ভাবে সংশ্লিষ্ট তথ্যে প্রতিস্থাপিত হবে।
                            </p>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <span class="px-3.5 py-1.5 bg-emerald-500/25 border border-emerald-400/40 rounded-xl text-emerald-200 text-xs font-bold font-mono">
                                ৫টি সিস্টেম টেমপ্লেট সক্রিয়
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Global Placeholder Cheatsheet Pill Bar -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🏷️</span> গ্লোবাল সিনট্যাক্স ট্যাগ চিটশিট (Click to Copy / Quick Reference)
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">নিচের যেকোনো টেমপ্লেটে এই ট্যাগগুলো কাজ করবে</span>
                    </div>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-emerald-700">%name%</span>
                            <span class="text-[11px] text-slate-500">= ব্যবহারকারীর পুরো নাম</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-purple-700">%login_magic_link%</span>
                            <span class="text-[11px] text-slate-500">= ১-ক্লিক অটো লগইন URL</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-blue-700">%property_code%</span>
                            <span class="text-[11px] text-slate-500">= প্রপার্টি কোড</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-blue-700">%property_title%</span>
                            <span class="text-[11px] text-slate-500">= প্রপার্টির নাম</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-blue-700">%property_url%</span>
                            <span class="text-[11px] text-slate-500">= বিস্তারিত পেজ লিঙ্ক</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-slate-700">%price%</span>
                            <span class="text-[11px] text-slate-500">= প্রপার্টির মূল্য</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-slate-700">%location%</span>
                            <span class="text-[11px] text-slate-500">= এলাকা ও অবস্থান</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-amber-700">%enquiry_type%</span>
                            <span class="text-[11px] text-slate-500">= লিডের ধরন</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-amber-700">%lead_details%</span>
                            <span class="text-[11px] text-slate-500">= বায়ার চাহিদা / নোট</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-rose-700">%penalty_reason%</span>
                            <span class="text-[11px] text-slate-500">= পেনাল্টি বা সতর্কতার কারণ</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                            <span class="font-mono font-bold text-teal-700">%site_name%</span>
                            <span class="text-[11px] text-slate-500">= প্ল্যাটফর্মের নাম</span>
                        </div>
                    </div>
                </div>

                <!-- Template Editors Loop -->
                @foreach ($template_definitions as $tpl_key => $tpl)
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-7 space-y-4 transition hover:border-slate-300">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-base font-extrabold text-slate-900 m-0">{{ $tpl['title'] }}</h4>
                                    <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-full text-[10px] font-extrabold uppercase">
                                        {{ $tpl['category'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 m-0">
                                    {{ $tpl['description'] }}
                                </p>
                            </div>
                            
                            <div class="shrink-0">
                                <button type="button" @click="resetToDefault('{{ $tpl_key }}')"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                                    <span>🔄</span>
                                    <span>ডিফল্টে রিসেট</span>
                                </button>
                            </div>
                        </div>

                        <!-- Clickable Available Variables Pill Bar -->
                        <div>
                            <div class="text-[11px] font-extrabold text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1">
                                <span>⚡</span> কার্সারে ট্যাগ বসাতে ক্লিক করুন:
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($tpl['variables'] as $var_tag => $var_meta)
                                    <button type="button"
                                            @click="insertTag('{{ $tpl_key }}', '{{ $var_tag }}')"
                                            class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 active:scale-95 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-mono font-bold transition flex items-center gap-1 cursor-pointer"
                                            title="{{ $var_meta['label'] }} (নমুনা: {{ $var_meta['sample'] }})">
                                        <span>+</span>
                                        <span>{{ $var_tag }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- 2-Column Layout: Textarea Editor + Live WhatsApp Preview -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 pt-1">
                            <!-- Left: Textarea -->
                            <div class="lg:col-span-7 flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 flex items-center justify-between">
                                    <span>মেসেজ টেমপ্লেট বডি:</span>
                                    <span class="text-[10px] text-slate-400 font-normal">স্পেস ও নতুন লাইন সংরক্ষিত থাকবে</span>
                                </label>
                                <textarea id="adm_tpl_{{ $tpl_key }}"
                                          name="adm_msg_template_{{ $tpl_key }}"
                                          x-model="templates.{{ $tpl_key }}"
                                          rows="8"
                                          class="w-full border border-slate-300 rounded-2xl p-4 bg-slate-50 font-sans text-xs text-slate-800 leading-relaxed focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition shadow-inner resize-y"></textarea>
                                <p class="text-[10.5px] text-slate-400 m-0">
                                    💡 পরামর্শ: যেকোনো ট্যাগে ক্লিক করলে তা কার্সারের স্থানে যুক্ত হবে।
                                </p>
                            </div>

                            <!-- Right: Live WhatsApp Chat Bubble Preview -->
                            <div class="lg:col-span-5 flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <span>📱</span>
                                    <span>হোয়াটসঅ্যাপ প্রিভিউ (নমুনা ডেটা সহ):</span>
                                </label>
                                <div class="bg-[#efeae2] border border-slate-300 rounded-2xl p-4 flex flex-col justify-between min-h-[190px] shadow-inner relative overflow-hidden"
                                     style="background-image: radial-gradient(#d1d7db 1px, transparent 1px); background-size: 16px 16px;">
                                    <div class="bg-[#d9fdd3] text-slate-900 rounded-2xl rounded-tl-none p-3.5 shadow-sm text-xs leading-relaxed whitespace-pre-wrap font-sans border border-[#bbf7d0]/60 max-w-full break-words relative self-start">
                                        <div x-text="renderPreview('{{ $tpl_key }}')"></div>
                                        <div class="text-[9px] text-slate-400 text-right mt-1 font-mono font-medium flex items-center justify-end gap-1">
                                            <span>১০:৩৫ AM</span>
                                            <span class="text-blue-500 font-bold">✓✓</span>
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-slate-500/80 text-center font-medium mt-2 pt-2 border-t border-slate-200/50">
                                        সরাসরি হোয়াটসঅ্যাপে ব্যবহারকারী ঠিক এই বার্তাটি দেখতে পাবেন
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div><!-- /activeTab === 'messages' -->

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 7: SEO & AEO (AI SEARCH & INDEXING) -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'seo'" x-cloak class="space-y-6" x-data="admSeoSettings()">
                <!-- Hero Info Banner -->
                <div class="p-6 bg-gradient-to-r from-emerald-900 to-slate-900 text-white rounded-3xl shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-emerald-400 font-bold text-xs uppercase tracking-wider mb-1">
                            <span>🚀</span> <span>Search & AI Answer Engine Optimization</span>
                        </div>
                        <h2 class="text-xl font-black text-white m-0">গুগল এসইও এবং এআই সার্চ (AEO) কন্ট্রোল সেন্টার</h2>
                        <p class="text-xs text-slate-300 mt-1 m-0">গুগল সার্চ কনসোল, জেমিনাই (Gemini), চ্যাটজিপিটি সার্চ ও পারপ্লেক্সিটির জন্য সাইটটিকে ১০০% অপটিমাইজড ও ইনডেক্সড রাখুন।</p>
                    </div>
                    <button type="button"
                            @click="pingIndexNow()"
                            :disabled="isPinging"
                            class="shrink-0 px-5 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs rounded-2xl shadow-lg transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <span x-show="!isPinging">⚡ সব প্রপার্টি IndexNow-তে পিং করুন</span>
                        <span x-show="isPinging">⏳ পিং পাঠানো হচ্ছে...</span>
                    </button>
                </div>

                <div x-show="pingResultMsg" x-cloak class="p-4 rounded-2xl text-xs font-bold"
                     :class="pingSuccess ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300'"
                     x-text="pingResultMsg"></div>

                <!-- Open Graph & Social Share Preview Images Card -->
                <div class="bg-gradient-to-br from-indigo-50/80 to-sky-50/50 border border-indigo-200/90 rounded-3xl p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-indigo-200/60 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-3xl">🖼️</span>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 m-0">সোশ্যাল মিডিয়া শেয়ার প্রিভিউ ইমেজ (Open Graph / WhatsApp / Facebook)</h3>
                                <p class="text-xs text-slate-600 m-0 mt-0.5">WhatsApp, Facebook, LinkedIn ও Twitter-এ যেকোনো পেজের লিংক শেয়ার করার সময় যে প্রিভিউ ব্যানার ও টাইটেল প্রদর্শিত হবে তা এখান থেকে কাস্টমাইজ করতে পারবেন।</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 font-bold text-xs rounded-full">1200x630 অপ্টিমাইজড</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
                        <!-- Chahida Page OG Image -->
                        <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-900 uppercase block">চাহিদা পেজ প্রিভিউ ইমেজ (/chahida)</label>
                                <span class="text-[10px] bg-sky-100 text-sky-800 px-2 py-0.5 rounded-full font-bold">Buyer Demand</span>
                            </div>
                            <div class="h-32 w-full rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative">
                                <img src="{{ $seo_chahida_og_image ?: asset('assets/images/og-chahida.jpg') }}" alt="Chahida OG" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <input type="url" name="adm_seo_chahida_og_image" value="{{ $seo_chahida_og_image }}" placeholder="কাস্টম ইমেজ URL দিন (ফাঁকা রাখলে বিল্ট-ইন ব্যানার ব্যবহার হবে)" class="w-full border rounded-xl px-3 py-2 bg-slate-50 font-mono text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="text-[10px] text-slate-500 mt-1 block">ডিফল্টভাবে রিয়েল এস্টেট প্রপার্টি রিকোয়েস্টের উপযোগী হাই-রেজ্যুলেশন ব্যানার সেট করা রয়েছে।</span>
                            </div>
                        </div>

                        <!-- Default / Home Page OG Image -->
                        <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-900 uppercase block">ডিফল্ট / হোমপেজ প্রিভিউ ইমেজ (Home & Fallback)</label>
                                <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold">Homepage & All Pages</span>
                            </div>
                            <div class="h-32 w-full rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative">
                                <img src="{{ $seo_default_og_image ?: asset('assets/images/og-default.jpg') }}" alt="Default OG" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <input type="url" name="adm_seo_default_og_image" value="{{ $seo_default_og_image }}" placeholder="কাস্টম ইমেজ URL দিন (ফাঁকা রাখলে বিল্ট-ইন লাক্সারি ভিলা ব্যবহার হবে)" class="w-full border rounded-xl px-3 py-2 bg-slate-50 font-mono text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="text-[10px] text-slate-500 mt-1 block">হোমপেজ ও যেসব পেজে আলাদা ফিচার্ড ইমেজ নেই, সেখানে স্বয়ংক্রিয়ভাবে এটি দেখাবে।</span>
                            </div>
                        </div>

                        <!-- Sell Property OG Image -->
                        <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-900 uppercase block">প্রপার্টি বিক্রয় পেজ (/sell-property)</label>
                                <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold">Sell / Add Property</span>
                            </div>
                            <div class="h-32 w-full rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative">
                                <img src="{{ $seo_sell_og_image ?: asset('assets/images/og-sell.jpg') }}" alt="Sell OG" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <input type="url" name="adm_seo_sell_og_image" value="{{ $seo_sell_og_image }}" placeholder="কাস্টম ইমেজ URL দিন (ঐচ্ছিক)" class="w-full border rounded-xl px-3 py-2 bg-slate-50 font-mono text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Flat & Land Search OG Images -->
                        <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-900 uppercase block">ফ্ল্যাট ও জমি সার্চ পেজ (Search Hubs)</label>
                                <span class="text-[10px] bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full font-bold">Land & Flat Search</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="h-32 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative">
                                    <img src="{{ $seo_land_og_image ?: asset('assets/images/og-land.jpg') }}" alt="Land OG" class="w-full h-full object-cover">
                                    <span class="absolute bottom-1 left-1 bg-black/70 text-white text-[9px] px-1.5 py-0.5 rounded">জমি / প্লট</span>
                                </div>
                                <div class="h-32 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 relative">
                                    <img src="{{ $seo_flat_og_image ?: asset('assets/images/og-flat.jpg') }}" alt="Flat OG" class="w-full h-full object-cover">
                                    <span class="absolute bottom-1 left-1 bg-black/70 text-white text-[9px] px-1.5 py-0.5 rounded">ফ্ল্যাট</span>
                                </div>
                            </div>
                            <div>
                                <input type="url" name="adm_seo_land_og_image" value="{{ $seo_land_og_image }}" placeholder="জমি সার্চের কাস্টম URL (ঐচ্ছিক)" class="w-full border rounded-xl px-3 py-1.5 bg-slate-50 font-mono text-xs text-slate-800 focus:outline-none mb-1.5">
                                <input type="url" name="adm_seo_flat_og_image" value="{{ $seo_flat_og_image }}" placeholder="ফ্ল্যাট সার্চের কাস্টম URL (ঐচ্ছিক)" class="w-full border rounded-xl px-3 py-1.5 bg-slate-50 font-mono text-xs text-slate-800 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-indigo-950 bg-indigo-100/70 border border-indigo-200 p-4 rounded-2xl space-y-1.5">
                        <div class="font-bold flex items-center gap-1.5">
                            <span>💡</span>
                            <span>হোয়াটসঅ্যাপ ও ফেসবুক প্রিভিউ ক্যাশ (Scraping Cache) ক্লিয়ার করার নিয়ম:</span>
                        </div>
                        <p class="text-[11px] leading-relaxed m-0 text-slate-700">
                            হোয়াটসঅ্যাপ ও ফেসবুক তাদের সার্ভারে আগের প্রিভিউ বেশ কিছুদিন সেভ করে রাখে। আপনি ইমেজ পরিবর্তন করার পর নতুন ছবি দেখতে চাইলে <a href="https://developers.facebook.com/tools/debug/" target="_blank" class="underline font-bold text-indigo-700">Facebook Sharing Debugger</a> টুলে গিয়ে আপনার পেজের লিংক দিয়ে <strong>"Scrape Again"</strong> বাটনে ক্লিক করুন অথবা নতুন প্রিভিউ তাৎক্ষণিক দেখতে লিংকের শেষে <code>?v=2</code> যোগ করে শেয়ার করুন।
                        </p>
                    </div>
                </div>

                <!-- Dynamic XML Sitemap Card -->
                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🗺️</span>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 m-0">ডায়নামিক প্রপার্টি XML সাইটম্যাপ (Dynamic XML Sitemap)</h3>
                                <p class="text-xs text-slate-500 m-0">সার্চ ইঞ্জিনের জন্য স্বয়ংক্রিয়ভাবে আপডেট হওয়া এক্সএমএল সাইটম্যাপ</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 font-bold text-xs rounded-full">স্বয়ংক্রিয় লাইভ সিঙ্ক</span>
                    </div>

                    <div class="p-4 bg-white rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="font-mono text-xs text-slate-700 select-all truncate w-full sm:w-auto">
                            {{ url('/property-sitemap.xml') }}
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ url('/property-sitemap.xml') }}" target="_blank"
                               class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition">
                                সাইটম্যাপ দেখুন ↗
                            </a>
                            <button type="button"
                                    @click="navigator.clipboard.writeText('{{ url('/property-sitemap.xml') }}'); alert('সাইটম্যাপের লিংক কপি করা হয়েছে!')"
                                    class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition cursor-pointer">
                                লিংক কপি করুন 📋
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-slate-600 bg-amber-50 border border-amber-200 p-4 rounded-2xl space-y-1">
                        <div class="font-bold text-amber-900">📌 গুগল সার্চ কনসোলে সাইটম্যাপ সাবমিট করার নিয়ম:</div>
                        <ol class="list-decimal list-inside space-y-1 m-0 text-amber-950">
                            <li><a href="https://search.google.com/search-console" target="_blank" class="underline font-bold">Google Search Console</a>-এ লগইন করুন এবং আপনার ডোমেইন সিলেক্ট করুন।</li>
                            <li>বামপাশের মেনু থেকে <strong>Sitemaps</strong> অপশনে যান।</li>
                            <li><strong>Add a new sitemap</strong> বক্সে লিখুন: <code class="bg-white px-1.5 py-0.5 rounded font-mono font-bold">property-sitemap.xml</code> এবং সাবমিট বাটনে চাপ দিন।</li>
                        </ol>
                    </div>
                </div>

                <!-- AEO & AI Readiness Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-5 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🤖</span>
                            <h3 class="text-sm font-extrabold text-slate-900 m-0">AEO ও AI সার্চ ইঞ্জিন ইন্টিগ্রেশন</h3>
                        </div>
                        <p class="text-xs text-slate-600 m-0 leading-relaxed">Gemini, ChatGPT Search, Perplexity ও Claude-এর মতো এআই সার্চ ইঞ্জিনগুলোর জন্য নিচের স্ট্রাকচারড স্কিমাগুলো স্বয়ংক্রিয়ভাবে সক্রিয় রয়েছে:</p>

                        <div class="space-y-2 pt-2">
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">RealEstateListing Schema (JSON-LD)</span>
                                <span class="text-emerald-600 font-bold">✓ সক্রিয়</span>
                            </div>
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">AEO FAQPage Q&A Schema</span>
                                <span class="text-emerald-600 font-bold">✓ সক্রিয়</span>
                            </div>
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">BreadcrumbList Rich Snippets</span>
                                <span class="text-emerald-600 font-bold">✓ সক্রিয়</span>
                            </div>
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">ক্লিন এসইও পারমালিংক (/property/{slug})</span>
                                <span class="text-emerald-600 font-bold">✓ সক্রিয়</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-5 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">⚡</span>
                            <h3 class="text-sm font-extrabold text-slate-900 m-0">IndexNow ও ক্রলার এক্সেস (Robots.txt)</h3>
                        </div>
                        <p class="text-xs text-slate-600 m-0 leading-relaxed">নতুন প্রপার্টি লিস্টিং প্রকাশের সাথে সাথে সার্চ ইঞ্জিনে তাৎক্ষণিক পুশ এবং এআই ক্রলার পারমিশন স্ট্যাটাস:</p>

                        <div class="space-y-2 pt-2">
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">IndexNow Instant Ping Protocol</span>
                                <span class="text-emerald-600 font-bold">✓ এনাবল্ড</span>
                            </div>
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">IndexNow API Key</span>
                                <code class="font-mono text-[11px] text-slate-600">{{ substr($indexnow_key, 0, 10) . '...' }}</code>
                            </div>
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">GPTBot, PerplexityBot, Google-Extended</span>
                                <span class="text-emerald-600 font-bold">✓ এলাউড (Allowed)</span>
                            </div>
                            <div class="flex items-center justify-between text-xs bg-white p-2.5 rounded-xl border border-slate-200">
                                <span class="font-medium text-slate-700">রোবট ফাইল প্রিভিউ (/robots.txt)</span>
                                <a href="{{ url('/robots.txt') }}" target="_blank" class="text-blue-600 font-bold underline">দেখুন ↗</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /activeTab === 'seo' -->

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 8: FACEBOOK AUTO-POST -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div x-show="activeTab === 'facebook'" x-cloak class="space-y-6" x-data="admFacebookSettings()">
                <!-- Header / Status Banner -->
                <div class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-3xl space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">📱</span>
                            <div>
                                <h2 class="text-lg font-extrabold text-blue-950 m-0">Facebook পেজ অটো-পোস্টিং ইঞ্জিন (Graph API)</h2>
                                <p class="text-xs text-blue-800 m-0 mt-0.5">নতুন প্রপার্টি লাইভ/পাবলিশ হওয়ার সাথে সাথে স্বয়ংক্রিয়ভাবে আপনার ফেসবুক পেজে ছবি ও বাংলা ক্যাপশনসহ সিঙ্গেল পোস্ট হিসেবে প্রকাশিত হবে।</p>
                            </div>
                        </div>
                        <div>
                            @if ($is_fb_post_ready)
                                <span class="px-3.5 py-1.5 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    সক্রিয় ও প্রস্তুত (Ready)
                                </span>
                            @else
                                <span class="px-3.5 py-1.5 bg-amber-100 text-amber-800 border border-amber-300 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-xs">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    কনফিগারেশন অসম্পূর্ণ
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Step-by-Step Guide Card -->
                <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-3 shadow-xs">
                    <h3 class="text-sm font-extrabold text-slate-900 m-0 flex items-center gap-2">
                        <span>📖</span> পেজ অ্যাক্সেস টোকেন (Permanent Page Access Token) সংগ্রহের ধাপসমূহ
                    </h3>
                    <ol class="text-xs text-slate-700 space-y-2 list-decimal pl-4 m-0 leading-relaxed font-normal">
                        <li>
                            <strong>Meta for Developers</strong> (<a href="https://developers.facebook.com/apps/" target="_blank" class="text-blue-600 underline font-bold">developers.facebook.com</a>) এ গিয়ে একটি অ্যাপ তৈরি করুন (App Type: <em>Business</em>)।
                        </li>
                        <li>
                            <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="text-blue-600 underline font-bold">Graph API Explorer</a> ওপেন করুন।
                        </li>
                        <li>
                            <strong>Meta App</strong> ড্রপডাউন থেকে আপনার অ্যাপ এবং <strong>User or Page</strong> ড্রপডাউন থেকে আপনার ফেসবুক পেজটি সিলেক্ট করুন।
                        </li>
                        <li>
                            <strong>Permissions</strong> থেকে নিচের ৩টি পারমিশন যোগ করুন:
                            <code class="px-1.5 py-0.5 bg-slate-100 text-blue-700 font-mono text-[11px] rounded font-bold">pages_manage_posts</code>,
                            <code class="px-1.5 py-0.5 bg-slate-100 text-blue-700 font-mono text-[11px] rounded font-bold">pages_read_engagement</code>,
                            <code class="px-1.5 py-0.5 bg-slate-100 text-blue-700 font-mono text-[11px] rounded font-bold">pages_show_list</code>।
                        </li>
                        <li>
                            <strong>Generate Access Token</strong> বাটনে ক্লিক করে পেজের পারমিশন কনফার্ম করুন। এরপর <a href="https://developers.facebook.com/tools/debug/accesstoken/" target="_blank" class="text-blue-600 underline font-bold">Access Token Debugger</a>-এ টোকেনটি পেস্ট করে <strong>Extend Access Token</strong>-এ ক্লিক করলে দীর্ঘমেয়াদী/পার্মানেন্ট পেজ টোকেন পেয়ে যাবেন।
                        </li>
                    </ol>
                </div>

                <!-- Settings Form Fields -->
                <div class="p-6 bg-slate-50 border border-slate-200 rounded-2xl space-y-5">
                    <!-- Enable Toggle -->
                    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <div class="font-extrabold text-sm text-slate-900">Facebook অটো-পোস্টিং চালু রাখুন</div>
                            <div class="text-xs text-slate-500">প্রপার্টি লাইভ বা এপ্রুভ হওয়ার সাথে সাথে পেজে পোস্ট হবে।</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="adm_fb_autopost_enabled" value="yes" {{ $fb_autopost_enabled ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- Page ID & Page Access Token -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-xs text-slate-800 uppercase mb-1">ফেসবুক পেজ আইডি (Page ID) *</label>
                            <input type="text" name="adm_fb_page_id" value="{{ $fb_page_id }}" placeholder="যেমন: 102938475610293" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 bg-white text-xs font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">আপনার ফেসবুক পেজের About সেকশন থেকে Page ID পাবেন।</span>
                        </div>

                        <div>
                            <label class="block font-bold text-xs text-slate-800 uppercase mb-1">পেজ অ্যাক্সেস টোকেন (Page Access Token) *</label>
                            <input type="password" name="adm_fb_page_access_token" value="{{ $fb_page_token }}" placeholder="EAAG..." class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 bg-white text-xs font-mono text-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Never-expiring permanent Page Access Token.</span>
                        </div>
                    </div>

                    <!-- Caption Template -->
                    <div>
                        <label class="block font-bold text-xs text-slate-800 uppercase mb-1">কাস্টম ফেসবুক পোস্ট ক্যাপশন টেমপ্লেট (Custom Caption Template)</label>
                        <textarea name="adm_fb_caption_template" rows="8" placeholder="খালি রাখলে সিস্টেমের ডিফল্ট সুন্দর বাংলা ক্যাপশন স্বয়ংক্রিয়ভাবে ব্যবহৃত হবে..." class="w-full border border-slate-300 rounded-xl p-3 bg-white text-xs font-mono text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none leading-relaxed">{{ $fb_caption_tpl }}</textarea>
                        
                        <div class="mt-2 p-3 bg-white border border-slate-200 rounded-xl text-[11px] text-slate-600 space-y-1">
                            <div class="font-bold text-slate-700">ব্যবহারযোগ্য ট্যাগসমূহ (Available Tags):</div>
                            <div class="flex flex-wrap gap-1.5">
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{title}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{code}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{type}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{area}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{block}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{plot}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{road}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{facing}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{size}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{price}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{url}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{phone}</code>
                                <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold">{description}</code>
                            </div>
                        </div>
                    </div>

                    <!-- Test Button -->
                    <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="text-xs text-slate-500">
                            💡 সেটিংস সেভ করার পর ফেসবুক পেজে টেস্ট পোস্ট পাঠিয়ে যাচাই করুন।
                        </div>
                        <button type="button" 
                                @click="testFbPost()" 
                                :disabled="testingFb"
                                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow transition flex items-center gap-2 cursor-pointer">
                            <span x-show="!testingFb">🚀</span>
                            <span x-show="testingFb" class="inline-block animate-spin">⏳</span>
                            <span x-text="testingFb ? 'পোস্ট পাঠানো হচ্ছে...' : '🧪 ফেসবুক পেজে টেস্ট পোস্ট পাঠান'"></span>
                        </button>
                    </div>

                    <!-- Test Result Alert -->
                    <template x-if="fbTestStatus === 'success'">
                        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-bold flex items-center gap-2">
                            <span>✅</span>
                            <span x-text="fbTestMsg"></span>
                        </div>
                    </template>
                    <template x-if="fbTestStatus === 'error'">
                        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-bold flex items-center gap-2">
                            <span>❌</span>
                            <span x-text="fbTestMsg"></span>
                        </div>
                    </template>
                </div>
            </div><!-- /activeTab === 'facebook' -->

            <!-- Submit Button for Tabs 1-8 -->
            <div x-show="activeTab !== 'penalty' && activeTab !== 'wpimport'" class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200">
                <div class="text-xs text-slate-500 font-medium flex items-center gap-2">
                    <span>💡</span>
                    <span>যেকোনো ট্যাবে সেটিংস পরিবর্তন করে <strong>"সেটিংস সেভ করুন"</strong> বাটনে ক্লিক করলেই সমস্ত ডাটা ডাটাবেজে স্থায়ীভাবে সংরক্ষিত হবে।</span>
                </div>
                <button type="submit" class="px-7 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-lg transition flex items-center gap-2 cursor-pointer shrink-0">
                    <span>💾</span>
                    <span>সেটিংস সেভ করুন &rarr;</span>
                </button>
            </div>
        </form>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- TAB 9: AGENT PENALTY & FRAUD CONTROL -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <div x-show="activeTab === 'penalty'" x-cloak class="space-y-6" x-data="admPenaltyControl()">
            <div class="p-6 bg-rose-50 border border-rose-200 rounded-2xl space-y-4">
                <div>
                    <h2 class="text-base font-extrabold text-rose-950 m-0 flex items-center gap-2">
                        <span>🛡️</span> এজেন্ট পেনাল্টি ও রেটিং কন্ট্রোল সেন্টার (Fraud & Bypass Penalty)
                    </h2>
                    <p class="text-xs text-rose-800 mt-1 m-0">
                        কোনো এজেন্ট বা ডিরেক্ট ওনার যদি সাকসেস ফি বাইপাস করা, ভূয়া প্রপার্টি পোস্ট করা বা চুক্তি ভঙ্গ করার চেষ্টা করে, তবে অ্যাডমিন থেকে তাকে স্থায়ী পেনাল্টি রেটিং (যেমন ১.০ স্টার) ও ফ্ল্যাগ দেওয়া যাবে।
                    </p>
                </div>

                <form method="post" action="{{ route('admin.settings.penalty') }}" class="space-y-4 text-xs bg-white p-5 rounded-xl border border-rose-200 shadow-xs">
                    @csrf

                    <!-- Quick Pick Agent Helper -->
                    @if(isset($all_agents_list) && $all_agents_list->isNotEmpty())
                        <div class="p-3 bg-rose-50/70 border border-rose-200/80 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <span class="font-bold text-slate-800 text-[11px] flex items-center gap-1.5 shrink-0">
                                <span>⚡</span> তালিকা থেকে দ্রুত এজেন্ট বাছাই করুন:
                            </span>
                            <select @change="if($event.target.value) { selectedUserId = $event.target.value; }" class="w-full sm:w-auto grow border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white font-bold text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500">
                                <option value="">-- নিবন্ধিত এজেন্ট বা ওনার বেছে নিন --</option>
                                @foreach($all_agents_list as $ag)
                                    <option value="{{ $ag->id }}" :selected="selectedUserId == {{ $ag->id }}">
                                        {{ $ag->name }} (ID: {{ $ag->id }} • {{ $ag->phone }} • {{ $ag->role === 'agent' ? 'এজেন্ট' : 'মালিক' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">এজেন্ট / ইউজার আইডি (User ID) *</label>
                            <input type="number" name="user_id" x-model="selectedUserId" required class="w-full border rounded-xl px-3 py-2 bg-slate-50 font-bold text-xs">
                            <span class="text-[10px] text-slate-500">ডিফল্ট প্রাইমারি টেস্ট ইউজার আইডি: 1</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">পেনাল্টি ও ট্রাস্ট স্ট্যাটাস *</label>
                            <select name="penalty_status" x-model="selectedStatus" class="w-full border rounded-xl px-3 py-2 bg-white font-bold text-xs">
                                <option value="verified">🟢 Verified Partner (স্বাভাবিক ভেরিফাইড)</option>
                                <option value="unverified">🟡 Unverified (অননুমোদিত)</option>
                                <option value="bypassed_warning">⚠️ Bypassed Warning (বাইপাস সতর্কতা)</option>
                                <option value="penalty_spam">🔴 Penalty Flagged (স্থায়ী পেনাল্টি ও স্প্যাম)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">অ্যাডমিন ফিক্সড পেনাল্টি রেটিং</label>
                            <input type="number" step="0.1" min="1.0" max="5.0" name="admin_fixed_rating" x-model="selectedRating" placeholder="যেমন: 1.0" class="w-full border rounded-xl px-3 py-2 bg-white font-bold text-rose-700 text-xs">
                            <span class="text-[10px] text-slate-500">খালি রাখলে কাস্টমার রিভিউয়ের ওপর ভিত্তি করে হিসাব হবে।</span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1">পেনাল্টি কারণ / ওনারশিপ অডিট নোট</label>
                        <textarea name="penalty_reason" x-model="selectedReason" placeholder="যেমন: ক্রেতার সাথে গোপনে যোগাযোগের মাধ্যমে সাকসেস ফি বাইপাস করার চেষ্টা করায় ১.০ স্টার স্থায়ী পেনাল্টি স্কোয়ার আরোপ করা হলো..." class="w-full border rounded-xl p-3 h-20 bg-white text-xs"></textarea>
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow transition cursor-pointer">
                        এজেন্ট পেনাল্টি ও রেটিং আপডেট করুন &rarr;
                    </button>
                </form>

                <!-- Currently Flagged / Penalized Agents Table -->
                @if(isset($penalized_agents) && $penalized_agents->isNotEmpty())
                    <div class="mt-6 pt-5 border-t border-rose-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-extrabold text-rose-950 m-0 flex items-center gap-2">
                                <span>📋</span> বর্তমানে পেনাল্টি বা বিশেষ রেটিং প্রাপ্ত এজেন্টদের তালিকা
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-200/80 text-rose-900 font-mono">
                                {{ $penalized_agents->count() }} জন
                            </span>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-rose-200 bg-white shadow-2xs">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-rose-100/60 text-rose-950 font-black border-b border-rose-200">
                                    <tr>
                                        <th class="p-3">ইউজার আইডি</th>
                                        <th class="p-3">এজেন্ট / নাম</th>
                                        <th class="p-3">পেনাল্টি স্ট্যাটাস</th>
                                        <th class="p-3">ফিক্সড রেটিং</th>
                                        <th class="p-3">পেনাল্টি কারণ</th>
                                        <th class="p-3 text-right">অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-rose-100 font-medium">
                                    @foreach($penalized_agents as $pa)
                                        <tr class="hover:bg-rose-50/50">
                                            <td class="p-3 font-mono font-bold text-slate-900">#{{ $pa->user_id }}</td>
                                            <td class="p-3">
                                                <div class="font-bold text-slate-900">{{ $pa->user?->name ?: 'ইউজার #' . $pa->user_id }}</div>
                                                <div class="text-[10px] text-slate-500">{{ $pa->agency_name ?: ($pa->user?->phone ?: 'Agent') }}</div>
                                            </td>
                                            <td class="p-3">
                                                @if($pa->penalty_status === 'penalty_spam')
                                                    <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-rose-100 text-rose-800 border border-rose-300">
                                                        🔴 Penalty Flagged
                                                    </span>
                                                @elseif($pa->penalty_status === 'bypassed_warning')
                                                    <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-amber-100 text-amber-800 border border-amber-300">
                                                        ⚠️ Bypassed Warning
                                                    </span>
                                                @elseif($pa->penalty_status === 'unverified')
                                                    <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-slate-100 text-slate-700">
                                                        🟡 Unverified
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-emerald-100 text-emerald-800">
                                                        🟢 Verified Partner
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="p-3 font-bold text-amber-600">
                                                {{ $pa->admin_fixed_rating ? $pa->admin_fixed_rating . ' ★' : 'ডিফল্ট (' . ($pa->rating ?: '5.0') . ' ★)' }}
                                            </td>
                                            <td class="p-3 text-slate-600 max-w-xs truncate" title="{{ $pa->penalty_reason }}">
                                                {{ $pa->penalty_reason ?: '—' }}
                                            </td>
                                            <td class="p-3 text-right">
                                                <button type="button"
                                                        @click="loadAgent({{ $pa->user_id }}, '{{ $pa->penalty_status }}', '{{ $pa->admin_fixed_rating }}', '{{ addslashes($pa->penalty_reason ?? '') }}')"
                                                        class="px-2.5 py-1 bg-rose-100 hover:bg-rose-200 text-rose-800 font-bold rounded-lg transition text-[11px] cursor-pointer">
                                                    এডিট ✏️
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div><!-- /activeTab === 'penalty' -->

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- TAB 10: LIVE WP IMPORT (APPLICATION PASSWORD & REST SYNC) -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <div x-show="activeTab === 'wpimport'" x-cloak class="space-y-6" x-data="admWpLiveImport()">

            <!-- Header Card -->
            <div class="p-6 bg-gradient-to-br from-indigo-900 via-slate-900 to-slate-950 text-white rounded-3xl border border-indigo-700/40 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-2xl shrink-0">
                            📥
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-white m-0 tracking-wide">লাইভ ওয়ার্ডপ্রেস ডাটা ইমপোর্ট ও সিঙ্ক (Live WP Import)</h2>
                            <p class="text-xs text-indigo-200/90 m-0 mt-0.5">লাইভ সাইট (https://propertykoi.com) থেকে সরাসরি Application Password দিয়ে প্রপার্টি, ইউজার, ভিউজ ও CRM লিড ইমপোর্ট করুন।</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="px-3 py-1 bg-indigo-500/30 border border-indigo-400/40 text-indigo-200 rounded-xl text-xs font-mono font-bold">
                            WP REST v2 + Basic Auth
                        </span>
                    </div>
                </div>
            </div>

            <!-- 1. LIVE SITE CREDENTIALS CARD -->
            <div class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🔑</span>
                        <h3 class="text-sm font-extrabold text-slate-800 m-0 uppercase tracking-wide">১. লাইভ সাইট সংযোগ তথ্য (Credentials)</h3>
                    </div>
                    <span class="text-[11px] text-slate-500">ওয়ার্ডপ্রেস অ্যাডমিন ক্রেডেনশিয়াল</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase mb-1">
                            লাইভ ওয়ার্ডপ্রেস URL <span class="text-rose-500">*</span>
                        </label>
                        <input type="url"
                               x-model="wpUrl"
                               name="adm_wp_live_url"
                               value="{{ $wp_live_url }}"
                               placeholder="https://propertykoi.com"
                               class="w-full text-xs px-3.5 py-2.5 bg-slate-50 rounded-xl border border-slate-300 font-mono font-bold focus:bg-white focus:outline-hidden focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase mb-1">
                            অ্যাডমিন ইউজারনেম / ইমেইল <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               x-model="wpUser"
                               name="adm_wp_live_username"
                               value="{{ $wp_live_username }}"
                               placeholder="admin"
                               class="w-full text-xs px-3.5 py-2.5 bg-slate-50 rounded-xl border border-slate-300 font-bold focus:bg-white focus:outline-hidden focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase mb-1">
                            Application Password <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   x-model="wpPassword"
                                   name="adm_wp_live_app_password"
                                   value="{{ $wp_live_app_password }}"
                                   placeholder="xxxx xxxx xxxx xxxx"
                                   class="w-full text-xs px-3.5 py-2.5 bg-slate-50 rounded-xl border border-slate-300 font-mono font-bold focus:bg-white focus:outline-hidden focus:border-indigo-500 pr-9">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 text-xs cursor-pointer"
                                    title="পাসওয়ার্ড দেখুন">
                                <span x-text="showPassword ? '🙈' : '👁️'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <button type="button"
                            @click="testConnection()"
                            :disabled="isTesting"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer">
                        <span x-show="!isTesting">🔌 টেস্ট কানেকশন (Test Connection)</span>
                        <span x-show="isTesting" class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            যাচাই করা হচ্ছে...
                        </span>
                    </button>

                    <div class="text-[11px] text-slate-500 flex items-center gap-2">
                        <span>💡 নিচে দেয়া <strong>"সেটিংস সেভ করুন"</strong> বাটনে ক্লিক করলে এই ক্রেডেনশিয়ালগুলো সেভ থাকবে।</span>
                    </div>
                </div>

                <!-- Test Connection Success Box -->
                <template x-if="testResult">
                    <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-2xl text-xs space-y-2">
                        <div class="flex items-center gap-2 font-black text-emerald-900">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>সংযোগ সফল হয়েছে! লাইভ সাইটের সাথে সফলভাবে যুক্ত।</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 font-mono text-[11px]">
                            <div class="bg-white p-2.5 rounded-xl border border-emerald-200">
                                <span class="text-slate-500 block text-[10px]">অ্যাডমিন ইউজার:</span>
                                <strong class="text-slate-900" x-text="testResult.authenticated_user || testResult.user_display_name"></strong>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-emerald-200">
                                <span class="text-slate-500 block text-[10px]">লাইভ প্রপার্টি:</span>
                                <strong class="text-emerald-700" x-text="testResult.counts?.properties || 'N/A'"></strong>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-emerald-200">
                                <span class="text-slate-500 block text-[10px]">লাইভ CRM লিডস:</span>
                                <strong class="text-indigo-700" x-text="testResult.counts?.leads || 'N/A'"></strong>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-emerald-200">
                                <span class="text-slate-500 block text-[10px]">লাইভ ইউজার:</span>
                                <strong class="text-slate-900" x-text="testResult.counts?.users || 'N/A'"></strong>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Test Connection Error Box -->
                <template x-if="testError">
                    <div class="p-4 bg-rose-50 border border-rose-300 text-rose-800 rounded-2xl text-xs font-bold flex items-start gap-2">
                        <span class="text-base shrink-0">⚠️</span>
                        <div>
                            <div class="font-extrabold text-rose-950">কানেকশন ব্যর্থ হয়েছে:</div>
                            <div class="font-normal text-[11px] mt-0.5" x-text="testError"></div>
                            <div class="text-[10px] text-rose-700 mt-1 font-normal">অনুগ্রহ করে সাইট URL এবং ১৬ অক্ষরের Application Password সঠিক কিনা পরীক্ষা করুন।</div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- 2. IMPORT OPTIONS & SCOPE -->
            <div class="p-6 bg-white rounded-3xl border border-slate-200 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⚙️</span>
                        <h3 class="text-sm font-extrabold text-slate-800 m-0 uppercase tracking-wide">২. ইমপোর্ট অপশন ও ডুপ্লিকেশন রুলস (Import Rules)</h3>
                    </div>
                    <span class="text-[11px] text-slate-500">আইটেম লিমিট ও কনফ্লিক্ট সেটিংস</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Option A: Items Limit -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                        <label class="block text-xs font-extrabold text-slate-800 uppercase">
                            📦 কতগুলো আইটেম আনবেন? (Item Limit):
                        </label>
                        <select x-model="limit" class="w-full text-xs px-3.5 py-2.5 bg-white rounded-xl border border-slate-300 font-bold focus:outline-hidden focus:border-indigo-500">
                            <option value="10">১০ টি প্রপার্টি (দ্রুত টেস্ট ইমপোর্ট)</option>
                            <option value="25">২৫ টি প্রপার্টি</option>
                            <option value="50">৫০ টি প্রপার্টি</option>
                            <option value="100">১০০ টি প্রপার্টি</option>
                            <option value="500">সব প্রপার্টি (All Items - সম্পূর্ণ ডাটা)</option>
                        </select>
                        <p class="text-[10px] text-slate-500 m-0">প্রথমে ১০টি দিয়ে টেস্ট রান করতে পারেন, সন্তুষ্ট হলে "সব প্রপার্টি" সিলেক্ট করুন।</p>
                    </div>

                    <!-- Option B: Duplication Strategy -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                        <label class="block text-xs font-extrabold text-slate-800 uppercase">
                            🛡️ ডুপ্লিকেট আইটেম হ্যান্ডলিং (Duplication Strategy):
                        </label>
                        <div class="space-y-2 text-xs">
                            <label class="flex items-center gap-2.5 p-2 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-indigo-300 transition">
                                <input type="radio" x-model="duplicateAction" value="skip" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <strong class="text-slate-800">Skip Existing (এড়িয়ে যান)</strong>
                                    <span class="block text-[10px] text-slate-500">প্রপার্টি কোড আগে থেকে থাকলে তা অপরিবর্তিত রাখবে (ডুপ্লিকেট হবে না)।</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 p-2 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-indigo-300 transition">
                                <input type="radio" x-model="duplicateAction" value="overwrite" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <strong class="text-slate-800">Overwrite / Update (ওভাররাইট / আপডেট করুন)</strong>
                                    <span class="block text-[10px] text-slate-500">পূর্বে থাকা প্রপার্টির ভিউজ কাউন্ট, দাম ও লেটেস্ট তথ্য দিয়ে আপডেট করবে।</span>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>

                <!-- Data Scope Selection (Checkboxes) -->
                <div class="space-y-2">
                    <label class="block text-xs font-extrabold text-slate-800 uppercase">
                        📋 কোন কোন ডাটা ইমপোর্ট করতে চান? (Select Data Scope):
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <label class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-indigo-50/50 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" x-model="importProperties" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="text-xs font-bold text-slate-800">🏢 প্রপার্টিজ ও বিবরণ</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-indigo-50/50 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" x-model="importLeads" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="text-xs font-bold text-slate-800">🎯 CRM লিডস ও বুকিং</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-indigo-50/50 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" x-model="importDemands" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="text-xs font-bold text-slate-800">📝 ক্রেতার চাহিদা (Demands)</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-indigo-50/50 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" x-model="importUsers" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="text-xs font-bold text-slate-800">👥 রেজিস্টার্ড ইউজার ডাটা</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-indigo-50/50 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" x-model="importReviews" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="text-xs font-bold text-slate-800">⭐ কাস্টমার রিভিউ</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 bg-slate-50 hover:bg-indigo-50/50 rounded-xl border border-slate-200 cursor-pointer transition sm:col-span-2">
                            <input type="checkbox" x-model="syncCloudinary" class="w-4 h-4 text-indigo-600 rounded">
                            <div class="text-xs">
                                <span class="font-bold text-slate-800">☁️ ক্লাউডিনারি অটো-সিঙ্ক (WebP)</span>
                                <span class="block text-[10px] text-slate-500">লোকাল কোনো ইমেজ পেলে WebP-তে কনভার্ট করে Cloudinary-তে আপলোড করবে।</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Big Import Trigger Button -->
                <div class="pt-2">
                    <button type="button"
                            @click="startImport()"
                            :disabled="isImporting"
                            class="w-full py-4 bg-gradient-to-r from-indigo-600 via-indigo-700 to-teal-700 hover:from-indigo-500 hover:to-teal-600 disabled:opacity-50 text-white font-black text-sm rounded-2xl shadow-xl transition flex items-center justify-center gap-3 cursor-pointer">
                        <span x-show="!isImporting">🚀 লাইভ সাইট থেকে ডাটা ইমপোর্ট শুরু করুন (Start Live Import)</span>
                        <span x-show="isImporting" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            লাইভ সাইট থেকে ডাটা ইমপোর্ট হচ্ছে... অনুগ্রহ করে অপেক্ষা করুন...
                        </span>
                    </button>
                </div>

            </div>

            <!-- 3. LIVE IMPORT RESULTS & LOGS -->
            <template x-if="importResult">
                <div class="p-6 bg-white rounded-3xl border border-emerald-300 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📊</span>
                            <h3 class="text-sm font-extrabold text-emerald-950 m-0">ইমপোর্ট ফলাফল সামারি (Import Summary)</h3>
                        </div>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold">সম্পন্ন ✅</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="bg-emerald-50 p-3 rounded-2xl border border-emerald-200">
                            <span class="text-[11px] text-emerald-700 font-bold block">নতুন প্রপার্টি</span>
                            <div class="text-2xl font-black text-emerald-950 mt-1" x-text="importResult.stats?.properties_imported || 0"></div>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-2xl border border-blue-200">
                            <span class="text-[11px] text-blue-700 font-bold block">আপডেট / ওভাররাইট</span>
                            <div class="text-2xl font-black text-blue-950 mt-1" x-text="importResult.stats?.properties_updated || 0"></div>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200">
                            <span class="text-[11px] text-slate-600 font-bold block">স্কিপ করা হয়েছে</span>
                            <div class="text-2xl font-black text-slate-800 mt-1" x-text="importResult.stats?.properties_skipped || 0"></div>
                        </div>
                        <div class="bg-indigo-50 p-3 rounded-2xl border border-indigo-200">
                            <span class="text-[11px] text-indigo-700 font-bold block">CRM লিডস</span>
                            <div class="text-2xl font-black text-indigo-950 mt-1" x-text="importResult.stats?.leads_imported || 0"></div>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-2xl border border-purple-200">
                            <span class="text-[11px] text-purple-700 font-bold block">রেজিস্টার্ড ইউজার</span>
                            <div class="text-2xl font-black text-purple-950 mt-1" x-text="importResult.stats?.users_imported || 0"></div>
                        </div>
                        <div class="bg-amber-50 p-3 rounded-2xl border border-amber-200">
                            <span class="text-[11px] text-amber-700 font-bold block">ক্রেতার চাহিদা (Demands)</span>
                            <div class="text-2xl font-black text-amber-950 mt-1" x-text="importResult.stats?.demands_imported || 0"></div>
                        </div>
                        <div class="bg-teal-50 p-3 rounded-2xl border border-teal-200">
                            <span class="text-[11px] text-teal-700 font-bold block">ক্লাউডিনারি আপলোড</span>
                            <div class="text-2xl font-black text-teal-950 mt-1" x-text="importResult.stats?.media_uploaded || 0"></div>
                        </div>
                        <div class="bg-rose-50 p-3 rounded-2xl border border-rose-200">
                            <span class="text-[11px] text-rose-700 font-bold block">কাস্টমার রিভিউ</span>
                            <div class="text-2xl font-black text-rose-950 mt-1" x-text="importResult.stats?.reviews_imported || 0"></div>
                        </div>
                    </div>

                    <!-- Visual Log Console -->
                    <template x-if="importResult.stats?.logs?.length">
                        <div class="mt-3">
                            <div class="text-xs font-bold text-slate-700 mb-1.5">📜 বিস্তারিত ইমপোর্ট লগ:</div>
                            <div class="bg-slate-900 text-slate-200 font-mono text-[11px] p-3.5 rounded-2xl max-h-52 overflow-y-auto space-y-1">
                                <template x-for="(logLine, idx) in importResult.stats.logs" :key="idx">
                                    <div x-text="logLine" class="border-b border-slate-800/80 pb-0.5"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Import Error Box -->
            <template x-if="importError">
                <div class="p-4 bg-rose-50 border border-rose-300 text-rose-900 rounded-2xl text-xs font-bold space-y-1">
                    <div class="text-sm font-extrabold flex items-center gap-2">
                        <span>❌</span>
                        <span>ইমপোর্ট ব্যর্থ হয়েছে:</span>
                    </div>
                    <p class="m-0 font-normal text-rose-800" x-text="importError"></p>
                </div>
            </template>

            <!-- 4. STEP-BY-STEP HELP & APPLICATION PASSWORD GUIDE -->
            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-200 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📖</span>
                    <h3 class="text-sm font-extrabold text-slate-800 m-0">কীভাবে ওয়ার্ডপ্রেসে Application Password তৈরি করবেন? (User Guide)</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-700">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 space-y-2 leading-relaxed">
                        <strong class="text-slate-900 block font-black">১. Application Password তৈরির সহজ ধাপ:</strong>
                        <ol class="list-decimal list-inside space-y-1.5 text-[11px] text-slate-600 m-0">
                            <li>আপনার লাইভ সাইটের ওয়ার্ডপ্রেস ড্যাশবোর্ডে লগইন করুন: <code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-700 font-bold">https://propertykoi.com/wp-admin/</code></li>
                            <li>বামের মেনু থেকে <strong>Users &gt; Profile</strong> (বা আপনার ব্যবহারকারী প্রোফাইল)-এ যান।</li>
                            <li>পেজের একদম নিচে স্ক্রল করে <strong>"Application Passwords"</strong> সেকশনে আসুন।</li>
                            <li>"New Application Password Name" ফিল্ডে লিখুন: <code class="bg-slate-100 px-1 py-0.5 rounded text-emerald-700 font-bold">Laravel Importer</code></li>
                            <li><strong>"Add New Application Password"</strong> বাটনে ক্লিক করুন।</li>
                            <li>স্ক্রিনে প্রদর্শিত ১৬ অক্ষরের পাসওয়ার্ডটি (যেমন: <code class="bg-slate-100 px-1 py-0.5 rounded font-mono font-bold">xxxx xxxx xxxx xxxx</code>) কপি করে উপরের ঘরে পেস্ট করুন।</li>
                        </ol>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-200 space-y-2 leading-relaxed">
                        <strong class="text-slate-900 block font-black">২. বিকল্প পদ্ধতি: Standalone Sync Bridge (১-ফাইল আপলোড)</strong>
                        <p class="text-[11px] text-slate-600 m-0">
                            যদি প্লাগইন আপডেট করতে না চান, তবে আমাদের প্রস্তুতকৃত হালকা ও সিকিউর <code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-700 font-bold">wp-sync-bridge.php</code> ফাইলটি ডাউনলোড করে আপনার লাইভ হোস্টিংয়ের ওয়ার্ডপ্রেস রুটে (<code class="bg-slate-100 px-1 py-0.5 rounded">public_html/</code>) আপলোড করে দিলেও সরাসরি ডাটা ইমপোর্ট করা যাবে।
                        </p>
                        <div class="pt-2">
                            <a href="{{ route('admin.settings.wp-import.download-bridge') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition no-underline">
                                <span>⬇️</span>
                                <span>wp-sync-bridge.php ডাউনলোড করুন</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /activeTab === 'wpimport' -->

    </div>

    <!-- Sticky Floating Action Bar for Quick 1-Click Saving Anywhere On Page -->
    <div x-show="activeTab !== 'penalty' && activeTab !== 'wpimport'"
         x-cloak
         class="sticky bottom-4 z-40 mt-6 p-3.5 sm:p-4 bg-slate-900/95 backdrop-blur-md text-white rounded-2xl shadow-2xl border border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-3 transition-all">
        <div class="flex items-center gap-3">
            <span class="flex h-3 w-3 relative shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <div class="text-xs">
                <span class="text-slate-400 font-normal">বর্তমান সক্রিয় ট্যাব: </span>
                <span class="text-emerald-400 font-black" x-text="{
                    'general': '⚙️ সাধারণ সেটিংস (General)',
                    'media': '🖼️ মিডিয়া ও ওয়াটারমার্ক (Media)',
                    'sheets': '📊 গুগল শিট সিঙ্ক (Google Sheets)',
                    'notifications': '🔔 নোটিফিকেশন ও ফায়ারবেস (Notifications & Firebase)',
                    'access': '🔀 রিডাইরেকশন ও পলিসি (Access & Policies)',
                    'messages': '💬 মেসেজ টেমপ্লেট (Message Templates)',
                    'seo': '🔍 SEO ও AEO (AI সার্চ)',
                    'facebook': '📱 Facebook অটো-পোস্ট (Facebook Graph API)',
                    'penalty': '🛡️ এজেন্ট পেনাল্টি (Agent Penalty)',
                    'wpimport': '📥 লাইভ WP ইমপোর্ট (Live WP Import)'
                }[activeTab] || activeTab"></span>
            </div>
        </div>
        <button type="submit" form="adm-settings-form"
                class="w-full sm:w-auto px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-xl transition flex items-center justify-center gap-2 cursor-pointer shrink-0">
            <span>💾</span>
            <span>সেটিংস সেভ করুন &rarr;</span>
        </button>
    </div>
</div>

<script>
function admWatermarkSettings() {
    return {
        enabled: {{ $watermark_enabled ? 'true' : 'false' }},
        text: @json($watermark_text),
        position: @json($watermark_position),
        opacity: {{ (int) $watermark_opacity }},
        testingWm: false,
        testWmMsg: '',
        testWmSuccess: false,
        testWmUrl: '',
        testWm: function() {
            var self = this;
            self.testingWm = true;
            self.testWmMsg = '';
            self.testWmUrl = '';
            var fd = new FormData();
            fd.append('_token', window.admSettingsConfig.csrfToken);

            fetch(window.admSettingsConfig.routes.testWatermark, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.testingWm = false;
                self.testWmSuccess = res.success;
                self.testWmMsg = (res.data && res.data.message) ? res.data.message : (res.success ? 'সফল!' : 'ব্যর্থ!');
                if (res.success && res.data && res.data.image_url) {
                    self.testWmUrl = res.data.image_url;
                }
            })
            .catch(function(err) {
                self.testingWm = false;
                self.testWmSuccess = false;
                self.testWmMsg = 'সার্ভারে রিকোয়েস্ট ব্যর্থ হয়েছে।';
            });
        }
    };
}

function admCloudinarySettings() {
    return {
        testing: false,
        testResult: '',
        testSuccess: false,
        testCld: function() {
            var self = this;
            self.testing = true;
            self.testResult = '';
            var fd = new FormData();
            fd.append('_token', window.admSettingsConfig.csrfToken);

            fetch(window.admSettingsConfig.routes.testCloudinary, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.testing = false;
                self.testSuccess = res.success;
                self.testResult = (res.data && res.data.message) ? res.data.message : (res.success ? 'সফল!' : 'ব্যর্থ!');
            })
            .catch(function() {
                self.testing = false;
                self.testSuccess = false;
                self.testResult = 'সার্ভারের সাথে যোগাযোগ করা যায়নি।';
            });
        }
    };
}

function admFcmSettings() {
    return {
        testingFcm: false,
        testFcmMsg: '',
        testFcmSuccess: false,
        init: function() {
            // If already granted, auto-sync token with backend
            if ('Notification' in window && Notification.permission === 'granted') {
                if (typeof window.admEnsureTokenSynced === 'function') {
                    window.admEnsureTokenSynced();
                }
            }
        },
        requestPermission: async function() {
            if (typeof window.admRequestPushPermission === 'function') {
                await window.admRequestPushPermission();
            } else if ('Notification' in window) {
                Notification.requestPermission();
            } else {
                alert('এই ব্রাউজার নোটিফিকেশন সাপোর্ট করে না।');
            }
        },
        sendTestPush: async function() {
            var self = this;
            self.testingFcm = true;
            self.testFcmMsg = '';

            // Ensure token is synced before sending test push
            if (typeof window.admEnsureTokenSynced === 'function') {
                await window.admEnsureTokenSynced();
            }

            var fd = new FormData();
            fd.append('_token', window.admSettingsConfig.csrfToken);

            fetch(window.admSettingsConfig.routes.testPush, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.testingFcm = false;
                self.testFcmSuccess = res.success;
                self.testFcmMsg = (res.data && res.data.message) ? res.data.message : (res.success ? 'টেস্ট নোটিফিকেশন পাঠানো হয়েছে!' : 'ব্যর্থ হয়েছে!');
                if (res.success) {
                    const badge = document.querySelector('.adm-fcm-device-badge');
                    if (badge && (badge.textContent.includes('0টি') || badge.textContent.includes('০টি'))) {
                        badge.textContent = '📱 ১টি ডিভাইস সংযুক্ত';
                    }
                    if (typeof window.admShowInAppToast === 'function') {
                        window.admShowInAppToast('🔔 টেস্ট পুশ নোটিফিকেশন!', self.testFcmMsg, window.location.href);
                    }
                    if (Notification.permission === 'granted') {
                        try {
                            const iconUrl = (window.admVars && window.admVars.baseUrl)
                                ? window.admVars.baseUrl.replace(/\/+$/, '') + '/assets/icons/icon-192x192.png'
                                : '/assets/icons/icon-192x192.png';
                            new Notification('🔔 টেস্ট পুশ নোটিফিকেশন!', {
                                body: self.testFcmMsg,
                                icon: iconUrl
                            });
                        } catch (e) {}
                    }
                }
            })
            .catch(function() {
                self.testingFcm = false;
                self.testFcmSuccess = false;
                self.testFcmMsg = 'সার্ভার যোগাযোগে সমস্যা হয়েছে।';
            });
        }
    };
}

function admTelegramSettings() {
    return {
        botToken: @json($tele_bot_token),
        botUsername: @json($tele_bot_username),
        adminChatId: @json($tele_admin_chat_id),
        testing: false,
        testResult: null,
        testError: null,
        testBot: function() {
            var self = this;
            self.testResult = null;
            self.testError = null;

            if (!self.botToken) {
                self.testError = '❌ অনুগ্রহ করে প্রথমে Telegram Bot Token বক্সে আপনার বটের টোকেনটি দিন।';
                return;
            }

            self.testing = true;
            var data = new FormData();
            data.append('_token', window.admSettingsConfig.csrfToken);
            data.append('bot_token', self.botToken);
            data.append('bot_username', self.botUsername);
            data.append('admin_chat_id', self.adminChatId);

            fetch(window.admSettingsConfig.routes.testTelegram, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.testing = false;
                if (res.success) {
                    self.testResult = res.message || '✅ টেলিগ্রাম বট সফলভাবে কানেক্ট হয়েছে এবং সংরক্ষিত হয়েছে!';
                    if (res.bot && res.bot.username && !self.botUsername) {
                        self.botUsername = res.bot.username;
                    }
                } else {
                    self.testError = '❌ ' + (res.error || res.message || 'কানেকশন ব্যর্থ হয়েছে। টোকেন চেক করুন।');
                }
            })
            .catch(function() {
                self.testing = false;
                self.testError = '❌ সার্ভারে যোগাযোগ করা যায়নি।';
            });
        }
    };
}

function admEmailSmtpSettings() {
    return {
        mailer: @json($mail_mailer),
        host: @json($mail_host),
        port: @json($mail_port),
        username: @json($mail_username),
        password: @json($mail_password),
        encryption: @json($mail_encryption),
        fromAddress: @json($mail_from_address),
        fromName: @json($mail_from_name),
        testEmailAddr: @json(auth()->user()?->email ?: 'admin@propertykoi.com'),
        testing: false,
        testResult: null,
        testError: null,
        testEmail: function() {
            var self = this;
            self.testResult = null;
            self.testError = null;

            if (!self.testEmailAddr) {
                self.testError = '❌ অনুগ্রহ করে টেস্ট করার জন্য একটি ইমেইল ঠিকানা দিন।';
                return;
            }

            self.testing = true;
            var data = new FormData();
            data.append('_token', window.admSettingsConfig.csrfToken);
            data.append('test_email', self.testEmailAddr);
            data.append('smtp_host', self.host);
            data.append('smtp_port', self.port);
            data.append('smtp_username', self.username);
            data.append('smtp_password', self.password);
            data.append('smtp_encryption', self.encryption);
            data.append('smtp_from_address', self.fromAddress);
            data.append('smtp_from_name', self.fromName);

            fetch(window.admSettingsConfig.routes.testEmail, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.testing = false;
                if (res.success) {
                    self.testResult = res.message;
                } else {
                    self.testError = res.message || 'ইমেইল পাঠানো যায়নি।';
                }
            })
            .catch(function(err) {
                self.testing = false;
                self.testError = 'সার্ভার যোগাযোগে সমস্যা হয়েছে: ' + err.message;
            });
        }
    };
}

function admDemandCronSettings() {
    return {
        running: false,
        runResult: null,
        runError: null,
        runCronNow: function() {
            var self = this;
            self.running = true;
            self.runResult = null;
            self.runError = null;
            var data = new FormData();
            data.append('_token', window.admSettingsConfig.csrfToken);

            fetch(window.admSettingsConfig.routes.triggerCron, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.running = false;
                if (res.success) {
                    self.runResult = '✅ ' + (res.data ? res.data.message : 'ম্যাচিং সম্পন্ন হয়েছে!');
                } else {
                    self.runError = '❌ ' + (res.data ? res.data.message : 'ম্যাচিং ব্যর্থ হয়েছে।');
                }
            })
            .catch(function() {
                self.running = false;
                self.runError = '❌ সার্ভার রিকোয়েস্ট ব্যর্থ হয়েছে।';
            });
        }
    };
}

function admGoogleSheetSync() {
    return {
        sheetUrl: @json($google_sheet_url),
        directLive: {{ $google_sheet_direct_live ? 'true' : 'false' }},
        defaultStatus: '{{ $google_sheet_default_status }}',
        previewing: false,
        previewOpen: false,
        previewData: null,
        previewError: '',
        syncing: false,
        syncResult: null,
        syncMsg: '',
        guideOpen: false,
        previewSheet: function() {
            var self = this;
            if (!self.sheetUrl) {
                alert('অনুগ্রহ করে আগে Google Sheet এর লিংক প্রদান করুন।');
                return;
            }
            self.previewing = true;
            self.previewError = '';
            var fd = new FormData();
            fd.append('_token', window.admSettingsConfig.csrfToken);
            fd.append('sheet_url', self.sheetUrl);

            fetch(window.admSettingsConfig.routes.previewSheet, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.previewing = false;
                if (res.success) {
                    self.previewData = res.data;
                    self.previewOpen = true;
                } else {
                    self.previewError = (res.data && res.data.error) ? res.data.error : (res.data && res.data.message ? res.data.message : 'প্রিভিউ লোড করা যায়নি।');
                    alert(self.previewError);
                }
            })
            .catch(function() {
                self.previewing = false;
                self.previewError = 'সার্ভারে যোগাযোগ করা যায়নি।';
                alert(self.previewError);
            });
        },
        syncNow: function() {
            var self = this;
            if (!self.sheetUrl) {
                alert('অনুগ্রহ করে আগে Google Sheet এর লিংক প্রদান করুন।');
                return;
            }
            if (!confirm('আপনি কি এই গুগল শিট থেকে প্রপার্টি সিঙ্ক শুরু করতে চান?\n\n' +
                '✓ ক্যাটালগে নতুন প্রপার্টি থাকলে তা যুক্ত হবে।\n' +
                '✓ একই এলাকা, ব্লক/সেক্টর ও প্লটের বিদ্যমান প্রপার্টিতে ডিরেক্ট সোর্স হিসেবে মার্জ হবে (কোনো ডুপ্লিকেট হবে না)।')) {
                return;
            }
            self.syncing = true;
            self.syncMsg = '';
            self.syncResult = null;
            var fd = new FormData();
            fd.append('_token', window.admSettingsConfig.csrfToken);
            fd.append('sheet_url', self.sheetUrl);
            fd.append('default_status', self.defaultStatus);

            fetch(window.admSettingsConfig.routes.syncSheet, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.syncing = false;
                if (res.success) {
                    self.syncResult = res.data.report;
                    self.syncMsg = res.data.message;
                } else {
                    self.syncMsg = (res.data && res.data.error) ? res.data.error : (res.data && res.data.message ? res.data.message : 'সিঙ্ক ব্যর্থ হয়েছে।');
                    alert(self.syncMsg);
                }
            })
            .catch(function() {
                self.syncing = false;
                self.syncMsg = 'সার্ভারে যোগাযোগ করা যায়নি।';
                alert(self.syncMsg);
            });
        }
    };
}

function admLinkParentlessProps() {
    return {
        whatsappNumber: '',
        loading: false,
        alertMsg: '',
        alertSuccess: false,
        result: null,
        linkProperties: function() {
            var self = this;
            if (!self.whatsappNumber || self.whatsappNumber.trim() === '') {
                alert('অনুগ্রহ করে WhatsApp নম্বর প্রদান করুন।');
                return;
            }
            self.loading = true;
            self.alertMsg = '';
            self.result = null;

            var fd = new FormData();
            fd.append('_token', window.admSettingsConfig.csrfToken);
            fd.append('whatsapp_number', self.whatsappNumber.trim());

            fetch(window.admSettingsConfig.routes.linkWhatsapp, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.loading = false;
                if (res.success) {
                    self.alertSuccess = true;
                    self.alertMsg = res.data.message;
                    self.result = res.data;
                } else {
                    self.alertSuccess = false;
                    self.alertMsg = (res.data && res.data.message) ? res.data.message : 'লিঙ্ক করা সম্ভব হয়নি।';
                }
            })
            .catch(function() {
                self.loading = false;
                self.alertSuccess = false;
                self.alertMsg = 'সার্ভারে যোগাযোগ করা যায়নি।';
            });
        }
    };
}

function admAccessControlSettings() {
    return {
        rolesSummary: @json(array_values($roles_summary)),
        accessBar: @json($access_admin_bar),
        accessPanel: @json($access_admin_panel),
        accessRedirects: @json($access_redirects),
        rules: @json($saved_rules),
        pages: @json($pages_list),
        rolesList: @json(collect($available_roles)->map(fn($v, $k) => ['key' => $k, 'label' => $v])->values()->all()),
        applyAccessPreset: function(preset) {
            var self = this;
            if (preset === 'recommended') {
                self.rolesSummary.forEach(function(rObj) {
                    var r = rObj.key;
                    if (r === 'administrator' || r === 'editor') {
                        self.accessBar[r] = 1;
                        self.accessPanel[r] = 1;
                        self.accessRedirects[r] = 'auto';
                    } else {
                        self.accessBar[r] = 0;
                        self.accessPanel[r] = 0;
                        self.accessRedirects[r] = 'auto';
                    }
                });
            } else if (preset === 'strict') {
                self.rolesSummary.forEach(function(rObj) {
                    var r = rObj.key;
                    if (r === 'administrator') {
                        self.accessBar[r] = 1;
                        self.accessPanel[r] = 1;
                        self.accessRedirects[r] = 'auto';
                    } else {
                        self.accessBar[r] = 0;
                        self.accessPanel[r] = 0;
                        self.accessRedirects[r] = 'home';
                    }
                });
            }
        },
        addRule: function() {
            this.rules.push({
                role: 'adm_buyer',
                type: 'page',
                page_id: (this.pages.length > 0 ? this.pages[0].id : ''),
                custom_url: ''
            });
        },
        removeRule: function(idx) {
            if (this.rules.length <= 1) {
                alert('কমপক্ষে একটি রিডাইরেকশন রুল থাকতে হবে।');
                return;
            }
            this.rules.splice(idx, 1);
        }
    };
}

function admMessagesSettings() {
    var rawTemplates = @json($saved_templates);
    var definitions = @json($template_definitions);

    return {
        templates: rawTemplates,
        definitions: definitions,
        sampleData: {
            '%name%': 'মোঃ রফিকুল ইসলাম',
            '%login_magic_link%': '{{ url('/agent-portal?adm_token=demo3YVT9AJMQ7VTOjv7') }}',
            '%property_code%': 'AF-LAND-84920',
            '%property_title%': 'আফতাবনগর এল ব্লক ৫ কাঠা দক্ষিণমুখী প্লট',
            '%property_url%': '{{ url('/property/af-land-84920') }}',
            '%price%': ' | মূল্য: ১ কোটি ২৫ লাখ',
            '%location%': ' | আফতাবনগর, ব্লক এল',
            '%source_tag%': 'Google Sheets সিঙ্ক রেকর্ডের মাধ্যমে',
            '%enquiry_type%': 'সরাসরি সাইট ভিজিট বুকিং',
            '%lead_details%': 'ভিজিটের তারিখ: ০২ অক্টোবর ২০২৬\nবায়ার নোট: "আমি সরাসরি পরিবার নিয়ে ফ্ল্যাটটি দেখতে চাই।"',
            '%lead_message%': 'আমি সরাসরি পরিবার নিয়ে ফ্ল্যাটটি দেখতে চাই।',
            '%penalty_reason%': 'ক্লায়েন্ট বাইপাস চেষ্টার অভিযোগে রেটিং সাময়িক স্থগিত।',
            '%site_name%': '{{ config('app.name', 'PropertyKoi.com') }}',
            '%phone%': '01712345678',
            '%role%': 'Direct Agent',
            '%portal_url%': '{{ url('/agent-portal') }}'
        },
        insertTag: function(tplKey, tag) {
            var el = document.getElementById('adm_tpl_' + tplKey);
            if (!el) {
                this.templates[tplKey] = (this.templates[tplKey] || '') + tag;
                return;
            }
            var start = el.selectionStart || 0;
            var end = el.selectionEnd || 0;
            var text = this.templates[tplKey] || '';
            this.templates[tplKey] = text.substring(0, start) + tag + text.substring(end);
            this.$nextTick(function() {
                el.focus();
                el.selectionStart = el.selectionEnd = start + tag.length;
            });
        },
        resetToDefault: function(tplKey) {
            if (this.definitions[tplKey] && confirm('আপনি কি এই টেমপ্লেটটি সিস্টেমে ডিফল্ট টেক্সটে রিসেট করতে চান?')) {
                this.templates[tplKey] = this.definitions[tplKey].default;
            }
        },
        renderPreview: function(tplKey) {
            var body = this.templates[tplKey] || '';
            for (var tag in this.sampleData) {
                body = body.split(tag).join(this.sampleData[tag]);
            }
            return body;
        }
    };
}

function admSeoSettings() {
    return {
        isPinging: false,
        pingResultMsg: '',
        pingSuccess: false,
        pingIndexNow: function() {
            var self = this;
            self.isPinging = true;
            self.pingResultMsg = '';
            var fd = new FormData();
            fd.append('_token', window.admSettingsConfig.csrfToken);

            fetch(window.admSettingsConfig.routes.pingIndexNow, {
                method: 'POST',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.isPinging = false;
                self.pingSuccess = res.success;
                self.pingResultMsg = res.success ? ('✅ ' + (res.data ? res.data.message : 'সফলভাবে পিং করা হয়েছে!')) : ('❌ ' + (res.data ? res.data.message : 'পিং ব্যর্থ হয়েছে'));
            })
            .catch(function(err) {
                self.isPinging = false;
                self.pingSuccess = false;
                self.pingResultMsg = '❌ নেটওয়ার্ক ত্রুটি। পুনরায় চেষ্টা করুন।';
            });
        }
    };
}

function admFacebookSettings() {
    return {
        testingFb: false,
        fbTestStatus: null,
        fbTestMsg: '',
        testFbPost: function() {
            var self = this;
            self.testingFb = true;
            self.fbTestStatus = null;
            self.fbTestMsg = '';
            var formData = new FormData();
            formData.append('_token', window.admSettingsConfig.csrfToken);

            fetch(window.admSettingsConfig.routes.testFacebook, {
                method: 'POST',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                self.testingFb = false;
                if (data.success) {
                    self.fbTestStatus = 'success';
                    self.fbTestMsg = (data.data && data.data.message) ? data.data.message : 'টেস্ট পোস্ট সফল হয়েছে!';
                } else {
                    self.fbTestStatus = 'error';
                    self.fbTestMsg = (data.data && data.data.message) ? data.data.message : 'টেস্ট পোস্ট ব্যর্থ হয়েছে।';
                }
            })
            .catch(function(err) {
                self.testingFb = false;
                self.fbTestStatus = 'error';
                self.fbTestMsg = 'সার্ভার রেসপন্স দেয়নি।';
            });
        }
    };
}

function admPenaltyControl() {
    return {
        selectedUserId: 1,
        selectedStatus: 'verified',
        selectedRating: '',
        selectedReason: '',
        loadAgent: function(userId, status, rating, reason) {
            this.selectedUserId = userId;
            this.selectedStatus = status || 'verified';
            this.selectedRating = rating || '';
            this.selectedReason = reason || '';
            var el = document.querySelector('[x-show="activeTab === \'penalty\'"]');
            if (el) {
                window.scrollTo({ top: el.offsetTop - 40, behavior: 'smooth' });
            }
        }
    };
}

function admWpLiveImport() {
    return {
        wpUrl: @json($wp_live_url),
        wpUser: @json($wp_live_username),
        wpPassword: @json($wp_live_app_password),
        showPassword: false,
        limit: '100',
        duplicateAction: 'skip',
        importProperties: true,
        importLeads: true,
        importDemands: true,
        importUsers: true,
        importReviews: true,
        syncCloudinary: true,

        isTesting: false,
        testResult: null,
        testError: '',

        isImporting: false,
        importResult: null,
        importError: '',

        testConnection: function() {
            var self = this;
            if (!self.wpUrl || !self.wpUser || !self.wpPassword) {
                alert('দয়া করে লাইভ সাইট URL, ইউজারনেম এবং Application Password প্রদান করুন।');
                return;
            }
            self.isTesting = true;
            self.testResult = null;
            self.testError = '';

            fetch(window.admSettingsConfig.routes.testWpImport, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.admSettingsConfig.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    url: self.wpUrl,
                    username: self.wpUser,
                    password: self.wpPassword
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.isTesting = false;
                if (res.success) {
                    self.testResult = res;
                } else {
                    self.testError = res.message || 'কানেকশন স্থাপন করা সম্ভব হয়নি।';
                }
            })
            .catch(function(err) {
                self.isTesting = false;
                self.testError = 'নেটওয়ার্ক এরর: ' + err.message;
            });
        },

        startImport: function() {
            var self = this;
            if (!self.wpUrl || !self.wpUser || !self.wpPassword) {
                alert('দয়া করে লাইভ সাইট URL, ইউজারনেম এবং Application Password প্রদান করুন।');
                return;
            }
            if (!confirm('আপনি কি নিশ্চিত যে লাইভ সাইট থেকে ডাটা ইমপোর্ট শুরু করতে চান?')) {
                return;
            }

            self.isImporting = true;
            self.importResult = null;
            self.importError = '';

            fetch(window.admSettingsConfig.routes.startWpImport, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.admSettingsConfig.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    url: self.wpUrl,
                    username: self.wpUser,
                    password: self.wpPassword,
                    limit: parseInt(self.limit),
                    duplicate_action: self.duplicateAction,
                    import_properties: self.importProperties ? 1 : 0,
                    import_leads: self.importLeads ? 1 : 0,
                    import_demands: self.importDemands ? 1 : 0,
                    import_users: self.importUsers ? 1 : 0,
                    import_reviews: self.importReviews ? 1 : 0,
                    sync_cloudinary: self.syncCloudinary ? 1 : 0
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                self.isImporting = false;
                if (res.success) {
                    self.importResult = res;
                } else {
                    self.importError = res.message || 'ইমপোর্ট ব্যর্থ হয়েছে।';
                }
            })
            .catch(function(err) {
                self.isImporting = false;
                self.importError = 'ইমপোর্ট এরর: ' + err.message;
            });
        }
    };
}
</script>
@endsection
