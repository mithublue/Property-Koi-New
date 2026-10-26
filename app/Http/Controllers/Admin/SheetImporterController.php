<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyDirectSource;
use App\Models\PropertyLandDetail;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyShareDetail;
use App\Models\SheetImportPreset;
use App\Services\GoogleSheetSyncService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SheetImporterController extends Controller
{
    /**
     * Display the Google Sheet Importer tool with saved presets.
     */
    public function index(Request $request): View
    {
        // Seed standard preset if database has none
        if (SheetImportPreset::count() === 0) {
            SheetImportPreset::create([
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

        $presets = SheetImportPreset::latest()->get();
        $targetFields = self::getTargetFields();

        return view('admin.sheet_importer.index', compact('presets', 'targetFields'));
    }

    /**
     * Target fields available for mapping.
     */
    public static function getTargetFields(): array
    {
        return [
            'প্রপার্টির মৌলিক তথ্য' => [
                'title'           => 'প্রপার্টি শিরোনাম (Title - ফাঁকা থাকলে অটো-জেনারেট হবে)',
                'area'            => 'এলাকা / এরিয়া (Area - যেমন: Aftabnagar, Bashundhara)',
                'sub_area'        => 'সাব-এরিয়া / ফেজ (Sub Area / Phase)',
                'block'           => 'ব্লক (Block - যেমন: G, H, L)',
                'sector'          => 'সেক্টর (Sector - যেমন: 4, 2)',
                'road_number'     => 'রোড নম্বর / নাম (Road Number - যেমন: main road, 50ft, 4)',
                'road_width_ft'   => 'রাস্তার প্রশস্ততা (Road Width in ft)',
                'plot_number'     => 'প্লট নম্বর (Plot Number - যেমন: 7, 49,51)',
                'holding_number'  => 'হোল্ডিং নম্বর (Holding Number)',
                'facing'          => 'ফেসিং / দিক (Facing - South, North, East, West)',
                'size'            => 'জমির সাইজ (কাঠা) / ফ্ল্যাট সাইজ (Size - যেমন: 5, 2.5+2.5, 10*20)',
                'asking_price'    => 'মূল্য / মোট দাম (Price - যেমন: 95 lac, 1.25 cr, 79750000)',
                'price_per_katha' => 'প্রতি কাঠার মূল্য (Price Per Katha)',
                'property_type'   => 'প্রপার্টির ধরন (Type - land, flat, commercial)',
                'description'     => 'বর্ণনা / বিবরণ (Description)',
            ],
            'যোগাযোগ ও এজেন্ট / সোর্স' => [
                'owner_name'  => 'এজেন্ট বা মালিকের নাম (Agent / Owner Name)',
                'owner_phone' => 'মোবাইল নম্বর / হোয়াটসঅ্যাপ (Phone / WhatsApp)',
                'messenger'   => 'ফেসবুক / মেসেঞ্জার লিংক (Messenger / FB Link)',
                'source_type' => 'সোর্স টাইপ (Direct Owner / Agent)',
            ],
            'স্মার্ট পার্সার ও কাস্টম ফিল্ড' => [
                'parse_extra'                => '🪄 স্মার্ট পার্সার (মৌজা, দাগ, বিঘা, ফেসিং অটো-আলাদা করবে)',
                'custom_field:মৌজা'          => 'কাস্টম ফিল্ড: মৌজা (Mouza)',
                'custom_field:RS দাগ নম্বর'   => 'কাস্টম ফিল্ড: RS দাগ নম্বর',
                'custom_field:CS দাগ নম্বর'   => 'কাস্টম ফিল্ড: CS দাগ নম্বর',
                'custom_field:SA দাগ নম্বর'   => 'কাস্টম ফিল্ড: SA দাগ নম্বর',
                'custom_field:খতিয়ান নম্বর'    => 'কাস্টম ফিল্ড: খতিয়ান নম্বর',
                'custom_field:কাগজের অবস্থা'  => 'কাস্টম ফিল্ড: কাগজের অবস্থা (নিষ্কণ্টক/রেডি)',
                'custom_field:হস্তান্তর সময়সীমা' => 'কাস্টম ফিল্ড: হস্তান্তর সময়সীমা (Timeline)',
                'custom_field:নোট'           => 'কাস্টম ফিল্ড: সাধারণ নোট বা মন্তব্য',
            ],
            'অন্যান্য' => [
                '_skip' => '✕ এই কলামটি বাদ দিন (Ignore Column)',
            ],
        ];
    }

    /**
     * Fetch sheet first row (headers) and first few data rows for preview.
     */
    public function fetchHeaders(Request $request)
    {
        $sheetUrl = trim((string) $request->input('sheet_url'));
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

        // Smart auto-suggestions
        $suggested = [];
        foreach ($cleanHeaders as $h) {
            $suggested[$h] = self::guessTargetField($h);
        }

        return response()->json([
            'success'            => true,
            'headers'            => $cleanHeaders,
            'preview_rows'       => $previewRows,
            'total_rows_approx'  => $totalCount,
            'suggested_mappings' => $suggested,
            'csv_url'            => $fetched['csv_url'],
        ]);
    }

    /**
     * Guess the most probable target field from column header name.
     */
    private static function guessTargetField(string $header): string
    {
        $h = mb_strtolower(trim($header), 'UTF-8');

        $map = [
            'place'       => 'area',
            'area'        => 'area',
            'location'    => 'area',
            'block'       => 'block',
            'sector'      => 'sector',
            'road'        => 'road_number',
            'road number' => 'road_number',
            'plot'        => 'plot_number',
            'plot number' => 'plot_number',
            'katha'       => 'size',
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
            'preset_id'        => 'nullable|exists:sheet_import_presets,id',
            'name'             => 'required|string|max:191',
            'description'      => 'nullable|string',
            'sample_sheet_url' => 'nullable|string',
            'field_mappings'   => 'required|array',
            'options'          => 'nullable|array',
        ]);

        if (!empty($validated['preset_id'])) {
            $preset = SheetImportPreset::findOrFail($validated['preset_id']);
            $preset->update([
                'name'             => $validated['name'],
                'description'      => $validated['description'] ?? null,
                'sample_sheet_url' => $validated['sample_sheet_url'] ?? null,
                'field_mappings'   => $validated['field_mappings'],
                'options'          => $validated['options'] ?? null,
            ]);
            $msg = "প্রিসেট '{$preset->name}' সফলভাবে আপডেট করা হয়েছে।";
        } else {
            $preset = SheetImportPreset::create([
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
     * Execute the import with the selected mapping & options.
     */
    public function import(Request $request)
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

        // Map column index to target field
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
                // Collect mapped data
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
                        $customKey = str_starts_with($target, 'custom_field:') ? trim(substr($target, strlen('custom_field:'))) : $colName;
                        if ($customKey !== '') {
                            $data['custom_fields'][] = ['key' => $customKey, 'value' => $rawVal];
                        }
                    }
                }

                // If block is embedded in sector like "H, sector 4"
                if (empty($data['block']) && !empty($data['sector'])) {
                    if (preg_match('/^([a-zA-Z0-9]+)\s*[,/]\s*(.*)$/i', $data['sector'], $bm)) {
                        $data['block'] = strtoupper(trim($bm[1]));
                        $data['sector'] = trim($bm[2]);
                    }
                }

                // Auto-generate title if missing
                if (empty($data['title'])) {
                    $typeLabel = match ($data['property_type']) {
                        'land' => 'জমির প্লট',
                        'flat' => 'রেডি ফ্ল্যাট',
                        'commercial' => 'কমার্শিয়াল স্পেস',
                        'land_share' => 'ল্যান্ড শেয়ার',
                        default => 'প্রপার্টি'
                    };
                    $loc = $data['area'] . ($data['block'] ? ", ব্লক {$data['block']}" : '') . ($data['sector'] ? ", সেক্টর {$data['sector']}" : '');
                    $sizeStr = $data['size'] > 0 ? " ({$data['size']} কাঠা)" : '';
                    $data['title'] = "{$loc} এ আকর্ষণীয় {$typeLabel}{$sizeStr}";
                }

                // Duplicate checking
                $existingProperty = null;
                if ($duplicateMode !== 'all') {
                    $q = Property::query();
                    if (!empty($data['plot_number']) && !empty($data['block'])) {
                        $existingProperty = $q->where('plot_number', $data['plot_number'])
                            ->where('block', $data['block'])
                            ->where('area', $data['area'])
                            ->first();
                    } elseif (!empty($data['title']) && !empty($data['owner_phone'])) {
                        $existingProperty = $q->where('title', $data['title'])->first();
                    }
                }

                if ($existingProperty && $duplicateMode === 'skip') {
                    $skippedCount++;
                    continue;
                }

                if ($existingProperty && $duplicateMode === 'update') {
                    // Update existing
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
                    // Create New Property
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

        $msg = "ইমপোর্ট সম্পন্ন: মোট {$totalRows} টি সারির মধ্যে {$createdCount} টি নতুন যুক্ত হয়েছে, {$updatedCount} টি আপডেট হয়েছে, এবং {$skippedCount} টি স্কিপ হয়েছে।";

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
        $ownerName  = !empty($data['owner_name']) ? $data['owner_name'] : 'প্রপার্টি সোর্স/এজেন্ট';
        $ownerPhone = !empty($data['owner_phone']) ? $data['owner_phone'] : '';

        $source = $property->directSources()->first();
        if ($source) {
            $source->update([
                'owner_name'   => $ownerName,
                'owner_phone'  => $ownerPhone ?: $source->owner_phone,
                'asking_price' => $data['asking_price'] ?: $source->asking_price,
            ]);
        } else {
            PropertyDirectSource::create([
                'property_id'              => $property->id,
                'source_user_id'           => auth()->id() ?: (\App\Models\User::first()?->id),
                'source_type'              => $data['source_type'] ?? 'direct_owner',
                'relationship_declaration' => ($data['source_type'] ?? '') === 'direct_agent' ? 'অনুমোদিত ডিরেক্ট এজেন্ট (Direct Agent)' : 'সরাসরি প্রপার্টির মালিক (Direct Owner)',
                'owner_name'               => $ownerName,
                'owner_phone'              => $ownerPhone,
                'asking_price'             => $data['asking_price'] ?? 0,
                'is_primary_source'        => true,
                'verification_status'      => 'pending',
            ]);
        }
    }
}
