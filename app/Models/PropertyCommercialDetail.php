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
}
