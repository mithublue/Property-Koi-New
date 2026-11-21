<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyCommercialDetail extends Model
{
    protected $fillable = [
        'property_id',
        'commercial_type',
        'market_name',
        'shop_number',
        'size_sqft',
        'floor_number',
        'total_floors',
        'price_total',
        'price_per_sqft',
        'frontage_ft',
        'ceiling_height_ft',
        'completion_status',
        'handover_date',
        'monthly_rent_yield',
        'washroom_type',
        'has_lift',
        'has_generator',
        'has_central_ac',
        'has_fire_safety',
        'has_cctv',
        'has_parking',
        'has_escalator',
        'suitable_for',
    ];

    protected $casts = [
        'size_sqft' => 'decimal:2',
        'price_total' => 'decimal:2',
        'price_per_sqft' => 'decimal:2',
        'frontage_ft' => 'decimal:2',
        'ceiling_height_ft' => 'decimal:2',
        'monthly_rent_yield' => 'decimal:2',
        'has_lift' => 'boolean',
        'has_generator' => 'boolean',
        'has_central_ac' => 'boolean',
        'has_fire_safety' => 'boolean',
        'has_cctv' => 'boolean',
        'has_parking' => 'boolean',
        'has_escalator' => 'boolean',
        'handover_date' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getFloorDisplayAttribute(): string
    {
        if (is_numeric($this->floor_number)) {
            $floor = (int) $this->floor_number;
            if ($floor <= 0) {
                return 'নিচতলা (Ground Floor)';
            }
            $tola = $floor + 1;
            $ordinalFloor = match ($floor) {
                1 => '১ম ফ্লোর',
                2 => '২য় ফ্লোর',
                3 => '৩য় ফ্লোর',
                4 => '৪র্থ ফ্লোর',
                5 => '৫ম ফ্লোর',
                6 => '৬ষ্ঠ ফ্লোর',
                7 => '৭ম ফ্লোর',
                8 => '৮ম ফ্লোর',
                9 => '৯ম ফ্লোর',
                10 => '১০ম ফ্লোর',
                default => Property::toBnNumber($floor) . 'তম ফ্লোর',
            };
            $ordinalTola = match ($tola) {
                1 => '১ম তলা',
                2 => '২য় তলা',
                3 => '৩য় তলা',
                4 => '৪র্থ তলা',
                5 => '৫ম তলা',
                6 => '৬ষ্ঠ তলা',
                7 => '৭ম তলা',
                8 => '৮ম তলা',
                9 => '৯ম তলা',
                10 => '১০ম তলা',
                default => Property::toBnNumber($tola) . 'তম তলা',
            };
            return "{$ordinalFloor} ({$ordinalTola})";
        }

        return (string) $this->floor_number;
    }
}
