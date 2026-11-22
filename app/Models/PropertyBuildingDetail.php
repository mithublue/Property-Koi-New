<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyBuildingDetail extends Model
{
    protected $fillable = [
        'property_id',
        'building_sub_type',
        'land_size_katha',
        'total_floors',
        'foundation_floors',
        'units_per_floor',
        'total_units',
        'total_built_area_sqft',
        'price_total',
        'monthly_rental_income',
        'building_age_years',
        'handover_year',
        'completion_status',
        'parking_capacity',
        'road_width_ft',
        'approved_plan',
        'plan_approval_authority',
        'gas_connection_type',
        'electricity_type',
        'has_lift',
        'has_generator',
        'has_substation',
        'has_cctv',
        'has_guard_room',
        'has_rooftop_garden',
        'is_corner',
        'facing',
        'occupancy_status',
    ];

    protected $casts = [
        'land_size_katha' => 'decimal:2',
        'total_floors' => 'integer',
        'foundation_floors' => 'integer',
        'units_per_floor' => 'integer',
        'total_units' => 'integer',
        'total_built_area_sqft' => 'decimal:2',
        'price_total' => 'decimal:2',
        'monthly_rental_income' => 'decimal:2',
        'building_age_years' => 'integer',
        'handover_year' => 'integer',
        'parking_capacity' => 'integer',
        'road_width_ft' => 'decimal:1',
        'approved_plan' => 'boolean',
        'has_lift' => 'boolean',
        'has_generator' => 'boolean',
        'has_substation' => 'boolean',
        'has_cctv' => 'boolean',
        'has_guard_room' => 'boolean',
        'has_rooftop_garden' => 'boolean',
        'is_corner' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get human readable Bengali label for building subtype.
     */
    public function getBuildingSubTypeLabelAttribute(): string
    {
        return match ($this->building_sub_type) {
            'residential_building' => 'সম্পূর্ণ আবাসিক ভবন (Multi-Unit)',
            'duplex' => 'ডুপ্লেক্স / ট্রিপ্লেক্স বাড়ি (Duplex Villa)',
            'single_family' => 'একক পারিবারিক বাড়ি (Independent House)',
            'land_with_building' => 'পুরনো বাড়িসহ জমি (Demolition Structure)',
            'commercial_building' => 'কমার্শিয়াল / মিক্সড-ইউজ ভবন',
            default => 'আবাসিক ভবন',
        };
    }

    /**
     * Get gross annual rental yield percentage.
     */
    public function getRentalYieldPercentAttribute(): ?float
    {
        if ($this->price_total > 0 && $this->monthly_rental_income > 0) {
            $annualRent = (float) $this->monthly_rental_income * 12;
            return round(($annualRent / (float) $this->price_total) * 100, 2);
        }
        return null;
    }

    /**
     * Human readable gas connection type.
     */
    public function getGasTypeLabelAttribute(): string
    {
        return match ($this->gas_connection_type) {
            'titas_pipeline', 'pipeline' => 'তিতাস সরকারি গ্যাস পাইপলাইন',
            'cylinder' => 'এলপিজি সিলিন্ডার ব্যবস্থা',
            default => 'গ্যাস লাইন নেই',
        };
    }

    /**
     * Human readable electricity type.
     */
    public function getElectricityTypeLabelAttribute(): string
    {
        return match ($this->electricity_type) {
            'substation' => 'নিজস্ব বিদ্যুৎ সাব-স্টেশন',
            'three_phase' => 'থ্রি-ফেজ সংযোগ',
            'individual_meters' => 'আলাদা ডিজিটাল মিটার',
            default => 'সিঙ্গেল ফেজ',
        };
    }

    /**
     * Human readable occupancy status.
     */
    public function getOccupancyStatusLabelAttribute(): string
    {
        return match ($this->occupancy_status) {
            'fully_rented' => 'শতভাগ ভাড়া দেওয়া (সম্পূর্ণ রেন্টেড)',
            'partially_rented' => 'আংশিক ভাড়া দেওয়া',
            'vacant' => 'সম্পূর্ণ খালি (রেডি টু শিফট)',
            'owner_occupied' => 'মালিক নিজে বসবাস করছেন',
            default => 'ভাড়া দেওয়া',
        };
    }
}
