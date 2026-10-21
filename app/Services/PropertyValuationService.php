<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Setting;

class PropertyValuationService
{
    /**
     * Area Benchmark Pricing Matrix (2026 Dhaka Real Estate Market Data)
     */
    public const AREA_BENCHMARKS = [
        'aftabnagar' => [
            'name'             => 'আফতাবনগর (Aftabnagar)',
            'land_min_katha'   => 11000000,   // 1.10 Crore BDT
            'land_max_katha'   => 16500000,   // 1.65 Crore BDT
            'flat_min_sqft'    => 7500,       // 7,500 BDT / sqft
            'flat_max_sqft'    => 10500,      // 10,500 BDT / sqft
            'blocks'           => [
                'A' => ['name' => 'ব্লক A (প্রাইম ফ্রন্ট / রামপুরা ব্রিজ সংলগ্ন)', 'bonus' => 0.18],
                'B' => ['name' => 'ব্লক B (মেইন রোড ও প্রাইম রেসিডেন্সিয়াল)', 'bonus' => 0.15],
                'C' => ['name' => 'ব্লক C (লেকভিউ ও সুপরিকল্পিত)', 'bonus' => 0.15],
                'D' => ['name' => 'ব্লক D (রেডি ও উন্নত বসতি)', 'bonus' => 0.12],
                'E' => ['name' => 'ব্লক E (মাঝখানের ব্লক)', 'bonus' => 0.05],
                'F' => ['name' => 'ব্লক F (মাঝখানের ব্লক)', 'bonus' => 0.05],
                'G' => ['name' => 'ব্লক G (উন্নয়নশীল ব্লক)', 'bonus' => 0.00],
                'H' => ['name' => 'ব্লক H (উন্নয়নশীল ব্লক)', 'bonus' => 0.00],
                'I' => ['name' => 'ব্লক I (ভিতরের ব্লক)', 'bonus' => -0.05],
                'J' => ['name' => 'ব্লক J (ভিতরের ব্লক)', 'bonus' => -0.08],
                'K' => ['name' => 'ব্লক K (ভিতরের ব্লক)', 'bonus' => -0.08],
                'L' => ['name' => 'ব্লক L (নতুন ব্লক)', 'bonus' => -0.10],
                'M' => ['name' => 'ব্লক M (নতুন ব্লক)', 'bonus' => -0.10],
                'N' => ['name' => 'ব্লক N (নতুন ব্লক)', 'bonus' => -0.10],
            ],
        ],
        'bashundhara' => [
            'name'             => 'বসুন্ধরা আ/এ (Bashundhara R/A)',
            'land_min_katha'   => 12000000,   // 1.20 Crore BDT
            'land_max_katha'   => 23000000,   // 2.30 Crore BDT
            'flat_min_sqft'    => 9000,
            'flat_max_sqft'    => 16000,
            'blocks'           => [
                'A' => ['name' => 'ব্লক A (ভিআইপি ফ্রন্টগেট)', 'bonus' => 0.25],
                'B' => ['name' => 'ব্লক B (প্রাইম রেডি)', 'bonus' => 0.22],
                'C' => ['name' => 'ব্লক C (প্রাইম রেডি)', 'bonus' => 0.20],
                'D' => ['name' => 'ব্লক D (প্রাইম রেডি)', 'bonus' => 0.18],
                'E' => ['name' => 'ব্লক E', 'bonus' => 0.10],
                'F' => ['name' => 'ব্লক F', 'bonus' => 0.10],
                'G' => ['name' => 'ব্লক G', 'bonus' => 0.05],
                'H' => ['name' => 'ব্লক H', 'bonus' => 0.05],
                'I' => ['name' => 'ব্লক I', 'bonus' => 0.00],
                'J' => ['name' => 'ব্লক J', 'bonus' => -0.05],
                'K' => ['name' => 'ব্লক K', 'bonus' => -0.08],
                'L' => ['name' => 'ব্লক L', 'bonus' => -0.10],
                'M' => ['name' => 'ব্লক M', 'bonus' => -0.10],
                'N' => ['name' => 'ব্লক N', 'bonus' => -0.12],
                'P' => ['name' => 'ব্লক P', 'bonus' => -0.15],
            ],
        ],
        'gulshan' => [
            'name'             => 'গুলশান (Gulshan 1 & 2)',
            'land_min_katha'   => 35000000,   // 3.50 Crore BDT
            'land_max_katha'   => 70000000,   // 7.00 Crore BDT
            'flat_min_sqft'    => 20000,
            'flat_max_sqft'    => 38000,
            'blocks'           => [],
        ],
        'banasree' => [
            'name'             => 'বনশ্রী (Banasree)',
            'land_min_katha'   => 9000000,    // 90 Lakh BDT
            'land_max_katha'   => 15000000,   // 1.50 Crore BDT
            'flat_min_sqft'    => 6800,
            'flat_max_sqft'    => 9500,
            'blocks'           => [],
        ],
        'dhanmondi' => [
            'name'             => 'ধানমন্ডি (Dhanmondi)',
            'land_min_katha'   => 28000000,
            'land_max_katha'   => 55000000,
            'flat_min_sqft'    => 15000,
            'flat_max_sqft'    => 26000,
            'blocks'           => [],
        ],
        'uttara' => [
            'name'             => 'উত্তরা (Uttara Sector 1-18)',
            'land_min_katha'   => 10000000,
            'land_max_katha'   => 19000000,
            'flat_min_sqft'    => 8000,
            'flat_max_sqft'    => 14500,
            'blocks'           => [],
        ],
        'purbachal' => [
            'name'             => 'পূর্বাচল নতুন শহর (Purbachal)',
            'land_min_katha'   => 4000000,
            'land_max_katha'   => 9000000,
            'flat_min_sqft'    => 5500,
            'flat_max_sqft'    => 8000,
            'blocks'           => [],
        ],
        'other' => [
            'name'             => 'ঢাকার অন্যান্য এলাকা (Other Dhaka)',
            'land_min_katha'   => 5000000,
            'land_max_katha'   => 9500000,
            'flat_min_sqft'    => 5800,
            'flat_max_sqft'    => 8500,
            'blocks'           => [],
        ],
    ];

    /**
     * Get active Area Benchmarks (reads admin custom settings or falls back to constants)
     */
    public static function getBenchmarks(): array
    {
        try {
            $custom = Setting::get('adm_valuation_benchmarks_json');
            if (!empty($custom)) {
                $decoded = is_array($custom) ? $custom : json_decode($custom, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return array_replace_recursive(self::AREA_BENCHMARKS, $decoded);
                }
            }
        } catch (\Throwable $e) {
            // fallback to default constants
        }
        return self::AREA_BENCHMARKS;
    }

    /**
     * Calculate estimated fair market valuation range
     */
    public static function estimate(array $params): array
    {
        $area = $params['area'] ?? 'aftabnagar';
        $type = $params['type'] ?? 'flat'; // 'flat' or 'land'
        $size = (float) ($params['size'] ?? ($type === 'land' ? 3 : 1350));
        $roadWidth = (int) ($params['road_width'] ?? 25);
        $facing = $params['facing'] ?? 'south';
        $isCorner = !empty($params['is_corner']);
        $hasLakeView = !empty($params['has_lake_view']);
        $floorLevel = (int) ($params['floor_level'] ?? 4);
        $hasParking = !empty($params['has_parking']);

        $allBenchmarks = self::getBenchmarks();
        $benchmarks = $allBenchmarks[$area] ?? ($allBenchmarks['other'] ?? self::AREA_BENCHMARKS['other']);
        $block = $params['block'] ?? '';

        // Determine base min and max rates
        if ($type === 'land') {
            $baseMin = $benchmarks['land_min_katha'];
            $baseMax = $benchmarks['land_max_katha'];
        } else {
            $baseMin = $benchmarks['flat_min_sqft'];
            $baseMax = $benchmarks['flat_max_sqft'];
        }

        // Multipliers
        $multiplier = 1.0;
        $breakdownNotes = [];

        // Block Location adjustment (e.g. Block A, B, C, D in Aftabnagar / Bashundhara)
        if (!empty($block) && !empty($benchmarks['blocks'][$block])) {
            $bonus = (float) ($benchmarks['blocks'][$block]['bonus'] ?? 0);
            $multiplier += $bonus;
            $blockName = $benchmarks['blocks'][$block]['name'] ?? ("ব্লক " . $block);
            $pct = round($bonus * 100);
            if ($pct > 0) {
                $breakdownNotes[] = "{$blockName} প্রিমিয়াম (+{$pct}%)";
            } elseif ($pct < 0) {
                $breakdownNotes[] = "{$blockName} সমন্বয় ({$pct}%)";
            }
        }

        // Road Width adjustment
        if ($roadWidth >= 60) {
            $multiplier += 0.18;
            $breakdownNotes[] = '৬০+ ফুট প্রধান সড়ক প্রিমিয়াম (+১৮%)';
        } elseif ($roadWidth >= 40) {
            $multiplier += 0.12;
            $breakdownNotes[] = '৪০ ফুট চওড়া রাস্তা প্রিমিয়াম (+১২%)';
        } elseif ($roadWidth >= 30) {
            $multiplier += 0.06;
            $breakdownNotes[] = '৩০ ফুট প্রশস্ত রাস্তা (+৬%)';
        }

        // Corner & View
        if ($isCorner) {
            $multiplier += 0.08;
            $breakdownNotes[] = 'কর্নার প্লট / স্পেস ভ্যালু (+৮%)';
        }
        if ($hasLakeView) {
            $multiplier += 0.10;
            $breakdownNotes[] = 'লেকভিউ / পার্ক সংলগ্ন প্রিমিয়াম (+১০%)';
        }
        if ($facing === 'south') {
            $multiplier += 0.04;
            $breakdownNotes[] = 'দক্ষিণমুখী বাতাস ও আলো সুবিধা (+৪%)';
        }

        // Flat Floor Level Adjustments
        if ($type === 'flat') {
            if ($floorLevel === 1) {
                $multiplier -= 0.03;
                $breakdownNotes[] = '১ম তলা (গ্রাউন্ড ফ্লোরের উপরে) ডিসকাউন্ট (-৩%)';
            } elseif ($floorLevel >= 6 && $floorLevel <= 9) {
                $multiplier += 0.03;
                $breakdownNotes[] = 'উচ্চ ফ্লোর (৬ষ্ঠ-৯ম তলা) প্রিমিয়াম (+৩%)';
            } elseif ($floorLevel >= 10) {
                $multiplier -= 0.02;
            }
        }

        $minValuation = $baseMin * $size * $multiplier;
        $maxValuation = $baseMax * $size * $multiplier;

        // Additional parking fixed addition for flat
        if ($type === 'flat' && $hasParking) {
            $parkingVal = 500000;
            $minValuation += $parkingVal;
            $maxValuation += $parkingVal;
            $breakdownNotes[] = 'ডেডিকেটেড কার পার্কিং (+৫ লাখ টাকা)';
        }

        $fairMarket = ($minValuation + $maxValuation) / 2;

        return [
            'area_name'          => $benchmarks['name'],
            'property_type'      => $type,
            'size'               => $size,
            'size_unit'          => $type === 'land' ? 'কাঠা' : 'বর্গফুট',
            'min_estimate'       => round($minValuation),
            'max_estimate'       => round($maxValuation),
            'fair_market_value'  => round($fairMarket),
            'unit_rate_min'      => round($baseMin * $multiplier),
            'unit_rate_max'      => round($baseMax * $multiplier),
            'confidence_score'   => 92,
            'breakdown_notes'    => $breakdownNotes,
        ];
    }
}
