<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BuyerDemand extends Model
{
    protected $fillable = [
        'requirement_code',
        'user_id',
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'property_type',
        'preferred_areas',
        'preferred_blocks',
        'min_road_width_ft',
        'preferred_facings',
        'min_size',
        'max_size',
        'min_budget',
        'max_budget',
        'purpose',
        'purchase_timeline',
        'status',
        'notify_channels',
        'telegram_chat_id',
        'telegram_username',
        'unsubscribe_token',
        'last_notified_at',
        'match_count',
        'notes',
        'criteria_details',
    ];

    protected $casts = [
        'preferred_areas' => 'array',
        'preferred_blocks' => 'array',
        'preferred_facings' => 'array',
        'notify_channels' => 'array',
        'criteria_details' => 'array',
        'min_size' => 'decimal:2',
        'max_size' => 'decimal:2',
        'min_budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'last_notified_at' => 'datetime',
        'match_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($demand) {
            if (empty($demand->requirement_code)) {
                $demand->requirement_code = 'REQ-' . strtoupper(Str::random(6));
            }
            if (empty($demand->unsubscribe_token)) {
                $demand->unsubscribe_token = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DemandNotification::class);
    }

    public function hasChannel(string $channel): bool
    {
        $channels = explode(',', (string) $this->notify_channels);
        return in_array(trim($channel), array_map('trim', $channels), true);
    }
}
