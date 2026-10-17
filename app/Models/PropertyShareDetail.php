<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyShareDetail extends Model
{
    protected $fillable = [
        'property_id',
        'project_name',
        'developer_name',
        'land_size_katha',
        'building_stories',
        'flat_size_sqft',
        'bedrooms',
        'bathrooms',
        'balconies',
        'share_price',
        'units_per_floor',
        'total_shares',
        'available_shares',
        'has_parking',
        'has_drawing_dining',
        'has_family_living',
        'has_lift',
        'has_generator',
        'has_substation',
        'construction_start_date',
        'construction_duration',
        'current_progress',
        'location_highlights',
    ];

    protected $casts = [
        'land_size_katha' => 'decimal:2',
        'flat_size_sqft' => 'decimal:2',
        'share_price' => 'decimal:2',
        'has_parking' => 'boolean',
        'has_drawing_dining' => 'boolean',
        'has_family_living' => 'boolean',
        'has_lift' => 'boolean',
        'has_generator' => 'boolean',
        'has_substation' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
