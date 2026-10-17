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
}
