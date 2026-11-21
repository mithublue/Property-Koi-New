<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyFlatDetail extends Model
{
    protected $fillable = [
        'property_id',
        'developer_name',
        'project_name',
        'building_type',
        'completion_status',
        'handover_date',
        'flat_size_sqft',
        'floor_number',
        'total_floors',
        'bedrooms',
        'bathrooms',
        'balconies',
        'price_total',
        'price_per_sqft',
        'parking_available',
        'garage_price',
        'utility_price',
        'down_payment',
        'has_lift',
        'has_generator',
        'has_substation',
        'has_gas',
        'land_share_size',
    ];

    protected $casts = [
        'flat_size_sqft' => 'decimal:2',
        'price_total' => 'decimal:2',
        'price_per_sqft' => 'decimal:2',
        'garage_price' => 'decimal:2',
        'utility_price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'land_share_size' => 'decimal:2',
        'parking_available' => 'boolean',
        'has_lift' => 'boolean',
        'has_generator' => 'boolean',
        'has_substation' => 'boolean',
        'has_gas' => 'boolean',
        'handover_date' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getFloorDisplayAttribute(): string
    {
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
}
