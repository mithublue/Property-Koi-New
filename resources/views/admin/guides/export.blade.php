@extends('layouts.admin')

@section('page_title', 'নলেজ হাব ও প্রপার্টি গাইডস PDF এক্সপোর্টার')

@section('page_badge')
    <span class="text-xs bg-indigo-100 text-indigo-800 font-bold px-2.5 py-1 rounded-full flex items-center gap-1">
        <span>📑</span> মোট {{ $totalAllCount }}টি গাইড সংরক্ষিত
    </span>
@endsection

@section('content')
<div class="p-6 md:p-8 space-y-6"
     x-data="{
        exportScope: '{{ count($preselectedIds) > 0 ? 'selected' : 'all' }}',
        selectedIds: {{ json_encode($preselectedIds) }},
        availableIds: {{ json_encode($guides->pluck('id')->toArray()) }},
        publishedFrom: '{{ request('published_from') }}',
        publishedTo: '{{ request('published_to') }}',
        toggleSelectAll() {
            if (this.selectedIds.length === this.availableIds.length) {
                this.selectedIds = [];
            } else {
                this.selectedIds = [...this.availableIds];
            }
        },
        toggleId(id) {
            id = parseInt(id);
            const index = this.selectedIds.indexOf(id);
            if (index > -1) {
                this.selectedIds.splice(index, 1);
            } else {
                this.selectedIds.push(id);
            }
        },
        selectAll() {
            this.selectedIds = [...this.availableIds];
        },
        deselectAll() {
            this.selectedIds = [];
        },
        setDateRange(preset) {
            const today = new Date();
            const formatDate = (d) => d.toISOString().split('T')[0];
            
            if (preset === 'today') {
                this.publishedFrom = formatDate(today);
                this.publishedTo = formatDate(today);
            } else if (preset === 'week') {
                const lastWeek = new Date(today);
                lastWeek.setDate(today.getDate() - 7);
                this.publishedFrom = formatDate(lastWeek);
                this.publishedTo = formatDate(today);
            } else if (preset === 'month') {
                const lastMonth = new Date(today);
                lastMonth.setDate(today.getDate() - 30);
                this.publishedFrom = formatDate(lastMonth);
                this.publishedTo = formatDate(today);
            } else if (preset === 'year') {
                const startOfYear = new Date(today.getFullYear(), 0, 1);
                this.publishedFrom = formatDate(startOfYear);
                this.publishedTo = formatDate(today);
            } else if (preset === 'all') {
                this.publishedFrom = '';
                this.publishedTo = '';
            }
            document.getElementById('filter-form').submit();
        }
     }">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-600">ড্যাশবোর্ড</a>
            <span>/</span>
            <a href="{{ route('admin.guides.index') }}" class="hover:text-emerald-600">গাইডস ও আর্টিকেলস</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">PDF এক্সপোর্টার</span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.guides.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition inline-flex items-center gap-1.5">
                <span>&larr; গাইড তালিকায় ফিরুন</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2">
            <span>⚠️</span> <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 1. Date Range & Search Filter Card (Independent Filter Form) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>📅</span> ধাপ ১: প্রকাশিত তারিখ রেঞ্জ ও ক্যাটাগরি ফিল্টার
            </h2>
            <span class="text-[11px] text-slate-400 font-semibold">ফিল্টার অনুযায়ী গাইড তালিকা লোড হবে</span>
        </div>

        <form id="filter-form" action="{{ route('admin.guides.export') }}" method="GET" class="space-y-4">
            <!-- Quick Date Range Presets -->
            <div class="flex items-center gap-1.5 flex-wrap text-xs">
                <span class="text-slate-400 font-bold mr-1 text-[11px]">তারিখ প্রিসেট:</span>
                <button type="button" @click="setDateRange('today')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition cursor-pointer">আজ</button>
                <button type="button" @click="setDateRange('week')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition cursor-pointer">গত ৭ দিন</button>
                <button type="button" @click="setDateRange('month')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition cursor-pointer">গত ৩০ দিন</button>
                <button type="button" @click="setDateRange('year')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition cursor-pointer">এই বছর (২০২৬)</button>
                <button type="button" @click="setDateRange('all')" class="px-2.5 py-1 rounded-lg bg-slate-800 text-white text-[11px] font-bold hover:bg-slate-900 transition cursor-pointer">সব সময় (All)</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">হতে (From Date)</label>
                    <input type="date"
                           name="published_from"
                           x-model="publishedFrom"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500 bg-white">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">পর্যন্ত (To Date)</label>
                    <input type="date"
                           name="published_to"
                           x-model="publishedTo"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500 bg-white">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">ক্যাটাগরি</label>
                    <select name="category" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden bg-white text-slate-700">
                        <option value="">সকল ক্যাটাগরি</option>
                        @foreach($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" {{ request('category') === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">স্ট্যাটাস</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden bg-white text-slate-700">
                        <option value="">সকল স্ট্যাটাস</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>শুধু প্রকাশিত (Published)</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>শুধু ড্রাফট (Draft)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <div class="flex-1 max-w-sm">
                    <input type="text"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="গাইড শিরোনাম বা কি-ওয়ার্ড দিয়ে ফিল্টার..."
                           class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-xs">
                        <span>🔍 ফিল্টার প্রয়োগ করুন</span>
                    </button>
                    @if(request()->hasAny(['published_from', 'published_to', 'category', 'status', 'q']))
                        <a href="{{ route('admin.guides.export') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition">
                            রিসেট
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- ======================================================== -->
    <!-- 2. MAIN EXPORT FORM (WRAPS BOTH LEFT & RIGHT COLUMNS)    -->
    <!-- ======================================================== -->
    <form id="export-form"
          action="{{ route('admin.guides.export.generate') }}"
          method="POST"
          target="_blank">
        @csrf
        <input type="hidden" name="published_from" :value="publishedFrom">
        <input type="hidden" name="published_to" :value="publishedTo">
        <input type="hidden" name="category" value="{{ request('category') }}">
        <input type="hidden" name="status" value="{{ request('status') }}">

        <!-- Dynamic Hidden Inputs for Selected IDs to guarantee 100% submission -->
        <template x-for="id in selectedIds" :key="'post-id-' + id">
            <input type="hidden" name="ids[]" :value="id">
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left Column: Guide Selection List (8 cols) -->
            <div class="lg:col-span-8 space-y-6">

                <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-6 space-y-5">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                                <span>📋</span> ধাপ ২: এক্সপোর্টের আওতা ও নির্দিষ্ট গাইড নির্বাচন
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5 m-0">সবগুলো গাইড একত্রে অথবা নির্দিষ্ট কিছু গাইড টিক দিয়ে এক্সপোর্ট করতে পারেন।</p>
                        </div>

                        <!-- Scope Switcher Radio Pill -->
                        <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 text-xs font-bold shrink-0">
                            <label class="px-3 py-1.5 rounded-lg cursor-pointer transition flex items-center gap-1.5"
                                   :class="exportScope === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                <input type="radio" name="export_scope" value="all" x-model="exportScope" class="hidden">
                                <span>🌐 সকল গাইড ({{ $guides->count() }}টি)</span>
                            </label>
                            <label class="px-3 py-1.5 rounded-lg cursor-pointer transition flex items-center gap-1.5"
                                   :class="exportScope === 'selected' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                <input type="radio" name="export_scope" value="selected" x-model="exportScope" class="hidden">
                                <span>✅ নির্বাচিত গাইড (<span x-text="selectedIds.length"></span>)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Selection Control Bar (Active when scope is selected) -->
                    <div x-show="exportScope === 'selected'"
                         x-transition
                         class="flex items-center justify-between text-xs bg-indigo-50/70 p-3 rounded-2xl border border-indigo-100">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAll()" class="px-3 py-1 rounded-lg bg-white border border-indigo-200 text-indigo-700 font-bold hover:bg-indigo-100/50 transition cursor-pointer">
                                সব টিক দিন (Select All)
                            </button>
                            <button type="button" @click="deselectAll()" class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition cursor-pointer">
                                সব বাতিল (Deselect)
                            </button>
                        </div>
                        <div class="font-bold text-indigo-900">
                            মোট <span class="text-indigo-600 font-black text-sm" x-text="selectedIds.length"></span>টি গাইড নির্বাচিত হয়েছে
                        </div>
                    </div>

                    <!-- Guides Checklist Table -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden max-h-[520px] overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="p-3 w-12 text-center" x-show="exportScope === 'selected'">
                                        <input type="checkbox"
                                               @change="toggleSelectAll()"
                                               :checked="selectedIds.length === availableIds.length && availableIds.length > 0"
                                               class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    </th>
                                    <th class="p-3">গাইড শিরোনাম ও বিষয়</th>
                                    <th class="p-3 whitespace-nowrap">ক্যাটাগরি</th>
                                    <th class="p-3 whitespace-nowrap">প্রকাশিত তারিখ</th>
                                    <th class="p-3 whitespace-nowrap text-center">স্ট্যাটাস</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($guides as $guide)
                                    <tr class="hover:bg-slate-50/80 transition cursor-pointer select-none"
                                        @click="if (exportScope === 'selected') toggleId({{ $guide->id }})"
                                        :class="selectedIds.includes({{ $guide->id }}) && exportScope === 'selected' ? 'bg-indigo-50/40' : ''">
                                        <td class="p-3 text-center" x-show="exportScope === 'selected'" @click.stop>
                                            <input type="checkbox"
                                                   :checked="selectedIds.includes({{ $guide->id }})"
                                                   @change="toggleId({{ $guide->id }})"
                                                   class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                        </td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-900 leading-snug line-clamp-2">
                                                {{ $guide->title }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                ⏱️ {{ $guide->reading_time_min }} মিনিট পাঠ • {{ number_format($guide->views_count) }} পাঠক
                                            </div>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $guide->category_badge_class }}">
                                                {{ $guide->category_label }}
                                            </span>
                                        </td>
                                        <td class="p-3 whitespace-nowrap font-medium text-slate-600">
                                            {{ $guide->formatted_date }}
                                        </td>
                                        <td class="p-3 whitespace-nowrap text-center">
                                            @if($guide->is_published)
                                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    প্রকাশিত
                                                </span>
                                            @else
                                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                    ড্রাফট
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-400 font-medium">
                                            আপনার ফিল্টারের শর্ত অনুযায়ী কোনো গাইড খুঁজে পাওয়া যায়নি।
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right Column: Document Options & Generate Button (4 cols) -->
            <div class="lg:col-span-4 space-y-6">

                <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-6 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <span>⚙️</span> ধাপ ৩: PDF লেআউট ও ব্র্যান্ডিং সেটিংস
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5 m-0">প্রিন্ট ও এ-ফোর (A4) বুকলেটের অপশন নির্বাচন করুন।</p>
                    </div>

                    <div class="space-y-3.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">বুকলেট/ডকুমেন্ট টাইটেল</label>
                            <input type="text"
                                   name="doc_title"
                                   value="প্রপার্টি কই নলেজ হাব - গাইডলাইন ও আইনি চেকলিস্ট সংকলন"
                                   class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500 font-semibold text-slate-800">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">সারসংক্ষেপ / সাবটাইটেল</label>
                            <textarea name="subtitle"
                                      rows="2"
                                      class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500 text-slate-600 leading-relaxed">বাংলাদেশে জমি, ফ্ল্যাট ও আবাসন খাতের হালনাগাদ সরকারি নিয়ম, দলিল যাচাই ও ইনভেস্টমেন্ট চেকলিস্ট ২০২৬।</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">গাইড সাজানোর ক্রম (Sorting)</label>
                            <select name="order" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden bg-white text-slate-700 font-medium">
                                <option value="asc">কালানুক্রমিক (আগের প্রকাশিত থেকে নতুন)</option>
                                <option value="desc">লেটেস্ট প্রথম (নতুন প্রকাশিত থেকে পুরানো)</option>
                                <option value="title">শিরোনাম অনুযায়ী (বর্ণানুক্রমিক A-Z)</option>
                            </select>
                        </div>

                        <div class="pt-2 border-t border-slate-100 space-y-2.5">
                            <span class="text-xs font-bold text-slate-800 block">ইনক্লুশন অপশন:</span>

                            <label class="flex items-center gap-2.5 text-xs text-slate-700 font-medium cursor-pointer">
                                <input type="checkbox" name="include_cover" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span>🎨 ব্র্যান্ডেড প্রফেশনাল কভার পেজ</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs text-slate-700 font-medium cursor-pointer">
                                <input type="checkbox" name="include_toc" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span>📑 পূর্ণাঙ্গ সূচিপত্র (Table of Contents)</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs text-slate-700 font-medium cursor-pointer">
                                <input type="checkbox" name="include_faqs" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span>❓ প্রতিটি গাইডের প্রশ্নোত্তর (FAQ) সেকশন</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs text-slate-700 font-medium cursor-pointer">
                                <input type="checkbox" name="include_images" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span>🖼️ মূল থাম্বনেইল ও ইনফোগ্রাফিক ছবি</span>
                            </label>

                            <label class="flex items-center gap-2.5 text-xs text-slate-700 font-medium cursor-pointer">
                                <input type="checkbox" name="auto_print" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span>🖨️ পেজ লোড হলেই প্রিন্ট / Save to PDF ডায়ালগ খুলুন</span>
                            </label>
                        </div>
                    </div>

                    <!-- Export Submission Action Button & Status -->
                    <div class="pt-4 border-t border-slate-100 space-y-2.5">
                        <button type="submit"
                                :disabled="exportScope === 'selected' && selectedIds.length === 0"
                                class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 hover:from-indigo-700 hover:to-purple-800 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-black rounded-2xl shadow-md shadow-indigo-900/20 transition cursor-pointer flex items-center justify-center gap-2 active:scale-95">
                            <svg class="w-4 h-4 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>📄 PDF তৈরি ও প্রিন্ট করুন</span>
                        </button>

                        <!-- Dynamic Scope Indicator Badge below button -->
                        <div class="text-[11px] text-center font-bold"
                             :class="exportScope === 'selected' && selectedIds.length === 0 ? 'text-rose-500' : 'text-slate-500'">
                            <template x-if="exportScope === 'all'">
                                <span>✅ সকল {{ $guides->count() }}টি গাইড PDF-এ যুক্ত হবে</span>
                            </template>
                            <template x-if="exportScope === 'selected' && selectedIds.length > 0">
                                <span>✅ নির্বাচিত <span x-text="selectedIds.length"></span>টি গাইড PDF-এ যুক্ত হবে</span>
                            </template>
                            <template x-if="exportScope === 'selected' && selectedIds.length === 0">
                                <span>⚠️ দয়া করে তালিকা থেকে অন্তত ১টি গাইড নির্বাচন করুন</span>
                            </template>
                        </div>

                        <div class="text-[10px] text-slate-400 text-center leading-relaxed">
                            নতুন ট্যাবে প্রিন্ট ভিউ খুলবে। ব্রাউজারের প্রিন্ট ডায়ালগে <span class="font-bold text-slate-700">'Destination: Save as PDF'</span> নির্বাচন করে সেভ করুন।
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>
@endsection
