@extends('layouts.admin')

@section('page_title', 'প্রপার্টি গাইডস ও আর্টিকেল ম্যানেজমেন্ট')

@section('page_badge')
    <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full">
        মোট {{ $guides->total() }}টি গাইড
    </span>
@endsection

@section('content')
<div class="p-6 md:p-8 space-y-6"
     x-data="{
        openPublishModal: false,
        openSettingsModal: false,
        openSeedMenu: false,
        publishScope: '{{ $publishSettings['publish_scope'] }}',
        defaultStatus: '{{ $publishSettings['default_status'] }}',
        liveSiteUrl: '{{ $publishSettings['live_site_url'] }}',
        apiKey: '{{ $publishSettings['api_key'] }}',
        showApiKey: false,
        specificIds: {{ json_encode($publishSettings['specific_ids'] ?? []) }},
        searchSpecific: '',
        isSubmitting: false,
        toggleSpecificId(id) {
            id = parseInt(id);
            const idx = this.specificIds.indexOf(id);
            if (idx > -1) {
                this.specificIds.splice(idx, 1);
            } else {
                this.specificIds.push(id);
            }
        },
        selectAllSpecific() {
            this.specificIds = {{ json_encode($allGuidesList->pluck('id')->toArray()) }};
        },
        deselectAllSpecific() {
            this.specificIds = [];
        }
     }">

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

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                ফিল্টার
            </button>
            @if(request()->hasAny(['q', 'category', 'status']))
                <a href="{{ route('admin.guides.index') }}" class="px-3 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold rounded-xl transition">
                    রিসেট
                </a>
            @endif
        </form>

        <div class="flex items-center flex-wrap gap-2.5 shrink-0">

            <!-- 1. Live Site Publisher Button -->
            <button type="button"
                    @click="openPublishModal = true"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 hover:from-indigo-700 hover:to-purple-800 text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer active:scale-95"
                    title="লেটেস্ট ব্লগগুলো সরাসরি লাইভ সাইটে (propertykoi.com) পাবলিশ করুন">
                <svg class="w-4 h-4 text-purple-200 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>🚀 লাইভ সাইটে পাবলিশ</span>
                @if($targetGuidesCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white shadow-xs">
                        {{ $targetGuidesCount }} নতুন
                    </span>
                @endif
            </button>

            <!-- 2. Publisher Settings Button -->
            <button type="button"
                    @click="openSettingsModal = true"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition cursor-pointer"
                    title="পাবলিশ কনফিগারেশন ও ফিল্টার সেটিংস">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>সেটিংস</span>
            </button>

            <!-- 3. 1-Click Seeder Dropdown Button -->
            <div class="relative" @click.outside="openSeedMenu = false">
                <button type="button"
                        @click="openSeedMenu = !openSeedMenu"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 hover:from-emerald-700 hover:to-cyan-800 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
                        title="টার্মিনাল ছাড়া সরাসরি ব্রাউজার থেকে সিড রান করুন">
                    <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>⚡ ১-ক্লিক ডেটাবেজ সিড</span>
                    <svg class="w-3 h-3 text-white/80 transition-transform" :class="openSeedMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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

            <!-- 4. New Guide Button -->
            <a href="{{ route('admin.guides.create') }}" data-turbo="false" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition shrink-0">
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
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- 1-Click Publish this single guide to live site -->
                                    <form action="{{ route('admin.guides.publish-single-live', $guide) }}" method="POST" class="inline" onsubmit="return confirm('এই গাইডটি সরাসরি লাইভ সাইটে ({{ $publishSettings['live_site_url'] }}) পাবলিশ করবেন?')">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition cursor-pointer" title="এই গাইডটি সরাসরি লাইভ সাইটে পাবলিশ করুন">
                                            🚀
                                        </button>
                                    </form>

                                    <a href="{{ route('guides.show', $guide->slug) }}" target="_blank" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="প্রিভিউ দেখুন">
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

    <!-- ========================================== -->
    <!-- MODAL 1: LIVE SITE PUBLISH ACTION & MODAL -->
    <!-- ========================================== -->
    <div x-show="openPublishModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]"
             @click.outside="if(!isSubmitting) openPublishModal = false">

            <!-- Modal Header -->
            <div class="p-6 bg-gradient-to-r from-indigo-900 via-purple-900 to-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-xl">
                        🚀
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white m-0">গাইডস লাইভ সাইটে পাবলিশ (Push to Live)</h3>
                        <p class="text-xs text-indigo-200 m-0">টার্গেট লাইভ সাইট: <span class="font-mono text-amber-300 font-bold" x-text="liveSiteUrl"></span></p>
                    </div>
                </div>
                <button type="button" @click="openPublishModal = false" class="p-1 rounded-xl text-slate-400 hover:text-white transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-5 overflow-y-auto flex-1">

                <!-- Quick Scope Switcher -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">কোন ব্লগগুলো পাবলিশ করতে চান?</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                               :class="publishScope === 'latest_unpublished' ? 'bg-indigo-50/70 border-indigo-500 ring-1 ring-indigo-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <input type="radio" name="modal_scope" value="latest_unpublished" x-model="publishScope" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-800">লেটেস্ট অপ্রকাশিত</span>
                                <span class="block text-[11px] text-slate-500">যেগুলো এখনও পাবলিশ হয়নি (ডিফল্ট)</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                               :class="publishScope === 'all' ? 'bg-indigo-50/70 border-indigo-500 ring-1 ring-indigo-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <input type="radio" name="modal_scope" value="all" x-model="publishScope" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-800">সকল ব্লগ (All)</span>
                                <span class="block text-[11px] text-slate-500">মোট {{ $allGuidesList->count() }}টি ব্লগ লাইভে সিঙ্ক হবে</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                               :class="publishScope === 'specific' ? 'bg-indigo-50/70 border-indigo-500 ring-1 ring-indigo-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <input type="radio" name="modal_scope" value="specific" x-model="publishScope" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-800">নির্দিষ্ট ব্লগ</span>
                                <span class="block text-[11px] text-slate-500" x-text="specificIds.length + 'টি ব্লগ নির্বাচিত'"></span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Default Status Badge & Live Site Info -->
                <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl border border-slate-200 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500">লাইভে পাবলিশ স্ট্যাটাস:</span>
                        <span class="font-bold px-2 py-0.5 rounded-md"
                              :class="defaultStatus === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                              x-text="defaultStatus === 'published' ? '✅ সরাসরি প্রকাশিত (Published)' : '⏸️ ড্রাফট (Draft)'">
                        </span>
                    </div>
                    <button type="button" @click="openPublishModal = false; openSettingsModal = true" class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline">
                        ⚙️ সেটিংস পরিবর্তন
                    </button>
                </div>

                <!-- Preview of Target Blogs -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-700">টার্গেট ব্লগ প্রিভিউ:</span>
                        <span class="text-[11px] text-slate-500"
                              x-text="publishScope === 'latest_unpublished' ? '{{ $targetGuidesCount }}টি অপ্রকাশিত ব্লগ' : (publishScope === 'all' ? '{{ $allGuidesList->count() }}টি ব্লগ' : specificIds.length + 'টি নির্দিষ্ট ব্লগ')"></span>
                    </div>

                    <!-- Target blog items -->
                    <div class="border border-slate-200 rounded-2xl divide-y divide-slate-100 max-h-56 overflow-y-auto bg-white p-1">
                        @if($targetGuidesCount > 0)
                            @foreach($targetGuides as $tg)
                                <div class="p-2.5 flex items-center justify-between gap-3 text-xs"
                                     x-show="publishScope === 'latest_unpublished' || publishScope === 'all' || (publishScope === 'specific' && specificIds.includes({{ $tg->id }}))">
                                    <div class="min-w-0 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full shrink-0 {{ $tg->is_published ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        <span class="font-semibold text-slate-800 truncate">{{ $tg->title }}</span>
                                    </div>
                                    <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold {{ $tg->category_badge_class }}">
                                        {{ $tg->category_label }}
                                    </span>
                                </div>
                            @endforeach
                        @else
                            <div class="p-4 text-center text-xs text-slate-500" x-show="publishScope === 'latest_unpublished'">
                                ℹ️ এই মুহূর্তে কোনো অপ্রকাশিত/ড্রাফট ব্লগ নেই। আপনি চাইলে উপরের রেডিও থেকে <strong>"সকল ব্লগ"</strong> অথবা <strong>"নির্দিষ্ট ব্লগ"</strong> সিলেক্ট করে পুনরায় লাইভে সিঙ্ক করতে পারেন।
                            </div>
                        @endif

                        @foreach($allGuidesList as $ag)
                            @if($ag->is_published)
                                <div class="p-2.5 flex items-center justify-between gap-3 text-xs"
                                     x-show="publishScope === 'all' || (publishScope === 'specific' && specificIds.includes({{ $ag->id }}))">
                                    <div class="min-w-0 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full shrink-0 bg-emerald-500"></span>
                                        <span class="font-semibold text-slate-800 truncate">{{ $ag->title }}</span>
                                    </div>
                                    <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $ag->category_label }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="openPublishModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                    বাতিল
                </button>

                <form action="{{ route('admin.guides.publish-live') }}" method="POST" @submit="isSubmitting = true">
                    @csrf
                    <input type="hidden" name="scope" :value="publishScope">
                    <input type="hidden" name="default_status" :value="defaultStatus">
                    <input type="hidden" name="live_site_url" :value="liveSiteUrl">
                    <input type="hidden" name="api_key" :value="apiKey">

                    <template x-for="id in specificIds" :key="id">
                        <input type="hidden" name="specific_ids[]" :value="id">
                    </template>

                    <button type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold shadow-md transition cursor-pointer active:scale-95 disabled:opacity-50">
                        <svg x-show="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="isSubmitting ? 'পাবলিশ হচ্ছে...' : '🚀 এখনই লাইভে পাবলিশ করুন'"></span>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL 2: PUBLISHER CONFIGURATION & SETTINGS    -->
    <!-- ============================================== -->
    <div x-show="openSettingsModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden flex flex-col max-h-[92vh]"
             @click.outside="openSettingsModal = false">

            <!-- Modal Header -->
            <div class="p-6 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-xl">
                        ⚙️
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white m-0">গাইডস লাইভ পাবলিশ সেটিংস (Settings)</h3>
                        <p class="text-xs text-slate-300 m-0">কোন কোন ব্লগ, কোন স্ট্যাটাসে এবং কোন ডোমেইনে স্বয়ংক্রিয়ভাবে যাবে তা নির্ধারণ করুন।</p>
                    </div>
                </div>
                <button type="button" @click="openSettingsModal = false" class="p-1 rounded-xl text-slate-400 hover:text-white transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Settings Form -->
            <form action="{{ route('admin.guides.publish-settings') }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
                @csrf

                <div class="p-6 space-y-6 overflow-y-auto flex-1">

                    <!-- Field 1: Target Live Site URL -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            টার্গেট লাইভ সাইট URL (Target Live Domain)
                        </label>
                        <input type="url"
                               name="live_site_url"
                               x-model="liveSiteUrl"
                               required
                               placeholder="https://propertykoi.com"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500">
                        <span class="text-[11px] text-slate-400 mt-1 block">ডিফল্ট: <code>https://propertykoi.com</code></span>
                    </div>

                    <!-- Field 2: API Secret Key -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            API সিক্রেট কী (Live API Secret Token)
                        </label>
                        <div class="relative">
                            <input :type="showApiKey ? 'text' : 'password'"
                                   name="api_key"
                                   x-model="apiKey"
                                   placeholder="propertykoi_guides_sec_2026"
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500 pr-16 font-mono">
                            <button type="button"
                                    @click="showApiKey = !showApiKey"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] text-slate-500 hover:text-slate-800 font-bold px-1.5 py-0.5 rounded cursor-pointer">
                                <span x-text="showApiKey ? 'লুকান' : 'দেখুন'"></span>
                            </button>
                        </div>
                        <span class="text-[11px] text-slate-400 mt-1 block">ডিফল্ট টোকেন: <code>propertykoi_guides_sec_2026</code> (লাইভ সার্ভারের সাথে অথেনটিকেশন করবে)।</span>
                    </div>

                    <!-- Field 3: Publish Scope (Radio) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">
                            কোন ব্লগগুলো লাইভ সাইটে পাবলিশ হবে? (Publish Scope)
                        </label>
                        <div class="space-y-2">
                            <!-- Option A: Latest Unpublished (Default) -->
                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition"
                                   :class="publishScope === 'latest_unpublished' ? 'bg-indigo-50/70 border-indigo-500 ring-1 ring-indigo-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                                <input type="radio" name="publish_scope" value="latest_unpublished" x-model="publishScope" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900">লেটেস্ট / অপ্রকাশিত ব্লগসমূহ</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">ডিফল্ট প্রস্তাবিত</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">যে ব্লগগুলো এখনও ড্রাফট রয়েছে বা পূর্বে লাইভে পাবলিশ করা হয়নি, কেবল সেগুলোই ফিল্টার করে পাঠাবে।</span>
                                </div>
                            </label>

                            <!-- Option B: All Blogs -->
                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition"
                                   :class="publishScope === 'all' ? 'bg-indigo-50/70 border-indigo-500 ring-1 ring-indigo-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                                <input type="radio" name="publish_scope" value="all" x-model="publishScope" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">সকল ব্লগ (All Existing Guides)</span>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">সবগুলো বিদ্যমান গাইড (মোট {{ $allGuidesList->count() }}টি) একসাথে লাইভ সাইটে আপলোড ও সিনক্রোনাইজ হবে।</span>
                                </div>
                            </label>

                            <!-- Option C: Specific Blogs -->
                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition"
                                   :class="publishScope === 'specific' ? 'bg-indigo-50/70 border-indigo-500 ring-1 ring-indigo-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                                <input type="radio" name="publish_scope" value="specific" x-model="publishScope" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">নির্দিষ্ট কিছু ব্লগ (Specific Selection)</span>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">নিচের তালিকা থেকে আপনি যে ব্লগগুলো ম্যানুয়ালি টিক দেবেন, শুধুমাত্র সেগুলোই যাবে।</span>
                                </div>
                            </label>
                        </div>

                        <!-- Specific Blogs Interactive Checklist -->
                        <div x-show="publishScope === 'specific'" class="mt-3 p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3" style="display: none;">
                            <div class="flex items-center justify-between gap-2">
                                <input type="text"
                                       x-model="searchSpecific"
                                       placeholder="নির্দিষ্ট ব্লগ খুঁজুন..."
                                       class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white flex-1 focus:outline-hidden focus:border-indigo-500">
                                <div class="flex items-center gap-1.5 shrink-0 text-[11px]">
                                    <button type="button" @click="selectAllSpecific()" class="px-2 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg font-bold text-slate-700 cursor-pointer">সব সিলেক্ট</button>
                                    <button type="button" @click="deselectAllSpecific()" class="px-2 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg font-bold text-slate-700 cursor-pointer">ক্লিয়ার</button>
                                </div>
                            </div>

                            <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 bg-white rounded-xl border border-slate-200 p-1">
                                @foreach($allGuidesList as $guideItem)
                                    <label class="flex items-center justify-between p-2 hover:bg-slate-50 rounded-lg cursor-pointer text-xs"
                                           x-show="!searchSpecific || '{{ strtolower(addslashes($guideItem->title)) }}'.includes(searchSpecific.toLowerCase())">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <input type="checkbox"
                                                   name="specific_ids[]"
                                                   value="{{ $guideItem->id }}"
                                                   :checked="specificIds.includes({{ $guideItem->id }})"
                                                   @change="toggleSpecificId({{ $guideItem->id }})"
                                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                                            <span class="font-medium text-slate-800 truncate">{{ $guideItem->title }}</span>
                                        </div>
                                        <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded {{ $guideItem->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $guideItem->is_published ? 'প্রকাশিত' : 'ড্রাফট' }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Field 4: Default Publish Status (Radio) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">
                            ডিফল্ট পাবলিশ স্ট্যাটাস কী হবে? (Default Publish Status)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition"
                                   :class="defaultStatus === 'published' ? 'bg-emerald-50/70 border-emerald-500 ring-1 ring-emerald-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                                <input type="radio" name="default_status" value="published" x-model="defaultStatus" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900">সরাসরি প্রকাশিত (Published)</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">ডিফল্ট</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">লাইভ সাইটে আপলোড হওয়ামাত্রই পাঠকরা সরাসরি পড়তে পারবেন।</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition"
                                   :class="defaultStatus === 'draft' ? 'bg-amber-50/70 border-amber-500 ring-1 ring-amber-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                                <input type="radio" name="default_status" value="draft" x-model="defaultStatus" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">ড্রাফট (Draft)</span>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">লাইভ সাইটে জমা হবে কিন্তু ড্রাফট মোডে থাকবে, অ্যাডমিন চাইলে পরে প্রকাশ করতে পারবেন।</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Sync Report info if available -->
                    @if(!empty($publishSettings['last_publish_time']))
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800">সর্বশেষ লাইভ পাবলিশ:</span>
                                <span>{{ $publishSettings['last_publish_time'] }}</span>
                            </div>
                            @if(!empty($publishSettings['last_publish_report']['message']))
                                <span class="text-[11px] text-emerald-700 font-semibold">{{ $publishSettings['last_publish_report']['message'] }}</span>
                            @endif
                        </div>
                    @endif

                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" @click="openSettingsModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">
                        বাতিল
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-md transition cursor-pointer">
                        💾 সেটিংস সংরক্ষণ করুন
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
