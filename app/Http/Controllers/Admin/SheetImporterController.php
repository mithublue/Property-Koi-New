<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentNote;
use App\Models\AgentProfile;
use App\Models\BuyerDemand;
use App\Models\Property;
use App\Models\PropertyDirectSource;
use App\Models\PropertyLandDetail;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyShareDetail;
use App\Models\SheetImportPreset;
use App\Models\User;
use App\Services\GoogleSheetSyncService;
use App\Services\MagicLoginService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SheetImporterController extends Controller
{
    /**
     * Display the Google Sheet Importer tool with saved presets across tabs.
     */
    public function index(Request $request): View
    {
        // 1. Seed Property standard preset if missing
        if (SheetImportPreset::where('entity_type', 'property')->count() === 0) {
            SheetImportPreset::create([
                'entity_type'      => 'property',
                'name'             => 'প্লট স্ট্যান্ডার্ড শিট (Plots Standard)',
                'description'      => 'কলামসমূহ: place, block, sector, road, plot number, katha, agent, contact, messenger, price, extra, time line',
                'sample_sheet_url' => 'https://docs.google.com/spreadsheets/d/1XWYmjcrBCIwIpBSnpCzvkH7sdtVPeIOSP9XoxmvH6r8/edit?gid=0#gid=0',
                'field_mappings'   => [
                    'place'       => 'area',
                    'block'       => 'block',
                    'sector'      => 'sector',
                    'road'        => 'road_number',
                    'plot number' => 'plot_number',
                    'katha'       => 'size',
                    'agent'       => 'owner_name',
                    'contact'     => 'owner_phone',
                    'messenger'   => 'custom_field:মেসেঞ্জার লিংক',
                    'price'       => 'asking_price',
                    'extra'       => 'parse_extra',
                    'time line'   => 'custom_field:হস্তান্তর সময়সীমা',
                ],
                'options'          => [
                    'destination'    => 'draft',
                    'duplicate_mode' => 'skip',
                    'default_type'   => 'land',
                    'default_area'   => 'Aftabnagar',
                ],
            ]);
        }

        // 2. Seed Agent & Owner standard preset if missing
        if (SheetImportPreset::where('entity_type', 'agent')->count() === 0) {
            SheetImportPreset::create([
                'entity_type'      => 'agent',
                'name'             => 'এজেন্ট ও মালিক স্ট্যান্ডার্ড শিট (Agents Standard)',
                'description'      => 'কলামসমূহ: name, phone, whatsapp, role, agency, rating, notes',
                'sample_sheet_url' => 'https://docs.google.com/spreadsheets/d/1XWYmjcrBCIwIpBSnpCzvkH7sdtVPeIOSP9XoxmvH6r8/edit?gid=0#gid=0',
                'field_mappings'   => [
                    'name'     => 'name',
                    'phone'    => 'phone',
                    'whatsapp' => 'whatsapp_number',
                    'role'     => 'role',
                    'agency'   => 'agency_name',
                    'rating'   => 'rating',
                    'notes'    => 'notes',
                ],
                'options'          => [
                    'default_role'   => 'agent',
                    'is_verified'    => true,
                    'default_rating' => 5.0,
                    'duplicate_mode' => 'skip',
                ],
            ]);
        }

        // 3. Seed Buyer standard preset if missing
        if (SheetImportPreset::where('entity_type', 'buyer')->count() === 0) {
            SheetImportPreset::create([
                'entity_type'      => 'buyer',
                'name'             => 'বায়ার ও চাহিদা স্ট্যান্ডার্ড শিট (Buyers Standard)',
                'description'      => 'কলামসমূহ: name, phone, whatsapp, property_type, area, blocks, budget, timeline, demand_notes',
                'sample_sheet_url' => 'https://docs.google.com/spreadsheets/d/1XWYmjcrBCIwIpBSnpCzvkH7sdtVPeIOSP9XoxmvH6r8/edit?gid=0#gid=0',
                'field_mappings'   => [
                    'name'          => 'name',
                    'phone'         => 'phone',
                    'whatsapp'      => 'whatsapp_number',
                    'property_type' => 'preferred_type',
                    'area'          => 'preferred_areas',
                    'blocks'        => 'preferred_blocks',
                    'budget'        => 'budget_range',
                    'timeline'      => 'purchase_timeline',
                    'demand_notes'  => 'notes',
                ],
                'options'          => [
                    'default_type'       => 'land',
                    'default_area'       => 'Aftabnagar',
                    'auto_create_demand' => true,
                    'duplicate_mode'     => 'skip',
                ],
            ]);
        }

        $presets = SheetImportPreset::latest()->get();

        $propertyTargetFields = self::getPropertyTargetFields();
        $agentTargetFields    = self::getAgentTargetFields();
        $buyerTargetFields    = self::getBuyerTargetFields();

        $activeTab = $request->query('tab', 'property');
        if (!in_array($activeTab, ['property', 'agent', 'buyer'])) {
            $activeTab = 'property';
        }

        return view('admin.sheet_importer.index', compact(
            'presets',
            'propertyTargetFields',
            'agentTargetFields',
            'buyerTargetFields',
            'activeTab'
        ));
    }

    /**
     * Target fields available for mapping Properties.
     */
    public static function getPropertyTargetFields(): array
    {
        return [
            'প্রপার্টির মৌলিক তথ্য' => [
                'title'           => 'প্রপার্টি শিরোনাম (Title - ফাঁকা থাকলে অটো-জেনারেট হবে)',
                'area'            => 'এলাকা / এরিয়া (Area - যেমন: Aftabnagar, Bashundhara)',
                'sub_area'        => 'সাব-এরিয়া / ফেজ (Sub Area / Phase)',
                'address'         => 'পূর্ণ ঠিকানা (Address)',
                'property_type'   => 'প্রপার্টি ধরণ (Property Type: land/flat/commercial)',
                'purpose'         => 'উদ্দেশ্য (Purpose: sale / rent)',
                'asking_price'    => 'মূল্য / দাম (Price - 95 lac, 1.25 cr ইত্যাদি স্মার্ট পার্সিং)',
                'price_per_unit'  => 'প্রতি ইউনিট মূল্য (Price Per Unit)',
                'is_negotiable'   => 'দাম আলোচনা সাপেক্ষ (Negotiable: yes/no)',
                'description'     => 'বিস্তারিত বিবরণ (Description)',
            ],
            'প্লট ও ফ্ল্যাটের স্পেসিফিকেশন' => [
                'size'            => 'জমির পরিমাণ / সাইজ (Katha / Sqft - গাণিতিক 2.5+2.5 সমাধান সহ)',
                'size_unit'       => 'সাইজ ইউনিট (Unit: katha, bigha, sqft)',
                'block'           => 'ব্লক (Block)',
                'sector'          => 'সেক্টর (Sector)',
                'road_number'     => 'রোড নম্বর / নাম (Road)',
                'plot_number'     => 'প্লট নম্বর (Plot Number)',
                'facing'          => 'দিক / ফেসিং (Facing: North, South, East, West ইত্যাদি)',
                'front_road_width'=> 'সামনের রাস্তার প্রশস্ততা (Road Width in Ft)',
                'bedrooms'        => 'বেডরুম সংখ্যা (Bedrooms - ফ্ল্যাটের ক্ষেত্রে)',
                'bathrooms'       => 'বাথরুম সংখ্যা (Bathrooms)',
                'balconies'       => 'বারান্দা সংখ্যা (Balconies)',
                'floor_level'     => 'ফ্লোর নম্বর (Floor Level)',
            ],
            'মালিক / এজেন্ট ও সোর্স' => [
                'owner_name'      => 'এজেন্ট / মালিকের নাম (Agent / Owner Name)',
                'owner_phone'     => 'কন্টাক্ট / ফোন নম্বর (Phone / Mobile)',
                'source_name'     => 'উৎস / সোর্সের নাম (Source Name)',
                'source_type'     => 'সোর্স ক্যাটাগরি (Source Type: agent/owner/broker)',
                'commission_terms'=> 'কমিশন বা শর্ত (Commission Terms)',
            ],
            'স্মার্ট পার্সার ও কাস্টম ফিল্ড' => [
                'parse_extra'     => '🧠 Extra ফিল্ড অটো-পার্সার (মৌজা, CS/RS/SA দাগ, বিঘা, হস্তান্তর ইত্যাদি স্বয়ংক্রিয়ভাবে কাস্টম ফিল্ডে রূপান্তর)',
                'custom_field'    => '🏷️ কাস্টম ফিল্ড (Custom Key-Value Pair - যেমন: মৌজা, দাগ, মেসেঞ্জার লিংক, হস্তান্তর সময়)',
            ],
            'অন্যান্য' => [
                '_skip'           => '✕ এই কলামটি বাদ দিন (Ignore Column)',
            ],
        ];
    }

    /**
     * Backward-compatibility alias for getPropertyTargetFields.
     */
    public static function getTargetFields(): array
    {
        return self::getPropertyTargetFields();
    }

    /**
     * Target fields available for mapping Agents & Owners.
     */
    public static function getAgentTargetFields(): array
    {
        return [
            'এজেন্ট/মালিকের মূল তথ্য' => [
                'name'            => 'নাম (Name) *',
                'phone'           => 'মোবাইল / ফোন নম্বর (Phone - Unique Identifier) *',
                'whatsapp_number' => 'হোয়াটসঅ্যাপ নম্বর (WhatsApp - ফাঁকা থাকলে ফোন নম্বর)',
                'email'           => 'ইমেইল অ্যাড্রেস (Email - ফাঁকা থাকলে অটো-জেনারেট)',
                'role'            => 'পদবী / রোল (Role: agent / owner)',
            ],
            'এজেন্সি ও প্রোফাইল সেটিংস' => [
                'agency_name'     => 'এজেন্সি / প্রতিষ্ঠানের নাম (Agency Name)',
                'rating'          => 'অ্যাডমিন ফিক্সড রেটিং (Rating: 1.0 - 5.0)',
                'is_verified'     => 'ভেরিফাইড স্ট্যাটাস (Verified: yes/no, 1/0)',
                'license_no'      => 'ট্রেড লাইসেন্স নম্বর (License No)',
                'experience'      => 'অভিজ্ঞতা (Experience in Years)',
            ],
            'মন্তব্য ও কাস্টম ফিল্ড' => [
                'notes'           => 'অ্যাডমিন নোট বা মন্তব্য (Internal Notes)',
                'custom_field'    => '🏷️ কাস্টম ফিল্ড (Custom Key-Value)',
            ],
            'অন্যান্য' => [
                '_skip'           => '✕ এই কলামটি বাদ দিন (Ignore Column)',
            ],
        ];
    }

    /**
     * Target fields available for mapping Buyers.
     */
    public static function getBuyerTargetFields(): array
    {
        return [
            'বায়ারের পরিচিতি' => [
                'name'             => 'বায়ারের নাম (Buyer Name) *',
                'phone'            => 'মোবাইল নম্বর (Phone - Unique Identifier) *',
                'whatsapp_number'  => 'হোয়াটসঅ্যাপ নম্বর (WhatsApp)',
                'email'            => 'ইমেইল (Email)',
            ],
            'চাহিদা ও পছন্দসমূহ (Preferences)' => [
                'preferred_type'   => 'প্রপার্টি ধরণ (Type: land/flat/commercial)',
                'preferred_areas'  => 'পছন্দের এলাকা (Preferred Areas - যেমন: Aftabnagar)',
                'preferred_blocks' => 'পছন্দের ব্লক (Blocks - যেমন: Block C, D)',
                'budget_range'     => 'বাজেট রেঞ্জ (যেমন: ১ - ১.৫ কোটি)',
                'min_budget'       => 'সর্বনিম্ন বাজেট (Min Budget - সংখ্যা)',
                'max_budget'       => 'সর্বোচ্চ বাজেট (Max Budget - সংখ্যা)',
                'purchase_timeline'=> 'ক্রয়ের সময়সীমা (Timeline - যেমন: জরুরি, ৩ মাস)',
            ],
            'চাহিদা নোট ও কাস্টম ফিল্ড' => [
                'notes'            => 'বিস্তারিত চাহিদা / রিকোয়ারমেন্টস (Requirement Notes)',
                'custom_field'     => '🏷️ কাস্টম ফিল্ড (Custom Key-Value)',
            ],
            'অন্যান্য' => [
                '_skip'            => '✕ এই কলামটি বাদ দিন (Ignore Column)',
            ],
        ];
    }

    /**
     * Fetch sheet first row (headers) and first few data rows for preview.
     */
    public function fetchHeaders(Request $request)
    {
        $sheetUrl   = trim((string) $request->input('sheet_url'));
        $entityType = trim((string) $request->input('entity_type', 'property'));

        if (empty($sheetUrl)) {
            return response()->json(['success' => false, 'error' => 'অনুগ্রহ করে গুগল শিটের লিংক প্রদান করুন।']);
        }

        $fetched = GoogleSheetSyncService::fetchCsvContent($sheetUrl);
        if (!$fetched['success']) {
            return response()->json(['success' => false, 'error' => $fetched['error']]);
        }

        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $fetched['content']);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers || empty($headers)) {
            fclose($stream);
            return response()->json(['success' => false, 'error' => 'শিটটিতে কোনো হেডার পাওয়া যায়নি।']);
        }

        $cleanHeaders = [];
        foreach ($headers as $h) {
            $val = trim((string) $h);
            if ($val !== '') {
                $cleanHeaders[] = $val;
            }
        }

        $previewRows = [];
        $totalCount = 0;
        while (($row = fgetcsv($stream)) !== false) {
            $totalCount++;
            if (count($previewRows) < 4) {
                $rowAssoc = [];
                foreach ($cleanHeaders as $idx => $h) {
                    $rowAssoc[$h] = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                }
                $previewRows[] = $rowAssoc;
            }
        }
        fclose($stream);

        // Smart auto-suggestions based on entity_type
        $suggested = [];
        foreach ($cleanHeaders as $h) {
            $suggested[$h] = self::guessTargetField($h, $entityType);
        }

        return response()->json([
            'success'            => true,
            'entity_type'        => $entityType,
            'headers'            => $cleanHeaders,
            'preview_rows'       => $previewRows,
            'total_rows_approx'  => $totalCount,
            'suggested_mappings' => $suggested,
            'csv_url'            => $fetched['csv_url'],
        ]);
    }

    /**
     * Guess the most probable target field from column header name and entity type.
     */
    public static function guessTargetField(string $header, string $entityType = 'property'): string
    {
        $h = mb_strtolower(trim($header), 'UTF-8');

        if ($entityType === 'agent') {
            $agentMap = [
                'name'      => 'name',
                'agent'     => 'name',
                'owner'     => 'name',
                'নাম'       => 'name',
                'phone'     => 'phone',
                'mobile'    => 'phone',
                'contact'   => 'phone',
                'ফোন'       => 'phone',
                'মোবাইল'    => 'phone',
                'নাম্বার'    => 'phone',
                'whatsapp'  => 'whatsapp_number',
                'wa'        => 'whatsapp_number',
                'হোয়াটসঅ্যাপ'=> 'whatsapp_number',
                'email'     => 'email',
                'mail'      => 'email',
                'ইমেইল'     => 'email',
                'role'      => 'role',
                'পদবী'      => 'role',
                'type'      => 'role',
                'agency'    => 'agency_name',
                'company'   => 'agency_name',
                'office'    => 'agency_name',
                'প্রতিষ্ঠান' => 'agency_name',
                'rating'    => 'rating',
                'star'      => 'rating',
                'রেটিং'     => 'rating',
                'license'   => 'license_no',
                'লাইসেন্স'  => 'license_no',
                'verified'  => 'is_verified',
                'ভেরিফাইড'  => 'is_verified',
                'note'      => 'notes',
                'notes'     => 'notes',
                'মন্তব্য'    => 'notes',
            ];
            if (isset($agentMap[$h])) return $agentMap[$h];
            foreach ($agentMap as $key => $target) {
                if (str_contains($h, $key)) return $target;
            }
            return '_skip';
        }

        if ($entityType === 'buyer') {
            $buyerMap = [
                'name'             => 'name',
                'buyer'            => 'name',
                'নাম'              => 'name',
                'ক্রেতা'           => 'name',
                'ক্লায়েন্ট'       => 'name',
                'phone'            => 'phone',
                'mobile'           => 'phone',
                'contact'          => 'phone',
                'ফোন'              => 'phone',
                'মোবাইল'           => 'phone',
                'whatsapp'         => 'whatsapp_number',
                'wa'               => 'whatsapp_number',
                'email'            => 'email',
                'mail'             => 'email',
                'type'             => 'preferred_type',
                'property_type'    => 'preferred_type',
                'ধরণ'              => 'preferred_type',
                'area'             => 'preferred_areas',
                'location'         => 'preferred_areas',
                'এলাকা'            => 'preferred_areas',
                'block'            => 'preferred_blocks',
                'ব্লক'              => 'preferred_blocks',
                'budget'           => 'budget_range',
                'price'            => 'budget_range',
                'বাজেট'            => 'budget_range',
                'timeline'         => 'purchase_timeline',
                'সময়সীমা'          => 'purchase_timeline',
                'note'             => 'notes',
                'notes'            => 'notes',
                'demand'           => 'notes',
                'চাহিদা'           => 'notes',
                'মন্তব্য'           => 'notes',
            ];
            if (isset($buyerMap[$h])) return $buyerMap[$h];
            foreach ($buyerMap as $key => $target) {
                if (str_contains($h, $key)) return $target;
            }
            return '_skip';
        }

        // Default: Property mappings
        $map = [
            'place'       => 'area',
            'area'        => 'area',
            'এলাকা'       => 'area',
            'location'    => 'area',
            'block'       => 'block',
            'ব্লক'         => 'block',
            'sector'      => 'sector',
            'সেক্টর'      => 'sector',
            'road'        => 'road_number',
            'road number' => 'road_number',
            'রাস্তা'      => 'road_number',
            'plot'        => 'plot_number',
            'plot number' => 'plot_number',
            'প্লট'         => 'plot_number',
            'katha'       => 'size',
            'কাঠা'        => 'size',
            'size'        => 'size',
            'agent'       => 'owner_name',
            'owner'       => 'owner_name',
            'contact'     => 'owner_phone',
            'phone'       => 'owner_phone',
            'mobile'      => 'owner_phone',
            'messenger'   => 'custom_field:মেসেঞ্জার লিংক',
            'price'       => 'asking_price',
            'extra'       => 'parse_extra',
            'details'     => 'parse_extra',
            'time line'   => 'custom_field:হস্তান্তর সময়সীমা',
            'timeline'    => 'custom_field:হস্তান্তর সময়সীমা',
            'facing'      => 'facing',
            'title'       => 'title',
            'type'        => 'property_type',
        ];

        if (isset($map[$h])) {
            return $map[$h];
        }

        foreach ($map as $key => $target) {
            if (str_contains($h, $key)) {
                return $target;
            }
        }

        return '_skip';
    }

    /**
     * Save a new or existing mapping preset.
     */
    public function storePreset(Request $request)
    {
        $validated = $request->validate([
            'entity_type'      => 'nullable|string|in:property,agent,buyer',
            'preset_id'        => 'nullable|exists:sheet_import_presets,id',
            'name'             => 'required|string|max:191',
            'description'      => 'nullable|string',
            'sample_sheet_url' => 'nullable|string',
            'field_mappings'   => 'required|array',
            'options'          => 'nullable|array',
        ]);

        $entityType = $validated['entity_type'] ?? 'property';

        if (!empty($validated['preset_id'])) {
            $preset = SheetImportPreset::findOrFail($validated['preset_id']);
            $preset->update([
                'entity_type'      => $entityType,
                'name'             => $validated['name'],
                'description'      => $validated['description'] ?? null,
                'sample_sheet_url' => $validated['sample_sheet_url'] ?? null,
                'field_mappings'   => $validated['field_mappings'],
                'options'          => $validated['options'] ?? null,
            ]);
            $msg = "প্রিসেট '{$preset->name}' সফলভাবে আপডেট করা হয়েছে।";
        } else {
            $preset = SheetImportPreset::create([
                'entity_type'        => $entityType,
                'name'               => $validated['name'],
                'description'        => $validated['description'] ?? null,
                'sample_sheet_url'   => $validated['sample_sheet_url'] ?? null,
                'field_mappings'     => $validated['field_mappings'],
                'options'            => $validated['options'] ?? null,
                'created_by_user_id' => auth()->id(),
            ]);
            $msg = "নতুন প্রিসেট '{$preset->name}' সফলভাবে সংরক্ষণ করা হয়েছে।";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'preset'  => $preset,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete a mapping preset.
     */
    public function deletePreset(SheetImportPreset $preset)
    {
        $name = $preset->name;
        $preset->delete();

        return response()->json([
            'success' => true,
            'message' => "প্রিসেট '{$name}' মুছে ফেলা হয়েছে।",
        ]);
    }

    /**
     * Master import endpoint - routes to property, agent, or buyer importer.
     */
    public function import(Request $request)
    {
        $entityType = $request->input('entity_type', 'property');

        if ($entityType === 'agent') {
            return $this->importAgents($request);
        } elseif ($entityType === 'buyer') {
            return $this->importBuyers($request);
        }

        return $this->importProperties($request);
    }

    /**
     * Execute Property Import.
     */
    protected function importProperties(Request $request)
    {
        $request->validate([
            'sheet_url'      => 'required|string',
            'mappings'       => 'required|array',
            'destination'    => 'required|in:draft,live',
            'duplicate_mode' => 'required|in:skip,update,all',
            'default_type'   => 'nullable|in:land,flat,commercial,land_share',
            'default_area'   => 'nullable|string',
        ]);

        $sheetUrl      = trim($request->input('sheet_url'));
        $mappings      = $request->input('mappings');
        $destination   = $request->input('destination', 'draft');
        $duplicateMode = $request->input('duplicate_mode', 'skip');
        $defaultType   = $request->input('default_type', 'land');
        $defaultArea   = trim((string)$request->input('default_area', 'Aftabnagar')) ?: 'Aftabnagar';

        $fetched = GoogleSheetSyncService::fetchCsvContent($sheetUrl);
        if (!$fetched['success']) {
            return response()->json(['success' => false, 'error' => $fetched['error']]);
        }

        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $fetched['content']);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers || empty($headers)) {
            fclose($stream);
            return response()->json(['success' => false, 'error' => 'শিটের কোনো কলাম খুঁজে পাওয়া যায়নি।']);
        }

        $colIndexToTarget = [];
        foreach ($headers as $idx => $h) {
            $colName = trim((string)$h);
            if (isset($mappings[$colName]) && $mappings[$colName] !== '_skip') {
                $colIndexToTarget[$idx] = [
                    'col_name' => $colName,
                    'target'   => $mappings[$colName],
                ];
            }
        }

        $totalRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        while (($row = fgetcsv($stream)) !== false) {
            $rowStr = implode('', array_map('trim', $row));
            if ($rowStr === '') {
                continue;
            }

            $totalRows++;

            try {
                $data = [
                    'area'          => $defaultArea,
                    'sub_area'      => '',
                    'block'         => '',
                    'sector'        => '',
                    'road_number'   => '',
                    'road_width_ft' => 20,
                    'plot_number'   => '',
                    'holding_number'=> '',
                    'facing'        => 'south',
                    'size'          => 0.0,
                    'asking_price'  => 0.0,
                    'price_per_katha'=> 0.0,
                    'property_type' => $defaultType,
                    'title'         => '',
                    'description'   => '',
                    'owner_name'    => '',
                    'owner_phone'   => '',
                    'source_type'   => 'direct_owner',
                    'custom_fields' => [],
                    'raw_extra'     => '',
                ];

                foreach ($colIndexToTarget as $idx => $mappingInfo) {
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    if ($rawVal === '') continue;

                    $target = $mappingInfo['target'];

                    if ($target === 'area') {
                        $data['area'] = $rawVal;
                    } elseif ($target === 'sub_area') {
                        $data['sub_area'] = $rawVal;
                    } elseif ($target === 'block') {
                        $data['block'] = strtoupper(trim(preg_replace('/^(?:block|ব্লক)\s*/iu', '', $rawVal)));
                    } elseif ($target === 'sector') {
                        $data['sector'] = $rawVal;
                    } elseif ($target === 'road_number') {
                        $data['road_number'] = GoogleSheetSyncService::toEnNumber($rawVal);
                    } elseif ($target === 'road_width_ft') {
                        $data['road_width_ft'] = (int) GoogleSheetSyncService::toEnNumber($rawVal) ?: 20;
                    } elseif ($target === 'plot_number') {
                        $data['plot_number'] = trim(preg_replace('/^(?:plot|প্লট|নং|#)\s*/iu', '', GoogleSheetSyncService::toEnNumber($rawVal)));
                    } elseif ($target === 'holding_number') {
                        $data['holding_number'] = $rawVal;
                    } elseif ($target === 'facing') {
                        $data['facing'] = self::normalizeFacing($rawVal);
                    } elseif ($target === 'size') {
                        $data['size'] = self::parseSize($rawVal);
                        if ((string)$data['size'] !== $rawVal) {
                            $data['custom_fields'][] = ['key' => 'জমির বিবরণ (Raw Size)', 'value' => $rawVal];
                        }
                    } elseif ($target === 'asking_price') {
                        $data['asking_price'] = self::parsePrice($rawVal);
                        $data['custom_fields'][] = ['key' => 'মূল্য টেক্সট (Raw Price)', 'value' => $rawVal];
                    } elseif ($target === 'price_per_katha') {
                        $data['price_per_katha'] = (float) GoogleSheetSyncService::toEnNumber(preg_replace('/[^0-9.]/', '', $rawVal));
                    } elseif ($target === 'property_type') {
                        $data['property_type'] = self::normalizeType($rawVal, $defaultType);
                    } elseif ($target === 'title') {
                        $data['title'] = $rawVal;
                    } elseif ($target === 'description') {
                        $data['description'] = $rawVal;
                    } elseif ($target === 'owner_name') {
                        $data['owner_name'] = $rawVal;
                    } elseif ($target === 'owner_phone') {
                        $data['owner_phone'] = trim(preg_replace('/[^0-9+,\/ \-]/', '', GoogleSheetSyncService::toEnNumber($rawVal)));
                    } elseif ($target === 'messenger') {
                        $data['custom_fields'][] = ['key' => 'ফেসবুক / মেসেঞ্জার লিংক', 'value' => $rawVal];
                    } elseif ($target === 'source_type') {
                        $data['source_type'] = str_contains(mb_strtolower($rawVal, 'UTF-8'), 'agent') ? 'direct_agent' : 'direct_owner';
                    } elseif ($target === 'parse_extra') {
                        $data['raw_extra'] = $rawVal;
                        self::parseExtraIntoCustomFields($rawVal, $data);
                    } elseif (str_starts_with($target, 'custom_field:') || $target === 'custom_field') {
                        $customKey = str_starts_with($target, 'custom_field:') ? trim(substr($target, strlen('custom_field:'))) : $mappingInfo['col_name'];
                        if ($customKey !== '') {
                            $data['custom_fields'][] = ['key' => $customKey, 'value' => $rawVal];
                        }
                    }
                }

                if (empty($data['block']) && !empty($data['sector'])) {
                    if (preg_match('/^([a-zA-Z0-9]+)\s*[,/]\s*(.*)$/i', $data['sector'], $bm)) {
                        $data['block'] = strtoupper(trim($bm[1]));
                        $data['sector'] = trim($bm[2]);
                    }
                }

                if (empty($data['title'])) {
                    $parts = [];
                    if (!empty($data['area'])) $parts[] = $data['area'];
                    if (!empty($data['block'])) $parts[] = 'ব্লক ' . $data['block'];
                    if (!empty($data['road_number'])) $parts[] = 'রোড ' . $data['road_number'];
                    if (!empty($data['size']) && $data['size'] > 0) $parts[] = $data['size'] . ' কাঠা প্লট';
                    else $parts[] = 'আকর্ষণীয় প্রপার্টি';
                    $data['title'] = implode(' - ', $parts);
                }

                $existingProperty = null;
                if (!empty($data['plot_number']) || !empty($data['road_number'])) {
                    $eq = Property::query();
                    if (!empty($data['area'])) $eq->where('area', $data['area']);
                    if (!empty($data['block'])) $eq->where('block', $data['block']);
                    if (!empty($data['road_number'])) $eq->where('road_number', $data['road_number']);
                    if (!empty($data['plot_number'])) $eq->where('plot_number', $data['plot_number']);
                    $existingProperty = $eq->first();
                }

                if ($existingProperty && $duplicateMode === 'skip') {
                    $skippedCount++;
                    continue;
                }

                if ($existingProperty && $duplicateMode === 'update') {
                    $mergedCustomFields = array_merge($existingProperty->custom_fields ?? [], $data['custom_fields']);
                    $existingProperty->update([
                        'title'          => $data['title'],
                        'area'           => $data['area'],
                        'sub_area'       => $data['sub_area'] ?: ($data['sector'] ?: $existingProperty->sub_area),
                        'block'          => $data['block'] ?: $existingProperty->block,
                        'sector'         => $data['sector'] ?: $existingProperty->sector,
                        'road_number'    => $data['road_number'] ?: $existingProperty->road_number,
                        'road_width_ft'  => $data['road_width_ft'] ?: $existingProperty->road_width_ft,
                        'plot_number'    => $data['plot_number'] ?: $existingProperty->plot_number,
                        'holding_number' => $data['holding_number'] ?: $existingProperty->holding_number,
                        'facing'         => $data['facing'] ?: $existingProperty->facing,
                        'description'    => $data['description'] ?: ($data['raw_extra'] ?: $existingProperty->description),
                        'status'         => $destination,
                        'custom_fields'  => $mergedCustomFields,
                    ]);

                    self::updateSubDetails($existingProperty, $data);
                    self::updateDirectSource($existingProperty, $data);

                    $updatedCount++;
                } else {
                    $code = 'PK-' . strtoupper(Str::random(6));
                    $slug = Str::slug($data['title']) . '-' . strtolower(Str::random(5));

                    $property = Property::create([
                        'property_code'      => $code,
                        'type'               => $data['property_type'],
                        'title'              => $data['title'],
                        'slug'               => $slug,
                        'area'               => $data['area'],
                        'sub_area'           => $data['sub_area'] ?: $data['sector'],
                        'block'              => $data['block'],
                        'sector'             => $data['sector'],
                        'road_width_ft'      => $data['road_width_ft'],
                        'road_number'        => $data['road_number'],
                        'plot_number'        => $data['plot_number'],
                        'holding_number'     => $data['holding_number'],
                        'address_visibility' => ['block' => true, 'sector' => true, 'road_number' => true, 'road_width_ft' => true, 'plot_number' => true, 'holding_number' => true],
                        'facing'             => $data['facing'],
                        'description'        => $data['description'] ?: $data['raw_extra'],
                        'status'             => $destination,
                        'custom_fields'      => $data['custom_fields'],
                        'created_by_user_id' => auth()->id(),
                    ]);

                    self::updateSubDetails($property, $data);
                    self::updateDirectSource($property, $data);

                    if ($property->status === 'live') {
                        SeoService::pingIndexNow($property);
                    }

                    $createdCount++;
                }

            } catch (\Throwable $e) {
                $errors[] = "সারি #{$totalRows}: " . $e->getMessage();
            }
        }

        fclose($stream);

        $msg = "প্রপার্টি ইমপোর্ট সম্পন্ন: মোট {$totalRows} টি সারির মধ্যে {$createdCount} টি নতুন যুক্ত হয়েছে, {$updatedCount} টি আপডেট হয়েছে, এবং {$skippedCount} টি স্কিপ হয়েছে।";

        return response()->json([
            'success'    => true,
            'message'    => $msg,
            'processed'  => $totalRows,
            'created'    => $createdCount,
            'updated'    => $updatedCount,
            'skipped'    => $skippedCount,
            'errors'     => $errors,
            'report'     => [
                'total_rows' => $totalRows,
                'created'    => $createdCount,
                'updated'    => $updatedCount,
                'skipped'    => $skippedCount,
                'errors'     => $errors,
            ],
        ]);
    }

    /**
     * Execute Agent & Owner Import.
     */
    protected function importAgents(Request $request)
    {
        $request->validate([
            'sheet_url'      => 'required|string',
            'mappings'       => 'required|array',
            'duplicate_mode' => 'required|in:skip,update,all',
            'default_role'   => 'nullable|in:agent,owner',
            'default_rating' => 'nullable|numeric|min:1|max:5',
            'is_verified'    => 'nullable|boolean',
        ]);

        $sheetUrl      = trim($request->input('sheet_url'));
        $mappings      = $request->input('mappings');
        $duplicateMode = $request->input('duplicate_mode', 'skip');
        $defaultRole   = $request->input('default_role', 'agent');
        $defaultRating = (float) $request->input('default_rating', 5.0) ?: 5.0;
        $isVerifiedDef = $request->boolean('is_verified', true);

        $fetched = GoogleSheetSyncService::fetchCsvContent($sheetUrl);
        if (!$fetched['success']) {
            return response()->json(['success' => false, 'error' => $fetched['error']]);
        }

        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $fetched['content']);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers || empty($headers)) {
            fclose($stream);
            return response()->json(['success' => false, 'error' => 'শিটের কোনো কলাম খুঁজে পাওয়া যায়নি।']);
        }

        $colIndexToTarget = [];
        foreach ($headers as $idx => $h) {
            $colName = trim((string)$h);
            if (isset($mappings[$colName]) && $mappings[$colName] !== '_skip') {
                $colIndexToTarget[$idx] = [
                    'col_name' => $colName,
                    'target'   => $mappings[$colName],
                ];
            }
        }

        $totalRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        while (($row = fgetcsv($stream)) !== false) {
            $rowStr = implode('', array_map('trim', $row));
            if ($rowStr === '') continue;

            $totalRows++;

            try {
                $agentData = [
                    'name'            => '',
                    'phone'           => '',
                    'whatsapp_number' => '',
                    'email'           => '',
                    'role'            => $defaultRole,
                    'agency_name'     => '',
                    'rating'          => $defaultRating,
                    'is_verified'     => $isVerifiedDef,
                    'license_no'      => '',
                    'notes'           => '',
                ];

                foreach ($colIndexToTarget as $idx => $mappingInfo) {
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    if ($rawVal === '') continue;

                    $target = $mappingInfo['target'];

                    if ($target === 'name') {
                        $agentData['name'] = $rawVal;
                    } elseif ($target === 'phone') {
                        $agentData['phone'] = GoogleSheetSyncService::toEnNumber(preg_replace('/[^0-9+]/', '', $rawVal));
                    } elseif ($target === 'whatsapp_number') {
                        $agentData['whatsapp_number'] = GoogleSheetSyncService::toEnNumber(preg_replace('/[^0-9+]/', '', $rawVal));
                    } elseif ($target === 'email') {
                        $agentData['email'] = strtolower($rawVal);
                    } elseif ($target === 'role') {
                        $r = strtolower($rawVal);
                        $agentData['role'] = (str_contains($r, 'owner') || str_contains($r, 'মালিক')) ? 'owner' : 'agent';
                    } elseif ($target === 'agency_name') {
                        $agentData['agency_name'] = $rawVal;
                    } elseif ($target === 'rating') {
                        $agentData['rating'] = (float) GoogleSheetSyncService::toEnNumber($rawVal) ?: $defaultRating;
                    } elseif ($target === 'is_verified') {
                        $v = strtolower($rawVal);
                        $agentData['is_verified'] = in_array($v, ['1', 'true', 'yes', 'verified', 'হ্যাঁ']);
                    } elseif ($target === 'license_no') {
                        $agentData['license_no'] = $rawVal;
                    } elseif ($target === 'notes') {
                        $agentData['notes'] = $rawVal;
                    } elseif (str_starts_with($target, 'custom_field:') || $target === 'custom_field') {
                        $cKey = str_starts_with($target, 'custom_field:') ? trim(substr($target, strlen('custom_field:'))) : $mappingInfo['col_name'];
                        $agentData['notes'] .= ($agentData['notes'] ? "\n" : '') . "{$cKey}: {$rawVal}";
                    }
                }

                if (empty($agentData['phone'])) {
                    $errors[] = "সারি #{$totalRows}: ফোন নম্বর খালি থাকায় বাদ দেওয়া হয়েছে।";
                    continue;
                }

                if (empty($agentData['name'])) {
                    $agentData['name'] = ($agentData['role'] === 'agent' ? 'এজেন্ট ' : 'মালিক ') . substr($agentData['phone'], -4);
                }

                if (empty($agentData['whatsapp_number'])) {
                    $agentData['whatsapp_number'] = $agentData['phone'];
                }

                // Check duplicate by phone or email
                $existing = User::where('phone', $agentData['phone'])->first();
                if (!$existing && !empty($agentData['email'])) {
                    $existing = User::where('email', $agentData['email'])->first();
                }

                if ($existing && $duplicateMode === 'skip') {
                    $skippedCount++;
                    continue;
                }

                if ($existing && $duplicateMode === 'update') {
                    $existing->update([
                        'name'            => $agentData['name'] ?: $existing->name,
                        'whatsapp_number' => $agentData['whatsapp_number'] ?: $existing->whatsapp_number,
                        'role'            => $agentData['role'] ?: $existing->role,
                    ]);

                    $profile = $existing->agentProfile ?: new AgentProfile(['user_id' => $existing->id]);
                    if (!empty($agentData['agency_name'])) $profile->agency_name = $agentData['agency_name'];
                    if (!empty($agentData['license_no'])) $profile->license_no = $agentData['license_no'];
                    if ($agentData['rating'] > 0) $profile->admin_fixed_rating = $agentData['rating'];
                    $profile->is_verified_direct_agent = $agentData['is_verified'];
                    $profile->save();

                    if (!empty($agentData['notes'])) {
                        AgentNote::create([
                            'user_id'        => $existing->id,
                            'author_user_id' => auth()->id(),
                            'author_name'    => auth()->user()?->name ?? 'Admin',
                            'note_text'      => $agentData['notes'],
                        ]);
                    }

                    $updatedCount++;
                } else {
                    // Create new Agent/Owner User
                    $email = $agentData['email'];
                    if (empty($email) || User::where('email', $email)->exists()) {
                        $email = $agentData['role'] . '_' . Str::random(6) . '@propertykoi.local';
                    }

                    $user = User::create([
                        'name'            => $agentData['name'],
                        'phone'           => $agentData['phone'],
                        'whatsapp_number' => $agentData['whatsapp_number'],
                        'email'           => $email,
                        'password'        => Hash::make(Str::random(16)),
                        'role'            => $agentData['role'],
                        'is_verified'     => true,
                    ]);

                    MagicLoginService::getOrCreateToken($user);

                    AgentProfile::create([
                        'user_id'                  => $user->id,
                        'agency_name'              => $agentData['agency_name'] ?: null,
                        'license_no'               => $agentData['license_no'] ?: null,
                        'admin_fixed_rating'       => $agentData['rating'] ?: $defaultRating,
                        'is_verified_direct_agent' => $agentData['is_verified'],
                        'verified_at'              => $agentData['is_verified'] ? now() : null,
                    ]);

                    if (!empty($agentData['notes'])) {
                        AgentNote::create([
                            'user_id'        => $user->id,
                            'author_user_id' => auth()->id(),
                            'author_name'    => auth()->user()?->name ?? 'Admin',
                            'note_text'      => $agentData['notes'],
                        ]);
                    }

                    $createdCount++;
                }

            } catch (\Throwable $e) {
                $errors[] = "সারি #{$totalRows}: " . $e->getMessage();
            }
        }

        fclose($stream);

        $msg = "এজেন্ট ও মালিক ইমপোর্ট সম্পন্ন: মোট {$totalRows} টি সারির মধ্যে {$createdCount} টি নতুন যুক্ত হয়েছে, {$updatedCount} টি আপডেট হয়েছে, এবং {$skippedCount} টি স্কিপ হয়েছে।";

        return response()->json([
            'success'    => true,
            'message'    => $msg,
            'processed'  => $totalRows,
            'created'    => $createdCount,
            'updated'    => $updatedCount,
            'skipped'    => $skippedCount,
            'errors'     => $errors,
            'report'     => [
                'total_rows' => $totalRows,
                'created'    => $createdCount,
                'updated'    => $updatedCount,
                'skipped'    => $skippedCount,
                'errors'     => $errors,
            ],
        ]);
    }

    /**
     * Execute Buyer Import.
     */
    protected function importBuyers(Request $request)
    {
        $request->validate([
            'sheet_url'          => 'required|string',
            'mappings'           => 'required|array',
            'duplicate_mode'     => 'required|in:skip,update,all',
            'default_type'       => 'nullable|string',
            'default_area'       => 'nullable|string',
            'auto_create_demand' => 'nullable|boolean',
        ]);

        $sheetUrl          = trim($request->input('sheet_url'));
        $mappings          = $request->input('mappings');
        $duplicateMode     = $request->input('duplicate_mode', 'skip');
        $defaultType       = $request->input('default_type', 'land');
        $defaultArea       = trim((string)$request->input('default_area', 'Aftabnagar')) ?: 'Aftabnagar';
        $autoCreateDemand  = $request->boolean('auto_create_demand', true);

        $fetched = GoogleSheetSyncService::fetchCsvContent($sheetUrl);
        if (!$fetched['success']) {
            return response()->json(['success' => false, 'error' => $fetched['error']]);
        }

        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $fetched['content']);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $headers = fgetcsv($stream);
        if (!$headers || empty($headers)) {
            fclose($stream);
            return response()->json(['success' => false, 'error' => 'শিটের কোনো কলাম খুঁজে পাওয়া যায়নি।']);
        }

        $colIndexToTarget = [];
        foreach ($headers as $idx => $h) {
            $colName = trim((string)$h);
            if (isset($mappings[$colName]) && $mappings[$colName] !== '_skip') {
                $colIndexToTarget[$idx] = [
                    'col_name' => $colName,
                    'target'   => $mappings[$colName],
                ];
            }
        }

        $totalRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        while (($row = fgetcsv($stream)) !== false) {
            $rowStr = implode('', array_map('trim', $row));
            if ($rowStr === '') continue;

            $totalRows++;

            try {
                $buyerData = [
                    'name'             => '',
                    'phone'            => '',
                    'whatsapp_number'  => '',
                    'email'            => '',
                    'preferred_type'   => $defaultType,
                    'preferred_areas'  => $defaultArea,
                    'preferred_blocks' => '',
                    'budget_range'     => '',
                    'min_budget'       => 0.0,
                    'max_budget'       => 0.0,
                    'purchase_timeline'=> '১ মাস',
                    'notes'            => '',
                ];

                foreach ($colIndexToTarget as $idx => $mappingInfo) {
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    if ($rawVal === '') continue;

                    $target = $mappingInfo['target'];

                    if ($target === 'name') {
                        $buyerData['name'] = $rawVal;
                    } elseif ($target === 'phone') {
                        $buyerData['phone'] = GoogleSheetSyncService::toEnNumber(preg_replace('/[^0-9+]/', '', $rawVal));
                    } elseif ($target === 'whatsapp_number') {
                        $buyerData['whatsapp_number'] = GoogleSheetSyncService::toEnNumber(preg_replace('/[^0-9+]/', '', $rawVal));
                    } elseif ($target === 'email') {
                        $buyerData['email'] = strtolower($rawVal);
                    } elseif ($target === 'preferred_type') {
                        $buyerData['preferred_type'] = self::normalizeType($rawVal, $defaultType);
                    } elseif ($target === 'preferred_areas') {
                        $buyerData['preferred_areas'] = $rawVal;
                    } elseif ($target === 'preferred_blocks') {
                        $buyerData['preferred_blocks'] = $rawVal;
                    } elseif ($target === 'budget_range') {
                        $buyerData['budget_range'] = $rawVal;
                    } elseif ($target === 'min_budget') {
                        $buyerData['min_budget'] = self::parsePrice($rawVal);
                    } elseif ($target === 'max_budget') {
                        $buyerData['max_budget'] = self::parsePrice($rawVal);
                    } elseif ($target === 'purchase_timeline') {
                        $buyerData['purchase_timeline'] = $rawVal;
                    } elseif ($target === 'notes') {
                        $buyerData['notes'] = $rawVal;
                    } elseif (str_starts_with($target, 'custom_field:') || $target === 'custom_field') {
                        $cKey = str_starts_with($target, 'custom_field:') ? trim(substr($target, strlen('custom_field:'))) : $mappingInfo['col_name'];
                        $buyerData['notes'] .= ($buyerData['notes'] ? "\n" : '') . "{$cKey}: {$rawVal}";
                    }
                }

                if (empty($buyerData['phone'])) {
                    $errors[] = "সারি #{$totalRows}: ফোন নম্বর খালি থাকায় বাদ দেওয়া হয়েছে।";
                    continue;
                }

                if (empty($buyerData['name'])) {
                    $buyerData['name'] = 'বায়ার ' . substr($buyerData['phone'], -4);
                }

                if (empty($buyerData['whatsapp_number'])) {
                    $buyerData['whatsapp_number'] = $buyerData['phone'];
                }

                // Check duplicate by phone or email
                $existing = User::where('phone', $buyerData['phone'])->first();
                if (!$existing && !empty($buyerData['email'])) {
                    $existing = User::where('email', $buyerData['email'])->first();
                }

                if ($existing && $duplicateMode === 'skip') {
                    $skippedCount++;
                    continue;
                }

                if ($existing && $duplicateMode === 'update') {
                    $existing->update([
                        'name'             => $buyerData['name'] ?: $existing->name,
                        'whatsapp_number'  => $buyerData['whatsapp_number'] ?: $existing->whatsapp_number,
                        'preferred_type'   => $buyerData['preferred_type'] ?: $existing->preferred_type,
                        'preferred_blocks' => $buyerData['preferred_blocks'] ?: $existing->preferred_blocks,
                        'budget_range'     => $buyerData['budget_range'] ?: $existing->budget_range,
                    ]);

                    if ($autoCreateDemand && (!empty($buyerData['notes']) || !empty($buyerData['preferred_blocks']))) {
                        BuyerDemand::create([
                            'user_id'           => $existing->id,
                            'buyer_name'        => $existing->name,
                            'buyer_phone'       => $existing->phone,
                            'buyer_email'       => $existing->email,
                            'property_type'     => $buyerData['preferred_type'] ?: 'land',
                            'preferred_areas'   => [$buyerData['preferred_areas'] ?: $defaultArea],
                            'preferred_blocks'  => $buyerData['preferred_blocks'] ? array_map('trim', explode(',', $buyerData['preferred_blocks'])) : [],
                            'min_budget'        => $buyerData['min_budget'] > 0 ? $buyerData['min_budget'] : null,
                            'max_budget'        => $buyerData['max_budget'] > 0 ? $buyerData['max_budget'] : null,
                            'purchase_timeline' => $buyerData['purchase_timeline'] ?: '১ মাস',
                            'notes'             => $buyerData['notes'] ?: 'শিট থেকে সংগৃহীত বায়ার চাহিদা।',
                            'status'            => 'active',
                            'notify_channels'   => ['sms', 'whatsapp'],
                        ]);
                    }

                    $updatedCount++;
                } else {
                    // Create new Buyer User
                    $email = $buyerData['email'];
                    if (empty($email) || User::where('email', $email)->exists()) {
                        $email = 'buyer_' . Str::random(6) . '@propertykoi.local';
                    }

                    $buyer = User::create([
                        'name'             => $buyerData['name'],
                        'phone'            => $buyerData['phone'],
                        'whatsapp_number'  => $buyerData['whatsapp_number'],
                        'email'            => $email,
                        'password'         => Hash::make(Str::random(16)),
                        'role'             => 'buyer',
                        'is_verified'      => true,
                        'preferred_type'   => $buyerData['preferred_type'] ?: $defaultType,
                        'preferred_blocks' => $buyerData['preferred_blocks'] ?: null,
                        'budget_range'     => $buyerData['budget_range'] ?: null,
                    ]);

                    MagicLoginService::getOrCreateToken($buyer);

                    if ($autoCreateDemand && (!empty($buyerData['notes']) || !empty($buyerData['preferred_blocks']) || !empty($buyerData['budget_range']))) {
                        BuyerDemand::create([
                            'user_id'           => $buyer->id,
                            'buyer_name'        => $buyer->name,
                            'buyer_phone'       => $buyer->phone,
                            'buyer_email'       => $buyer->email,
                            'property_type'     => $buyerData['preferred_type'] ?: $defaultType,
                            'preferred_areas'   => [$buyerData['preferred_areas'] ?: $defaultArea],
                            'preferred_blocks'  => $buyerData['preferred_blocks'] ? array_map('trim', explode(',', $buyerData['preferred_blocks'])) : [],
                            'min_budget'        => $buyerData['min_budget'] > 0 ? $buyerData['min_budget'] : null,
                            'max_budget'        => $buyerData['max_budget'] > 0 ? $buyerData['max_budget'] : null,
                            'purchase_timeline' => $buyerData['purchase_timeline'] ?: '১ মাস',
                            'notes'             => $buyerData['notes'] ?: 'শিট থেকে সংগৃহীত বায়ার চাহিদা।',
                            'status'            => 'active',
                            'notify_channels'   => ['sms', 'whatsapp'],
                        ]);
                    }

                    $createdCount++;
                }

            } catch (\Throwable $e) {
                $errors[] = "সারি #{$totalRows}: " . $e->getMessage();
            }
        }

        fclose($stream);

        $msg = "বায়ার ইমপোর্ট সম্পন্ন: মোট {$totalRows} টি সারির মধ্যে {$createdCount} টি নতুন যুক্ত হয়েছে, {$updatedCount} টি আপডেট হয়েছে, এবং {$skippedCount} টি স্কিপ হয়েছে।";

        return response()->json([
            'success'    => true,
            'message'    => $msg,
            'processed'  => $totalRows,
            'created'    => $createdCount,
            'updated'    => $updatedCount,
            'skipped'    => $skippedCount,
            'errors'     => $errors,
            'report'     => [
                'total_rows' => $totalRows,
                'created'    => $createdCount,
                'updated'    => $updatedCount,
                'skipped'    => $skippedCount,
                'errors'     => $errors,
            ],
        ]);
    }

    public static function parseSize(string $raw): float
    {
        $raw = GoogleSheetSyncService::toEnNumber(trim($raw));
        if (preg_match('/^([0-9.]+)\s*\+\s*([0-9.]+)$/', $raw, $sm)) {
            return (float) $sm[1] + (float) $sm[2];
        } elseif (preg_match('/^([0-9.]+)\s*\*\s*([0-9.]+)$/', $raw, $sm)) {
            return (float) $sm[1] * (float) $sm[2];
        } elseif (preg_match('/([0-9.]+)/', $raw, $sm)) {
            return (float) $sm[1];
        }
        return 0.0;
    }

    public static function parsePrice(string $raw): float
    {
        $en = GoogleSheetSyncService::toEnNumber(trim($raw));
        if (preg_match('/([0-9.]+)\s*(?:lac|lakh|লাখ)/iu', $en, $pm)) {
            return (float)$pm[1] * 100000;
        } elseif (preg_match('/([0-9.]+)\s*(?:cr|crore|কোটি)/iu', $en, $pm)) {
            return (float)$pm[1] * 10000000;
        } elseif (preg_match('/^([0-9.]+)$/', preg_replace('/[^0-9.]/', '', $en), $pm)) {
            $val = (float)$pm[1];
            if ($val > 0 && $val < 50) {
                return $val * 10000000; // e.g. 1.25 -> 1.25 crore
            }
            return $val;
        }
        return 0.0;
    }

    public static function normalizeFacing(string $raw): string
    {
        $map = [
            'south' => 'south', 'দক্ষিণ' => 'south', 'দক্ষিন' => 'south',
            'north' => 'north', 'উত্তর' => 'north',
            'east' => 'east', 'পূর্ব' => 'east',
            'west' => 'west', 'পশ্চিম' => 'west',
            'south-east' => 'south-east', 'দক্ষিণ-পূর্ব' => 'south-east', 'দক্ষিন-পূর্ব' => 'south-east',
            'north-east' => 'north-east', 'উত্তর-পূর্ব' => 'north-east',
            'south-west' => 'south-west', 'দক্ষিণ-পশ্চিম' => 'south-west', 'দক্ষিন-পশ্চিম' => 'south-west',
            'north-west' => 'north-west', 'উত্তর-পশ্চিম' => 'north-west',
        ];
        $r = mb_strtolower(trim($raw), 'UTF-8');
        if (isset($map[$r])) {
            return $map[$r];
        }

        foreach ($map as $k => $v) {
            if (str_contains($r, $k)) {
                return $v;
            }
        }

        return 'south';
    }

    public static function normalizeType(string $raw, string $default): string
    {
        $r = mb_strtolower(trim($raw), 'UTF-8');
        if (str_contains($r, 'flat') || str_contains($r, 'ফ্ল্যাট')) return 'flat';
        if (str_contains($r, 'commercial') || str_contains($r, 'দোকান') || str_contains($r, 'shop')) return 'commercial';
        if (str_contains($r, 'share') || str_contains($r, 'শেয়ার')) return 'land_share';
        if (str_contains($r, 'land') || str_contains($r, 'জমি') || str_contains($r, 'plot')) return 'land';
        return $default;
    }

    public static function parseExtraIntoCustomFields(string $extra, array &$data): void
    {
        // Mouza / মৌজা
        if (preg_match('/(?:mouza|মৌজা)[-\s:]+([A-Za-z0-9\s\x{0980}-\x{09FF}]+?)(?:,|\n|$)/iu', $extra, $em)) {
            $data['custom_fields'][] = ['key' => 'মৌজা', 'value' => trim($em[1])];
        }
        // RS / CS / SA / দাগ
        if (preg_match('/(?:rs|আরএস)[-\s:]*([0-9,\s]+)/iu', $extra, $em)) {
            $data['custom_fields'][] = ['key' => 'RS দাগ নম্বর', 'value' => trim($em[1])];
        }
        if (preg_match('/(?:cs|সিএস)[-\s:]*([0-9,\s]+)/iu', $extra, $em)) {
            $data['custom_fields'][] = ['key' => 'CS দাগ নম্বর', 'value' => trim($em[1])];
        }
        if (preg_match('/(?:দাগ|দাগ\s*নং)[-\s:]*([A-Za-z0-9,\s\x{0980}-\x{09FF}]+?)(?:,|\n|$)/iu', $extra, $em)) {
            $data['custom_fields'][] = ['key' => 'দাগ নম্বর', 'value' => trim($em[1])];
        }
        if (preg_match('/(?:হস্তান্তর|টাইমলাইন|সময়সীমা|timeline)[-\s:]*([A-Za-z0-9\s\x{0980}-\x{09FF}]+?)(?:,|\n|$)/iu', $extra, $em)) {
            $data['custom_fields'][] = ['key' => 'হস্তান্তর সময়সীমা', 'value' => trim($em[1])];
        }
        if (preg_match('/([0-9]+)\s*(?:bigha|বিঘা)/iu', $extra, $em)) {
            $data['custom_fields'][] = ['key' => 'জমির পরিমাণ (বিঘা)', 'value' => trim($em[1]) . ' বিঘা'];
        }
        $data['custom_fields'][] = ['key' => 'অতিরিক্ত বিবরণ ও কাগজপত্র', 'value' => $extra];
    }

    private static function updateSubDetails(Property $property, array $data): void
    {
        $askingPrice = (float)($data['asking_price'] ?? 0);
        $size = (float)($data['size'] ?? 0);

        if ($property->type === 'land') {
            $pricePerKatha = (float)($data['price_per_katha'] ?? 0);
            if ($pricePerKatha <= 0 && $size > 0 && $askingPrice > 0) {
                $pricePerKatha = round($askingPrice / $size, 2);
            }
            PropertyLandDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'katha_size'          => $size ?: 3.0,
                    'price_total'         => $askingPrice,
                    'price_per_katha'     => $pricePerKatha,
                    'soil_filling_status' => 'filled',
                ]
            );
        } elseif ($property->type === 'flat') {
            $sqft = $size ?: 1200;
            PropertyFlatDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'flat_size_sqft' => $sqft,
                    'price_total'    => $askingPrice,
                    'price_per_sqft' => $sqft > 0 && $askingPrice > 0 ? round($askingPrice / $sqft, 2) : 0,
                    'bedrooms'       => 3,
                    'bathrooms'      => 3,
                ]
            );
        } elseif ($property->type === 'commercial') {
            PropertyCommercialDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'size_sqft'       => $size ?: 500,
                    'price_total'     => $askingPrice,
                    'commercial_type' => 'shop',
                ]
            );
        } elseif ($property->type === 'land_share') {
            PropertyShareDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'flat_size_sqft' => $size ?: 1200,
                    'share_price'    => $askingPrice,
                ]
            );
        }
    }

    private static function updateDirectSource(Property $property, array $data): void
    {
        $ownerName   = !empty($data['owner_name']) ? $data['owner_name'] : 'প্রপার্টি সোর্স/এজেন্ট';
        $ownerPhone  = !empty($data['owner_phone']) ? $data['owner_phone'] : '';
        $askingPrice = (float)($data['asking_price'] ?? 0);

        $source = $property->directSources()->first();
        if ($source) {
            $source->update([
                'owner_name'   => $ownerName ?: $source->owner_name,
                'owner_phone'  => $ownerPhone ?: $source->owner_phone,
                'asking_price' => $askingPrice > 0 ? $askingPrice : $source->asking_price,
                'source_type'  => $data['source_type'] ?? $source->source_type,
            ]);
        } else {
            $property->directSources()->create([
                'owner_name'        => $ownerName,
                'owner_phone'       => $ownerPhone,
                'asking_price'      => $askingPrice,
                'source_type'       => $data['source_type'] ?? 'direct_owner',
                'is_primary_source' => true,
            ]);
        }
    }
}
