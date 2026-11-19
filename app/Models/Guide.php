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
            'legal_docs'        => 'দলিল ও আইনি গাইড',
            'market_insights'   => 'মার্কেট অ্যানালাইসিস',
            'buying_guides'     => 'ক্রয়-বিক্রয় গাইডলাইন',
            'investment'        => 'বিনিয়োগ ও ল্যান্ড শেয়ার',
            'finance'           => 'রিয়েল এস্টেট ফাইন্যান্স',
            'rajuk_dap'         => 'রাজউক ও ড্যাপ অনুমোদন',
            'registration_tax'  => 'রেজিস্ট্রেশন ফি ও সরকারি কর',
            'nrb_property'      => 'প্রবাসী (NRB) প্রপার্টি গাইড',
            'rental_tenancy'    => 'বাড়িভাড়া ও ভাড়াটিয়া নীতিমালা',
            'inheritance'       => 'উত্তরাধিকার ও পারিবারিক সম্পত্তি',
            'construction'      => 'বিল্ডিং কনস্ট্রাকশন ও ইঞ্জিনিয়ারিং',
            'interior_design'   => 'ইন্টেরিয়র ডিজাইন ও হোম ডেকর',
            'area_guides'       => 'এরিয়া ও নেইবারহুড রিভিউ',
            'land_survey'       => 'ভূমি জরিপ ও খতিয়ান রেকর্ডস',
            'commercial'        => 'বাণিজ্যিক ও শিল্প প্রপার্টি',
            'uncategorised', '' => 'অশ্রেণিকৃত / সাধারণ',
            default             => $this->category,
        };
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return match ($this->category) {
            'legal_docs'        => 'bg-amber-50 text-amber-800 border-amber-200',
            'market_insights'   => 'bg-sky-50 text-sky-800 border-sky-200',
            'buying_guides'     => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'investment'        => 'bg-purple-50 text-purple-800 border-purple-200',
            'finance'           => 'bg-blue-50 text-blue-800 border-blue-200',
            'rajuk_dap'         => 'bg-rose-50 text-rose-800 border-rose-200',
            'registration_tax'  => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            'nrb_property'      => 'bg-teal-50 text-teal-800 border-teal-200',
            'rental_tenancy'    => 'bg-orange-50 text-orange-800 border-orange-200',
            'inheritance'       => 'bg-red-50 text-red-800 border-red-200',
            'construction'      => 'bg-yellow-50 text-yellow-800 border-yellow-200',
            'interior_design'   => 'bg-pink-50 text-pink-800 border-pink-200',
            'area_guides'       => 'bg-cyan-50 text-cyan-800 border-cyan-200',
            'land_survey'       => 'bg-lime-50 text-lime-800 border-lime-200',
            'commercial'        => 'bg-violet-50 text-violet-800 border-violet-200',
            'uncategorised'     => 'bg-slate-100 text-slate-700 border-slate-300',
            default             => 'bg-teal-50 text-teal-800 border-teal-200',
        };
    }

    public static function getCategoryOptions(): array
    {
        $defaults = [
            'legal_docs'        => 'দলিল ও আইনি গাইড (Legal & Title Docs)',
            'market_insights'   => 'মার্কেট অ্যানালাইসিস (Market Insights)',
            'buying_guides'     => 'ক্রয়-বিক্রয় গাইডলাইন (Buying & Selling Guides)',
            'investment'        => 'বিনিয়োগ ও ল্যান্ড শেয়ার (Investment & Land Share)',
            'finance'           => 'রিয়েল এস্টেট ফাইন্যান্স (Real Estate Finance)',
            'rajuk_dap'         => 'রাজউক ও ড্যাপ অনুমোদন (RAJUK & DAP Approvals)',
            'registration_tax'  => 'রেজিস্ট্রেশন ফি ও সরকারি কর (Registration & Tax)',
            'nrb_property'      => 'প্রবাসী (NRB) প্রপার্টি গাইড (NRB Property Guides)',
            'rental_tenancy'    => 'বাড়িভাড়া ও ভাড়াটিয়া নীতিমালা (Rental & Tenancy)',
            'inheritance'       => 'উত্তরাধিকার ও পারিবারিক সম্পত্তি (Inheritance & Family)',
            'construction'      => 'বিল্ডিং কনস্ট্রাকশন ও ইঞ্জিনিয়ারিং (Building Construction)',
            'interior_design'   => 'ইন্টেরিয়র ডিজাইন ও হোম ডেকর (Interior Design & Decor)',
            'area_guides'       => 'এরিয়া ও নেইবারহুড রিভিউ (Neighborhood & Area Guides)',
            'land_survey'       => 'ভূমি জরিপ ও খতিয়ান রেকর্ডস (Land Survey & Records)',
            'commercial'        => 'বাণিজ্যিক ও শিল্প প্রপার্টি (Commercial Real Estate)',
            'uncategorised'     => 'অশ্রেণিকৃত (Uncategorised)',
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

    public static function getCategoryShortLabels(): array
    {
        return [
            'all'               => 'সব গাইড',
            'legal_docs'        => 'দলিল ও আইনি গাইড',
            'market_insights'   => 'মার্কেট অ্যানালাইসিস',
            'buying_guides'     => 'ক্রয়-বিক্রয় গাইডলাইন',
            'investment'        => 'বিনিয়োগ ও ল্যান্ড শেয়ার',
            'finance'           => 'রিয়েল এস্টেট ফাইন্যান্স',
            'rajuk_dap'         => 'রাজউক ও ড্যাপ অনুমোদন',
            'registration_tax'  => 'রেজিস্ট্রেশন ফি ও কর',
            'nrb_property'      => 'প্রবাসী (NRB) প্রপার্টি',
            'rental_tenancy'    => 'বাড়িভাড়া ও ভাড়াটিয়া',
            'inheritance'       => 'উত্তরাধিকার সম্পত্তি',
            'construction'      => 'বিল্ডিং কনস্ট্রাকশন',
            'interior_design'   => 'ইন্টেরিয়র ডিজাইন',
            'area_guides'       => 'এরিয়া রিভিউ',
            'land_survey'       => 'ভূমি জরিপ ও খতিয়ান',
            'commercial'        => 'বাণিজ্যিক প্রপার্টি',
        ];
    }

    public function getCoverUrlAttribute(): string
    {
        // 1. If an explicit custom image is uploaded or a valid image URL is set
        if (! empty($this->cover_image)) {
            if (! str_contains($this->cover_image, 'assets/images/guide-default')
                && ! str_contains($this->cover_image, 'assets/images/og-')) {
                return Str::startsWith($this->cover_image, ['http://', 'https://'])
                    ? $this->cover_image
                    : asset($this->cover_image);
            }
        }

        // 2. Resolve topic-matched royalty-free photography
        $royaltyFree = GuideImageService::resolveForGuide($this);
        if ($royaltyFree) {
            return $royaltyFree;
        }

        // 3. Fallback to official default thumbnail logo banner if no suitable royalty-free image is found
        return asset('assets/images/guide-default.png');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d M, Y') : '';
    }
}
