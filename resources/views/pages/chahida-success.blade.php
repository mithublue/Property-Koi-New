@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 space-y-8">

    <!-- Success Card -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-10 shadow-lg text-center space-y-4">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-inner">
            ✓
        </div>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-900">
            আপনার প্রপার্টি চাহিদা সফলভাবে গৃহীত হয়েছে!
        </h1>

        <p class="text-sm text-slate-500 max-w-lg mx-auto">
            আপনার রিকোয়ারমেন্ট কোড: <strong class="font-mono text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-base">{{ $demand->requirement_code }}</strong>। এটি সংরক্ষণ করে রাখুন।
        </p>

        <!-- Unsubscribe Reassurance Link -->
        <div class="pt-2 text-xs text-slate-400">
            ভবিষ্যতে নোটিফিকেশন বন্ধ করতে চাইলে:
            <a href="{{ route('demand.unsubscribe', $demand->unsubscribe_token) }}" class="text-rose-500 underline hover:text-rose-600 font-medium ml-1">
                এখানে ক্লিক করে আনসাবস্ক্রাইব করতে পারেন
            </a>
        </div>
    </div>

    <!-- ⚡ Telegram 1-Click Connect & Alert Card -->
    @php
        $teleConnected = !empty($demand->telegram_chat_id);
        $teleChannelUrl = \App\Services\TelegramService::getChannelJoinUrl();
        $teleBotUser = \App\Services\TelegramService::getBotUsername();
    @endphp

    <div x-data="{
            connected: {{ $teleConnected ? 'true' : 'false' }},
            chatId: '{{ $demand->telegram_chat_id }}',
            username: '{{ $demand->telegram_username ?? '' }}',
            checking: false,
            pollTimer: null,
            init() {
                if (!this.connected) {
                    this.startPolling();
                }
            },
            startPolling() {
                if (this.pollTimer) clearInterval(this.pollTimer);
                this.pollTimer = setInterval(() => {
                    this.checkStatus(false);
                }, 3500);
            },
            checkStatus(manual = true) {
                if (manual) this.checking = true;
                fetch('{{ route('telegram.status', $demand->requirement_code) }}', {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.connected) {
                        this.connected = true;
                        this.chatId = data.chat_id;
                        this.username = data.username || '';
                        if (this.pollTimer) clearInterval(this.pollTimer);
                    }
                })
                .catch(() => {})
                .finally(() => {
                    if (manual) this.checking = false;
                });
            }
         }"
         class="rounded-3xl border transition-all duration-300 p-6 sm:p-8"
         :class="connected ? 'bg-gradient-to-br from-emerald-50 via-teal-50 to-white border-emerald-300 shadow-md' : 'bg-gradient-to-br from-sky-50 via-indigo-50/40 to-white border-sky-200 shadow-lg'">

        <!-- State 1: Connected -->
        <template x-if="connected">
            <div class="space-y-4">
                <div class="flex items-start sm:items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-2xl shadow-sm">
                            ✓
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 m-0 flex items-center gap-2">
                                <span>টেলিগ্রাম অ্যালার্ট সফলভাবে সক্রিয় হয়েছে!</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Active</span>
                            </h2>
                            <p class="text-xs text-slate-600 m-0 mt-0.5">
                                নতুন কোনো প্রপার্টি লিস্টিং ম্যাচ হওয়া মাত্রই আপনি সরাসরি আপনার টেলিগ্রাম অ্যাপে অ্যালার্ট পাবেন।
                            </p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="inline-block text-[11px] font-mono bg-white px-3 py-1 rounded-xl border border-emerald-200 text-emerald-950 font-bold shadow-xs">
                            Chat ID: <span x-text="chatId"></span>
                            <span x-show="username" class="ml-1 text-slate-500 font-normal">(@<span x-text="username"></span>)</span>
                        </span>
                    </div>
                </div>

                @if($teleChannelUrl)
                    <div class="pt-2 border-t border-emerald-100/80 flex items-center justify-between flex-wrap gap-2 text-xs">
                        <span class="text-slate-600">এছাড়াও আমাদের পাবলিক চ্যানেলে যুক্ত হয়ে সকল হট ডিল ও অফার দেখতে পারেন:</span>
                        <a href="{{ $teleChannelUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow-xs transition">
                            <span>📢 টেলিগ্রাম চ্যানেলে জয়েন করুন</span> &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </template>

        <!-- State 2: Not connected yet - 1-Click Connect Button -->
        <template x-if="!connected">
            <div class="space-y-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-sky-500 text-white flex items-center justify-center text-2xl shadow-sm">
                            🤖
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-black text-slate-900 m-0">টেলিগ্রামে ১-ক্লিকে নোটিফিকেশন চালু করুন</h2>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-300 animate-pulse">১-ক্লিক সংযোগ</span>
                            </div>
                            <p class="text-xs text-slate-600 m-0 mt-0.5">
                                কোনো চ্যাট আইডি বা তথ্য লিখতে হবে না! শুধু টেলিগ্রাম চ্যানেলে যেভাবে জয়েন করেন, সেভাবে নিচের বাটনে ক্লিক করে 'START' চাপুন।
                            </p>
                        </div>
                    </div>

                    <!-- Auto polling badge -->
                    <div class="flex items-center gap-2 text-[11px] text-sky-800 bg-sky-100/70 border border-sky-200 px-3 py-1 rounded-xl">
                        <span class="w-2 h-2 rounded-full bg-sky-500 animate-ping"></span>
                        <span>সংযোগের অপেক্ষায়...</span>
                    </div>
                </div>

                <!-- Action CTA Buttons -->
                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <a href="{{ route('telegram.connect', $demand->requirement_code) }}" target="_blank" rel="noopener"
                       style="background-color: #0284c7 !important; background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%) !important; color: #ffffff !important;"
                       class="inline-flex items-center gap-2.5 px-6 py-3.5 hover:opacity-95 text-white font-black text-sm rounded-2xl shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5 cursor-pointer">
                        <svg class="w-5 h-5 shrink-0" style="fill: #ffffff !important;" viewBox="0 0 24 24">
                            <path fill="#ffffff" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                        <span style="color: #ffffff !important;">⚡ ১-ক্লিকে টেলিগ্রাম চালু করুন</span>
                    </a>

                    <button type="button" @click="checkStatus(true)" :disabled="checking"
                            class="inline-flex items-center gap-1.5 px-4 py-3 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-bold text-xs rounded-2xl shadow-xs transition cursor-pointer">
                        <span x-show="!checking">🔄 স্ট্যাটাস চেক করুন</span>
                        <span x-show="checking" class="flex items-center gap-1">
                            <span class="inline-block animate-spin">⏳</span> যাচাই হচ্ছে...
                        </span>
                    </button>

                    @if($teleChannelUrl)
                        <a href="{{ $teleChannelUrl }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 px-4 py-3 bg-sky-50 hover:bg-sky-100 text-sky-800 border border-sky-300 font-bold text-xs rounded-2xl shadow-xs transition">
                            <span>📢 টেলিগ্রাম চ্যানেলে জয়েন করুন</span>
                        </a>
                    @endif
                </div>

                <div class="p-3 bg-white/80 rounded-xl border border-sky-200/80 text-[11px] text-slate-600 flex items-center gap-2">
                    <span class="text-base">💡</span>
                    <span>বাটনে চাপলে টেলিগ্রাম অ্যাপে <strong>{{ '@' . $teleBotUser }}</strong> ওপেন হবে এবং নিচে একটি <strong>START</strong> বোতাম আসবে। শুধু 'START'-এ চাপলেই এই চাহিদাটির সাথে টেলিগ্রাম স্বয়ংক্রিয়ভাবে কানেক্ট হয়ে যাবে।</span>
                </div>
            </div>
        </template>
    </div>

    <!-- Matched Properties if found -->
    @if(!empty($matches))
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-900">
                    🎯 আপনার চাহিদার সাথে মিলে এমন {{ count($matches) }}টি প্রপার্টি পাওয়া গেছে
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($matches as $m)
                    <x-property-card :property="$m['property']" />
                @endforeach
            </div>
        </div>
    @endif

</div>

@if(session('just_submitted') || session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.4);
            } catch (e) {}
        }

        function triggerNativeNotification(title, body, url) {
            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    const iconUrl = (window.admVars && window.admVars.baseUrl)
                        ? window.admVars.baseUrl.replace(/\/+$/, '') + '/assets/icons/icon-192x192.png'
                        : '/assets/icons/icon-192x192.png';
                    
                    if (navigator.serviceWorker && navigator.serviceWorker.ready) {
                        navigator.serviceWorker.ready.then(function(reg) {
                            if (reg && reg.showNotification) {
                                reg.showNotification(title, {
                                    body: body,
                                    icon: iconUrl,
                                    badge: iconUrl,
                                    data: { url: url }
                                });
                            } else {
                                new Notification(title, { body: body, icon: iconUrl, data: { url: url } });
                            }
                        }).catch(function() {
                            new Notification(title, { body: body, icon: iconUrl, data: { url: url } });
                        });
                    } else {
                        new Notification(title, { body: body, icon: iconUrl, data: { url: url } });
                    }
                } catch (e) {}
            }
        }

        // Notification 1: Demand Submission Confirmation
        setTimeout(function() {
            playChime();
            const title1 = '📋 আপনার চাহিদা রিকোয়েস্ট জমা হয়েছে!';
            const body1 = 'রিকোয়ারমেন্ট কোড: #{{ $demand->requirement_code }}। চাহিদাটি সফলভাবে সিস্টেমে সংরক্ষিত হয়েছে।';
            const url1 = window.location.href;

            if (typeof window.admShowInAppToast === 'function') {
                window.admShowInAppToast(title1, body1, url1);
            }
            triggerNativeNotification(title1, body1, url1);
        }, 500);

        // Notification 2: Property Match Alert (if matches found)
        @if(!empty($matches) && count($matches) > 0)
        setTimeout(function() {
            playChime();
            const matchCount = {{ count($matches) }};
            const firstPropTitle = @json($matches[0]['property']->title ?? '');
            const title2 = '🎯 ' + matchCount + 'টি নতুন প্রপার্টি ম্যাচ অ্যালার্ট!';
            const body2 = 'আপনার চাহিদার সাথে মিলে এমন ' + matchCount + 'টি প্রপার্টি পাওয়া গেছে।' + (firstPropTitle ? ' প্রথমটি: ' + firstPropTitle : '');
            const url2 = @json(url('/property/' . ($matches[0]['property']->slug ?? '')));

            if (typeof window.admShowInAppToast === 'function') {
                window.admShowInAppToast(title2, body2, url2);
            }
            triggerNativeNotification(title2, body2, url2);
        }, 2400);
        @endif
    });
</script>
@endif
@endsection
