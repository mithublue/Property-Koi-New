<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitBooking extends Model
{
    protected $fillable = [
        'property_id',
        'lead_id',
        'user_id',
        'slot_date',
        'slot_time',
        'buyer_name',
        'buyer_phone',
        'message',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
