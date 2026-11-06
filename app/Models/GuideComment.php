<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuideComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_id',
        'user_id',
        'parent_id',
        'author_name',
        'author_email',
        'author_phone',
        'comment',
        'is_approved',
        'is_admin_reply',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_approved'    => 'boolean',
        'is_admin_reply' => 'boolean',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GuideComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(GuideComment::class, 'parent_id')->oldest();
    }

    public function approvedReplies(): HasMany
    {
        return $this->hasMany(GuideComment::class, 'parent_id')
            ->where('is_approved', true)
            ->oldest();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function getFormattedDateAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }

        $enMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $bnMonths = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
        $enNums = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bnNums = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        $formatted = $this->created_at->format('d M, Y h:i A');
        $formatted = str_replace($enMonths, $bnMonths, $formatted);
        $formatted = str_replace($enNums, $bnNums, $formatted);
        $formatted = str_replace(['AM', 'PM'], ['সকাল', 'সন্ধ্যা'], $formatted);

        return $formatted;
    }
}
