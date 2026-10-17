<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'agency_name',
        'license_no',
        'experience_years',
        'direct_owner_network_count',
        'rating',
        'admin_fixed_rating',
        'penalty_status',
        'penalty_reason',
        'is_verified_direct_agent',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'admin_fixed_rating' => 'decimal:2',
        'is_verified_direct_agent' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEffectiveRatingAttribute(): float
    {
        return (float) ($this->admin_fixed_rating !== null ? $this->admin_fixed_rating : $this->rating);
    }
}
