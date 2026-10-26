@extends('layouts.admin')

@section('title', 'প্রাইভেট প্রপার্টি ডিরেক্টরি (Internal Vault)')
@section('page_title', 'প্রাইভেট প্রপার্টি ডিরেক্টরি')

@section('page_badge')
    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1.5 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
        অভ্যন্তরীণ ডাটাবেস ভল্ট (Non-Public)
    </span>
@endsection

@section('content')
<div class="space-y-6" x-data="{ confirmingPublishId: null, confirmingPropertyTitle: '' }">

    <!-- Hero / Information Notice -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-3xl p-6 shadow-sm border border-slate-700/60 relative overflow-hidden">
        <div class="absolute -right-12 -bottom-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2 max-w-3xl">
                <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold tracking-wide uppercase">
                    <span>🔒 প্রাইভেট ডাটাবেস রিপোজিটরি</span>
                    <span>•</span>
                    <span>100% অফলাইন ও সার্চ ইঞ্জিন সুরক্ষিত</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
                    অভ্যন্তরীণ প্রপার্টি ভল্ট &amp; ১-ক্লিক SEO/AEO লঞ্চপ্যাড
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    এই তালিকার প্রপার্টিগুলো সরাসরি জনসাধারণের কাছে দৃশ্যমান নয়—শুধুমাত্র আপনার ব্যাকএন্ড ডাটাবেসে সংরক্ষিত। যেকোনো প্রপার্টি বাজারে রিলিজ করতে চাইলে <strong class="text-emerald-300">"১-ক্লিকে পাবলিক লিস্টিং"</strong> চাপুন। স্বয়ংক্রিয়ভাবে ক্লিন স্লাগ, মেটাডাটা, OpenGraph, Schema.org LD-JSON গ্রাফ এবং AI রেজাল্ট ফ্রেন্ডলি FAQ সহ সাইটে লাইভ হয়ে যাবে এবং সার্চ ইঞ্জিন ও বায়ারদের অ্যালার্ট চলে যাবে।
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.properties.create', ['destination' => 'directory']) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition transform active:scale-95">
                    <span>➕</span> নতুন প্রাইভেট প্রপার্টি
                </a>
                <a href="{{ route('admin.properties.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800/80 hover:bg-slate-700 text-white font-semibold text-xs rounded-xl border border-slate-600/60 transition">
                    <span>🏠</span> লাইভ ক্যাটালগ
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-slate-500">মোট প্রাইভেট</div>
            <div class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $stats['total_private'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-amber-600">ড্রাফট (Draft)</div>
            <div class="text-xl font-extrabold text-amber-700 mt-0.5">{{ $stats['draft'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-indigo-600">পেন্ডিং যাচাই</div>
            <div class="text-xl font-extrabold text-indigo-700 mt-0.5">{{ $stats['pending'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-emerald-600">জমি / প্লট</div>
            <div class="text-xl font-extrabold text-emerald-700 mt-0.5">{{ $stats['plots'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-blue-600">রেডি ফ্ল্যাট</div>
            <div class="text-xl font-extrabold text-blue-700 mt-0.5">{{ $stats['flats'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-purple-600">কমার্শিয়াল</div>
            <div class="text-xl font-extrabold text-purple-700 mt-0.5">{{ $stats['commercial'] ?? 0 }}</div>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="text-[11px] font-semibold text-rose-600">ল্যান্ড শেয়ার</div>
            <div class="text-xl font-extrabold text-rose-700 mt-0.5">{{ $stats['share'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.properties.directory') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="প্রপার্টি কোড, টাইটেল, রোড, প্লট বা মালিকের ফোন..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>
            <div>
                <select name="type" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="all">সকল ধরন (All Types)</option>
                    <option value="land" {{ request('type') === 'land' ? 'selected' : '' }}>জমি / প্লট</option>
                    <option value="flat" {{ request('type') === 'flat' ? 'selected' : '' }}>রেডি ফ্ল্যাট</option>
                    <option value="commercial" {{ request('type') === 'commercial' ? 'selected' : '' }}>কমার্শিয়াল</option>
                    <option value="land_share" {{ request('type') === 'land_share' ? 'selected' : '' }}>ল্যান্ড শেয়ার</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition">
                    ফিল্টার করুন
                </button>
                @if(request()->hasAny(['search', 'type', 'area']))
                    <a href="{{ route('admin.properties.directory') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-center" title="রিসেট">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Properties Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-bold tracking-wider">
                        <th class="p-4">প্রপার্টি বিবরণ</th>
                        <th class="p-4">লোকেশন ও স্পেক্স</th>
                        <th class="p-4">মূল্য বিবরণ</th>
                        <th class="p-4">সরাসরি মালিক / সোর্স</th>
                        <th class="p-4 text-center">SEO &amp; AEO স্ট্যাটাস</th>
                        <th class="p-4 text-right">লঞ্চ / অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($properties as $property)
                        @php
                            $primarySource = $property->directSources->first();
                            $ownerPhone = $primarySource?->owner_phone ?: ($property->creator?->phone ?: '');
                            $ownerName = $primarySource?->owner_name ?: ($property->creator?->name ?: 'অজানা');
                            $cleanPhone = preg_replace('/[^0-9]/', '', $ownerPhone);
                            $waPhone = str_starts_with($cleanPhone, '0') ? ('88' . $cleanPhone) : $cleanPhone;
                            $thumb = $property->media->first()?->thumbnail_url ?: ($property->media->first()?->file_url ?: '');
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition group">
                            <!-- Property Info -->
                            <td class="p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-14 h-14 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center text-slate-400">
                                        @if(!empty($thumb))
                                            <img src="{{ $thumb }}" alt="{{ $property->title }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xl">
                                                @if($property->type === 'land') 🏞️
                                                @elseif($property->type === 'flat') 🏢
                                                @elseif($property->type === 'commercial') 🏬
                                                @else 🤝 @endif
                                            </span>
                                        @endif
                                    </div>
                                    <div class="space-y-1 max-w-xs sm:max-w-sm">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-900 text-amber-400">
                                                #{{ $property->property_code }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded font-semibold text-[10px] bg-amber-100 text-amber-800 border border-amber-200">
                                                {{ ucfirst($property->status) }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-slate-100 text-slate-700">
                                                @if($property->type === 'land') জমি / প্লট
                                                @elseif($property->type === 'flat') রেডি ফ্ল্যাট
                                                @elseif($property->type === 'commercial') কমার্শিয়াল
                                                @else ল্যান্ড শেয়ার @endif
                                            </span>
                                        </div>
                                        <div class="font-bold text-slate-900 line-clamp-1 leading-snug">
                                            {{ $property->title }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            যোগ হয়েছে: {{ $property->created_at->format('d M, Y') }} ({{ $property->created_at->diffForHumans() }})
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Location & Specs -->
                            <td class="p-4">
                                <div class="space-y-1">
                                    <div class="font-semibold text-slate-800 flex items-center gap-1">
                                        <span>📍</span> {{ $property->area ?: 'আফতাবনগর' }}
                                        @if($property->block)
                                            <span class="px-1.5 py-0.2 bg-slate-100 text-slate-700 rounded font-bold">ব্লক {{ $property->block }}</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        রোড: {{ $property->road_number ?: '-' }} | প্লট: {{ $property->plot_number ?: '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        আয়তন: <strong class="text-slate-700">{{ $property->formatted_size ?: '-' }}</strong>
                                        @if($property->facing) | ফেসিং: {{ ucfirst($property->facing) }} @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Price -->
                            <td class="p-4">
                                <div class="space-y-0.5">
                                    <div class="text-sm font-extrabold text-emerald-700 font-mono">
                                        {{ $property->formatted_price }}
                                    </div>
                                    @if($property->price_per_unit)
                                        <div class="text-[10px] text-slate-500">
                                            {{ $property->price_per_unit }}
                                        </div>
                                    @endif
                                    @if($property->is_negotiable)
                                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">দাম আলোচনা সাপেক্ষ</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Owner / Direct Source -->
                            <td class="p-4">
                                <div class="space-y-1">
                                    <div class="font-bold text-slate-800 flex items-center gap-1">
                                        <span>👤</span> {{ $ownerName }}
                                    </div>
                                    @if(!empty($ownerPhone))
                                        <div class="font-mono text-[11px] text-slate-600 flex items-center gap-1.5">
                                            <span>📞</span> {{ $ownerPhone }}
                                        </div>
                                        <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode('আসসালামু আলাইকুম, PropertyKoi প্রাইভেট ভল্ট থেকে প্রপার্টি #' . $property->property_code . ' (' . $property->title . ') প্রসঙ্গে জানতে যোগাযোগ করছি।') }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                            <span>💬</span> হোয়াটসঅ্যাপ চ্যাট
                                        </a>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">নম্বর নেই</span>
                                    @endif
                                </div>
                            </td>

                            <!-- SEO & AEO Status -->
                            <td class="p-4 text-center">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shadow-xs">
                                        <span>🟢</span> অপ্টিমাইজড রেডি
                                    </span>
                                    <span class="text-[9px] text-slate-400">
                                        Meta, Schema, AI FAQ
                                    </span>
                                </div>
                            </td>

                            <!-- Actions & 1-Click Launch -->
                            <td class="p-4 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    <!-- 1-Click Publish Button -->
                                    <form action="{{ route('admin.properties.publish-live', $property) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে প্রপার্টি #{{ $property->property_code }} সম্পূর্ণ SEO, AEO ও AI FAQ সহ তাৎক্ষণিকভাবে পাবলিক সাইটে লাইভ লিস্টিং করতে চান?');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-xs rounded-xl shadow-sm transition transform active:scale-95 whitespace-nowrap">
                                            <span>🚀</span> ১-ক্লিকে পাবলিক লিস্টিং
                                        </button>
                                    </form>

                                    <!-- Quick Utility Links -->
                                    <div class="flex items-center gap-2 text-xs">
                                        <a href="{{ route('admin.properties.edit', $property) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-0.5">
                                            ✏️ এডিট
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <form action="{{ route('admin.properties.destroy', $property) }}" method="POST" onsubmit="return confirm('প্রপার্টিটি ডিলিট করতে চান?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold flex items-center gap-0.5">
                                                🗑️ মুছুন
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-500">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="text-4xl">🗄️</div>
                                    <div class="text-base font-bold text-slate-800">প্রাইভেট ডিরেক্টরিতে কোনো প্রপার্টি নেই</div>
                                    <p class="text-xs text-slate-500">
                                        আপনি নতুন প্রপার্টি তৈরি করার সময় "প্রাইভেট ডিরেক্টরিতে সংরক্ষণ" নির্বাচন করতে পারেন অথবা লাইভ প্রপার্টিকে আনপাবলিশ করে এখানে রাখতে পারেন।
                                    </p>
                                    <a href="{{ route('admin.properties.create', ['destination' => 'directory']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                                        <span>➕</span> নতুন প্রাইভেট প্রপার্টি যুক্ত করুন
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($properties->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $properties->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
