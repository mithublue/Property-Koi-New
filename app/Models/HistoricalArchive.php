<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalArchive extends Model
{
    protected $fillable = [
        'property_id',
        'property_type',
        'block',
        'road_width_ft',
        'facing',
        'size_katha_or_sqft',
        'initial_asking_price',
        'final_negotiated_price',
        'sold_price',
        'sold_date',
    ];

    protected $casts = [
        'size_katha_or_sqft' => 'decimal:2',
        'initial_asking_price' => 'decimal:2',
        'final_negotiated_price' => 'decimal:2',
        'sold_price' => 'decimal:2',
        'sold_date' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
