<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $options['doc_title'] }} - {{ $siteName }}</title>

    <!-- Google Fonts: Hind Siliguri -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'Hind Siliguri', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
            background-color: #f1f5f9;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .page-break-before {
            page-break-before: always !important;
            break-before: page !important;
        }

        .page-break-after {
            page-break-after: always !important;
            break-after: page !important;
        }

        .avoid-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        @media screen {
            .document-sheet {
                max-width: 210mm;
                min-height: 297mm;
                margin: 20px auto 40px auto;
                background: #ffffff;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
                border-radius: 12px;
                padding: 24mm 20mm;
            }
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .document-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
                min-height: auto !important;
            }
        }

        /* Typography & Body styling for printed documents */
        .pdf-content-body {
            font-size: 14.5px;
            line-height: 1.85;
            color: #1e293b;
        }

        .pdf-content-body h2 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 24px;
            margin-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
        }

        .pdf-content-body h3 {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 18px;
            margin-bottom: 8px;
        }

        .pdf-content-body p {
            margin-top: 10px;
            margin-bottom: 12px;
            text-align: justify;
        }

        .pdf-content-body ul, .pdf-content-body ol {
            padding-left: 20px;
            margin: 12px 0;
        }

        .pdf-content-body li {
            margin: 6px 0;
            line-height: 1.8;
        }

        .pdf-content-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 13px;
        }

        .pdf-content-body th, .pdf-content-body td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }

        .pdf-content-body th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #0f172a;
        }

        .pdf-content-body blockquote {
            border-left: 4px solid #10b981;
            background: #f0fdf4;
            padding: 10px 14px;
            border-radius: 6px;
            margin: 14px 0;
            font-style: normal;
        }
    </style>
</head>
<body>

    <!-- On-Screen Floating Print Action Bar (Hidden during Print) -->
    <header class="no-print sticky top-0 z-50 bg-slate-900 text-white border-b border-slate-800 shadow-xl py-3 px-6">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.guides.export') }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center gap-1.5">
                    <span>&larr; ফিল্টারে ফিরে যান</span>
                </a>
                <div class="h-4 w-px bg-slate-700 hidden sm:block"></div>
                <div>
                    <h1 class="text-sm font-bold text-white truncate max-w-md">{{ $options['doc_title'] }}</h1>
                    <span class="text-[11px] text-emerald-400 font-semibold">মোট {{ $guides->count() }}টি গাইড সংকলিত হয়েছে</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                        onclick="window.print()"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-lg transition flex items-center gap-2 cursor-pointer active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>🖨️ প্রিন্ট করুন / Save as PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Printable Content Container -->
    <main class="document-sheet">

        <!-- ======================================================== -->
        <!-- 1. PROFESSIONAL BOOK COVER PAGE                          -->
        <!-- ======================================================== -->
        @if($options['include_cover'])
            <section class="min-h-[250mm] flex flex-col justify-between p-6 sm:p-12 border-4 border-slate-900 rounded-3xl relative overflow-hidden bg-gradient-to-b from-white via-slate-50 to-emerald-50/30">
                <!-- Top Brand Badge -->
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-6">
                    <div class="flex items-center gap-3">
                        @if($siteLogoUrl)
                            <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="h-10 object-contain">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-black text-xl flex items-center justify-center shadow-md">
                                PK
                            </div>
                            <span class="text-xl font-black text-slate-900 tracking-tight">{{ $siteName }}</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-black bg-emerald-600 text-white uppercase tracking-wider">
                            নলেজ হাব সংকলন ২০২৬
                        </span>
                    </div>
                </div>

                <!-- Center Title & Subtitle -->
                <div class="my-auto py-12 space-y-5 text-center">
                    <span class="text-xs font-black tracking-widest text-emerald-700 uppercase bg-emerald-100/80 px-4 py-1.5 rounded-full inline-block border border-emerald-300">
                        অফিশিয়াল প্রপার্টি ও লিগ্যাল রিসার্চ গাইডলাইন
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-slate-950 leading-tight max-w-2xl mx-auto">
                        {{ $options['doc_title'] }}
                    </h1>
                    <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl mx-auto font-medium">
                        {{ $options['subtitle'] }}
                    </p>
                </div>

                <!-- Cover Bottom Metadata -->
                <div class="border-t-2 border-slate-900 pt-6 grid grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">প্রকাশনা সংস্থা</span>
                        <span class="font-bold text-slate-900">{{ $siteName }} লিগ্যাল ও রিসার্চ সেল</span>
                    </div>
                    <div class="text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">মোট নিবন্ধ সংখ্যা</span>
                        <span class="font-black text-emerald-700 text-sm">{{ $guides->count() }}টি স্বয়ংসম্পূর্ণ গাইড</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block">এক্সপোর্ট তারিখ</span>
                        <span class="font-bold text-slate-900">{{ date('d F, Y') }}</span>
                    </div>
                </div>
            </section>

            <div class="page-break-after"></div>
        @endif

        <!-- ======================================================== -->
        <!-- 2. TABLE OF CONTENTS (সূচিপত্র)                           -->
        <!-- ======================================================== -->
        @if($options['include_toc'])
            <section class="py-6 space-y-6">
                <div class="border-b-2 border-emerald-600 pb-3 flex items-center justify-between">
                    <h2 class="text-2xl font-black text-slate-900 m-0 flex items-center gap-2">
                        <span>📑</span> <span>সূচিপত্র (Table of Contents)</span>
                    </h2>
                    <span class="text-xs font-bold text-slate-500">মোট {{ $guides->count() }}টি বিষয়</span>
                </div>

                <div class="space-y-3">
                    @foreach($guides as $idx => $g)
                        <div class="flex items-baseline justify-between gap-3 p-3 rounded-xl hover:bg-slate-50 border border-slate-100 transition">
                            <div class="flex items-baseline gap-3 flex-1 min-w-0">
                                <span class="font-black text-xs text-emerald-700 w-6 shrink-0">{{ sprintf('%02d', $idx + 1) }}.</span>
                                <div class="min-w-0">
                                    <a href="#guide-{{ $g->id }}" class="font-bold text-slate-900 hover:text-emerald-700 text-sm leading-snug block">
                                        {{ $g->title }}
                                    </a>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">
                                        {{ $g->category_label }} • ⏱️ {{ $g->reading_time_min }} মিনিট পাঠ • প্রকাশিত: {{ $g->formatted_date }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-400 shrink-0">পৃষ্ঠা #{{ $idx + 1 }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="page-break-after"></div>
        @endif

        <!-- ======================================================== -->
        <!-- 3. INDIVIDUAL GUIDE ARTICLES                             -->
        <!-- ======================================================== -->
        @foreach($guides as $index => $guide)
            <article id="guide-{{ $guide->id }}" class="{{ $index > 0 ? 'page-break-before' : '' }} py-6 space-y-6">

                <!-- Article Header -->
                <header class="border-b-2 border-slate-200 pb-4 space-y-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <span class="px-3 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            {{ $guide->category_label }}
                        </span>
                        <div class="flex items-center gap-3 text-xs text-slate-500 font-semibold">
                            <span>⏱️ {{ $guide->reading_time_min }} মিনিট পড়ার সময়</span>
                            <span>•</span>
                            <span>📅 প্রকাশিত: {{ $guide->formatted_date }}</span>
                        </div>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black text-slate-950 leading-tight m-0">
                        {{ $guide->title }}
                    </h1>

                    <div class="text-xs text-slate-400 flex items-center justify-between">
                        <span>অনলাইন লিংক: <strong>{{ url('/guides/' . $guide->slug) }}</strong></span>
                        <span>নিবন্ধ নং #{{ $index + 1 }}</span>
                    </div>
                </header>

                <!-- Featured Image (Optional) -->
                @if($options['include_images'] && !empty($guide->cover_image) && !str_contains($guide->cover_image, 'guide-default'))
                    <div class="avoid-break rounded-2xl overflow-hidden border border-slate-200 shadow-xs max-h-80">
                        <img src="{{ $guide->cover_url }}" alt="{{ $guide->title }}" class="w-full h-auto object-cover max-h-80">
                    </div>
                @endif

                <!-- Excerpt Summary Box -->
                @if(!empty($guide->excerpt))
                    <div class="avoid-break p-4 sm:p-5 rounded-2xl bg-emerald-50/60 border border-emerald-200/90 text-xs sm:text-sm text-emerald-950 leading-relaxed font-medium">
                        <strong class="font-bold text-emerald-900 block mb-1">📌 সারসংক্ষেপ:</strong>
                        {{ $guide->excerpt }}
                    </div>
                @endif

                <!-- Article Full Content Body -->
                <div class="pdf-content-body">
                    {!! $guide->content !!}
                </div>

                <!-- FAQs Section (Optional) -->
                @if($options['include_faqs'] && !empty($guide->faqs) && is_array($guide->faqs))
                    <section class="avoid-break mt-8 pt-6 border-t-2 border-slate-200 space-y-4">
                        <h3 class="text-lg font-black text-slate-900 m-0 flex items-center gap-2">
                            <span>❓</span> <span>সাধারণ জিজ্ঞাসা ও আইনি উত্তর (FAQ)</span>
                        </h3>
                        <div class="space-y-3">
                            @foreach($guide->faqs as $faq)
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm space-y-1">
                                    <div class="font-bold text-slate-900 flex items-start gap-1.5">
                                        <span class="text-emerald-600 font-black">প্র:</span>
                                        <span>{{ $faq['question'] ?? '' }}</span>
                                    </div>
                                    <div class="text-slate-600 leading-relaxed pl-4">
                                        <span class="text-slate-400 font-bold">উ:</span> {{ $faq['answer'] ?? '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Printed Article Footer -->
                <footer class="avoid-break pt-6 mt-8 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-400">
                    <span>{{ $siteName }} নলেজ হাব • সত্যতা যাচাইকৃত প্রপার্টি গাইডলাইন</span>
                    <span>গাইড #{{ $index + 1 }} • {{ $guide->formatted_date }}</span>
                </footer>

            </article>
        @endforeach

    </main>

    <!-- Auto Print Script if requested -->
    @if($options['auto_print'])
        <script>
            window.addEventListener('load', function() {
                // Short timeout to ensure Google fonts & images are fully rendered
                setTimeout(function() {
                    window.print();
                }, 800);
            });
        </script>
    @endif

</body>
</html>
