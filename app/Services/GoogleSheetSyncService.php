<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyDirectSource;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyMedia;
use App\Models\PropertyShareDetail;
use App\Models\PropertyVerification;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleSheetSyncService
{
    /**
     * Convert any standard Google Sheet URL to a direct CSV export URL.
     */
    public static function convertToCsvUrl(string $url): string
    {
        $url = trim($url);
        if (empty($url)) {
            return '';
        }

        if (str_contains($url, '/export?format=csv') || str_ends_with(strtolower($url), '.csv')) {
            return $url;
        }

        if (preg_match('/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheetId = $matches[1];
            $gid = '';
            if (preg_match('/[?&#]gid=([0-9]+)/', $url, $gidMatches)) {
                $gid = $gidMatches[1];
            }

            $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";
            if ($gid !== '') {
                $csvUrl .= "&gid={$gid}";
            }
            return $csvUrl;
        }

        return $url;
    }

    /**
     * Fetch raw CSV content from Google Sheet or URL.
     */
    public static function fetchCsvContent(string $url): array
    {
        $csvUrl = self::convertToCsvUrl($url);
        if (empty($csvUrl)) {
            return ['success' => false, 'error' => 'Google Sheet URL প্রদান করা হয়নি।'];
        }

        try {
            $response = Http::timeout(45)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PropertyKoi/1.0',
            ])->get($csvUrl);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error'   => "Google সার্ভার রেসপন্স কোড: {$response->status()}। শিটটি পাবলিক ও সঠিক লিংক কিনা চেক করুন।",
                ];
            }

            $body = $response->body();
            if (empty(trim($body))) {
                return ['success' => false, 'error' => 'গুগল শিটটি ফাঁকা বা কোনো ডাটা পাওয়া যায়নি।'];
            }

            if (stripos($body, '<!DOCTYPE html') !== false || stripos($body, 'accounts.google.com') !== false) {
                return [
                    'success' => false,
                    'error'   => "Google Sheet টি পাবলিক করা নেই! অনুগ্রহ করে Sheet-টির Share সেটিংসে গিয়ে General access: 'Anyone with the link can view' নির্বাচন করুন।",
                ];
            }

            return ['success' => true, 'content' => $body, 'csv_url' => $csvUrl];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => 'গুগল শিট থেকে ডাটা আনা সম্ভব হয়নি: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Convert Bengali digits to English digits.
     */
    public static function toEnNumber(string $str): string
    {
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($bn, $en, trim($str));
    }

    /**
     * Parse raw CSV content into array of rows with mapped headers.
     */
    public static function parseCsv(string $csvContent): array
    {
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        $rawHeaders = fgetcsv($stream);
        if (!$rawHeaders || empty($rawHeaders)) {
            fclose($stream);
            return [];
        }

        $headerMap = [];
        foreach ($rawHeaders as $idx => $h) {
            $cleaned = mb_strtolower(trim((string) $h), 'UTF-8');
            $canonical = self::resolveHeaderCanonical($cleaned);
            if ($canonical) {
                $headerMap[$idx] = $canonical;
            }
        }

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $rowStr = implode('', array_map('trim', $row));
            if ($rowStr === '') {
                continue;
            }

            $mapped = [];
            foreach ($row as $idx => $val) {
                if (isset($headerMap[$idx])) {
                    $key = $headerMap[$idx];
                    $mapped[$key] = trim((string) $val);
                }
            }
            if (!empty($mapped)) {
                $rows[] = $mapped;
            }
        }

        fclose($stream);
        return $rows;
    }

    private static function resolveHeaderCanonical(string $header): ?string
    {
        $aliases = [
            'area' => ['area', 'place', 'প্লেস', 'এলাকা', 'লোকেশন', 'স্থান', 'district', 'city', 'location', 'প্রকল্প'],
            'sub_area' => ['sub_area', 'subarea', 'সাব এলাকা', 'সাব-এলাকা', 'সেক্টর', 'sector', 'phase', 'ফেজ', 'মহল্লা', 'মৌজা'],
            'block' => ['block', 'ব্লক'],
            'plot_number' => ['plot_number', 'plot number', 'plot', 'প্লট', 'প্লট নম্বর', 'প্লট নং', 'holding', 'holding_number', 'হোল্ডিং', 'দাগ', 'দাগ নম্বর'],
            'road_number' => ['road_number', 'road', 'রোড', 'রোড নম্বর', 'রোড নং', 'রাস্তা', 'রাস্তা নম্বর'],
            'road_width_ft' => ['road_width_ft', 'road_width', 'width', 'রাস্তার চওড়া', 'রাস্তা চওড়া', 'রাস্তা (ফুট)', 'চওড়া'],
            'type' => ['type', 'property_type', 'ধরন', 'প্রপার্টি টাইপ', 'টাইপ', 'ক্যাটেগরি', 'category'],
            'title' => ['title', 'name', 'শিরোনাম', 'নাম', 'প্রপার্টির নাম', 'প্রপার্টি শিরোনাম'],
            'description' => ['description', 'details', 'বিবরণ', 'বর্ণনা', 'ডিটেইলস', 'বিস্তারিত'],
            'asking_price' => ['asking_price', 'price', 'total_price', 'দাম', 'মূল্য', 'মোট দাম', 'মোট মূল্য', 'টাকা'],
            'price_per_unit' => ['price_per_unit', 'price_per_katha', 'price_per_sqft', 'rate', 'দর', 'কাঠা প্রতি দাম', 'বর্গফুট প্রতি দাম'],
            'size' => ['size', 'katha', 'কাঠা', 'katha_size', 'flat_size_sqft', 'সাইজ', 'বর্গফুট', 'স্কয়ারফিট', 'জমির সাইজ', 'ফ্ল্যাটের সাইজ'],
            'facing' => ['facing', 'দিক', 'মুখ', 'ফেসিং'],
            'is_negotiable' => ['is_negotiable', 'negotiable', 'আলোচনা সাপেক্ষ', 'নেগোশিয়েবল'],
            'owner_name' => ['owner_name', 'agent', 'contact_name', 'seller_name', 'মালিক', 'মালিকের নাম', 'এজেন্ট', 'এজেন্টের নাম', 'যোগাযোগ ব্যক্তি'],
            'owner_phone' => ['owner_phone', 'contact', 'phone', 'mobile', 'যোগাযোগ', 'ফোন', 'মোবাইল', 'ফোন নম্বর', 'মোবাইল নম্বর'],
            'source_type' => ['source_type', 'সোর্স', 'সোর্স টাইপ', 'role', 'রোল'],
            'images' => ['images', 'photos', 'image_url', 'photo_url', 'ছবি', 'ছবির লিংক', 'ফটো'],
            'google_map_pin_url' => ['google_map_pin_url', 'map_url', 'map', 'ম্যাপ', 'গুগল ম্যাপ'],
            'corner' => ['corner', 'is_corner', 'কর্নার', 'কর্নার প্লট'],
            'lake_facing' => ['lake_facing', 'is_lake_facing', 'লেক ভিউ', 'লেক ফেসিং'],
            'bedrooms' => ['bedrooms', 'bed', 'বেড', 'বেডরুম'],
            'bathrooms' => ['bathrooms', 'bath', 'বাথ', 'বাথরুম'],
            'floor_number' => ['floor_number', 'floor', 'তলা', 'ফ্লোর'],
            'completion_status' => ['completion_status', 'handover_status', 'হ্যান্ডওভার', 'অবস্থা'],
            'has_lift' => ['has_lift', 'lift', 'লিফট'],
            'parking_available' => ['parking_available', 'parking', 'has_parking', 'পার্কিং'],
            'project_name' => ['project_name', 'প্রজেক্টের নাম', 'প্রজেক্ট', 'বিল্ডিং'],
            'developer_name' => ['developer_name', 'ডেভেলপার', 'কোম্পানি'],
            'extra' => ['extra', 'অতিরিক্ত', 'নোট', 'মৌজা'],
            'time_line' => ['time line', 'timeline', 'সময়সীমা', 'টাইমলাইন'],
            'messenger' => ['messenger', 'facebook', 'fb', 'মেসেঞ্জার'],
        ];

        foreach ($aliases as $canonical => $list) {
            foreach ($list as $alias) {
                if ($header === $alias) {
                    return $canonical;
                }
            }
        }

        foreach ($aliases as $canonical => $list) {
            foreach ($list as $alias) {
                if (mb_strlen($alias, 'UTF-8') >= 3 && mb_strpos($header, $alias, 0, 'UTF-8') !== false) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /**
     * Preview top rows of the sheet.
     */
    public static function preview(string $sheetUrl, int $limit = 5): array
    {
        $fetched = self::fetchCsvContent($sheetUrl);
        if (!$fetched['success']) {
            return $fetched;
        }

        $rows = self::parseCsv($fetched['content']);
        $totalRows = count($rows);
        $sampleRows = array_slice($rows, 0, $limit);

        return [
            'success'     => true,
            'total_rows'  => $totalRows,
            'sample_rows' => $sampleRows,
            'csv_url'     => $fetched['csv_url'],
        ];
    }

    /**
     * Normalize a single row into clean array.
     */
    public static function normalizeRow(array $row): array
    {
        $area = !empty($row['area']) ? trim($row['area']) : 'Aftabnagar';
        $subArea = !empty($row['sub_area']) ? trim($row['sub_area']) : '';
        $block = !empty($row['block']) ? trim($row['block']) : '';

        if (empty($block) && preg_match('/(?:block|ব্লক)\s*([a-zA-Z0-9]+)/iu', $subArea, $m)) {
            $block = strtoupper($m[1]);
        }
        if (!empty($block)) {
            $block = trim(preg_replace('/^(?:block|ব্লক)\s*/iu', '', $block));
        }

        $plotNumber = !empty($row['plot_number']) ? self::toEnNumber($row['plot_number']) : '';
        $plotNumber = trim(preg_replace('/^(?:plot|প্লট|নং|#)\s*/iu', '', $plotNumber));

        $rawType = mb_strtolower(trim($row['type'] ?? ''), 'UTF-8');
        $type = 'land';
        if (str_contains($rawType, 'flat') || str_contains($rawType, 'ফ্ল্যাট') || str_contains($rawType, 'apartment')) {
            $type = 'flat';
        } elseif (str_contains($rawType, 'share') || str_contains($rawType, 'শেয়ার') || str_contains($rawType, 'শেয়ার')) {
            $type = 'land_share';
        } elseif (str_contains($rawType, 'commercial') || str_contains($rawType, 'দোকান') || str_contains($rawType, 'shop')) {
            $type = 'commercial';
        }

        $rawSize = !empty($row['size']) ? self::toEnNumber($row['size']) : '';
        $size = 0.0;
        if (!empty($rawSize)) {
            if (preg_match('/^([0-9.]+)\s*\+\s*([0-9.]+)$/', $rawSize, $sm)) {
                $size = (float) $sm[1] + (float) $sm[2];
            } elseif (preg_match('/^([0-9.]+)\s*\*\s*([0-9.]+)$/', $rawSize, $sm)) {
                $size = (float) $sm[1] * (float) $sm[2];
            } elseif (preg_match('/([0-9.]+)/', $rawSize, $sm)) {
                $size = (float) $sm[1];
            }
        }

        $rawPrice = !empty($row['asking_price']) ? trim((string)$row['asking_price']) : '';
        $askingPrice = 0.0;
        if (!empty($rawPrice)) {
            $enPrice = self::toEnNumber($rawPrice);
            if (preg_match('/([0-9.]+)\s*(?:lac|lakh|লাখ)/i', $enPrice, $pm)) {
                $askingPrice = (float)$pm[1] * 100000;
            } elseif (preg_match('/([0-9.]+)\s*(?:cr|crore|কোটি)/i', $enPrice, $pm)) {
                $askingPrice = (float)$pm[1] * 10000000;
            } elseif (preg_match('/^([0-9.]+)$/', preg_replace('/[^0-9.]/', '', $enPrice), $pm)) {
                $val = (float)$pm[1];
                if ($val > 0 && $val < 50) {
                    $askingPrice = $val * 10000000; // e.g. 1.25 -> 1.25 crore
                } else {
                    $askingPrice = $val;
                }
            }
        }

        $pricePerUnit = !empty($row['price_per_unit']) ? (float) self::toEnNumber(preg_replace('/[^0-9.]/', '', $row['price_per_unit'])) : 0.0;

        if ($askingPrice <= 0 && $pricePerUnit > 0 && $size > 0) {
            $askingPrice = $pricePerUnit * $size;
        } elseif ($pricePerUnit <= 0 && $askingPrice > 0 && $size > 0) {
            $pricePerUnit = $askingPrice / $size;
        }

        $title = !empty($row['title']) ? trim($row['title']) : '';
        if (empty($title)) {
            $typeLabel = match ($type) {
                'land' => 'জমির প্লট',
                'land_share' => 'ল্যান্ড শেয়ার প্রজেক্ট',
                'commercial' => 'কমার্শিয়াল স্পেস',
                default => 'রেডি ফ্ল্যাট',
            };
            $loc = $area . ($block ? ", ব্লক {$block}" : '') . ($subArea && $subArea !== $block ? ", {$subArea}" : '');
            $sizeStr = ($size > 0) ? ($type === 'flat' ? " ({$size} স্কয়ারফিট)" : " ({$size} কাঠা)") : '';
            $title = "{$loc} এ আকর্ষণীয় {$typeLabel}{$sizeStr}";
        }

        $roadNumber = !empty($row['road_number']) ? self::toEnNumber($row['road_number']) : '';
        $roadWidthFt = !empty($row['road_width_ft']) ? (int) self::toEnNumber($row['road_width_ft']) : 20;

        $facingMap = [
            'south' => 'south', 'দক্ষিণ' => 'south', 'দক্ষিন' => 'south',
            'north' => 'north', 'উত্তর' => 'north',
            'east' => 'east', 'পূর্ব' => 'east',
            'west' => 'west', 'পশ্চিম' => 'west',
            'south-east' => 'south-east', 'দক্ষিণ-পূর্ব' => 'south-east',
            'north-east' => 'north-east', 'উত্তর-পূর্ব' => 'north-east',
            'south-west' => 'south-west', 'দক্ষিণ-পশ্চিম' => 'south-west',
            'north-west' => 'north-west', 'উত্তর-পশ্চিম' => 'north-west',
        ];
        $rawFacing = mb_strtolower(trim($row['facing'] ?? ''), 'UTF-8');
        $facing = (!empty($rawFacing) && isset($facingMap[$rawFacing])) ? $facingMap[$rawFacing] : 'south';

        $ownerName = !empty($row['owner_name']) ? trim($row['owner_name']) : '';
        $rawPhone  = !empty($row['owner_phone']) ? self::toEnNumber($row['owner_phone']) : '';
        $ownerPhone = trim(preg_replace('/[^0-9+,\/ \-]/', '', $rawPhone));

        $sourceType = 'direct_owner';
        if (!empty($row['source_type'])) {
            $st = mb_strtolower($row['source_type'], 'UTF-8');
            if (str_contains($st, 'agent') || str_contains($st, 'এজেন্ট')) {
                $sourceType = 'direct_agent';
            }
        }

        $images = [];
        if (!empty($row['images'])) {
            $parts = preg_split('/[\r\n,|;]+/', $row['images']);
            foreach ($parts as $p) {
                $p = trim($p);
                if (filter_var($p, FILTER_VALIDATE_URL)) {
                    $images[] = $p;
                }
            }
        }

        $customFields = [];
        if (!empty($rawPrice)) {
            $customFields[] = ['key' => 'মূল্য টেক্সট (Raw Price)', 'value' => $rawPrice];
        }
        if (!empty($rawSize) && $rawSize !== (string)$size) {
            $customFields[] = ['key' => 'জমির বিবরণ (Raw Size)', 'value' => $rawSize];
        }
        if (!empty($row['time_line'])) {
            $customFields[] = ['key' => 'হস্তান্তর সময়সীমা', 'value' => trim($row['time_line'])];
        }
        if (!empty($row['messenger'])) {
            $customFields[] = ['key' => 'ফেসবুক / মেসেঞ্জার লিংক', 'value' => trim($row['messenger'])];
        }
        if (!empty($row['extra'])) {
            $extra = trim($row['extra']);
            if (preg_match('/mouza[-\s:]+([A-Za-z0-9\s\x{0980}-\x{09FF}]+?)(?:,|\n|$)/iu', $extra, $em)) {
                $customFields[] = ['key' => 'মৌজা (Mouza)', 'value' => trim($em[1])];
            }
            if (preg_match('/rs[-\s:]+([0-9,\s]+)/i', $extra, $em)) {
                $customFields[] = ['key' => 'RS দাগ নম্বর', 'value' => trim($em[1])];
            }
            if (preg_match('/cs[-\s:]+([0-9,\s]+)/i', $extra, $em)) {
                $customFields[] = ['key' => 'CS দাগ নম্বর', 'value' => trim($em[1])];
            }
            if (preg_match('/ct[-\s:]+([0-9,\s]+)/i', $extra, $em)) {
                $customFields[] = ['key' => 'সিটি জরিপ নম্বর', 'value' => trim($em[1])];
            }
            if (preg_match('/([0-9]+)\s*bigha/i', $extra, $em)) {
                $customFields[] = ['key' => 'জমির পরিমাণ (বিঘা)', 'value' => trim($em[1]) . ' বিঘা'];
            }
            $customFields[] = ['key' => 'অতিরিক্ত বিবরণ ও কাগজপত্র', 'value' => $extra];
        }

        return [
            'area'               => $area,
            'sub_area'           => $subArea ?: $block,
            'block'              => $block,
            'plot_number'        => $plotNumber,
            'road_number'        => $roadNumber,
            'road_width_ft'      => $roadWidthFt,
            'type'               => $type,
            'title'              => $title,
            'description'        => !empty($row['description']) ? trim($row['description']) : (!empty($row['extra']) ? trim($row['extra']) : ''),
            'asking_price'       => $askingPrice,
            'price_per_unit'     => $pricePerUnit,
            'size'               => $size,
            'facing'             => $facing,
            'is_negotiable'      => !empty($row['is_negotiable']) ? 1 : 1,
            'owner_name'         => $ownerName,
            'owner_phone'        => $ownerPhone,
            'source_type'        => $sourceType,
            'images'             => $images,
            'google_map_pin_url' => !empty($row['google_map_pin_url']) ? $row['google_map_pin_url'] : '',
            'is_corner'          => !empty($row['corner']) ? 1 : 0,
            'is_lake_facing'     => !empty($row['lake_facing']) ? 1 : 0,
            'bedrooms'           => !empty($row['bedrooms']) ? (int) self::toEnNumber($row['bedrooms']) : 3,
            'bathrooms'          => !empty($row['bathrooms']) ? (int) self::toEnNumber($row['bathrooms']) : 3,
            'floor_number'       => !empty($row['floor_number']) ? (int) self::toEnNumber($row['floor_number']) : 1,
            'completion_status'  => !empty($row['completion_status']) ? trim($row['completion_status']) : 'ready',
            'has_lift'           => !empty($row['has_lift']) ? 1 : 0,
            'parking_available'  => !empty($row['parking_available']) ? 1 : 0,
            'project_name'       => !empty($row['project_name']) ? trim($row['project_name']) : '',
            'developer_name'     => !empty($row['developer_name']) ? trim($row['developer_name']) : '',
            'custom_fields'      => $customFields,
        ];
    }

    /**
     * Run full sync from a Google Sheet URL.
     */
    public static function sync(string $sheetUrl, array $options = []): array
    {
        $fetched = self::fetchCsvContent($sheetUrl);
        if (!$fetched['success']) {
            return $fetched;
        }

        $rawRows = self::parseCsv($fetched['content']);
        if (empty($rawRows)) {
            return [
                'success' => false,
                'error'   => 'গুগল শিট থেকে কোনো প্রপার্টি ডাটা রিড করা সম্ভব হয়নি। হেডার নাম ঠিক আছে কিনা যাচাই করুন।',
            ];
        }

        $directLiveOpt = Setting::isTrue('adm_google_sheet_direct_live', false);
        $defaultStatus = $options['status'] ?? ($directLiveOpt ? 'live' : 'pending_verification');
        $currentUserId = auth()->id() ?? 1;

        $totalRead     = count($rawRows);
        $createdCount  = 0;
        $mergedSources = 0;
        $skippedCount  = 0;
        $errors        = [];

        foreach ($rawRows as $rowIdx => $rawRow) {
            $lineNum = $rowIdx + 2;
            $data = self::normalizeRow($rawRow);

            $area       = $data['area'];
            $subArea    = $data['sub_area'];
            $block      = $data['block'];
            $plotNumber = $data['plot_number'];
            $type       = $data['type'];

            // Anti-Duplication Check
            $duplicate = null;
            if ($plotNumber !== '') {
                $duplicate = Property::where('block', $block)
                    ->where('plot_number', $plotNumber)
                    ->where('type', $type)
                    ->first();
            }

            if ($duplicate) {
                $existingPropId = $duplicate->id;
                $phone = $data['owner_phone'];

                $alreadySource = false;
                if ($phone !== '') {
                    $alreadySource = PropertyDirectSource::where('property_id', $existingPropId)
                        ->where('owner_phone', $phone)
                        ->exists();
                }

                if (!$alreadySource && ($data['owner_name'] || $phone)) {
                    PropertyDirectSource::create([
                        'property_id'              => $existingPropId,
                        'source_user_id'           => $currentUserId,
                        'source_type'              => $data['source_type'],
                        'relationship_declaration' => 'Google Sheets থেকে সিঙ্ককৃত প্রপার্টি।',
                        'owner_name'               => $data['owner_name'],
                        'owner_phone'              => $phone,
                        'asking_price'             => $data['asking_price'],
                        'is_primary_source'        => false,
                        'verification_status'      => 'pending',
                    ]);
                    $mergedSources++;
                } else {
                    $skippedCount++;
                }
                continue;
            }

            // Create New Property
            $areaCode = (strcasecmp($area, 'Aftabnagar') === 0) ? 'AF' : 'PK';
            $prefix = match ($type) {
                'flat' => "{$areaCode}-FLAT-",
                'land_share' => "{$areaCode}-SHARE-",
                'commercial' => "{$areaCode}-COMM-",
                default => "{$areaCode}-LAND-",
            };

            $code = $prefix . rand(10000, 99999);
            $slug = Str::slug($data['title']) . '-' . rand(100, 999);

            $prop = Property::create([
                'property_code'      => $code,
                'type'               => $type,
                'title'              => $data['title'],
                'slug'               => $slug,
                'description'        => $data['description'],
                'area'               => $area,
                'sub_area'           => $subArea,
                'block'              => $block,
                'road_number'        => $data['road_number'],
                'road_width_ft'      => $data['road_width_ft'],
                'plot_number'        => $plotNumber,
                'facing'             => $data['facing'],
                'is_negotiable'      => (bool) $data['is_negotiable'],
                'status'             => $defaultStatus,
                'created_by_user_id' => $currentUserId,
                'custom_fields'      => $data['custom_fields'] ?? [],
            ]);

            // Specs
            if ($type === 'land') {
                PropertyLandDetail::create([
                    'property_id'         => $prop->id,
                    'katha_size'          => $data['size'],
                    'price_total'         => $data['asking_price'],
                    'price_per_katha'     => $data['price_per_unit'],
                    'is_corner'           => (bool) $data['is_corner'],
                    'is_lake_facing'      => (bool) $data['is_lake_facing'],
                    'boundary_wall_done'  => false,
                    'soil_filling_status' => 'filled',
                ]);
            } elseif ($type === 'flat') {
                PropertyFlatDetail::create([
                    'property_id'       => $prop->id,
                    'flat_size_sqft'    => $data['size'],
                    'price_total'       => $data['asking_price'],
                    'price_per_sqft'    => $data['price_per_unit'],
                    'bedrooms'          => $data['bedrooms'],
                    'bathrooms'         => $data['bathrooms'],
                    'floor_number'      => $data['floor_number'],
                    'completion_status' => $data['completion_status'],
                    'has_lift'          => (bool) $data['has_lift'],
                    'parking_available' => (bool) $data['parking_available'],
                ]);
            } elseif ($type === 'land_share') {
                PropertyShareDetail::create([
                    'property_id'      => $prop->id,
                    'project_name'     => $data['project_name'],
                    'developer_name'   => $data['developer_name'],
                    'land_size_katha'  => $data['size'],
                    'share_price'      => $data['asking_price'],
                    'has_lift'         => (bool) $data['has_lift'],
                    'has_parking'      => (bool) $data['parking_available'],
                ]);
            }

            // Direct Source
            PropertyDirectSource::create([
                'property_id'              => $prop->id,
                'source_user_id'           => $currentUserId,
                'source_type'              => $data['source_type'],
                'relationship_declaration' => 'Google Sheets থেকে সিঙ্ককৃত প্রপার্টি।',
                'owner_name'               => $data['owner_name'],
                'owner_phone'              => $data['owner_phone'],
                'asking_price'             => $data['asking_price'],
                'is_primary_source'        => true,
                'verification_status'      => 'pending',
            ]);

            // Verification Checklist (4 points unchecked)
            PropertyVerification::create([
                'property_id'             => $prop->id,
                'owner_identity_checked'  => false,
                'location_checked'        => false,
                'basic_info_checked'      => false,
                'basic_documents_checked' => false,
            ]);

            // Images
            if (!empty($data['images'])) {
                foreach ($data['images'] as $imgIdx => $imgUrl) {
                    PropertyMedia::create([
                        'property_id' => $prop->id,
                        'file_path'   => $imgUrl,
                        'media_type'  => 'photo',
                        'is_cover'    => ($imgIdx === 0),
                        'sort_order'  => $imgIdx,
                    ]);
                }
            }

            $createdCount++;
        }

        $report = [
            'timestamp'      => now()->toDateTimeString(),
            'total_read'     => $totalRead,
            'created'        => $createdCount,
            'merged_sources' => $mergedSources,
            'skipped'        => $skippedCount,
            'error_count'    => count($errors),
            'errors'         => array_slice($errors, 0, 10),
        ];

        Setting::set('adm_google_sheet_last_sync_report', $report);
        Setting::set('adm_google_sheet_last_sync_time', now()->toDateTimeString());

        return [
            'success' => true,
            'message' => "গুগল শিট সিঙ্ক সফল হয়েছে! মোট রিড: {$totalRead}টি, নতুন প্রপার্টি তৈরি: {$createdCount}টি, ডুপ্লিকেট রোধ করে সোর্স মার্জ: {$mergedSources}টি, স্কিপ: {$skippedCount}টি।",
            'report'  => $report,
        ];
    }

    /**
     * Link parentless / Google Sheets properties to a registered user by WhatsApp number
     */
    public static function linkParentlessProperties(string $rawPhone): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '1')) {
            $cleanPhone = '0' . $cleanPhone;
        } elseif (strlen($cleanPhone) === 13 && str_starts_with($cleanPhone, '880')) {
            $cleanPhone = substr($cleanPhone, 2);
        }

        if (strlen($cleanPhone) !== 11 || !str_starts_with($cleanPhone, '01')) {
            return [
                'success' => false,
                'message' => 'অনুগ্রহ করে সঠিক ১১ ডিজিটের WhatsApp নম্বর প্রদান করুন (যেমন: 017xxxxxxxx)।',
            ];
        }

        $last10 = substr($cleanPhone, 1);

        // Find user by phone, whatsapp_number, or last 10 digits
        $user = \App\Models\User::where('phone', $cleanPhone)
            ->orWhere('whatsapp_number', $cleanPhone)
            ->orWhere('phone', 'like', "%{$last10}")
            ->orWhere('whatsapp_number', 'like', "%{$last10}")
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => "WhatsApp নম্বর '{$cleanPhone}' দিয়ে কোনো নিবন্ধিত ব্যবহারকারী অ্যাকাউন্ট পাওয়া যায়নি। অনুগ্রহ করে প্রথমে এই WhatsApp নম্বর দিয়ে অ্যাকাউন্ট সাইনআপ করতে বলুন।",
            ];
        }

        $userName = $user->name ?: "ব্যবহারকারী #{$user->id}";

        // Find matching direct sources with this phone number
        $sources = PropertyDirectSource::where(function ($q) use ($cleanPhone, $last10, $rawPhone) {
            $q->where('owner_phone', 'like', "%{$cleanPhone}%")
              ->orWhere('owner_phone', 'like', "%{$last10}%")
              ->orWhere('owner_phone', 'like', "%{$rawPhone}%");
        })->with('property')->get();

        $linkedProps = [];
        $linkedPids = [];
        $alreadyLinkedCount = 0;

        foreach ($sources as $source) {
            $prop = $source->property;
            if (!$prop) continue;

            $pid = $prop->id;

            if ($source->source_user_id === $user->id && $prop->created_by_user_id === $user->id) {
                $alreadyLinkedCount++;
                continue;
            }

            $source->update(['source_user_id' => $user->id]);
            $prop->update(['created_by_user_id' => $user->id]);

            if (!in_array($pid, $linkedPids, true)) {
                $linkedPids[] = $pid;
                $typeLabel = match ($prop->type) {
                    'land' => 'জমি',
                    'land_share' => 'ল্যান্ড শেয়ার',
                    'commercial' => 'দোকান/কমার্শিয়াল',
                    default => 'ফ্ল্যাট',
                };
                $linkedProps[] = [
                    'id'            => $pid,
                    'property_code' => $prop->property_code,
                    'title'         => $prop->title,
                    'type'          => $prop->type,
                    'type_label'    => $typeLabel,
                    'block'         => $prop->block,
                    'plot_number'   => $prop->plot_number,
                    'owner_name'    => $source->owner_name,
                    'owner_phone'   => $source->owner_phone,
                    'url'           => url('/property/' . $prop->slug),
                ];
            }
        }

        $count = count($linkedProps);

        if ($count === 0) {
            if ($alreadyLinkedCount > 0) {
                return [
                    'success'        => true,
                    'message'        => "এই WhatsApp নম্বরের ({$cleanPhone}) মোট {$alreadyLinkedCount}টি প্রপার্টি ইতিমধ্যে ব্যবহারকারী '{$userName}' এর অ্যাকাউন্টে লিংক করা রয়েছে। নতুন কোনো প্যারেন্টলেস প্রপার্টি পাওয়া যায়নি।",
                    'linked_count'   => 0,
                    'already_linked' => $alreadyLinkedCount,
                    'user_name'      => $userName,
                    'user_id'        => $user->id,
                    'user_phone'     => $cleanPhone,
                    'properties'     => [],
                ];
            }

            return [
                'success' => false,
                'message' => "WhatsApp নম্বর '{$cleanPhone}' এর সাথে মিলে এমন কোনো প্যারেন্টলেস/শিট প্রপার্টি ডেটাবেজে পাওয়া যায়নি। অনুগ্রহ করে গুগল শিটে দেওয়া মালিকের ফোন নম্বরটি মিলিয়ে দেখুন।",
            ];
        }

        return [
            'success'        => true,
            'message'        => "সফল! মোট {$count}টি প্রপার্টি সফলভাবে ব্যবহারকারী '{$userName}' (ID: {$user->id}, Phone: {$cleanPhone})-এর অ্যাকাউন্টের সাথে লিঙ্ক করা হয়েছে। ব্যবহারকারী এখন তার ড্যাশবোর্ডে 'আমার প্রপার্টি' ট্যাবে এগুলো দেখতে পাবেন।",
            'linked_count'   => $count,
            'already_linked' => $alreadyLinkedCount,
            'user_name'      => $userName,
            'user_id'        => $user->id,
            'user_phone'     => $cleanPhone,
            'properties'     => $linkedProps,
        ];
    }
}
