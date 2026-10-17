@extends('layouts.admin')

@section('title', '৪-দফা ভেরিফিকেশন হাব')
@section('page_title', '৪-দফা প্রপার্টি নিরাপত্তা ও ভেরিফিকেশন হাব')

@section('content')
<div class="space-y-6">

    <!-- Stats & Status Filter Tabs -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <a href="{{ route('admin.verifications.index', ['status' => 'pending', 'type' => $type]) }}" class="px-3.5 py-2 rounded-xl border {{ $status === 'pending' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}">
                ⏳ পেন্ডিং ভেরিফিকেশন: {{ $stats['pending'] }}
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'verified', 'type' => $type]) }}" class="px-3.5 py-2 rounded-xl border {{ $status === 'verified' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                ✅ ভেরিফাইড ও লাইভ: {{ $stats['verified'] }}
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'rejected', 'type' => $type]) }}" class="px-3.5 py-2 rounded-xl border {{ $status === 'rejected' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100' }}">
                ❌ বাতিল (Rejected): {{ $stats['rejected'] }}
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'all', 'type' => $type]) }}" class="px-3.5 py-2 rounded-xl border {{ $status === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                মোট: {{ $stats['all'] }}
            </a>
        </div>

        <!-- Category Dropdown / Tabs -->
        <div class="flex items-center gap-1.5 text-xs font-medium">
            <span class="text-slate-500">টাইপ:</span>
            <a href="{{ route('admin.verifications.index', ['status' => $status, 'type' => 'all']) }}" class="px-2.5 py-1 rounded-lg {{ $type === 'all' ? 'bg-slate-800 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">সব</a>
            <a href="{{ route('admin.verifications.index', ['status' => $status, 'type' => 'land']) }}" class="px-2.5 py-1 rounded-lg {{ $type === 'land' ? 'bg-slate-800 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">জমি</a>
            <a href="{{ route('admin.verifications.index', ['status' => $status, 'type' => 'flat']) }}" class="px-2.5 py-1 rounded-lg {{ $type === 'flat' ? 'bg-slate-800 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">ফ্ল্যাট</a>
            <a href="{{ route('admin.verifications.index', ['status' => $status, 'type' => 'commercial']) }}" class="px-2.5 py-1 rounded-lg {{ $type === 'commercial' ? 'bg-slate-800 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">কমার্শিয়াল</a>
        </div>
    </div>

    <!-- Verification Items Grid -->
    <div class="space-y-4">
        @forelse($properties as $p)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 md:p-6 shadow-sm hover:border-slate-300 transition" 
                 x-data="{ 
                    openRejectModal: false,
                    saving: false,
                    c1: {{ $p->verification?->checklist_owner_identity ? 'true' : 'false' }},
                    c2: {{ $p->verification?->checklist_location ? 'true' : 'false' }},
                    c3: {{ $p->verification?->checklist_basic_info ? 'true' : 'false' }},
                    c4: {{ $p->verification?->checklist_documents ? 'true' : 'false' }},
                    propStatus: '{{ $p->status }}',
                    async saveChecklist() {
                        this.saving = true;
                        try {
                            const res = await fetch('{{ route('admin.verifications.checklist', $p) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    checklist_owner_identity: this.c1 ? 1 : 0,
                                    checklist_location: this.c2 ? 1 : 0,
                                    checklist_basic_info: this.c3 ? 1 : 0,
                                    checklist_documents: this.c4 ? 1 : 0,
                                })
                            });
                            const data = await res.json();
                            if (data.success) {
                                this.propStatus = data.status;
                                if (window.showToast) window.showToast(data.message, 'success');
                            } else {
                                if (window.showToast) window.showToast('সংরক্ষণ ব্যর্থ হয়েছে।', 'error');
                            }
                        } catch(e) {
                            if (window.showToast) window.showToast('সার্ভার এরর: চেকলিস্ট আপডেট করা যায়নি।', 'error');
                        } finally {
                            this.saving = false;
                        }
                    }
                 }">
                <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">

                    <!-- Property Summary Left -->
                    <div class="flex items-start gap-4 flex-1">
                        <div class="w-20 h-20 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                            @if($p->media->isNotEmpty())
                                <img src="{{ asset($p->media->first()->file_path) }}" 
                                     alt="{{ $p->title }}" 
                                     onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=No+Photo';"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-xs text-slate-400 font-medium">ছবি নাই</div>
                            @endif
                        </div>
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 font-mono text-[11px] font-bold bg-slate-100 text-slate-800 rounded">#{{ $p->property_code }}</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full transition-all"
                                      :class="propStatus === 'live' ? 'bg-emerald-100 text-emerald-800' : (propStatus === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')"
                                      x-text="propStatus.toUpperCase()">
                                    {{ strtoupper($p->status) }}
                                </span>
                                <span class="text-xs text-slate-500">({{ $p->type }})</span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">
                                <a href="{{ route('admin.properties.edit', $p) }}" class="hover:text-emerald-600">
                                    {{ $p->title }}
                                </a>
                            </h3>
                            <div class="text-xs text-slate-600 flex flex-wrap items-center gap-3 pt-0.5">
                                <span>📍 আফতাবনগর, ব্লক: <strong class="text-slate-900">{{ $p->block ?? 'N/A' }}</strong>, রোড: {{ $p->road_number ?? 'N/A' }}</span>
                                <span>📐 সাইজ: {{ $p->size ? $p->size . ' কাঠা' : ($p->flat_size_sqft ? $p->flat_size_sqft . ' sqft' : 'N/A') }}</span>
                                <span class="font-bold text-emerald-700">💰 ৳ {{ number_format($p->asking_price) }}</span>
                            </div>
                            @if($p->creator)
                                <div class="text-[11px] text-slate-500 pt-1">
                                    ইনসার্টার: <span class="font-semibold text-slate-700">{{ $p->creator->name }}</span> ({{ $p->creator->phone }})
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- 4-Point Checklist Form Right -->
                    <div class="lg:w-2/5 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3 text-xs">
                        <div class="font-bold text-slate-800 flex items-center justify-between">
                            <span>৪-দফা চেকলিস্ট সম্পন্ন করুন:</span>
                            @if($p->verification?->verified_at)
                                <span class="text-[10px] text-emerald-600 font-semibold">ভেরিফাইড: {{ $p->verification->verified_at->format('d/m/y') }}</span>
                            @endif
                        </div>

                        <form @submit.prevent="saveChecklist()" class="space-y-2">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" x-model="c1" class="rounded text-emerald-600">
                                <span class="font-medium text-slate-700">১. প্রপার্টি মালিকের এনআইডি / পরিচয়পত্র</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" x-model="c2" class="rounded text-emerald-600">
                                <span class="font-medium text-slate-700">২. ফিজিক্যাল লোকেশন ও প্লট স্পট যাচাই</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" x-model="c3" class="rounded text-emerald-600">
                                <span class="font-medium text-slate-700">৩. সাইজ, ফেসিং ও রাস্তা যাচাই</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" x-model="c4" class="rounded text-emerald-600">
                                <span class="font-medium text-slate-700">৪. প্রাথমিক দলিল ও পরচা কপি যাচাই</span>
                            </label>

                            <div class="pt-2 flex items-center justify-between gap-2">
                                <button type="submit" :disabled="saving" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-lg transition shadow-sm">
                                    <span x-text="saving ? 'সংরক্ষণ হচ্ছে...' : 'চেকলিস্ট সেভ করুন (Save)'"></span>
                                </button>
                                <button type="button" @click="openRejectModal = true" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg transition">
                                    বাতিল (Reject)
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- Rejection Modal -->
                <div x-show="openRejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl border border-slate-200" @click.away="openRejectModal = false">
                        <h4 class="text-sm font-bold text-slate-900 mb-2">প্রপার্টি বাতিল নিশ্চিতকরণ</h4>
                        <p class="text-xs text-slate-500 mb-3">বাতিল করার কারণ উল্লেখ করুন। প্রপার্টি স্ট্যাটাস 'rejected' হয়ে যাবে।</p>
                        <form action="{{ route('admin.verifications.reject', $p) }}" method="POST">
                            @csrf
                            <textarea name="reason" rows="3" required placeholder="বাতিল করার সুনির্দিষ্ট কারণ..." class="w-full p-2.5 border border-slate-300 rounded-xl text-xs mb-3"></textarea>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="openRejectModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg">ফিরে যান</button>
                                <button type="submit" class="px-3 py-1.5 bg-rose-600 text-white text-xs font-bold rounded-lg shadow-sm">বাতিল করুন</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white p-12 text-center text-xs text-slate-400 rounded-2xl border border-slate-200">
                এই ক্যাটাগরিতে কোনো প্রপার্টি ভেরিফিকেশনের জন্য অপেক্ষমাণ নেই।
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200">
        {{ $properties->links() }}
    </div>

</div>
@endsection
