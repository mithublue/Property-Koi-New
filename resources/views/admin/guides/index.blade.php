@extends('layouts.admin')

@section('page_title', 'প্রপার্টি গাইডস ও আর্টিকেল ম্যানেজমেন্ট')

@section('page_badge')
    <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full">
        মোট {{ $guides->total() }}টি গাইড
    </span>
@endsection

@section('content')
<div class="p-6 md:p-8 space-y-6">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <!-- Filter and Search Form -->
        <form action="{{ route('admin.guides.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 flex-1">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="গাইড শিরোনাম খুঁজুন..."
                   class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-emerald-500 w-full sm:w-60">

            <select name="category" class="px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden bg-white text-slate-700">
                <option value="">সকল ক্যাটাগরি</option>
                <option value="legal_docs" {{ request('category') === 'legal_docs' ? 'selected' : '' }}>দলিল ও আইনি যাচাই</option>
                <option value="market_insights" {{ request('category') === 'market_insights' ? 'selected' : '' }}>মার্কেট অ্যানালাইসিস</option>
                <option value="buying_guides" {{ request('category') === 'buying_guides' ? 'selected' : '' }}>ক্রয়-বিক্রয় গাইডলাইন</option>
                <option value="investment" {{ request('category') === 'investment' ? 'selected' : '' }}>বিনিয়োগ ও ল্যান্ড শেয়ার</option>
            </select>

            <select name="status" class="px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden bg-white text-slate-700">
                <option value="">সকল স্ট্যাটাস</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>প্রকাশিত (Published)</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>ড্রাফট (Draft)</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition">
                ফিল্টার
            </button>
            @if(request()->hasAny(['q', 'category', 'status']))
                <a href="{{ route('admin.guides.index') }}" class="px-3 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold rounded-xl transition">
                    রিসেট
                </a>
            @endif
        </form>

        <div class="flex items-center flex-wrap gap-2.5 shrink-0" x-data="{ openSeedMenu: false }" @click.outside="openSeedMenu = false">
            <!-- 1-Click Seeder Dropdown Button (Runs PHP 8.3 via Web Request) -->
            <div class="relative">
                <button type="button"
                        @click="openSeedMenu = !openSeedMenu"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 hover:from-emerald-700 hover:to-cyan-800 text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer active:scale-95"
                        title="টার্মিনাল ছাড়া সরাসরি ব্রাউজার থেকে সিড রান করুন">
                    <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>⚡ ১-ক্লিক ডেটাবেজ সিড</span>
                    <svg class="w-3.5 h-3.5 text-white/80 transition-transform" :class="openSeedMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <!-- Dropdown items -->
                <div x-show="openSeedMenu"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                     class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 z-50 divide-y divide-slate-100"
                     style="display: none;">
                    <div class="p-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">ওয়ান-ক্লিক সিডার (Web / FPM)</span>
                        <p class="text-[11px] text-slate-500 leading-normal m-0">টার্মিনালে না ঢুকে ওয়েব সার্ভার থেকেই ১-ক্লিকে সিড রান করুন।</p>
                    </div>
                    <div class="py-1 space-y-1">
                        <form action="{{ route('admin.guides.seed') }}" method="POST" onsubmit="return confirm('১০টি এআই ও এসইও অপ্টিমাইজড নলেজ হাব গাইড সিড করবেন? বর্তমান ডেটা সুরক্ষিত থাকবে।')" class="m-0">
                            @csrf
                            <input type="hidden" name="target" value="guides">
                            <button type="submit" class="w-full text-left px-3 py-2.5 rounded-xl hover:bg-emerald-50 text-slate-800 hover:text-emerald-800 transition flex items-start gap-2.5 cursor-pointer">
                                <span class="text-base leading-none">📚</span>
                                <div>
                                    <span class="font-bold text-xs block leading-tight text-emerald-800">১০টি এআই গাইড সিড / সিঙ্ক</span>
                                    <span class="text-[10px] text-slate-400">GuideSeeder (আইন, দলিল, রেট)</span>
                                </div>
                            </button>
                        </form>

                        <form action="{{ route('admin.guides.seed') }}" method="POST" onsubmit="return confirm('সম্পূর্ণ ডেটাবেজ (ইউজার, প্রপার্টি ও গাইডস) সিড করবেন?')" class="m-0">
                            @csrf
                            <input type="hidden" name="target" value="all">
                            <button type="submit" class="w-full text-left px-3 py-2.5 rounded-xl hover:bg-slate-100 text-slate-800 hover:text-slate-900 transition flex items-start gap-2.5 cursor-pointer">
                                <span class="text-base leading-none">🗄️</span>
                                <div>
                                    <span class="font-bold text-xs block leading-tight">সম্পূর্ণ ডেটাবেজ সিড করুন</span>
                                    <span class="text-[10px] text-slate-400">DatabaseSeeder (Full Demo)</span>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- New Guide Button -->
            <a href="{{ route('admin.guides.create') }}" data-turbo="false" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>নতুন গাইড লিখুন</span>
            </a>
        </div>
    </div>

    <!-- Guides Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="p-4">শিরোনাম ও ক্যাটাগরি</th>
                        <th class="p-4">ভিউ / পাঠক</th>
                        <th class="p-4">সময় / তারিখ</th>
                        <th class="p-4">স্ট্যাটাস</th>
                        <th class="p-4 text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($guides as $guide)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                                        <img src="{{ $guide->cover_url }}" alt="" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $guide->category_badge_class }}">
                                                {{ $guide->category_label }}
                                            </span>
                                            @if($guide->is_featured)
                                                <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                    ⭐ ফিচার্ড
                                                </span>
                                            @endif
                                        </div>
                                        <a href="{{ route('admin.guides.edit', $guide) }}" data-turbo="false" class="font-bold text-slate-900 hover:text-emerald-600 transition text-sm line-clamp-1">
                                            {{ $guide->title }}
                                        </a>
                                        <span class="text-[11px] text-slate-400 block truncate max-w-md">/guides/{{ $guide->slug }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ number_format($guide->views_count) }}</span>
                                <span class="text-[10px] text-slate-400">বার পঠিত</span>
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-700 block">{{ $guide->formatted_date }}</span>
                                <span class="text-[10px] text-slate-400">⏱️ {{ $guide->reading_time_min }} মিনিট পাঠ</span>
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <form action="{{ route('admin.guides.toggle-published', $guide) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[11px] font-bold border transition cursor-pointer {{ $guide->is_published ? 'bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200' }}">
                                        {{ $guide->is_published ? '✅ প্রকাশিত' : '⏸️ ড্রাফট' }}
                                    </button>
                                </form>
                            </td>
                            <td class="p-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('guides.show', $guide->slug) }}" target="_blank" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="লাইভ দেখুন">
                                        🌐
                                    </a>
                                    <a href="{{ route('admin.guides.edit', $guide) }}" data-turbo="false" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 font-bold transition text-xs">
                                        এডিট
                                    </a>
                                    <form action="{{ route('admin.guides.destroy', $guide) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই গাইডটি মুছে ফেলতে চান?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition cursor-pointer" title="মুছে ফেলুন">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500">
                                কোনো গাইড পাওয়া যায়নি। নতুন গাইড তৈরি করতে উপরের বাটনে ক্লিক করুন।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($guides->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $guides->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
