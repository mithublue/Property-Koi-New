<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyLandDetail extends Model
{
    protected $fillable = [
        'property_id',
        'katha_size',
        'price_total',
        'price_per_katha',
        'is_corner',
        'is_park_facing',
        'is_lake_facing',
        'boundary_wall_done',
        'soil_filling_status',
        'demarcation_ready',
    ];

    protected $casts = [
        'katha_size' => 'decimal:2',
        'price_total' => 'decimal:2',
        'price_per_katha' => 'decimal:2',
        'is_corner' => 'boolean',
        'is_park_facing' => 'boolean',
        'is_lake_facing' => 'boolean',
        'boundary_wall_done' => 'boolean',
        'demarcation_ready' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
