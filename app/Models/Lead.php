<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lead extends Model
{
    protected $fillable = [
        'lead_code',
        'property_id',
        'user_id',
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'enquiry_type',
        'stage',
        'offered_price',
        'message',
        'qualification_notes',
        'budget_range',
        'payment_readiness',
        'source_channel',
        'utm_source',
        'assigned_agent_id',
        'assigned_at',
    ];

    protected $casts = [
        'offered_price' => 'decimal:2',
        'assigned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($lead) {
            if (empty($lead->lead_code)) {
                $lead->lead_code = 'LD-' . strtoupper(Str::random(6));
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(SiteVisit::class);
    }
}
