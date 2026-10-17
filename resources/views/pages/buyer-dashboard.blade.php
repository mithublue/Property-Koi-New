@extends('layouts.app')

@section('title', 'ক্রেতা ড্যাশবোর্ড - PropertyKoi')

@section('content')
@include('pages.partials.buyer-dashboard-scripts')

<div class="aftabnagar-marketplace-wrapper font-sans text-slate-800 antialiased bg-slate-50 py-10 min-h-screen"
     x-data="admBuyerDashboard()"
     x-init="initDashboard()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Verification Success Banner -->
        @if(request('email_verified') || session('email_verified'))
        <div class="bg-emerald-500/10 border-2 border-emerald-500 text-emerald-800 rounded-2xl p-4 flex items-center justify-between gap-4 shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🎉</span>
                <span class="font-bold text-sm">অভিনন্দন! আপনার ইমেইল সফলভাবে ভেরিফাই সম্পন্ন হয়েছে।</span>
            </div>
            <a href="{{ route('buyer.dashboard') }}" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg transition">ঠিক আছে</a>
        </div>
        @endif

        @if(session('success') && !request('email_verified'))
        <div class="bg-emerald-500/10 border-2 border-emerald-500 text-emerald-800 rounded-2xl p-4 flex items-center justify-between gap-4 shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <span class="text-2xl">✓</span>
                <span class="font-bold text-sm">{{ session('success') }}</span>
            </div>
        </div>
        @endif

        <!-- ==========================================
             TOP PROFILE & WELCOME HEADER CARD
             ========================================== -->
        <div class="text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden"
             style="background-color: #0f172a; background-image: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #064e3b 100%);">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
                <div class="flex items-center gap-4 sm:gap-5">
                    <!-- Avatar with edit trigger -->
                    <div class="relative shrink-0 group cursor-pointer" @click="activeTab = 'profile'">
                        <img src="{{ $profile['avatar_url'] }}"
                             :src="profileForm.avatarPreview || profileForm.avatarUrl || '{{ $profile['avatar_url'] }}'"
                             onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($profile['display_name'] ?: 'Buyer') }}&background=059669&color=fff&size=160&bold=true';"
                             alt="Avatar"
                             class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border-2 border-emerald-400/80 shadow-md">
                        <div class="absolute inset-0 bg-black/40 rounded-2xl opacity-0 group-hover:opacity-100 flex items-center justify-center transition text-xs font-bold text-white">
                            এডিট
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-emerald-500/20 text-emerald-300 text-[11px] font-extrabold rounded-lg border border-emerald-400/30">
                                🛒 সম্মানিত ক্রেতা (Buyer)
                            </span>
                            <span class="text-xs text-slate-400 font-medium">
                                সদস্য: {{ $profile['member_since'] }}
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black m-0" x-text="profileForm.displayName || '{{ $profile['display_name'] }}'"></h1>
                        <p class="text-xs text-slate-300 mt-1 m-0 flex flex-wrap items-center gap-3">
                            <span x-show="profileForm.whatsappNumber" class="inline-flex items-center gap-1 text-emerald-300 font-bold">
                                💬 <span x-text="profileForm.whatsappNumber"></span>
                            </span>
                            <span x-show="profileForm.email" class="inline-flex items-center gap-1.5">
                                <span class="text-slate-300" x-text="profileForm.email"></span>
                                <template x-if="profileForm.isEmailVerified">
                                    <span class="px-1.5 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 rounded text-[10px] font-bold">✓ ভেরিফাইড</span>
                                </template>
                                <template x-if="!profileForm.isEmailVerified && profileForm.email">
                                    <span class="px-1.5 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-400/40 rounded text-[10px] font-bold cursor-pointer" @click="activeTab = 'profile'">⚠️ আনভেরিফাইড</span>
                                </template>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('property.create', ['role' => 'owner']) }}" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 ring-2 ring-amber-400/40">
                        <span>🏠 নিজের প্রপার্টি পোস্ট করুন</span>
                    </a>
                    <a href="{{ route('properties.land') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>🏞️ জমি খুঁজুন</span>
                    </a>
                    <a href="{{ route('properties.flat') }}" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>🏢 ফ্ল্যাট খুঁজুন</span>
                    </a>
                    <a href="{{ route('properties.share') }}" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <span>🤝 ল্যান্ড শেয়ার খুঁজুন</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-2.5 bg-white/10 hover:bg-rose-600/80 text-white font-bold text-xs rounded-xl transition cursor-pointer">
                            লগআউট
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stats Bar (6 Metrics) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mt-6 pt-6 border-t border-white/10 relative z-10">
                <div class="bg-white/5 backdrop-blur rounded-2xl p-3 sm:p-4 border border-white/10 text-center cursor-pointer hover:bg-white/10 transition"
                     @click="activeTab = 'favorites'">
                    <div class="text-xl sm:text-2xl font-black text-emerald-400" x-text="favoritesList.length"></div>
                    <div class="text-[11px] text-slate-300 font-bold mt-0.5">❤️ সংরক্ষিত পছন্দ</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-3 sm:p-4 border border-white/10 text-center cursor-pointer hover:bg-white/10 transition"
                     @click="activeTab = 'demands'">
                    <div class="text-xl sm:text-2xl font-black text-amber-300" x-text="demandsList.length + ' (' + totalDemandMatches() + ')'"></div>
                    <div class="text-[11px] text-slate-300 font-bold mt-0.5">🎯 চাহিদা ও ম্যাচিং</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-3 sm:p-4 border border-white/10 text-center cursor-pointer hover:bg-white/10 transition"
                     @click="activeTab = 'interactions'; interactionFilter = 'whatsapp_contact'">
                    <div class="text-xl sm:text-2xl font-black text-emerald-400" x-text="countInteractions('whatsapp_contact')"></div>
                    <div class="text-[11px] text-slate-300 font-bold mt-0.5">💬 হোয়াটসঅ্যাপ</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-3 sm:p-4 border border-white/10 text-center cursor-pointer hover:bg-white/10 transition"
                     @click="activeTab = 'interactions'; interactionFilter = 'make_offer'">
                    <div class="text-xl sm:text-2xl font-black text-teal-400" x-text="countInteractions('make_offer')"></div>
                    <div class="text-[11px] text-slate-300 font-bold mt-0.5">🏷️ দর প্রস্তাব</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-3 sm:p-4 border border-white/10 text-center cursor-pointer hover:bg-white/10 transition"
                     @click="activeTab = 'interactions'; interactionFilter = 'site_visit'">
                    <div class="text-xl sm:text-2xl font-black text-indigo-300" x-text="countInteractions('site_visit')"></div>
                    <div class="text-[11px] text-slate-300 font-bold mt-0.5">📅 সাইট ভিজিট</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-3 sm:p-4 border border-white/10 text-center cursor-pointer hover:bg-white/10 transition"
                     @click="activeTab = 'my_properties'">
                    <div class="text-xl sm:text-2xl font-black text-amber-400" x-text="myPropertiesList.length"></div>
                    <div class="text-[11px] text-slate-300 font-bold mt-0.5">🏠 আমার পোস্ট</div>
                </div>
            </div>
        </div>

        <!-- Profile Incomplete Notice Banner -->
        <div x-show="!profileForm.email || !profileForm.displayName || !profileForm.whatsappNumber || (!profileForm.isEmailVerified && profileForm.email)" x-cloak
             class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5">
                <span class="text-3xl shrink-0">⚠️</span>
                <div>
                    <h3 class="text-sm font-extrabold text-amber-950 m-0">আপনার প্রোফাইলটি সম্পূর্ণ করুন</h3>
                    <p class="text-xs text-amber-800 m-0 mt-1 leading-relaxed">
                        আপনার অ্যাকাউন্টে আবশ্যকীয় তথ্য 
                        <span x-show="!profileForm.email" class="font-bold underline">(ইমেইল ঠিকানা)</span>
                        <span x-show="!profileForm.displayName" class="font-bold underline">(পূর্ণ নাম)</span>
                        <span x-show="!profileForm.whatsappNumber" class="font-bold underline">(WhatsApp নম্বর)</span>
                        <span x-show="!profileForm.isEmailVerified && profileForm.email" class="font-bold underline text-amber-950">(ইমেইল ভেরিফিকেশন বাকি)</span>
                        এখনও সম্পন্ন হয়নি। বুকিং নোটিফিকেশন, দর প্রস্তাব এবং জরুরি প্রপার্টি আপডেট পেতে অনুগ্রহ করে আপনার প্রোফাইল সম্পূর্ণ করুন।
                    </p>
                </div>
            </div>
            <button type="button" @click="activeTab = 'profile'"
                class="shrink-0 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md transition cursor-pointer flex items-center gap-2">
                <span>👤 প্রোফাইল সম্পূর্ণ করুন</span>
                <span>&rarr;</span>
            </button>
        </div>

        <!-- Tab Navigation Buttons -->
        <div class="flex flex-wrap items-center gap-2 p-1.5 bg-slate-200/80 rounded-2xl border border-slate-300/80 text-xs font-extrabold max-w-4xl">
            <button type="button" @click="activeTab = 'favorites'"
                    :class="activeTab === 'favorites' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    class="flex-1 py-3 px-3 rounded-xl transition text-center flex items-center justify-center gap-1.5 cursor-pointer">
                <span>❤️</span>
                <span>পছন্দসমূহ (<span x-text="favoritesList.length"></span>)</span>
            </button>
            <button type="button" @click="activeTab = 'demands'"
                    :class="activeTab === 'demands' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    class="flex-1 py-3 px-3 rounded-xl transition text-center flex items-center justify-center gap-1.5 cursor-pointer">
                <span>🎯</span>
                <span>আমার চাহিদা (<span x-text="demandsList.length"></span>)</span>
            </button>
            <button type="button" @click="activeTab = 'interactions'"
                    :class="activeTab === 'interactions' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    class="flex-1 py-3 px-3 rounded-xl transition text-center flex items-center justify-center gap-1.5 cursor-pointer">
                <span>📋</span>
                <span>কার্যক্রম (<span x-text="interactionsList.length"></span>)</span>
            </button>
            <button type="button" @click="activeTab = 'my_properties'"
                    :class="activeTab === 'my_properties' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    class="flex-1 py-3 px-3 rounded-xl transition text-center flex items-center justify-center gap-1.5 cursor-pointer">
                <span>🏠</span>
                <span>আমার প্রপার্টি (<span x-text="myPropertiesList.length"></span>)</span>
            </button>
            <button type="button" @click="activeTab = 'profile'"
                    :class="activeTab === 'profile' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    class="flex-1 py-3 px-3 rounded-xl transition text-center flex items-center justify-center gap-1.5 cursor-pointer">
                <span>👤</span>
                <span>প্রোফাইল</span>
            </button>
        </div>

        <!-- ==========================================
             TAB 1: FAVORITE PROPERTIES
             ========================================== -->
        <div x-show="activeTab === 'favorites'" class="space-y-6">
            
            <!-- Category Filter Bar & Search -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 w-full sm:w-auto flex-wrap">
                    <button type="button" @click="categoryFilter = 'all'"
                            :class="categoryFilter === 'all' ? 'bg-emerald-600 text-white font-extrabold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer">
                        সব ক্যাটাগরি (<span x-text="favoritesList.length"></span>)
                    </button>
                    <button type="button" @click="categoryFilter = 'land'"
                            :class="categoryFilter === 'land' ? 'bg-emerald-600 text-white font-extrabold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1">
                        <span>🏞️ জমি</span>
                        <span>(<span x-text="favoritesList.filter(p => p.type === 'land').length"></span>)</span>
                    </button>
                    <button type="button" @click="categoryFilter = 'flat'"
                            :class="categoryFilter === 'flat' ? 'bg-teal-600 text-white font-extrabold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1">
                        <span>🏢 ফ্ল্যাট</span>
                        <span>(<span x-text="favoritesList.filter(p => p.type === 'flat').length"></span>)</span>
                    </button>
                    <button type="button" @click="categoryFilter = 'land_share'"
                            :class="categoryFilter === 'land_share' ? 'bg-purple-600 text-white font-extrabold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1">
                        <span>🤝 ল্যান্ড শেয়ার</span>
                        <span>(<span x-text="favoritesList.filter(p => p.type === 'land_share').length"></span>)</span>
                    </button>
                    <button type="button" @click="categoryFilter = 'commercial'"
                            :class="categoryFilter === 'commercial' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1">
                        <span>🏬 কমার্শিয়াল</span>
                        <span>(<span x-text="favoritesList.filter(p => p.type === 'commercial').length"></span>)</span>
                    </button>
                </div>

                <!-- Search Input inside favorites -->
                <div class="w-full sm:w-72">
                    <input type="text" x-model="searchKeyword" placeholder="পছন্দের তালিকা থেকে খুঁজুন..."
                           class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium">
                </div>
            </div>

            <!-- Favorites Grid -->
            <div x-show="filteredFavorites().length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="p in filteredFavorites()" :key="p.id">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group relative">
                        <!-- Image Container -->
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                            <a :href="p.detail_url" class="block w-full h-full">
                                <img :src="p.cover_url"
                                     :alt="p.title"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </a>

                            <!-- Badges -->
                            <div class="absolute top-3 left-3 flex flex-wrap gap-1.5 items-center">
                                <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-lg uppercase tracking-wider text-white shadow-sm"
                                      :class="p.type === 'land' ? 'bg-emerald-600' : (p.type === 'land_share' ? 'bg-purple-600' : (p.type === 'commercial' ? 'bg-indigo-600' : 'bg-teal-600'))"
                                      x-text="p.type_label"></span>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-900/80 text-white backdrop-blur"
                                      x-text="'ব্লক ' + p.block"></span>
                            </div>

                            <!-- Remove from Favorites Button -->
                            <button type="button" @click="removeFavorite(p.id)"
                                    title="পছন্দ তালিকা থেকে মুছুন"
                                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 hover:bg-rose-50 hover:text-rose-600 text-rose-500 flex items-center justify-center shadow-md transition cursor-pointer">
                                ✕
                            </button>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div>
                                <div class="text-[11px] font-mono text-slate-400 font-semibold" x-text="p.property_code"></div>
                                <h3 class="text-sm font-extrabold text-slate-900 mt-1 line-clamp-2 leading-snug group-hover:text-emerald-700 transition">
                                    <a :href="p.detail_url" x-text="p.title"></a>
                                </h3>
                                <div class="text-xs text-slate-500 mt-2 flex items-center gap-3">
                                    <span x-show="p.road_number" x-text="'রোড: ' + p.road_number"></span>
                                    <span x-show="p.plot_number" x-text="'প্লট: ' + p.plot_number"></span>
                                    <span class="font-bold text-slate-700" x-text="p.size_label"></span>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase">মূল্য</div>
                                    <div class="text-base font-black text-emerald-700 font-mono" x-text="p.formatted_price"></div>
                                </div>
                                <a :href="p.detail_url" class="px-3.5 py-2 bg-slate-900 hover:bg-emerald-600 text-white font-extrabold text-xs rounded-xl transition shadow-xs">
                                    বিস্তারিত &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty Favorites State -->
            <div x-show="filteredFavorites().length === 0"
                 class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-sm space-y-4 max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto">
                    ❤️
                </div>
                <h3 class="text-lg font-extrabold text-slate-900 m-0">কোনো প্রপার্টি পছন্দের তালিকায় নেই</h3>
                <p class="text-xs text-slate-500 leading-relaxed m-0">
                    মার্কেটপ্লেসের জমি ও ফ্ল্যাটের বিস্তারিত দেখে পছন্দ বোতামে (❤️) ক্লিক করলে তা স্বয়ংক্রিয়ভাবে এখানে সংরক্ষিত থাকবে।
                </p>
                <div class="pt-2 flex justify-center gap-3">
                    <a href="{{ route('properties.land') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition">
                        🏞️ জমি দেখুন
                    </a>
                    <a href="{{ route('properties.flat') }}" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs rounded-xl shadow-md transition">
                        🏢 ফ্ল্যাট দেখুন
                    </a>
                    <a href="{{ route('properties.share') }}" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs rounded-xl shadow-md transition">
                        🤝 ল্যান্ড শেয়ার দেখুন
                    </a>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 2: BUYER DEMANDS & AI MATCH ENGINE
             ========================================== -->
        <div x-show="activeTab === 'demands'" class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[11px] font-extrabold rounded-lg border border-emerald-300">
                            🤖 AI Demand Match Engine
                        </span>
                        <span class="text-xs text-slate-500 font-medium">
                            প্রতিদিন সকাল ৯:০০ টায় স্বয়ংক্রিয় ক্রন শিডিউল
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 m-0">আমার প্রপার্টি চাহিদা ও স্বয়ংক্রিয় এলার্ট</h2>
                    <p class="text-xs text-slate-600 mt-1 m-0 leading-relaxed max-w-xl">
                        আপনার বাজেট ও পছন্দের এলাকার সাথে মিল রেখে নতুন কোনো জমি বা ফ্ল্যাট লিস্টিং হওয়ামাত্রই ক্রন শিডিউলে স্বয়ংক্রিয়ভাবে আপনাকে পুশ নোটিফিকেশন, ইমেইল ও হোয়াটসঅ্যাপে জানিয়ে দেওয়া হবে।
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <button type="button" @click="demandModalOpen = true"
                            class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer">
                        <span class="text-base leading-none">＋</span>
                        <span>নতুন চাহিদা যোগ করুন</span>
                    </button>
                    <button type="button" @click="runDemandMatchCronManual()" :disabled="runningCron"
                            class="px-4 py-3 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-2 cursor-pointer">
                        <span x-show="!runningCron">🔄 এখনই ম্যাচিং চেক করুন</span>
                        <span x-show="runningCron">যাচাই হচ্ছে... ⏳</span>
                    </button>
                </div>
            </div>

            <!-- List of Demands -->
            <div class="space-y-5">
                <template x-for="d in demandsList" :key="d.id">
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-xl text-xs font-black"
                                      :class="d.status === 'active' ? 'bg-emerald-50 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                      x-text="d.status === 'active' ? '● সক্রিয় (Active)' : '○ বন্ধ (Closed)'"></span>
                                <span class="font-mono text-xs font-bold text-slate-500" x-text="'ID: ' + d.requirement_code"></span>
                                <span class="text-xs text-slate-400" x-text="'যোগ করা হয়েছে: ' + d.created_at"></span>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <a x-show="!d.telegram_chat_id" :href="d.telegram_deep_link" target="_blank"
                                   class="px-2.5 py-1.5 rounded-lg bg-sky-50 hover:bg-sky-100 border border-sky-300 text-sky-800 text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs">
                                    <span>⚡ Telegram ১-ক্লিক</span>
                                </a>
                                <span x-show="d.telegram_chat_id" class="px-2 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold inline-flex items-center gap-1">
                                    <span>✓ Telegram সক্রিয়</span>
                                </span>
                                <button type="button" @click="toggleDemandStatus(d.id, d.status)"
                                        class="px-3 py-1.5 rounded-lg border text-xs font-bold transition cursor-pointer"
                                        :class="d.status === 'active' ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50'"
                                        x-text="d.status === 'active' ? 'সাময়িক বন্ধ করুন' : 'পুনরায় সক্রিয় করুন'"></button>
                                <button type="button" @click="deleteDemand(d.id)"
                                        class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold transition cursor-pointer">
                                    মুছে ফেলুন
                                </button>
                            </div>
                        </div>

                        <!-- Demand Details Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="block text-[11px] font-bold text-slate-400 uppercase">প্রপার্টির ধরন</span>
                                <span class="font-extrabold text-slate-800 text-sm mt-0.5 block" x-text="d.property_type_label"></span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="block text-[11px] font-bold text-slate-400 uppercase">পছন্দের এলাকা</span>
                                <div class="font-bold text-slate-800 mt-0.5 flex flex-wrap gap-1">
                                    <template x-for="ar in (d.preferred_areas && d.preferred_areas.length ? d.preferred_areas : ['সকল এলাকা'])" :key="ar">
                                        <span class="px-2 py-0.5 bg-white border border-slate-200 rounded-md text-[11px]" x-text="ar"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="block text-[11px] font-bold text-slate-400 uppercase">বাজেট পরিসীমা</span>
                                <span class="font-extrabold text-emerald-700 text-sm mt-0.5 block"
                                      x-text="(d.min_budget || d.max_budget) ? ((d.min_budget ? (d.min_budget/100000).toFixed(0) + ' লাখ' : '০') + ' - ' + (d.max_budget ? (d.max_budget/100000).toFixed(0) + ' লাখ' : 'উন্মুক্ত')) : 'নির্দিষ্ট নেই'"></span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="block text-[11px] font-bold text-slate-400 uppercase">নোটিফিকেশন চ্যানেল</span>
                                <div class="mt-0.5 flex items-center gap-1.5 font-bold text-slate-700">
                                    <span x-show="d.notify_channels && d.notify_channels.includes('push')" title="পুশ নোটিফিকেশন">🔔</span>
                                    <span x-show="d.notify_channels && d.notify_channels.includes('email')" title="ইমেইল">📧</span>
                                    <span x-show="d.notify_channels && d.notify_channels.includes('phone')" title="ফোন/হোয়াটসঅ্যাপ">💬</span>
                                </div>
                            </div>
                        </div>

                        <!-- Matched Properties Section -->
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-sm text-slate-900">🎯 ম্যাচিং প্রপার্টিসমূহ</span>
                                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-black rounded-full" x-text="(d.matches ? d.matches.length : 0) + ' টি ম্যাচ'"></span>
                                </div>
                                <span class="text-[11px] text-slate-400" x-show="d.last_notified_at" x-text="'সর্বশেষ নোটিফাই: ' + d.last_notified_at"></span>
                            </div>

                            <template x-if="d.matches && d.matches.length > 0">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <template x-for="m in d.matches" :key="m.id">
                                        <div class="p-3 bg-slate-50 hover:bg-emerald-50/50 rounded-2xl border border-slate-200 transition flex flex-col justify-between gap-2">
                                            <div>
                                                <div class="flex items-center justify-between gap-1 mb-1">
                                                    <span class="text-[10px] font-bold text-emerald-700 uppercase" x-text="m.property_code + ' • ' + (m.area || '') + ' ' + (m.block ? 'ব্লক ' + m.block : '')"></span>
                                                    <span class="px-1.5 py-0.5 bg-emerald-200/80 text-emerald-900 font-black text-[10px] rounded-md" x-text="Math.round(m.match_score) + '% Match'"></span>
                                                </div>
                                                <h4 class="font-bold text-xs text-slate-900 line-clamp-2 mt-1" x-text="m.title"></h4>
                                            </div>
                                            <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 text-[11px]">
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    <template x-for="ch in (m.channels || [])" :key="ch">
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                                              :class="ch === 'push' ? 'bg-amber-100 text-amber-800' : (ch === 'email' ? 'bg-teal-100 text-teal-800' : 'bg-emerald-100 text-emerald-800')"
                                                              x-text="ch === 'push' ? '🔔 পুশ' : (ch === 'email' ? '📧 ইমেইল' : '💬 হোয়াটসঅ্যাপ')"></span>
                                                    </template>
                                                </div>
                                                <a :href="m.detail_url" target="_blank" class="font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-0.5 shrink-0">
                                                    <span>বিস্তারিত</span>
                                                    <span>&rarr;</span>
                                                </a>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!d.matches || d.matches.length === 0">
                                <div class="p-5 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center space-y-1">
                                    <div class="text-xl">⏳</div>
                                    <div class="font-bold text-xs text-slate-700">এখনো কোনো নতুন ম্যাচিং প্রপার্টি পাওয়া যায়নি</div>
                                    <p class="text-[11px] text-slate-500 m-0">নতুন প্রপার্টি লিস্টিং হওয়ামাত্রই ক্রন শিডিউল আপনাকে পুশ নোটিফিকেশন, ইমেইল বা হোয়াটসঅ্যাপে অবগত করবে।</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="demandsList.length === 0">
                    <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-3">
                        <div class="text-4xl">📋</div>
                        <h3 class="text-base font-extrabold text-slate-900 m-0">আপনার কোনো সক্রিয় প্রপার্টি চাহিদা নেই</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto m-0 leading-relaxed">
                            আপনি যে ধরনের প্রপার্টি খুঁজছেন (বাজেট, এলাকা, সাইজ ইত্যাদি) তা রেকর্ড করে রাখুন। উপযুক্ত কোনো প্রপার্টি লিস্টিং হওয়ামাত্রই আমাদের সিস্টেম স্বয়ংক্রিয়ভাবে আপনাকে নোটিফাই করবে।
                        </p>
                        <button type="button" @click="demandModalOpen = true"
                                class="mt-2 inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition cursor-pointer">
                            <span>＋ প্রথম চাহিদা যোগ করুন</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Inline Demand Creation Modal -->
        <div x-show="demandModalOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="demandModalOpen = false"
                 class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 sm:p-8 space-y-5 my-8">
                
                <div class="flex items-center justify-between border-b pb-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 m-0 flex items-center gap-2">
                            <span>🎯</span>
                            <span>নতুন প্রপার্টি চাহিদা রেকর্ড করুন</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5 m-0">চাহিদা অনুযায়ী নতুন লিস্টিং এলেই প্রতিদিন ক্রনে স্বয়ংক্রিয় নোটিফিকেশন পাবেন।</p>
                    </div>
                    <button type="button" @click="demandModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1 cursor-pointer">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitDemandModal()" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1.5">প্রপার্টির ধরন</label>
                        <div class="grid grid-cols-4 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" value="land" x-model="demandForm.property_type" class="sr-only peer">
                                <div class="p-2.5 text-center rounded-xl border border-slate-200 peer-checked:bg-emerald-600 peer-checked:text-white font-bold transition">
                                    🏞️ জমি
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="flat" x-model="demandForm.property_type" class="sr-only peer">
                                <div class="p-2.5 text-center rounded-xl border border-slate-200 peer-checked:bg-teal-600 peer-checked:text-white font-bold transition">
                                    🏢 ফ্ল্যাট
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="share" x-model="demandForm.property_type" class="sr-only peer">
                                <div class="p-2.5 text-center rounded-xl border border-slate-200 peer-checked:bg-purple-600 peer-checked:text-white font-bold transition">
                                    🤝 শেয়ার
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="any" x-model="demandForm.property_type" class="sr-only peer">
                                <div class="p-2.5 text-center rounded-xl border border-slate-200 peer-checked:bg-slate-800 peer-checked:text-white font-bold transition">
                                    যেকোনো
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1.5">পছন্দের এলাকা (একাধিক নির্বাচনযোগ্য)</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="ar in ['Aftabnagar', 'Bashundhara R/A', 'Badda', 'Banasree', 'Gulshan', 'Uttara', 'Mirpur', 'Dhanmondi', 'Rampura']" :key="ar">
                                <button type="button" 
                                        @click="toggleModalArea(ar)"
                                        :class="demandForm.preferred_areas.includes(ar) ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-medium'"
                                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer"
                                        x-text="ar === 'Aftabnagar' ? 'আফতাবনগর' : (ar === 'Bashundhara R/A' ? 'বসুন্ধরা আ/এ' : (ar === 'Badda' ? 'বাড্ডা' : (ar === 'Banasree' ? 'বনশ্রী' : (ar === 'Gulshan' ? 'গুলশান' : (ar === 'Uttara' ? 'উত্তরা' : (ar === 'Mirpur' ? 'মিরপুর' : (ar === 'Dhanmondi' ? 'ধানমন্ডি' : 'রামপুরা')))))))">
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">সর্বনিম্ন বাজেট (টাকা)</label>
                            <input type="number" x-model="demandForm.min_budget" placeholder="যেমন: 5000000" class="w-full border rounded-xl px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">সর্বোচ্চ বাজেট (টাকা)</label>
                            <input type="number" x-model="demandForm.max_budget" placeholder="যেমন: 15000000" class="w-full border rounded-xl px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">সর্বনিম্ন সাইজ (কাঠা/স্কয়ারফিট)</label>
                            <input type="number" step="0.1" x-model="demandForm.min_size" placeholder="যেমন: 3" class="w-full border rounded-xl px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">সর্বোচ্চ সাইজ (কাঠা/স্কয়ারফিট)</label>
                            <input type="number" step="0.1" x-model="demandForm.max_size" placeholder="যেমন: 5" class="w-full border rounded-xl px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1.5">নোটিফিকেশন চ্যানেল</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex items-center gap-2 p-2.5 bg-slate-50 border rounded-xl cursor-pointer">
                                <input type="checkbox" value="push" x-model="demandForm.notify_channels" class="w-4 h-4 rounded text-emerald-600">
                                <span class="font-bold">🔔 পুশ</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 bg-slate-50 border rounded-xl cursor-pointer">
                                <input type="checkbox" value="email" x-model="demandForm.notify_channels" class="w-4 h-4 rounded text-teal-600">
                                <span class="font-bold">📧 ইমেইল</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 bg-slate-50 border rounded-xl cursor-pointer">
                                <input type="checkbox" value="phone" x-model="demandForm.notify_channels" class="w-4 h-4 rounded text-indigo-600">
                                <span class="font-bold">💬 WhatsApp</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-end gap-2">
                        <button type="button" @click="demandModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition cursor-pointer">
                            বাতিল
                        </button>
                        <button type="submit" :disabled="savingDemand" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold rounded-xl shadow transition cursor-pointer">
                            <span x-show="!savingDemand">চাহিদা রেকর্ড করুন &rarr;</span>
                            <span x-show="savingDemand">সংরক্ষণ হচ্ছে... ⏳</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==========================================
             TAB 3: INTERACTIONS & ACTIVITIES
             ========================================== -->
        <div x-show="activeTab === 'interactions'" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="interactionFilter = 'all'"
                            :class="interactionFilter === 'all' ? 'bg-slate-900 text-white font-extrabold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer">
                        সব কার্যক্রম (<span x-text="interactionsList.length"></span>)
                    </button>
                    <button type="button" @click="interactionFilter = 'whatsapp_contact'"
                            :class="interactionFilter === 'whatsapp_contact' ? 'bg-emerald-600 text-white font-extrabold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5">
                        <span>💬 হোয়াটসঅ্যাপ</span>
                        <span>(<span x-text="countInteractions('whatsapp_contact')"></span>)</span>
                    </button>
                    <button type="button" @click="interactionFilter = 'make_offer'"
                            :class="interactionFilter === 'make_offer' ? 'bg-teal-600 text-white font-extrabold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5">
                        <span>🏷️ দর প্রস্তাব</span>
                        <span>(<span x-text="countInteractions('make_offer')"></span>)</span>
                    </button>
                    <button type="button" @click="interactionFilter = 'site_visit'"
                            :class="interactionFilter === 'site_visit' ? 'bg-indigo-600 text-white font-extrabold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                            class="px-4 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5">
                        <span>📅 সাইট ভিজিট</span>
                        <span>(<span x-text="countInteractions('site_visit')"></span>)</span>
                    </button>
                </div>

                <div class="text-xs text-slate-500 font-medium">
                    মোট <strong class="text-slate-800 font-bold" x-text="filteredInteractions().length"></strong> টি রেকর্ড
                </div>
            </div>

            <!-- Interactions Cards -->
            <div x-show="filteredInteractions().length > 0" class="space-y-4">
                <template x-for="item in filteredInteractions()" :key="item.id">
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm hover:shadow-md transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <img :src="item.cover_url" alt="" class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover shrink-0 border border-slate-200">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-extrabold shadow-xs"
                                          :class="item.badge_color"
                                          x-text="item.type_label"></span>
                                    <span class="text-[11px] font-mono text-slate-400 font-bold" x-text="item.property_code"></span>
                                    <span class="text-[11px] text-slate-400" x-text="item.formatted_date"></span>
                                </div>

                                <h4 class="text-sm font-extrabold text-slate-900 m-0 leading-tight">
                                    <a :href="item.detail_url" class="hover:text-emerald-700 transition" x-text="item.property_title"></a>
                                </h4>

                                <div class="text-xs text-slate-600 pt-0.5">
                                    <template x-if="item.interaction_type === 'make_offer'">
                                        <div class="font-bold text-teal-800">
                                            💰 আপনার প্রস্তাবিত মূল্য: <span class="font-mono text-sm" x-text="item.formatted_offer"></span>
                                            <span x-show="item.notes" class="text-slate-500 font-normal ml-2" x-text="'(' + item.notes + ')'"></span>
                                        </div>
                                    </template>

                                    <template x-if="item.interaction_type === 'site_visit'">
                                        <div class="font-bold text-indigo-900">
                                            🗓️ ভিজিট শিডিউল: <span class="font-mono" x-text="item.slot_date"></span> | স্লট: <span class="font-mono" x-text="item.slot_time"></span>
                                            <span x-show="item.status" class="ml-2 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] uppercase font-bold" x-text="item.status"></span>
                                        </div>
                                    </template>

                                    <template x-if="item.interaction_type === 'whatsapp_contact'">
                                        <div class="text-slate-500 text-[11px]">
                                            📱 হোয়াটসঅ্যাপে বিস্তারিত তথ্য জানতে সরাসরি যোগাযোগ রিকোয়েস্ট করা হয়েছিল।
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 flex justify-end">
                            <a :href="item.detail_url" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-800 font-extrabold text-xs rounded-xl transition text-center shadow-xs">
                                প্রপার্টি দেখুন &rarr;
                            </a>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="filteredInteractions().length === 0"
                 class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-sm space-y-4 max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto">
                    📋
                </div>
                <h3 class="text-lg font-extrabold text-slate-900 m-0">কোনো কার্যক্রম বা ইন্টারঅ্যাকশন রেকর্ড নেই</h3>
                <p class="text-xs text-slate-500 leading-relaxed m-0">
                    আপনি যখন কোনো প্রপার্টিতে হোয়াটসঅ্যাপে যোগাযোগ করবেন, দর প্রস্তাব দেবেন বা সাইট ভিজিট শিডিউল করবেন—সব তথ্য এখানে সুসংগঠিতভাবে সংরক্ষিত থাকবে।
                </p>
                <div class="pt-2">
                    <a href="{{ route('properties.land') }}" class="inline-block px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition">
                        মার্কেটপ্লেসের প্রপার্টিসমূহ দেখুন &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 4: MY PROPERTIES (POSTED AS OWNER/BUYER)
             ========================================== -->
        <div x-show="activeTab === 'my_properties'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="font-extrabold text-slate-900 text-lg m-0 flex items-center gap-2">
                            <span>🏠</span>
                            <span>আমার পোস্টকৃত প্রপার্টি তালিকা (<span x-text="myPropertiesList.length"></span>)</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5 m-0">মালিক হিসেবে সরাসরি যুক্ত করা আপনার জমি বা ফ্ল্যাটের লিস্টিং ও তথ্য পরিবর্তন</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono font-bold bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl">
                            প্রদর্শিত: <strong x-text="filteredMyProperties().length"></strong>/<span x-text="myPropertiesList.length"></span>টি
                        </span>
                        <a href="{{ route('property.create', ['role' => 'owner']) }}" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 ring-2 ring-amber-400/40">
                            <span>+ নতুন প্রপার্টি পোস্ট</span>
                        </a>
                    </div>
                </div>

                <div x-show="myPropertiesList.length === 0" class="py-12 text-center max-w-md mx-auto space-y-4">
                    <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center text-3xl mx-auto border border-amber-200 text-amber-600">
                        🏠
                    </div>
                    <h3 class="text-lg font-extrabold text-slate-900 m-0">আপনি এখনো কোনো প্রপার্টি লিস্টিং করেননি</h3>
                    <p class="text-xs text-slate-500 leading-relaxed m-0">
                        আপনার কি আফতাবনগরে নিজস্ব জমি বা ফ্ল্যাট রয়েছে? ক্রেতা অ্যাকাউন্ট থাকা সত্ত্বেও আপনি নিজেই সরাসরি মালিক হিসেবে প্রপার্টি পোস্ট করতে পারেন সম্পূর্ণ বিনামূল্যে।
                    </p>
                    <div class="pt-2">
                        <a href="{{ route('property.create', ['role' => 'owner']) }}" class="inline-block px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md transition">
                            + নিজের প্রপার্টি পোস্ট করুন (মালিক হিসেবে) &rarr;
                        </a>
                    </div>
                </div>

                <div x-show="myPropertiesList.length > 0" class="space-y-4">
                    <!-- Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-xs">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1 text-[11px]">সার্চ (কোড / টাইটেল / প্লট):</label>
                            <input type="text" x-model="mySearchQuery" placeholder="যেমন: AF-101 বা প্লট..." class="w-full border rounded-xl px-3 py-2 bg-white font-medium focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1 text-[11px]">প্রপার্টির টাইপ:</label>
                            <select x-model="myTypeFilter" class="w-full border rounded-xl px-3 py-2 bg-white font-bold">
                                <option value="all">সব টাইপ</option>
                                <option value="land">🏞️ জমি</option>
                                <option value="flat">🏢 ফ্ল্যাট</option>
                                <option value="land_share">🤝 ল্যান্ড শেয়ার</option>
                                <option value="commercial">🏬 দোকান/কমার্শিয়াল</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1 text-[11px]">স্ট্যাটাস:</label>
                            <select x-model="myStatusFilter" class="w-full border rounded-xl px-3 py-2 bg-white font-bold">
                                <option value="all">সব স্ট্যাটাস</option>
                                <option value="live">🟢 Live (অনুমোদিত)</option>
                                <option value="pending_verification">🟡 Pending Verification</option>
                                <option value="draft">⚪ Draft</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1 text-[11px]">ব্লক ফিল্টার:</label>
                            <select x-model="myBlockFilter" class="w-full border rounded-xl px-3 py-2 bg-white font-bold">
                                <option value="all">সব ব্লক</option>
                                @foreach(range('A', 'P') as $blk)
                                    <option value="{{ $blk }}">ব্লক {{ $blk }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 border-b border-slate-200 font-bold uppercase text-[10px]">
                                    <th class="p-3">কোড & টাইপ</th>
                                    <th class="p-3">প্রপার্টি বিবরণ</th>
                                    <th class="p-3">অবস্থান</th>
                                    <th class="p-3">আস্কিং মূল্য</th>
                                    <th class="p-3">স্ট্যাটাস</th>
                                    <th class="p-3 text-right">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="p in filteredMyProperties()" :key="p.id">
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="p-3">
                                            <div class="font-mono font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded inline-block border border-emerald-200" x-text="p.property_code"></div>
                                            <div class="mt-1 flex items-center gap-1.5">
                                                <span :class="p.type === 'land' ? 'bg-emerald-600' : (p.type === 'land_share' ? 'bg-purple-600' : (p.type === 'commercial' ? 'bg-indigo-600' : 'bg-teal-600'))" class="px-2 py-0.5 rounded text-[10px] font-bold text-white" x-text="p.type_label"></span>
                                                <template x-if="p.views_count && Number(p.views_count) > 0">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded" title="ভিউ">
                                                        <span>👁️</span>
                                                        <span x-text="p.views_count_bn + ' ভিউ'"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-900 text-sm leading-snug" x-text="p.title"></div>
                                            <div class="text-slate-400 text-[11px] mt-0.5" x-text="'পোস্ট তারিখ: ' + p.created_at"></div>
                                        </td>
                                        <td class="p-3 font-semibold text-slate-800">
                                            <span x-text="'ব্লক ' + p.block"></span>
                                            <template x-if="p.plot_number">
                                                <span x-text="' • প্লট #' + p.plot_number"></span>
                                            </template>
                                        </td>
                                        <td class="p-3 font-extrabold text-emerald-700 text-sm" x-text="p.formatted_price"></td>
                                        <td class="p-3">
                                            <span class="px-2.5 py-1 rounded-full font-bold text-[10px] uppercase"
                                                  :class="p.status_color"
                                                  x-text="p.status_label"></span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" @click="openEditModal(p.id)" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition text-[11px] shadow-xs cursor-pointer">
                                                    ✏️ এডিট
                                                </button>
                                                <a :href="p.detail_url" target="_blank" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition text-[11px]">
                                                    পেজ &rarr;
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TAB 5: PROFILE & ACCOUNT SETTINGS
             ========================================== -->
        <div x-show="activeTab === 'profile'" class="space-y-6">
            <div class="max-w-2xl bg-white rounded-3xl border border-slate-200 shadow-xl p-6 sm:p-8 space-y-6">
                
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 m-0">ব্যক্তিগত তথ্য ও প্রোফাইল সেটিংস</h2>
                    <p class="text-xs text-slate-500 mt-1 m-0">আপনার যোগাযোগের তথ্য, ইমেইল এবং পছন্দের প্রপার্টির মানদণ্ড হালনাগাদ রাখুন।</p>
                </div>

                <!-- Alert Message -->
                <div x-show="profileMsg" x-cloak
                     :class="profileSuccess ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-rose-50 text-rose-800 border-rose-300'"
                     class="p-4 rounded-2xl border text-xs font-bold leading-relaxed shadow-xs flex items-center gap-2" x-text="profileMsg">
                </div>

                <form @submit.prevent="saveProfile()" class="space-y-5 text-xs">
                    
                    <!-- Avatar Upload Area -->
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center gap-5">
                        <div class="relative shrink-0">
                            <img src="{{ $profile['avatar_url'] }}"
                                 :src="profileForm.avatarPreview || profileForm.avatarUrl || '{{ $profile['avatar_url'] }}'"
                                 onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($profile['display_name'] ?: 'Buyer') }}&background=059669&color=fff&size=160&bold=true';"
                                 alt="Avatar Preview"
                                 class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border-2 border-emerald-500 shadow">
                        </div>
                        <div class="space-y-1.5 flex-1">
                            <label class="block font-bold text-slate-800 uppercase">প্রোফাইল ছবি (Profile Picture)</label>
                            <input type="file" @change="handleAvatarChange($event)" accept="image/png, image/jpeg, image/webp"
                                   class="text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                            <p class="text-[11px] text-slate-400 m-0">JPG, PNG বা WEBP (সর্বোচ্চ ৫ মেগাবাইট)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">আপনার পূর্ণ নাম *</label>
                            <input type="text" x-model="profileForm.displayName" required class="w-full border rounded-xl px-3.5 py-2.5 text-sm bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-700 uppercase">ইমেইল ঠিকানা *</label>
                                <span x-show="profileForm.email && profileForm.isEmailVerified" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-300 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                    ✓ ভেরিফাইড
                                </span>
                                <span x-show="profileForm.email && !profileForm.isEmailVerified" class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-300 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                    ⚠️ ভেরিফিকেশন বাকি
                                </span>
                            </div>
                            <input type="email" x-model="profileForm.email" required class="w-full border rounded-xl px-3.5 py-2.5 text-sm bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Email Verification Code Box (Show when unverified) -->
                    <div x-show="profileForm.email && !profileForm.isEmailVerified" class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div>
                                <h5 class="text-xs font-black text-amber-950 m-0 flex items-center gap-1.5">
                                    <span>✉️</span> ইমেইল ভেরিফিকেশন (৬ সংখ্যার কোড বা লিঙ্ক)
                                </h5>
                                <p class="text-[11px] text-amber-800 m-0 mt-0.5">
                                    <template x-if="!codeSent">
                                        <span>আপনার ইমেইল <strong class="font-bold text-amber-950" x-text="profileForm.email"></strong> ভেরিফাই করতে 'কোড পাঠান' বাটনে চাপ দিন।</span>
                                    </template>
                                    <template x-if="codeSent">
                                        <span>আপনার ইমেইল <strong class="font-bold text-amber-950" x-text="profileForm.email"></strong> এ প্রেরিত ৬ সংখ্যার ওটিপি কোড লিখুন বা লিংকে ক্লিক করুন।</span>
                                    </template>
                                </p>
                            </div>
                            <button type="button" @click="sendVerificationEmail()" :disabled="sendingVerifyEmail"
                                class="shrink-0 text-[11px] font-bold text-emerald-700 bg-white hover:bg-emerald-50 border border-emerald-300 px-3.5 py-1.5 rounded-xl shadow-xs transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer">
                                <span x-show="sendingVerifyEmail">পাঠানো হচ্ছে...</span>
                                <span x-show="!sendingVerifyEmail && !codeSent">✉️ কোড পাঠান</span>
                                <span x-show="!sendingVerifyEmail && codeSent">🔄 কোড পুনরায় পাঠান</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-2 max-w-sm">
                            <input type="text" maxlength="6" x-model="verifyCodeInput" placeholder="৬ সংখ্যার কোড"
                                   class="w-36 tracking-widest text-center font-mono font-bold text-base border-2 border-amber-300 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <button type="button" @click="verifyEmailCode()" :disabled="verifyingCode || !verifyCodeInput"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer">
                                <span x-show="!verifyingCode">যাচাই করুন</span>
                                <span x-show="verifyingCode">যাচাই হচ্ছে...</span>
                            </button>
                        </div>
                        <div x-show="verifyNotice" class="text-xs font-bold" :class="verifyNoticeSuccess ? 'text-emerald-700' : 'text-rose-600'" x-text="verifyNotice"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">
                                হোয়াটসঅ্যাপ নম্বর * <span class="text-emerald-600">(সুপারিশকৃত)</span>
                            </label>
                            <input type="tel" x-model="profileForm.whatsappNumber" @input="if(copyWhatsappToAlt) profileForm.phone = profileForm.whatsappNumber" required placeholder="017xxxxxxxx" class="w-full border rounded-xl px-3.5 py-2.5 text-sm bg-white font-mono font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <p class="text-[10px] text-slate-400 mt-1 m-0">ডিল কোঅর্ডিনেশনের জন্য সক্রিয় WhatsApp নম্বর ব্যবহার করুন।</p>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">বিকল্প মোবাইল নম্বর</label>
                            <input type="tel" x-model="profileForm.phone" @input="if(profileForm.phone !== profileForm.whatsappNumber) copyWhatsappToAlt = false" placeholder="018xxxxxxxx" class="w-full border rounded-xl px-3.5 py-2.5 text-sm bg-white font-mono font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <div class="mt-2">
                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" x-model="copyWhatsappToAlt" @change="if(copyWhatsappToAlt) profileForm.phone = profileForm.whatsappNumber" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                                    <span class="text-xs font-semibold text-slate-700">WhatsApp নম্বরটি বিকল্প মোবাইল নম্বরে কপি করুন</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Buyer Preferences Section -->
                    <div class="pt-4 border-t border-slate-200 space-y-4">
                        <h4 class="font-extrabold text-slate-900 text-sm m-0">পছন্দের প্রপার্টি সংক্রান্ত তথ্য (Buyer Preferences)</h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">পছন্দের ক্যাটাগরি</label>
                                <select x-model="profileForm.preferredType" class="w-full border rounded-xl px-3 py-2.5 text-xs bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="any">উভয়ই (জমি ও ফ্ল্যাট)</option>
                                    <option value="land">শুধুমাত্র জমি (Land)</option>
                                    <option value="flat">শুধুমাত্র রেডি ফ্ল্যাট (Flat)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">আনুমানিক বাজেট রেঞ্জ</label>
                                <input type="text" x-model="profileForm.budgetRange" placeholder="যেমন: ১ - ১.৫ কোটি" class="w-full border rounded-xl px-3.5 py-2.5 text-xs bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">পছন্দের আফতাবনগর ব্লকসমূহ</label>
                            <input type="text" x-model="profileForm.preferredBlocks" placeholder="যেমন: ব্লক C, E, F, L" class="w-full border rounded-xl px-3.5 py-2.5 text-xs bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">নতুন পাসওয়ার্ড (ঐচ্ছিক - পরিবর্তন করতে চাইলে)</label>
                            <input type="password" x-model="profileForm.password" placeholder="কমপক্ষে ৬ অক্ষর" class="w-full border rounded-xl px-3.5 py-2.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" :disabled="savingProfile"
                                class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
                            <span x-text="savingProfile ? 'সংরক্ষণ করা হচ্ছে... ⏳' : '✓ তথ্য সংরক্ষণ ও আপডেট করুন'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

<!-- ════════════════════════════════════════════════════════════ -->
    <!-- PROPERTY EDIT & CLOUDINARY MEDIA MANAGER MODAL / DRAWER -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div x-show="editModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur" style="display: none;" x-cloak>
        <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-7 space-y-5 shadow-2xl relative max-h-[92vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between border-b pb-4 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-bold">✏️</div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-slate-900 text-lg m-0">প্রপার্টি ও মিডিয়া এডিটর</h3>
                            <span class="font-mono text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200" x-text="editForm.property_code"></span>
                        </div>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">তথ্য পরিবর্তন করুন এবং গ্যালারি থেকে ছবি যোগ/মুছুন</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-800 text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <!-- Loading Spinner -->
            <div x-show="loadingProperty" class="py-16 text-center text-slate-500 font-bold text-sm space-y-2">
                <div class="animate-spin text-3xl">⏳</div>
                <p>প্রপার্টি ও মিডিয়া লোড হচ্ছে...</p>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div x-show="!loadingProperty" class="overflow-y-auto space-y-5 flex-1 pr-1">
                <!-- Navigation Tabs -->
                <div class="flex gap-2 border-b border-slate-100 pb-2">
                    <button type="button" @click="editTab = 'basic'"
                            :class="editTab === 'basic' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
                        📋 সাধারণ তথ্য (Basic)
                    </button>
                    <button type="button" @click="editTab = 'specs'"
                            :class="editTab === 'specs' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
                        📐 স্পেসিফিকেশন (<span x-text="editForm.type === 'land' ? 'জমি' : (editForm.type === 'land_share' ? 'ল্যান্ড শেয়ার' : (editForm.type === 'commercial' ? 'দোকান/কমার্শিয়াল' : 'ফ্ল্যাট'))"></span>)
                    </button>
                    <button type="button" @click="editTab = 'media'"
                            :class="editTab === 'media' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <span>📸 ছবি ও মিডিয়া (<span x-text="mediaList.filter(m => m.media_type === 'photo').length"></span>টি)</span>
                    </button>
                </div>

                <!-- TAB 1: BASIC INFO -->
                <div x-show="editTab === 'basic'" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1">প্রপার্টি টাইটেল *</label>
                        <input type="text" x-model="editForm.title" required class="w-full border rounded-xl px-3.5 py-2.5 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">আস্কিং মোট মূল্য (৳) <span class="text-slate-400 font-normal text-[10px]">(ঐচ্ছিক)</span></label>
                            <input type="number" x-model="editForm.price" class="w-full border rounded-xl px-3.5 py-2.5 font-mono font-bold text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">মূল্য দরকষাকষি যোগ্য?</label>
                            <select x-model="editForm.is_negotiable" class="w-full border rounded-xl px-3.5 py-2.5 font-bold bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option :value="1">হ্যাঁ (Negotiable)</option>
                                <option :value="0">ফিক্সড (Fixed Price)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">মালিকের নাম (ঐচ্ছিক)</label>
                            <input type="text" x-model="editForm.owner_name" placeholder="মূল মালিকের নাম" class="w-full border rounded-xl px-3.5 py-2 font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">মালিকের ফোন (ঐচ্ছিক)</label>
                            <input type="text" x-model="editForm.owner_phone" placeholder="মালিকের যোগাযোগ নম্বর" class="w-full border rounded-xl px-3.5 py-2 font-medium">
                        </div>
                    </div>

                    <div class="flex items-center justify-between bg-slate-100/90 px-3 py-1.5 rounded-xl border border-slate-200 text-[11px] text-slate-700">
                        <span class="font-bold flex items-center gap-1.5">📍 ঠিকানা ও ফ্রন্টএন্ড ভিজিবিলিটি কন্ট্রোল:</span>
                        <span class="text-[10px] text-slate-500">টগল বাটনে ক্লিক করে ফ্রন্টএন্ডে শো/হাইড নিয়ন্ত্রণ করুন (ডিফল্ট: শো)</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-800 uppercase text-[11px] m-0">ব্লক</label>
                                <button type="button" 
                                        @click="editForm.address_visibility.block = !editForm.address_visibility.block"
                                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                        :class="editForm.address_visibility.block ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300'"
                                        :title="editForm.address_visibility.block ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                    <span x-text="editForm.address_visibility.block ? '👁️ শো' : '🔒 হাইড'"></span>
                                </button>
                            </div>
                            <select x-model="editForm.block" class="w-full border rounded-xl px-3 py-2 font-bold bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">-- ব্লক নির্বাচন করুন --</option>
                                @foreach(range('A', 'P') as $b)
                                    <option value="{{ $b }}">ব্লক {{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-800 uppercase text-[11px] m-0">সেক্টর</label>
                                <button type="button" 
                                        @click="editForm.address_visibility.sector = !editForm.address_visibility.sector"
                                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                        :class="editForm.address_visibility.sector ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                        :title="editForm.address_visibility.sector ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                    <span x-text="editForm.address_visibility.sector ? '🔒 হাইড' : '👁️ শো'"></span>
                                </button>
                            </div>
                            <input type="text" x-model="editForm.sector" placeholder="যেমন: 2" class="w-full border rounded-xl px-3 py-2 font-medium">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-800 uppercase text-[11px] m-0">রোড নম্বর</label>
                                <button type="button" 
                                        @click="editForm.address_visibility.road_number = !editForm.address_visibility.road_number"
                                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                        :class="editForm.address_visibility.road_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                        :title="editForm.address_visibility.road_number ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                    <span x-text="editForm.address_visibility.road_number ? '🔒 হাইড' : '👁️ শো'"></span>
                                </button>
                            </div>
                            <input type="text" x-model="editForm.road_number" placeholder="যেমন: 4" class="w-full border rounded-xl px-3 py-2 font-medium">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-800 uppercase text-[11px] m-0">রোড চওড়া</label>
                                <button type="button" 
                                        @click="editForm.address_visibility.road_width_ft = !editForm.address_visibility.road_width_ft"
                                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                        :class="editForm.address_visibility.road_width_ft ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                        :title="editForm.address_visibility.road_width_ft ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                    <span x-text="editForm.address_visibility.road_width_ft ? '🔒 হাইড' : '👁️ শো'"></span>
                                </button>
                            </div>
                            <input type="number" x-model="editForm.road_width_ft" min="10" class="w-full border rounded-xl px-3 py-2 font-medium">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-800 uppercase text-[11px] m-0">প্লট নম্বর</label>
                                <button type="button" 
                                        @click="editForm.address_visibility.plot_number = !editForm.address_visibility.plot_number"
                                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                        :class="editForm.address_visibility.plot_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                        :title="editForm.address_visibility.plot_number ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                    <span x-text="editForm.address_visibility.plot_number ? '🔒 হাইড' : '👁️ শো'"></span>
                                </button>
                            </div>
                            <input type="text" x-model="editForm.plot_number" class="w-full border rounded-xl px-3 py-2 font-bold">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-slate-800 uppercase text-[11px] m-0">হোল্ডিং নম্বর</label>
                                <button type="button" 
                                        @click="editForm.address_visibility.holding_number = !editForm.address_visibility.holding_number"
                                        class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold transition border cursor-pointer select-none"
                                        :class="editForm.address_visibility.holding_number ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200'"
                                        :title="editForm.address_visibility.holding_number ? 'বর্তমানে সবার জন্য দৃশ্যমান/উন্মুক্ত (গোপন করতে ক্লিক করুন)' : 'বর্তমানে গোপন রয়েছে (শো করতে ক্লিক করুন)'">
                                    <span x-text="editForm.address_visibility.holding_number ? '🔒 হাইড' : '👁️ শো'"></span>
                                </button>
                            </div>
                            <input type="text" x-model="editForm.holding_number" class="w-full border rounded-xl px-3 py-2 font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 uppercase mb-1">ফেসিং</label>
                            <select x-model="editForm.facing" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                <option value="">-- দিক নির্বাচন করুন (ঐচ্ছিক) --</option>
                                <option value="south">দক্ষিণমুখী (South)</option>
                                <option value="north">উত্তরমুখী (North)</option>
                                <option value="east">পূর্বমুখী (East)</option>
                                <option value="west">পশ্চিমমুখী (West)</option>
                                <option value="south_east">দক্ষিণ-পূর্ব (South-East)</option>
                                <option value="south_west">দক্ষিণ-পশ্চিম (South-West)</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200 mt-2">
                        <label class="block font-bold text-slate-800 uppercase mb-2">🗺️ গুগল ম্যাপ লিংক</label>
                        <input type="url" x-model="editForm.google_map_pin_url" placeholder="https://maps.app.goo.gl/..." class="w-full border rounded-xl px-3 py-2 font-mono bg-white">
                        <p class="text-[10px] text-slate-500 mt-1">ম্যাপের শেয়ার লিংক দিলে ফ্রন্টএন্ডে ম্যাপ শো করবে।</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1 mt-2">বিস্তারিত বিবরণ (Description)</label>
                        <textarea x-model="editForm.description" rows="4" class="w-full border rounded-xl p-3 text-xs leading-relaxed focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-2xl space-y-2 mt-2">
                        <div class="flex items-center gap-1.5">
                            <span>📱</span>
                            <label class="block font-bold text-slate-800 text-[11px] uppercase m-0">Facebook পোস্টের ক্যাপশন (সোশ্যাল মিডিয়া)</label>
                        </div>
                        <textarea x-model="editForm.fb_caption" rows="3" placeholder="ফেসবুক পেজে পোস্টের জন্য বিশেষ ক্যাপশন থাকলে লিখুন..." class="w-full border border-blue-200 rounded-xl p-2.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 leading-relaxed"></textarea>
                        
                        @if(auth()->user() && auth()->user()->isAdmin())
                        <div class="pt-2 border-t border-blue-200/60 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-[11px] text-blue-950">👑 সেভ করার সাথে সাথে Facebook-এ পোস্ট করুন (Admin Only)</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="editForm.post_to_facebook" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- TAB 2: SPECS (LAND / FLAT) -->
                <div x-show="editTab === 'specs'" class="space-y-4 text-xs">
                    <!-- Land Specs -->
                    <template x-if="editForm.type === 'land'">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">জমির পরিমাণ (কাঠা)</label>
                                    <input type="number" step="0.1" x-model="editForm.katha_size" class="w-full border rounded-xl px-3 py-2 font-bold text-emerald-700">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">জমির ধরন</label>
                                    <select x-model="editForm.land_type" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                        <option value="">-- ধরন নির্বাচন করুন --</option>
                                        <option value="residential">আবাসিক (Residential)</option>
                                        <option value="commercial">বাণিজ্যিক (Commercial)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">মাটি ভরাট অবস্থা</label>
                                    <select x-model="editForm.soil_filling" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                        <option value="">-- স্ট্যাটাস নির্বাচন করুন --</option>
                                        <option value="ready">রেডি টু বিল্ড (Ready)</option>
                                        <option value="semi_filled">আংশিক ভরাট (Semi-filled)</option>
                                        <option value="raw">ভরাট প্রয়োজন (Raw)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.boundary_wall == 1" @change="editForm.boundary_wall = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-emerald-600">
                                    <span>বাউন্ডারি ওয়াল আছে</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.corner_plot == 1" @change="editForm.corner_plot = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-emerald-600">
                                    <span>কর্নার প্লট (Corner Plot)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.lake_facing == 1" @change="editForm.lake_facing = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-emerald-600">
                                    <span>লেক ভিউ / লেক ফেসিং</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <!-- Flat Specs -->
                    <template x-if="editForm.type === 'flat'">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">সাইজ (স্কয়ার ফিট)</label>
                                    <input type="number" x-model="editForm.flat_size_sqft" class="w-full border rounded-xl px-3 py-2 font-bold text-teal-700">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">কত তলা (Floor)</label>
                                    <input type="number" x-model="editForm.floor_number" class="w-full border rounded-xl px-3 py-2 font-medium">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">মোট তলা</label>
                                    <input type="number" x-model="editForm.total_floors" class="w-full border rounded-xl px-3 py-2 font-medium">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">হ্যান্ডওভার স্ট্যাটাস</label>
                                    <select x-model="editForm.handover_status" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                        <option value="">-- স্ট্যাটাস নির্বাচন করুন --</option>
                                        <option value="ready">রেডি ফ্ল্যাট (Ready)</option>
                                        <option value="under_construction">চলমান কাজ (Under Construction)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">বেডরুম সংখ্যা</label>
                                    <input type="number" x-model="editForm.bedrooms" class="w-full border rounded-xl px-3 py-2 font-medium">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">বাথরুম সংখ্যা</label>
                                    <input type="number" x-model="editForm.bathrooms" class="w-full border rounded-xl px-3 py-2 font-medium">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">বারান্দা সংখ্যা</label>
                                    <input type="number" x-model="editForm.balconies" class="w-full border rounded-xl px-3 py-2 font-medium">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">ডাউন পেমেন্ট (৳)</label>
                                    <input type="number" x-model="editForm.down_payment" placeholder="যেমন: 500000" class="w-full border rounded-xl px-3 py-2 font-bold text-emerald-700">
                                </div>
                            </div>

                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_parking == 1" @change="editForm.has_parking = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-teal-600">
                                    <span>গাড়ি পার্কিং সুবিধা অন্তর্ভুক্ত</span>
                                </label>
                            </div>
                        </div>
                    </template>
                    <!-- Land Share Specs -->
                    <template x-if="editForm.type === 'land_share'">
                        <div class="space-y-4">
                            <div class="p-3 bg-purple-50/60 rounded-2xl border border-purple-200 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">প্রকল্পের নাম</label>
                                    <input type="text" x-model="editForm.project_name" placeholder="যেমন: মায়া গার্ডেনিয়া" class="w-full border rounded-xl px-3 py-2 font-bold text-purple-900 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">ডেভেলপার কোম্পানি</label>
                                    <input type="text" x-model="editForm.developer_name" placeholder="যেমন: মায়া এসেটস অ্যান্ড ইনভেস্টমেন্ট" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">জমির সাইজ (কাঠা)</label>
                                    <input type="number" step="0.01" x-model="editForm.land_size_katha" class="w-full border rounded-xl px-3 py-2 font-bold text-purple-800 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">ভবনের প্ল্যান / তলা</label>
                                    <input type="text" x-model="editForm.building_stories" placeholder="যেমন: G+8" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">সম্ভাব্য ফ্ল্যাট সাইজ (sqft)</label>
                                    <input type="number" x-model.number="editForm.flat_size_sqft" class="w-full border rounded-xl px-3 py-2 font-bold text-purple-900 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">প্রতি ফ্লোরে ইউনিট</label>
                                    <input type="text" x-model="editForm.units_per_floor" placeholder="যেমন: 1" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">বেডরুম</label>
                                    <input type="text" x-model="editForm.bedrooms" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">বাথরুম</label>
                                    <input type="text" x-model="editForm.bathrooms" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">বারান্দা</label>
                                    <input type="text" x-model="editForm.balconies" class="w-full border rounded-xl px-3 py-2 font-bold bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">মোট শেয়ার</label>
                                    <input type="number" x-model.number="editForm.total_shares" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-purple-800 uppercase mb-1">অবশিষ্ট শেয়ার</label>
                                    <input type="number" x-model.number="editForm.available_shares" class="w-full border border-purple-300 rounded-xl px-3 py-2 font-bold text-purple-700 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">কাজের বর্তমান অগ্রগতি</label>
                                    <input type="text" x-model="editForm.current_progress" placeholder="যেমন: পাইলিং সম্পন্ন" class="w-full border rounded-xl px-3 py-2 font-bold text-purple-900 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">নির্মাণ শুরুর সময়</label>
                                    <input type="text" x-model="editForm.construction_start_date" placeholder="যেমন: ডিসেম্বর ২০২৪" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">নির্মাণ সময়কাল</label>
                                    <input type="text" x-model="editForm.construction_duration" placeholder="যেমন: ২৪ মাস" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                            </div>

                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-2 sm:grid-cols-6 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_parking == 1" @change="editForm.has_parking = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-purple-600">
                                    <span>১টি পার্কিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_drawing_dining == 1" @change="editForm.has_drawing_dining = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-purple-600">
                                    <span>ড্রয়িং ও ডাইনিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_family_living == 1" @change="editForm.has_family_living = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-purple-600">
                                    <span>ফ্যামিলি লিভিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_lift == 1" @change="editForm.has_lift = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-purple-600">
                                    <span>লিফট (Lift)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_generator == 1" @change="editForm.has_generator = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-purple-600">
                                    <span>জেনারেটর</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_substation == 1" @change="editForm.has_substation = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-purple-600">
                                    <span>সাবস্টেশন</span>
                                </label>
                            </div>

                            <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-200">
                                <label class="block font-bold text-purple-950 uppercase mb-1">📍 লোকেশনের বিশেষত্ব (Location Highlights)</label>
                                <textarea x-model="editForm.location_highlights" rows="2" placeholder="যেমন: লেক ও খেলার মাঠ সংলগ্ন..." class="w-full border rounded-xl p-2.5 text-xs bg-white"></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- Commercial Specs -->
                    <template x-if="editForm.type === 'commercial'">
                        <div class="space-y-4">
                            <div class="p-3 bg-indigo-50/60 rounded-2xl border border-indigo-200 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">স্পেসের ধরন (Type)</label>
                                    <select x-model="editForm.commercial_type" class="w-full border rounded-xl px-3 py-2 font-bold text-slate-800 bg-white">
                                        <option value="shop">দোকান (Shop)</option>
                                        <option value="office">অফিস স্পেস (Office Space)</option>
                                        <option value="showroom">শোরুম (Showroom)</option>
                                        <option value="open_floor">ওপেন ফ্লোর (Open Floor)</option>
                                        <option value="restaurant">রেস্তোরাঁ / ফুড কোর্ট (Restaurant)</option>
                                        <option value="warehouse">গুদাম / ওয়্যারহাউস (Warehouse)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">মার্কেট / প্লাজার নাম</label>
                                    <input type="text" x-model="editForm.market_name" placeholder="যেমন: ইম্পেরিয়াল প্লাজা" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">দোকান / স্পেস নম্বর</label>
                                    <input type="text" x-model="editForm.shop_number" placeholder="যেমন: শপ # ১০৪" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">সাইজ (বর্গফুট / sqft)</label>
                                    <input type="number" step="any" x-model="editForm.size_sqft" class="w-full border rounded-xl px-3 py-2 font-bold text-indigo-900 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">প্রতি বর্গফুট দাম (টাকা)</label>
                                    <input type="number" step="any" x-model="editForm.price_per_sqft" class="w-full border rounded-xl px-3 py-2 font-bold text-emerald-700 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">ফ্লোর নম্বর</label>
                                    <input type="number" x-model="editForm.floor_number" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">ভবনের মোট ফ্লোর</label>
                                    <input type="number" x-model="editForm.total_floors" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">ফ্রন্টেজ (ফুট)</label>
                                    <input type="number" step="any" x-model="editForm.frontage_ft" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">সিলিং উচ্চতা (ফুট)</label>
                                    <input type="number" step="any" x-model="editForm.ceiling_height_ft" class="w-full border rounded-xl px-3 py-2 bg-white">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">প্রকল্পের অবস্থা</label>
                                    <select x-model="editForm.completion_status" class="w-full border rounded-xl px-3 py-2 bg-white">
                                        <option value="ready">১০০% রেডি</option>
                                        <option value="under_construction">নির্মাণাধীন</option>
                                        <option value="upcoming">আসন্ন</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">মাসিক ভাড়া আয় (টাকা)</label>
                                    <input type="number" step="any" x-model="editForm.monthly_rent_yield" class="w-full border rounded-xl px-3 py-2 bg-white font-bold text-purple-700">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-800 uppercase mb-1">ওয়াশরুম ব্যবস্থা</label>
                                    <select x-model="editForm.washroom_type" class="w-full border rounded-xl px-3 py-2 bg-white">
                                        <option value="common">কমন ওয়াশরুম</option>
                                        <option value="attached">অ্যাটাচড ওয়াশরুম</option>
                                        <option value="none">নেই</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_lift == 1" @change="editForm.has_lift = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>লিফট (Lift)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_escalator == 1" @change="editForm.has_escalator = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>এস্কেলেটর</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_generator == 1" @change="editForm.has_generator = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>জেনারেটর</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_parking == 1" @change="editForm.has_parking = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>কার পার্কিং</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_central_ac == 1" @change="editForm.has_central_ac = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>সেন্ট্রাল এসি</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_cctv == 1" @change="editForm.has_cctv = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>CCTV নজরদারি</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.has_fire_safety == 1" @change="editForm.has_fire_safety = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>ফায়ার সেফটি</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.is_corner == 1" @change="editForm.is_corner = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>কর্নার স্পেস</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                    <input type="checkbox" :checked="editForm.is_main_road_facing == 1" @change="editForm.is_main_road_facing = $event.target.checked ? 1 : 0" class="w-4 h-4 rounded text-indigo-600">
                                    <span>মেইন রোড ফেসিং</span>
                                </label>
                            </div>
                        </div>
                    </template>


                    <!-- অন্যান্য বৈশিষ্ট্য (Other Features - Repeatable Field) -->
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-extrabold text-slate-800 text-xs uppercase m-0 flex items-center gap-1.5">
                                <span>✨</span> অন্যান্য বৈশিষ্ট্য ও সুযোগ-সুবিধা (Other Features)
                            </h4>
                            <button type="button" @click="addOtherFeature()"
                                class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>+</span> নতুন বৈশিষ্ট্য যোগ করুন
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 m-0">প্রপার্টির বিশেষ বৈশিষ্ট্যগুলো টিক দিন বা প্রয়োজনমতো নিজের লেবেল লিখে নতুন বৈশিষ্ট্য যোগ করুন।</p>

                        <div class="space-y-2">
                            <template x-for="(feat, idx) in editForm.other_features" :key="idx">
                                <div class="flex items-center gap-2.5 bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs hover:border-emerald-300 transition">
                                    <input type="checkbox" x-model="feat.checked" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                    <input type="text" x-model="feat.label" placeholder="বৈশিষ্ট্যের নাম লিখুন (যেমন: ওয়াইড ড্রেনেজ, বিদ্যুৎ সংযোগ...)"
                                        class="flex-1 border-0 bg-transparent text-xs text-slate-800 focus:ring-0 focus:outline-none font-medium">
                                    <button type="button" @click="removeOtherFeature(idx)" title="মুছে ফেলুন"
                                        class="w-6 h-6 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold transition cursor-pointer">
                                        ✕
                                    </button>
                                </div>
                            </template>
                            <div x-show="!editForm.other_features || editForm.other_features.length === 0"
                                class="text-center py-3 text-slate-400 text-xs border border-dashed rounded-xl bg-white">
                                কোনো অতিরিক্ত বৈশিষ্ট্য যুক্ত করা হয়নি। "+ নতুন বৈশিষ্ট্য যোগ করুন" বাটনে ক্লিক করুন।
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: MEDIA & CLOUDINARY GALLERY -->
                <div x-show="editTab === 'media'" class="space-y-5 text-xs">
                    <!-- Existing Media Grid -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="font-extrabold text-slate-800 uppercase">বিদ্যমান ছবিসমূহ (WebP Gallery):</label>
                            <span class="text-[11px] text-slate-500 font-medium">ক্লিক করে কভার ইমেজ বা ডিলিট করুন</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <template x-for="m in mediaList.filter(item => item.media_type === 'photo')" :key="m.id">
                                <div class="bg-slate-100 rounded-2xl border border-slate-200 overflow-hidden relative group">
                                    <img :src="m.file_path" alt="Property image" class="w-full h-32 object-cover">
                                    
                                    <!-- Cover Badge -->
                                    <template x-if="m.is_cover == 1">
                                        <div class="absolute top-2 left-2 bg-emerald-600 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-md shadow-md">
                                            ★ কভার ইমেজ
                                        </div>
                                    </template>

                                    <!-- Action Buttons Overlay -->
                                    <div class="p-2 bg-white flex items-center justify-between gap-1 border-t border-slate-200">
                                        <button type="button"
                                                x-show="m.is_cover != 1"
                                                @click="setCoverMedia(m.id)"
                                                class="px-2 py-1 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-[10px] font-bold rounded-lg transition border border-slate-200 cursor-pointer">
                                            কভার বানান
                                        </button>
                                        <button type="button"
                                                @click="deleteMedia(m.id)"
                                                class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 text-[10px] font-bold rounded-lg transition border border-rose-200 ml-auto cursor-pointer">
                                            🗑️ ডিলিট
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="mediaList.filter(item => item.media_type === 'photo').length === 0">
                                <div class="col-span-full py-8 text-center text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                    এখনো কোনো ছবি আপলোড করা হয়নি। নিচে নতুন ছবি যোগ করুন।
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Upload New Images Dropzone -->
                    <div class="p-5 bg-emerald-50/60 rounded-2xl border-2 border-dashed border-emerald-300 text-center space-y-2">
                        <div class="text-2xl">☁️</div>
                        <div class="font-bold text-slate-800 text-xs">নতুন ছবি আপলোড করুন (স্বয়ংক্রিয় WebP অপ্টিমাইজেশন)</div>
                        <p class="text-[11px] text-slate-500 m-0">একাধিক ছবি একসাথে নির্বাচন করতে পারেন</p>
                        
                        <div class="pt-2">
                            <label class="cursor-pointer px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                <span x-show="!uploadingMedia">📁 ছবি ফাইল সিলেক্ট করুন</span>
                                <span x-show="uploadingMedia">আপলোড ও অপটিমাইজ হচ্ছে... ⏳</span>
                                <input type="file" multiple accept="image/*" @change="uploadNewMedia($event)" :disabled="uploadingMedia" class="hidden">
                            </label>
                        </div>
                    </div>

                    <!-- YouTube Video Embed -->
                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1">YouTube ভিডিও লিংক (ঐচ্ছিক)</label>
                        <input type="url" x-model="editForm.youtube_video_url" placeholder="https://www.youtube.com/watch?v=..." class="w-full border rounded-xl px-3.5 py-2.5 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Footer Action Bar -->
            <div class="border-t pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                <div>
                    <span x-show="editMsg" :class="editSuccess ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200'" class="px-3.5 py-1.5 rounded-xl border text-xs font-bold inline-block" x-text="editMsg" style="display:none;"></span>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" @click="editModalOpen = false" class="flex-1 sm:flex-initial px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                        বাতিল
                    </button>
                    <button type="button" @click="saveProperty()" :disabled="savingProperty" class="flex-1 sm:flex-initial px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer disabled:cursor-not-allowed">
                        <template x-if="savingProperty">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>সেভ হচ্ছে...</span>
                            </span>
                        </template>
                        <template x-if="!savingProperty">
                            <span>💾 পরিবর্তন সংরক্ষণ করুন</span>
                        </template>
                    </button>
                </div>
            </div>
        </div>

</div>
@endsection
