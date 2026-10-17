@extends('layouts.admin')

@section('title', 'রেভিনিউ ও ০.৫% কমিশন')
@section('page_title', 'রেভিনিউ ও ০.৫% প্ল্যাটফর্ম ফি ট্র্যাকার')

@section('content')
<div class="space-y-6" x-data="{ openRecordModal: false }">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">মোট বিক্রিত ভলিউম</div>
            <div class="text-2xl font-black text-slate-900 mt-1">৳ {{ number_format($stats['gross_volume']) }}</div>
            <div class="text-xs text-slate-500 mt-1">সম্পন্ন ডিল: {{ $stats['closed_deals_count'] }} টি</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">মোট ০.৫% প্ল্যাটফর্ম ফি</div>
            <div class="text-2xl font-black text-purple-700 mt-1">৳ {{ number_format($stats['total_commission']) }}</div>
            <div class="text-xs text-purple-600 font-medium mt-1">অর্জিত মোট রাজস্ব</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">আদায়কৃত কমিশন</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">৳ {{ number_format($stats['collected_commission']) }}</div>
            <div class="text-xs text-emerald-700 font-medium mt-1">সফলভাবে সংগৃহীত</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 uppercase">পেন্ডিং কমিশন</div>
            <div class="text-2xl font-black text-amber-600 mt-1">৳ {{ number_format($stats['pending_commission']) }}</div>
            <div class="text-xs text-amber-700 font-medium mt-1">বকেয়া রয়েছে</div>
        </div>
    </div>

    <!-- Actions & Filter Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-semibold">
            <a href="{{ route('admin.revenue.index', ['status' => 'all']) }}" class="px-3 py-1.5 rounded-xl border {{ $status === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700' }}">সব</a>
            <a href="{{ route('admin.revenue.index', ['status' => 'collected']) }}" class="px-3 py-1.5 rounded-xl border {{ $status === 'collected' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700' }}">আদায়কৃত (Collected)</a>
            <a href="{{ route('admin.revenue.index', ['status' => 'pending']) }}" class="px-3 py-1.5 rounded-xl border {{ $status === 'pending' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700' }}">বকেয়া (Pending)</a>
        </div>

        <button type="button" @click="openRecordModal = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <span>➕</span> নতুন ডিল / লেনদেন রেকর্ড করুন
        </button>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-3.5">লেনদেন কোড ও তারিখ</th>
                        <th class="p-3.5">প্রপার্টি</th>
                        <th class="p-3.5">ক্রেতা ও বিক্রেতা</th>
                        <th class="p-3.5">চুক্তিমূল্য</th>
                        <th class="p-3.5">০.৫% প্ল্যাটফর্ম ফি</th>
                        <th class="p-3.5">ফি স্ট্যাটাস</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-3.5">
                                <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-[11px]">#{{ $tx->transaction_code }}</span>
                                <div class="text-[10px] text-slate-400 mt-1">{{ \Carbon\Carbon::parse($tx->closed_at)->format('d M, Y') }}</div>
                            </td>
                            <td class="p-3.5">
                                <div class="font-bold text-slate-900">{{ $tx->property?->title ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500">#{{ $tx->property?->property_code }}</div>
                            </td>
                            <td class="p-3.5">
                                <div><strong>ক্রেতা:</strong> {{ $tx->buyer_name }} ({{ $tx->buyer_phone }})</div>
                                <div class="text-[11px] text-slate-500 mt-0.5"><strong>বিক্রেতা:</strong> {{ $tx->seller?->name ?? 'সরাসরি মালিক' }}</div>
                            </td>
                            <td class="p-3.5 font-bold text-slate-900">
                                ৳ {{ number_format($tx->agreed_price) }}
                            </td>
                            <td class="p-3.5 font-bold text-purple-700">
                                ৳ {{ number_format($tx->platform_fee_amount) }}
                                <div class="text-[10px] text-slate-400 font-normal">({{ $tx->platform_fee_percent }}%)</div>
                            </td>
                            <td class="p-3.5">
                                <form action="{{ route('admin.revenue.fee-status', $tx) }}" method="POST">
                                    @csrf
                                    <select name="fee_status" onchange="this.form.submit()" class="text-xs font-bold rounded-lg border-slate-300 py-1 px-2 {{ $tx->fee_status === 'collected' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                                        <option value="pending" {{ $tx->fee_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="collected" {{ $tx->fee_status === 'collected' ? 'selected' : '' }}>Collected (আদায়)</option>
                                        <option value="partial" {{ $tx->fee_status === 'partial' ? 'selected' : '' }}>Partial</option>
                                        <option value="waived" {{ $tx->fee_status === 'waived' ? 'selected' : '' }}>Waived (মওকুফ)</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-xs text-slate-400">কোনো লেনদেন পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Record Transaction Modal -->
    <div x-show="openRecordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200" @click.away="openRecordModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                <span>💰</span> নতুন বিক্রিত ডিল ও ফি রেকর্ড করুন
            </h3>
            <p class="text-xs text-slate-500 mb-4">লেনদেন রেকর্ড করার সাথে সাথে সিস্টেম প্রপার্টিটিকে সোল্ড মার্ক করবে এবং ০.৫% ফি হিসাব করবে।</p>

            <form action="{{ route('admin.revenue.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">প্রপার্টি নির্বাচন *</label>
                    <select name="property_id" required class="w-full p-2 border border-slate-300 rounded-xl">
                        <option value="">-- প্রপার্টি সিলেক্ট করুন --</option>
                        @foreach($properties as $prop)
                            <option value="{{ $prop->id }}">{{ $prop->title }} (#{{ $prop->property_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ক্রেতার নাম *</label>
                        <input type="text" name="buyer_name" required placeholder="নাম..." class="w-full p-2 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ক্রেতার ফোন *</label>
                        <input type="text" name="buyer_phone" required placeholder="01XXXXXXXXX" class="w-full p-2 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">বিক্রেতার ধরন *</label>
                        <select name="seller_type" required class="w-full p-2 border border-slate-300 rounded-xl">
                            <option value="direct_owner">সরাসরি মালিক (Direct Owner)</option>
                            <option value="direct_agent">ভেরিফাইড এজেন্ট (Direct Agent)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">বিক্রেতা ইউজার</label>
                        <select name="seller_user_id" class="w-full p-2 border border-slate-300 rounded-xl">
                            <option value="">-- নির্বাচন করুন (ঐচ্ছিক) --</option>
                            @foreach($sellers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->role }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">চূড়ান্ত বিক্রয়মূল্য (৳) *</label>
                        <input type="number" name="agreed_price" required placeholder="যেমন: 45000000" class="w-full p-2 border border-slate-300 rounded-xl font-bold text-slate-900">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ফি শতকরা (%)</label>
                        <input type="number" step="0.01" name="platform_fee_percent" value="0.50" class="w-full p-2 border border-slate-300 rounded-xl font-bold text-purple-700">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ফি স্ট্যাটাস *</label>
                        <select name="fee_status" required class="w-full p-2 border border-slate-300 rounded-xl">
                            <option value="pending">বকেয়া (Pending)</option>
                            <option value="collected">আদায় হয়েছে (Collected)</option>
                            <option value="partial">আংশিক (Partial)</option>
                            <option value="waived">মওকুফ (Waived)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">লেনদেনের তারিখ *</label>
                        <input type="date" name="closed_at" value="{{ date('Y-m-d') }}" required class="w-full p-2 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" @click="openRecordModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-semibold rounded-xl">বাতিল</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md">ডিল সেভ করুন</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
