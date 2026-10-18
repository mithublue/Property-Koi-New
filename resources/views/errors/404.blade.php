@extends('layouts.app')

@section('title', '৪০৪ - পেজটি খুঁজে পাওয়া যায়নি | PropertyKoi')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4 py-16">
    <div class="max-w-2xl w-full text-center space-y-8">
        
        <!-- Animated 404 Visual Illustration -->
        <div class="relative mx-auto w-48 h-48 sm:w-56 sm:h-56 flex items-center justify-center">
            <div class="absolute inset-0 bg-gradient-to-tr from-emerald-200/50 via-teal-100/40 to-slate-100 rounded-full blur-2xl -z-10 animate-pulse"></div>
            <div class="w-full h-full rounded-3xl bg-white border border-slate-200/80 shadow-xl flex flex-col items-center justify-center p-6 space-y-2">
                <span class="text-5xl sm:text-6xl select-none">🧭</span>
                <span class="text-4xl sm:text-5xl font-black text-slate-800 tracking-tight font-mono">404</span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">Page Not Found</span>
            </div>
        </div>

        <!-- Headings & Message -->
        <div class="space-y-3">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">
                দুঃখিত! এই পেজটি খুঁজে পাওয়া যায়নি
            </h1>
            <p class="text-sm sm:text-base text-slate-600 max-w-lg mx-auto leading-relaxed">
                আপনি যে লিংকটিতে প্রবেশ করার চেষ্টা করছেন তা হয়তো ভুল, পরিবর্তন করা হয়েছে অথবা মুছে ফেলা হয়েছে।
            </p>
        </div>

        <!-- Quick Search Bar -->
        <div class="max-w-md mx-auto">
            <form action="{{ route('properties.index') }}" method="GET" class="relative">
                <input type="text"
                       name="q"
                       placeholder="প্রপার্টি বা এলাকা খুঁজুন (যেমন: আফতাবনগর, প্লট, ফ্ল্যাট)..."
                       class="w-full pl-4 pr-24 py-3.5 bg-white rounded-2xl border-2 border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 shadow-sm">
                <button type="submit"
                        class="absolute right-1.5 top-1.5 bottom-1.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>খুঁজুন</span>
                </button>
            </form>
        </div>

        <!-- Primary Action Buttons -->
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            <a href="{{ route('home') }}"
               class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
                <span>🏠</span>
                <span>হোমপেজে ফিরে যান</span>
            </a>
            <a href="{{ route('properties.index') }}"
               class="px-6 py-3 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm rounded-xl border border-slate-200 shadow-2xs transition flex items-center gap-2">
                <span>🔍</span>
                <span>সব প্রপার্টি দেখুন</span>
            </a>
            <a href="{{ route('chahida.index') }}"
               class="px-6 py-3 bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold text-xs sm:text-sm rounded-xl border border-teal-200 transition flex items-center gap-2">
                <span>📋</span>
                <span>চাহিদা জমা দিন</span>
            </a>
        </div>

        <!-- Popular Links Quick Grid -->
        <div class="pt-6 border-t border-slate-200 max-w-lg mx-auto">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">জনপ্রিয় ক্যাটাগরিগুলো</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <a href="{{ route('properties.land') }}" class="p-2.5 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl font-semibold text-slate-700 border border-slate-200/80 transition">
                    🌾 জমি ও প্লট
                </a>
                <a href="{{ route('properties.flat') }}" class="p-2.5 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl font-semibold text-slate-700 border border-slate-200/80 transition">
                    🏢 রেডি ফ্ল্যাট
                </a>
                <a href="{{ route('properties.share') }}" class="p-2.5 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl font-semibold text-slate-700 border border-slate-200/80 transition">
                    🤝 ল্যান্ড শেয়ার
                </a>
                <a href="{{ route('properties.commercial') }}" class="p-2.5 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl font-semibold text-slate-700 border border-slate-200/80 transition">
                    🏬 কমার্শিয়াল
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
