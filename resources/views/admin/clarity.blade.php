@extends('layouts.admin')

@section('title', 'Microsoft Clarity — সেশন রেকর্ডিং ও হিটম্যাপ')
@section('page_title', 'Microsoft Clarity অ্যানালিটিক্স')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{ showConfigForm: {{ !$isConfigured ? 'true' : 'false' }} }">

    <!-- Top Status Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-2xl">📈</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Microsoft Clarity Web Analytics & Session Replay</span>
                    @if($isConfigured && $isEnabled)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            সক্রিয় ও ট্র্যাকিং চালু
                        </span>
                    @elseif($isConfigured && !$isEnabled)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            নিষ্ক্রিয় (Disabled)
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            প্রজেক্ট আইডি নেই
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white m-0">Microsoft Clarity কন্ট্রোল ড্যাশবোর্ড</h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    WordPress সাইটের মতো একই অফিসিয়াল ক্ল্যারিটি ইঞ্জিন। লাইভ ব্যবহারকারীদের স্ক্রিন রেকর্ডিং, ক্লিক হিটম্যাপ, স্ক্রোল ডেপথ, প্রপার্টি শেয়ার এবং রিয়েল এস্টেট কনভার্সন ট্র্যাক করুন।
                </p>
            </div>

            <div class="flex flex-wrap md:flex-col items-stretch gap-2.5 shrink-0">
                <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener noreferrer"
                   class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                    <span>📊</span>
                    <span>ফুল ক্ল্যারিটি ড্যাশবোর্ড</span>
                    <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
                <button type="button" @click="showConfigForm = !showConfigForm"
                        class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs rounded-xl transition flex items-center justify-center gap-2 cursor-pointer">
                    <span>⚙️</span>
                    <span x-text="showConfigForm ? 'কনফিগারেশন লুকান' : 'প্রজেক্ট আইডি ও সেটিংস পরিবর্তন'"></span>
                </button>
            </div>
        </div>

        @if($isConfigured)
            <div class="mt-6 pt-6 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block text-[11px]">প্রজেক্ট আইডি (Project ID):</span>
                    <span class="font-mono font-bold text-emerald-400 text-sm select-all">{{ $projectId }}</span>
                </div>
                <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block text-[11px]">অ্যাডমিন এক্সক্লুশন:</span>
                    <span class="font-bold {{ $excludeAdmin ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ $excludeAdmin ? '✅ অ্যাডমিন সেশন বাদ দেওয়া হচ্ছে' : '⚠️ অ্যাডমিন সেশন রেকর্ড হচ্ছে' }}
                    </span>
                </div>
                <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block text-[11px]">রোল ও ইউজার ট্র্যাকিং:</span>
                    <span class="font-bold {{ $trackRoles ? 'text-emerald-400' : 'text-slate-400' }}">
                        {{ $trackRoles ? '✅ বায়ার/এজেন্ট রোল ট্যাগিং চালু' : '❌ বন্ধ' }}
                    </span>
                </div>
                <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                    <span class="text-slate-400 block text-[11px]">ইনপুট ডেটা মাস্কিং:</span>
                    <span class="font-bold {{ $maskInputs ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $maskInputs ? '🛡️ সংবেদনশীল ডেটা সুরক্ষিত' : '⚠️ মাস্কিং বন্ধ' }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Inline Configuration & Project ID Switcher Panel -->
    <div x-show="showConfigForm" x-cloak class="bg-white rounded-3xl border border-slate-200 shadow-xl p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-xl">
                    🚀
                </div>
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900 m-0">Microsoft Clarity প্রজেক্ট আইডি ও ট্র্যাকিং কনফিগারেশন</h2>
                    <p class="text-xs text-slate-500 m-0 mt-0.5">আপনার Clarity Project ID এবং প্রাইভেসি/ট্র্যাকিং অপশন এখান থেকে সরাসরি নিয়ন্ত্রণ করুন</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.clarity.save-project') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-800 mb-1.5">Microsoft Clarity Project ID:</label>
                    <input type="text" name="project_id" value="{{ $projectId }}" placeholder="যেমন: p3k7x9zq1m"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono text-sm font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">Clarity ড্যাশবোর্ডের Settings &rarr; Overview থেকে ১০ অক্ষরের Project ID কপি করে দিন।</p>
                </div>

                <div class="space-y-2.5 pt-1 text-xs">
                    <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" {{ $isEnabled || !$isConfigured ? 'checked' : '' }} class="rounded text-indigo-600">
                        <span class="font-bold text-slate-800">ফ্রন্টএন্ডে Microsoft Clarity ট্র্যাকিং সক্রিয় রাখুন</span>
                    </label>
                    <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                        <input type="hidden" name="exclude_admin" value="0">
                        <input type="checkbox" name="exclude_admin" value="1" {{ $excludeAdmin ? 'checked' : '' }} class="rounded text-indigo-600">
                        <span class="font-bold text-slate-800">অ্যাডমিনদের ভিজিট সেশন রেকর্ডিং থেকে বাদ দিন (Exclude Admin)</span>
                    </label>
                    <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                        <input type="hidden" name="track_roles" value="0">
                        <input type="checkbox" name="track_roles" value="1" {{ $trackRoles ? 'checked' : '' }} class="rounded text-indigo-600">
                        <span class="font-bold text-slate-800">লগইনকৃত ক্রেতা/এজেন্ট রোল ও ইভেন্ট ট্যাগিং চালু রাখুন</span>
                    </label>
                    <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                        <input type="hidden" name="mask_inputs" value="0">
                        <input type="checkbox" name="mask_inputs" value="1" {{ $maskInputs ? 'checked' : '' }} class="rounded text-indigo-600">
                        <span class="font-bold text-slate-800">ফর্মের সংবেদনশীল ইনপুট ডেটা মাস্ক করুন (Privacy Masking)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer">
                    <span>💾</span>
                    <span>ক্ল্যারিটি কনফিগারেশন সেভ করুন</span>
                </button>
            </div>
        </form>
    </div>

    @if($isConfigured)
        <!-- Action Quick-Launch Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Recordings Card -->
            <a href="{{ $recordingsUrl }}" target="_blank" rel="noopener noreferrer"
               class="group p-6 bg-white hover:bg-gradient-to-br hover:from-white hover:to-indigo-50/50 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-200 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 group-hover:bg-indigo-600 text-indigo-600 group-hover:text-white transition flex items-center justify-center text-2xl shadow-inner">
                        🎥
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900 group-hover:text-indigo-600 transition m-0">সেশন ভিডিও রেকর্ডিং</h3>
                    <p class="text-xs text-slate-500 leading-relaxed m-0">
                        ভিজিটররা কীভাবে মাউস মুভ করছেন, পেজের কোথায় আটকে যাচ্ছেন বা বাউন্স করছেন তার হুবহু ভিডিও রিপ্লে দেখুন।
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-indigo-600">
                    <span>রেকর্ডিংস ওপেন করুন</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <!-- Heatmaps Card -->
            <a href="{{ $heatmapsUrl }}" target="_blank" rel="noopener noreferrer"
               class="group p-6 bg-white hover:bg-gradient-to-br hover:from-white hover:to-emerald-50/50 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-200 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 group-hover:bg-emerald-600 text-emerald-600 group-hover:text-white transition flex items-center justify-center text-2xl shadow-inner">
                        🗺️
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900 group-hover:text-emerald-600 transition m-0">ক্লিক ও স্ক্রোল হিটম্যাপ</h3>
                    <p class="text-xs text-slate-500 leading-relaxed m-0">
                        পেজের কোন বাটন ও ছবিতে ক্রেতারা সবচেয়ে বেশি ক্লিক করছেন এবং পেজের কত শতাংশ গভীরতা পর্যন্ত স্ক্রোল করছেন তা ভিজ্যুয়ালি দেখুন।
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-emerald-600">
                    <span>হিটম্যাপ দেখুন</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <!-- Conversion Insights Card -->
            <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener noreferrer"
               class="group p-6 bg-white hover:bg-gradient-to-br hover:from-white hover:to-amber-50/50 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-200 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 group-hover:bg-amber-600 text-amber-600 group-hover:text-white transition flex items-center justify-center text-2xl shadow-inner">
                        ⚡
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900 group-hover:text-amber-600 transition m-0">রিয়েল এস্টেট ইভেন্ট ও ফানেল</h3>
                    <p class="text-xs text-slate-500 leading-relaxed m-0">
                        হোয়াটসঅ্যাপ ক্লিক, প্রপার্টি শেয়ার (`property_shared`), চাহিদা ফর্ম সাবমিশন, সাইট ভিজিট বুকিং এবং ফিল্টার ব্যবহারের কনভার্সন মেট্রিক্স।
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-amber-600">
                    <span>মেট্রিক্স অ্যানালাইসিস</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>
        </div>
    @endif

    <!-- Embedded Dashboard / Web Experience Box (1:1 WordPress Clarity Plugin Integration) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-4 sm:p-5 bg-slate-900 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-indigo-400 font-bold text-sm">Microsoft Clarity লাইভ এমবেড পোর্টাল</span>
                @if($projectId)
                    <span class="text-[11px] text-slate-400 font-mono">ID: {{ $projectId }}</span>
                @endif
            </div>
            <div class="flex items-center gap-2 text-xs">
                <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener noreferrer"
                   class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold flex items-center gap-1 transition">
                    <span>নতুন উইন্ডোতে খুলুন ↗</span>
                </a>
            </div>
        </div>

        <div class="relative w-full h-[650px] bg-slate-100">
            <iframe id="clarity-wp-iframe"
                    src="{{ $embedUrl }}"
                    class="w-full h-full border-0"
                    title="Microsoft Clarity Dashboard"
                    sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-storage-access-by-user-activation"
                    loading="lazy">
            </iframe>

            <div class="absolute bottom-4 right-4 bg-slate-900/90 text-white p-3 rounded-2xl shadow-2xl border border-slate-700 text-xs max-w-sm backdrop-blur-sm">
                <p class="m-0 leading-relaxed text-[11px] text-slate-300">
                    💡 যদি ব্রাউজারের থার্ড-পার্টি কুকি পলিসির কারণে আইফ্রেম লোড না হয়, সরাসরি
                    <a href="{{ $dashboardUrl }}" target="_blank" class="text-indigo-400 font-bold hover:underline">এখানে ক্লিক করে অফিশিয়াল ড্যাশবোর্ড</a> ওপেন করুন।
                </p>
            </div>
        </div>
    </div>

</div>

<!-- Official WordPress Clarity Plugin postMessage Bridge -->
<script>
    window.addEventListener('message', function (event) {
        if (event.origin !== 'https://clarity.microsoft.com') return;
        const postedMessage = event.data;
        if (!postedMessage || typeof postedMessage !== 'object') return;

        if (postedMessage.operation === 'PROJECT_ID_CHANGE') {
            const newId = typeof postedMessage.id === 'string' ? postedMessage.id.trim() : '';
            fetch('{{ route('admin.clarity.save-project') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ project_id: newId, enabled: newId ? 1 : 0 })
            }).then(() => {
                window.location.reload();
            });
        }
    }, false);
</script>
@endsection
