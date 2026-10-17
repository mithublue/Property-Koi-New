<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDirectSource extends Model
{
    protected $fillable = [
        'property_id',
        'source_user_id',
        'source_type',
        'relationship_declaration',
        'owner_name',
        'owner_phone',
        'asking_price',
        'is_primary_source',
        'verification_status',
        'verified_at',
        'verified_by_user_id',
        'verification_notes',
    ];

    protected $casts = [
        'asking_price' => 'decimal:2',
        'is_primary_source' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
