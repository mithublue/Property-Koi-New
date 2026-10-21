@extends('layouts.admin')

@section('page_title', $guide->exists ? 'গাইড সম্পাদনা: ' . $guide->title : 'নতুন প্রপার্টি গাইড / আর্টিকেল তৈরি')

@section('content')
<div class="p-4 md:p-8 max-w-7xl mx-auto space-y-6"
     data-turbo="false"
     x-data="{
         title: {{ json_encode(old('title', $guide->title ?? '')) }},
         slug: {{ json_encode(old('slug', $guide->slug ?? '')) }},
         slugManuallyEdited: {{ json_encode($guide->exists && !empty($guide->slug)) }},
         category: {{ json_encode(old('category', $guide->category ?: 'uncategorised')) }},
         showCustomCat: false,
         customCategory: '',
         coverUrl: {{ json_encode(old('cover_image', $guide->cover_image ?? '')) }},
         uploadingThumbnail: false,
         faqs: {{ json_encode(!empty($guide->faqs) && is_array($guide->faqs) ? $guide->faqs : [['question' => '', 'answer' => '']]) }},

         slugify(text) {
             if (!text) return '';
             return text.toString().toLowerCase().trim()
                 .replace(/\s+/g, '-')           // Replace spaces with -
                 .replace(/[^\w\u0980-\u09FF-]+/g, '') // Keep alphanumeric, Bengali characters, and -
                 .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                 .replace(/^-+/, '')             // Trim - from start of text
                 .replace(/-+$/, '');            // Trim - from end of text
         },

         onTitleChange() {
             if (!this.slugManuallyEdited || !this.slug) {
                 this.slug = this.slugify(this.title);
             }
         },

         resetSlug() {
             this.slug = this.slugify(this.title);
             this.slugManuallyEdited = false;
         },

         addFaq() {
             this.faqs.push({ question: '', answer: '' });
         },

         removeFaq(index) {
             if (this.faqs.length > 1) {
                 this.faqs.splice(index, 1);
             } else {
                 this.faqs = [{ question: '', answer: '' }];
             }
         },

         uploadThumbnailFile(e) {
             const file = e.target.files[0];
             if (!file) return;

             this.uploadingThumbnail = true;
             const formData = new FormData();
             formData.append('image', file);
             formData.append('_token', '{{ csrf_token() }}');

             fetch('{{ route('admin.guides.upload-thumbnail') }}', {
                 method: 'POST',
                 body: formData,
                 headers: {
                     'Accept': 'application/json'
                 }
             })
             .then(res => res.json())
             .then(data => {
                 this.uploadingThumbnail = false;
                 if (data.success && data.url) {
                     this.coverUrl = data.url;
                 } else {
                     alert(data.message || 'ছবি আপলোড ব্যর্থ হয়েছে।');
                 }
             })
             .catch(err => {
                 this.uploadingThumbnail = false;
                 console.error(err);
                 alert('ছবি আপলোডে সমস্যা হয়েছে।');
             });
         }
     }">

    <!-- Top Breadcrumb / Action Bar -->
    <div class="flex items-center justify-between pb-2">
        <a href="{{ route('admin.guides.index') }}" data-turbo="false" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
            &larr; <span>সকল আর্টিকেলে ফিরে যান</span>
        </a>
        @if($guide->exists && $guide->slug)
            <a href="{{ route('guides.show', $guide->slug) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold border border-emerald-200 transition">
                <span>🌐 লাইভ আর্টিকেল প্রিভিউ</span> &rarr;
            </a>
        @endif
    </div>

    <!-- Main Form Formatted with WordPress / Medium Style 2-Columns -->
    <form action="{{ $guide->exists ? route('admin.guides.update', $guide) : route('admin.guides.store') }}"
          method="POST"
          enctype="multipart/form-data"
          id="guide-post-form">
        @csrf
        @if($guide->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- LEFT COLUMN: Main Post Editor (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Title & Slug Card -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-4">
                    <div>
                        <input type="text"
                               name="title"
                               x-model="title"
                               @input="onTitleChange()"
                               required
                               placeholder="আর্টিকেলের শিরোনাম লিখুন (যেমন: ২০২৬ সালে জমির দলিল ও নামজারি খতিয়ান যাচাই গাইড)..."
                               class="w-full px-0 py-2 border-0 border-b-2 border-slate-200 focus:border-emerald-600 focus:ring-0 text-xl sm:text-2xl font-black text-slate-900 placeholder:text-slate-300 transition-colors">
                    </div>

                    <!-- Auto-generated Slug with Edit Toggle -->
                    <div class="flex items-center flex-wrap gap-2 text-xs text-slate-500 pt-1">
                        <span class="font-bold text-slate-700">পার্মালিংক (URL):</span>
                        <span class="text-slate-400 font-mono">{{ url('/guides') }}/</span>
                        <input type="text"
                               name="slug"
                               x-model="slug"
                               @input="slugManuallyEdited = true"
                               placeholder="auto-generated-slug"
                               class="px-2.5 py-1 rounded-lg border border-slate-200 text-xs font-mono font-semibold text-emerald-800 bg-slate-50 focus:bg-white focus:outline-hidden focus:border-emerald-500 max-w-xs">
                        <button type="button"
                                @click="resetSlug()"
                                class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-[11px] font-semibold transition"
                                title="শিরোনাম অনুযায়ী পুনরায় রি-জেনারেট করুন">
                            🔄 অটো রিসেট
                        </button>
                    </div>

                    <!-- Excerpt / Summary -->
                    <div class="pt-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">সংক্ষিপ্ত সারসংক্ষেপ (Excerpt / Lead Summary)</label>
                        <textarea name="excerpt"
                                  rows="2"
                                  placeholder="আর্টিকেলের মূল বার্তা ১-২ বাক্যে লিখুন (সার্চ ইঞ্জিন স্নাইপেট ও ফেসবুক প্রিভিউয়ের জন্য অতি প্রয়োজনীয়)..."
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-hidden focus:border-emerald-500 leading-relaxed">{{ old('excerpt', $guide->excerpt) }}</textarea>
                    </div>
                </div>

                <!-- WYSIWYG / Gutenberg / Medium Editor Card -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">✍️</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">সম্পূর্ণ আর্টিকেল বিষয়বস্তু (Medium / Gutenberg WYSIWYG)</h3>
                                <p class="text-xs text-slate-400">অন্য যেকোনো জায়গা (Google Docs/Word/Medium) থেকে ফরম্যাটসহ পেস্ট সাপোর্ট করে</p>
                            </div>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                            WebP + Cloudinary অটো-ইমেজ
                        </span>
                    </div>

                    <!-- Gutenberg Block / Widget Quick-Inserters Bar -->
                    <div class="flex flex-wrap items-center gap-2 p-2.5 bg-slate-50 border border-slate-200/80 rounded-2xl">
                        <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider px-1">গুটেনবার্গ ব্লক:</span>
                        <button type="button" onclick="insertEditorBlock('info')" class="px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200 transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                            <span>💡</span> <span>তথ্য কলআউট</span>
                        </button>
                        <button type="button" onclick="insertEditorBlock('warning')" class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold border border-amber-200 transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                            <span>⚠️</span> <span>সতর্কতা বক্স</span>
                        </button>
                        <button type="button" onclick="insertEditorBlock('calc')" class="px-2.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                            <span>🧮</span> <span>ক্যালকুলেটর ব্যানার CTA</span>
                        </button>
                        <button type="button" onclick="insertEditorBlock('table')" class="px-2.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 text-xs font-bold border border-slate-200 transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                            <span>📊</span> <span>রেট তুলনা টেবিল</span>
                        </button>
                        <button type="button" onclick="insertEditorBlock('quote')" class="px-2.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 text-xs font-bold border border-slate-200 transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                            <span>💬</span> <span>বড় উক্তি (Pullquote)</span>
                        </button>
                    </div>

                    <!-- Editor Textarea Container -->
                    <div>
                        <textarea id="guide-wysiwyg-editor"
                                  name="content"
                                  rows="22"
                                  placeholder="এখানে লিখুন বা কপি করা লেখা পেস্ট করুন...">{{ old('content', $guide->content) }}</textarea>
                    </div>
                </div>

                <!-- AEO FAQ Repeater Card -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span>🤖</span> AEO প্রশ্নোত্তর রিপিটার (Answer Engine Optimization)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">গুগল, পারপ্লেক্সিটি ও চ্যাটজিপিটি সরাসরি এই প্রশ্ন-উত্তরগুলো প্রদর্শন করবে।</p>
                        </div>
                        <button type="button"
                                @click="addFaq()"
                                class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-200 transition flex items-center gap-1">
                            <span>+</span> প্রশ্ন যোগ করুন
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(faq, index) in faqs" :key="index">
                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/70 space-y-3 relative group">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-700 flex items-center gap-1">
                                        <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]" x-text="index + 1"></span>
                                        <span>প্রশ্ন</span>
                                    </span>
                                    <button type="button"
                                            @click="removeFaq(index)"
                                            class="text-rose-500 hover:text-rose-700 text-xs font-bold cursor-pointer transition">
                                        ✕ বাদ দিন
                                    </button>
                                </div>
                                <div>
                                    <input type="text"
                                           name="faq_questions[]"
                                           x-model="faq.question"
                                           placeholder="প্রশ্ন লিখুন (যেমন: বায়া দলিল ছাড়া কি জমি রেজিস্ট্রি করা সম্ভব?)"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:outline-hidden focus:border-emerald-500 bg-white">
                                </div>
                                <div>
                                    <textarea name="faq_answers[]"
                                              x-model="faq.answer"
                                              rows="2"
                                              placeholder="সুস্পষ্ট ও নির্ভরযোগ্য উত্তর লিখুন..."
                                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-hidden focus:border-emerald-500 bg-white"></textarea>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Custom SEO Settings Card -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-xs space-y-4">
                    <h3 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                        <span>🔍</span> সার্চ ইঞ্জিন অপ্টিমাইজেশন (SEO Meta Tags)
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">কাস্টম এসইও টাইটেল (SEO Title)</label>
                            <input type="text"
                                   name="seo_title"
                                   value="{{ old('seo_title', $guide->seo_title) }}"
                                   placeholder="খালি রাখলে মূল শিরোনাম থেকে স্বয়ংক্রিয় তৈরি হবে"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-hidden focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">মেটা ডেসক্রিপশন (Meta Description - Max 160 Chars)</label>
                            <textarea name="seo_description"
                                      rows="2"
                                      placeholder="সার্চ রেজাল্ট ও প্রিভিউতে যা দেখাবে..."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-hidden focus:border-emerald-500">{{ old('seo_description', $guide->seo_description) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">সার্চ কিওয়ার্ডস (Comma Separated)</label>
                            <input type="text"
                                   name="seo_keywords"
                                   value="{{ old('seo_keywords', $guide->seo_keywords) }}"
                                   placeholder="যেমন: jomir dolil jachai, namjari khotian, rajuk plan approval"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-hidden focus:border-emerald-500">
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Publishing & Taxonomy Sidebar (4 Cols) -->
            <div class="lg:col-span-4 space-y-6">

                <!-- 1. Publish & Status Card -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs space-y-5">
                    <h3 class="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="flex items-center gap-2"><span>🚀</span> পাবলিকেশন কন্ট্রোল</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $guide->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $guide->is_published ? 'প্রকাশিত' : 'ড্রাফট' }}
                        </span>
                    </h3>

                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100/70 transition">
                            <span class="text-xs font-bold text-slate-800">পাবলিশ করুন (Published)</span>
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $guide->is_published ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        </label>

                        <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100/70 transition">
                            <span class="text-xs font-bold text-slate-800">⭐ ফিচার্ড হিসেবে হাইলাইট</span>
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $guide->is_featured ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        </label>
                    </div>

                    <div class="space-y-3 pt-2 border-t border-slate-100 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">লেখক / গবেষক</label>
                            <input type="text"
                                   name="author_name"
                                   value="{{ old('author_name', $guide->author_name ?: 'PropertyKoi Research Team') }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-hidden focus:border-emerald-500">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">পড়ার সময় (মিনিট)</label>
                            <input type="number"
                                   name="reading_time_min"
                                   min="1"
                                   max="60"
                                   value="{{ old('reading_time_min', $guide->reading_time_min ?: 5) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-hidden focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                        <button type="submit" class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                            <span>💾</span>
                            <span>{{ $guide->exists ? 'আপডেট সংরক্ষণ করুন' : 'আর্টিকেল প্রকাশ করুন' }}</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Category Selector & Custom Category Card -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <span>🏷️</span> ক্যাটাগরি (Category)
                    </h3>

                    <!-- Categories Radio/Select Options -->
                    <div class="space-y-2">
                        @foreach($categories as $catKey => $catLabel)
                            <label class="flex items-center gap-2.5 p-2 rounded-xl border transition cursor-pointer text-xs font-semibold"
                                   :class="category === '{{ $catKey }}' && !showCustomCat ? 'bg-emerald-50 border-emerald-500 text-emerald-900 font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-50'">
                                <input type="radio"
                                       name="category"
                                       value="{{ $catKey }}"
                                       x-model="category"
                                       @change="showCustomCat = false; customCategory = ''"
                                       class="text-emerald-600 focus:ring-emerald-500">
                                <span>{{ $catLabel }}</span>
                            </label>
                        @endforeach

                        <!-- Custom Category Option -->
                        <div class="pt-2">
                            <label class="flex items-center gap-2.5 p-2 rounded-xl border transition cursor-pointer text-xs font-bold"
                                   :class="showCustomCat ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : 'border-slate-200 text-slate-700 hover:bg-slate-50'">
                                <input type="radio"
                                       name="category_choice"
                                       value="custom"
                                       @change="showCustomCat = true; category = ''"
                                       :checked="showCustomCat"
                                       class="text-emerald-600 focus:ring-emerald-500">
                                <span>+ নতুন ক্যাটাগরি তৈরি করুন</span>
                            </label>

                            <!-- Custom Category Input Field -->
                            <div x-show="showCustomCat" x-collapse class="mt-2.5 p-3 rounded-2xl bg-emerald-50/60 border border-emerald-200 space-y-1.5">
                                <label class="block text-[11px] font-bold text-emerald-900">কাস্টম ক্যাটাগরির নাম লিখুন *</label>
                                <input type="text"
                                       name="custom_category"
                                       x-model="customCategory"
                                       placeholder="যেমন: গৃহঋণ ও ব্যাংক লোন, ডিজাইন টিপস..."
                                       class="w-full px-3 py-2 rounded-xl border border-emerald-300 text-xs bg-white focus:outline-hidden focus:border-emerald-600 font-semibold">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Featured Image / Thumbnail Upload to Cloudinary (WebP) -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span>🖼️</span> ফিচার্ড থাম্বনেইল (WebP + Cloudinary)
                        </h3>
                    </div>

                    <!-- Hidden input to store Cloudinary URL -->
                    <input type="hidden" name="cover_image" x-model="coverUrl">

                    <!-- Image Preview Area -->
                    <div class="relative aspect-video rounded-2xl bg-slate-100 border-2 border-dashed border-slate-200 overflow-hidden flex items-center justify-center group">
                        <template x-if="coverUrl">
                            <div class="w-full h-full relative">
                                <img :src="coverUrl" alt="Thumbnail" class="w-full h-full object-cover">
                                <button type="button"
                                        @click="coverUrl = ''"
                                        class="absolute top-2 right-2 p-1.5 rounded-full bg-slate-900/80 text-white hover:bg-rose-600 transition text-xs shadow-md"
                                        title="ছবি রিমুভ করুন">
                                    ✕
                                </button>
                            </div>
                        </template>

                        <template x-if="!coverUrl">
                            <div class="text-center p-4">
                                <span class="text-3xl block mb-1">📸</span>
                                <span class="text-xs font-bold text-slate-600 block">থাম্বনেইল নির্বাচন করুন</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">স্বয়ংক্রিয় WebP কনভার্ট হয়ে ক্লাউডিনারিতে সেভ হবে</span>
                            </div>
                        </template>

                        <!-- Upload Spinner Overlay -->
                        <div x-show="uploadingThumbnail" class="absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center gap-2">
                            <div class="w-7 h-7 border-3 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-xs font-bold text-emerald-800">WebP ও ক্লাউডিনারি আপলোড হচ্ছে...</span>
                        </div>
                    </div>

                    <!-- File Picker Button -->
                    <div>
                        <label class="w-full py-2.5 px-4 rounded-xl border border-slate-200 hover:border-emerald-400 bg-slate-50 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
                            <span>📁 ফাইল থেকে ছবি আপলোড করুন</span>
                            <input type="file"
                                   accept="image/*"
                                   @change="uploadThumbnailFile($event)"
                                   class="sr-only">
                        </label>
                    </div>

                    <!-- Direct URL Option Collapsible -->
                    <div x-data="{ showUrlInput: false }" class="pt-1">
                        <button type="button"
                                @click="showUrlInput = !showUrlInput"
                                class="text-[11px] font-bold text-slate-500 hover:text-emerald-600 cursor-pointer">
                            🔗 অথবা সরাসরি ইমেজ লিংক দিন &rarr;
                        </button>
                        <div x-show="showUrlInput" x-collapse class="mt-2">
                            <input type="text"
                                   x-model="coverUrl"
                                   placeholder="https://res.cloudinary.com/..."
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-700 focus:outline-hidden focus:border-emerald-500">
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
<!-- TinyMCE Rich Text Editor (Local Asset with CDN Fallback) -->
<script src="{{ asset('assets/vendor/tinymce/tinymce.min.js') }}"></script>
<script>
    if (typeof tinymce === 'undefined') {
        const cdnScript = document.createElement('script');
        cdnScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js';
        cdnScript.referrerPolicy = 'origin';
        document.head.appendChild(cdnScript);
    }
</script>

<script>
(function() {
    function initGuideEditor() {
        const textarea = document.getElementById('guide-wysiwyg-editor');
        if (!textarea) return;

        if (typeof tinymce === 'undefined') {
            setTimeout(initGuideEditor, 100);
            return;
        }

        // Clean up previous instance if already initialized (Turbo SPA support)
        if (tinymce.get('guide-wysiwyg-editor')) {
            tinymce.get('guide-wysiwyg-editor').remove();
        }

        tinymce.init({
            selector: '#guide-wysiwyg-editor',
            license_key: 'gpl',
            promotion: false,
            branding: false,
            base_url: '{{ asset("assets/vendor/tinymce") }}',
            suffix: '.min',
            min_height: 520,
            autoresize_bottom_margin: 50,
            menubar: 'file edit insert view format table tools',
            plugins: [
                'autoresize', 'link', 'image', 'media', 'table', 'lists', 'code',
                'wordcount', 'quickbars', 'emoticons', 'charmap'
            ],
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link image media insert_widget | removeformat code',
            quickbars_selection_toolbar: 'bold italic underline | formatselect | blockquote quicklink',
            quickbars_insert_toolbar: 'quickimage quicktable | hr insert_widget',
            
            // Retain pasted styles from Word, Google Docs, Medium, Web
            paste_as_text: false,
            paste_data_images: true,
            paste_enable_default_filters: false,
            paste_retain_style_properties: 'all',
            valid_children: '+body[style]',
            extended_valid_elements: 'span[*],div[*],table[*],tr[*],td[*],th[*],iframe[*],figure[*],figcaption[*],blockquote[*]',
            content_style: `
                body {
                    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    font-size: 16px;
                    line-height: 1.8;
                    color: #1e293b;
                    padding: 1.5rem;
                    max-width: 850px;
                    margin: 0 auto;
                }
                h1, h2, h3, h4 { font-weight: 800; color: #0f172a; margin-top: 1.5em; margin-bottom: 0.5em; }
                h2 { font-size: 1.65rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.3em; }
                h3 { font-size: 1.35rem; }
                p { margin-bottom: 1.25em; }
                table { width: 100%; border-collapse: collapse; margin: 1.5em 0; border: 1px solid #cbd5e1; }
                th { background-color: #f1f5f9; padding: 10px 12px; font-weight: bold; border: 1px solid #cbd5e1; }
                td { padding: 10px 12px; border: 1px solid #e2e8f0; }
                tr:nth-child(even) td { background-color: #f8fafc; }
                blockquote {
                    border-left: 4px solid #059669;
                    padding: 1rem 1.5rem;
                    margin: 1.5em 0;
                    background-color: #f0fdf4;
                    font-style: italic;
                    color: #065f46;
                    border-radius: 0 1rem 1rem 0;
                }
                .info-callout {
                    background-color: #f0fdf4;
                    border: 1px solid #bbf7d0;
                    border-left: 5px solid #059669;
                    padding: 1rem 1.25rem;
                    border-radius: 0.75rem;
                    margin: 1.5rem 0;
                }
                .warning-callout {
                    background-color: #fffbeb;
                    border: 1px solid #fde68a;
                    border-left: 5px solid #d97706;
                    padding: 1rem 1.25rem;
                    border-radius: 0.75rem;
                    margin: 1.5rem 0;
                }
                .calc-cta-widget {
                    background: linear-gradient(135deg, #0f172a 0%, #064e3b 100%);
                    color: #ffffff;
                    padding: 1.5rem;
                    border-radius: 1rem;
                    margin: 2rem 0;
                    text-align: center;
                }
                .calc-cta-btn {
                    display: inline-block;
                    background-color: #059669;
                    color: #ffffff !important;
                    padding: 0.6rem 1.25rem;
                    border-radius: 0.5rem;
                    font-weight: bold;
                    text-decoration: none;
                    margin-top: 0.75rem;
                }
                img { max-width: 100%; height: auto; border-radius: 0.75rem; margin: 1rem 0; }
            `,

            // Automatic Image Upload to Cloudinary via backend endpoint
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    formData.append('_token', '{{ csrf_token() }}');

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route('admin.guides.upload-image') }}');

                    xhr.upload.onprogress = function (e) {
                        progress(e.loaded / e.total * 100);
                    };

                    xhr.onload = function () {
                        if (xhr.status < 200 || xhr.status >= 300) {
                            reject('HTTP Error: ' + xhr.status);
                            return;
                        }
                        const json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location !== 'string') {
                            reject('Invalid JSON: ' + xhr.responseText);
                            return;
                        }
                        resolve(json.location);
                    };

                    xhr.onerror = function () {
                        reject('Image upload failed due to network error.');
                    };

                    xhr.send(formData);
                });
            },

            // Custom Medium / Gutenberg Widget Inserters Menu
            setup: function (editor) {
                editor.ui.registry.addMenuButton('insert_widget', {
                    text: '🧩 উইজেট কম্পোনেন্ট',
                    tooltip: 'Medium স্টাইল উইজেট ও কম্পোনেন্ট যুক্ত করুন',
                    fetch: function (callback) {
                        const items = [
                            {
                                type: 'menuitem',
                                text: '💡 তথ্য কলআউট বক্স (Info Box)',
                                onAction: function () {
                                    editor.insertContent(`
                                        <div class="info-callout">
                                            <h4 style="margin: 0 0 0.5rem 0; color: #065f46; font-size: 1.1rem; font-weight: bold;">💡 গুরুত্বপূর্ণ তথ্য / পরামর্শ</h4>
                                            <p style="margin: 0;">এখানে আপনার গুরুত্বপূর্ণ আইনি বা বাজার সংক্রান্ত তথ্য লিখুন...</p>
                                        </div>
                                    `);
                                }
                            },
                            {
                                type: 'menuitem',
                                text: '⚠️ সতর্কতা ও ঝুঁকি বক্স (Warning Box)',
                                onAction: function () {
                                    editor.insertContent(`
                                        <div class="warning-callout">
                                            <h4 style="margin: 0 0 0.5rem 0; color: #92400e; font-size: 1.1rem; font-weight: bold;">⚠️ সতর্কতা ও আইনি ঝুঁকি</h4>
                                            <p style="margin: 0;">যেকোনো জমি বা ফ্ল্যাট কেনার আগে এই সতর্কতামূলক দিকটি সরেজমিন যাচাই করুন...</p>
                                        </div>
                                    `);
                                }
                            },
                            {
                                type: 'menuitem',
                                text: '🧮 ক্যালকুলেটর ব্যানার CTA (Calculator Widget)',
                                onAction: function () {
                                    editor.insertContent(`
                                        <div class="calc-cta-widget">
                                            <h3 style="color: #ffffff; margin: 0 0 0.5rem 0; font-size: 1.35rem; font-weight: 800;">জমি ও ফ্ল্যাট রেজিস্ট্রেশন খরচ হিসাব করতে চান?</h3>
                                            <p style="color: #94a3b8; margin: 0 0 1rem 0; font-size: 0.95rem;">২০২৬ সালের সরকারি স্ট্যাম্প শুল্ক, নিবন্ধন ফি ও উৎসে কর ১-ক্লিকে গণনা করুন।</p>
                                            <a href="/calculators/registration" class="calc-cta-btn" target="_blank">রেজিস্ট্রেশন ক্যালকুলেটরে হিসাব দেখুন &rarr;</a>
                                        </div>
                                    `);
                                }
                            },
                            {
                                type: 'menuitem',
                                text: '📊 রেট তুলনা টেবিল (Comparison Table)',
                                onAction: function () {
                                    editor.insertContent(`
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>এলাকা / সেক্টর</th>
                                                    <th>কাঠাপ্রতি জমির গড় দর (২০২৬)</th>
                                                    <th>রেডি ফ্ল্যাট দর (sqft)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>আফতাবনগর (ব্লক A, B, C)</strong></td>
                                                    <td>১.৩০ কোটি — ১.৭০ কোটি টাকা</td>
                                                    <td>৭,৫০০ — ৯,৫০০ টাকা</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>বসুন্ধরা আ/এ (ব্লক A - D)</strong></td>
                                                    <td>১.৪০ কোটি — ২.২০ কোটি টাকা</td>
                                                    <td>৯,০০০ — ১৪,০০০ টাকা</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>বনশ্রী আ/এ</strong></td>
                                                    <td>৯৫ লাখ — ১.৪৫ কোটি টাকা</td>
                                                    <td>৬,৮০০ — ৮,৫০০ টাকা</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    `);
                                }
                            },
                            {
                                type: 'menuitem',
                                text: '💬 লার্জ পুলকোট (Medium Pullquote)',
                                onAction: function () {
                                    editor.insertContent(`
                                        <blockquote style="font-size: 1.25rem; font-style: italic; line-height: 1.6; border-left: 4px solid #059669; padding-left: 1.5rem; margin: 2rem 0; color: #047857;">
                                            "জমির মালিকানা নিশ্চিত করার মূল চাবিকাঠি হলো সর্বশেষ নামজারি খতিয়ান ও খাজনা পরিশোধের ডিসিআর যাচাই করা।"
                                        </blockquote>
                                    `);
                                }
                            }
                        ];
                        callback(items);
                    }
                });
            }
        });
    }

    // Trigger on both regular DOM load and Hotwire Turbo navigation
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initGuideEditor, 50);
    }
    document.addEventListener('DOMContentLoaded', initGuideEditor);
    document.addEventListener('turbo:load', initGuideEditor);
    document.addEventListener('turbo:render', initGuideEditor);

    // Clean up TinyMCE before Turbo caches the page
    document.addEventListener('turbo:before-cache', function () {
        if (window.tinymce && tinymce.get('guide-wysiwyg-editor')) {
            tinymce.get('guide-wysiwyg-editor').remove();
        }
    });

    // Make sure TinyMCE syncs back to textarea before submit
    document.addEventListener('submit', function (e) {
        if (e.target && e.target.id === 'guide-post-form') {
            if (window.tinymce && tinymce.get('guide-wysiwyg-editor')) {
                tinymce.get('guide-wysiwyg-editor').save();
            }
        }
    });

    // Global Gutenberg / Medium Quick Block Inserter
    window.insertEditorBlock = function(type) {
        const editor = window.tinymce ? tinymce.get('guide-wysiwyg-editor') : null;
        if (!editor) {
            alert('এডিটর প্রস্তুত হচ্ছে, অনুগ্রহ করে এক সেকেন্ড অপেক্ষা করুন...');
            return;
        }

        if (type === 'info') {
            editor.insertContent(`
                <div class="info-callout" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-left: 5px solid #059669; padding: 1.25rem 1.5rem; border-radius: 0.75rem; margin: 1.75rem 0;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #065f46; font-size: 1.15rem; font-weight: bold;">💡 গুরুত্বপূর্ণ তথ্য / পরামর্শ</h4>
                    <p style="margin: 0; color: #1e293b; line-height: 1.7;">এখানে আপনার গুরুত্বপূর্ণ আইনি বা বাজার সংক্রান্ত পরামর্শ লিখুন...</p>
                </div>
                <p></p>
            `);
        } else if (type === 'warning') {
            editor.insertContent(`
                <div class="warning-callout" style="background-color: #fffbeb; border: 1px solid #fde68a; border-left: 5px solid #d97706; padding: 1.25rem 1.5rem; border-radius: 0.75rem; margin: 1.75rem 0;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #92400e; font-size: 1.15rem; font-weight: bold;">⚠️ সতর্কতা ও আইনি ঝুঁকি</h4>
                    <p style="margin: 0; color: #1e293b; line-height: 1.7;">যেকোনো জমি বা ফ্ল্যাট কেনার আগে এই সতর্কতামূলক দিকটি সরেজমিন যাচাই করুন...</p>
                </div>
                <p></p>
            `);
        } else if (type === 'calc') {
            editor.insertContent(`
                <div class="calc-cta-widget" style="background: linear-gradient(135deg, #0f172a 0%, #064e3b 100%); color: #ffffff; padding: 1.75rem; border-radius: 1rem; margin: 2rem 0; text-align: center;">
                    <h3 style="color: #ffffff; margin: 0 0 0.5rem 0; font-size: 1.4rem; font-weight: 800;">জমি ও ফ্ল্যাট রেজিস্ট্রেশন খরচ হিসাব করতে চান?</h3>
                    <p style="color: #94a3b8; margin: 0 0 1rem 0; font-size: 0.95rem;">২০২৬ সালের সরকারি স্ট্যাম্প শুল্ক, নিবন্ধন ফি ও উৎসে কর ১-ক্লিকে গণনা করুন।</p>
                    <a href="/calculators/registration" style="display: inline-block; background-color: #059669; color: #ffffff !important; padding: 0.65rem 1.35rem; border-radius: 0.6rem; font-weight: bold; text-decoration: none; margin-top: 0.5rem;" target="_blank">রেজিস্ট্রেশন ক্যালকুলেটরে হিসাব দেখুন &rarr;</a>
                </div>
                <p></p>
            `);
        } else if (type === 'table') {
            editor.insertContent(`
                <table style="width: 100%; border-collapse: collapse; margin: 1.75em 0; border: 1px solid #cbd5e1;">
                    <thead>
                        <tr style="background-color: #f1f5f9;">
                            <th style="padding: 10px 14px; font-weight: bold; border: 1px solid #cbd5e1; text-align: left;">এলাকা / সেক্টর</th>
                            <th style="padding: 10px 14px; font-weight: bold; border: 1px solid #cbd5e1; text-align: left;">কাঠাপ্রতি জমির গড় দর (২০২৬)</th>
                            <th style="padding: 10px 14px; font-weight: bold; border: 1px solid #cbd5e1; text-align: left;">রেডি ফ্ল্যাট দর (sqft)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;"><strong>আফতাবনগর (ব্লক A, B, C)</strong></td>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;">১.৩০ কোটি — ১.৭০ কোটি টাকা</td>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;">৭,৫০০ — ৯,৫০০ টাকা</td>
                        </tr>
                        <tr style="background-color: #f8fafc;">
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;"><strong>বসুন্ধরা আ/এ (ব্লক A - D)</strong></td>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;">১.৪০ কোটি — ২.২০ কোটি টাকা</td>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;">৯,০০০ — ১৪,০০০ টাকা</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;"><strong>বনশ্রী আ/এ</strong></td>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;">৯৫ লাখ — ১.৪৫ কোটি টাকা</td>
                            <td style="padding: 10px 14px; border: 1px solid #e2e8f0;">৬,৮০০ — ৮,৫০০ টাকা</td>
                        </tr>
                    </tbody>
                </table>
                <p></p>
            `);
        } else if (type === 'quote') {
            editor.insertContent(`
                <blockquote style="font-size: 1.25rem; font-style: italic; line-height: 1.6; border-left: 4px solid #059669; padding: 1.2rem 1.5rem; margin: 2rem 0; color: #047857; background-color: #f0fdf4; border-radius: 0 1rem 1rem 0;">
                    "জমির মালিকানা নিশ্চিত করার মূল চাবিকাঠি হলো সর্বশেষ নামজারি খতিয়ান ও খাজনা পরিশোধের ডিসিআর সরেজমিন যাচাই করা।"
                </blockquote>
                <p></p>
            `);
        }
        editor.focus();
    };
})();
</script>
@endpush
