<!DOCTYPE html>
<html lang="bn" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PropertyKoi') }} - ওয়েব ইনস্টলেশন উইজার্ড</title>
    <!-- Google Fonts: Inter & Hind Siliguri for beautiful Bengali typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Standalone Tailwind CSS & Heroicons support -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                        }
                    },
                    fontFamily: {
                        sans: ['"Hind Siliguri"', '"Inter"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js for lightweight interactive wizard state -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between text-slate-800 antialiased selection:bg-brand-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight">প্রপার্টি কই (PropertyKoi)</h1>
                    <p class="text-xs text-slate-500 font-medium">ওয়েব অ্যাপ্লিকেশন ইনস্টলার ও কনফিগারেশন উইজার্ড</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>PHP {{ PHP_VERSION }}</span>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 py-8">

        @if($isInstalled && !$isReinstall)
            <!-- Already Installed Screen -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8 text-center max-w-xl mx-auto">
                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">অ্যাপ্লিকেশনটি ইতিমধ্যে ইনস্টল করা আছে!</h2>
                <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                    নিরাপত্তার স্বার্থে সিস্টেমটি বর্তমানে ইনস্টলার লক অবস্থায় রয়েছে। আপনি সরাসরি ওয়েবসাইট অথবা এডমিন ড্যাশবোর্ডে প্রবেশ করতে পারেন।
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center mb-6">
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-600/20 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        ওয়েবসাইটে যান (Visit Site)
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        এডমিন লগইন (Login)
                    </a>
                </div>

                <div class="text-xs text-slate-400 border-t border-slate-100 pt-4">
                    পুনরায় ইনস্টল করতে চাইলে সার্ভারের <code class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-mono">storage/installed</code> ফাইলটি ডিলিট করুন অথবা <a href="{{ route('install.index', ['reinstall' => 1]) }}" class="text-brand-600 underline font-medium">এখানে ক্লিক করে ফোর্স মোড খুলুন</a>।
                </div>
            </div>
        @else
            <!-- Multi-Step Installer Wizard via Alpine.js -->
            <div x-data="installerWizard()" class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                
                <!-- Wizard Stepper Indicator -->
                <div class="bg-slate-50 border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between max-w-2xl mx-auto">
                        
                        <!-- Step 1 Indicator -->
                        <div class="flex items-center">
                            <div :class="step >= 1 ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors">
                                <span x-show="step > 1">✓</span>
                                <span x-show="step <= 1">১</span>
                            </div>
                            <span class="ml-2 text-xs font-semibold hidden sm:inline" :class="step === 1 ? 'text-slate-900 font-bold' : 'text-slate-500'">সার্ভার চেক</span>
                        </div>
                        <div class="flex-1 h-0.5 mx-3" :class="step >= 2 ? 'bg-brand-500' : 'bg-slate-200'"></div>

                        <!-- Step 2 Indicator -->
                        <div class="flex items-center">
                            <div :class="step >= 2 ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors">
                                <span x-show="step > 2">✓</span>
                                <span x-show="step <= 2">২</span>
                            </div>
                            <span class="ml-2 text-xs font-semibold hidden sm:inline" :class="step === 2 ? 'text-slate-900 font-bold' : 'text-slate-500'">ডাটাবেজ</span>
                        </div>
                        <div class="flex-1 h-0.5 mx-3" :class="step >= 3 ? 'bg-brand-500' : 'bg-slate-200'"></div>

                        <!-- Step 3 Indicator -->
                        <div class="flex items-center">
                            <div :class="step >= 3 ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors">
                                <span x-show="step > 3">✓</span>
                                <span x-show="step <= 3">৩</span>
                            </div>
                            <span class="ml-2 text-xs font-semibold hidden sm:inline" :class="step === 3 ? 'text-slate-900 font-bold' : 'text-slate-500'">সাইট ও এডমিন</span>
                        </div>
                        <div class="flex-1 h-0.5 mx-3" :class="step >= 4 ? 'bg-brand-500' : 'bg-slate-200'"></div>

                        <!-- Step 4 Indicator -->
                        <div class="flex items-center">
                            <div :class="step >= 4 ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20' : 'bg-slate-200 text-slate-600'" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors">
                                <span>৪</span>
                            </div>
                            <span class="ml-2 text-xs font-semibold hidden sm:inline" :class="step === 4 ? 'text-slate-900 font-bold' : 'text-slate-500'">ইনস্টলেশন</span>
                        </div>

                    </div>
                </div>

                <div class="p-6 sm:p-8">

                    <!-- ============================================== -->
                    <!-- STEP 1: SERVER REQUIREMENTS & PERMISSIONS      -->
                    <!-- ============================================== -->
                    <div x-show="step === 1" x-transition>
                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-900 mb-1">ধাপ ১: সার্ভার প্রয়োজনীয়তা ও ফোল্ডার পারমিশন চেক</h2>
                            <p class="text-sm text-slate-500">অ্যাপ্লিকেশনটি লাইভ সার্ভার অথবা লোকালহোস্টে সঠিকভাবে রান করার জন্য নিচের রিকোয়ারমেন্টগুলো যাচাই করুন।</p>
                        </div>

                        <!-- Overall status alert -->
                        @if($canInstall)
                            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start space-x-3 rtl:space-x-reverse">
                                <div class="text-emerald-600 shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-sm text-emerald-800">
                                    <strong class="font-bold">চমৎকার!</strong> আপনার সার্ভার প্রপার্টি কই অ্যাপ্লিকেশনটি ইনস্টল ও পরিচালনা করার জন্য সম্পূর্ণ উপযোগী।
                                </div>
                            </div>
                        @else
                            <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start space-x-3 rtl:space-x-reverse">
                                <div class="text-rose-600 shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-sm text-rose-800">
                                    <strong class="font-bold">মনোযোগ দিন:</strong> কিছু প্রয়োজনীয় পিএইচপি এক্সটেনশন অথবা ফোল্ডার পারমিশন মিসিং রয়েছে। নিচের লাল চিহ্নিত বিষয়গুলো সমাধান করে পেইজটি রিফ্রেশ করুন।
                                </div>
                            </div>
                        @endif

                        <!-- Requirements Checklist -->
                        <div class="mb-6">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3">PHP এক্সটেনশনসমূহ</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                @foreach($requirements as $key => $req)
                                    <div class="p-3 rounded-xl border {{ $req['pass'] ? 'border-slate-200 bg-slate-50/50' : 'border-rose-200 bg-rose-50/50' }} flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-800">{{ $req['name'] }}</div>
                                            <div class="text-xs text-slate-500">{{ $req['required'] }} (বর্তমান: <span class="font-mono">{{ $req['current'] }}</span>)</div>
                                            @if(!$req['pass'])
                                                <div class="text-[11px] text-rose-600 mt-0.5">{{ $req['help'] }}</div>
                                            @endif
                                        </div>
                                        <div class="shrink-0 ml-2">
                                            @if($req['pass'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    ✓ ঠিক আছে
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                    ✕ প্রয়োজন
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Permissions Checklist -->
                        <div class="mb-8">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3">ডিরেক্টরি ও ফাইল রাইট পারমিশন</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                @foreach($permissions as $key => $perm)
                                    <div class="p-3 rounded-xl border {{ $perm['pass'] ? 'border-slate-200 bg-slate-50/50' : 'border-rose-200 bg-rose-50/50' }} flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-semibold font-mono text-slate-800">{{ $perm['name'] }}</div>
                                            <div class="text-xs text-slate-500">রাইটেবল পারমিশন (Writable)</div>
                                            @if(!$perm['pass'])
                                                <div class="text-[11px] text-rose-600 mt-0.5">{{ $perm['help'] }}</div>
                                            @endif
                                        </div>
                                        <div class="shrink-0 ml-2">
                                            @if($perm['pass'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    ✓ Writable
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                    ✕ Not Writable
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                            <button type="button" onclick="window.location.reload()" class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                                ↻ পুনরায় চেক করুন (Refresh)
                            </button>

                            @if($canInstall)
                                <button type="button" @click="step = 2" class="inline-flex items-center px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-600/20 transition-all">
                                    পরবর্তী ধাপ: ডাটাবেজ কনফিগারেশন →
                                </button>
                            @else
                                <button type="button" disabled class="inline-flex items-center px-6 py-2.5 rounded-xl text-sm font-semibold text-slate-400 bg-slate-200 cursor-not-allowed">
                                    পারমিশন সমাধান না হওয়া পর্যন্ত অগ্রসর হওয়া যাবে না
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 2: DATABASE CONFIGURATION & TEST          -->
                    <!-- ============================================== -->
                    <div x-show="step === 2" x-transition x-cloak>
                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-900 mb-1">ধাপ ২: ডাটাবেজ কনফিগারেশন ও কানেকশন টেস্ট</h2>
                            <p class="text-sm text-slate-500">আপনার cPanel MySQL Database অথবা লোকালহোস্ট ডাটাবেজ ক্রেডেনশিয়াল প্রদান করুন।</p>
                        </div>

                        <!-- Connection Test Live Feedback Alert -->
                        <div x-show="dbTestStatus" class="mb-6 p-4 rounded-xl text-sm flex items-start space-x-3 rtl:space-x-reverse transition-all"
                             :class="dbTestStatus === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800'">
                            <div class="shrink-0 mt-0.5">
                                <span x-show="dbTestStatus === 'success'">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <span x-show="dbTestStatus === 'error'">
                                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                            </div>
                            <div class="flex-1" x-text="dbTestMessage"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                            <!-- Database Host -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    ডাটাবেজ হোস্ট (Database Host) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="form.db_host" placeholder="127.0.0.1 অথবা localhost" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm font-mono">
                                <p class="text-[11px] text-slate-400 mt-1">সাধারণত <code class="font-mono">127.0.0.1</code> বা <code class="font-mono">localhost</code> হয়।</p>
                            </div>

                            <!-- Database Port -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    পোর্ট (Port) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" x-model="form.db_port" placeholder="3306" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm font-mono">
                                <p class="text-[11px] text-slate-400 mt-1">MySQL-এর ডিফল্ট পোর্ট হলো <code class="font-mono">3306</code>।</p>
                            </div>

                            <!-- Database Name -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    ডাটাবেজ নাম (Database Name) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="form.db_database" placeholder="e.g. cpaneluser_propertykoi" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm font-mono">
                                <p class="text-[11px] text-slate-400 mt-1">cPanel-এ তৈরি করা ডাটাবেজের সঠিক নাম লিখুন।</p>
                            </div>

                            <!-- Database Username -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    ডাটাবেজ ইউজারনেম (Username) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="form.db_username" placeholder="e.g. cpaneluser_dbadmin" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm font-mono">
                                <p class="text-[11px] text-slate-400 mt-1">উক্ত ডাটাবেজের সাথে প্রিভিলেজ যুক্ত ইউজারের নাম।</p>
                            </div>

                            <!-- Database Password -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    ডাটাবেজ পাসওয়ার্ড (Password)
                                </label>
                                <input type="password" x-model="form.db_password" placeholder="••••••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm font-mono">
                                <p class="text-[11px] text-slate-400 mt-1">ডাটাবেজ ইউজারের পাসওয়ার্ড (লোকালহোস্ট Laragon/XAMPP-এ রুট পাসওয়ার্ড সাধারণত ফাঁকা থাকে)।</p>
                            </div>
                        </div>

                        <!-- Test Connection Button -->
                        <div class="mb-8 p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">কানেকশন টেস্ট করুন</h4>
                                <p class="text-xs text-slate-500">তথ্যগুলো সঠিক আছে কিনা তা নিশ্চিত হতে এক ক্লিকে টেস্ট করে নিন।</p>
                            </div>
                            <button type="button" @click="testDatabaseConnection()" :disabled="isTestingDb" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition-all shadow-sm">
                                <svg x-show="isTestingDb" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-700" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-show="!isTestingDb">🔍 কানেকশন টেস্ট (Test Connection)</span>
                                <span x-show="isTestingDb">যাচাই করা হচ্ছে...</span>
                            </button>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                            <button type="button" @click="step = 1" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                                ← পূর্ববর্তী ধাপ (সার্ভার চেক)
                            </button>
                            <button type="button" @click="validateStep2()" class="inline-flex items-center px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-600/20 transition-all">
                                পরবর্তী ধাপ: সাইট ও এডমিন সেটআপ →
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 3: SITE INFO & SUPER ADMIN ACCOUNT        -->
                    <!-- ============================================== -->
                    <div x-show="step === 3" x-transition x-cloak>
                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-slate-900 mb-1">ধাপ ৩: সাইট ও সুপার এডমিন অ্যাকাউন্ট সেটআপ</h2>
                            <p class="text-sm text-slate-500">আপনার মার্কেটপ্লেসের নাম এবং এডমিন প্যানেল পরিচালনার জন্য মূল এডমিন অ্যাকাউন্ট তৈরি করুন।</p>
                        </div>

                        <div class="space-y-6 mb-8">
                            
                            <!-- Site Info Group -->
                            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">সাইটের প্রাথমিক তথ্য</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">সাইটের নাম (Site Name) <span class="text-rose-500">*</span></label>
                                        <input type="text" x-model="form.app_name" placeholder="PropertyKoi" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">সাইট ইউআরএল (Site URL) <span class="text-rose-500">*</span></label>
                                        <input type="url" x-model="form.app_url" placeholder="https://propertykoi.com" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm font-mono">
                                    </div>
                                </div>
                            </div>

                            <!-- Super Admin Account Group -->
                            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">সুপার এডমিন অ্যাকাউন্ট (Super Admin Credentials)</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">এডমিনের পূর্ণ নাম (Full Name) <span class="text-rose-500">*</span></label>
                                        <input type="text" x-model="form.admin_name" placeholder="PropertyKoi Admin" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">এডমিন ইমেইল (Email Address) <span class="text-rose-500">*</span></label>
                                        <input type="email" x-model="form.admin_email" placeholder="admin@propertykoi.com" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                        <p class="text-[11px] text-slate-400 mt-1">এই ইমেইল দিয়ে আপনি এডমিন প্যানেলে লগইন করবেন।</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">এডমিন মোবাইল / হোয়াটসঅ্যাপ <span class="text-rose-500">*</span></label>
                                        <input type="text" x-model="form.admin_phone" placeholder="01711000001" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                    </div>
                                    <div></div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">লগইন পাসওয়ার্ড (Password) <span class="text-rose-500">*</span></label>
                                        <input type="password" x-model="form.admin_password" placeholder="কমপক্ষে ৬ ক্যারেক্টার" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">পাসওয়ার্ড নিশ্চিত করুন (Confirm Password) <span class="text-rose-500">*</span></label>
                                        <input type="password" x-model="form.admin_password_confirmation" placeholder="একই পাসওয়ার্ড পুনরায় লিখুন" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Installation Options -->
                            <div class="space-y-3 px-1">
                                <label class="flex items-start space-x-3 rtl:space-x-reverse cursor-pointer">
                                    <input type="checkbox" x-model="form.seed_demo" class="mt-1 rounded text-brand-600 focus:ring-brand-500 border-slate-300 w-4 h-4">
                                    <div>
                                        <span class="text-sm font-semibold text-slate-800">ডেমো ডাটা লোড করুন (Load Sample Properties & Categories)</span>
                                        <p class="text-xs text-slate-500">পরীক্ষামূলক ব্যবহারের জন্য কিছু রেডিমেড প্লট ও ফ্ল্যাটের স্যাম্পল প্রপার্টি ডাটাবেজে যুক্ত করবে।</p>
                                    </div>
                                </label>

                                <label class="flex items-start space-x-3 rtl:space-x-reverse cursor-pointer">
                                    <input type="checkbox" x-model="form.fresh_migration" class="mt-1 rounded text-rose-600 focus:ring-rose-500 border-slate-300 w-4 h-4">
                                    <div>
                                        <span class="text-sm font-semibold text-slate-800">ফ্রেশ ডাটাবেজ ইনস্টলেশন (Wipe old tables if any)</span>
                                        <p class="text-xs text-slate-500">যদি ডাটাবেজে পূর্বের কোনো পুরনো টেবিল থাকে, তবে সেগুলো সম্পূর্ণ মুছে নতুন ফ্রেশ স্ট্রাকচার তৈরি করবে।</p>
                                    </div>
                                </label>
                            </div>

                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                            <button type="button" @click="step = 2" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                                ← পূর্ববর্তী ধাপ (ডাটাবেজ)
                            </button>
                            <button type="button" @click="startInstallation()" class="inline-flex items-center px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-600/20 transition-all">
                                🚀 ইনস্টলেশন সম্পন্ন করুন (Install Now)
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 4: INSTALLATION EXECUTION & PROGRESS      -->
                    <!-- ============================================== -->
                    <div x-show="step === 4" x-transition x-cloak class="py-8">
                        
                        <div class="text-center max-w-md mx-auto">
                            
                            <!-- Animated Spinner / Status Icon -->
                            <div class="relative w-20 h-20 mx-auto mb-6">
                                <div class="absolute inset-0 rounded-full border-4 border-slate-100"></div>
                                <div class="absolute inset-0 rounded-full border-4 border-brand-500 border-t-transparent animate-spin"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-brand-600">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>

                            <h3 class="text-xl font-bold text-slate-900 mb-2">ইনস্টলেশন প্রক্রিয়া চলছে...</h3>
                            <p class="text-sm text-slate-500 mb-6" x-text="installProgressMessage">দয়া করে অপেক্ষা করুন, সার্ভার কনফিগারেশন আপডেট করা হচ্ছে।</p>

                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-3 mb-6 overflow-hidden border border-slate-200">
                                <div class="bg-gradient-to-r from-brand-600 to-emerald-400 h-3 rounded-full transition-all duration-500" :style="'width: ' + installPercent + '%'"></div>
                            </div>

                            <!-- Sequential Steps Log -->
                            <div class="text-left space-y-2.5 text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-200 font-medium">
                                <div class="flex items-center space-x-2" :class="installPercent >= 25 ? 'text-emerald-700 font-bold' : 'text-slate-400'">
                                    <span>✓</span>
                                    <span>১. .env কনফিগারেশন ও সিকিউরিটি কী (APP_KEY) তৈরি</span>
                                </div>
                                <div class="flex items-center space-x-2" :class="installPercent >= 55 ? 'text-emerald-700 font-bold' : 'text-slate-400'">
                                    <span>✓</span>
                                    <span>২. ডাটাবেজ কানেকশন ও সকল মাইগ্রেশন টেবিল তৈরি</span>
                                </div>
                                <div class="flex items-center space-x-2" :class="installPercent >= 80 ? 'text-emerald-700 font-bold' : 'text-slate-400'">
                                    <span>✓</span>
                                    <span>৩. সুপার এডমিন অ্যাকাউন্ট ও ডিফল্ট সেটিংস রেজিস্টার</span>
                                </div>
                                <div class="flex items-center space-x-2" :class="installPercent >= 100 ? 'text-emerald-700 font-bold' : 'text-slate-400'">
                                    <span>✓</span>
                                    <span>৪. ক্যাশ অপটিমাইজেশন ও ইনস্টলার লক ফাইল তৈরি</span>
                                </div>
                            </div>

                            <!-- Error Alert if failed -->
                            <div x-show="installError" class="mt-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs text-left" x-cloak>
                                <strong class="font-bold block mb-1">ইনস্টলেশনে ত্রুটি ঘটেছে:</strong>
                                <span x-text="installError"></span>
                                <div class="mt-3">
                                    <button type="button" @click="step = 3; installError = null;" class="px-3 py-1.5 rounded-lg bg-rose-600 text-white font-semibold hover:bg-rose-700 text-xs">
                                        ← সেটিংস সংশোধন করে পুনরায় চেষ্টা করুন
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>
        @endif

    </main>

    <!-- Footer Copyright -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} PropertyKoi.com — সর্বস্বত্ব সংরক্ষিত। WordPress-এর মতো স্বয়ংক্রিয় ইনস্টলেশন সিস্টেম।
    </footer>

    <!-- Alpine.js Component Script -->
    <script>
        function installerWizard() {
            return {
                step: 1,
                isTestingDb: false,
                dbTestStatus: null,
                dbTestMessage: '',
                isInstalling: false,
                installPercent: 15,
                installProgressMessage: 'ইনস্টলেশন শুরু করার জন্য প্রস্তুত হচ্ছে...',
                installError: null,

                form: {
                    app_name: '{{ addslashes($config['app_name']) }}',
                    app_url: '{{ addslashes($config['app_url']) }}',
                    db_host: '{{ addslashes($config['db_host']) }}',
                    db_port: '{{ addslashes($config['db_port']) }}',
                    db_database: '{{ addslashes($config['db_database']) }}',
                    db_username: '{{ addslashes($config['db_username']) }}',
                    db_password: '{{ addslashes($config['db_password']) }}',
                    admin_name: 'PropertyKoi Admin',
                    admin_email: 'admin@propertykoi.com',
                    admin_phone: '01711000001',
                    admin_password: '',
                    admin_password_confirmation: '',
                    seed_demo: false,
                    fresh_migration: false,
                },

                // Test Database Connection via AJAX
                async testDatabaseConnection() {
                    if (!this.form.db_host || !this.form.db_database || !this.form.db_username) {
                        this.dbTestStatus = 'error';
                        this.dbTestMessage = 'দয়া করে হোস্ট, ডাটাবেজের নাম এবং ইউজারনেম পূরণ করুন।';
                        return;
                    }

                    this.isTestingDb = true;
                    this.dbTestStatus = null;
                    this.dbTestMessage = '';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch('{{ route('install.test-db') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                db_host: this.form.db_host,
                                db_port: this.form.db_port,
                                db_database: this.form.db_database,
                                db_username: this.form.db_username,
                                db_password: this.form.db_password,
                            })
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.dbTestStatus = 'success';
                            this.dbTestMessage = data.message || 'ডাটাবেজ কানেকশন সফল হয়েছে!';
                        } else {
                            this.dbTestStatus = 'error';
                            this.dbTestMessage = data.message || 'ডাটাবেজ কানেকশন ব্যর্থ হয়েছে। ক্রেডেনশিয়াল পরীক্ষা করুন।';
                        }
                    } catch (err) {
                        this.dbTestStatus = 'error';
                        this.dbTestMessage = 'সার্ভারের সাথে যোগাযোগে সমস্যা হয়েছে: ' + err.message;
                    } finally {
                        this.isTestingDb = false;
                    }
                },

                validateStep2() {
                    if (!this.form.db_host || !this.form.db_database || !this.form.db_username) {
                        alert('দয়া করে হোস্ট, ডাটাবেজের নাম এবং ইউজারনেম পূরণ করুন।');
                        return;
                    }
                    this.step = 3;
                },

                // 1-Click Installation Runner
                async startInstallation() {
                    // Validation checks
                    if (!this.form.app_name || !this.form.app_url) {
                        alert('সাইটের নাম এবং ইউআরএল আবশ্যক।');
                        return;
                    }
                    if (!this.form.admin_name || !this.form.admin_email || !this.form.admin_phone) {
                        alert('এডমিনের নাম, ইমেইল এবং মোবাইল নাম্বার আবশ্যক।');
                        return;
                    }
                    if (!this.form.admin_password || this.form.admin_password.length < 6) {
                        alert('এডমিন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');
                        return;
                    }
                    if (this.form.admin_password !== this.form.admin_password_confirmation) {
                        alert('কনফার্ম পাসওয়ার্ড মিলছে না!');
                        return;
                    }

                    this.step = 4;
                    this.isInstalling = true;
                    this.installError = null;
                    this.installPercent = 25;
                    this.installProgressMessage = 'ধাপ ১: .env ফাইল কনফিগার ও সিকিউরিটি কী জেনারেট হচ্ছে...';

                    // Smooth visual progression
                    setTimeout(() => {
                        if (this.isInstalling && !this.installError) {
                            this.installPercent = 55;
                            this.installProgressMessage = 'ধাপ ২: ডাটাবেজ মাইগ্রেশন ও টেবিল স্ট্রাকচার তৈরি হচ্ছে...';
                        }
                    }, 1200);

                    setTimeout(() => {
                        if (this.isInstalling && !this.installError) {
                            this.installPercent = 80;
                            this.installProgressMessage = 'ধাপ ৩: সুপার এডমিন অ্যাকাউন্ট ও ডিফল্ট সেটিংস রেজিস্টার হচ্ছে...';
                        }
                    }, 3000);

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch('{{ route('install.run') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.installPercent = 100;
                            this.installProgressMessage = 'ধাপ ৪: ইনস্টলেশন সম্পন্ন হয়েছে! রিডাইরেক্ট হচ্ছে...';
                            setTimeout(() => {
                                window.location.href = data.redirect_url || '{{ route('install.success') }}';
                            }, 1000);
                        } else {
                            this.installError = data.message || 'ইনস্টলেশন ব্যর্থ হয়েছে। দয়া করে এরর চেক করুন।';
                            this.isInstalling = false;
                        }
                    } catch (err) {
                        this.installError = 'সার্ভার রেসপন্সে সমস্যা হয়েছে: ' + err.message;
                        this.isInstalling = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
