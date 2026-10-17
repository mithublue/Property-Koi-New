<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyerInteraction extends Model
{
    protected $fillable = [
        'user_id',
        'property_id',
        'interaction_type',
        'offered_price',
        'slot_date',
        'slot_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'offered_price' => 'decimal:2',
        'slot_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
