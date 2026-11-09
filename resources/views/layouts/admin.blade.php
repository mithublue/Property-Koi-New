<!DOCTYPE html>
<html lang="bn" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-cache-control" content="no-cache">
    @php
        $siteName = \App\Models\Setting::get('adm_site_name', config('app.name', 'PropertyKoi'));
        $siteFavicon = \App\Models\Setting::get('adm_site_favicon');
        $siteFaviconUrl = $siteFavicon ? (str_starts_with($siteFavicon, 'http') ? $siteFavicon : asset($siteFavicon)) : asset('assets/icons/icon-192x192.png');
        $siteLogo = \App\Models\Setting::get('adm_site_logo_dark') ?: \App\Models\Setting::get('adm_site_logo');
        $siteLogoUrl = $siteLogo ? (str_starts_with($siteLogo, 'http') ? $siteLogo : asset($siteLogo)) : null;
    @endphp
    <title>@yield('title', 'অ্যাডমিন প্যানেল') - {{ $siteName }} Admin</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteFaviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $siteFaviconUrl }}">

    <!-- Google Fonts: Hind Siliguri -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Hind Siliguri', system-ui, -apple-system, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-800" x-data="{ mobileMenuOpen: false }">
    <div class="min-h-full flex flex-col md:flex-row">

        <!-- Mobile Header Bar -->
        <header class="md:hidden bg-slate-900 text-white flex items-center justify-between px-4 py-3 sticky top-0 z-40 border-b border-slate-800 shadow-md">
            <div class="flex items-center space-x-2 space-x-reverse">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-slate-300 hover:text-white rounded-lg focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="font-bold text-lg text-emerald-400 flex items-center gap-1.5">
                    <span>🏢</span> {{ $siteName }} Admin
                </a>
            </div>
            <a href="{{ route('home') }}" target="_blank" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-full flex items-center gap-1 border border-slate-700">
                <span>🌐 সাইট দেখুন</span>
            </a>
        </header>

        <!-- Sidebar / Drawer -->
        <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
               class="fixed md:static inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 flex flex-col transition-transform duration-300 ease-in-out border-r border-slate-800 shadow-xl md:shadow-none">
            
            <!-- Sidebar Header -->
            <div class="p-5 flex items-center justify-between border-b border-slate-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                    @if($siteLogoUrl)
                        <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="h-8 max-w-[150px] object-contain">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-black text-lg shadow-inner">
                            PK
                        </div>
                        <div>
                            <div class="font-bold text-base text-white tracking-wide truncate max-w-[150px]">{{ $siteName }}</div>
                            <div class="text-[11px] text-emerald-400 font-medium">অ্যাডমিন কন্ট্রোল</div>
                        </div>
                    @endif
                </a>
                <button @click="mobileMenuOpen = false" class="md:hidden text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Navigation Links (Grouped Menu > Submenu Structure) -->
            <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto text-sm">
                @php
                    $navStructure = [
                        [
                            'type' => 'single',
                            'route' => 'admin.dashboard',
                            'icon' => '📊',
                            'label' => 'ড্যাশবোর্ড ওভারভিউ',
                        ],
                        [
                            'type' => 'group',
                            'key' => 'properties',
                            'label' => 'প্রপার্টি ও লিস্টিং',
                            'icon' => '🏠',
                            'routes' => [
                                'admin.properties.*',
                                'admin.sheet-importer.*',
                                'admin.verifications.*',
                                'admin.media.*',
                            ],
                            'items' => [
                                ['route' => 'admin.properties.index', 'label' => 'প্রপার্টি ক্যাটালগ (লাইভ)', 'icon' => '🏢'],
                                ['route' => 'admin.properties.directory', 'label' => 'প্রাইভেট প্রপার্টি ভল্ট', 'icon' => '🗄️'],
                                ['route' => 'admin.sheet-importer.index', 'label' => 'গুগল শিট বাল্ক ইমপোর্টার', 'icon' => '📑'],
                                ['route' => 'admin.verifications.index', 'label' => '৪-দফা লিগ্যাল ভেরিফিকেশন', 'icon' => '✅'],
                                ['route' => 'admin.media.index', 'label' => 'মিডিয়া লাইব্রেরি (Cloudinary)', 'icon' => '🖼️'],
                            ],
                        ],
                        [
                            'type' => 'group',
                            'key' => 'guides',
                            'label' => 'নলেজ হাব ও গাইডস',
                            'icon' => '📚',
                            'routes' => [
                                'admin.guides.*',
                            ],
                            'items' => [
                                ['route' => 'admin.guides.index', 'label' => 'সকল গাইডস ও আর্টিকেল', 'icon' => '📖'],
                                ['route' => 'admin.guides.export', 'label' => 'গাইড PDF এক্সপোর্টার', 'icon' => '📄'],
                                ['route' => 'admin.guides.comments.index', 'label' => 'গাইড কমেন্টস ও প্রশ্নোত্তর', 'icon' => '💬'],
                            ],
                        ],
                        [
                            'type' => 'group',
                            'key' => 'crm',
                            'label' => 'সিআরএম ও কমিউনিকেশন',
                            'icon' => '🎯',
                            'routes' => [
                                'admin.leads.*',
                                'admin.demands.*',
                                'admin.visits.*',
                                'admin.revenue.*',
                            ],
                            'items' => [
                                ['route' => 'admin.leads.index', 'label' => 'লিড ও সিআরএম পাইপলাইন', 'icon' => '🎯'],
                                ['route' => 'admin.demands.index', 'label' => 'প্রপার্টি চাহিদা (Demands)', 'icon' => '📋'],
                                ['route' => 'admin.visits.index', 'label' => 'সাইট ভিজিট ও শিডিউলার', 'icon' => '📅'],
                                ['route' => 'admin.revenue.index', 'label' => 'রেভিনিউ ও ০.৫% কমিশন', 'icon' => '💰'],
                            ],
                        ],
                        [
                            'type' => 'group',
                            'key' => 'users',
                            'label' => 'ইউজার ও ডিরেক্টরি',
                            'icon' => '👥',
                            'routes' => [
                                'admin.agents.*',
                                'admin.buyers.*',
                            ],
                            'items' => [
                                ['route' => 'admin.agents.index', 'label' => 'এজেন্ট ও মালিক ডিরেক্টরি', 'icon' => '👥'],
                                ['route' => 'admin.buyers.index', 'label' => 'বায়ার ডিরেক্টরি (Buyers)', 'icon' => '👤'],
                            ],
                        ],
                        [
                            'type' => 'group',
                            'key' => 'system',
                            'label' => 'সিস্টেম ও অ্যানালিটিক্স',
                            'icon' => '⚙️',
                            'routes' => [
                                'admin.settings.*',
                                'admin.documentation',
                                'admin.clarity',
                            ],
                            'items' => [
                                ['route' => 'admin.settings.index', 'label' => 'মাস্টার সেটিংস (১০ ট্যাব)', 'icon' => '⚙️'],
                                ['route' => 'admin.documentation', 'label' => 'সিস্টেম ডকস ও রুলস', 'icon' => '📖'],
                                ['route' => 'admin.clarity', 'label' => 'Microsoft Clarity অ্যানালিটিক্স', 'icon' => '📈'],
                            ],
                        ],
                    ];
                @endphp

                @foreach($navStructure as $entry)
                    @if($entry['type'] === 'single')
                        @php $isActive = request()->routeIs($entry['route'].'*'); @endphp
                        <a href="{{ route($entry['route']) }}"
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition-all duration-150 {{ $isActive ? 'bg-emerald-600 text-white shadow-sm font-semibold' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                            <span class="text-base leading-none">{{ $entry['icon'] }}</span>
                            <span class="flex-1">{{ $entry['label'] }}</span>
                        </a>
                    @else
                        @php
                            $isGroupActive = false;
                            foreach ($entry['routes'] as $pattern) {
                                if (request()->routeIs($pattern)) {
                                    $isGroupActive = true;
                                    break;
                                }
                            }
                        @endphp
                        <div x-data="{ open: {{ $isGroupActive ? 'true' : 'false' }} }" class="space-y-1">
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full flex items-center justify-between gap-2.5 px-3.5 py-2.5 rounded-xl font-medium transition-all duration-150 cursor-pointer select-none {{ $isGroupActive ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="text-base leading-none">{{ $entry['icon'] }}</span>
                                    <span class="truncate">{{ $entry['label'] }}</span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 shrink-0"
                                     :class="open ? 'rotate-180 text-emerald-400' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Submenu Items -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="pl-3 pr-1 py-1 space-y-1 border-l-2 border-slate-800 ml-5"
                                 style="display: none;">
                                @foreach($entry['items'] as $subItem)
                                    @php $isSubActive = request()->routeIs($subItem['route'].'*'); @endphp
                                    <a href="{{ route($subItem['route']) }}"
                                       class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs transition-all duration-150 {{ $isSubActive ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-800/80 font-medium' }}">
                                        <span class="text-[11px] leading-none">{{ $subItem['icon'] ?? '•' }}</span>
                                        <span class="flex-1 truncate">{{ $subItem['label'] }}</span>
                                        @if($isSubActive)
                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <!-- Bottom User & Live Site Info -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-950/40">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2.5 truncate">
                        <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-bold text-xs">
                            {{ substr(auth()->user()?->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-semibold text-white truncate">{{ auth()->user()?->name ?? 'Super Admin' }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ auth()->user()?->email ?? 'admin@propertykoi.com' }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('home') }}" target="_blank" class="flex-1 text-center text-xs py-1.5 px-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg transition border border-slate-700">
                        🌐 লাইভ সাইট
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs py-1.5 px-3 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white rounded-lg transition border border-rose-500/30">
                            লগআউট
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col min-w-0 bg-slate-50">
            <!-- Top App Bar for Desktop -->
            <header class="hidden md:flex items-center justify-between bg-white border-b border-slate-200 px-8 py-3.5 sticky top-0 z-30 shadow-sm">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-bold text-slate-800">@yield('page_title', 'ড্যাশবোর্ড')</h1>
                    @yield('page_badge')
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('property.create') }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-xl transition shadow-sm" title="নতুন ট্যাবে প্রপার্টি অ্যাড ফর্ম খুলুন">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>নতুন প্রপার্টি লিস্টিং ↗</span>
                    </a>
                    <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl transition">
                        <span>🌐 লাইভ ভিজিট</span>
                    </a>
                </div>
            </header>

            <!-- Notification Alerts -->
            <div class="px-4 md:px-8 pt-4">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between text-sm shadow-sm">
                        <div class="flex items-center gap-2">
                            <span>✅</span>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-center justify-between text-sm shadow-sm">
                        <div class="flex items-center gap-2">
                            <span>❌</span>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button @click="show = false" class="text-rose-500 hover:text-rose-700">&times;</button>
                    </div>
                @endif
            </div>

            <!-- Page Content -->
            <div class="p-4 md:p-8 flex-1">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Global SPA Toast Notification System -->
    <div x-data="{
            toasts: [],
            add(msg, type = 'success') {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, msg, type });
                setTimeout(() => this.remove(id), 3800);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @notify.window="add($event.detail.message, $event.detail.type)"
        class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm w-full">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="pointer-events-auto px-4 py-3 rounded-2xl shadow-2xl flex items-center justify-between gap-3 text-xs font-semibold border backdrop-blur-md transition-all"
                 :class="toast.type === 'error' ? 'bg-rose-950/90 text-rose-100 border-rose-700/80' : 'bg-slate-900/95 text-slate-100 border-emerald-500/50 shadow-emerald-950/30'">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span x-text="toast.type === 'error' ? '❌' : '✅'" class="text-sm flex-shrink-0"></span>
                    <span x-text="toast.msg" class="truncate"></span>
                </div>
                <button @click="remove(toast.id)" class="text-slate-400 hover:text-white text-base leading-none pl-2 flex-shrink-0">&times;</button>
            </div>
        </template>
    </div>

    @php
        $appBaseUrl = url('/');
        if (!str_contains($appBaseUrl, 'localhost') && !str_contains($appBaseUrl, '127.0.0.1')) {
            $appBaseUrl = preg_replace('#/public/?$#', '', $appBaseUrl);
        }
    @endphp
    <script>
        window.showToast = function(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
        };
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

