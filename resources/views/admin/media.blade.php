@extends('layouts.admin')

@section('title', 'মিডিয়া লাইব্রেরি ও ফাইল ম্যানেজার (Cloudinary)')
@section('page_title', 'মিডিয়া লাইব্রেরি (Cloudinary)')

@section('content')
<div class="space-y-6" x-data="admMediaManager()">

    <!-- Top KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">মোট মিডিয়া ফাইল</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total']) }}টি</div>
                <div class="text-xs text-slate-500 mt-1">ক্যাটালগ ও প্রপার্টি গ্যালারি</div>
            </div>
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                🖼️
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">ক্লাউডিনারি হোস্টেড</div>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats['cloudinary']) }}টি</div>
                <div class="text-xs text-emerald-700 font-bold mt-1">✓ ১০০% WebP অপ্টিমাইজড</div>
            </div>
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                ☁️
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">প্রপার্টিতে যুক্ত</div>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ number_format($stats['assigned']) }}টি</div>
                <div class="text-xs text-blue-700 font-bold mt-1">সক্রিয় বিজ্ঞাপন গ্যালারিতে</div>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                🏠
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">আন-অ্যাসাইনড / সাধারণ</div>
                <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($stats['unassigned']) }}টি</div>
                <div class="text-xs text-amber-700 font-bold mt-1">প্রপার্টিতে যুক্ত করার জন্য প্রস্তুত</div>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
                📂
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Action Bar: Search, Filters, Upload CTA -->
        <div class="p-5 sm:p-6 border-b border-slate-200/80 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 m-0 flex items-center gap-2">
                        <span>☁️ Cloudinary মিডিয়া ফাইল ম্যানেজার</span>
                        @if($isCloudinaryReady)
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase">সক্রিয়</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px] font-black uppercase">এপিআই আনকনফিগারড</span>
                        @endif
                    </h2>
                    <p class="text-xs text-slate-500 m-0 mt-0.5">সব ছবি স্বয়ংক্রিয়ভাবে WebP ফরম্যাটে রূপান্তর হয়ে Cloudinary ক্লাউডে সংরক্ষিত থাকে। ডিলিট করলে Cloudinary থেকেও স্থায়ীভাবে মুছে যায়।</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Bulk Delete Button (Hidden when none selected) -->
                    <button type="button"
                            x-show="selectedIds.length > 0"
                            x-cloak
                            @click="bulkDelete()"
                            class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <span>🗑️ নির্বাচিত মুছুন (<span x-text="selectedIds.length"></span>)</span>
                    </button>

                    <!-- Cloudinary Sync Button -->
                    <button type="button"
                            @click="syncCloudinary()"
                            :disabled="syncing"
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-xl border border-slate-300 transition flex items-center gap-1.5 cursor-pointer">
                        <span x-show="!syncing">⚡ লোকাল ফাইল সিঙ্ক</span>
                        <span x-show="syncing" class="flex items-center gap-1">
                            <span class="animate-spin">⏳</span> সিঙ্ক হচ্ছে...
                        </span>
                    </button>

                    <!-- Upload New Media Button -->
                    <button type="button"
                            @click="uploadModalOpen = true"
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                        <span>+ নতুন মিডিয়া আপলোড</span>
                    </button>
                </div>
            </div>

            <!-- Filters & Search Strip -->
            <form method="GET" action="{{ route('admin.media.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 pt-2">
                <!-- Search input -->
                <div class="lg:col-span-5 relative">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="ক্যাপশন, প্রপার্টি নাম বা কোড দিয়ে খুঁজুন..."
                           class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-hidden focus:border-emerald-500 focus:bg-white transition">
                    <span class="absolute left-3 top-2.5 text-slate-400 text-sm">🔍</span>
                </div>

                <!-- Attachment Filter -->
                <div class="lg:col-span-3">
                    <select name="filter"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-hidden focus:border-emerald-500">
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>সকল মিডিয়া (All Media)</option>
                        <option value="assigned" {{ $filter === 'assigned' ? 'selected' : '' }}>🏠 প্রপার্টিতে যুক্ত (Attached)</option>
                        <option value="unassigned" {{ $filter === 'unassigned' ? 'selected' : '' }}>📂 আন-অ্যাসাইনড (Unassigned)</option>
                        <option value="cloudinary" {{ $filter === 'cloudinary' ? 'selected' : '' }}>☁️ ক্লাউডিনারি হোস্টেড</option>
                        <option value="local" {{ $filter === 'local' ? 'selected' : '' }}>💻 লোকাল ড্রাইভ ফাইল</option>
                    </select>
                </div>

                <!-- Media Type -->
                <div class="lg:col-span-2">
                    <select name="type"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-hidden focus:border-emerald-500">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>সকল টাইপ</option>
                        <option value="photo" {{ $type === 'photo' ? 'selected' : '' }}>📷 ছবি (Photos)</option>
                        <option value="video_embed" {{ $type === 'video_embed' ? 'selected' : '' }}>🎥 ভিডিও এমবেড</option>
                    </select>
                </div>

                <!-- Submit / Clear -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2.5 bg-slate-900 text-white font-extrabold text-xs rounded-xl hover:bg-slate-800 transition cursor-pointer text-center">
                        ফিল্টার
                    </button>
                    @if(!empty($search) || $filter !== 'all' || $type !== 'all')
                        <a href="{{ route('admin.media.index') }}" class="px-3 py-2.5 text-slate-500 hover:text-slate-800 text-xs font-bold transition">
                            ✕ ক্লিয়ার
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Media Grid -->
        <div class="p-5 sm:p-6">
            @if($mediaItems->isEmpty())
                <div class="text-center py-16 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 space-y-3">
                    <div class="text-4xl">🖼️</div>
                    <h3 class="text-base font-extrabold text-slate-800 m-0">কোনো মিডিয়া ফাইল পাওয়া যায়নি</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto m-0">নতুন প্রপার্টি ছবি আপলোড করতে উপরের "+ নতুন মিডিয়া আপলোড" বাটনে ক্লিক করুন।</p>
                    <button type="button" @click="uploadModalOpen = true" class="mt-2 px-4 py-2 bg-emerald-600 text-white text-xs font-extrabold rounded-xl shadow-xs transition">
                        ছবি আপলোড করুন
                    </button>
                </div>
            @else
                <!-- Select All Bar -->
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 text-xs text-slate-500">
                    <label class="flex items-center gap-2 font-bold cursor-pointer text-slate-700">
                        <input type="checkbox"
                               @change="toggleSelectAll($event)"
                               :checked="selectedIds.length === {{ $mediaItems->count() }} && {{ $mediaItems->count() }} > 0"
                               class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                        <span>সবগুলো সিলেক্ট করুন ({{ $mediaItems->count() }}টি আইটেম প্রদর্শিত)</span>
                    </label>
                    <span class="text-slate-400 font-medium">মোট {{ $mediaItems->total() }}টি ফাইলের মধ্যে {{ $mediaItems->firstItem() }}-{{ $mediaItems->lastItem() }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($mediaItems as $item)
                        @php
                            $isCloud = str_starts_with($item->file_path, 'https://res.cloudinary.com/');
                            $prop = $item->property;
                        @endphp
                        <div class="group relative bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-emerald-400 transition flex flex-col overflow-hidden"
                             :class="selectedIds.includes({{ $item->id }}) ? 'ring-2 ring-emerald-500 border-emerald-500' : ''">

                            <!-- Checkbox Selector -->
                            <div class="absolute top-2 left-2 z-10">
                                <input type="checkbox"
                                       value="{{ $item->id }}"
                                       @change="toggleSelect({{ $item->id }})"
                                       :checked="selectedIds.includes({{ $item->id }})"
                                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer shadow-xs bg-white/90">
                            </div>

                            <!-- Storage Badge -->
                            <div class="absolute top-2 right-2 z-10 flex items-center gap-1">
                                @if($item->is_cover)
                                    <span class="px-2 py-0.5 rounded-md bg-amber-500 text-white text-[10px] font-black shadow-xs">
                                        ⭐ কভার
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold shadow-xs {{ $isCloud ? 'bg-emerald-600 text-white' : 'bg-slate-900 text-white' }}">
                                    {{ $isCloud ? '☁️ Cloud' : '💻 Local' }}
                                </span>
                            </div>

                            <!-- Image Thumbnail Thumbnail Container -->
                            <div class="aspect-square bg-slate-100 overflow-hidden relative cursor-pointer"
                                 @click="openLightbox('{{ $item->url }}', '{{ addslashes($item->caption ?: 'Property Media') }}')">
                                @if($item->media_type === 'video_embed')
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-slate-900 text-white p-3 text-center">
                                        <span class="text-3xl">🎥</span>
                                        <span class="text-[10px] font-bold mt-1 text-slate-300">ভিডিও লিঙ্ক</span>
                                    </div>
                                @else
                                    <img src="{{ $item->url }}"
                                         alt="{{ $item->caption ?: 'Media' }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @endif

                                <!-- Hover Quick View Overlay -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 text-white">
                                    <span class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-sm font-bold">🔍</span>
                                </div>
                            </div>

                            <!-- Card Info Details -->
                            <div class="p-3 flex-1 flex flex-col justify-between space-y-2 bg-slate-50/50">
                                <div>
                                    <!-- Caption or Filename -->
                                    <div class="text-xs font-bold text-slate-900 truncate" title="{{ $item->caption ?: basename($item->file_path) }}">
                                        {{ $item->caption ?: basename($item->file_path) }}
                                    </div>

                                    <!-- Attached Property Indicator -->
                                    <div class="mt-1">
                                        @if($prop)
                                            <a href="{{ route('admin.properties.edit', $prop->id) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 truncate max-w-full"
                                               title="{{ $prop->title }} (#{{ $prop->property_code }})">
                                                <span>🏠</span>
                                                <span class="truncate">#{{ $prop->property_code }}</span>
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-400">
                                                <span>📂</span>
                                                <span>আন-অ্যাসাইনড</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Card Actions -->
                                <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($item) }})"
                                            class="text-indigo-600 hover:text-indigo-900 font-bold text-[11px] cursor-pointer">
                                        এডিট / লিংক
                                    </button>

                                    <button type="button"
                                            @click="deleteSingle({{ $item->id }})"
                                            class="text-rose-600 hover:text-rose-800 font-bold text-[11px] cursor-pointer">
                                        মুছুন
                                    </button>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $mediaItems->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- 1. UPLOAD NEW MEDIA MODAL -->
    <div x-show="uploadModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="uploadModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="uploadModalOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="uploadModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">

                <form action="{{ route('admin.media.upload') }}" method="POST" enctype="multipart/form-data" @submit="uploading = true">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-black text-slate-900 m-0 flex items-center gap-2">
                                <span>☁️</span>
                                <span>নতুন ছবি আপলোড (Cloudinary + WebP)</span>
                            </h3>
                            <button type="button" @click="uploadModalOpen = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold cursor-pointer">✕</button>
                        </div>

                        <!-- Dropzone area -->
                        <div class="border-2 border-dashed border-emerald-300 rounded-2xl p-6 text-center bg-emerald-50/30 hover:bg-emerald-50/60 transition cursor-pointer relative"
                             @click="$refs.uploadFileInput.click()">
                            <input type="file"
                                   x-ref="uploadFileInput"
                                   name="files[]"
                                   multiple
                                   accept="image/*"
                                   class="hidden"
                                   @change="selectedFileNames = Array.from($event.target.files).map(f => f.name)">
                            <div class="text-3xl mb-1">🖼️</div>
                            <div class="font-extrabold text-xs text-slate-800">ছবি সিলেক্ট করতে ক্লিক করুন অথবা ফাইল ড্র্যাগ করুন</div>
                            <div class="text-[11px] text-slate-500 mt-1">সব ছবি স্বয়ংক্রিয়ভাবে পয়েন্ট-টু-পয়েন্ট <strong>WebP</strong> তে কনভার্ট হয়ে ক্লাউডিনারিতে আপলোড হবে।</div>
                            <div x-show="selectedFileNames.length > 0" class="mt-2 text-xs font-bold text-emerald-800 bg-emerald-100 p-2 rounded-xl">
                                নির্বাচিত ফাইল: <span x-text="selectedFileNames.length"></span>টি
                            </div>
                        </div>

                        <!-- Property assignment dropdown -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">কোনো প্রপার্টির সাথে যুক্ত করবেন? (ঐচ্ছিক)</label>
                            <select name="property_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium bg-slate-50">
                                <option value="">-- আন-অ্যাসাইনড / সাধারণ মিডিয়া (Unassigned) --</option>
                                @foreach($properties as $p)
                                    <option value="{{ $p->id }}">{{ $p->title }} (#{{ $p->property_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Caption -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">ক্যাপশন / বিবরণ (ঐচ্ছিক)</label>
                            <input type="text" name="caption" placeholder="যেমন: কর্নার ভিউ, ফ্রন্ট রোড..." class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs bg-slate-50">
                        </div>

                        <!-- Set as cover -->
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
                            <input type="checkbox" name="is_cover" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>প্রপার্টির মূল কভার ছবি হিসেবে সেট করুন</span>
                        </label>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="uploadModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                            বাতিল
                        </button>
                        <button type="submit" :disabled="uploading" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                            <span x-show="!uploading">☁️ Cloudinary-তে আপলোড করুন</span>
                            <span x-show="uploading" class="flex items-center gap-1">
                                <span class="animate-spin">⏳</span> আপলোড ও WebP কনভার্ট হচ্ছে...
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- 2. EDIT / ATTACH PROPERTY MODAL -->
    <div x-show="editModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="edit-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="editModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="editModalOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="editModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-black text-slate-900 m-0">মিডিয়া তথ্য ও প্রপার্টি সংযোগ</h3>
                        <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold cursor-pointer">✕</button>
                    </div>

                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-200/80">
                        <img :src="editForm.url" class="w-16 h-16 rounded-xl object-cover border border-slate-200 shadow-2xs">
                        <div class="text-xs truncate flex-1">
                            <div class="font-bold text-slate-900 truncate" x-text="editForm.caption || 'Property Media'"></div>
                            <div class="text-[10px] text-slate-500 font-mono mt-0.5 truncate" x-text="editForm.url"></div>
                        </div>
                    </div>

                    <!-- Property Select -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">প্রপার্টি অ্যাসাইন করুন</label>
                        <select x-model="editForm.property_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium bg-slate-50">
                            <option value="">-- আন-অ্যাসাইনড (Unassigned) --</option>
                            @foreach($properties as $p)
                                <option value="{{ $p->id }}">{{ $p->title }} (#{{ $p->property_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Caption -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">ক্যাপশন</label>
                        <input type="text" x-model="editForm.caption" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs bg-slate-50">
                    </div>

                    <!-- Cover status -->
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
                        <input type="checkbox" x-model="editForm.is_cover" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>এই প্রপার্টির প্রধান কভার ছবি (Cover Photo)</span>
                    </label>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                        বাতিল
                    </button>
                    <button type="button" @click="saveEdit()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition cursor-pointer">
                        সংরক্ষণ করুন
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- 3. FULLSCREEN LIGHTBOX -->
    <div x-show="lightboxOpen"
         x-cloak
         @click="lightboxOpen = false"
         class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex flex-col items-center justify-center p-4">
        <button type="button" @click="lightboxOpen = false" class="absolute top-5 right-5 text-white/80 hover:text-white text-3xl font-bold">✕</button>
        <img :src="lightboxUrl" class="max-w-[90vw] max-h-[80vh] rounded-2xl shadow-2xl object-contain border border-white/20">
        <div class="mt-4 text-white font-bold text-sm text-center" x-text="lightboxCaption"></div>
        <div class="mt-2 flex items-center gap-2">
            <button type="button" @click.stop="copyUrl(lightboxUrl)" class="px-3.5 py-1.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold backdrop-blur-md transition">
                📋 লিঙ্ক কপি করুন
            </button>
            <a :href="lightboxUrl" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition">
                🔗 ক্লাউডিনারিতে দেখুন
            </a>
        </div>
    </div>

</div>

<script>
function admMediaManager() {
    return {
        selectedIds: [],
        uploadModalOpen: false,
        uploading: false,
        selectedFileNames: [],

        editModalOpen: false,
        editForm: {
            id: null,
            property_id: '',
            caption: '',
            is_cover: false,
            url: ''
        },

        lightboxOpen: false,
        lightboxUrl: '',
        lightboxCaption: '',

        syncing: false,

        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) {
                this.selectedIds.splice(idx, 1);
            } else {
                this.selectedIds.push(id);
            }
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedIds = Array.from(document.querySelectorAll('input[type="checkbox"][value]')).map(el => parseInt(el.value));
            } else {
                this.selectedIds = [];
            }
        },

        openLightbox(url, caption) {
            this.lightboxUrl = url;
            this.lightboxCaption = caption;
            this.lightboxOpen = true;
        },

        copyUrl(url) {
            navigator.clipboard.writeText(url);
            alert('মিডিয়া লিঙ্ক কপি করা হয়েছে:\n' + url);
        },

        openEditModal(item) {
            this.editForm.id = item.id;
            this.editForm.property_id = item.property_id || '';
            this.editForm.caption = item.caption || '';
            this.editForm.is_cover = !!item.is_cover;
            this.editForm.url = item.url || item.file_path;
            this.editModalOpen = true;
        },

        saveEdit() {
            fetch('/admin/media/' + this.editForm.id + '/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.editForm)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    this.editModalOpen = false;
                    window.location.reload();
                } else {
                    alert(res.message || 'আপডেট করা সম্ভব হয়নি।');
                }
            })
            .catch(() => alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।'));
        },

        deleteSingle(id) {
            if (!confirm('আপনি কি নিশ্চিত যে এই মিডিয়া ফাইলটি Cloudinary এবং ডাটাবেস থেকে স্থায়ীভাবে ডিলিট করতে চান?')) {
                return;
            }

            fetch('/admin/media/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'ডিলিট করা যায়নি।');
                }
            })
            .catch(() => alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।'));
        },

        bulkDelete() {
            if (this.selectedIds.length === 0) return;

            if (!confirm(`আপনি কি নিশ্চিত যে নির্বাচিত ${this.selectedIds.length}টি মিডিয়া ফাইল Cloudinary এবং ডাটাবেস থেকে স্থায়ীভাবে মুছে ফেলতে চান?`)) {
                return;
            }

            fetch('{{ route('admin.media.bulk-delete') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids: this.selectedIds })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'ডিলিট করা সম্ভব হয়নি।');
                }
            })
            .catch(() => alert('সার্ভার যোগাযোগে সমস্যা হয়েছে।'));
        },

        syncCloudinary() {
            this.syncing = true;
            fetch('{{ route('admin.media.sync-cloudinary') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(res => {
                this.syncing = false;
                alert(res.message || 'সিঙ্ক সম্পন্ন হয়েছে!');
                window.location.reload();
            })
            .catch(() => {
                this.syncing = false;
                alert('সিঙ্ক সম্পন্ন হতে সময় লাগছে। পেজ রিফ্রেশ করে দেখুন।');
                window.location.reload();
            });
        }
    };
}
</script>
@endsection
