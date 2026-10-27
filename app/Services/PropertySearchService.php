<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class PropertySearchService
{
    public const BN_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    public const EN_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Map English block letters (A-Z) to Bengali phonetic representations.
     */
    public const BLOCK_LETTER_MAP = [
        'A' => ['এ', 'এই'],
        'B' => ['বি'],
        'C' => ['সি'],
        'D' => ['ডি'],
        'E' => ['ই', 'ঈ'],
        'F' => ['এফ'],
        'G' => ['জি'],
        'H' => ['এইচ', 'এইস'],
        'I' => ['আই'],
        'J' => ['জে'],
        'K' => ['কে'],
        'L' => ['এল'],
        'M' => ['এম'],
        'N' => ['এন'],
        'O' => ['ও'],
        'P' => ['পি'],
        'Q' => ['কিউ'],
        'R' => ['আর'],
        'S' => ['এস'],
        'T' => ['টি'],
        'U' => ['ইউ'],
        'V' => ['ভি'],
        'W' => ['ডব্লিউ', 'ডাবলু'],
        'X' => ['এক্স'],
        'Y' => ['ওয়াই', 'ওয়াই'],
        'Z' => ['জেড'],
    ];

    /**
     * Bilingual Area Dictionary
     */
    public const AREA_MAP = [
        'Aftabnagar' => [
            'canonical' => 'Aftabnagar',
            'aliases'   => ['aftabnagar', 'aftab nagar', 'aftab', 'আফতাবনগর', 'আফতাব নগর', 'আফতাব'],
        ],
        'Bashundhara' => [
            'canonical' => 'Bashundhara',
            'aliases'   => ['bashundhara', 'bashundhara r/a', 'bashundhara residential area', 'বসুন্ধরা', 'বসুন্ধরা আবাসিক', 'বসুন্ধরা আ/এ', 'বসুন্ধরা আর/এ'],
        ],
        'Banasree' => [
            'canonical' => 'Banasree',
            'aliases'   => ['banasree', 'bonosree', 'bonoshree', 'বনশ্রী', 'বনশ্রী আ/এ'],
        ],
        'Uttara' => [
            'canonical' => 'Uttara',
            'aliases'   => ['uttara', 'উত্তরা'],
        ],
        'Gulshan' => [
            'canonical' => 'Gulshan',
            'aliases'   => ['gulshan', 'gulshan 1', 'gulshan 2', 'গুলশান', 'গুলশান ১', 'গুলশান ২'],
        ],
        'Banani' => [
            'canonical' => 'Banani',
            'aliases'   => ['banani', 'বনানী'],
        ],
        'Dhanmondi' => [
            'canonical' => 'Dhanmondi',
            'aliases'   => ['dhanmondi', 'ধানমন্ডি', 'ধানমণ্ডি'],
        ],
        'Mirpur' => [
            'canonical' => 'Mirpur',
            'aliases'   => ['mirpur', 'মিরপুর', 'মিরপুর ১', 'মিরপুর ২', 'মিরপুর ১০', 'মিরপুর ১১', 'মিরপুর ১২', 'মিরপুর ১৪', 'mirpur 1', 'mirpur 2', 'mirpur 10', 'mirpur 11', 'mirpur 12', 'mirpur 14'],
        ],
        'Baridhara' => [
            'canonical' => 'Baridhara',
            'aliases'   => ['baridhara', 'baridhara dohs', 'বারিধারা', 'বারিধারা ডিওএইচএস'],
        ],
        'Mohakhali' => [
            'canonical' => 'Mohakhali',
            'aliases'   => ['mohakhali', 'mohakhali dohs', 'মহাখালী', 'মহাকালী', 'মহাখালী ডিওএইচএস'],
        ],
        'Badda' => [
            'canonical' => 'Badda',
            'aliases'   => ['badda', 'বাড্ডা', 'মধ্য বাড্ডা', 'উত্তর বাড্ডা', 'দক্ষিণ বাড্ডা', 'middle badda', 'north badda', 'south badda'],
        ],
        'Rampura' => [
            'canonical' => 'Rampura',
            'aliases'   => ['rampura', 'রামপুরা'],
        ],
        'Khilgaon' => [
            'canonical' => 'Khilgaon',
            'aliases'   => ['khilgaon', 'খিলগাঁও'],
        ],
        'Motijheel' => [
            'canonical' => 'Motijheel',
            'aliases'   => ['motijheel', 'motijhil', 'মতিঝিল'],
        ],
        'Mohammadpur' => [
            'canonical' => 'Mohammadpur',
            'aliases'   => ['mohammadpur', 'mohammedpur', 'মোহাম্মদপুর'],
        ],
        'Lalmatia' => [
            'canonical' => 'Lalmatia',
            'aliases'   => ['lalmatia', 'লালমাটিয়া', 'লালমাটিয়া'],
        ],
        'Tejgaon' => [
            'canonical' => 'Tejgaon',
            'aliases'   => ['tejgaon', 'তেজগাঁও'],
        ],
        'Nikunja' => [
            'canonical' => 'Nikunja',
            'aliases'   => ['nikunja', 'nikunjo', 'নিকুঞ্জ', 'নিকুঞ্জ ১', 'নিকুঞ্জ ২', 'nikunja 1', 'nikunja 2'],
        ],
        'Khilkhet' => [
            'canonical' => 'Khilkhet',
            'aliases'   => ['khilkhet', 'খিলক্ষেত'],
        ],
        'Purbachal' => [
            'canonical' => 'Purbachal',
            'aliases'   => ['purbachal', 'পূর্বাচল', 'পূর্বাচল নতুন শহর'],
        ],
        'Jalsiri' => [
            'canonical' => 'Jalsiri',
            'aliases'   => ['jalsiri', 'jalshiri', 'jolsiri', 'জলসিঁড়ি', 'জলসিড়ি'],
        ],
        'Keraniganj' => [
            'canonical' => 'Keraniganj',
            'aliases'   => ['keraniganj', 'কেরানীগঞ্জ'],
        ],
        'Savar' => [
            'canonical' => 'Savar',
            'aliases'   => ['savar', 'সাভার'],
        ],
        'Ashulia' => [
            'canonical' => 'Ashulia',
            'aliases'   => ['ashulia', 'আশুলিয়া', 'আশুলিয়া'],
        ],
        'Kanchpur' => [
            'canonical' => 'Kanchpur',
            'aliases'   => ['kanchpur', 'কাঁচপুর'],
        ],
    ];

    /**
     * Facing direction mapping
     */
    public const FACING_MAP = [
        'North'      => ['north', 'north facing', 'উত্তর', 'উত্তরমুখী', 'উত্তর মুখী'],
        'South'      => ['south', 'south facing', 'দক্ষিণ', 'দক্ষিণমুখী', 'দক্ষিণ মুখী'],
        'East'       => ['east', 'east facing', 'পূর্ব', 'পূর্বমুখী', 'পূর্ব মুখী'],
        'West'       => ['west', 'west facing', 'পশ্চিম', 'পশ্চিমমুখী', 'পশ্চিম মুখী'],
        'North-East' => ['north-east', 'northeast', 'উত্তর-পূর্ব', 'উত্তর পূর্ব'],
        'North-West' => ['north-west', 'northwest', 'উত্তর-পশ্চিম', 'উত্তর পশ্চিম'],
        'South-East' => ['south-east', 'southeast', 'দক্ষিণ-পূর্ব', 'দক্ষিণ পূর্ব'],
        'South-West' => ['south-west', 'southwest', 'দক্ষিণ-পশ্চিম', 'দক্ষিণ পশ্চিম'],
        'Corner'     => ['corner', 'corner plot', 'কর্নার', 'কোনার'],
    ];

    /**
     * Convert Bengali numerals to English digits.
     */
    public static function bn2enDigits(string $str): string
    {
        return str_replace(self::BN_DIGITS, self::EN_DIGITS, $str);
    }

    /**
     * Convert English digits to Bengali numerals.
     */
    public static function en2bnDigits(string $str): string
    {
        return str_replace(self::EN_DIGITS, self::BN_DIGITS, $str);
    }

    /**
     * Parse and structure a search input query with bilingual entity extraction.
     */
    public static function parseSearch(string $input): array
    {
        $raw = trim($input);
        if ($raw === '') {
            return [
                'raw'      => '',
                'clean'    => '',
                'detected' => [],
                'terms'    => [],
            ];
        }

        $cleanEn = self::bn2enDigits($raw);
        $cleanBn = self::en2bnDigits($raw);

        $detected = [];

        // 1. Detect Area
        foreach (self::AREA_MAP as $canonical => $data) {
            foreach ($data['aliases'] as $alias) {
                if (mb_stripos($raw, $alias) !== false || mb_stripos($cleanEn, $alias) !== false) {
                    $detected['area'] = [
                        'canonical' => $canonical,
                        'variants'  => array_values(array_unique(array_merge([$canonical], $data['aliases']))),
                    ];
                    break 2;
                }
            }
        }

        // 2. Detect Block (e.g., 'Block H', 'H block', 'এইচ ব্লক', 'ব্লক এইচ', 'Block-H', 'এইচ', 'H')
        $detectedBlock = self::extractBlock($raw, $cleanEn);
        if ($detectedBlock) {
            $detected['block'] = $detectedBlock;
        }

        // 3. Detect Road (e.g. 'Road 5', 'Road-5', 'রোড ৫', 'রাস্তা ৫')
        $detectedRoad = self::extractRoad($raw, $cleanEn);
        if ($detectedRoad) {
            $detected['road'] = $detectedRoad;
        }

        // 4. Detect Plot (e.g. 'Plot 12', 'প্লট ১২', 'দাগ ১২')
        $detectedPlot = self::extractPlot($raw, $cleanEn);
        if ($detectedPlot) {
            $detected['plot'] = $detectedPlot;
        }

        // 5. Detect Sector (e.g. 'Sector 3', 'সেক্টর ৩')
        $detectedSector = self::extractSector($raw, $cleanEn);
        if ($detectedSector) {
            $detected['sector'] = $detectedSector;
        }

        // 6. Detect Facing (e.g. 'North', 'উত্তর', 'South', 'দক্ষিণ')
        $detectedFacing = self::extractFacing($raw);
        if ($detectedFacing) {
            $detected['facing'] = $detectedFacing;
        }

        // 7. Detect Katha / Size (e.g. '5 katha', '৫ কাঠা')
        $detectedKatha = self::extractKatha($raw, $cleanEn);
        if ($detectedKatha) {
            $detected['katha'] = $detectedKatha;
        }

        // 8. General search terms (bilingual variants)
        $terms = array_values(array_filter(array_unique([
            $raw,
            $cleanEn,
            $cleanBn,
            strtolower($raw),
            strtolower($cleanEn),
        ])));

        return [
            'raw'      => $raw,
            'clean'    => $cleanEn,
            'detected' => $detected,
            'terms'    => $terms,
        ];
    }

    /**
     * Extract Block information from input string.
     */
    protected static function extractBlock(string $raw, string $cleanEn): ?array
    {
        // 1. Check explicit "Block <letter>" or "<letter> Block" patterns in English
        if (preg_match('/(?:block[\s\:\#\-]*([a-zA-Z0-9]+)|([a-zA-Z0-9]+)[\s\:\#\-]*block)/i', $cleanEn, $m)) {
            $val = strtoupper(trim(!empty($m[1]) ? $m[1] : $m[2], ",.-_:#;()[]"));
            if ($val !== '') {
                return self::buildBlockVariants($val);
            }
        }

        // 2. Check explicit "ব্লক <অক্ষর>" or "<অক্ষর> ব্লক" patterns in Bengali
        if (preg_match('/(?:ব্লক[\s\:\#\-]*([^\s]+)|([^\s]+)[\s\:\#\-]*ব্লক)/u', $raw, $m)) {
            $word = trim(trim(!empty($m[1]) ? $m[1] : $m[2]), ",.-_:#;()[]");
            // Convert to English if it's Bengali numerals or letters
            $enVal = self::bn2enDigits($word);
            if (is_numeric($enVal)) {
                return self::buildBlockVariants($enVal);
            }
            // Check phonetic letter map
            foreach (self::BLOCK_LETTER_MAP as $enLetter => $bnLetters) {
                if ($word === $enLetter || strcasecmp($word, $enLetter) === 0 || in_array($word, $bnLetters, true)) {
                    return self::buildBlockVariants($enLetter);
                }
            }
        }

        // 3. Stand-alone token matching for distinctive phonetic block letters (e.g. "এইচ", "সি", "ডি")
        $tokens = preg_split('/[\s,\.\-]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $tokenCount = count($tokens);

        foreach ($tokens as $token) {
            $token = trim($token, ",.-_:#;()[]");
            if ($token === '') continue;

            // Precaution for common Bengali words 'এ' or 'ও': only match if standalone query of 1 token
            if (in_array($token, ['এ', 'এই', 'ও', 'a', 'o'], true) && $tokenCount > 1) {
                continue;
            }

            foreach (self::BLOCK_LETTER_MAP as $enLetter => $bnLetters) {
                if (in_array($token, $bnLetters, true)) {
                    return self::buildBlockVariants($enLetter);
                }
            }
        }

        return null;
    }

    /**
     * Build comprehensive variants for a detected block (e.g., 'H').
     */
    protected static function buildBlockVariants(string $blockVal): array
    {
        $canonical = strtoupper($blockVal);
        $variants = [
            $canonical,
            strtolower($canonical),
            "Block {$canonical}",
            "Block-{$canonical}",
            "{$canonical} Block",
            "{$canonical}-Block",
        ];

        // If letter A-Z
        if (isset(self::BLOCK_LETTER_MAP[$canonical])) {
            $bnLetters = self::BLOCK_LETTER_MAP[$canonical];
            foreach ($bnLetters as $bnL) {
                $variants[] = $bnL;
                $variants[] = "ব্লক {$bnL}";
                $variants[] = "ব্লক-{$bnL}";
                $variants[] = "{$bnL} ব্লক";
                $variants[] = "{$bnL}-ব্লক";
            }
        } elseif (is_numeric($canonical)) {
            $bnNum = self::en2bnDigits($canonical);
            $variants[] = $bnNum;
            $variants[] = "ব্লক {$bnNum}";
            $variants[] = "ব্লক-{$bnNum}";
            $variants[] = "{$bnNum} ব্লক";
            $variants[] = "{$bnNum} নম্বর ব্লক";
        }

        return [
            'canonical' => $canonical,
            'variants'  => array_values(array_unique($variants)),
        ];
    }

    /**
     * Extract Road information.
     */
    protected static function extractRoad(string $raw, string $cleanEn): ?array
    {
        if (preg_match('/(?:রোড|রাস্তা|road|rd)[\s\:\#\-]*(?:নং|নম্বর|no|#)?[\s\:\#\-]*([০-৯0-9]+[a-zA-Z\x{0980}-\x{09FF}]?)/iu', $raw, $m) ||
            preg_match('/([০-৯0-9]+[a-zA-Z\x{0980}-\x{09FF}]?)[\s\:\#\-]*(?:নং|নম্বর|no)?[\s\:\#\-]*(?:রোড|রাস্তা|road|rd)/iu', $raw, $m) ||
            preg_match('/(?:road|rd)[\s\:\#\-]*(?:no|#)?[\s\:\#\-]*([0-9]+[a-zA-Z]?)/i', $cleanEn, $m)) {
            $roadEn = self::bn2enDigits(trim($m[1], ",.-_:#;()[]"));
            $roadBn = self::en2bnDigits($roadEn);
            return [
                'canonical' => $roadEn,
                'variants'  => array_values(array_unique([
                    $roadEn,
                    $roadBn,
                    "Road {$roadEn}",
                    "Road-{$roadEn}",
                    "Rd {$roadEn}",
                    "রোড {$roadBn}",
                    "রোড-{$roadBn}",
                    "রাস্তা {$roadBn}",
                ])),
            ];
        }
        return null;
    }

    /**
     * Extract Plot information.
     */
    protected static function extractPlot(string $raw, string $cleanEn): ?array
    {
        if (preg_match('/(?:প্লট|দাগ|plot)[\s\:\#\-]*(?:নং|নম্বর|no|#)?[\s\:\#\-]*([০-৯0-9]+[a-zA-Z\x{0980}-\x{09FF}]?)/iu', $raw, $m) ||
            preg_match('/([০-৯0-9]+[a-zA-Z\x{0980}-\x{09FF}]?)[\s\:\#\-]*(?:নং|নম্বর|no)?[\s\:\#\-]*(?:প্লট|দাগ|plot)/iu', $raw, $m) ||
            preg_match('/plot[\s\:\#\-]*(?:no|#)?[\s\:\#\-]*([0-9]+[a-zA-Z]?)/i', $cleanEn, $m)) {
            $plotEn = self::bn2enDigits(trim($m[1], ",.-_:#;()[]"));
            $plotBn = self::en2bnDigits($plotEn);
            return [
                'canonical' => $plotEn,
                'variants'  => array_values(array_unique([
                    $plotEn,
                    $plotBn,
                    "Plot {$plotEn}",
                    "Plot-{$plotEn}",
                    "প্লট {$plotBn}",
                    "প্লট-{$plotBn}",
                    "দাগ {$plotBn}",
                ])),
            ];
        }
        return null;
    }

    /**
     * Extract Sector information.
     */
    protected static function extractSector(string $raw, string $cleanEn): ?array
    {
        if (preg_match('/(?:সেক্টর|sector)[\s\:\#\-]*(?:নং|নম্বর|no|#)?[\s\:\#\-]*([০-৯0-9]+)/iu', $raw, $m) ||
            preg_match('/sector[\s\:\#\-]*(?:no|#)?[\s\:\#\-]*([0-9]+)/i', $cleanEn, $m)) {
            $secEn = self::bn2enDigits(trim($m[1], ",.-_:#;()[]"));
            $secBn = self::en2bnDigits($secEn);
            return [
                'canonical' => $secEn,
                'variants'  => array_values(array_unique([
                    $secEn,
                    $secBn,
                    "Sector {$secEn}",
                    "Sector-{$secEn}",
                    "সেক্টর {$secBn}",
                    "সেক্টর-{$secBn}",
                ])),
            ];
        }
        return null;
    }

    /**
     * Extract Facing direction.
     */
    protected static function extractFacing(string $raw): ?array
    {
        foreach (self::FACING_MAP as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (mb_stripos($raw, $alias) !== false) {
                    return [
                        'canonical' => $canonical,
                        'variants'  => array_values(array_unique(array_merge([$canonical], $aliases))),
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Extract Katha / Size information.
     */
    protected static function extractKatha(string $raw, string $cleanEn): ?array
    {
        if (preg_match('/(?:([০-৯0-9\.]+)\s*(?:কাঠা|katha|kathas|shotok|শতক|বিঘা|bigha))/iu', $raw, $m) ||
            preg_match('/(?:([0-9\.]+)\s*(?:katha|kathas))/i', $cleanEn, $m)) {
            $val = self::bn2enDigits(trim($m[1]));
            $valBn = self::en2bnDigits($val);
            return [
                'canonical' => $val,
                'variants'  => array_values(array_unique([
                    $val,
                    $valBn,
                    "{$val} katha",
                    "{$val} Katha",
                    "{$valBn} কাঠা",
                ])),
            ];
        }
        return null;
    }

    /**
     * Apply intelligent bilingual search query to Eloquent query builder.
     *
     * @param Builder $query
     * @param string $searchTerm
     * @param bool $isVaultOrAdmin When true, also searches private owner contacts, creator and custom fields.
     * @return Builder
     */
    public static function applySearchQuery(Builder $query, string $searchTerm, bool $isVaultOrAdmin = false): Builder
    {
        $parsed = self::parseSearch($searchTerm);
        if ($parsed['raw'] === '') {
            return $query;
        }

        $detected = $parsed['detected'];

        $query->where(function (Builder $q) use ($parsed, $detected, $isVaultOrAdmin) {
            if (!empty($detected)) {
                // All detected criteria must match together (AND)
                $q->where(function (Builder $structuredQ) use ($detected) {
                    // 1. Area matching
                    if (!empty($detected['area'])) {
                        $areaVariants = $detected['area']['variants'];
                        $canonicalArea = $detected['area']['canonical'];
                        $structuredQ->where(function ($sub) use ($areaVariants, $canonicalArea) {
                            $sub->where('area', $canonicalArea)
                                ->orWhereIn('area', $areaVariants);
                            foreach ($areaVariants as $v) {
                                $sub->orWhere('area', 'like', "%{$v}%")
                                    ->orWhere('title', 'like', "%{$v}%");
                            }
                        });
                    }

                    // 2. Block matching
                    if (!empty($detected['block'])) {
                        $blockCanonical = $detected['block']['canonical'];
                        $blockVariants = $detected['block']['variants'];
                        $structuredQ->where(function ($sub) use ($blockCanonical, $blockVariants) {
                            $sub->where('block', $blockCanonical)
                                ->orWhere('block', 'like', "%{$blockCanonical}%")
                                ->orWhere('title', 'like', "%Block {$blockCanonical}%")
                                ->orWhere('title', 'like', "%Block-{$blockCanonical}%")
                                ->orWhere('title', 'like', "%{$blockCanonical} Block%");

                            foreach ($blockVariants as $v) {
                                if (mb_strlen($v) > 1) {
                                    $sub->orWhere('title', 'like', "%{$v}%")
                                        ->orWhere('block', 'like', "%{$v}%");
                                } else {
                                    $sub->orWhere('block', $v);
                                }
                            }
                        });
                    }

                    // 3. Road matching
                    if (!empty($detected['road'])) {
                        $roadVal = $detected['road']['canonical'];
                        $roadVariants = $detected['road']['variants'];
                        $structuredQ->where(function ($sub) use ($roadVal, $roadVariants) {
                            $sub->where('road_number', 'like', "%{$roadVal}%");
                            foreach ($roadVariants as $v) {
                                $sub->orWhere('title', 'like', "%{$v}%")
                                    ->orWhere('road_number', 'like', "%{$v}%");
                            }
                        });
                    }

                    // 4. Plot matching
                    if (!empty($detected['plot'])) {
                        $plotVal = $detected['plot']['canonical'];
                        $plotVariants = $detected['plot']['variants'];
                        $structuredQ->where(function ($sub) use ($plotVal, $plotVariants) {
                            $sub->where('plot_number', 'like', "%{$plotVal}%");
                            foreach ($plotVariants as $v) {
                                $sub->orWhere('title', 'like', "%{$v}%")
                                    ->orWhere('plot_number', 'like', "%{$v}%");
                            }
                        });
                    }

                    // 5. Sector matching
                    if (!empty($detected['sector'])) {
                        $secVal = $detected['sector']['canonical'];
                        $secVariants = $detected['sector']['variants'];
                        $structuredQ->where(function ($sub) use ($secVal, $secVariants) {
                            $sub->where('sector', 'like', "%{$secVal}%");
                            foreach ($secVariants as $v) {
                                $sub->orWhere('title', 'like', "%{$v}%");
                            }
                        });
                    }

                    // 6. Facing matching
                    if (!empty($detected['facing'])) {
                        $faceVal = $detected['facing']['canonical'];
                        $faceVariants = $detected['facing']['variants'];
                        $structuredQ->where(function ($sub) use ($faceVal, $faceVariants) {
                            $sub->where('facing', 'like', "%{$faceVal}%");
                            foreach ($faceVariants as $v) {
                                $sub->orWhere('title', 'like', "%{$v}%");
                            }
                        });
                    }

                    // 7. Katha matching
                    if (!empty($detected['katha'])) {
                        $kathaVal = $detected['katha']['canonical'];
                        $kathaVariants = $detected['katha']['variants'];
                        $structuredQ->where(function ($sub) use ($kathaVal, $kathaVariants) {
                            $sub->whereHas('landDetail', fn($lq) => $lq->where('katha_size', 'like', "%{$kathaVal}%"));
                            foreach ($kathaVariants as $v) {
                                $sub->orWhere('title', 'like', "%{$v}%");
                            }
                        });
                    }
                });

                // Also allow literal match if the full raw text is directly in title or code or description
                $q->orWhere(function (Builder $fullPhraseQ) use ($parsed) {
                    $raw = $parsed['raw'];
                    $fullPhraseQ->where('title', 'like', "%{$raw}%")
                                ->orWhere('property_code', 'like', "%{$raw}%")
                                ->orWhere('description', 'like', "%{$raw}%");
                });
            } else {
                // If NO structured entities were detected, execute broad search across all fields
                self::applyBroadMatches($q, $parsed, $isVaultOrAdmin);
            }
        });

        return $query;
    }

    /**
     * Apply broad matches across all property fields.
     */
    protected static function applyBroadMatches(Builder $query, array $parsed, bool $isVaultOrAdmin): void
    {
        $terms = $parsed['terms'];
        $cleanEn = $parsed['clean'];

        // Clean digits for phone number matching
        $digitsOnly = preg_replace('/[^0-9]/', '', $cleanEn);

        $query->where(function (Builder $sub) use ($terms, $cleanEn, $digitsOnly, $isVaultOrAdmin) {
            foreach ($terms as $term) {
                if (mb_strlen($term) < 1) continue;

                $escaped = trim(json_encode($term, JSON_UNESCAPED_SLASHES), '"');

                $sub->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('property_code', 'like', "%{$term}%")
                    ->orWhere('area', 'like', "%{$term}%")
                    ->orWhere('sub_area', 'like', "%{$term}%")
                    ->orWhere('block', 'like', "%{$term}%")
                    ->orWhere('plot_number', 'like', "%{$term}%")
                    ->orWhere('road_number', 'like', "%{$term}%")
                    ->orWhere('sector', 'like', "%{$term}%")
                    ->orWhere('facing', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('custom_fields', 'like', "%{$term}%")
                    ->orWhere('custom_fields', 'like', "%{$escaped}%");

                if ($isVaultOrAdmin) {
                    $sub->orWhereHas('landDetail', fn($lq) => $lq->where('katha_size', 'like', "%{$term}%")->orWhere('price_total', 'like', "%{$term}%"))
                        ->orWhereHas('directSources', fn($dq) => $dq->where('owner_name', 'like', "%{$term}%")->orWhere('owner_phone', 'like', "%{$term}%"));
                }
            }

            // Phone search fallback if numbers are present
            if ($isVaultOrAdmin && !empty($digitsOnly) && strlen($digitsOnly) >= 5) {
                $last10 = strlen($digitsOnly) >= 10 ? substr($digitsOnly, -10) : $digitsOnly;
                $sub->orWhereHas('directSources', function ($dq) use ($digitsOnly, $last10) {
                    $dq->where('owner_phone', 'like', "%{$digitsOnly}%")
                       ->orWhere('owner_phone', 'like', "%{$last10}%");
                })->orWhereHas('creator', function ($uq) use ($digitsOnly, $last10) {
                    $uq->where('phone', 'like', "%{$digitsOnly}%")
                       ->orWhere('whatsapp_number', 'like', "%{$digitsOnly}%")
                       ->orWhere('phone', 'like', "%{$last10}%")
                       ->orWhere('whatsapp_number', 'like', "%{$last10}%");
                });
            }
        });
    }

    /**
     * Apply bilingual Area filter (e.g. from area dropdown).
     */
    public static function applyAreaFilter(Builder $query, string $filterArea): Builder
    {
        $filterArea = trim($filterArea);
        if ($filterArea === '' || strcasecmp($filterArea, 'all') === 0) {
            return $query;
        }

        $variants = [$filterArea];
        foreach (self::AREA_MAP as $canonical => $data) {
            if (strcasecmp($filterArea, $canonical) === 0 || in_array(mb_strtolower($filterArea), array_map('mb_strtolower', $data['aliases']), true)) {
                $variants = array_values(array_unique(array_merge([$canonical], $data['aliases'])));
                break;
            }
        }

        return $query->where(function ($q) use ($variants, $filterArea) {
            $q->whereIn('area', $variants)
              ->orWhere('area', 'like', "%{$filterArea}%");
            foreach ($variants as $v) {
                $q->orWhere('area', 'like', "%{$v}%");
            }
        });
    }

    /**
     * Smart keyword & tag extraction from hidden admin notes.
     *
     * @param string|null $note
     * @return array
     */
    public static function extractKeywordsFromNote(?string $note): array
    {
        if (empty($note)) {
            return [];
        }

        // Normalize Bengali composite characters (nukta vs dedicated letters)
        $note = str_replace(
            ["\xE0\xA6\xAF\xE0\xA6\xBC", "\xE0\xA6\xA1\xE0\xA6\xBC", "\xE0\xA6\xA2\xE0\xA6\xBC"],
            ['য়', 'ড়', 'ঢ়'],
            $note
        );
        if (class_exists(\Normalizer::class)) {
            $note = \Normalizer::normalize($note, \Normalizer::FORM_C);
        }

        $tags = [];

        // 1. Extract explicit hashtags (e.g. #urgent, #জরুরী_বিক্রয়, #corner)
        if (preg_match_all('/#([a-zA-Z0-9_\x{0980}-\x{09FF}]+)/u', $note, $matches)) {
            foreach ($matches[1] as $ht) {
                $cleanHt = str_replace('_', ' ', trim($ht));
                if (mb_strlen($cleanHt) >= 2) {
                    $tags[] = $cleanHt;
                }
            }
        }

        // 2. High-value Bangladesh real estate domain key phrases
        $domainPatterns = [
            'জরুরী বিক্রয়'      => ['জরুরী বিক্রয়', 'জরুরী বিক্রি', 'জরুরি বিক্রয়', 'জরুরি বিক্রি', 'urgent sale', 'urgent sell', 'distress sale', 'কম দাম'],
            'মালিক বিদেশে'      => ['মালিক বিদেশে', 'প্রবাসী মালিক', 'মালিক ইউকে', 'মালিক আমেরিকা', 'মালিক কানাডা', 'owner abroad'],
            'পাওয়ার অব এটর্নি'   => ['পাওয়ার অব অ্যাটর্নি', 'পাওয়ার অব এটর্নি', 'পাওয়ার অফ এটর্নি', 'power of attorney', 'আমমোক্তার'],
            'নামজারি সম্পন্ন'     => ['নামজারি শেষ', 'নামজারি সম্পন্ন', 'নামজারি ও খাজনা', 'খারিজ শেষ', 'খারিজ সম্পন্ন', 'mutation done', 'mutation ok'],
            'খাজনা হালনাগাদ'    => ['খাজনা হালনাগাদ', 'খাজনা পরিশোধ', 'খাজনা ক্লিয়ার', 'খাজনা ক্লিয়ার', 'tax updated'],
            'বায়া দলিল ক্লিয়ার' => ['বায়া দলিল ক্লিয়ার', 'বায়া দলিল ক্লিয়ার', 'বায়া দলিল ওকে', 'বায়া দলিল আছে', 'বায়া দলিল', 'baya dolil'],
            'রাজউক অনুমোদন'    => ['রাজউক অনুমোদন', 'রাজউক প্ল্যান', 'রাজউক পাশ', 'rajuk approved'],
            'ব্যাংক লোন সুবিধা'   => ['ব্যাংক লোন সম্ভব', 'ব্যাংক লোন সুবিধা', 'লোন সুবিধা', 'লোন সম্ভব', 'bank loan available'],
            'সীমানা প্রাচীর'     => ['বাউন্ডারি ওয়াল', 'বাউন্ডারি ওয়াল', 'বাউন্ডারি করা', 'সীমানা প্রাচীর', 'boundary wall done', 'boundary done'],
            'নিষ্কণ্টক জমি'      => ['নিষ্কণ্টক', 'ঝামেলামুক্ত', 'dispute free', 'clear title'],
            'মেইন রোড সংলগ্ন'     => ['মেইন রোড সংলগ্ন', 'মেইন রোডে', 'প্রধান সড়ক', 'main road'],
            'কর্নার প্লট'       => ['কর্নার প্লট', 'কোনার প্লট', 'corner plot', 'corner'],
            'লেক ভিউ'           => ['লেক ভিউ', 'লেক ফেসিং', 'লেক পার', 'লেক সংলগ্ন', 'lake view', 'lake facing'],
            'পার্ক সংলগ্ন'       => ['পার্কের কাছে', 'পার্ক সংলগ্ন', 'পার্ক ভিউ', 'park facing'],
            'মসজিদের কাছে'      => ['মসজিদের কাছে', 'মসজিদ সংলগ্ন', 'near mosque'],
            'দাম ফিক্সড'        => ['দাম ফিক্সড', 'ফিক্সড প্রাইজ', 'ফিক্সড দাম', 'fixed price', 'non negotiable'],
            'আলোচনা সাপেক্ষে'   => ['আলোচনা সাপেক্ষে', 'দাম আলোচনা', 'negotiable'],
            'নগদ টাকা প্রয়োজন'   => ['নগদ টাকা লাগবে', 'ক্যাশ পেমেন্ট', 'cash payment', 'cash only'],
            'ডাইরেক্ট ওনার'      => ['ডাইরেক্ট ওনার', 'সরাসরি মালিক', 'direct owner'],
            'ওয়ারিশান সম্পত্তি'  => ['ওয়ারিশান', 'ওয়ারিশ সনদ', 'ওয়ারিশ সূত্রে'],
        ];

        foreach ($domainPatterns as $canonicalTag => $aliases) {
            foreach ($aliases as $alias) {
                if (mb_stripos($note, $alias) !== false) {
                    $tags[] = $canonicalTag;
                    break;
                }
            }
        }

        // 3. Extract itemized bullets or comma/newline-separated short points
        $lines = preg_split('/[\r\n\t,;•|●\-\*]+/u', $note, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B\"'()[]{}.,;:!?#-");
            $wordCount = count(preg_split('/\s+/u', $line, -1, PREG_SPLIT_NO_EMPTY));
            if ($wordCount >= 2 && $wordCount <= 5 && mb_strlen($line) <= 40) {
                if (!preg_match('/(?:কথা|বলল|বলবেন|হবে|করবেন|আছেন|যাবেন|দেখা|হয়েছে)/u', $line)) {
                    $tags[] = $line;
                }
            }
        }

        // Deduplicate and filter
        $tags = array_values(array_unique(array_filter($tags, fn($t) => mb_strlen(trim($t)) >= 2)));

        return array_slice($tags, 0, 10);
    }
}
