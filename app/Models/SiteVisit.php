<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    protected $fillable = [
        'lead_id',
        'property_id',
        'preferred_date',
        'preferred_time_slot',
        'scheduled_at',
        'field_coordinator_id',
        'assigned_agent_id',
        'status',
        'feedback',
        'outcome',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'scheduled_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function fieldCoordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'field_coordinator_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }
}
