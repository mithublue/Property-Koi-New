<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_code',
        'property_id',
        'lead_id',
        'buyer_name',
        'buyer_phone',
        'seller_type',
        'seller_user_id',
        'agreed_price',
        'platform_fee_percent',
        'platform_fee_amount',
        'fee_status',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'agreed_price' => 'decimal:2',
        'platform_fee_percent' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'closed_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($tx) {
            if (empty($tx->transaction_code)) {
                $tx->transaction_code = 'TXN-' . strtoupper(Str::random(8));
            }
            if (empty($tx->platform_fee_amount) && !empty($tx->agreed_price)) {
                $percent = $tx->platform_fee_percent ?: 0.50;
                $tx->platform_fee_amount = round(($tx->agreed_price * $percent) / 100, 2);
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }
}
