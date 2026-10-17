@extends('layouts.admin')

@section('title', 'সিস্টেম ডকুমেন্টেশন ও রুলস')
@section('page_title', 'সিস্টেম ডকুমেন্টেশন ও আর্কিটেকচার রুলস')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 text-xs leading-relaxed">

    <!-- Platform Architecture Overview Card -->
    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>🏛️</span> প্ল্যাটফর্ম টেকনোলজি ও আর্কিটেকচার
        </h3>
        <p class="text-slate-600">
            এই প্ল্যাটফর্মটি <strong>Laravel 11 + Hotwired Turbo + Alpine.js + Tailwind CSS</strong> ভিত্তিক একটি হাইপার-পারফর্ম্যান্ট SPA এবং PWA সিস্টেম। এটি Hosttier বা যেকোনো স্ট্যান্ডার্ড cPanel Shared Hosting-এ খুব কম মেমোরি খরচে উচ্চগতিতে চলতে পারে।
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                <div class="font-bold text-emerald-900 mb-1">⚡ Hotwired Turbo SPA</div>
                <div class="text-[11px] text-emerald-700">ফুল পেজ রিলোড ছাড়া মোবাইল অ্যাপের মত স্মুথ ট্রানজিশন ও নেভিগেশন।</div>
            </div>
            <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                <div class="font-bold text-blue-900 mb-1">📱 Progressive Web App (PWA)</div>
                <div class="text-[11px] text-blue-700">অফলাইন ক্যাশিং ও হোমস্ক্রিন অ্যাপ ইন্সটল সমর্থন।</div>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl border border-purple-200">
                <div class="font-bold text-purple-900 mb-1">🚀 Shared Hosting Ready</div>
                <div class="text-[11px] text-purple-700">কোনো হেভিওয়েট প্যাকেজ (Filament ইত্যাদি) ছাড়া লাইটওয়েট এবং সুপার ফাস্ট।</div>
            </div>
        </div>
    </div>

    <!-- Core Business Rules -->
    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-5">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>⚖️</span> মূল বিজনেস রুলস ও লজিক
        </h3>

        <div class="space-y-4 text-slate-700">
            <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">১. ৪-দফা বাধ্যতামূলক ভেরিফিকেশন (4-Point Verification)</h4>
                <p>
                    একটি প্রপার্টি লাইভ হতে হলে ৪টি চেকলিস্ট সম্পন্ন হতে হবে: ১) মালিকের এনআইডি/পরিচয়পত্র, ২) ফিজিক্যাল লোকেশন, ৩) সাইজ/ফেসিং ও রাস্তা, ৪) প্রাথমিক দলিল বা বরাদ্দপত্র। চারটি চেকলিস্ট ট্রু হলেই প্রপার্টি স্বয়ংক্রিয়ভাবে লাইভ হবে এবং সার্চ ইঞ্জিনগুলোকে IndexNow পিং পাঠানো হবে।
                </p>
            </div>

            <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">২. অ্যান্টি-ডুপ্লিকেট প্লট ও ডিরেক্ট সোর্স মার্জিং</h4>
                <p>
                    একই ব্লক ও প্লট নম্বরের জমি একাধিকবার সাবমিট হলে নতুন কোনো প্রপার্টি রো তৈরি হয় না। বরং বিদ্যমান প্রপার্টির অধীনে একটি নতুন <code>PropertyDirectSource</code> রেকর্ড সংযুক্ত হয়, যা একাধিক ব্রোকার বা মালিকের আলাদা প্রাইস ট্র্যাক করতে সাহায্য করে।
                </p>
            </div>

            <div>
                <h4 class="font-bold text-slate-900 text-sm mb-1">৩. ০.৫% প্ল্যাটফর্ম ফি ও রেভিনিউ ইঞ্জিন</h4>
                <p>
                    যেকোনো সফল লেনদেনে ক্রেতা ও বিক্রেতার সম্মতিক্রমে মোট মূল্যের উপর ০.৫% প্ল্যাটফর্ম ফি প্রযোজ্য হয়। ডিল ক্লোজ হওয়ার পর তা সরাসরি ডাটাবেজের ট্রানজ্যাকশন টেবিলে সংরক্ষিত হয় এবং হিস্টোরিক্যাল আর্কাইভে যোগ হয়।
                </p>
            </div>
        </div>
    </div>

    <!-- Cron Setup for Hosttier cPanel -->
    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>⏰</span> Hosttier cPanel ক্রন জব কনফিগারেশন
        </h3>
        <p class="text-slate-600">
            শেয়ার্ড হোস্টিংয়ে প্রতিদিন স্বয়ংক্রিয়ভাবে ক্রেতাদের চাহিদার সাথে নতুন প্রপার্টি ম্যাচ করাতে এবং নোটিফিকেশন পাঠাতে নিচের ক্রন জবটি cPanel &rarr; Cron Jobs-এ যোগ করুন:
        </p>
        <div class="bg-slate-900 text-emerald-400 p-4 rounded-xl font-mono text-[11px] overflow-x-auto">
            0 9 * * * curl -s "{{ url('/api/cron/match-demands?key=propertykoi_cron_secret_2026') }}" > /dev/null 2>&1
        </div>
        <p class="text-[11px] text-slate-500">
            প্রতিদিন সকাল ৯:০০ টায় এই ক্রন রান করবে এবং নতুন উপযুক্ত প্রপার্টি পেলে ক্রেতাদের ইমেইল ও টেলিগ্রামে অ্যালার্ট পাঠাবে।
        </p>
    </div>

</div>
@endsection
