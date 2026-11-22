<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Expand properties.type column to support 'building'
        Schema::table('properties', function (Blueprint $table) {
            $table->string('type', 50)->default('land')->change();
        });

        // 2. Create Building & House Details table
        Schema::create('property_building_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('properties')->cascadeOnDelete();
            $table->string('building_sub_type', 50)->default('residential_building')->index();
            $table->decimal('land_size_katha', 6, 2)->index();
            $table->integer('total_floors')->default(1)->index();
            $table->integer('foundation_floors')->nullable();
            $table->integer('units_per_floor')->nullable();
            $table->integer('total_units')->nullable()->index();
            $table->decimal('total_built_area_sqft', 10, 2)->nullable();
            $table->decimal('price_total', 15, 2)->index();
            $table->decimal('monthly_rental_income', 15, 2)->nullable()->index();
            $table->integer('building_age_years')->nullable();
            $table->integer('handover_year')->nullable();
            $table->string('completion_status', 50)->default('ready')->index();
            $table->integer('parking_capacity')->default(0);
            $table->decimal('road_width_ft', 5, 1)->nullable();
            $table->boolean('approved_plan')->default(true);
            $table->string('plan_approval_authority', 100)->nullable();
            $table->string('gas_connection_type', 50)->default('none');
            $table->string('electricity_type', 50)->default('three_phase');
            $table->boolean('has_lift')->default(false);
            $table->boolean('has_generator')->default(false);
            $table->boolean('has_substation')->default(false);
            $table->boolean('has_cctv')->default(false);
            $table->boolean('has_guard_room')->default(false);
            $table->boolean('has_rooftop_garden')->default(false);
            $table->boolean('is_corner')->default(false);
            $table->string('facing', 50)->nullable();
            $table->string('occupancy_status', 50)->default('fully_rented');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_building_details');
    }
};
