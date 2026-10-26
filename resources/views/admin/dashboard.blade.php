@extends('layouts.admin')

@section('title', 'ড্যাশবোর্ড ওভারভিউ')
@section('page_title', 'ড্যাশবোর্ড ওভারভিউ')

@section('content')
<div class="space-y-6">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">মোট প্রপার্টি</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_properties']) }}</div>
                <div class="text-xs text-emerald-600 font-medium mt-1">লাইভ: {{ number_format($stats['live_properties']) }} টি</div>
            </div>
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl">
                🏠
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">পেন্ডিং ভেরিফিকেশন</div>
                <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($stats['pending_verifications']) }}</div>
                <a href="{{ route('admin.verifications.index') }}" class="text-xs text-amber-700 hover:underline font-medium mt-1 inline-block">ভেরিফাই করুন &rarr;</a>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl">
                ⏳
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">মোট লিড ও চাহিদা</div>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ number_format($stats['total_leads']) }}</div>
                <div class="text-xs text-blue-600 font-medium mt-1">চাহিদা রিকোয়েস্ট: {{ number_format($stats['total_demands']) }}</div>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                🎯
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">০.৫% কমিশন আয়</div>
                <div class="text-2xl font-black text-purple-700 mt-1">৳ {{ number_format($stats['total_commission']) }}</div>
                <div class="text-xs text-slate-500 font-medium mt-1">ভলিউম: ৳ {{ number_format($stats['gross_volume']) }}</div>
            </div>
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl">
                💰
            </div>
        </div>
    </div>

    <!-- Quick Action Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-6 rounded-2xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="space-y-1 text-center md:text-left">
            <h3 class="text-lg font-bold text-white flex items-center gap-2 justify-center md:justify-start">
                <span>⚡</span> দ্রুত অ্যাকশন শর্টকাট
            </h3>
            <p class="text-xs text-slate-300">আফতাবনগর ডিরেক্ট কন্ট্রোল প্যানেল থেকে প্রয়োজনীয় কাজগুলো এক ক্লিকেই পরিচালনা করুন।</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('property.create') }}" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <span>➕</span> নতুন প্রপার্টি যোগ
            </a>
            <a href="{{ route('admin.verifications.index') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <span>✅</span> ভেরিফিকেশন ড্রাফট
            </a>
            <form action="{{ route('admin.settings.sync-sheet') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <span>🔄</span> গুগল শিট সিঙ্ক
                </button>
            </form>
            <a href="{{ route('admin.settings.index', ['tab' => 'notifications']) }}" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-slate-600">
                <span>🔔</span> নোটিফিকেশন সেটিংস
            </a>
        </div>
    </div>

    <!-- Recent Listings and Leads Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Properties -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                    <span>🏠</span> সাম্প্রতিক লিস্টিং
                </h3>
                <a href="{{ route('admin.properties.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">সব দেখুন &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentProperties as $p)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50/70 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0">
                                @if($p->media->isNotEmpty())
                                    <img src="{{ $p->media->first()->file_path }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xs text-slate-400">ছবি নাই</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('admin.properties.edit', $p) }}" class="text-xs font-bold text-slate-900 hover:text-emerald-600 truncate block">
                                    {{ $p->title }}
                                </a>
                                <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2">
                                    <span class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] font-mono">#{{ $p->property_code }}</span>
                                    <span>ব্লক: {{ $p->block ?? 'N/A' }}</span>
                                    <span class="font-semibold text-emerald-700">৳ {{ number_format($p->asking_price) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $p->status === 'live' ? 'bg-emerald-100 text-emerald-800' : ($p->status === 'sold' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ strtoupper($p->status) }}
                            </span>
                            <a href="{{ route('admin.properties.edit', $p) }}" class="text-slate-400 hover:text-slate-600 p-1">
                                ✏️
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">কোনো প্রপার্টি পাওয়া যায়নি।</div>
                @endforelse
            </div>
        </div>

        <!-- Recent CRM Leads -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                    <span>🎯</span> সাম্প্রতিক ইনকোয়ারি ও লিড
                </h3>
                <a href="{{ route('admin.leads.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">সব দেখুন &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentLeads as $lead)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50/70 transition">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-900">{{ $lead->buyer_name }}</span>
                                <span class="text-[10px] font-mono bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded">#{{ $lead->lead_code }}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-3">
                                <span>📞 {{ $lead->buyer_phone }}</span>
                                @if($lead->property)
                                    <span class="truncate">প্রপার্টি: #{{ $lead->property->property_code }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $lead->stage === 'closed' ? 'bg-emerald-100 text-emerald-800' : ($lead->stage === 'new' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ ucfirst($lead->stage) }}
                            </span>
                            <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $lead->buyer_phone) }}" target="_blank" class="p-1 text-emerald-600 hover:text-emerald-700" title="WhatsApp Message">
                                💬
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">কোনো নতুন লিড পাওয়া যায়নি।</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
