@extends('layouts.app')

@section('title', 'গুগল সাইন-ইন - PropertyKoi')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 p-8 shadow-xl space-y-6 text-center">
        
        <div class="w-16 h-16 bg-red-50 text-red-600 rounded-3xl mx-auto flex items-center justify-center text-3xl shadow-sm border border-red-100">
            <svg class="w-8 h-8" viewBox="0 0 24 24">
                <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.8 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
                <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
                <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 12.3 0 15s.7 5.3 1.9 7.7l3.7-2.9z"/>
                <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2-6.4-4.8L1.9 16.4C3.7 20.4 7.5 23 12 23z"/>
            </svg>
        </div>

        <div>
            <h2 class="text-xl font-black text-slate-900">গুগল সাইন-ইন (Google OAuth)</h2>
            <p class="text-xs text-slate-500 mt-1">
                লাইভ মোডের জন্য <code class="bg-slate-100 px-1 py-0.5 rounded font-mono text-emerald-700">.env</code> ফাইলে <code class="font-mono">GOOGLE_CLIENT_ID</code> ও <code class="font-mono">GOOGLE_CLIENT_SECRET</code> যুক্ত করুন।
            </p>
        </div>

        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-xs text-emerald-800 text-left space-y-2">
            <div class="font-bold flex items-center gap-1.5">
                <span>⚡</span> লোকালহোস্ট টেস্ট সিমুলেশন:
            </div>
            <p class="text-[11px] leading-relaxed text-emerald-700 m-0">
                আপনি লোকালহোস্টে টেস্ট করার জন্য নিচের বাটনে ক্লিক করে সরাসরি গুগল ভেরিফাইড ক্রেতা অ্যাকাউন্টে প্রবেশ করতে পারেন।
            </p>
        </div>

        <form action="{{ route('auth.google.mock') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <input type="email" name="mock_email" value="mithu.buyer@gmail.com" required placeholder="টেস্ট গুগল ইমেইল" class="w-full text-xs p-3 border rounded-xl font-medium focus:ring-2 focus:ring-emerald-500">
            </div>
            <button type="submit" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2">
                <span>🚀 ১-ক্লিকে গুগল সাইন-ইন দিয়ে টেস্ট করুন &rarr;</span>
            </button>
        </form>

        <div class="pt-2">
            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-slate-800 font-bold">
                &larr; সাধারণ লগইনে ফিরে যান
            </a>
        </div>

    </div>
</div>
@endsection
