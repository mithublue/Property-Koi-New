@extends('layouts.admin')

@section('title', 'লিড ও সিআরএম পাইপলাইন')
@section('page_title', 'লিড ও সিআরএম পাইপলাইন (Inquiries & CRM)')

@section('content')
<div class="space-y-6" x-data="leadManager()">

    <!-- Stats & Pipeline Filter Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <a href="{{ route('admin.leads.index', ['stage' => 'all']) }}" class="px-3.5 py-2 rounded-xl border {{ $stage === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                মোট লিড: {{ $stats['total'] }}
            </a>
            <a href="{{ route('admin.leads.index', ['stage' => 'new']) }}" class="px-3.5 py-2 rounded-xl border {{ $stage === 'new' ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50 text-blue-800 border-blue-200 hover:bg-blue-100' }}">
                🆕 নতুন (New): {{ $stats['new'] }}
            </a>
            <a href="{{ route('admin.leads.index', ['stage' => 'contacted']) }}" class="px-3.5 py-2 rounded-xl border {{ $stage === 'contacted' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-indigo-50 text-indigo-800 border-indigo-200 hover:bg-indigo-100' }}">
                📞 যোগাযোগ হয়েছে: {{ $stats['contacted'] }}
            </a>
            <a href="{{ route('admin.leads.index', ['stage' => 'visit_scheduled']) }}" class="px-3.5 py-2 rounded-xl border {{ $stage === 'visit_scheduled' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}">
                📅 ভিজিট শিডিউল্ড: {{ $stats['visit_scheduled'] }}
            </a>
            <a href="{{ route('admin.leads.index', ['stage' => 'closed']) }}" class="px-3.5 py-2 rounded-xl border {{ $stage === 'closed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                🤝 ডিল সম্পন্ন (Closed): {{ $stats['closed'] }}
            </a>
        </div>

        <form method="GET" action="{{ route('admin.leads.index') }}" class="flex items-center gap-2 text-xs">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="কোড, নাম বা ফোন নম্বর..." class="px-3 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition">ফিল্টার</button>
        </form>
    </div>

    <!-- Leads Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-3.5">লিড কোড ও সময়</th>
                        <th class="p-3.5">গ্রাহকের বিবরণ</th>
                        <th class="p-3.5">আগ্রহী প্রপার্টি</th>
                        <th class="p-3.5">পাইপলাইন স্টেজ</th>
                        <th class="p-3.5">মালিককে অ্যালার্ট</th>
                        <th class="p-3.5 text-center">সিআরএম হিস্ট্রি</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-3.5">
                                <div class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded inline-block">
                                    #{{ $lead->lead_code }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    {{ $lead->created_at->format('d M, Y - h:i A') }}
                                </div>
                            </td>
                            <td class="p-3.5">
                                <div class="font-bold text-slate-900">{{ $lead->buyer_name }}</div>
                                <div class="text-[11px] text-slate-500">📞 {{ $lead->buyer_phone }}</div>
                                @if($lead->message)
                                    <div class="text-[11px] text-slate-600 italic mt-0.5 line-clamp-1">"{{ $lead->message }}"</div>
                                @endif
                            </td>
                            <td class="p-3.5">
                                @if($lead->property)
                                    <a href="{{ route('admin.properties.edit', $lead->property) }}" class="font-semibold text-slate-800 hover:text-emerald-600 line-clamp-1">
                                        {{ $lead->property->title }}
                                    </a>
                                    <div class="text-[11px] text-slate-500">
                                        #{{ $lead->property->property_code }} | ৳ {{ number_format($lead->property->asking_price) }}
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">সাধারণ রিকোয়েস্ট</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                <div class="relative inline-flex items-center">
                                    <select 
                                        :disabled="loadingStages[{{ $lead->id }}]"
                                        :value="stages[{{ $lead->id }}]"
                                        @change="changeStage({{ $lead->id }}, '{{ $lead->lead_code }}', $event.target.value, $event.target)"
                                        :class="{
                                            'opacity-50 cursor-wait': loadingStages[{{ $lead->id }}],
                                            'bg-emerald-50 text-emerald-800 border-emerald-300 ring-1 ring-emerald-200': stages[{{ $lead->id }}] === 'closed',
                                            'bg-blue-50 text-blue-800 border-blue-300 ring-1 ring-blue-200': stages[{{ $lead->id }}] === 'new',
                                            'bg-indigo-50 text-indigo-800 border-indigo-300 ring-1 ring-indigo-200': stages[{{ $lead->id }}] === 'visit_scheduled',
                                            'bg-amber-50 text-amber-800 border-amber-300 ring-1 ring-amber-200': stages[{{ $lead->id }}] === 'contacted',
                                            'bg-purple-50 text-purple-800 border-purple-300 ring-1 ring-purple-200': stages[{{ $lead->id }}] === 'negotiation',
                                            'bg-rose-50 text-rose-800 border-rose-300 ring-1 ring-rose-200': stages[{{ $lead->id }}] === 'dropped'
                                        }"
                                        class="text-xs font-bold rounded-lg border py-1 px-2 pr-6 focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer transition">
                                        <option value="new">New (নতুন)</option>
                                        <option value="contacted">Contacted</option>
                                        <option value="visit_scheduled">Visit Scheduled</option>
                                        <option value="negotiation">Negotiation</option>
                                        <option value="closed">Closed (সম্পন্ন)</option>
                                        <option value="dropped">Dropped (বাতিল)</option>
                                    </select>
                                    <template x-if="loadingStages[{{ $lead->id }}]">
                                        <div class="absolute right-1.5 top-1/2 -translate-y-1/2 pointer-events-none">
                                            <svg class="animate-spin h-3 w-3 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <button @click="notifyOwner({{ $lead->id }})" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg text-[11px] flex items-center gap-1 transition">
                                    <span>💬</span> হোয়াটসঅ্যাপ অ্যালার্ট
                                </button>
                            </td>
                            <td class="p-3.5 text-center">
                                <button @click="openLeadDrawer({{ $lead->toJson() }})" class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-xs transition">
                                    নোট ও হিস্ট্রি &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-xs text-slate-400">কোনো ইনকোয়ারি পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $leads->links() }}
        </div>
    </div>

    <!-- Slide-Over Drawer for Lead Details & Notes -->
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-sm flex justify-end">
        <div class="w-full max-w-md bg-white h-full shadow-2xl flex flex-col justify-between" @click.away="drawerOpen = false">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900" x-text="currentLead?.buyer_name"></h3>
                    <p class="text-xs text-slate-500" x-text="'লিড কোড: #' + currentLead?.lead_code"></p>
                </div>
                <button @click="drawerOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <div class="p-5 space-y-4 flex-1 overflow-y-auto text-xs">
                <!-- Customer info card -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-1.5">
                    <div><strong>ফোন:</strong> <span x-text="currentLead?.buyer_phone"></span></div>
                    <div><strong>বাজেট রেঞ্জ:</strong> <span x-text="currentLead?.budget_range || 'উল্লেখ নেই'"></span></div>
                    <div><strong>পেমেন্ট প্রস্তুতি:</strong> <span x-text="currentLead?.payment_readiness"></span></div>
                    <div><strong>মেসেজ:</strong> <span x-text="currentLead?.message || 'কোনো মেসেজ নেই'"></span></div>
                </div>

                <!-- Notes Timeline -->
                <div class="space-y-2">
                    <h4 class="font-bold text-slate-800">পূর্বে সংরক্ষিত নোটসমূহ:</h4>
                    <template x-for="n in currentLead?.notes" :key="n.id">
                        <div class="bg-indigo-50/50 p-3 rounded-lg border border-indigo-100 text-[11px]">
                            <div class="flex items-center justify-between text-indigo-900 font-semibold mb-1">
                                <span x-text="n.author_name"></span>
                                <span class="text-[10px] text-slate-400" x-text="new Date(n.created_at).toLocaleDateString()"></span>
                            </div>
                            <div class="text-slate-700" x-text="n.note_text"></div>
                        </div>
                    </template>
                    <div x-show="!currentLead?.notes?.length" class="text-slate-400 italic py-2">কোনো নোট নেই।</div>
                </div>

                <!-- Add Note Form -->
                <form :action="'/backend/leads/' + currentLead?.id + '/note'" method="POST" class="space-y-2 pt-2 border-t border-slate-100">
                    @csrf
                    <label class="block font-bold text-slate-800">নতুন ফলো-আপ নোট লিখুন</label>
                    <textarea name="note" rows="3" required placeholder="গ্রাহকের সাথে কি কথা হলো..." class="w-full p-2.5 border border-slate-300 rounded-xl"></textarea>
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
function leadManager() {
    return {
        drawerOpen: false,
        currentLead: null,
        stages: {
            @foreach($leads as $lead)
                {{ $lead->id }}: '{{ $lead->stage }}',
            @endforeach
        },
        loadingStages: {},

        async changeStage(leadId, leadCode, newStage, selectEl) {
            const prev = this.stages[leadId];
            if (newStage === prev) return;

            this.loadingStages[leadId] = true;
            try {
                const res = await fetch(`{{ url('admin/leads') }}/${leadId}/stage`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ stage: newStage })
                });
                const data = await res.json();
                if (data.success) {
                    this.stages[leadId] = newStage;
                    if (window.showToast) window.showToast(`লিড #${leadCode} স্টেজ '${newStage.toUpperCase()}' আপডেট হয়েছে!`, 'success');
                } else {
                    this.stages[leadId] = prev;
                    if (selectEl) selectEl.value = prev;
                    if (window.showToast) window.showToast(data.message || 'স্টেজ আপডেট ব্যর্থ হয়েছে।', 'error');
                }
            } catch(e) {
                this.stages[leadId] = prev;
                if (selectEl) selectEl.value = prev;
                if (window.showToast) window.showToast('সার্ভার এরর: স্টেজ পরিবর্তন করা সম্ভব হয়নি।', 'error');
            } finally {
                this.loadingStages[leadId] = false;
            }
        },

        openLeadDrawer(lead) {
            this.currentLead = lead;
            this.drawerOpen = true;
        },

        notifyOwner(leadId) {
            fetch(`{{ url('admin/leads') }}/${leadId}/owner-wa`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.whatsapp_url) {
                        window.open(data.whatsapp_url, '_blank');
                    } else {
                        if (window.showToast) {
                            window.showToast(data.error || 'মালিকের হোয়াটসঅ্যাপ তথ্য পাওয়া যায়নি', 'error');
                        } else {
                            alert(data.error || 'মালিকের হোয়াটসঅ্যাপ তথ্য পাওয়া যায়নি');
                        }
                    }
                });
        }
    }
}
</script>
@endpush
