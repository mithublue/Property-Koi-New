<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Property extends Model
{
    protected $fillable = [
        'property_code',
        'type',
        'title',
        'slug',
        'description',
        'area',
        'sub_area',
        'block',
        'sector',
        'road_number',
        'road_width_ft',
        'plot_number',
        'holding_number',
        'address_visibility',
        'facing',
        'status',
        'is_featured',
        'is_best_deal',
        'is_negotiable',
        'created_by_user_id',
        'latitude',
        'longitude',
        'google_map_pin_url',
        'other_features',
        'fb_caption',
        'fb_post_id',
        'views_count',
        'last_verified_at',
    ];

    protected $casts = [
        'address_visibility' => 'array',
        'other_features' => 'array',
        'is_featured' => 'boolean',
        'is_best_deal' => 'boolean',
        'is_negotiable' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'views_count' => 'integer',
        'last_verified_at' => 'datetime',
    ];

    // Relationships
    public function land(): HasOne
    {
        return $this->hasOne(PropertyLandDetail::class);
    }

    public function flat(): HasOne
    {
        return $this->hasOne(PropertyFlatDetail::class);
    }

    public function share(): HasOne
    {
        return $this->hasOne(PropertyShareDetail::class);
    }

    public function commercial(): HasOne
    {
        return $this->hasOne(PropertyCommercialDetail::class);
    }

    public function landDetail(): HasOne
    {
        return $this->hasOne(PropertyLandDetail::class);
    }

    public function flatDetail(): HasOne
    {
        return $this->hasOne(PropertyFlatDetail::class);
    }

    public function shareDetail(): HasOne
    {
        return $this->hasOne(PropertyShareDetail::class);
    }

    public function commercialDetail(): HasOne
    {
        return $this->hasOne(PropertyCommercialDetail::class);
    }

    public function directSources(): HasMany
    {
        return $this->hasMany(PropertyDirectSource::class);
    }

    public function verification(): HasOne
    {
        return $this->hasOne(PropertyVerification::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(PropertyMedia::class)->orderBy('sort_order');
    }

    public function coverMedia(): HasOne
    {
        return $this->hasOne(PropertyMedia::class)->where('is_cover', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'land' => 'জমি',
            'flat' => 'ফ্ল্যাট',
            'land_share' => 'ল্যান্ড শেয়ার',
            'commercial' => 'দোকান/কমার্শিয়াল',
            default => 'প্রপার্টি',
        };
    }

    public function getCoverUrlAttribute(): string
    {
        $cover = $this->media->firstWhere('is_cover', true) ?? $this->media->first();
        if ($cover && !empty($cover->file_path)) {
            return Str::startsWith($cover->file_path, ['http://', 'https://'])
                ? $cover->file_path
                : asset($cover->file_path);
        }

        return match ($this->type) {
            'flat' => asset('assets/images/og-flat.jpg'),
            'commercial' => asset('assets/images/og-commercial.jpg'),
            default => asset('assets/images/og-land.jpg'),
        };
    }

    public function getRawPriceAttribute(): float
    {
        if ($this->type === 'land' && $this->land) {
            return (float) $this->land->price_total;
        } elseif ($this->type === 'flat' && $this->flat) {
            return (float) $this->flat->price_total;
        } elseif ($this->type === 'land_share' && $this->share) {
            return (float) $this->share->share_price;
        } elseif ($this->type === 'commercial' && $this->commercial) {
            return (float) $this->commercial->price_total;
        }
        return 0.0;
    }

    public function getFormattedPriceAttribute(): string
    {
        $amount = $this->raw_price;
        if ($amount <= 0) {
            return 'আলোচনা সাপেক্ষ';
        }

        if ($amount >= 10000000) {
            $crore = $amount / 10000000;
            return '৳' . number_format($crore, 2, '.', ',') . ' কোটি';
        } elseif ($amount >= 100000) {
            $lakh = $amount / 100000;
            return '৳' . number_format($lakh, 2, '.', ',') . ' লাখ';
        } else {
            return '৳' . number_format($amount, 0, '.', ',');
        }
    }

    public function getAskingPriceAttribute(): ?float
    {
        return $this->raw_price > 0 ? $this->raw_price : null;
    }

    public function getSizeAttribute(): ?float
    {
        if ($this->type === 'land' && $this->landDetail) {
            return (float) $this->landDetail->katha_size;
        } elseif ($this->type === 'flat' && $this->flatDetail) {
            return (float) $this->flatDetail->flat_size_sqft;
        } elseif ($this->type === 'commercial' && $this->commercialDetail) {
            return (float) $this->commercialDetail->size_sqft;
        } elseif ($this->type === 'land_share' && $this->shareDetail) {
            return (float) $this->shareDetail->land_size_katha;
        }
        return null;
    }

    public function getPricePerKathaAttribute(): ?float
    {
        if ($this->type === 'land' && $this->landDetail) {
            return (float) $this->landDetail->price_per_katha;
        }
        return null;
    }

    public static function toBnNumber(int|float|string|null $num): string
    {
        if ($num === null) return '';
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return str_replace($en, $bn, (string) $num);
    }

    public function isAddressVisible(string $field): bool
    {
        $vis = $this->address_visibility;
        if (is_array($vis) && isset($vis[$field])) {
            return (bool) $vis[$field];
        }
        return true;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'live' => 'Live (অনুমোদিত)',
            'pending', 'pending_verification' => 'Pending (পেন্ডিং)',
            'sold' => 'Sold (বিক্রিত)',
            'booked' => 'Booked (বুকড)',
            'draft' => 'Draft (ড্রাফট)',
            'archived' => 'Archived (আর্কাইভ)',
            'verified' => 'Verified (ভেরিফাইড)',
            'rejected' => 'Rejected (বাতিল)',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorClassAttribute(): string
    {
        return match ($this->status) {
            'live' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'pending', 'pending_verification' => 'bg-amber-100 text-amber-800 border-amber-300',
            'sold' => 'bg-slate-800 text-white border-slate-700',
            'booked' => 'bg-blue-100 text-blue-800 border-blue-300',
            'draft' => 'bg-gray-100 text-gray-700 border-gray-300',
            'archived' => 'bg-rose-100 text-rose-800 border-rose-300',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
