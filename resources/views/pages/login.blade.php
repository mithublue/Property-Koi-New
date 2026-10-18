@extends('layouts.app')

@section('title', 'লগইন বা রেজিস্টার করুন - PropertyKoi')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center px-4 py-12"
     x-data="{ tab: '{{ request('tab') === 'register' || !empty($isRegisterTab) || old('is_register') ? 'register' : 'login' }}' }">
    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xl space-y-6">

        <!-- Logo & Header -->
        <div class="text-center space-y-2">
            <div class="w-14 h-14 bg-gradient-to-tr from-emerald-700 to-teal-600 text-white font-black text-2xl rounded-2xl mx-auto flex items-center justify-center shadow-lg shadow-emerald-700/20">
                PK
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight" x-text="tab === 'login' ? 'অ্যাকাউন্টে প্রবেশ করুন' : 'নতুন অ্যাকাউন্ট তৈরি করুন'"></h2>
            <p class="text-xs text-slate-500" x-text="tab === 'login' ? 'ক্রেতা ড্যাশবোর্ড বা প্রপার্টি ম্যানেজ করতে লগইন করুন' : 'রেজিস্ট্রেশনের পর আপনার ইমেইলে অ্যাক্টিভেশন লিঙ্ক পাঠানো হবে'"></p>
        </div>

        @if(!empty(config('services.google.client_id')) && !empty(config('services.google.client_secret')))
        <!-- Google / Gmail One-Click Auth Button -->
        <div>
            <a href="{{ route('auth.google') }}"
               class="w-full py-3 px-4 bg-white hover:bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-2xl text-xs font-bold text-slate-700 transition flex items-center justify-center gap-3 shadow-xs active:scale-[0.98]">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.8 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
                    <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
                    <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 12.3 0 15s.7 5.3 1.9 7.7l3.7-2.9z"/>
                    <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2-6.4-4.8L1.9 16.4C3.7 20.4 7.5 23 12 23z"/>
                </svg>
                <span>Gmail / Google দিয়ে প্রবেশ করুন</span>
            </a>

            <div class="relative my-5">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <span class="bg-white px-3">অথবা ইউজারনেম ও পাসওয়ার্ড দিয়ে</span>
                </div>
            </div>
        </div>
        @endif

        <!-- Tab Toggle Buttons -->
        <div class="flex items-center p-1.5 bg-slate-100 rounded-2xl border border-slate-200 text-xs font-bold">
            <button type="button" @click="tab = 'login'"
                    :class="tab === 'login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                    class="flex-1 py-2.5 rounded-xl transition text-center cursor-pointer">
                লগইন (Sign In)
            </button>
            <button type="button" @click="tab = 'register'"
                    :class="tab === 'register' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                    class="flex-1 py-2.5 rounded-xl transition text-center cursor-pointer">
                নতুন রেজিস্ট্রেশন (Register)
            </button>
        </div>

        <!-- Global Flash Alerts -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 p-3.5 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-300 text-rose-800 p-3.5 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-2xl text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <!-- ==========================================
             FORM 1: LOGIN (EMAIL / USERNAME / PHONE)
             ========================================== -->
        <form x-show="tab === 'login'" action="{{ route('login') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">ইমেইল, ইউজারনেম বা মোবাইল নম্বর *</label>
                <input type="text" name="email_or_phone" value="{{ old('email_or_phone') }}" required
                       placeholder="e.g. mithu_buyer অথবা buyer@gmail.com অথবা 017..."
                       class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-medium">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">পাসওয়ার্ড *</label>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-semibold select-none">
                    <input type="checkbox" name="remember" value="1" checked class="rounded text-emerald-600">
                    <span>লগইন মনে রাখুন</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-extrabold text-xs rounded-xl transition shadow-md flex items-center justify-center gap-1.5 cursor-pointer">
                <span>লগইন করুন &rarr;</span>
            </button>
        </form>

        <!-- ==========================================
             FORM 2: REGISTRATION WITH ACTIVATION LINK
             ========================================== -->
        <form x-show="tab === 'register'" action="{{ route('register') }}" method="POST" class="space-y-3.5 text-xs" x-cloak>
            @csrf
            <input type="hidden" name="is_register" value="1">

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">আপনার পূর্ণ নাম *</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Mithu Rahman"
                       class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-medium">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">ইউজারনেম *</label>
                    <input type="text" name="username" value="{{ old('username') }}" required placeholder="e.g. mithu_buyer"
                           class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-400 mt-0.5">লগইনের জন্য ব্যবহার করা যাবে</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">WhatsApp নম্বর</label>
                    <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="017xxxxxxxx"
                           class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-400 mt-0.5">জরুরি ডিল নোটিফিকেশনের জন্য</p>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block font-bold text-slate-700 uppercase">ইমেইল ঠিকানা *</label>
                    <span class="text-[10px] text-emerald-600 font-bold">✉️ অ্যাক্টিভেশন লিঙ্ক পাঠানো হবে</span>
                </div>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="e.g. yourname@gmail.com"
                       class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-medium">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">পাসওয়ার্ড *</label>
                    <input type="password" name="password" required placeholder="কমপক্ষে ৬ অক্ষর"
                           class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">পাসওয়ার্ড নিশ্চিত করুন *</label>
                    <input type="password" name="password_confirmation" required placeholder="একই পাসওয়ার্ড লিখুন"
                           class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
            </div>

            <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-[11px] text-emerald-900 leading-relaxed">
                ℹ️ রেজিস্ট্রেশন সম্পন্ন হওয়ামাত্রই আপনার অ্যাকাউন্টে স্বয়ংক্রিয় লগইন হয়ে <strong>ক্রেতা ড্যাশবোর্ডে</strong> চলে যাবে এবং একটি ইমেইল অ্যাক্টিভেশন লিঙ্ক ও ৬-সংখ্যার ওটিপি কোড পাঠানো হবে।
            </div>

            <button type="submit"
                    class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-extrabold text-xs rounded-xl transition shadow-md flex items-center justify-center gap-1.5 cursor-pointer">
                <span>✓ অ্যাকাউন্ট তৈরি ও সক্রিয় করুন &rarr;</span>
            </button>
        </form>

        <!-- Quick Localhost Testing Accounts Helper (Only visible in Local Development) -->
        @if(app()->isLocal() && config('app.debug'))
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs space-y-2 text-slate-600">
            <div class="font-bold text-slate-800 flex items-center justify-between">
                <span class="flex items-center gap-1.5">⚡ ১-ক্লিকে টেস্ট অ্যাকাউন্ট লগইন:</span>
                <span class="text-[10px] text-slate-400">Local Dev Only</span>
            </div>
            <div class="grid grid-cols-2 gap-2 pt-1">
                <button type="button" @click="tab = 'login'" onclick="fillLogin('admin@propertykoi.com', 'password')"
                        class="p-2.5 bg-white hover:bg-emerald-50/50 border border-slate-200 rounded-xl text-left cursor-pointer transition">
                    <div class="font-bold text-slate-900">👑 Admin</div>
                    <div class="text-[10px] text-slate-400 font-mono">admin@propertykoi.com</div>
                </button>
                <button type="button" @click="tab = 'login'" onclick="fillLogin('buyer@propertykoi.com', 'password')"
                        class="p-2.5 bg-white hover:bg-emerald-50/50 border border-slate-200 rounded-xl text-left cursor-pointer transition">
                    <div class="font-bold text-slate-900">🛒 Buyer</div>
                    <div class="text-[10px] text-slate-400 font-mono">buyer@propertykoi.com</div>
                </button>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
function fillLogin(user, pass) {
    var emailInput = document.querySelector('input[name="email_or_phone"]');
    var passInput = document.querySelector('input[name="password"]');
    if (emailInput) emailInput.value = user;
    if (passInput) passInput.value = pass;
}
</script>
@endpush
