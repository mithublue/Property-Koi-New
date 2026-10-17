/**
 * Aftabnagar Direct Marketplace - Property Text Parser
 * Pure Client-Side Regex/NLP engine to extract real estate details from Bangla, English, and Banglish texts.
 * 0ms latency, zero API calls, 100% free.
 */
(function() {
    'use strict';

    function parsePropertyText(rawText) {
        if (!rawText || typeof rawText !== 'string' || !rawText.trim()) return null;

        // 1. Convert Bangla numerals ০-৯ to 0-9
        let text = rawText.replace(/[০-৯]/g, function(d) {
            return "০১২৩৪৫৬৭৮৯".indexOf(d);
        });

        // 2. Normalize Bengali spoken fractions
        text = text.replace(/(?:আড়াই|আড়াই)/gi, '2.5');
        text = text.replace(/(?:সাড়ে\s*তিন|সাড়ে\s*তিন)/gi, '3.5');
        text = text.replace(/(?:সাড়ে\s*চার|সাড়ে\s*চার)/gi, '4.5');
        text = text.replace(/(?:সাড়ে\s*পাঁচ|সাড়ে\s*পাঁচ)/gi, '5.5');
        text = text.replace(/(?:দেড়|দেড়)/gi, '1.5');

        const detected = {
            description: rawText.trim()
        };
        const summaryList = [];

        // 3. Property Type Detection
        if (/(?:ল্যান্ড\s*শেয়ার|land\s*share|শেয়ার\s*ফ্ল্যাট|share\s*project)/i.test(text)) {
            detected.property_type = 'land_share';
            summaryList.push('ধরন: ল্যান্ড শেয়ার');
        } else if (/(?:দোকান|shop|commercial|বাণিজ্যিক|মার্কেট|স্পেস)/i.test(text)) {
            detected.property_type = 'commercial';
            summaryList.push('ধরন: দোকান / কমার্শিয়াল');
        } else if (/(?:ফ্ল্যাট|flat|অ্যাপার্টমেন্ট|apartment|sqft|sft|স্কয়ার\s*ফিট|স্কয়ারফিট|বেডরুম|বাথরুম)/i.test(text)) {
            detected.property_type = 'flat';
            summaryList.push('ধরন: ফ্ল্যাট');
        } else if (/(?:জমি|প্লট|land|plot|কাঠা|কাটা|katha|khata)/i.test(text)) {
            detected.property_type = 'land';
            summaryList.push('ধরন: জমি / প্লট');
        }

        // 4. Area Detection
        const areaMap = [
            { regex: /(?:বসুন্ধরা|bashundhara)/i, val: 'Bashundhara R/A', bn: 'বসুন্ধরা আ/এ' },
            { regex: /(?:আফতাবনগর|aftabnagar|aftab\s*nagar)/i, val: 'Aftabnagar', bn: 'আফতাবনগর' },
            { regex: /(?:পূর্বাচল|purbachal)/i, val: 'Purbachal', bn: 'পূর্বাচল' },
            { regex: /(?:বনশ্রী|banasree)/i, val: 'Banasree', bn: 'বনশ্রী' },
            { regex: /(?:বাড্ডা|মেরুল|badda)/i, val: 'Badda', bn: 'বাড্ডা' },
            { regex: /(?:গুলশান|gulshan)/i, val: 'Gulshan', bn: 'গুলশান' },
            { regex: /(?:উত্তরা|uttara)/i, val: 'Uttara', bn: 'উত্তরা' },
            { regex: /(?:ধানমন্ডি|dhanmondi)/i, val: 'Dhanmondi', bn: 'ধানমন্ডি' }
        ];
        for (let i = 0; i < areaMap.length; i++) {
            const a = areaMap[i];
            if (a.regex.test(text)) {
                detected.area = a.val;
                summaryList.push('এলাকা: ' + a.bn);
                break;
            }
        }

        // 5. Block Detection (English letter or Bangla transliterated letter)
        const banglaBlockLetters = {
            'এ': 'A', 'বি': 'B', 'সি': 'C', 'ডি': 'D', 'ই': 'E', 'এফ': 'F',
            'জি': 'G', 'এইচ': 'H', 'আই': 'I', 'জে': 'J', 'কে': 'K', 'এল': 'L',
            'এম': 'M', 'এন': 'N', 'পি': 'P', 'কিউ': 'Q'
        };

        const blockMatch = text.match(/(?:ব্লক|ব্লকে|সেক্টর|block|sector)[:\s-]*([A-Za-z])/i)
                        || text.match(/([A-Za-z])[:\s-]*(?:ব্লক|ব্লকে|block)/i);
        if (!blockMatch) {
            const bnMatch = text.match(/(?:ব্লক|ব্লকে|সেক্টর)[:\s-]*([এ-হ]{1,2})/i)
                         || text.match(/([এ-হ]{1,2})[:\s-]*(?:ব্লক|ব্লকে)/i);
            if (bnMatch && banglaBlockLetters[bnMatch[1]]) {
                detected.block = banglaBlockLetters[bnMatch[1]];
                summaryList.push('ব্লক: ' + detected.block);
            }
        } else {
            detected.block = blockMatch[1].toUpperCase();
            summaryList.push('ব্লক: ' + detected.block);
        }

        // Sector if mentioned separately
        const sectorMatch = text.match(/(?:সেক্টর|sector)[:\s#-]*(\d{1,2}|[A-Za-z])/i);
        if (sectorMatch) {
            detected.sector = 'সেক্টর-' + sectorMatch[1];
        }

        // 6. Road Width & Road Number
        const roadWidthMatch = text.match(/(\d{2,3})\s*(?:ফিট|ফুট|ft|feet|')\s*(?:প্রশস্ত|চওড়া|চওড়া|রাস্তা|রোড|road|wide)?/i);
        if (roadWidthMatch) {
            detected.road_width_ft = parseInt(roadWidthMatch[1], 10);
            summaryList.push('রাস্তা: ' + detected.road_width_ft + ' ফিট');
        }

        const roadNumMatch = text.match(/(?:রোড|রাস্তা|road)[:\s#-]*(\d{1,3}[A-Za-z]?)/i);
        if (roadNumMatch && !roadWidthMatch) {
            detected.road_number = roadNumMatch[1];
        }

        // 7. Plot Number
        const plotNumMatch = text.match(/(?:প্লট|plot)[:\s#-]*(\d{1,4}[A-Za-z]?)/i);
        if (plotNumMatch) {
            detected.plot_number = plotNumMatch[1];
            summaryList.push('প্লট নং: ' + detected.plot_number);
        }

        // 8. Size (Katha for land, Sqft for flat/commercial)
        const kathaMatch = text.match(/(\d+(?:\.\d+)?)\s*(?:কাঠা|কাটা|katha|khata|cottha|k\b)/i);
        if (kathaMatch) {
            detected.size = parseFloat(kathaMatch[1]);
            detected.katha_size = parseFloat(kathaMatch[1]);
            summaryList.push('সাইজ: ' + detected.size + ' কাঠা');
        }

        const sqftMatch = text.match(/(\d{3,5})\s*(?:স্কয়ারফিট|স্কয়ার\s*ফিট|বর্গফুট|sqft|sft|sq\s*ft)/i);
        if (sqftMatch) {
            detected.flat_size_sqft = parseFloat(sqftMatch[1]);
            detected.size_sqft = parseFloat(sqftMatch[1]);
            summaryList.push('সাইজ: ' + detected.flat_size_sqft + ' sqft');
        }

        // 9. Facing Detection
        const facingMap = [
            { regex: /(?:দক্ষিণ-পূর্ব|দক্ষিণপূর্ব|south-east|southeast)/i, val: 'south-east', bn: 'দক্ষিণ-পূর্ব' },
            { regex: /(?:উত্তর-পূর্ব|উত্তরপূর্ব|north-east|northeast)/i, val: 'north-east', bn: 'উত্তর-পূর্ব' },
            { regex: /(?:দক্ষিণ-পশ্চিম|দক্ষিণপশ্চিম|south-west|southwest)/i, val: 'south-west', bn: 'দক্ষিণ-পশ্চিম' },
            { regex: /(?:উত্তর-পশ্চিম|উত্তরপশ্চিম|north-west|northwest)/i, val: 'north-west', bn: 'উত্তর-পশ্চিম' },
            { regex: /(?:দক্ষিণমুখী|দক্ষিণমুখ|দক্ষিণ|south\s*facing|south)/i, val: 'south', bn: 'দক্ষিণমুখী' },
            { regex: /(?:পূর্বমুখী|পূর্বমুখ|পূর্ব|east\s*facing|east)/i, val: 'east', bn: 'পূর্বমুখী' },
            { regex: /(?:উত্তরমুখী|উত্তরমুখ|উত্তর|north\s*facing|north)/i, val: 'north', bn: 'উত্তরমুখী' },
            { regex: /(?:পশ্চিমমুখী|পশ্চিমমুখ|পশ্চিম|west\s*facing|west)/i, val: 'west', bn: 'পশ্চিমমুখী' }
        ];
        for (let j = 0; j < facingMap.length; j++) {
            const f = facingMap[j];
            if (f.regex.test(text)) {
                detected.facing = f.val;
                summaryList.push('ফেসিং: ' + f.bn);
                break;
            }
        }

        // 10. Beds, Baths, Balconies
        const bedMatch = text.match(/(\d)\s*(?:টি)?\s*(?:বেডরুম|বেড|শয়নকক্ষ|bed|bedroom|beds|bhk)/i);
        if (bedMatch) {
            detected.bedrooms = parseInt(bedMatch[1], 10);
            summaryList.push(detected.bedrooms + ' বেড');
        }
        const bathMatch = text.match(/(\d)\s*(?:টি)?\s*(?:বাথরুম|বাথ|গোসলখানা|bath|bathroom|baths)/i);
        if (bathMatch) {
            detected.bathrooms = parseInt(bathMatch[1], 10);
            summaryList.push(detected.bathrooms + ' বাথ');
        }
        const balconyMatch = text.match(/(\d)\s*(?:টি)?\s*(?:বারান্দা|বারান্দার|ব্যালকনি|balcony|balconies)/i);
        if (balconyMatch) {
            detected.balconies = parseInt(balconyMatch[1], 10);
            summaryList.push(detected.balconies + ' বারান্দা');
        }

        // 11. Floor Number
        const floorMatch = text.match(/(\d{1,2})\s*(?:তম|ম|র্থ|ষ্ঠ|st|nd|rd|th)?\s*(?:তলা|তলায়|ফ্লোর|floor)/i);
        if (floorMatch) {
            detected.floor_number = parseInt(floorMatch[1], 10);
            summaryList.push(detected.floor_number + 'ম তলা');
        }

        // 12. Price Parsing
        // Compound: "X কোটি Y লাখ"
        const compoundCrLac = text.match(/(\d+(?:\.\d+)?)\s*(?:কোটি|crore|cr)\s*(\d+(?:\.\d+)?)\s*(?:লাখ|লাক|লক্ষ|lac|lakh)/i);
        const isPerKathaContext = /(?:প্রতি\s*কাঠা|কাঠা\s*প্রতি|per\s*katha)/i.test(text);

        if (compoundCrLac) {
            const compoundVal = (parseFloat(compoundCrLac[1]) * 10000000) + (parseFloat(compoundCrLac[2]) * 100000);
            if (isPerKathaContext) {
                detected.price_per_katha = compoundVal;
                summaryList.push('প্রতি কাঠা: ' + compoundCrLac[1] + ' কোটি ' + compoundCrLac[2] + ' লাখ');
            } else {
                detected.asking_price = compoundVal;
                summaryList.push('মূল্য: ' + compoundCrLac[1] + ' কোটি ' + compoundCrLac[2] + ' লাখ');
            }
        } else {
            // Per katha
            const perKathaCr = text.match(/(?:প্রতি\s*কাঠা|কাঠা\s*প্রতি|per\s*katha)[^\d]*(\d+(?:\.\d+)?)\s*(?:কোটি|crore|cr)/i)
                            || text.match(/(\d+(?:\.\d+)?)\s*(?:কোটি|crore|cr)\s*(?:প্রতি\s*কাঠা|কাঠা|per\s*katha)/i);
            const perKathaLac = text.match(/(?:প্রতি\s*কাঠা|কাঠা\s*প্রতি|per\s*katha)[^\d]*(\d+(?:\.\d+)?)\s*(?:লাখ|লাক|লক্ষ|lac|lakh|l\b)/i)
                             || text.match(/(\d+(?:\.\d+)?)\s*(?:লাখ|লাক|লক্ষ|lac|lakh|l\b)\s*(?:প্রতি\s*কাঠা|কাঠা|per\s*katha)/i);

            if (perKathaCr) {
                detected.price_per_katha = parseFloat(perKathaCr[1]) * 10000000;
                summaryList.push('প্রতি কাঠা: ' + perKathaCr[1] + ' কোটি');
            } else if (perKathaLac) {
                detected.price_per_katha = parseFloat(perKathaLac[1]) * 100000;
                summaryList.push('প্রতি কাঠা: ' + perKathaLac[1] + ' লাখ');
            }

            // Per sqft
            const perSqftMatch = text.match(/(?:প্রতি\s*স্কয়ারফিট|প্রতি\s*sqft|per\s*sqft|per\s*sft)[^\d]*(\d{3,6})/i)
                              || text.match(/(\d{3,6})\s*(?:টাকা|tk)?\s*(?:প্রতি\s*স্কয়ারফিট|per\s*sqft|per\s*sft)/i);
            if (perSqftMatch) {
                detected.price_per_sqft = parseFloat(perSqftMatch[1]);
                summaryList.push('প্রতি sqft: ৳' + detected.price_per_sqft);
            }

            // Total price
            const totalCr = text.match(/(?:মোট\s*দাম|মোট\s*মূল্য|টোটাল|সর্বমোট|দাম|মূল্য|price|asking|package)[^\d]*(\d+(?:\.\d+)?)\s*(?:কোটি|crore|cr)/i);
            const totalLac = text.match(/(?:মোট\s*দাম|মোট\s*মূল্য|টোটাল|সর্বমোট|দাম|মূল্য|price|asking)[^\d]*(\d+(?:\.\d+)?)\s*(?:লাখ|লাক|লক্ষ|lac|lakh)/i);

            if (totalCr) {
                detected.asking_price = parseFloat(totalCr[1]) * 10000000;
                summaryList.push('মোট মূল্য: ' + totalCr[1] + ' কোটি');
            } else if (totalLac) {
                detected.asking_price = parseFloat(totalLac[1]) * 100000;
                summaryList.push('মোট মূল্য: ' + totalLac[1] + ' লাখ');
            } else if (!detected.price_per_katha) {
                const crAny = text.match(/(\d+(?:\.\d+)?)\s*(?:কোটি|crore|cr)/i);
                if (crAny) {
                    detected.asking_price = parseFloat(crAny[1]) * 10000000;
                    summaryList.push('মূল্য: ' + crAny[1] + ' কোটি');
                }
            }
        }

        // Auto multiply asking price if size and unit price are given
        if (detected.size && detected.price_per_katha && !detected.asking_price) {
            detected.asking_price = detected.size * detected.price_per_katha;
        }
        if (detected.flat_size_sqft && detected.price_per_sqft && !detected.asking_price) {
            detected.asking_price = detected.flat_size_sqft * detected.price_per_sqft;
        }

        // 13. Features & Amenities
        if (/(?:কর্নার|corner|২\s*দিক\s*খোলা|3\s*দিক\s*খোলা)/i.test(text)) {
            detected.is_corner = true;
            detected.corner_plot = 1;
            summaryList.push('কর্নার প্লট');
        }
        if (/(?:লেকভিউ|লেক\s*ভিউ|লেক\s*ফেসিং|lake\s*view|lake\s*facing)/i.test(text)) {
            detected.is_lake_facing = true;
            detected.lake_facing = 1;
            summaryList.push('লেকভিউ');
        }
        if (/(?:বাউন্ডারি|সীমানা\s*প্রাচীর|boundary|boundary\s*wall)/i.test(text)) {
            detected.boundary_wall_done = true;
            detected.boundary_wall = 1;
            summaryList.push('বাউন্ডারি করা');
        }
        if (/(?:ভরাট|বালু\s*ভরাট|রেডি\s*প্লট|মাটি\s*ভরাট|filled|ready\s*plot)/i.test(text)) {
            detected.soil_filling_status = 'completed';
            detected.soil_filling = 'ready';
            summaryList.push('ভরাট রেডি');
        }
        if (/(?:লিফট|lift|elevator)/i.test(text)) {
            detected.has_lift = true;
            summaryList.push('লিফট');
        }
        if (/(?:জেনারেটর|generator|power\s*backup)/i.test(text)) {
            detected.has_generator = true;
            summaryList.push('জেনারেটর');
        }
        if (/(?:তিতাস\s*গ্যাস|গ্যাস|gas|titas)/i.test(text)) {
            detected.has_gas = true;
            summaryList.push('গ্যাস সংযোগ');
        }
        if (/(?:সাবস্টেশন|substation|transformer)/i.test(text)) {
            detected.has_substation = true;
        }
        if (/(?:পার্কিং|গ্যারেজ|parking|garage)/i.test(text)) {
            detected.parking_available = true;
            detected.has_parking = true;
            summaryList.push('পার্কিং সহ');
        }
        if (/(?:রেডি\s*ফ্ল্যাট|রেডি\s*টু\s*শিফট|ready\s*flat|ready)/i.test(text)) {
            detected.completion_status = 'ready';
            detected.handover_status = 'ready';
        }

        // 13.5. Extra Amenities & Other Features detection
        const extraFeatures = [];
        if (/(?:ওয়াইড\s*ড্রেনেজ|ড্রেনেজ|drainage)/i.test(text)) {
            extraFeatures.push('ওয়াইড ড্রেনেজ ব্যবস্থা');
        }
        if (/(?:বিদ্যুৎ\s*সংযোগ|বিদ্যুৎ|electricity)/i.test(text)) {
            extraFeatures.push('বিদ্যুৎ সংযোগ');
        }
        if (/(?:নিরাপদ\s*পরিবেশ|আবাসিক\s*পরিবেশ|residential\s*area)/i.test(text)) {
            extraFeatures.push('নিরাপদ আবাসিক পরিবেশ');
        }
        if (/(?:সিসিটিভি|cctv)/i.test(text)) {
            extraFeatures.push('CCTV নজরদারি');
            summaryList.push('সিসিটিভি');
        }
        if (/(?:খেলার\s*মাঠ|playground|play\s*zone)/i.test(text)) {
            extraFeatures.push('খেলার মাঠ');
            summaryList.push('খেলার মাঠ');
        }
        if (/(?:মসজিদ|mosque)/i.test(text)) {
            extraFeatures.push('মসজিদ সংলগ্ন');
            summaryList.push('মসজিদ সংলগ্ন');
        }
        if (/(?:গার্ড\s*রুম|সিকিউরিটি\s*গার্ড|security\s*guard)/i.test(text)) {
            extraFeatures.push('২৪/৭ সিকিউরিটি গার্ড');
            summaryList.push('সিকিউরিটি গার্ড');
        }
        if (/(?:ইন্টারকম|intercom)/i.test(text)) {
            extraFeatures.push('ইন্টারকম সুবিধা');
        }

        if (extraFeatures.length > 0) {
            detected.other_features = extraFeatures.map(function(label) {
                return { label: label, checked: true };
            });
        }

        // 14. Auto-generate Title
        const areaName = detected.area || '';
        const blockName = detected.block ? ('ব্লক ' + detected.block) : '';

        if (detected.property_type === 'land') {
            const parts = [];
            if (areaName) parts.push(areaName);
            if (blockName) parts.push(blockName);
            if (detected.size) parts.push(detected.size + ' কাঠা');
            if (detected.facing) {
                const fLabel = detected.facing === 'south' ? 'দক্ষিণমুখী' : (detected.facing === 'north' ? 'উত্তরমুখী' : (detected.facing === 'east' ? 'পূর্বমুখী' : (detected.facing === 'west' ? 'পশ্চিমমুখী' : detected.facing)));
                parts.push(fLabel);
            }
            if (parts.length > 0) {
                detected.title = parts.join(' ') + ' প্লট বিক্রয়';
            }
        } else if (detected.property_type === 'flat') {
            const parts = [];
            if (areaName) parts.push(areaName);
            if (blockName) parts.push(blockName);
            if (detected.flat_size_sqft) parts.push(detected.flat_size_sqft + ' sft');
            if (detected.bedrooms) parts.push(detected.bedrooms + ' বেড');
            if (parts.length > 0) {
                detected.title = parts.join(' ') + ' ফ্ল্যাট বিক্রয়';
            }
        } else if (detected.property_type === 'land_share') {
            const parts = [];
            if (areaName) parts.push(areaName);
            if (blockName) parts.push(blockName);
            parts.push('ল্যান্ড শেয়ার প্রজেক্ট');
            detected.title = parts.join(' ');
        }

        // 15. Auto-generate Facebook Caption
        detected.fb_caption = buildBengaliCaption(detected, rawText);

        return {
            detected: detected,
            summaryList: summaryList
        };
    }

    /**
     * Builds an attractive Bengali Facebook Caption based on detected attributes.
     */
    function buildBengaliCaption(d, originalText) {
        var lines = [];
        var title = d.title || 'আফতাবনগরে আকর্ষণীয় নতুন প্রপার্টি';
        lines.push('🏡 ' + title);
        
        var typeBn = 'প্রপার্টি';
        if (d.property_type === 'land') typeBn = 'জমি / প্লট (Land Plot)';
        else if (d.property_type === 'flat') typeBn = 'রেডি ফ্ল্যাট (Ready Flat)';
        else if (d.property_type === 'land_share') typeBn = 'ল্যান্ড শেয়ার প্রজেক্ট (Land Share)';
        else if (d.property_type === 'commercial') typeBn = 'কমার্শিয়াল স্পেস (Commercial)';
        lines.push('📌 ক্যাটাগরি: ' + typeBn);

        var locParts = [];
        if (d.area) locParts.push(d.area);
        if (d.block) locParts.push('ব্লক ' + d.block);
        if (d.sector) locParts.push(d.sector);
        if (d.road_number) locParts.push('রোড #' + d.road_number);
        if (locParts.length > 0) {
            lines.push('📍 লোকেশন: ' + locParts.join(', '));
        }

        if (d.property_type === 'land' && (d.size || d.katha_size)) {
            lines.push('📐 সাইজ: ' + (d.size || d.katha_size) + ' কাঠা');
        } else if (d.flat_size_sqft) {
            lines.push('📐 সাইজ: ' + d.flat_size_sqft + ' বর্গফুট');
        } else if (d.size_sqft) {
            lines.push('📐 সাইজ: ' + d.size_sqft + ' বর্গফুট');
        }

        if (d.facing) {
            var facingMap = {
                'south': 'দক্ষিণমুখী',
                'north': 'উত্তরমুখী',
                'east': 'পূর্বমুখী',
                'west': 'পশ্চিমমুখী',
                'north-east': 'উত্তর-পূর্বমুখী',
                'south-east': 'দক্ষিণ-পূর্বমুখী',
                'north-west': 'উত্তর-পশ্চিমমুখী',
                'south-west': 'দক্ষিণ-পশ্চিমমুখী'
            };
            lines.push('🧭 ফেসিং: ' + (facingMap[d.facing] || d.facing));
        }

        if (d.road_width_ft) {
            lines.push('🛣️ সামনের রাস্তা: ' + d.road_width_ft + ' ফুট চওড়া');
        }

        if (d.bedrooms || d.bathrooms) {
            var roomInfo = [];
            if (d.bedrooms) roomInfo.push(d.bedrooms + ' বেডরুম');
            if (d.bathrooms) roomInfo.push(d.bathrooms + ' বাথরুম');
            if (d.balconies) roomInfo.push(d.balconies + ' বারান্দা');
            if (roomInfo.length > 0) lines.push('🛏️ রুম কনফিগারেশন: ' + roomInfo.join(', '));
        }

        if (d.floor_number) {
            lines.push('🏢 ফ্লোর: ' + d.floor_number + 'ম তলা');
        }

        // Highlights & Amenities
        var feats = [];
        if (d.is_corner) feats.push('কর্নার প্লট');
        if (d.is_lake_facing) feats.push('লেকভিউ');
        if (d.boundary_wall_done) feats.push('বাউন্ডারি ওয়াল করা');
        if (d.has_lift) feats.push('লিফট সুবিধা');
        if (d.parking_available) feats.push('পার্কিং ব্যবস্থা');
        if (d.has_generator) feats.push('জেনারেটর ব্যাকআপ');
        if (d.has_gas) feats.push('গ্যাস সংযোগ');
        if (feats.length > 0) {
            lines.push('✨ বিশেষ সুবিধা: ' + feats.join(' • '));
        }

        // Price formatting
        if (d.asking_price && Number(d.asking_price) > 0) {
            var pr = Number(d.asking_price);
            var prStr = (pr >= 10000000) ? ('৳' + (pr / 10000000).toFixed(2) + ' কোটি') : ((pr >= 100000) ? ('৳' + (pr / 100000).toFixed(2) + ' লাখ') : ('৳' + pr.toLocaleString('bn-BD')));
            lines.push('💰 মূল্য: ' + prStr + (d.is_negotiable ? ' (আলোচনা সাপেক্ষ)' : ''));
        } else if (d.price_per_katha && Number(d.price_per_katha) > 0) {
            var pk = Number(d.price_per_katha);
            var pkStr = (pk >= 10000000) ? ('৳' + (pk / 10000000).toFixed(2) + ' কোটি/কাঠা') : ('৳' + (pk / 100000).toFixed(2) + ' লাখ/কাঠা');
            lines.push('💰 প্রতি কাঠা মূল্য: ' + pkStr);
        } else {
            lines.push('💰 মূল্য: আলোচনা সাপেক্ষ (সরাসরি যোগাযোগের মাধ্যমে নির্ধারিত)');
        }

        lines.push('');
        lines.push('📞 সরাসরি মালিক/অথরাইজড এজেন্টের সাথে কথা বলতে ও সাইট ভিজিট করতে ইনবক্স করুন অথবা হোয়াটসঅ্যাপে যোগাযোগ করুন।');
        lines.push('🌐 শতভাগ ভেরিফাইড প্রপার্টি ডিল — সরাসরি ও নিরাপদ!');
        return lines.join('\n');
    }

    /**
     * Scans form inputs [x-model] and/or dataModel to produce an empty JSON template.
     */
    function generateSchema(formRootElement, dataModel) {
        const template = {};

        // 1. Scan DOM inputs for exact field names in active form
        if (formRootElement && typeof formRootElement.querySelectorAll === 'function') {
            const elements = formRootElement.querySelectorAll('[x-model]');
            elements.forEach(function(el) {
                const modelExpr = el.getAttribute('x-model');
                if (!modelExpr) return;
                // Clean up prefixes (formData. or editForm.)
                const cleanPath = modelExpr.replace(/^(?:formData|editForm)\./, '');
                if (!cleanPath) return;
                // Ignore UI-only flags and sub-repeatable fields like feat.label or admin_feat
                if (cleanPath.indexOf('smart') === 0 || cleanPath.indexOf('adminSmart') === 0 || cleanPath.indexOf('json') === 0 || cleanPath.indexOf('feat') === 0 || cleanPath.indexOf('admin_feat') === 0) return;

                if (cleanPath.indexOf('.') !== -1) {
                    const parts = cleanPath.split('.');
                    if (!template[parts[0]] || typeof template[parts[0]] !== 'object') {
                        template[parts[0]] = {};
                    }
                    template[parts[0]][parts[1]] = "";
                } else {
                    template[cleanPath] = "";
                }
            });
        }

        // 2. Scan dataModel to ensure all standard property attributes exist
        if (dataModel && typeof dataModel === 'object') {
            const ignoredKeys = {
                'isDevMode': true, 'submitted': true, 'isSubmitting': true, 'message': true,
                'policyModalOpen': true, 'lastEditedPriceField': true, 'showSmartBox': true,
                'smartRawText': true, 'smartExtractedSummary': true, 'activeSmartTab': true,
                'jsonTemplateText': true, 'jsonInputText': true, 'jsonCopySuccess': true,
                'adminShowSmartBox': true, 'adminSmartRawText': true, 'adminSmartSummary': true,
                'adminJsonTemplateText': true, 'adminJsonInputText': true, 'adminActiveSmartTab': true,
                'adminJsonCopySuccess': true, 'loadingProperty': true, 'editModalOpen': true,
                'editMsg': true, 'editSuccess': true, 'activeTab': true, 'mediaList': true
            };

            for (const k in dataModel) {
                if (ignoredKeys[k] || typeof dataModel[k] === 'function') continue;
                if (k === 'other_features') {
                    if (!template[k]) {
                        if (Array.isArray(dataModel.other_features) && dataModel.other_features.length > 0) {
                            template[k] = dataModel.other_features.map(function(f) {
                                return (typeof f === 'string') ? f : (f.label || '');
                            }).filter(Boolean);
                        } else {
                            template[k] = [
                                "বিদ্যুৎ সংযোগ",
                                "ওয়াইড ড্রেনেজ ব্যবস্থা",
                                "নিরাপদ আবাসিক পরিবেশ"
                            ];
                        }
                    }
                } else if (typeof dataModel[k] === 'object' && dataModel[k] !== null && !Array.isArray(dataModel[k])) {
                    if (!template[k]) template[k] = {};
                    for (const subK in dataModel[k]) {
                        if (typeof dataModel[k][subK] !== 'function') {
                            template[k][subK] = "";
                        }
                    }
                } else if (template[k] === undefined) {
                    template[k] = "";
                }
            }
        }

        // Explicitly ensure fb_caption is present in schema template
        if (template['fb_caption'] === undefined) {
            template['fb_caption'] = "";
        }

        return template;
    }

    /**
     * Helper to normalize other_features from various formats (string array, object array, comma string)
     */
    function normalizeOtherFeatures(val) {
        if (!val) return [];
        var normalized = [];
        if (typeof val === 'string') {
            normalized = val.split(/[,;\n]+/).map(function(s) {
                return s.trim();
            }).filter(Boolean).map(function(s) {
                return { label: s, checked: true };
            });
        } else if (Array.isArray(val)) {
            normalized = val.map(function(item) {
                if (typeof item === 'string') {
                    return { label: item.trim(), checked: true };
                } else if (typeof item === 'object' && item !== null) {
                    var lbl = item.label || item.feature || item.name || item.title || item.text || '';
                    var chk = (item.checked !== undefined) ? (item.checked === true || item.checked === 'true' || item.checked === 1) : true;
                    return { label: String(lbl).trim(), checked: chk };
                }
                return { label: String(item).trim(), checked: true };
            }).filter(function(item) {
                return item.label !== '';
            });
        } else if (typeof val === 'object' && val !== null) {
            normalized = Object.values(val).map(function(item) {
                if (typeof item === 'string') return { label: item.trim(), checked: true };
                if (typeof item === 'object' && item !== null) {
                    var lbl = item.label || item.feature || item.name || item.title || item.text || '';
                    return { label: String(lbl).trim(), checked: true };
                }
                return null;
            }).filter(Boolean);
        }
        return normalized;
    }

    /**
     * Parses a JSON string and populates the given targetModel (formData or editForm).
     */
    function populateFromJSON(jsonInput, targetModel) {
        if (!jsonInput || (typeof jsonInput === 'string' && !jsonInput.trim())) {
            throw new Error('অনুগ্রহ করে পূরণকৃত JSON টেক্সট পেস্ট করুন।');
        }
        var data = jsonInput;
        if (typeof data === 'string') {
            try {
                data = JSON.parse(jsonInput);
            } catch (e) {
                throw new Error('ভুল JSON সিনট্যাক্স! সঠিক JSON কোড পেস্ট করুন (' + e.message + ')');
            }
        }
        if (typeof data !== 'object' || data === null || Array.isArray(data)) {
            throw new Error('JSON ফরম্যাটটি অবশ্যই একটি অবজেক্ট { ... } হতে হবে।');
        }

        var populatedCount = 0;
        var populatedKeys = [];

        for (var key in data) {
            var val = data[key];
            if (val === undefined || val === null) continue;

            // Handle feature key aliases
            var targetKey = key;
            if ((key === 'features' || key === 'other_features_list' || key === 'amenities') && !targetModel.hasOwnProperty(key) && targetModel.hasOwnProperty('other_features')) {
                targetKey = 'other_features';
            }

            // Handle fb_caption aliases (caption, facebook_caption, fb_post_caption)
            if ((key === 'caption' || key === 'facebook_caption' || key === 'fb_post_caption') && !targetModel.hasOwnProperty(key) && targetModel.hasOwnProperty('fb_caption')) {
                targetKey = 'fb_caption';
            }

            // Special normalization for other_features
            if (targetKey === 'other_features') {
                targetModel.other_features = normalizeOtherFeatures(val);
                populatedCount++;
                populatedKeys.push('other_features');
                continue;
            }

            // Type-casting string numbers/booleans if matching target
            if (typeof val === 'string') {
                var trimmed = val.trim();
                if (trimmed !== '') {
                    if (typeof targetModel[targetKey] === 'number') {
                        var num = Number(trimmed);
                        if (!isNaN(num)) val = num;
                    } else if (typeof targetModel[targetKey] === 'boolean') {
                        if (trimmed.toLowerCase() === 'true' || trimmed === '1') val = true;
                        if (trimmed.toLowerCase() === 'false' || trimmed === '0') val = false;
                    }
                }
            }

            // Object merge for nested objects (e.g. address_visibility)
            if (typeof val === 'object' && !Array.isArray(val) && typeof targetModel[targetKey] === 'object' && targetModel[targetKey] !== null) {
                targetModel[targetKey] = Object.assign({}, targetModel[targetKey], val);
            } else {
                targetModel[targetKey] = val;
            }

            populatedCount++;
            populatedKeys.push(targetKey);
        }

        // Auto-generate fb_caption if targetModel has fb_caption property and it's empty after import
        if (targetModel.hasOwnProperty('fb_caption') && (!targetModel.fb_caption || !String(targetModel.fb_caption).trim())) {
            var autoCap = buildBengaliCaption({
                title: targetModel.title || data.title,
                property_type: targetModel.property_type || targetModel.type || data.property_type || data.type,
                area: targetModel.area || data.area,
                block: targetModel.block || data.block,
                sector: targetModel.sector || data.sector,
                road_number: targetModel.road_number || data.road_number,
                road_width_ft: targetModel.road_width_ft || data.road_width_ft,
                size: targetModel.size || targetModel.katha_size || data.size || data.katha_size,
                flat_size_sqft: targetModel.flat_size_sqft || data.flat_size_sqft,
                size_sqft: targetModel.size_sqft || data.size_sqft,
                facing: targetModel.facing || data.facing,
                bedrooms: targetModel.bedrooms || data.bedrooms,
                bathrooms: targetModel.bathrooms || data.bathrooms,
                balconies: targetModel.balconies || data.balconies,
                floor_number: targetModel.floor_number || data.floor_number,
                asking_price: targetModel.asking_price || targetModel.price || data.asking_price || data.price,
                price_per_katha: targetModel.price_per_katha || data.price_per_katha,
                is_negotiable: targetModel.is_negotiable,
                is_corner: targetModel.is_corner || targetModel.corner_plot,
                is_lake_facing: targetModel.is_lake_facing || targetModel.lake_facing,
                boundary_wall_done: targetModel.boundary_wall_done || targetModel.boundary_wall,
                has_lift: targetModel.has_lift,
                parking_available: targetModel.parking_available || targetModel.has_parking,
                has_generator: targetModel.has_generator,
                has_gas: targetModel.has_gas
            }, '');
            if (autoCap) {
                targetModel.fb_caption = autoCap;
                populatedCount++;
                populatedKeys.push('fb_caption');
            }
        }

        return {
            success: true,
            count: populatedCount,
            populatedKeys: populatedKeys,
            data: data
        };
    }

    // Expose to window globally
    window.AdmPropertyTextParser = {
        parse: parsePropertyText,
        generateSchema: generateSchema,
        populateFromJSON: populateFromJSON
    };
    window.parsePropertyText = parsePropertyText;
})();

