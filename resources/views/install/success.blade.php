<!DOCTYPE html>
<html lang="bn" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $installedInfo['app_name'] ?? 'PropertyKoi' }} - ইনস্টলেশন সম্পন্ন!</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
</head>
<body class="min-h-full flex flex-col justify-between text-slate-800 antialiased selection:bg-brand-500 selection:text-white">

    <!-- Header -->
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ $installedInfo['app_name'] ?? 'PropertyKoi' }}</h1>
                    <p class="text-xs text-slate-500 font-medium">ইনস্টলেশন সফলভাবে সম্পন্ন হয়েছে</p>
                </div>
            </div>
            <div class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">
                ✓ লাইভ রেডি
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-2xl w-full mx-auto px-4 py-12">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8 sm:p-10 text-center">
            
            <!-- Big Success Checkmark Animation -->
            <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-emerald-500/20">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-2">অভিনন্দন! ইনস্টলেশন সফল হয়েছে</h2>
            <p class="text-slate-600 text-sm mb-8 leading-relaxed max-w-lg mx-auto">
                আপনার রিয়েল এস্টেট পোর্টাল এবং সুপার এডমিন অ্যাকাউন্ট সাফল্যের সাথে তৈরি হয়েছে। নিচের তথ্যগুলো সংগ্রহে রাখুন।
            </p>

            <!-- Admin Credentials Summary Card -->
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 text-left mb-8 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 pb-2 mb-3">
                    এডমিন প্যানেল এক্সেস তথ্য (Login Credentials)
                </h3>
                
                <div class="flex items-center justify-between text-sm py-1 border-b border-slate-100">
                    <span class="text-slate-500">সাইট ইউআরএল (Site URL):</span>
                    <a href="{{ $installedInfo['app_url'] ?? url('/') }}" target="_blank" class="font-mono text-brand-600 font-semibold hover:underline">
                        {{ $installedInfo['app_url'] ?? url('/') }}
                    </a>
                </div>

                <div class="flex items-center justify-between text-sm py-1 border-b border-slate-100">
                    <span class="text-slate-500">এডমিন ইমেইল (Login Email):</span>
                    <span class="font-mono text-slate-900 font-bold bg-white px-2 py-0.5 rounded border border-slate-200">
                        {{ $installedInfo['admin_email'] ?? 'admin@propertykoi.com' }}
                    </span>
                </div>

                <div class="flex items-center justify-between text-sm py-1 border-b border-slate-100">
                    <span class="text-slate-500">এডমিন পাসওয়ার্ড:</span>
                    <span class="text-slate-700 italic text-xs">
                        [ইনস্টলেশনে আপনার দেওয়া পাসওয়ার্ড]
                    </span>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <span class="text-slate-500">এডমিন লগইন লিংক:</span>
                    <a href="{{ route('login') }}" class="font-mono text-xs font-semibold text-brand-600 hover:underline">
                        {{ route('login') }}
                    </a>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-lg shadow-brand-600/30 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    এডমিন প্যানেলে লগইন করুন
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    ওয়েবসাইট হোমপেজ দেখুন
                </a>
            </div>

            <!-- Security Lock Notice -->
            <div class="text-xs text-slate-400 border-t border-slate-100 pt-5 text-left flex items-start space-x-2.5 rtl:space-x-reverse">
                <div class="text-amber-500 shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div class="text-slate-500 leading-normal">
                    <strong>নিরাপত্তা নিশ্চিতকরণ:</strong> সিস্টেমটি সুরক্ষিত রাখতে সার্ভারের <code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-slate-700">storage/installed</code> লক ফাইলটি তৈরি করা হয়েছে। পরবর্তীতে এই ইনস্টলার পেইজটিতে কেউ অননুমোদিতভাবে প্রবেশ করতে পারবে না।
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} PropertyKoi.com — সর্বস্বত্ব সংরক্ষিত।
    </footer>

</body>
</html>
