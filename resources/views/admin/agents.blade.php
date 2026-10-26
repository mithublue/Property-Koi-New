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

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('admin.agents.index') }}" class="flex items-center gap-2 text-xs">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম, ফোন বা ইমেইল দিয়ে খুঁজুন..." class="px-3 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition">খুঁজুন</button>
            </form>
            <button type="button" @click="openAddDrawer()" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                <span>➕</span> নতুন সদস্য
            </button>
        </div>
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
                                <button type="button" @click="openEditDrawer({{ $user->toJson() }})" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-xl text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                    <span>✏️</span> এডিট ও প্রোফাইল &rarr;
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

    <!-- Right-Side Drawer: Add New Member -->
    <div x-show="addDrawerOpen" x-cloak
         class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex justify-end"
         @keydown.window.escape="closeAddDrawer()">
        <div class="w-full max-w-lg bg-white h-full shadow-2xl flex flex-col justify-between"
             x-show="addDrawerOpen"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             @click.outside="closeAddDrawer()">

            <!-- Header -->
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/90">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>➕</span> নতুন এজেন্ট বা মালিক যুক্ত করুন
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">ডিরেক্টরিতে নতুন সদস্যের প্রোফাইল তৈরি করুন</p>
                </div>
                <button type="button" @click="closeAddDrawer()" class="text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer p-1">&times;</button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('admin.agents.store') }}" method="POST" class="p-5 space-y-4 flex-1 overflow-y-auto text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">সদস্যের পুরো নাম *</label>
                        <input type="text" name="name" required placeholder="যেমন: রাশেদুল ইসলাম" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ভূমিকা (Role) *</label>
                        <select name="role" required class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold">
                            <option value="agent">এজেন্ট / মিডিয়া (Agent)</option>
                            <option value="owner">সরাসরি মালিক (Owner)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">মোবাইল নম্বর *</label>
                        <input type="text" name="phone" required placeholder="017XXXXXXXX" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">WhatsApp নম্বর</label>
                        <input type="text" name="whatsapp_number" placeholder="খালি রাখলে ফোন নম্বর ব্যবহৃত হবে" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">এজেন্সি / প্রতিষ্ঠানের নাম</label>
                        <input type="text" name="agency_name" placeholder="যেমন: ঢাকা রিয়েল এস্টেট" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ইমেইল (ঐচ্ছিক)</label>
                        <input type="email" name="email" placeholder="agent@example.com" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">অ্যাডমিন নির্ধারিত রেটিং (১.০ - ৫.০)</label>
                    <input type="number" step="0.1" name="admin_fixed_rating" value="5.0" min="1" max="5" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold">
                </div>

                <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-200">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_verified_direct_agent" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-emerald-900">ভেরিফাইড ব্যাজ সক্রিয় রাখুন (Verified Direct Agent)</span>
                    </label>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">অভ্যন্তরীণ অ্যাডমিন নোট (ঐচ্ছিক)</label>
                    <textarea name="notes" rows="2" placeholder="অফিস লোকেশন বা বিশেষ কোনো তথ্য..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="closeAddDrawer()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition cursor-pointer">
                        বাতিল
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <span>➕</span> সদস্য যুক্ত করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right-Side Drawer: Edit Existing Member -->
    <div x-show="editDrawerOpen" x-cloak
         class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-xs flex justify-end"
         @keydown.window.escape="closeEditDrawer()">
        <div class="w-full max-w-lg bg-white h-full shadow-2xl flex flex-col justify-between"
             x-show="editDrawerOpen"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             @click.outside="closeEditDrawer()">

            <!-- Header -->
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/90">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>✏️</span> সদস্যের তথ্য এডিট করুন
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="currentUser?.name + ' (আইডি #' + currentUser?.id + ')'"></p>
                </div>
                <button type="button" @click="closeEditDrawer()" class="text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer p-1">&times;</button>
            </div>

            <!-- Body Form & Actions -->
            <div class="p-5 space-y-5 flex-1 overflow-y-auto text-xs">
                <form :action="'/backend/agents/' + currentUser?.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">সদস্যের নাম *</label>
                            <input type="text" name="name" x-model="editForm.name" required class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">ভূমিকা (Role) *</label>
                            <select name="role" x-model="editForm.role" required class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold">
                                <option value="agent">এজেন্ট / মিডিয়া (Agent)</option>
                                <option value="owner">সরাসরি মালিক (Owner)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">মোবাইল নম্বর *</label>
                            <input type="text" name="phone" x-model="editForm.phone" required class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">WhatsApp নম্বর</label>
                            <input type="text" name="whatsapp_number" x-model="editForm.whatsapp_number" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">এজেন্সি / প্রতিষ্ঠানের নাম</label>
                            <input type="text" name="agency_name" x-model="editForm.agency_name" placeholder="যেমন: ঢাকা রিয়েল এস্টেট" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">ইমেইল</label>
                            <input type="email" name="email" x-model="editForm.email" placeholder="agent@example.com" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">অ্যাডমিন নির্ধারিত রেটিং (1.0 - 5.0)</label>
                            <input type="number" step="0.1" min="1" max="5" name="admin_fixed_rating" x-model="editForm.admin_fixed_rating" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">পেনাল্টি স্ট্যাটাস</label>
                            <select name="penalty_status" x-model="editForm.penalty_status" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="verified">ভেরিফাইড (স্বাভাবিক)</option>
                                <option value="unverified">আনভেরিফাইড</option>
                                <option value="bypassed_warning">সতর্কবার্তা জারি (Warning)</option>
                                <option value="penalty_spam">স্থগিত / পেনাল্টি (Suspended)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">পেনাল্টির কারণ (প্রযোজ্য ক্ষেত্রে)</label>
                        <input type="text" name="penalty_reason" x-model="editForm.penalty_reason" placeholder="কারণ লিখুন..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-200">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_verified_direct_agent" value="1" :checked="editForm.is_verified_direct_agent" x-model="editForm.is_verified_direct_agent" class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="font-bold text-emerald-900">ভেরিফাইড ডিরেক্ট এজেন্ট ব্যাজ (Verified Direct Agent Badge)</span>
                        </label>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" @click="closeEditDrawer()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition cursor-pointer">
                            বাতিল
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                            <span>💾</span> তথ্য আপডেট করুন
                        </button>
                    </div>
                </form>

                <!-- CRM Magic Link & Fast Actions Card -->
                <div class="pt-4 border-t border-slate-200">
                    <div class="bg-indigo-50/60 p-4 rounded-2xl border border-indigo-200 space-y-2">
                        <div class="font-bold text-indigo-950 flex items-center justify-between">
                            <span>🔑 ম্যাজিক লগইন লিঙ্ক</span>
                            <button type="button" @click="regenerateToken(currentUser?.id)" class="text-xs text-indigo-600 hover:text-indigo-800 underline font-semibold cursor-pointer">
                                🔄 নতুন লিঙ্ক তৈরি করুন
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-600">পাসওয়ার্ড ছাড়াই সদস্য এই লিঙ্কে ক্লিক করে সরাসরি তার পোর্টাল অ্যাক্সেস করতে পারবেন।</p>
                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" @click="copyMagicLink('{{ url('/auth/magic') }}/' + currentUser?.magic_token)" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-xs flex items-center gap-1 cursor-pointer">
                                <span>📋</span> লিঙ্ক কপি
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CRM Notes Form -->
                <div class="pt-2 border-t border-slate-200">
                    <form :action="'/backend/agents/' + currentUser?.id + '/note'" method="POST" class="space-y-2 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        @csrf
                        <label class="block font-bold text-slate-800">📝 অভ্যন্তরীণ অ্যাডমিন নোট যোগ করুন</label>
                        <textarea name="note" rows="2" required placeholder="এই সদস্য সম্পর্কে কোনো পর্যবেক্ষণ বা মন্তব্য লিখুন..." class="w-full p-2.5 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                        <button type="submit" class="w-full py-2 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition cursor-pointer">
                            নোট সেভ করুন
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function agentManager() {
    return {
        addDrawerOpen: false,
        editDrawerOpen: false,
        currentUser: null,
        editForm: {
            name: '',
            role: 'agent',
            phone: '',
            whatsapp_number: '',
            agency_name: '',
            email: '',
            admin_fixed_rating: 5.0,
            is_verified_direct_agent: true,
            penalty_status: 'verified',
            penalty_reason: '',
        },

        openAddDrawer() {
            this.addDrawerOpen = true;
            this.editDrawerOpen = false;
        },

        closeAddDrawer() {
            this.addDrawerOpen = false;
        },

        openEditDrawer(user) {
            this.currentUser = user;
            this.editForm = {
                name: user.name || '',
                role: user.role || 'agent',
                phone: user.phone || '',
                whatsapp_number: user.whatsapp_number || user.phone || '',
                agency_name: user.agent_profile?.agency_name || '',
                email: user.email && !user.email.includes('@propertykoi.local') ? user.email : '',
                admin_fixed_rating: user.agent_profile?.admin_fixed_rating || user.agent_profile?.rating || 5.0,
                is_verified_direct_agent: user.agent_profile ? !!user.agent_profile.is_verified_direct_agent : true,
                penalty_status: user.agent_profile?.penalty_status || 'verified',
                penalty_reason: user.agent_profile?.penalty_reason || '',
            };
            this.editDrawerOpen = true;
            this.addDrawerOpen = false;
        },

        closeEditDrawer() {
            this.editDrawerOpen = false;
            this.currentUser = null;
        },

        copyMagicLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('ম্যাজিক লগইন লিঙ্ক ক্লিপবোর্ডে কপি করা হয়েছে!\n\n' + url);
            });
        },

        regenerateToken(userId) {
            if (!confirm('আপনি কি এই সদস্যের জন্য নতুন ম্যাজিক টোকেন জেনারেট করতে চান? পূর্বের লিঙ্কটি আর কাজ করবে না।')) return;
            fetch(`{{ route('admin.agents.index') }}/${userId}/magic-token`, {
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
