<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Site Identity, Dynamic Branding, SEO & AEO Meta Tags -->
    @php
        $siteName = \App\Services\SeoService::getSiteName();
        $siteTagline = \App\Models\Setting::get('adm_site_tagline', 'সরাসরি প্রপার্টি মার্কেটপ্লেস');
        $siteLogo = \App\Models\Setting::get('adm_site_logo', '');
        $siteLogoUrl = $siteLogo ? (str_starts_with($siteLogo, 'http') ? $siteLogo : asset($siteLogo)) : null;
        $siteFavicon = \App\Models\Setting::get('adm_site_favicon', '');
        $siteFaviconUrl = $siteFavicon ? (str_starts_with($siteFavicon, 'http') ? $siteFavicon : asset($siteFavicon)) : asset('assets/icons/icon-192x192.png');
        $siteEmail = \App\Models\Setting::get('adm_site_email', 'support@propertykoi.com');
        $sitePhone = \App\Models\Setting::get('adm_site_phone', '');
        $footerLogo = \App\Models\Setting::get('adm_site_logo_dark', '') ?: $siteLogo;
        $footerLogoUrl = $footerLogo ? (str_starts_with($footerLogo, 'http') ? $footerLogo : asset($footerLogo)) : null;
        $footerAbout = \App\Models\Setting::get('adm_site_footer_about', 'PropertyKoi.com – Your Trusted Property Classifieds Website in Bangladesh. Discover the Best Opportunities to Buy, Sell, or Rent Real Estate, Apartments, and Commercial Properties with Ease and Confidence.');
        $footerCopyright = \App\Models\Setting::get('adm_site_copyright', '© ' . date('Y') . ' ' . $siteName . '. সর্বস্বত্ব সংরক্ষিত (All rights reserved).');

        $defaultSeo = \App\Services\SeoService::getOgMeta(request()->segment(1) ?: 'home');
        $seoData = array_merge($defaultSeo, is_array($seo ?? null) ? $seo : []);

        $seoTitle = $seoData['title'] ?? ($siteName . ' — ' . $siteTagline);
        $seoDesc = $seoData['description'] ?? 'ঢাকা ও আফতাবনগরের সরাসরি জমি, ফ্ল্যাট ও কমার্শিয়াল প্রপার্টি মার্কেটপ্লেস। কোনো ব্রোকার ছাড়া সরাসরি মালিকের সাথে কথা বলুন।';
        $seoKeywords = $seoData['keywords'] ?? 'আফতাবনগর জমি বিক্রয়, আফতাবনগর ফ্ল্যাট, Aftabnagar land for sale, Aftabnagar flat price, রেডি ফ্ল্যাট ঢাকা, কমার্শিয়াল দোকান আফতাবনগর, ল্যান্ড শেয়ার প্রজেক্ট, PropertyKoi, প্রপার্টি কই';
        $seoImage = \App\Services\SeoService::formatOgImageUrl($seoData['image'] ?? null, 'og-default.jpg');
        $seoImages = !empty($seoData['images']) && is_array($seoData['images'])
            ? array_map(fn($img) => \App\Services\SeoService::formatOgImageUrl($img, 'og-default.jpg'), $seoData['images'])
            : [$seoImage];
        $seoImageAlt = $seoData['image_alt'] ?? $seoTitle;
        $seoUrl = $seoData['url'] ?? url()->current();
        $seoType = $seoData['type'] ?? 'website';
        $seoRobots = $seoData['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        $seoSchema = $seoData['schema'] ?? null;
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="author" content="{{ $siteName }}">
    <link rel="canonical" href="{{ $seoUrl }}">

    <!-- Local & Geo SEO Meta Tags (Dhaka / Aftabnagar) -->
    <meta name="geo.region" content="BD-13">
    <meta name="geo.placename" content="Aftabnagar, Dhaka, Bangladesh">
    <meta name="geo.position" content="23.7650;90.4350">
    <meta name="ICBM" content="23.7650, 90.4350">

    <!-- Open Graph (Facebook, WhatsApp, Telegram, LinkedIn) -->
    <meta property="og:locale" content="bn_BD">
    <meta property="og:locale:alternate" content="en_US">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    @foreach(array_slice($seoImages, 0, 4) as $ogImgUrl)
        <meta property="og:image" content="{{ $ogImgUrl }}">
        <meta property="og:image:secure_url" content="{{ $ogImgUrl }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="{{ str_ends_with(strtolower($ogImgUrl), '.png') ? 'image/png' : (str_ends_with(strtolower($ogImgUrl), '.webp') ? 'image/webp' : 'image/jpeg') }}">
        <meta property="og:image:alt" content="{{ $seoImageAlt }}">
    @endforeach

    <!-- Twitter / X Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $seoImageAlt }}">

    <!-- PWA Manifest & Mobile Icons (Dynamic Favicon) -->
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#059669">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="{{ $siteFaviconUrl }}">
    <link rel="icon" type="image/x-icon" href="{{ $siteFaviconUrl }}">
    <link rel="shortcut icon" href="{{ $siteFaviconUrl }}">

    <!-- Schema.org JSON-LD (SEO & AEO Answer Engine Optimization) -->
    @if(!empty($seoSchema))
        <script type="application/ld+json">
            {!! json_encode($seoSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endif

    <!-- Microsoft Clarity Session Replay, Heatmaps & Real Estate Analytics -->
    @if(\App\Services\ClarityService::shouldRender())
        @php
            $clarityId = \App\Services\ClarityService::getProjectId();
            $clarityTrackRoles = \App\Services\ClarityService::shouldTrackRoles();
            $clarityUser = auth()->user();
        @endphp
        @if(!empty($clarityId))
            <script type="text/javascript">
                (function(c,l,a,r,i,t,y){
                    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
                })(window, document, "clarity", "script", "{{ $clarityId }}");

                @if($clarityTrackRoles && $clarityUser)
                    window.clarity("identify", "{{ $clarityUser->id }}", null, null, "{{ addslashes($clarityUser->name ?? '') }}");
                    window.clarity("set", "role", "{{ $clarityUser->role ?? 'buyer' }}");
                    window.clarity("set", "phone_verified", "{{ $clarityUser->phone_verified_at ? 'yes' : 'no' }}");
                @else
                    window.clarity("set", "role", "guest");
                @endif

                // Global Helper for tracking real estate conversions
                window.trackClarityEvent = function(eventName, meta) {
                    try {
                        if (window.clarity) {
                            window.clarity("event", eventName);
                            if (meta && typeof meta === 'object') {
                                for (var k in meta) {
                                    if (Object.prototype.hasOwnProperty.call(meta, k)) {
                                        window.clarity("set", k, String(meta[k]));
                                    }
                                }
                            }
                        }
                    } catch (e) {
                        console.debug("Clarity event:", eventName, e);
                    }
                };

                // Hotwire Turbo Lifecycle: Notify Clarity on SPA page transitions
                document.addEventListener('turbo:load', function() {
                    if (window.clarity) {
                        window.clarity("set", "page_path", window.location.pathname);
                        window.clarity("event", "page_view");
                    }
                });

                // Auto-Capture Real Estate Conversion Click Listeners
                document.addEventListener('click', function(e) {
                    var target = e.target.closest('a, button');
                    if (!target) return;

                    var href = target.getAttribute('href') || '';

                    // 1. WhatsApp Clicks
                    if (href.indexOf('wa.me') !== -1 || href.indexOf('api.whatsapp.com') !== -1 || target.matches('[data-clarity-event="whatsapp_click"]')) {
                        window.trackClarityEvent('whatsapp_click', {
                            target: 'whatsapp_contact',
                            path: window.location.pathname
                        });
                    }

                    // 2. Direct Phone Call Clicks
                    if (href.indexOf('tel:') === 0 || target.matches('[data-clarity-event="call_click"]')) {
                        window.trackClarityEvent('phone_call_click', {
                            target: 'phone_call',
                            path: window.location.pathname
                        });
                    }

                    // 3. Site Visit Booking Form Trigger
                    if (target.matches('[data-clarity-event="site_visit_click"]')) {
                        window.trackClarityEvent('site_visit_click', {
                            path: window.location.pathname
                        });
                    }
                }, true);
            </script>
        @endif
    @endif

    <!-- Vite Assets (Tailwind CSS, Turbo, Alpine.js) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex flex-col pb-16 md:pb-0"
      x-data="{ mobileMenuOpen: false }">

    @if(\App\Services\AccessControlService::shouldShowAdminBar(Auth::user()))
        <!-- WP-Style Top Admin Toolbar Bar (Controlled via Settings Tab 5) -->
        <div class="bg-slate-900 text-slate-200 text-xs py-1.5 px-4 border-b border-slate-800 z-50">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-emerald-400 font-extrabold flex items-center gap-1">
                        <span>🛡️</span>
                        <span>{{ config('app.name', 'PropertyKoi') }} Admin Bar</span>
                    </span>
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-300 transition flex items-center gap-1">
                        <span>📊</span> অ্যাডমিন
                    </a>
                    <a href="{{ route('admin.properties.index') }}" class="hidden sm:inline-flex hover:text-emerald-300 transition items-center gap-1">
                        <span>🏢</span> প্রপার্টিজ
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="hidden md:inline-flex hover:text-emerald-300 transition items-center gap-1">
                        <span>⚙️</span> সেটিংস
                    </a>
                </div>
                <div class="flex items-center gap-3 text-[11px]">
                    <span class="text-slate-400">স্বাগতম, <strong class="text-white">{{ Auth::user()->name }}</strong> ({{ Auth::user()->role }})</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline m-0 p-0">
                        @csrf
                        <button type="submit" class="text-rose-400 hover:text-rose-300 cursor-pointer">লগআউট</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Top Navigation Header -->
    <header id="site-header" class="site-header sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">

                <!-- Left / Mobile Hamburger -->
                <div class="flex items-center md:hidden">
                    <button type="button"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 hover:text-emerald-600 focus:outline-hidden"
                            aria-label="Toggle menu">
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Brand Logo (Centered on mobile, Left on desktop) -->
                <div class="site-branding flex items-center justify-center flex-1 md:flex-initial py-1">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 group text-decoration-none">
                        @if($siteLogoUrl)
                            <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="h-10 sm:h-12 md:h-14 lg:h-16 w-auto max-w-[240px] md:max-w-[280px] lg:max-w-[320px] object-contain group-hover:scale-105 transition-transform">
                        @else
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-gradient-to-tr from-emerald-600 via-teal-600 to-emerald-500 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <div class="text-left">
                                <span class="text-xl md:text-2xl font-black text-slate-900 tracking-tight leading-none group-hover:text-emerald-600 transition-colors">{{ $siteName }}</span>
                                <span class="text-[10px] md:text-[11px] text-emerald-600 font-bold tracking-wider block">{{ $siteTagline }}</span>
                            </div>
                        @endif
                    </a>
                </div>

                <!-- Desktop Navigation Menu -->
                <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                    <a href="{{ route('home') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('home') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        হোম
                    </a>
                    <a href="{{ route('properties.land') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('properties.land') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        জমি ও প্লট
                    </a>
                    <a href="{{ route('properties.flat') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('properties.flat') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        ফ্ল্যাট
                    </a>
                    <a href="{{ route('properties.share') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('properties.share') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        ল্যান্ড শেয়ার
                    </a>
                    <a href="{{ route('properties.commercial') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('properties.commercial') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        দোকান / স্পেস
                    </a>
                    <a href="{{ route('map.view') }}"
                       class="px-3 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('map.view') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        ম্যাপ ভিউ
                    </a>
                    <a href="{{ route('shortlist') }}"
                       x-data="{
                           count: 0,
                           init() {
                               const update = () => {
                                   try {
                                       const a = JSON.parse(localStorage.getItem('aftabnagar_shortlist') || '[]');
                                       const b = JSON.parse(localStorage.getItem('propertykoi_shortlist') || '[]');
                                       this.count = Array.from(new Set([...(Array.isArray(a)?a:[]), ...(Array.isArray(b)?b:[])].map(Number).filter(Boolean))).length;
                                   } catch(e) { this.count = 0; }
                               };
                               update();
                               window.addEventListener('shortlist-updated', update);
                               window.addEventListener('storage', (e) => {
                                   if (e.key === 'aftabnagar_shortlist' || e.key === 'propertykoi_shortlist') update();
                               });
                           }
                       }"
                       class="px-3 py-2 rounded-xl text-sm font-semibold transition-all inline-flex items-center gap-1.5 {{ request()->routeIs('shortlist') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        <span>শর্টলিস্ট</span>
                        <span x-show="count > 0" x-text="count" class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black leading-none shadow-xs"></span>
                    </a>
                    <a href="{{ route('chahida.index') }}"
                       class="px-3 py-2 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('chahida.*') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-100' }}">
                        চাহিদা দিন
                    </a>

                    @auth
                        <!-- Direct Buyer Dashboard Quick Access Button -->
                        <a href="{{ route('buyer.dashboard') }}"
                           class="px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ request()->routeIs('buyer.dashboard*') ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}"
                           title="আপনার চাহিদা, ফেভারিট ও অ্যাপয়েন্টমেন্ট দেখতে বায়ার ড্যাশবোর্ডে যান">
                            <span>🛒</span>
                            <span>বায়ার ড্যাশবোর্ড</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:text-emerald-600 transition">
                            লগইন
                        </a>
                        <a href="{{ route('register') }}" class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-800 hover:bg-slate-200 transition">
                            রেজিস্ট্রেশন
                        </a>
                    @endauth

                    <a href="{{ route('property.create') }}"
                       class="ml-1 px-4 py-2 rounded-xl text-sm font-bold bg-emerald-600 text-white shadow-md shadow-emerald-600/25 hover:bg-emerald-700 hover:shadow-lg transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>বিজ্ঞাপন দিন</span>
                    </a>
                </nav>

                <!-- Mobile Right Action (Post ad button on mobile topbar) -->
                <div class="flex items-center md:hidden">
                    <a href="{{ route('property.create') }}"
                       class="p-2 rounded-xl bg-emerald-600 text-white shadow-xs"
                       title="বিজ্ঞাপন দিন">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </a>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             @click.away="mobileMenuOpen = false"
             class="md:hidden bg-white border-t border-slate-100 px-4 pt-3 pb-6 shadow-xl space-y-2">
            <a href="{{ route('home') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('home') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-800 bg-slate-50' }}">
                হোম
            </a>
            <a href="{{ route('properties.land') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('properties.land') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-800 bg-slate-50' }}">
                জমি ও প্লট খুঁজুন
            </a>
            <a href="{{ route('properties.flat') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('properties.flat') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-800 bg-slate-50' }}">
                ফ্ল্যাট ও অ্যাপার্টমেন্ট
            </a>
            <a href="{{ route('properties.share') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('properties.share') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-800 bg-slate-50' }}">
                ল্যান্ড শেয়ার প্রকল্প
            </a>
            <a href="{{ route('properties.commercial') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('properties.commercial') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-800 bg-slate-50' }}">
                দোকান ও কমার্শিয়াল
            </a>
            <a href="{{ route('shortlist') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('shortlist') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-800 bg-slate-50' }}">
                ❤️ শর্টলিস্ট করা প্রপার্টি
            </a>
            <a href="{{ route('chahida.index') }}" class="block px-4 py-3 rounded-xl text-base font-semibold {{ request()->routeIs('chahida.*') ? 'bg-emerald-50 text-emerald-600 font-bold' : 'text-slate-800 bg-slate-50' }}">
                আপনার চাহিদা জমা দিন
            </a>
            @auth
                <div class="p-3 bg-slate-100 rounded-2xl space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <span class="font-bold text-slate-900 text-xs truncate">{{ auth()->user()->name }}</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ auth()->user()->isAdmin() ? 'bg-purple-200 text-purple-900' : 'bg-emerald-200 text-emerald-900' }}">
                            {{ auth()->user()->isAdmin() ? '👑 অ্যাডমিন' : '🛒 ক্রেতা' }}
                        </span>
                    </div>

                    <a href="{{ route('buyer.dashboard') }}" class="block px-4 py-3 rounded-xl text-sm font-bold bg-emerald-600 text-white text-center shadow-xs">
                        🛒 বায়ার ড্যাশবোর্ড (Buyer Dashboard)
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-900 text-white text-center shadow-xs">
                            👑 অ্যাডমিন মাস্টার প্যানেল
                        </a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST" class="m-0 pt-1">
                        @csrf
                        <button type="submit" class="w-full text-center py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer">
                            লগআউট করুন
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="block px-4 py-3 rounded-xl text-base font-semibold text-slate-800 bg-slate-50">
                    লগইন / নতুন রেজিস্ট্রেশন
                </a>
            @endauth
            <a href="{{ route('property.create') }}" class="block px-4 py-3.5 rounded-xl text-base font-bold bg-emerald-600 text-white text-center shadow-md">
                ফ্রি প্রপার্টি বিজ্ঞাপন দিন
            </a>
        </div>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error') || (isset($errors) && $errors->any()))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-sm font-medium">
                    @if(session('error'))
                        <div>{{ session('error') }}</div>
                    @endif
                    @if(isset($errors) && $errors->any())
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content Body -->
    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <!-- Floating Compare Action Button -->
    @if(!request()->routeIs('compare.*'))
    <div x-data="{
        count: 0,
        compareUrl: '{{ route('compare.index') }}',
        updateState() {
            var list = [];
            try {
                list = JSON.parse(localStorage.getItem('aftabnagar_compare') || '[]').map(Number).filter(Boolean);
            } catch(e) {}
            this.count = list.length;
            this.compareUrl = list.length > 0
                ? '{{ route('compare.index') }}?ids=' + list.join(',')
                : '{{ route('compare.index') }}';
        },
        init() {
            this.updateState();
            window.addEventListener('compare-updated', () => this.updateState());
            document.addEventListener('turbo:load', () => this.updateState());
        },
        openCompare(e) {
            this.updateState();
            if (this.count === 0) {
                if (e) e.preventDefault();
                alert('তুলনা করার জন্য অন্তত ১টি প্রপার্টি সিলেক্ট করুন');
                return;
            }
            if (window.Turbo) {
                if (e) e.preventDefault();
                window.Turbo.visit(this.compareUrl);
            }
        }
    }"
    x-show="count > 0"
    x-transition
    class="fixed bottom-20 md:bottom-8 left-5 z-40">
        <a :href="compareUrl"
           @click="openCompare($event)"
           class="flex items-center gap-2.5 px-4 py-3 rounded-full bg-slate-900 text-white font-bold text-sm shadow-2xl hover:bg-emerald-600 transition-all border-2 border-emerald-400 no-underline cursor-pointer">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>তুলনা করুন</span>
            <span class="px-2 py-0.5 rounded-full bg-emerald-500 text-white text-xs font-black" x-text="count"></span>
        </a>
    </div>
    @endif

    <!-- WhatsApp Floating Live Chat Widget -->
    <x-whatsapp-live-chat :property="$property ?? null" />

    <!-- Mobile-First Fixed Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 md:hidden py-1.5 px-2">
        <div class="grid grid-cols-5 gap-1 items-center text-center">
            <a href="{{ route('home') }}"
               class="flex flex-col items-center justify-center py-1 rounded-xl text-xs font-semibold {{ request()->routeIs('home') ? 'text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>হোম</span>
            </a>
            <a href="{{ route('properties.land') }}"
               class="flex flex-col items-center justify-center py-1 rounded-xl text-xs font-semibold {{ request()->routeIs('properties.land') ? 'text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span>জমি</span>
            </a>
            <a href="{{ route('properties.flat') }}"
               class="flex flex-col items-center justify-center py-1 rounded-xl text-xs font-semibold {{ request()->routeIs('properties.flat') ? 'text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>ফ্ল্যাট</span>
            </a>
            <a href="{{ route('chahida.index') }}"
               class="flex flex-col items-center justify-center py-1 rounded-xl text-xs font-semibold {{ request()->routeIs('chahida.*') ? 'text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>চাহিদা</span>
            </a>
            <a href="{{ route('property.create') }}"
               class="flex flex-col items-center justify-center py-1 rounded-xl text-xs font-semibold text-emerald-600 font-bold">
                <div class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center mb-0.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <span>পোস্ট</span>
            </a>
        </div>
    </nav>

    <!-- Footer Component (100% replica of WordPress footer) -->
    <footer id="site-footer" class="site-footer bg-[#050508] text-slate-300 font-sans border-t border-slate-800/80 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-12 w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-800/80">
                <!-- Col 1 -->
                <div class="lg:col-span-4 space-y-5">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3.5 group text-decoration-none">
                        @if($footerLogoUrl)
                            <img src="{{ $footerLogoUrl }}" alt="{{ $siteName }}" class="h-10 md:h-12 w-auto max-w-[220px] object-contain">
                        @else
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-600 to-emerald-400 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <div>
                                <span class="text-2xl font-black text-white tracking-tight block leading-tight">{{ $siteName }}</span>
                                <span class="text-[11px] text-emerald-400 font-semibold tracking-wider block">{{ $siteTagline }}</span>
                            </div>
                        @endif
                    </a>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed m-0">
                        {{ $footerAbout }}
                    </p>
                </div>

                <!-- Col 2 -->
                <div class="lg:col-span-3 space-y-4">
                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-white m-0 pb-1">
                        INFORMATION / প্রয়োজনীয় লিংক
                    </h4>
                    <ul class="space-y-2.5 text-xs sm:text-sm list-none p-0 m-0">
                        <li><a href="{{ route('home') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>হোম (Home)</span></a></li>
                        <li><a href="{{ route('map.view') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>ইন্টারেক্টিভ ম্যাপ ভিউ (Map)</span></a></li>
                        <li><a href="{{ route('properties.land') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>জমি ও প্লট খুঁজুন (Land)</span></a></li>
                        <li><a href="{{ route('properties.flat') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>ফ্ল্যাট ও অ্যাপার্টমেন্ট (Flat)</span></a></li>
                        <li><a href="{{ route('chahida.index') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>ক্রেতা চাহিদা জমা দিন (Chahida)</span></a></li>
                        <li><a href="{{ route('buyer.dashboard') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>ক্রেতা ড্যাশবোর্ড (Buyer Dashboard)</span></a></li>
                        <li><a href="{{ route('shortlist') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>শর্টলিস্ট প্রপার্টি (Shortlist)</span></a></li>
                        <li><a href="{{ route('property.create') }}" class="text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-2 group"><span class="text-emerald-500 text-xs transition-transform group-hover:translate-x-1">&rsaquo;</span><span>প্রপার্টি পোস্ট করুন (Post Ad)</span></a></li>
                    </ul>
                </div>

                <!-- Col 3 -->
                <div class="lg:col-span-2 space-y-4">
                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-white m-0 pb-1">
                        LEGAL AND POLICY
                    </h4>
                    <ul class="space-y-2.5 text-xs sm:text-sm list-none p-0 m-0">
                        <li><a href="#" class="text-slate-400 hover:text-emerald-400 transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-emerald-400 transition-colors">Terms of Use</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-emerald-400 transition-colors">Cookie Policy</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-emerald-400 transition-colors">Listing Policy</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-emerald-400 transition-colors">FAQ (সাধারণ জিজ্ঞাসা)</a></li>
                    </ul>
                </div>

                <!-- Col 4 -->
                <div class="lg:col-span-3 space-y-4">
                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-white m-0 pb-1">
                        CONTACT US / যোগাযোগ
                    </h4>
                    <div class="space-y-3 text-xs sm:text-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-slate-900 border border-slate-700/60 flex items-center justify-center shrink-0 text-emerald-400 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 leading-none">ইমেইল সাপোর্ট</span>
                                <a href="mailto:{{ $siteEmail }}" class="text-slate-200 hover:text-emerald-400 transition-colors font-medium text-xs sm:text-sm mt-0.5 inline-block">
                                    {{ $siteEmail }}
                                </a>
                            </div>
                        </div>
                        @if(!empty($sitePhone))
                            <div class="flex items-center gap-3 pt-1">
                                <div class="w-8 h-8 rounded-xl bg-slate-900 border border-slate-700/60 flex items-center justify-center shrink-0 text-emerald-400 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-slate-400 leading-none">হটলাইন নম্বর</span>
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sitePhone) }}" class="text-slate-200 hover:text-emerald-400 transition-colors font-medium text-xs sm:text-sm mt-0.5 inline-block">
                                        {{ $sitePhone }}
                                    </a>
                                </div>
                            </div>
                        @endif
                        <p class="text-xs text-slate-400 leading-relaxed m-0 pt-1">
                            যেকোনো প্রশ্ন, তথ্য অথবা সহযোগিতার জন্য সরাসরি আমাদের ইমেইল করতে পারেন। আমাদের টিম দ্রুত আপনার সাথে যোগাযোগ করবে।
                        </p>
                    </div>
                </div>
            </div>

            <!-- Descriptive SEO Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 py-10 border-b border-slate-800/80 text-xs text-slate-400 leading-relaxed">
                <div>
                    <p class="m-0 leading-relaxed">
                        <strong class="text-slate-200 font-semibold">{{ $siteName }} – The #1 Property Classifieds Website in Bangladesh</strong> for Residential and Commercial Real Estate. Find Your Dream Property in Dhaka or Any Other City Across Bangladesh.
                    </p>
                </div>
                <div>
                    <p class="m-0 leading-relaxed">
                        <strong class="text-slate-200 font-semibold">At {{ $siteName }}</strong>, find the perfect property across all prime locations in Dhaka: Gulshan, Banani, Baridhara, Bashundhara, Dhanmondi, Uttara, Mirpur, Aftabnagar, Purbachal, and more.
                    </p>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <p class="m-0">
                    {{ $footerCopyright }}
                </p>
                <p class="m-0 flex items-center gap-1.5 text-[11px] text-slate-500">
                    <span>Designed & Developed with</span>
                    <span class="text-rose-500">❤️</span>
                    <span>in Bangladesh</span>
                </p>
            </div>
        </div>
    </footer>

    @php
        $appBaseUrl = url('/');
        if (!str_contains($appBaseUrl, 'localhost') && !str_contains($appBaseUrl, '127.0.0.1')) {
            $appBaseUrl = preg_replace('#/public/?$#', '', $appBaseUrl);
        }
    @endphp
    <script>
        window.admVars = {
            baseUrl: "{{ $appBaseUrl }}",
            swUrl: "{{ $appBaseUrl }}/firebase-messaging-sw.js",
            scope: "{{ $appBaseUrl }}/",
            registerTokenUrl: "{{ $appBaseUrl }}/api/fcm/register-token",
            csrfToken: "{{ csrf_token() }}",
            currentUserId: {{ auth()->id() ?? 0 }},
            isAdmin: {{ auth()->check() && auth()->user()->isAdmin() ? 'true' : 'false' }},
            isAgent: {{ auth()->check() && (auth()->user()->role === 'agent' || auth()->user()->is_agent) ? 'true' : 'false' }},
            isLoggedIn: {{ auth()->check() ? 'true' : 'false' }},
            vapidKey: "{{ \App\Services\FcmService::VAPID_KEY }}",
            firebase: {
                apiKey: "{{ \App\Models\Setting::get('adm_firebase_api_key', 'AIzaSyDE_xFL36AlFB-3ZhhP6eia_4LCfGpUzc4') }}",
                authDomain: "{{ \App\Models\Setting::get('adm_firebase_auth_domain', 'nuzul-d2161.firebaseapp.com') }}",
                projectId: "{{ \App\Models\Setting::get('adm_firebase_project_id', 'nuzul-d2161') }}",
                storageBucket: "nuzul-d2161.firebasestorage.app",
                messagingSenderId: "{{ \App\Models\Setting::get('adm_firebase_messaging_sender_id', '1057782250848') }}",
                appId: "{{ \App\Models\Setting::get('adm_firebase_app_id', '1:1057782250848:web:5b56f7becb5974e9877205') }}"
            }
        };
    </script>
    <script src="{{ asset('assets/js/adm-fcm-client.js') }}?v=1.0.5" defer></script>

    @stack('scripts')
</body>
</html>
