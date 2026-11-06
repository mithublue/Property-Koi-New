@extends('layouts.admin')

@section('title', 'সিস্টেম ডকুমেন্টেশন ও রুলস')
@section('page_title', 'সিস্টেম ডকুমেন্টেশন ও এপিআই আর্কিটেকচার')

@section('content')
<div class="max-w-5xl mx-auto space-y-8 text-xs leading-relaxed" x-data="{
    activeTab: 'api-property',
    copiedProp: false,
    copiedAgent: false,
    copiedKey: false,
    copiedScript: false,
    apiKey: '{{ $apiKey }}'
}">

    <!-- Top Navigation Tabs -->
    <div class="flex items-center gap-2 p-1.5 bg-slate-100 rounded-2xl border border-slate-200 overflow-x-auto">
        <button type="button"
                @click="activeTab = 'api-property'"
                :class="activeTab === 'api-property' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                class="px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 whitespace-nowrap cursor-pointer">
            <span>🏠 Property API (JSON Schema)</span>
        </button>
        <button type="button"
                @click="activeTab = 'api-agent'"
                :class="activeTab === 'api-agent' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                class="px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 whitespace-nowrap cursor-pointer">
            <span>👤 Agent API (JSON Schema)</span>
        </button>
        <button type="button"
                @click="activeTab = 'google-sheets'"
                :class="activeTab === 'google-sheets' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                class="px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 whitespace-nowrap cursor-pointer">
            <span>📑 Google Sheets AI Sync Script</span>
        </button>
        <button type="button"
                @click="activeTab = 'architecture'"
                :class="activeTab === 'architecture' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                class="px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 whitespace-nowrap cursor-pointer">
            <span>🏛️ সিস্টেম রুলস ও আর্কিটেকচার</span>
        </button>
    </div>

    <!-- API Overview & Credentials Banner -->
    <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl border border-slate-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider inline-block mb-1.5">
                    PropertyKoi REST API (v1)
                </span>
                <h3 class="text-lg sm:text-xl font-black">AI ও ৩য় পার্টি ইন্টিগ্রেশন হাব</h3>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Google Workspace Gemini, Google Sheets, Zapier, Python স্ক্রিপ্ট বা যেকোনো ৩য় পক্ষ প্ল্যাটফর্ম থেকে সরাসরি লাইভ সাইটে প্রপার্টি ও এজেন্ট ডেটা ইনসার্ট করার জন্য নিরাপদ REST API।
                </p>
            </div>
            <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10 shrink-0">
                <span class="text-[10px] font-bold text-slate-300 uppercase block mb-1">আপনার সিক্রেট API Key:</span>
                <div class="flex items-center gap-2">
                    <code class="px-2.5 py-1 bg-black/40 rounded-lg font-mono text-emerald-400 text-xs font-bold" x-text="apiKey"></code>
                    <button type="button"
                            @click="navigator.clipboard.writeText(apiKey); copiedKey = true; setTimeout(() => copiedKey = false, 2500)"
                            class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold transition cursor-pointer">
                        <span x-show="!copiedKey">কপি</span>
                        <span x-show="copiedKey" class="text-emerald-300">✓ কপিড</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2 text-xs border-t border-slate-800">
            <div class="p-3 bg-white/5 rounded-xl border border-white/5">
                <span class="text-slate-400 block text-[11px]">Base Endpoint:</span>
                <code class="font-mono text-indigo-300 font-bold">{{ url('/api/v1') }}</code>
            </div>
            <div class="p-3 bg-white/5 rounded-xl border border-white/5">
                <span class="text-slate-400 block text-[11px]">Authentication Header:</span>
                <code class="font-mono text-emerald-300 font-bold">X-API-KEY: {{ $apiKey }}</code>
            </div>
            <div class="p-3 bg-white/5 rounded-xl border border-white/5">
                <span class="text-slate-400 block text-[11px]">Content Format:</span>
                <code class="font-mono text-amber-300 font-bold">application/json</code>
            </div>
        </div>
    </div>

    <!-- TAB 1: Property API Schema -->
    <div x-show="activeTab === 'api-property'" class="space-y-6">
        <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800">POST</span>
                        <code class="text-sm font-bold text-slate-900 font-mono">/api/v1/properties</code>
                    </div>
                    <p class="text-slate-500 text-xs mt-1">
                        "Sell Property" ফর্মের সব ফিল্ডযুক্ত সম্পূর্ণ প্রপার্টি ডেটা ইনসার্ট করার JSON স্কিমা।
                    </p>
                </div>
                <button type="button"
                        @click="navigator.clipboard.writeText(document.getElementById('property-json-template').innerText); copiedProp = true; setTimeout(() => copiedProp = false, 2500)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <span x-show="!copiedProp">📋 সম্পূর্ণ JSON স্কিমা কপি করুন</span>
                    <span x-show="copiedProp" class="font-bold">✅ সফলভাবে কপি হয়েছে!</span>
                </button>
            </div>

            <!-- JSON Schema Display Box -->
            <div class="relative">
                <pre id="property-json-template" class="bg-slate-950 text-slate-100 p-5 rounded-2xl font-mono text-[11px] leading-relaxed overflow-x-auto border border-slate-800 max-h-[500px]">{
  "owner_name": "ইঞ্জিনিয়ার মো: আরিফ",
  "owner_phone": "01811998877",
  "source_type": "owner",
  "relationship_declaration": "আমি এই প্লটের মূল মালিক",
  "property_type": "land",
  "area": "Aftabnagar",
  "custom_area": "",
  "sub_area": "Block C",
  "title": "আফতাবনগর C ব্লকে ৫ কাঠা আকর্ষণীয় দক্ষিণমুখী প্লট বিক্রয়",
  "block": "C",
  "sector": "Sector 2",
  "road_number": "12",
  "road_width_ft": "25",
  "plot_number": "305",
  "holding_number": "14/A",
  "hidden_note": "মালিকের সাথে সরাসরি কথা হয়েছে, পেপারস ১০০% ফ্রেশ।",
  "address_visibility": {
    "block": true,
    "sector": true,
    "road_number": true,
    "road_width_ft": true,
    "plot_number": true,
    "holding_number": false
  },
  "facing": "south",
  "size": "5",
  "price_per_katha": "1.4cr",
  "flat_size_sqft": "",
  "price_per_sqft": "",
  "garage_price": "",
  "utility_price": "",
  "down_payment": "",
  "asking_price": "7cr",
  "is_negotiable": true,
  "description": "আফতাবনগর সি ব্লকে ৫ কাঠা চমৎকার দক্ষিণমুখী আবাসিক প্লট।",
  "fb_caption": "আফতাবনগর সি ব্লকে ৫ কাঠা রেডি প্লট!",
  "post_to_facebook": false,
  "youtube_video_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
  "google_map_pin_url": "https://maps.google.com/?q=23.766,90.435",
  "latitude": 23.766,
  "longitude": 90.435,
  "is_corner": false,
  "is_lake_facing": true,
  "boundary_wall_done": true,
  "soil_filling_status": "filled",
  "bedrooms": 3,
  "bathrooms": 3,
  "balconies": 2,
  "floor_number": 4,
  "completion_status": "ready",
  "has_lift": true,
  "parking_available": true,
  "has_generator": true,
  "has_substation": false,
  "has_gas": true,
  "other_features": [
    "বিদ্যুৎ সংযোগ",
    "ওয়াইড ড্রেনেজ ব্যবস্থা",
    "নিরাপদ আবাসিক পরিবেশ"
  ],
  "status": "directory"
}</pre>
            </div>

            <!-- Field Definitions Guide -->
            <div class="space-y-3 pt-2">
                <h4 class="font-bold text-slate-900 text-sm">💡 ফিল্ড ও ইন্টেলিজেন্ট অটো-হ্যান্ডলিং ব্যাখ্যা:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-slate-700">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <strong class="text-slate-900 block mb-0.5">১. অটো-এজেন্ট ডিরেক্টরি লিংকিং:</strong>
                        <p class="text-[11px] text-slate-600">
                            <code>owner_phone</code> বা <code>contact</code> দেওয়া হলে সিস্টেম স্বয়ংক্রিয়ভাবে চেক করে এজেন্ট নিবন্ধিত কিনা। নিবন্ধিত না থাকলে স্বয়ংক্রিয়ভাবে এজেন্ট প্রোফাইল বানিয়ে প্রপার্টির সাথে লিংক করে দেয়।
                        </p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <strong class="text-slate-900 block mb-0.5">২. স্মার্ট প্রাইস ও সাইজ পার্সার:</strong>
                        <p class="text-[11px] text-slate-600">
                            মূল্য বা সাইজ <code>"1.55cr"</code>, <code>"6.2 cr"</code>, <code>"80 লাখ"</code> বা সাধারণ সংখ্যা যেকোনো ফরম্যাটে দিন, এপিআই স্বয়ংক্রিয়ভাবে কোটি/লাখ হিসাব করে নিবে।
                        </p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <strong class="text-slate-900 block mb-0.5">৩. স্ট্যাটাস অপশনস (status):</strong>
                        <p class="text-[11px] text-slate-600">
                            <code>"directory"</code> বা <code>"draft"</code> দিলে প্রপার্টি প্রাইভেট ভল্টে থাকবে। <code>"live"</code> দিলে সরাসরি ওয়েবসাইটে প্রকাশিত হবে।
                        </p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <strong class="text-slate-900 block mb-0.5">৪. বাল্ক ইনসার্ট সাপোর্ট:</strong>
                        <p class="text-[11px] text-slate-600">
                            একাধিক রো একসাথে পাঠাতে <code>POST /api/v1/properties/bulk</code> এ <code>{"properties": [ {...}, {...} ]}</code> পাঠাতে পারেন।
                        </p>
                    </div>
                </div>
            </div>

            <!-- cURL Sample -->
            <div class="space-y-2 pt-2">
                <span class="font-bold text-slate-900 block">cURL টেস্ট কমান্ড:</span>
                <pre class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-[11px] overflow-x-auto">curl -X POST "{{ url('/api/v1/properties') }}" \
  -H "X-API-KEY: {{ $apiKey }}" \
  -H "Content-Type: application/json" \
  -d '{
    "property_type": "land",
    "area": "Bashundhara",
    "block": "F",
    "road_number": "25",
    "plot_number": "4500",
    "size": "4",
    "owner_name": "Saiful Islam",
    "owner_phone": "+880 1924-400307",
    "price_per_katha": "1.55cr",
    "asking_price": "6.2cr",
    "description": "Just close to Viqarunnisa, asking",
    "status": "directory"
  }'</pre>
            </div>
        </div>
    </div>

    <!-- TAB 2: Agent API Schema -->
    <div x-show="activeTab === 'api-agent'" class="space-y-6">
        <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-100 text-indigo-800">POST</span>
                        <code class="text-sm font-bold text-slate-900 font-mono">/api/v1/agents</code>
                    </div>
                    <p class="text-slate-500 text-xs mt-1">
                        এজেন্ট বা প্রপার্টি মালিকের সম্পূর্ণ প্রোফাইল ডিরেক্টরিতে যুক্ত ও আপডেট করার JSON স্কিমা।
                    </p>
                </div>
                <button type="button"
                        @click="navigator.clipboard.writeText(document.getElementById('agent-json-template').innerText); copiedAgent = true; setTimeout(() => copiedAgent = false, 2500)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <span x-show="!copiedAgent">📋 সম্পূর্ণ এজেন্ট JSON স্কিমা কপি করুন</span>
                    <span x-show="copiedAgent" class="font-bold">✅ সফলভাবে কপি হয়েছে!</span>
                </button>
            </div>

            <!-- Agent JSON Display Box -->
            <div class="relative">
                <pre id="agent-json-template" class="bg-slate-950 text-slate-100 p-5 rounded-2xl font-mono text-[11px] leading-relaxed overflow-x-auto border border-slate-800">{
  "name": "মো: সাইফুল ইসলাম",
  "phone": "+880 1924-400307",
  "whatsapp_number": "+880 1924-400307",
  "email": "saiful.realestate@gmail.com",
  "agency_name": "বাটারফ্লাই রিয়েল এস্টেট",
  "license_no": "RE-DHK-2026-981",
  "experience_years": 8,
  "direct_owner_network_count": 45,
  "role": "agent",
  "rating": 5.0,
  "is_verified_direct_agent": true,
  "notes": "বসুন্ধরা ও আফতাবনগর অঞ্চলের অভিজ্ঞ ও বিশ্বস্ত প্রপার্টি এজেন্ট।"
}</pre>
            </div>

            <!-- Agent Endpoints Overview -->
            <div class="space-y-3 pt-2">
                <h4 class="font-bold text-slate-900 text-sm">💡 এজেন্ট সংক্রান্ত এপিআই রুটসমূহ:</h4>
                <div class="space-y-2 font-mono text-[11px]">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                        <span><strong class="text-emerald-700 font-bold">GET</strong> /api/v1/agents?q=সাইফুল&role=agent</span>
                        <span class="text-slate-500 font-sans text-xs">এজেন্ট ও মালিক ডিরেক্টরি সার্চ ও ফিল্টার</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                        <span><strong class="text-indigo-700 font-bold">POST</strong> /api/v1/agents</span>
                        <span class="text-slate-500 font-sans text-xs">নতুন এজেন্ট তৈরি বা তথ্য আপডেট</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                        <span><strong class="text-emerald-700 font-bold">GET</strong> /api/v1/agents/{id বা phone}</span>
                        <span class="text-slate-500 font-sans text-xs">নির্দিষ্ট এজেন্টের প্রোফাইল ও প্রপার্টি তালিকা</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Google Sheets AI Integration Script -->
    <div x-show="activeTab === 'google-sheets'" class="space-y-6">
        <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>📑</span> Google Sheets Apps Script (Gemini AI to Live Site)
                    </h3>
                    <p class="text-slate-500 text-xs mt-1">
                        গুগল শিটের <strong>Extensions &rarr; Apps Script</strong> এ এই কোডটি পেস্ট করলে শিটের উপরে ১-ক্লিক সিঙ্ক মেনু তৈরি হয়ে যাবে।
                    </p>
                </div>
                <button type="button"
                        @click="navigator.clipboard.writeText(document.getElementById('apps-script-code').innerText); copiedScript = true; setTimeout(() => copiedScript = false, 2500)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <span x-show="!copiedScript">📋 সম্পূর্ণ স্ক্রিপ্ট কপি করুন</span>
                    <span x-show="copiedScript" class="font-bold">✅ কপি হয়েছে!</span>
                </button>
            </div>

            <!-- Code display -->
            <pre id="apps-script-code" class="bg-slate-950 text-slate-100 p-5 rounded-2xl font-mono text-[11px] leading-relaxed overflow-x-auto border border-slate-800 max-h-[450px]">/**
 * PropertyKoi Live Site Sync Engine for Google Sheets
 * API: {{ url('/api/v1/properties') }}
 */

const API_URL = "{{ url('/api/v1/properties') }}";
const API_KEY = "{{ $apiKey }}";

// গুগল শিট ওপেন হলে উপরে ১টি কাস্টম মেনু যুক্ত করবে
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu("🏢 PropertyKoi")
    .addItem("🚀 Sync Selected Row(s) to Live Site", "syncSelectedRowToPropertyKoi")
    .addToUi();
}

function syncSelectedRowToPropertyKoi() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const range = sheet.getActiveRange();
  const startRow = range.getRow();
  const numRows = range.getNumRows();

  if (startRow === 1) {
    SpreadsheetApp.getUi().alert("অনুগ্রহ করে হেডার বাদ দিয়ে ডাটা রো নির্বাচন করুন।");
    return;
  }

  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  const rows = sheet.getRange(startRow, 1, numRows, sheet.getLastColumn()).getValues();

  let successCount = 0;
  let errorMessages = [];

  for (let i = 0; i < rows.length; i++) {
    let rowData = {};
    for (let j = 0; j < headers.length; j++) {
      let key = String(headers[j]).trim().toLowerCase().replace(/\s+/g, '_');
      rowData[key] = rows[i][j];
    }

    const payload = {
      property_type: rowData["property_type"] || rowData["type"] || "land",
      area: rowData["area"] || rowData["place"] || "Aftabnagar",
      block: rowData["block"] || "",
      sector: rowData["sector"] || "",
      road_number: rowData["road_number"] || rowData["road"] || "",
      plot_number: rowData["plot_number"] || rowData["plot"] || "",
      size: rowData["katha"] || rowData["size"] || "",
      owner_name: rowData["agent"] || rowData["owner_name"] || "",
      owner_phone: rowData["contact"] || rowData["owner_phone"] || "",
      whatsapp_number: rowData["messenger"] || rowData["whatsapp"] || "",
      price_per_katha: rowData["price"] || rowData["price_per_katha"] || "",
      asking_price: rowData["total_price"] || rowData["asking_price"] || "",
      description: rowData["notes"] || rowData["extra_notes"] || rowData["description"] || "",
      hidden_note: "Source: Google Sheet row " + (startRow + i) + " via Gemini AI",
      status: "directory"
    };

    const options = {
      method: "post",
      contentType: "application/json",
      headers: { "X-API-KEY": API_KEY },
      payload: JSON.stringify(payload),
      muteHttpExceptions: true
    };

    try {
      const response = UrlFetchApp.fetch(API_URL, options);
      const resCode = response.getResponseCode();
      const resJson = JSON.parse(response.getContentText());

      if (resCode === 200 || resCode === 201) {
        successCount++;
      } else {
        errorMessages.push("রো " + (startRow + i) + ": " + (resJson.message || "Error"));
      }
    } catch (err) {
      errorMessages.push("রো " + (startRow + i) + ": " + err.message);
    }
  }

  if (errorMessages.length === 0) {
    SpreadsheetApp.getUi().alert("✅ সফল! " + successCount + "টি প্রপার্টি লাইভ সাইটে যুক্ত হয়েছে।");
  } else {
    SpreadsheetApp.getUi().alert("সফল: " + successCount + "টি\nত্রুটি:\n" + errorMessages.join("\n"));
  }
}</pre>
        </div>
    </div>

    <!-- TAB 4: Platform Architecture & Rules -->
    <div x-show="activeTab === 'architecture'" class="space-y-6">
        <!-- Platform Architecture Overview Card -->
        <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>🏛️</span> প্ল্যাটফর্ম টেকনোলজি ও আর্কিটেকচার
            </h3>
            <p class="text-slate-600">
                এই প্ল্যাটফর্মটি <strong>Laravel 11 + Hotwired Turbo + Alpine.js + Tailwind CSS</strong> ভিত্তিক একটি হাইপার-পারফর্ম্যান্ট SPA এবং PWA সিস্টেম। এটি Hosttier বা যেকোনো স্ট্যান্ডার্ড cPanel Shared Hosting-এ খুব কম মেমোরি খরচে উচ্চগতিতে চলতে পারে।
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
                <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                    <div class="font-bold text-emerald-900 mb-1">⚡ Hotwired Turbo SPA</div>
                    <div class="text-[11px] text-emerald-700">ফুল পেজ রিলোড ছাড়া মোবাইল অ্যাপের মত স্মুথ ট্রানজিশন ও নেভিগেশন।</div>
                </div>
                <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                    <div class="font-bold text-blue-900 mb-1">📱 Progressive Web App (PWA)</div>
                    <div class="text-[11px] text-blue-700">অফলাইন ক্যাশিং ও হোমস্ক্রিন অ্যাপ ইন্সটল সমর্থন।</div>
                </div>
                <div class="p-4 bg-purple-50 rounded-xl border border-purple-200">
                    <div class="font-bold text-purple-900 mb-1">🚀 Shared Hosting Ready</div>
                    <div class="text-[11px] text-purple-700">কোনো হেভিওয়েট প্যাকেজ ছাড়া লাইটওয়েট এবং সুপার ফাস্ট।</div>
                </div>
            </div>
        </div>

        <!-- Core Business Rules -->
        <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200/80 shadow-xs space-y-5">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>⚖️</span> মূল বিজনেস রুলস ও লজিক
            </h3>

            <div class="space-y-4 text-slate-700">
                <div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">১. ৪-দফা বাধ্যতামূলক ভেরিফিকেশন (4-Point Verification)</h4>
                    <p>
                        একটি প্রপার্টি লাইভ হতে হলে ৪টি চেকলিস্ট সম্পন্ন হতে হবে: ১) মালিকের এনআইডি/পরিচয়পত্র, ২) ফিজিক্যাল লোকেশন, ৩) সাইজ/ফেসিং ও রাস্তা, ৪) প্রাথমিক দলিল বা বরাদ্দপত্র। চারটি চেকলিস্ট ট্রু হলেই প্রপার্টি স্বয়ংক্রিয়ভাবে লাইভ হবে এবং সার্চ ইঞ্জিনগুলোকে IndexNow পিং পাঠানো হবে।
                    </p>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">২. অ্যান্টি-ডুপ্লিকেট প্লট ও ডিরেক্ট সোর্স মার্জিং</h4>
                    <p>
                        একই ব্লক ও প্লট নম্বরের জমি একাধিকবার সাবমিট হলে নতুন কোনো প্রপার্টি রো তৈরি হয় না। বরং বিদ্যমান প্রপার্টির অধীনে একটি নতুন <code>PropertyDirectSource</code> রেকর্ড সংযুক্ত হয়, যা একাধিক ব্রোকার বা মালিকের আলাদা প্রাইস ট্র্যাক করতে সাহায্য করে।
                    </p>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">৩. ০.৫% প্ল্যাটফর্ম ফি ও রেভিনিউ ইঞ্জিন</h4>
                    <p>
                        যেকোনো সফল লেনদেনে ক্রেতা ও বিক্রেতার সম্মতিক্রমে মোট মূল্যের উপর ০.৫% প্ল্যাটফর্ম ফি প্রযোজ্য হয়। ডিল ক্লোজ হওয়ার পর তা সরাসরি ডাটাবেজের ট্রানজ্যাকশন টেবিলে সংরক্ষিত হয় এবং হিস্টোরিক্যাল আর্কাইভে যোগ হয়।
                    </p>
                </div>
            </div>
        </div>

        <!-- Cron Setup for Hosttier cPanel -->
        <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>⏰</span> Hosttier cPanel ক্রন জব কনফিগারেশন
            </h3>
            <p class="text-slate-600">
                শেয়ার্ড হোস্টিংয়ে প্রতিদিন স্বয়ংক্রিয়ভাবে ক্রেতাদের চাহিদার সাথে নতুন প্রপার্টি ম্যাচ করাতে এবং নোটিফিকেশন পাঠাতে নিচের ক্রন জবটি cPanel &rarr; Cron Jobs-এ যোগ করুন:
            </p>
            <div class="bg-slate-900 text-emerald-400 p-4 rounded-xl font-mono text-[11px] overflow-x-auto">
                0 9 * * * curl -s "{{ url('/api/cron/match-demands?key=propertykoi_cron_secret_2026') }}" > /dev/null 2>&1
            </div>
            <p class="text-[11px] text-slate-500">
                প্রতিদিন সকাল ৯:০০ টায় এই ক্রন রান করবে এবং নতুন উপযুক্ত প্রপার্টি পেলে ক্রেতাদের ইমেইল ও টেলিগ্রামে অ্যালার্ট পাঠাবে।
            </p>
        </div>
    </div>

</div>
@endsection
