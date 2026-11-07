<?php

namespace App\Models;

use App\Services\GuideImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Guide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'cover_image',
        'reading_time_min',
        'faqs',
        'en_profile',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'is_featured',
        'is_published',
        'synced_to_live_at',
        'views_count',
        'author_name',
    ];

    protected $casts = [
        'faqs' => 'array',
        'en_profile' => 'array',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'synced_to_live_at' => 'datetime',
        'reading_time_min' => 'integer',
        'views_count' => 'integer',
    ];

    public function isSyncedToLive(): bool
    {
        return ! is_null($this->synced_to_live_at);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GuideComment::class);
    }

    public function approvedRootComments(): HasMany
    {
        return $this->hasMany(GuideComment::class)
            ->whereNull('parent_id')
            ->where('is_approved', true)
            ->with(['approvedReplies.user'])
            ->latest('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if (! empty($category) && $category !== 'all') {
            return $query->where('category', $category);
        }

        return $query;
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'legal_docs' => 'দলিল ও আইনি গাইড',
            'market_insights' => 'মার্কেট অ্যানালাইসিস',
            'buying_guides' => 'ক্রয়-বিক্রয় গাইড',
            'investment' => 'বিনিয়োগ ও ল্যান্ড শেয়ার',
            'uncategorised', '' => 'অশ্রেণিকৃত / সাধারণ',
            default => $this->category,
        };
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return match ($this->category) {
            'legal_docs' => 'bg-amber-50 text-amber-800 border-amber-200',
            'market_insights' => 'bg-sky-50 text-sky-800 border-sky-200',
            'buying_guides' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'investment' => 'bg-purple-50 text-purple-800 border-purple-200',
            'uncategorised' => 'bg-slate-100 text-slate-700 border-slate-300',
            default => 'bg-teal-50 text-teal-800 border-teal-200',
        };
    }

    public static function getCategoryOptions(): array
    {
        $defaults = [
            'uncategorised' => 'অশ্রেণিকৃত (Uncategorised)',
            'legal_docs' => 'দলিল ও আইনি গাইড (Legal & Title Docs)',
            'market_insights' => 'মার্কেট অ্যানালাইসিস (Market Insights)',
            'buying_guides' => 'ক্রয়-বিক্রয় গাইডলাইন (Buying & Selling Guides)',
            'investment' => 'বিনিয়োগ ও ল্যান্ড শেয়ার (Investment & Land Share)',
        ];

        try {
            $existing = static::distinct()->pluck('category')->filter()->toArray();
            foreach ($existing as $cat) {
                if (! isset($defaults[$cat]) && ! empty($cat)) {
                    $defaults[$cat] = $cat;
                }
            }
        } catch (\Throwable $e) {
            // Ignore during migrations or initial run
        }

        return $defaults;
    }

    public function getCoverUrlAttribute(): string
    {
        // 1. If an explicit custom image is uploaded and not an external stock/unsplash link
        if (! empty($this->cover_image)) {
            if (str_contains($this->cover_image, 'unsplash.com') ||
                str_contains($this->cover_image, 'assets/images/guide-default') ||
                str_contains($this->cover_image, 'assets/images/og-')) {
                return asset('assets/images/guide-default.png');
            }

            if (Str::startsWith($this->cover_image, ['http://', 'https://'])) {
                return $this->cover_image;
            }

            return asset($this->cover_image);
        }

        // 2. If no thumbnail exists, show default thumbnail
        return asset('assets/images/guide-default.png');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M, Y') : '';
    }
}
