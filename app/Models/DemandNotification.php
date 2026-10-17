<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandNotification extends Model
{
    protected $fillable = [
        'buyer_demand_id',
        'property_id',
        'user_id',
        'match_score',
        'channel',
        'status',
        'delivery_details',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
    ];

    public function buyerDemand(): BelongsTo
    {
        return $this->belongsTo(BuyerDemand::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
