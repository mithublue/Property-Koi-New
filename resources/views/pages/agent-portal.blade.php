@extends('layouts.app')

@section('title', 'এজেন্ট ও মালিক পোর্টাল - PropertyKoi')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6"
     x-data="{ showPolicyModal: false }">

    <!-- Profile Header Card -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white p-6 md:p-8 rounded-3xl shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4 text-center md:text-left">
            <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-bold text-2xl flex-shrink-0">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div>
                <div class="flex items-center gap-2 justify-center md:justify-start">
                    <h1 class="text-xl font-bold">{{ $user->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500 text-slate-900 uppercase">
                        {{ $user->role === 'agent' ? 'ভেরিফাইড এজেন্ট' : 'সরাসরি মালিক' }}
                    </span>
                </div>
                <div class="text-xs text-slate-300 mt-1 flex flex-wrap items-center gap-3 justify-center md:justify-start">
                    <span>📞 {{ $user->phone }}</span>
                    <span>✉️ {{ $user->email }}</span>
                    <span>⭐ রেটিং: <strong>5.0 / 5.0</strong></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap justify-center md:justify-end">
            <button type="button" @click="showPolicyModal = true" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                <span>📜</span> চার্জ পলিসি ও শর্তাবলী
            </button>
            <a href="{{ route('property.create') }}" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-900 font-bold text-xs rounded-xl transition shadow-lg flex items-center gap-1.5">
                <span>➕</span> নতুন লিস্টিং পোস্ট করুন
            </a>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition">
                    লগআউট
                </button>
            </form>
        </div>
    </div>

    <!-- Quick 1-Click Magic Login URL Notification -->
    @if($magicUrl)
        <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 text-indigo-900">
                <span>🔑</span>
                <span><strong>মোবাইল অটো-লগইন লিঙ্ক:</strong> পাসওয়ার্ড ছাড়াই যেকোনো ডিভাইস থেকে ১-ক্লিকে লগইন করতে এই লিঙ্কটি বুকমার্ক রাখুন।</span>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $magicUrl }}'); alert('ম্যাজিক লিঙ্ক কপি হয়েছে!');" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition shadow-sm flex-shrink-0">
                📋 লিঙ্ক কপি করুন
            </button>
        </div>
    @endif

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">আমার মোট লিস্টিং</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $stats['my_properties'] }} টি</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">লাইভ ও প্রদর্শিত</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['live_properties'] }} টি</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">প্রাপ্ত গ্রাহক ইনকোয়ারি</div>
            <div class="text-2xl font-black text-blue-600 mt-1">{{ $stats['total_leads'] }} টি</div>
        </div>
    </div>

    <!-- My Properties Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-900">আমার সাবমিট করা প্রপার্টিসমূহ</h3>
            <a href="{{ route('property.create') }}" class="text-xs font-semibold text-emerald-600 hover:underline">+ নতুন পোস্ট</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-3.5">প্রপার্টি</th>
                        <th class="p-3.5">লোকেশন</th>
                        <th class="p-3.5">মূল্য</th>
                        <th class="p-3.5">স্ট্যাটাস</th>
                        <th class="p-3.5 text-center">ভিউ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($properties as $p)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-3.5">
                                <div class="font-bold text-slate-900 line-clamp-1">{{ $p->title }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">#{{ $p->property_code }}</div>
                            </td>
                            <td class="p-3.5">
                                <div>আফতাবনগর, ব্লক: {{ $p->block ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500">রোড: {{ $p->road_number ?? 'N/A' }}</div>
                            </td>
                            <td class="p-3.5 font-bold text-emerald-700">
                                ৳ {{ number_format($p->asking_price) }}
                            </td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] {{ $p->status === 'live' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ strtoupper($p->status) }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center">
                                <a href="{{ route('property.show', $p->slug) }}" target="_blank" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition">
                                    পেজ দেখুন
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-xs text-slate-400">আপনি এখনো কোনো প্রপার্টি পোস্ট করেননি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $properties->links() }}
        </div>
    </div>

    <!-- Policy & Terms Modal (Connected to Settings Tab 5 adm_agent_policy_notice) -->
    <div x-show="showPolicyModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="showPolicyModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-5 shadow-2xl relative border border-slate-200"
             @click.outside="showPolicyModal = false">
            <button @click="showPolicyModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 text-2xl font-bold cursor-pointer">&times;</button>
            
            <div class="flex items-center gap-3 border-b pb-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-bold shrink-0">📜</div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-lg m-0">এজেন্ট ও ওনার চার্জ পলিসি এবং নীতিমালা</h3>
                    <p class="text-xs text-slate-500 m-0 mt-0.5">আফতাবনগর ডিরেক্ট মার্কেটপ্লেস পার্টনার গাইডলাইন</p>
                </div>
            </div>

            <div class="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-700 leading-relaxed max-h-80 overflow-y-auto whitespace-pre-line space-y-2">
                {!! nl2br(e(\App\Models\Setting::get('adm_agent_policy_notice', "স্বাগতম! আফতাবনগর ডিরেক্ট মার্কেটপ্লেসে আপনার সম্পত্তি তালিকাভুক্ত করার পূর্বে আমাদের কিছু গুরুত্বপূর্ণ নীতিমালা ও চার্জ পলিসি জেনে নিন:\n\n১. সাকসেস ফি ও সার্ভিস চার্জ:\n   • জমি বা ফ্ল্যাট চূড়ান্ত বিক্রয় সম্পন্ন হলে সফল ডিলের উপর নির্ধারিত ১% প্ল্যাটফর্ম কোঅর্ডিনেশন ফি প্রযোজ্য হবে।\n\n২. ডিরেক্ট সোর্স ও স্বচ্ছতা:\n   • প্ল্যাটফর্মে কোনো ভুয়া বা অসত্য তথ্য প্রদান করা যাবে না।\n\n৩. চেইন ব্রোকার ও বাইপাস নিষেধাজ্ঞা:\n   • গোপনে যোগাযোগ করে প্ল্যাটফর্ম বাইপাস করার চেষ্টা করলে আইডি স্থায়ীভাবে পেনাল্টি ফ্ল্যাগ করা হবে।\n\n৪. হোয়াটসঅ্যাপ ও ফোন ভেরিফিকেশন:\n   • জরুরি প্রয়োজনে আমাদের ভেরিফিকেশন টিম আপনার সাথে যোগাযোগ করবে।"))) !!}
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-[11px] text-slate-400">
                    🔒 প্ল্যাটফর্ম স্বচ্ছতা ও জিরো-স্প্যাম সুরক্ষার জন্য এই নীতিমালা প্রযোজ্য।
                </div>
                <button type="button" @click="showPolicyModal = false" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-lg transition cursor-pointer">
                    ✓ আমি বুঝতে পেরেছি ও সম্মত
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
