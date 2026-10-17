<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'target_user_id',
        'target_type',
        'property_id',
        'reviewer_name',
        'reviewer_phone',
        'rating',
        'review_text',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
