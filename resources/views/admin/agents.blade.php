@extends('layouts.admin')

@section('title', 'এজেন্ট ও মালিক ডিরেক্টরি')
@section('page_title', 'এজেন্ট ও মালিক ডিরেক্টরি (Agents & Owners)')

@section('content')
<div class="space-y-6" x-data="agentManager()">

    <!-- Stats and Filter Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <a href="{{ route('admin.agents.index', ['role' => 'all']) }}" class="px-3.5 py-2 rounded-xl border {{ $role === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                মোট সদস্য: {{ $stats['total_agents'] + $stats['total_owners'] }}
            </a>
            <a href="{{ route('admin.agents.index', ['role' => 'agent']) }}" class="px-3.5 py-2 rounded-xl border {{ $role === 'agent' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                💼 ভেরিফাইড এজেন্ট: {{ $stats['total_agents'] }}
            </a>
            <a href="{{ route('admin.agents.index', ['role' => 'owner']) }}" class="px-3.5 py-2 rounded-xl border {{ $role === 'owner' ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50 text-blue-800 border-blue-200 hover:bg-blue-100' }}">
                🏠 সরাসরি মালিক: {{ $stats['total_owners'] }}
            </a>
            <span class="px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-semibold">
                ⚠️ পেনাল্টি প্রাপ্ত: {{ $stats['penalized'] }}
            </span>
        </div>

        <form method="GET" action="{{ route('admin.agents.index') }}" class="flex items-center gap-2 text-xs">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম, ফোন বা ইমেইল দিয়ে খুঁজুন..." class="px-3 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition">খুঁজুন</button>
        </form>
    </div>

    <!-- Agents Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-3.5">সদস্যের বিবরণ</th>
                        <th class="p-3.5">ভূমিকা ও রেটিং</th>
                        <th class="p-3.5">প্রপার্টি সংখ্যা</th>
                        <th class="p-3.5">ম্যাজিক লগইন লিঙ্ক (1-Click)</th>
                        <th class="p-3.5">হোয়াটসঅ্যাপ</th>
                        <th class="p-3.5 text-center">ম্যানেজ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-[11px] text-slate-500">📞 {{ $user->phone }} | ✉️ {{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] {{ $user->role === 'agent' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $user->role === 'agent' ? 'এজেন্ট' : 'মালিক' }}
                                </span>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    ⭐ রেটিং: <strong class="text-amber-600">{{ $user->agentProfile?->admin_fixed_rating ?? $user->agentProfile?->rating ?? '5.0' }}</strong> / 5.0
                                </div>
                                @if($user->agentProfile?->penalty_status === 'penalty_spam')
                                    <div class="mt-1">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-300 inline-block">
                                            🔴 পেনাল্টি ফ্ল্যাগড
                                        </span>
                                    </div>
                                @elseif($user->agentProfile?->penalty_status === 'bypassed_warning')
                                    <div class="mt-1">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 inline-block">
                                            ⚠️ বাইপাস সতর্কতা
                                        </span>
                                    </div>
                                @elseif($user->agentProfile?->penalty_status === 'unverified')
                                    <div class="mt-1">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 inline-block">
                                            🟡 অননুমোদিত
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="p-3.5 font-bold text-slate-800">
                                {{ $user->properties->count() }} টি
                            </td>
                            <td class="p-3.5">
                                @php
                                    $cleanPhone = preg_replace('/[^0-9]/', '', (string)($user->whatsapp_number ?? $user->phone));
                                    if ($cleanPhone && str_starts_with($cleanPhone, '0')) {
                                        $cleanPhone = '88' . $cleanPhone;
                                    }
                                    $magicLoginMsg = \App\Services\MessageTemplateService::render('magic_login_profile', [
                                        '%name%'             => $user->name,
                                        '%login_magic_link%' => url('/auth/magic/' . $user->magic_token),
                                        '%phone%'            => $user->phone,
                                        '%role%'             => $user->role === 'agent' ? 'Direct Agent' : 'Direct Owner',
                                        '%portal_url%'       => url('/agent-portal'),
                                    ]);
                                    $generalContactMsg = \App\Services\MessageTemplateService::render('agent_general_contact', [
                                        '%name%'             => $user->name,
                                        '%phone%'            => $user->phone,
                                        '%role%'             => $user->role === 'agent' ? 'Direct Agent' : 'Direct Owner',
                                        '%login_magic_link%' => url('/auth/magic/' . $user->magic_token),
                                    ]);
                                @endphp
                                <div class="flex items-center gap-1.5">
                                    <button @click="copyMagicLink('{{ url('/auth/magic/' . $user->magic_token) }}')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-[11px] flex items-center gap-1 transition cursor-pointer" title="ক্লিপবোর্ডে কপি">
                                        <span>📋</span> কপি
                                    </button>
                                    @if($cleanPhone)
                                        <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode($magicLoginMsg) }}" target="_blank" class="px-2 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-lg text-[11px] inline-flex items-center gap-1 transition" title="হোয়াটসঅ্যাপে ম্যাজিক লগইন লিঙ্ক পাঠান">
                                            <span>🔑</span> WA
                                        </a>
                                    @endif
                                    <button @click="regenerateToken({{ $user->id }})" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer" title="নতুন টোকেন জেনারেট করুন">
                                        🔄
                                    </button>
                                </div>
                            </td>
                            <td class="p-3.5">
                                @if($cleanPhone)
                                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode($generalContactMsg) }}" target="_blank" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg text-[11px] inline-flex items-center gap-1 transition" title="হোয়াটসঅ্যাপে বার্তা পাঠান">
                                        <span>💬</span> হোয়াটসঅ্যাপ
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[11px]">নম্বর নেই</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-center">
                                <button @click="openDrawer({{ $user->toJson() }})" class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-xs transition">
                                    ড্রয়ার ওপেন &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-xs text-slate-400">কোনো সদস্য পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Slide-Over Drawer -->
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-sm flex justify-end">
        <div class="w-full max-w-md bg-white h-full shadow-2xl flex flex-col justify-between" @click.away="drawerOpen = false">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900" x-text="currentUser?.name"></h3>
                    <p class="text-xs text-slate-500" x-text="'রোল: ' + currentUser?.role"></p>
                </div>
                <button @click="drawerOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <div class="p-5 space-y-5 flex-1 overflow-y-auto text-xs">
                <!-- Rating and Penalty -->
                <form :action="'/admin/agents/' + currentUser?.id + '/profile'" method="POST" class="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">অ্যাডমিন নির্ধারিত রেটিং (1.0 - 5.0)</label>
                        <input type="number" step="0.1" min="1" max="5" name="rating" :value="currentUser?.agent_profile?.admin_fixed_rating || 5.0" class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">পেনাল্টি স্ট্যাটাস</label>
                        <select name="penalty_status" :value="currentUser?.agent_profile?.penalty_status || 'verified'" class="w-full p-2 border border-slate-300 rounded-lg">
                            <option value="verified">ভেরিফাইড (স্বাভাবিক)</option>
                            <option value="unverified">আনভেরিফাইড</option>
                            <option value="bypassed_warning">সতর্কবার্তা জারি (Warning)</option>
                            <option value="penalty_spam">স্থগিত / পেনাল্টি (Suspended)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">পেনাল্টির কারণ</label>
                        <input type="text" name="penalty_reason" :value="currentUser?.agent_profile?.penalty_reason || ''" placeholder="কারণ লিখুন..." class="w-full p-2 border border-slate-300 rounded-lg">
                    </div>
                    <button type="submit" class="w-full py-2 bg-slate-900 text-white font-bold rounded-lg hover:bg-slate-800 transition">
                        প্রোফাইল আপডেট করুন
                    </button>
                </form>

                <!-- CRM Notes Form -->
                <form :action="'/admin/agents/' + currentUser?.id + '/note'" method="POST" class="space-y-2">
                    @csrf
                    <label class="block font-bold text-slate-800">অভ্যন্তরীণ অ্যাডমিন নোট যোগ করুন</label>
                    <textarea name="note" rows="3" required placeholder="এই এজেন্ট বা মালিক সম্পর্কে কোনো গুরুত্বপূর্ণ পর্যবেক্ষণ..." class="w-full p-2.5 border border-slate-300 rounded-xl"></textarea>
                    <button type="submit" class="w-full py-2 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition">
                        নোট সেভ করুন
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function agentManager() {
    return {
        drawerOpen: false,
        currentUser: null,

        openDrawer(user) {
            this.currentUser = user;
            this.drawerOpen = true;
        },

        copyMagicLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('ম্যাজিক লগইন লিঙ্ক ক্লিপবোর্ডে কপি করা হয়েছে!\n\n' + url);
            });
        },

        regenerateToken(userId) {
            if (!confirm('আপনি কি এই সদস্যের জন্য নতুন ম্যাজিক টোকেন জেনারেট করতে চান? পূর্বের লিঙ্কটি আর কাজ করবে না।')) return;
            fetch(`{{ url('admin/agents') }}/${userId}/magic-token`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('নতুন ম্যাজিক লিঙ্ক তৈরি করা হয়েছে:\n\n' + data.url);
                    window.location.reload();
                }
            });
        }
    }
}
</script>
@endpush
