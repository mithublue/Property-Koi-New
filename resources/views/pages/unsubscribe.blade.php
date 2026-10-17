@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto px-4 py-16 text-center space-y-6">
    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-2xl mx-auto shadow-inner">
        🔕
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-md space-y-4">
        <h1 class="text-2xl font-black text-slate-900">
            নোটিফিকেশন সফলভাবে বন্ধ করা হয়েছে
        </h1>

        @if($demand)
            <p class="text-sm text-slate-600 leading-relaxed">
                আপনার চাহিদা <strong>{{ $demand->requirement_code }}</strong>-এর জন্য আর কোনো অ্যালার্ট বা ইমেইল পাঠানো হবে না।
            </p>
        @else
            <p class="text-sm text-slate-600 leading-relaxed">
                আপনার প্রদত্ত টোকেনটি পাওয়া যায়নি অথবা ইতিমধ্যে আনসাবস্ক্রাইব করা হয়েছে।
            </p>
        @endif

        <div class="pt-4">
            <a href="{{ route('home') }}" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md inline-block">
                হোম পেজে ফিরে যান
            </a>
        </div>
    </div>
</div>
@endsection
