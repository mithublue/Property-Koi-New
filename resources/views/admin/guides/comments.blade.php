@extends('layouts.admin')

@section('page_title', 'গাইড প্রশ্নোত্তর ও কমেন্ট মডারেশন')

@section('page_badge')
    <span class="text-xs bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full">
        {{ $stats['pending'] }}টি অপেক্ষমান
    </span>
@endsection

@section('content')
<div class="p-6 md:p-8 space-y-6" x-data="{
    replyModalOpen: false,
    activeCommentId: null,
    activeAuthorName: '',
    activeCommentText: '',
    openReply(id, name, text) {
        this.activeCommentId = id;
        this.activeAuthorName = name;
        this.activeCommentText = text;
        this.replyModalOpen = true;
    }
}">

    <!-- Success / Error Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-semibold flex items-center gap-3 shadow-xs">
            <span class="text-xl">✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm font-semibold flex items-center gap-3 shadow-xs">
            <span class="text-xl">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Top Action Bar & Filter Tabs -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('admin.guides.comments.index', ['status' => 'all', 'q' => request('q')]) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $status === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>সবগুলো</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $status === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $stats['total'] }}</span>
                </a>
                <a href="{{ route('admin.guides.comments.index', ['status' => 'pending', 'q' => request('q')]) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $status === 'pending' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                    <span>⏳ অপেক্ষমান (Pending)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $status === 'pending' ? 'bg-white/20 text-white' : 'bg-amber-200 text-amber-900' }}">{{ $stats['pending'] }}</span>
                </a>
                <a href="{{ route('admin.guides.comments.index', ['status' => 'approved', 'q' => request('q')]) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $status === 'approved' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                    <span>✅ অনুমোদিত (Live)</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $status === 'approved' ? 'bg-white/20 text-white' : 'bg-emerald-200 text-emerald-900' }}">{{ $stats['approved'] }}</span>
                </a>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('admin.guides.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition">
                    <span>📚 সকল গাইড তালিকা</span>
                </a>
                <a href="{{ route('guides.index') }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 transition">
                    <span>🌐 লাইভ নলেজ হাব</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>

        <!-- Search Form -->
        <form action="{{ route('admin.guides.comments.index') }}" method="GET" class="flex items-center gap-2 pt-2 border-t border-slate-100">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative flex-1">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="প্রেরকের নাম, ফোন, ইমেইল বা মন্তব্যের টেক্সট খুঁজুন..."
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-emerald-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                খুঁজুন
            </button>
            @if(request('q'))
                <a href="{{ route('admin.guides.comments.index', ['status' => $status]) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition">
                    রিসেট
                </a>
            @endif
        </form>
    </div>

    <!-- Comments List -->
    <div class="space-y-4">
        @forelse($comments as $comment)
            <div class="bg-white rounded-2xl border {{ $comment->is_approved ? 'border-slate-200' : 'border-amber-300 ring-2 ring-amber-100' }} p-5 sm:p-6 shadow-xs transition hover:shadow-md">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <!-- Left: Author & Guide Info -->
                    <div class="flex items-start gap-3.5">
                        <div class="w-11 h-11 rounded-2xl {{ $comment->is_admin_reply ? 'bg-emerald-600 text-white' : ($comment->is_approved ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-800') }} flex items-center justify-center font-black text-base shrink-0 border border-slate-200/60 shadow-2xs">
                            {{ $comment->is_admin_reply ? '👑' : mb_substr($comment->author_name, 0, 1) }}
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="text-sm font-bold text-slate-900">{{ $comment->author_name }}</h4>

                                @if($comment->is_admin_reply)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">
                                        অ্যাডমিন উত্তর
                                    </span>
                                @elseif($comment->is_approved)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        ✓ প্রকাশিত
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                                        ⏳ অপেক্ষমান
                                    </span>
                                @endif

                                @if($comment->parent_id)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">
                                        #{{ $comment->parent_id }} এর রিপ্লাই
                                    </span>
                                @endif
                            </div>

                            <!-- Contact details -->
                            <div class="flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                                @if($comment->author_phone)
                                    <a href="tel:{{ $comment->author_phone }}" class="text-indigo-600 font-bold hover:underline flex items-center gap-1">
                                        <span>📞</span>
                                        <span>{{ $comment->author_phone }}</span>
                                    </a>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $comment->author_phone) }}" target="_blank" class="text-emerald-600 font-bold hover:underline flex items-center gap-1">
                                        <span>💬 WhatsApp</span>
                                    </a>
                                @endif

                                @if($comment->author_email)
                                    <a href="mailto:{{ $comment->author_email }}" class="text-slate-600 hover:underline flex items-center gap-1">
                                        <span>✉️</span>
                                        <span>{{ $comment->author_email }}</span>
                                    </a>
                                @endif

                                <span class="text-slate-400">•</span>
                                <time class="text-slate-400" title="{{ $comment->created_at }}">
                                    {{ $comment->formatted_date }} ({{ $comment->created_at->diffForHumans() }})
                                </time>
                            </div>

                            <!-- Target Guide -->
                            @if($comment->guide)
                                <div class="pt-1 flex items-center gap-1.5 text-xs">
                                    <span class="text-slate-400">গাইড:</span>
                                    <a href="{{ route('guides.show', $comment->guide->slug) }}" target="_blank"
                                       class="font-bold text-slate-700 hover:text-emerald-600 transition flex items-center gap-1">
                                        <span>{{ $comment->guide->title }}</span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Moderation Action Buttons -->
                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-start">
                        @if(!$comment->is_approved)
                            <form action="{{ route('admin.guides.comments.approve', $comment) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1 shadow-xs cursor-pointer">
                                    <span>✓ অনুমোদন</span>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.guides.comments.reject', $comment) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition flex items-center gap-1 cursor-pointer">
                                    <span>⏸️ স্থগিত</span>
                                </button>
                            </form>
                        @endif

                        <!-- Reply button -->
                        <button type="button"
                                @click="openReply({{ $comment->id }}, '{{ addslashes($comment->author_name) }}', '{{ addslashes(str_replace(["\r", "\n"], ' ', Str::limit($comment->comment, 120))) }}')"
                                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 transition flex items-center gap-1 cursor-pointer">
                            <span>💬 উত্তর দিন</span>
                        </button>

                        <!-- Delete button -->
                        <form action="{{ route('admin.guides.comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই মন্তব্যটি মুছে ফেলতে চান?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="মুছে ফেলুন">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Comment Body -->
                <div class="mt-4 pt-3 border-t border-slate-100 text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line bg-slate-50/60 p-3.5 rounded-xl">
                    {{ $comment->comment }}
                </div>

                <!-- Nested Admin Replies if any -->
                @if($comment->replies->count() > 0)
                    <div class="mt-4 pl-4 sm:pl-8 space-y-2 border-l-2 border-emerald-400">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">রিপ্লাইসমূহ:</span>
                        @foreach($comment->replies as $nestedReply)
                            <div class="bg-emerald-50/50 rounded-xl p-3 border border-emerald-200/60 text-xs">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-emerald-950">{{ $nestedReply->author_name }}</span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-600 text-white">ভেরিফাইড</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-400">{{ $nestedReply->formatted_date }}</span>
                                        <form action="{{ route('admin.guides.comments.destroy', $nestedReply) }}" method="POST" onsubmit="return confirm('এই উত্তরটি মুছে ফেলবেন?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 text-[10px] font-bold cursor-pointer">মুছুন</button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-slate-800 leading-normal whitespace-pre-line">{{ $nestedReply->comment }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-2xl border border-slate-200">
                <span class="text-4xl block mb-2">💬</span>
                <h3 class="text-sm font-bold text-slate-800">কোনো মন্তব্য পাওয়া যায়নি</h3>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $status === 'pending' ? 'কোনো অপেক্ষমান মন্তব্য নেই। সব মন্তব্য পরিদর্শিত ও অনুমোদিত।' : 'এখনও কোনো ভিজিটর মন্তব্য বা প্রশ্ন জমা পড়েনি।' }}
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $comments->links() }}
    </div>

    <!-- Admin Reply Modal -->
    <div x-show="replyModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         @keydown.escape.window="replyModalOpen = false">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200"
             @click.outside="replyModalOpen = false">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-black">👑</span>
                    <div>
                        <h4 class="text-sm font-black text-slate-900">অ্যাডমিন এক্সপার্ট রিপ্লাই</h4>
                        <p class="text-[11px] text-slate-500">PropertyKoi Legal & Property Desk এর পক্ষ থেকে উত্তর</p>
                    </div>
                </div>
                <button type="button" @click="replyModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>

            <!-- Parent Comment Excerpt -->
            <div class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <span class="text-[10px] font-bold text-slate-500 uppercase block mb-0.5">প্রশ্নকর্তা: <strong class="text-slate-800" x-text="activeAuthorName"></strong></span>
                <p class="text-slate-700 italic" x-text="activeCommentText"></p>
            </div>

            <form :action="'{{ url('/backend/guides/comments') }}/' + activeCommentId + '/reply'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        আপনার উত্তর বা বিশেষজ্ঞ মতামত লিখুন:
                    </label>
                    <textarea name="reply" rows="5" required minlength="3" maxlength="3000"
                              placeholder="আইনসম্মত ও সহায়ক সমাধান লিখুন..."
                              class="w-full p-3 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 resize-y"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="replyModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        বাতিল
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                        <span>উত্তর প্রকাশ করুন</span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
