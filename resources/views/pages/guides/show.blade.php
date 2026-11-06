@extends('layouts.app', ['seo' => $ogMeta])

@section('content')
<div class="bg-gradient-to-b from-slate-50 via-white to-slate-50 min-h-screen py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center flex-wrap gap-2 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">হোম</a>
            <span>/</span>
            <a href="{{ route('guides.index') }}" class="hover:text-emerald-600 transition">প্রপার্টি গাইডস</a>
            <span>/</span>
            <a href="{{ route('guides.index', ['category' => $guide->category]) }}" class="hover:text-emerald-600 transition">{{ $guide->category_label }}</a>
            <span>/</span>
            <span class="text-slate-800 font-medium truncate max-w-[200px] sm:max-w-md">{{ $guide->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

            <!-- Main Article Column (8 Cols) -->
            <article class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/90 p-6 sm:p-10 shadow-xs" itemscope itemtype="https://schema.org/Article">
                <meta itemprop="inLanguage" content="bn-BD">
                <meta itemprop="mainEntityOfPage" content="{{ route('guides.show', $guide->slug) }}">
                @if(!empty($ogMeta['en_profile']['headline']))
                    <meta itemprop="alternateName" content="{{ $ogMeta['en_profile']['headline'] }}">
                @endif
                @if(!empty($ogMeta['en_profile']['abstract']))
                    <meta itemprop="description" content="{{ $ogMeta['en_profile']['abstract'] }}">
                @endif

                <!-- Meta Header -->
                <header class="mb-8 pb-8 border-b border-slate-100">
                    <div class="flex items-center flex-wrap gap-2.5 mb-4">
                        <span class="px-3 py-1 rounded-lg text-xs font-bold border {{ $guide->category_badge_class }}">
                            {{ $guide->category_label }}
                        </span>
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <span>⏱️</span> {{ $guide->reading_time_min }} মিনিট পড়ার সময়
                        </span>
                        <span class="text-xs text-slate-500 flex items-center gap-1">
                            <span>👁️</span> {{ number_format($guide->views_count) }} বার পঠিত
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-snug" itemprop="headline">
                        {{ $guide->title }}
                    </h1>

                    <!-- Summary Box -->
                    @if(!empty($guide->excerpt))
                        <div class="aeo-quick-summary my-6 p-5 sm:p-6 rounded-3xl bg-gradient-to-br from-emerald-50 via-teal-50 to-emerald-50 border-2 border-emerald-500/80 shadow-xs" data-aeo="summary">
                            <div class="flex items-center gap-2 mb-2 text-emerald-900 font-extrabold text-sm sm:text-base">
                                <span class="text-lg">📌</span>
                                <span>সারসংক্ষেপ</span>
                            </div>
                            <p class="text-sm sm:text-base text-emerald-950 font-medium leading-relaxed m-0" itemprop="abstract">
                                {{ $guide->excerpt }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-6 flex items-center justify-between flex-wrap gap-4 pt-4 border-t border-slate-100 text-xs">
                        <div class="flex items-center gap-3" itemprop="author" itemscope itemtype="https://schema.org/Person">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">
                                🏢
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block text-sm" itemprop="name">Property Koi Desk</span>
                                <span class="text-slate-400">আপডেট: <time itemprop="dateModified" datetime="{{ $guide->updated_at ? $guide->updated_at->toIso8601String() : now()->toIso8601String() }}">{{ $guide->formatted_date }}</time></span>
                                <meta itemprop="datePublished" content="{{ $guide->created_at ? $guide->created_at->toIso8601String() : now()->toIso8601String() }}">
                            </div>
                        </div>

                        <!-- Social Share Actions with Conventional Solid Brand Buttons -->
                        <div class="flex items-center flex-wrap gap-2" x-data="{ copied: false }">
                            <span class="text-slate-500 text-xs font-bold mr-1">শেয়ার করুন:</span>

                            <!-- Facebook -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               title="Facebook-এ শেয়ার করুন"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#1877F2] hover:bg-[#166fe5] text-white text-xs font-bold transition shadow-xs hover:shadow-md active:scale-95 group">
                                <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <span>Facebook</span>
                            </a>

                            <!-- WhatsApp -->
                            <a href="https://api.whatsapp.com/send?text={{ urlencode($guide->title . ' ' . url()->current()) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               title="WhatsApp-এ শেয়ার করুন"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold transition shadow-xs hover:shadow-md active:scale-95 group">
                                <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                                    <path d="M12.031 0C5.39 0 0 5.39 0 12.031c0 2.125.555 4.198 1.609 6.02L.062 24l6.133-1.609a12.002 12.002 0 005.836 1.512h.005c6.64 0 12.031-5.391 12.031-12.031 0-3.219-1.25-6.242-3.523-8.516A11.95 11.95 0 0012.031 0zm-.005 21.996h-.004a9.99 9.99 0 01-5.093-1.398l-.367-.219-3.781.992 1.01-3.687-.24-.383a9.972 9.972 0 01-1.531-5.27c0-5.523 4.492-10.016 10.016-10.016 2.672 0 5.187 1.047 7.078 2.938a9.96 9.96 0 012.937 7.078c0 5.523-4.492 10.016-10.02 10.016zm5.485-7.492c-.3-.15-1.773-.875-2.05-.973-.273-.1-.476-.15-.672.15-.2.3-.77.973-.945 1.172-.176.2-.348.223-.648.074-.3-.15-1.27-.469-2.42-1.492-.894-.797-1.496-1.785-1.672-2.086-.176-.3-.02-.465.13-.613.136-.137.3-.348.45-.523.15-.176.2-.3.3-.5.1-.2.05-.375-.023-.523-.075-.15-.672-1.62-.922-2.22-.246-.582-.496-.5-.672-.511-.176-.008-.375-.008-.574-.008-.2 0-.523.074-.797.375-.273.3-1.047 1.023-1.047 2.5 0 1.473 1.074 2.898 1.223 3.098.15.2 2.113 3.227 5.121 4.523.715.309 1.273.492 1.707.63.719.227 1.375.195 1.895.117.578-.086 1.773-.727 2.023-1.426.25-.7.25-1.3.176-1.426-.075-.125-.274-.2-.574-.35z"/>
                                </svg>
                                <span>WhatsApp</span>
                            </a>

                            <!-- Telegram -->
                            <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($guide->title) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               title="Telegram-এ শেয়ার করুন"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#229ED9] hover:bg-[#1e8dbf] text-white text-xs font-bold transition shadow-xs hover:shadow-md active:scale-95 group">
                                <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.195 1.006.131.832.922z"/>
                                </svg>
                                <span>Telegram</span>
                            </a>

                            <!-- Copy Link Button -->
                            <button type="button"
                                    @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2500)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white transition text-xs font-bold cursor-pointer group shadow-xs hover:shadow-md active:scale-95">
                                <svg x-show="!copied" class="w-3.5 h-3.5 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span x-show="!copied">লিংক কপি</span>
                                <span x-show="copied" class="text-emerald-300 font-black">✅ কপি হয়েছে!</span>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                @if(!empty($guide->cover_image))
                    <div class="mb-8 rounded-2xl overflow-hidden bg-slate-900 shadow-sm aspect-video" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                        <img src="{{ $guide->cover_url }}"
                             alt="{{ $guide->title }}"
                             itemprop="url"
                             class="w-full h-full object-cover">
                    </div>
                @endif

                <!-- Article Rich Content Body -->
                <div class="guide-content-body prose prose-slate sm:prose-lg max-w-none prose-headings:font-black prose-headings:tracking-tight prose-headings:text-slate-900 prose-h2:text-xl sm:prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4 prose-p:leading-relaxed prose-p:text-slate-700 prose-li:text-slate-700 prose-table:text-sm prose-th:bg-slate-100 prose-th:p-3 prose-td:p-3 prose-a:text-emerald-600 prose-a:font-bold hover:prose-a:text-emerald-700" itemprop="articleBody">
                    {!! $guide->content !!}
                </div>

                <!-- AEO FAQ Section for this Guide with Microdata -->
                @if(!empty($guide->faqs) && is_array($guide->faqs))
                    <section class="mt-12 pt-8 border-t border-slate-200" itemscope itemtype="https://schema.org/FAQPage">
                        <div class="flex items-center gap-2 mb-6">
                            <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">💡</span>
                            <div>
                                <h3 class="text-xl font-black text-slate-900">গাইড সংক্রান্ত সাধারণ জিজ্ঞাসা (FAQ)</h3>
                                <p class="text-xs text-slate-500">আইনি ও সরকারি নিয়মাবলী সম্পর্কে সুস্পষ্ট ব্যাখ্যা (AI Answer Engine Ready)</p>
                            </div>
                        </div>

                        <div class="space-y-3" x-data="{ openFaq: 0 }">
                            @foreach($guide->faqs as $i => $faq)
                                @if(!empty($faq['question']) && !empty($faq['answer']))
                                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                                        <button type="button"
                                                @click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}"
                                                class="w-full text-left p-4 sm:p-5 font-bold text-sm sm:text-base text-slate-900 flex items-center justify-between gap-4 cursor-pointer hover:text-emerald-700 transition">
                                            <span itemprop="name">{{ $faq['question'] }}</span>
                                            <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === {{ $i }} ? 'rotate-180 text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="openFaq === {{ $i }}" x-collapse class="px-4 sm:px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                            <div itemprop="text">
                                                {!! nl2br(e($faq['answer'])) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Legal Disclaimer & Editorial Transparency -->
                <div class="mt-10 p-5 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs sm:text-sm leading-relaxed flex items-start gap-3">
                    <span class="text-lg shrink-0">⚖️</span>
                    <div>
                        <strong class="font-bold block mb-0.5">আইনি ও সম্পাদকীয় ঘোষণা:</strong>
                        এই গাইডে উল্লেখিত তথ্যাদি ভূমি মন্ত্রণালয়, জাতীয় রাজস্ব বোর্ড (NBR), এবং রাজউকের বিধিমালা ও বিশেষজ্ঞদের বিশ্লেষণ অনুযায়ী সংকলিত। যেকোনো জমি বা ফ্ল্যাট কেনার চূড়ান্ত সিদ্ধান্ত গ্রহণের আগে সংশ্লিষ্ট সাব-রেজিস্ট্রি অফিস বা একজন বিজ্ঞ আইনজীবীর মাধ্যমে কাগজপত্র সরেজমিন যাচাই করার পরামর্শ দেওয়া হচ্ছে।
                    </div>
                </div>

                <!-- End of Article Share Strip -->
                <div class="mt-8 p-5 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4" x-data="{ bottomCopied: false }">
                    <div class="text-center sm:text-left">
                        <span class="text-xs font-black text-slate-900 block">গাইডটি কি আপনার উপকারে এসেছে?</span>
                        <span class="text-[11px] text-slate-500">আপনার পরিচিতদের সাথে শেয়ার করে নিরাপদে জমি ও ফ্ল্যাট কেনাবেচায় সাহায্য করুন।</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap shrink-0">
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                           target="_blank" rel="noopener noreferrer"
                           title="Facebook-এ শেয়ার করুন"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#1877F2] hover:bg-[#166fe5] text-white text-xs font-bold transition shadow-xs active:scale-95">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            <span>Facebook</span>
                        </a>
                        <!-- WhatsApp -->
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($guide->title . ' ' . url()->current()) }}"
                           target="_blank" rel="noopener noreferrer"
                           title="WhatsApp-এ শেয়ার করুন"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold transition shadow-xs active:scale-95">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.031 0C5.39 0 0 5.39 0 12.031c0 2.125.555 4.198 1.609 6.02L.062 24l6.133-1.609a12.002 12.002 0 005.836 1.512h.005c6.64 0 12.031-5.391 12.031-12.031 0-3.219-1.25-6.242-3.523-8.516A11.95 11.95 0 0012.031 0zm-.005 21.996h-.004a9.99 9.99 0 01-5.093-1.398l-.367-.219-3.781.992 1.01-3.687-.24-.383a9.972 9.972 0 01-1.531-5.27c0-5.523 4.492-10.016 10.016-10.016 2.672 0 5.187 1.047 7.078 2.938a9.96 9.96 0 012.937 7.078c0 5.523-4.492 10.016-10.02 10.016zm5.485-7.492c-.3-.15-1.773-.875-2.05-.973-.273-.1-.476-.15-.672.15-.2.3-.77.973-.945 1.172-.176.2-.348.223-.648.074-.3-.15-1.27-.469-2.42-1.492-.894-.797-1.496-1.785-1.672-2.086-.176-.3-.02-.465.13-.613.136-.137.3-.348.45-.523.15-.176.2-.3.3-.5.1-.2.05-.375-.023-.523-.075-.15-.672-1.62-.922-2.22-.246-.582-.496-.5-.672-.511-.176-.008-.375-.008-.574-.008-.2 0-.523.074-.797.375-.273.3-1.047 1.023-1.047 2.5 0 1.473 1.074 2.898 1.223 3.098.15.2 2.113 3.227 5.121 4.523.715.309 1.273.492 1.707.63.719.227 1.375.195 1.895.117.578-.086 1.773-.727 2.023-1.426.25-.7.25-1.3.176-1.426-.075-.125-.274-.2-.574-.35z"/></svg>
                            <span>WhatsApp</span>
                        </a>
                        <!-- Telegram -->
                        <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($guide->title) }}"
                           target="_blank" rel="noopener noreferrer"
                           title="Telegram-এ শেয়ার করুন"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#229ED9] hover:bg-[#1e8dbf] text-white text-xs font-bold transition shadow-xs active:scale-95">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.195 1.006.131.832.922z"/></svg>
                            <span>Telegram</span>
                        </a>
                        <!-- Copy Link -->
                        <button type="button"
                                @click="navigator.clipboard.writeText(window.location.href); bottomCopied = true; setTimeout(() => bottomCopied = false, 2500)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition shadow-xs active:scale-95 cursor-pointer">
                            <span x-show="!bottomCopied">🔗 লিংক কপি</span>
                            <span x-show="bottomCopied" class="text-emerald-300 font-bold">✅ কপি হয়েছে!</span>
                        </button>
                    </div>
                </div>

                <!-- Article Navigation / Bottom CTAs -->
                <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between flex-wrap gap-4">
                    <a href="{{ route('guides.index') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-emerald-600 hover:text-emerald-700">
                        &larr; <span>সকল গাইডে ফিরে যান</span>
                    </a>
                    @auth
                    <a href="{{ route('property.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
                        <span>বিনামূল্যে আপনার প্রপার্টি বিজ্ঞাপন দিন</span> &rarr;
                    </a>
                    @endauth
                </div>

                <!-- Discussion & Inquiries (Comments & Legal Consultation) -->
                <section id="discussion-section" class="mt-12 pt-8 border-t border-slate-200" x-data="{ replyToId: null, replyToName: '' }">
                    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
                        <div>
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 uppercase tracking-wider inline-block mb-1.5">
                                প্রশ্নোত্তর ও আইনি পরামর্শ
                            </span>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 flex items-center gap-2">
                                <span>💬</span>
                                <span>আলোচনা ও বিশেষজ্ঞ মতামত</span>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                    {{ $comments->count() }}টি প্রশ্ন
                                </span>
                            </h3>
                        </div>
                        <a href="#ask-form" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50/70 hover:bg-emerald-100/70 px-3 py-1.5 rounded-lg transition">
                            <span>✏️ প্রশ্ন করুন</span>
                        </a>
                    </div>

                    @if(session('comment_success'))
                        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex items-start gap-3">
                            <span class="text-xl">✅</span>
                            <div>
                                <p class="font-bold">সফলভাবে জমা হয়েছে!</p>
                                <p class="text-xs text-emerald-800 mt-0.5">{{ session('comment_success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-sm flex items-start gap-3">
                            <span class="text-xl">⚠️</span>
                            <div>
                                <p class="font-bold">ত্রুটি!</p>
                                <p class="text-xs text-rose-800 mt-0.5">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Comments List -->
                    <div class="space-y-4 mb-8">
                        @forelse($comments as $comment)
                            <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-2xs">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center font-black text-slate-700 text-sm border border-slate-200 shrink-0">
                                            {{ mb_substr($comment->author_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="text-sm font-bold text-slate-900">{{ $comment->author_name }}</h4>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">পাঠক জিজ্ঞাসা</span>
                                            </div>
                                            <time class="text-[11px] text-slate-400 block mt-0.5">{{ $comment->formatted_date }}</time>
                                        </div>
                                    </div>

                                    <button type="button"
                                            @click="replyToId = {{ $comment->id }}; replyToName = '{{ addslashes($comment->author_name) }}'; document.getElementById('ask-form').scrollIntoView({ behavior: 'smooth' });"
                                            class="text-xs font-semibold text-slate-500 hover:text-emerald-600 transition flex items-center gap-1 px-2.5 py-1 rounded-lg hover:bg-slate-50 cursor-pointer">
                                        <span>↩️</span>
                                        <span>রিপ্লাই</span>
                                    </button>
                                </div>

                                <p class="mt-3 text-sm text-slate-700 leading-relaxed whitespace-pre-line pl-1 sm:pl-13">
                                    {{ $comment->comment }}
                                </p>

                                <!-- Approved Admin / Verified Replies -->
                                @if($comment->approvedReplies->count() > 0)
                                    <div class="mt-4 pt-4 border-t border-slate-100 pl-3 sm:pl-10 space-y-3">
                                        @foreach($comment->approvedReplies as $reply)
                                            <div class="bg-gradient-to-br from-emerald-50/70 to-teal-50/40 rounded-xl p-4 border border-emerald-200/70 relative">
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-black shrink-0">👑</span>
                                                        <span class="text-xs font-bold text-emerald-950">{{ $reply->author_name }}</span>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white shadow-2xs">
                                                            ✓ ভেরিফাইড অ্যাডমিন উত্তর
                                                        </span>
                                                    </div>
                                                    <time class="text-[10px] text-emerald-700/80">{{ $reply->formatted_date }}</time>
                                                </div>
                                                <p class="text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                                                    {{ $reply->comment }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8 px-4 rounded-2xl bg-slate-50/80 border border-dashed border-slate-200">
                                <span class="text-3xl block mb-2">💡</span>
                                <h4 class="text-sm font-bold text-slate-700">এখনও কোনো প্রশ্ন জমা পড়েনি</h4>
                                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                                    এই গাইড বা প্রপার্টি আইন বিষয়ে আপনার কোনো প্রশ্ন বা মতামত থাকলে নিচের ফর্মটি পূরণ করে নির্দ্বিধায় জিজ্ঞাসা করুন।
                                </p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Question / Inquiry Form -->
                    <div id="ask-form" class="bg-gradient-to-br from-slate-50 via-white to-emerald-50/30 rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-xs">
                        <div class="mb-5">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider block w-max mb-1.5">
                                দ্রুত পরামর্শ
                            </span>
                            <h4 class="text-lg font-black text-slate-900">
                                আপনার কোনো প্রশ্ন বা জিজ্ঞাসা আছে?
                            </h4>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                দলিল যাচাই, নামজারি, ফ্ল্যাট ক্রয় বা আইনগত জটিলতায় আপনার প্রশ্ন লিখুন। আমাদের অ্যাডমিন ও লিগ্যাল টিম পর্যালোচনা করে দ্রুত উত্তর প্রদান করবে।
                            </p>
                        </div>

                        <!-- Replying Indicator -->
                        <div x-show="replyToId" x-cloak class="mb-4 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center justify-between">
                            <span>
                                💬 <strong><span x-text="replyToName"></span></strong> এর প্রশ্নের উত্তরে লিখছেন...
                            </span>
                            <button type="button" @click="replyToId = null; replyToName = ''" class="text-emerald-700 hover:text-emerald-900 font-bold ml-2 cursor-pointer">
                                ✕ বাতিল
                            </button>
                        </div>

                        <form action="{{ route('guides.comments.store', $guide->slug) }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="parent_id" :value="replyToId">

                            <!-- Anti-spam honeypot -->
                            <div class="hidden" aria-hidden="true" style="display:none !important;">
                                <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                        আপনার নাম <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" name="author_name" required maxlength="100"
                                           value="{{ auth()->check() ? auth()->user()->name : old('author_name') }}"
                                           placeholder="যেমন: ইঞ্জিনিয়ার রফিকুল ইসলাম"
                                           class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                        মোবাইল / হোয়াটসঅ্যাপ অথবা ইমেইল <span class="text-slate-400 font-normal">(অ্যাডমিন যোগাযোগের জন্য)</span>
                                    </label>
                                    <input type="text" name="author_phone" maxlength="50"
                                           value="{{ auth()->check() ? (auth()->user()->phone ?: auth()->user()->email) : old('author_phone') }}"
                                           placeholder="017XXXXXXXX বা your@email.com"
                                           class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    আপনার প্রশ্ন বা মন্তব্য বিস্তারিত লিখুন <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="comment" rows="4" required minlength="4" maxlength="2000"
                                          placeholder="যেমন: আমার ওয়ারিশ সূত্রে পাওয়া জমির বায়া দলিলে নামের অমিল আছে, এ অবস্থায় নামজারি করতে কি কি অতিরিক্ত কাগজ লাগবে?"
                                          class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition resize-y">{{ old('comment') }}</textarea>
                            </div>

                            <div class="flex items-center justify-between flex-wrap gap-3 pt-1">
                                <span class="text-[11px] text-slate-400 flex items-center gap-1">
                                    🔒 আপনার ফোন নম্বর সম্পূর্ণ গোপন রাখা হয় (পাবলিকলি দৃশ্যমান হবে না)।
                                </span>
                                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs sm:text-sm font-bold shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2 cursor-pointer">
                                    <span>প্রশ্ন বা মন্তব্য জমা দিন</span>
                                    <span>&rarr;</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

            </article>

            <!-- Sidebar Column (4 Cols) -->
            <aside class="lg:col-span-4 space-y-6">

                <!-- Interactive Calculators Widget Card -->
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden">
                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-white/20 text-white uppercase tracking-wider block w-max mb-3">
                        স্মার্ট ক্যালকুলেটর
                    </span>
                    <h3 class="text-lg font-black leading-snug">
                        রেজিস্ট্রেশন বা ল্যান্ড শেয়ারের সাশ্রয় হিসাব করুন
                    </h3>
                    <p class="mt-2 text-xs text-emerald-100 leading-relaxed">
                        স্ট্যাম্প ডিউটি, ফি ও উৎসে করের সরকারি ২০২৬ তালিকা অনুযায়ী নিখুঁত হিসাব করুন মাত্র ১ মিনিটে।
                    </p>
                    <div class="mt-5 space-y-2">
                        <a href="{{ route('calculators.registration') }}" class="w-full py-2.5 px-4 rounded-xl bg-white text-emerald-800 hover:bg-emerald-50 text-xs font-bold transition flex items-center justify-between shadow-xs">
                            <span>🧮 রেজিস্ট্রেশন ফি ক্যালকুলেটর</span>
                            <span>&rarr;</span>
                        </a>
                        <a href="{{ route('calculators.land-share') }}" class="w-full py-2.5 px-4 rounded-xl bg-emerald-800/80 hover:bg-emerald-800 text-white text-xs font-bold transition flex items-center justify-between border border-emerald-500/40">
                            <span>📊 ল্যান্ড শেয়ার ROI ক্যালকুলেটর</span>
                            <span>&rarr;</span>
                        </a>
                        <a href="{{ route('valuation.index') }}" class="w-full py-2.5 px-4 rounded-xl bg-teal-800/80 hover:bg-teal-800 text-white text-xs font-bold transition flex items-center justify-between border border-teal-500/40">
                            <span>💡 প্রপার্টি মার্কেট ভ্যালু যাচাই</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- Contextual Live Properties for Sale -->
                @if($featuredProperties->count() > 0)
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                <span>🏢</span> যাচাইকৃত ভেরিফাইড প্রপার্টি
                            </h3>
                            <a href="{{ route('properties.index') }}" class="text-[11px] font-bold text-emerald-600 hover:underline">সকল দেখুন</a>
                        </div>
                        <div class="space-y-3.5">
                            @foreach($featuredProperties as $prop)
                                <a href="{{ route('property.show', $prop->slug ?: $prop->id) }}" class="flex items-center gap-3 group p-2 rounded-xl hover:bg-slate-50 transition">
                                    <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden shrink-0 relative">
                                        <img src="{{ $prop->cover_url }}" alt="{{ $prop->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[10px] font-bold text-emerald-600 block">{{ $prop->type_label }} • {{ $prop->area ?: 'ঢাকা' }}</span>
                                        <h4 class="text-xs font-bold text-slate-800 truncate group-hover:text-emerald-600 transition-colors">{{ $prop->title }}</h4>
                                        <span class="text-xs font-black text-slate-900 mt-0.5 block">{{ $prop->formatted_price ?: 'আলোচনা সাপেক্ষে' }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Related Guides in Same Category -->
                @if($relatedGuides->count() > 0)
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-1.5">
                            <span>📖</span> সম্পর্কিত গাইড ও পরামর্শ
                        </h3>
                        <div class="space-y-4">
                            @foreach($relatedGuides as $rg)
                                <article class="border-b border-slate-100 last:border-b-0 pb-3 last:pb-0">
                                    <span class="text-[10px] font-semibold text-slate-400 block mb-1">
                                        {{ $rg->category_label }} • {{ $rg->reading_time_min }} মিনিট
                                    </span>
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-800 leading-snug hover:text-emerald-600 transition-colors">
                                        <a href="{{ route('guides.show', $rg->slug) }}">{{ $rg->title }}</a>
                                    </h4>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Buyer Demand Callout -->
                <div class="bg-slate-900 rounded-3xl p-6 text-white shadow-xs">
                    <span class="text-2xl block mb-2">🎯</span>
                    <h3 class="text-base font-black">কাঙ্ক্ষিত জমি বা ফ্ল্যাট খুঁজে পাচ্ছেন না?</h3>
                    <p class="mt-1.5 text-xs text-slate-300 leading-relaxed">
                        আপনার বাজেট ও পছন্দের চাহিদা সাবমিট করুন। সরাসরি মালিকের নতুন প্রপার্টি পেলেই তাৎক্ষণিক মেসেজ পাবেন।
                    </p>
                    <a href="{{ route('chahida.index') }}" class="mt-4 block w-full py-2.5 text-center rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">
                        চাহিদা জমা দিন (বিনামূল্যে)
                    </a>
                </div>

            </aside>

        </div>

    </div>
</div>
@endsection
