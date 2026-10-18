@php
    $rawWa = \App\Models\Setting::get('adm_admin_whatsapp_number', \App\Models\Setting::get('admin_whatsapp_number', '01672646242'));
    $cleanWa = preg_replace('/[^0-9]/', '', (string)$rawWa);
    $intlWa = str_starts_with($cleanWa, '0') ? ('88' . $cleanWa) : $cleanWa;
    if (empty($intlWa)) {
        $intlWa = '8801672646242';
    }

    $propTitle = isset($property) && !empty($property->title) ? $property->title : null;
    $propCode  = isset($property) && !empty($property->property_code) ? $property->property_code : null;
    $propPrice = isset($property) && isset($property->price) && $property->price > 0 ? (number_format($property->price) . ' ৳') : null;
    $propImage = isset($property) && method_exists($property, 'getFirstMediaUrl') ? $property->getFirstMediaUrl() : (isset($property->featured_image) ? asset($property->featured_image) : null);
    $propBlock = isset($property) && !empty($property->block) ? ('ব্লক ' . $property->block) : null;
    $propUrl   = url()->current();

    $siteName  = \App\Models\Setting::get('adm_site_name', 'PropertyKoi');
    $authName  = auth()->check() ? auth()->user()->name : '';
    $authPhone = auth()->check() ? (auth()->user()->whatsapp_number ?: auth()->user()->phone) : '';
@endphp

<!-- Scoped CSS: Strict Right-Side Pinning & WhatsApp Theme -->
<style>
    [x-cloak] {
        display: none !important;
    }
    #pk-whatsapp-widget {
        position: fixed !important;
        right: 20px !important;
        left: auto !important;
        bottom: 24px !important;
        z-index: 999999 !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: inherit;
    }
    @media (max-width: 768px) {
        #pk-whatsapp-widget {
            right: 14px !important;
            left: auto !important;
            bottom: 74px !important;
        }
    }
    .pk-wa-chat-canvas {
        background-color: #EFEAE2;
        background-image: radial-gradient(#d3c9be 0.75px, transparent 0.75px), radial-gradient(#d3c9be 0.75px, #EFEAE2 0.75px);
        background-size: 28px 28px;
        background-position: 0 0, 14px 14px;
    }
    .pk-wa-scroll::-webkit-scrollbar {
        width: 4px;
    }
    .pk-wa-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(0,0,0,0.18);
        border-radius: 4px;
    }
</style>

<!-- In-Site WhatsApp Live Chat Widget Root -->
<div id="pk-whatsapp-widget"
     x-data="pkLiveChatWidget()"
     class="select-none pointer-events-auto">

    <!-- 1. POPUP CHAT WINDOW (Opens Inside Site, Anchored to Right) -->
    <div x-show="isOpen"
         x-cloak
         style="display: none;"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="absolute bottom-18 sm:bottom-20 right-0 w-[calc(100vw-28px)] sm:w-[380px] max-w-[400px] h-[520px] max-h-[80vh] bg-white rounded-3xl shadow-2xl border border-emerald-900/10 flex flex-col overflow-hidden z-50">

        <!-- Top Header (WhatsApp Styled) -->
        <div class="bg-gradient-to-r from-[#075E54] to-[#128C7E] text-white p-3.5 sm:p-4 flex items-center justify-between shadow-md shrink-0">
            <div class="flex items-center gap-3">
                <div class="relative w-11 h-11 rounded-full bg-white/10 p-0.5 border border-white/20 shrink-0">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80"
                         alt="Customer Support"
                         class="w-full h-full object-cover rounded-full">
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-[#075E54] rounded-full"></span>
                </div>
                <div>
                    <div class="flex items-center gap-1.5 leading-none">
                        <h4 class="font-bold text-sm text-white m-0 tracking-wide">{{ $siteName }} সাপোর্ট</h4>
                        <svg class="w-3.5 h-3.5 text-emerald-300 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <p class="text-[11px] text-emerald-100 m-0 mt-0.5 flex items-center gap-1 opacity-90">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        <span>অনলাইনে আছেন • সরাসরি রিপ্লাই</span>
                    </p>
                </div>
            </div>

            <!-- Close Button -->
            <button type="button"
                    @click="toggleChat()"
                    class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition cursor-pointer"
                    title="উইন্ডো বন্ধ করুন">
                ✕
            </button>
        </div>

        <!-- Navigation Tabs: Live Chat vs Request Call -->
        <div class="bg-slate-100 border-b border-slate-200 px-3 py-1.5 flex items-center gap-2 text-xs font-bold shrink-0">
            <button type="button"
                    @click="activeTab = 'chat'"
                    :class="activeTab === 'chat' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="flex-1 py-1 px-3 rounded-lg transition flex items-center justify-center gap-1.5 cursor-pointer">
                <span>💬</span>
                <span>WhatsApp চ্যাট</span>
            </button>
            <button type="button"
                    @click="activeTab = 'callback'"
                    :class="activeTab === 'callback' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="flex-1 py-1 px-3 rounded-lg transition flex items-center justify-center gap-1.5 cursor-pointer">
                <span>📞</span>
                <span>কলব্যাক চান?</span>
            </button>
        </div>

        <!-- TAB 1: LIVE CHAT -->
        <div x-show="activeTab === 'chat'" class="flex-1 flex flex-col min-h-0">
            <!-- Scrollable Message Canvas with WhatsApp Wallpaper -->
            <div x-ref="chatMessages" class="flex-1 p-3.5 sm:p-4 overflow-y-auto space-y-3.5 pk-wa-chat-canvas pk-wa-scroll text-xs">

                <!-- Encryption Notice -->
                <div class="text-center">
                    <span class="inline-block bg-[#FFF8E7] text-[#5E583B] text-[10px] font-medium px-3 py-1 rounded-full shadow-2xs border border-[#F3E7C4]">
                        🔒 এন্ড-টু-এন্ড সুরক্ষিত প্রপার্টি ইনকোয়ারি
                    </span>
                </div>

                <!-- Date Divider -->
                <div class="text-center my-1">
                    <span class="inline-block bg-white/80 text-slate-500 text-[10px] font-bold px-2.5 py-0.5 rounded-md shadow-2xs">
                        আজ
                    </span>
                </div>

                <!-- Support Welcome Message -->
                <div class="flex items-end gap-2 max-w-[85%]">
                    <div class="bg-white text-slate-800 rounded-2xl rounded-bl-xs p-3 shadow-xs border border-slate-200/60 leading-relaxed">
                        <div class="font-extrabold text-[11px] text-[#075E54] mb-1">
                            {{ $siteName }} সাপোর্ট
                        </div>
                        <p class="m-0 leading-normal text-xs text-slate-700">
                            আসসালামু আলাইকুম! 👋 <strong>{{ $siteName }}</strong>-তে স্বাগতম। আপনি কোনো প্রপার্টি নিয়ে জানতে চান বা কোনো সাহায্য প্রয়োজন?
                        </p>
                        <span class="block text-[9px] text-slate-400 text-right mt-1.5">অনলাইন 🟢</span>
                    </div>
                </div>

                <!-- Current Property Preview Card (if applicable) -->
                @if($propTitle && $propCode)
                    <div class="flex items-end gap-2 max-w-[92%]">
                        <div class="bg-white text-slate-800 rounded-2xl rounded-bl-xs p-3 shadow-md border-l-4 border-l-emerald-500 border border-slate-200/80 w-full space-y-2">
                            <div class="text-[10px] font-black uppercase text-emerald-700 tracking-wider flex items-center gap-1">
                                <span>🏢</span> বর্তমানে প্রদর্শিত প্রপার্টি
                            </div>
                            <div class="flex items-center gap-2.5 bg-slate-50 p-2 rounded-xl border border-slate-100">
                                @if($propImage)
                                    <img src="{{ $propImage }}" alt="{{ $propTitle }}" class="w-12 h-12 object-cover rounded-lg shrink-0 border border-slate-200">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs shrink-0">
                                        🏢
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h5 class="font-bold text-xs text-slate-900 truncate m-0">{{ $propTitle }}</h5>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-black text-[10px]">#{{ $propCode }}</span>
                                        @if($propBlock)
                                            <span class="text-[10px] text-slate-500">{{ $propBlock }}</span>
                                        @endif
                                    </div>
                                    @if($propPrice)
                                        <div class="font-extrabold text-xs text-emerald-700 mt-1">{{ $propPrice }}</div>
                                    @endif
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-600 m-0">
                                এই প্রপার্টি সম্পর্কে দ্রুত জানতে নিচের অপশনে ট্যাপ করুন অথবা মেসেজ লিখুন:
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Dynamic User Messages (Appended in Chat Canvas) -->
                <template x-for="msg in userMessages" :key="msg.id">
                    <div class="flex items-end justify-end gap-1.5 max-w-[88%] ml-auto">
                        <div class="bg-[#DCF8C6] text-slate-800 rounded-2xl rounded-br-xs p-3 shadow-xs border border-emerald-200/50 leading-relaxed text-xs">
                            <p class="m-0 break-words" x-text="msg.text"></p>
                            <div class="flex items-center justify-end gap-1 text-[9px] text-slate-400 mt-1">
                                <span x-text="msg.time"></span>
                                <svg class="w-3.5 h-3.5 text-blue-500 fill-current" viewBox="0 0 16 15">
                                    <path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.879a.32.32 0 0 1-.484.033L1.891 7.769a.366.366 0 0 0-.515.006l-.423.433a.364.364 0 0 0 .006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Connecting / Sending Indicator -->
                <div x-show="isConnecting" x-cloak class="flex items-center gap-2 bg-emerald-50 text-emerald-800 text-[11px] font-bold p-2.5 rounded-xl border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>সরাসরি হোয়াটসঅ্যাপে কানেক্ট হচ্ছে...</span>
                </div>

                <!-- Quick-Reply Suggestion Chips -->
                <div class="pt-1">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">💡 দ্রুত জানতে যেকোনো একটি বেছে নিন:</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button"
                                @click="sendUserMessage('প্রপার্টির সর্বশেষ মূল্য ও কিস্তির সুবিধা জানতে চাই')"
                                class="bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 text-[11px] font-semibold px-2.5 py-1.5 rounded-xl shadow-2xs transition active:scale-95 text-left cursor-pointer">
                            💰 সর্বশেষ মূল্য কত?
                        </button>
                        <button type="button"
                                @click="sendUserMessage('আমি প্রপার্টিটি সরেজমিনে ভিজিট করতে চাই')"
                                class="bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 text-[11px] font-semibold px-2.5 py-1.5 rounded-xl shadow-2xs transition active:scale-95 text-left cursor-pointer">
                            📍 সরেজমিনে ভিজিট করতে চাই
                        </button>
                        <button type="button"
                                @click="sendUserMessage('কাগজপত্র ও দলিলের কপি যাচাই করতে চাই')"
                                class="bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 text-[11px] font-semibold px-2.5 py-1.5 rounded-xl shadow-2xs transition active:scale-95 text-left cursor-pointer">
                            📄 কাগজপত্র ঠিক আছে কি?
                        </button>
                        <button type="button"
                                @click="sendUserMessage('সরাসরি দায়িত্বপ্রাপ্ত প্রতিনিধির সাথে কথা বলতে চাই')"
                                class="bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 text-[11px] font-semibold px-2.5 py-1.5 rounded-xl shadow-2xs transition active:scale-95 text-left cursor-pointer">
                            📞 সরাসরি কথা বলতে চাই
                        </button>
                    </div>
                </div>

            </div>

            <!-- Footer Message Input Form -->
            <form @submit.prevent="sendUserMessage()" class="p-2 sm:p-2.5 bg-slate-100 border-t border-slate-200 flex items-center gap-2 shrink-0">
                <div class="relative flex-1">
                    <input type="text"
                           x-ref="chatInput"
                           x-model="userMessage"
                           placeholder="আপনার মেসেজ লিখুন..."
                           class="w-full bg-white text-slate-800 text-xs px-3.5 py-2.5 rounded-full border border-slate-300 focus:outline-hidden focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 pr-9">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">💬</span>
                </div>

                <!-- Green Send Button -->
                <button type="submit"
                        class="w-10 h-10 rounded-full bg-[#25D366] hover:bg-[#128C7E] text-white flex items-center justify-center shadow-md transition active:scale-90 shrink-0 cursor-pointer"
                        title="মেসেজ পাঠান">
                    <svg class="w-4 h-4 fill-current ml-0.5" viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- TAB 2: REQUEST DIRECT CALLBACK -->
        <div x-show="activeTab === 'callback'" x-cloak class="flex-1 p-4 bg-slate-50 flex flex-col justify-between overflow-y-auto">
            <template x-if="callbackSuccess">
                <div class="my-auto text-center py-6 px-4 space-y-3 bg-white rounded-2xl border border-emerald-200 shadow-sm">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mx-auto">
                        ✓
                    </div>
                    <h4 class="font-extrabold text-sm text-slate-900 m-0">কলব্যাক রিকোয়েস্ট গৃহীত হয়েছে!</h4>
                    <p class="text-xs text-slate-600 m-0">
                        আমাদের প্রপার্টি কনসালট্যান্ট খুব দ্রুত আপনার দেওয়া নম্বরে কল করবেন। ধন্যবাদ!
                    </p>
                    <button type="button"
                            @click="callbackSuccess = false; activeTab = 'chat'"
                            class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-xs cursor-pointer">
                        চ্যাটে ফিরে যান
                    </button>
                </div>
            </template>

            <template x-if="!callbackSuccess">
                <form @submit.prevent="submitCallback()" class="space-y-3">
                    <div class="text-center pb-1">
                        <div class="text-2xl mb-1">📞</div>
                        <h4 class="font-bold text-slate-800 text-sm m-0">সরাসরি ফোন কল চান?</h4>
                        <p class="text-[11px] text-slate-500 m-0 mt-0.5">আপনার নম্বর দিলে আমাদের টিম দ্রুত আপনাকে কল করবে</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">আপনার নাম</label>
                        <input type="text"
                               x-model="callbackName"
                               placeholder="আপনার নাম"
                               class="w-full text-xs px-3 py-2 bg-white rounded-xl border border-slate-300 focus:outline-hidden focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">ফোন নম্বর <span class="text-rose-500">*</span></label>
                        <input type="tel"
                               x-model="callbackPhone"
                               required
                               placeholder="017xxxxxxxx"
                               class="w-full text-xs px-3 py-2 bg-white rounded-xl border border-slate-300 font-mono focus:outline-hidden focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">কী জানতে চান? (ঐচ্ছিক)</label>
                        <textarea x-model="callbackMessage"
                                  rows="2"
                                  placeholder="যেমন: প্রপার্টির বিস্তারিত ও ভিজিটের সময়..."
                                  class="w-full text-xs px-3 py-2 bg-white rounded-xl border border-slate-300 focus:outline-hidden focus:border-emerald-500"></textarea>
                    </div>

                    <button type="submit"
                            :disabled="callbackSending"
                            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                        <span x-show="!callbackSending">কলব্যাক রিকোয়েস্ট পাঠান &rarr;</span>
                        <span x-show="callbackSending">পাঠানো হচ্ছে...</span>
                    </button>
                </form>
            </template>
        </div>

    </div>

    <!-- 2. FLOATING TRIGGER BUTTON & GREETING PROMPT (Anchored Right) -->
    <div class="flex items-end justify-end gap-2.5">

        <!-- Dismissible Greeting Bubble (Visible when chat is closed) -->
        <div x-show="!isOpen && showTooltip && !tooltipDismissed"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click="toggleChat()"
             class="relative bg-white text-slate-800 rounded-2xl p-3 shadow-2xl border border-emerald-100 max-w-[200px] sm:max-w-[260px] text-xs cursor-pointer hover:border-emerald-400 transition group shrink-0">

            <!-- Mini Dismiss ✕ -->
            <button type="button"
                    @click.stop="dismissTooltip()"
                    class="absolute -top-1.5 -left-1.5 w-5 h-5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 text-[10px] font-bold flex items-center justify-center shadow-xs transition cursor-pointer"
                    title="বন্ধ করুন">
                ✕
            </button>

            <div class="flex items-center gap-1.5 mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-black text-emerald-800 text-[10px] uppercase">{{ $siteName }} সাপোর্ট</span>
                <span class="ml-auto text-[9px] text-slate-400 font-semibold">অনলাইন 🟢</span>
            </div>

            <p class="text-slate-700 m-0 leading-tight text-[11px]">
                @if($propTitle)
                    👋 এই প্রপার্টি (<strong>#{{ $propCode }}</strong>) দেখতে চান? চ্যাট শুরু করুন!
                @else
                    👋 আসসালামু আলাইকুম! কোনো প্রশ্ন থাকলে এখানে চ্যাট করুন।
                @endif
            </p>

            <div class="mt-2 pt-1 border-t border-slate-100 flex items-center justify-between text-[10px] font-extrabold text-emerald-600 group-hover:text-emerald-700">
                <span>চ্যাট উইন্ডো খুলুন &rarr;</span>
            </div>

            <!-- Bubble Pointer pointing right -->
            <div class="hidden sm:block absolute top-1/2 -right-1.5 -translate-y-1/2 w-3 h-3 bg-white border-t border-r border-emerald-100 rotate-45"></div>
        </div>

        <!-- Circular WhatsApp Action Button -->
        <button type="button"
                @click="toggleChat()"
                class="relative w-14 h-14 sm:w-15 sm:h-15 rounded-full flex items-center justify-center text-white shadow-2xl transition-all duration-300 transform hover:scale-110 active:scale-95 cursor-pointer shrink-0 focus:outline-hidden"
                style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);"
                title="WhatsApp লাইভ চ্যাট"
                aria-label="Toggle WhatsApp Live Chat">

            <!-- Pulsing Online Badge -->
            <span x-show="!isOpen" class="absolute -top-0.5 -right-0.5 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-white shadow-xs"></span>
            </span>

            <!-- WhatsApp Icon (When closed) -->
            <span x-show="!isOpen" class="transition-transform duration-200">
                <svg class="w-8 h-8 sm:w-9 sm:h-9 fill-current drop-shadow-md" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
            </span>

            <!-- Close Icon (When open) -->
            <span x-show="isOpen" x-cloak style="display: none;" class="text-2xl font-black leading-none">
                ✕
            </span>
        </button>

    </div>

</div>

<!-- Clean Script Block: Zero Quotes Inside HTML Attributes -->
<script>
function pkLiveChatWidget() {
    const isDismissed = (function() {
        try {
            return localStorage.getItem('pk_wa_bubble_dismissed') === '1' || sessionStorage.getItem('pk_wa_bubble_dismissed') === '1';
        } catch(e) {
            return false;
        }
    })();

    return {
        isOpen: false,
        showTooltip: true,
        tooltipDismissed: isDismissed,
        activeTab: 'chat',
        userMessage: '',
        callbackName: {!! json_encode($authName) !!},
        callbackPhone: {!! json_encode($authPhone) !!},
        callbackMessage: '',
        callbackSending: false,
        callbackSuccess: false,
        userMessages: [],
        isConnecting: false,
        adminWaNumber: '{{ $intlWa }}',
        propCode: '{{ $propCode ?? '' }}',
        propTitle: {!! json_encode($propTitle ?? '') !!},
        propUrl: '{{ $propUrl }}',
        trackUrl: '{{ route('live-chat.track') }}',
        csrfToken: '{{ csrf_token() }}',

        dismissTooltip() {
            this.tooltipDismissed = true;
            try {
                localStorage.setItem('pk_wa_bubble_dismissed', '1');
                sessionStorage.setItem('pk_wa_bubble_dismissed', '1');
            } catch(e) {}
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            this.dismissTooltip();
            if (this.isOpen) {
                this.$nextTick(() => {
                    const input = this.$refs.chatInput;
                    if (input) input.focus();
                    this.scrollToBottom();
                });
                if (window.trackClarityEvent) {
                    window.trackClarityEvent('whatsapp_widget_open', {
                        path: window.location.pathname,
                        property_code: this.propCode
                    });
                }
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatMessages;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        sendUserMessage(text) {
            const msgText = (text || this.userMessage).trim();
            if (!msgText) return;

            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            // 1. Append user message bubble immediately on screen
            this.userMessages.push({
                id: Date.now(),
                sender: 'user',
                text: msgText,
                time: timeStr
            });

            const fullWaText = this.propCode
                ? `[${this.propCode}] ${msgText}\n(লিঙ্ক: ${this.propUrl})`
                : msgText;

            const targetUrl = 'https://wa.me/' + this.adminWaNumber + '?text=' + encodeURIComponent(fullWaText);

            // 2. Silent background sync to database & notifications
            this.syncLiveChatLead(msgText);

            this.userMessage = '';
            this.scrollToBottom();

            // 3. Connect to WhatsApp
            this.isConnecting = true;
            setTimeout(() => {
                window.open(targetUrl, '_blank');
                this.isConnecting = false;
            }, 400);
        },

        async submitCallback() {
            if (!this.callbackPhone || this.callbackPhone.length < 9) {
                alert('দয়া করে সঠিক ফোন নম্বর লিখুন');
                return;
            }
            this.callbackSending = true;

            const payload = {
                _token: this.csrfToken,
                name: this.callbackName || 'ওয়েবসাইট ভিজিটর',
                phone: this.callbackPhone,
                message: this.callbackMessage || (this.propCode ? `প্রপার্টি #${this.propCode} এর জন্য সরাসরি কল চেয়েছেন।` : 'সরাসরি ফোন কল চেয়েছেন।'),
                property_code: this.propCode || null
            };

            try {
                const res = await fetch(this.trackUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    this.callbackSuccess = true;
                    if (window.trackClarityEvent) {
                        window.trackClarityEvent('callback_requested', { phone: this.callbackPhone });
                    }
                }
            } catch (e) {
                console.error('Callback error:', e);
            } finally {
                this.callbackSending = false;
            }
        },

        syncLiveChatLead(message) {
            const payload = {
                _token: this.csrfToken,
                name: this.callbackName || 'ওয়েবসাইট ভিজিটর',
                phone: this.callbackPhone || '',
                message: message,
                property_code: this.propCode || null
            };
            fetch(this.trackUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            }).catch(err => console.debug(err));
        }
    };
}
</script>
