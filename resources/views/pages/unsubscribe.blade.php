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
            <div class="pt-2 pb-1">
                <form action="{{ route('demand.delete-by-token', $demand->unsubscribe_token) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই চাহিদাটি সম্পূর্ণরূপে ডিলিট করতে চান? ডিলিট করলে আপনি নতুন চাহিদা জমা দেওয়ার সুযোগ পাবেন।');">
                    @csrf
                    <button type="submit" class="text-rose-600 hover:text-rose-700 text-xs font-bold underline cursor-pointer">
                        🗑️ এই চাহিদাটি সম্পূর্ণ ডিলিট করুন (কোটা খালি করুন)
                    </button>
                </form>
            </div>
        @else
            <p class="text-sm text-slate-600 leading-relaxed">
                আপনার প্রদত্ত টোকেনটি পাওয়া যায়নি অথবা ইতিমধ্যে আনসাবস্ক্রাইব করা হয়েছে।
            </p>
        @endif

        <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('chahida.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md inline-block">
                📋 চাহিদা পেজে যান
            </a>
            <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md inline-block">
                হোম পেজে ফিরে যান
            </a>
        </div>
    </div>
</div>
@endsection
