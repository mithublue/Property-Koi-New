<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Land Details
        Schema::create('property_land_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('properties')->cascadeOnDelete();
            $table->decimal('katha_size', 6, 2)->index();
            $table->decimal('price_total', 15, 2)->index();
            $table->decimal('price_per_katha', 15, 2)->index();
            $table->boolean('is_corner')->default(false)->index();
            $table->boolean('is_park_facing')->default(false);
            $table->boolean('is_lake_facing')->default(false)->index();
            $table->boolean('boundary_wall_done')->default(false);
            $table->enum('soil_filling_status', ['filled', 'partial', 'unfilled'])->default('filled');
            $table->boolean('demarcation_ready')->default(true);
            $table->timestamps();
        });

        // 2. Flat Details
        Schema::create('property_flat_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('properties')->cascadeOnDelete();
            $table->string('developer_name')->nullable();
            $table->string('project_name')->nullable();
            $table->string('building_type', 50)->default('multi_unit');
            $table->enum('completion_status', ['ready', 'ongoing', 'under_construction'])->default('ready')->index();
            $table->date('handover_date')->nullable();
            $table->decimal('flat_size_sqft', 8, 2)->index();
            $table->integer('floor_number')->default(1);
            $table->integer('total_floors')->default(6);
            $table->integer('bedrooms')->default(3)->index();
            $table->integer('bathrooms')->default(3);
            $table->integer('balconies')->default(2);
            $table->decimal('price_total', 15, 2)->index();
            $table->decimal('price_per_sqft', 15, 2);
            $table->boolean('parking_available')->default(true);
            $table->decimal('garage_price', 15, 2)->default(0.00);
            $table->decimal('utility_price', 15, 2)->default(0.00);
            $table->decimal('down_payment', 15, 2)->nullable();
            $table->boolean('has_lift')->default(true);
            $table->boolean('has_generator')->default(true);
            $table->boolean('has_substation')->default(false);
            $table->boolean('has_gas')->default(false);
            $table->decimal('land_share_size', 6, 2)->nullable();
            $table->timestamps();
        });

        // 3. Share Details (Land Share / Joint Venture)
        Schema::create('property_share_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('properties')->cascadeOnDelete();
            $table->string('project_name')->nullable();
            $table->string('developer_name')->nullable();
            $table->decimal('land_size_katha', 6, 2)->nullable();
            $table->string('building_stories', 50)->nullable();
            $table->decimal('flat_size_sqft', 8, 2)->nullable();
            $table->string('bedrooms', 50)->nullable();
            $table->string('bathrooms', 50)->nullable();
            $table->string('balconies', 50)->nullable();
            $table->decimal('share_price', 15, 2)->nullable();
            $table->string('units_per_floor', 50)->nullable();
            $table->integer('total_shares')->nullable();
            $table->integer('available_shares')->nullable();
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_drawing_dining')->default(false);
            $table->boolean('has_family_living')->default(false);
            $table->boolean('has_lift')->default(false);
            $table->boolean('has_generator')->default(false);
            $table->boolean('has_substation')->default(false);
            $table->string('construction_start_date', 100)->nullable();
            $table->string('construction_duration', 100)->nullable();
            $table->string('current_progress', 100)->nullable();
            $table->text('location_highlights')->nullable();
            $table->timestamps();
        });

        // 4. Commercial Details (Shops & Offices)
        Schema::create('property_commercial_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('properties')->cascadeOnDelete();
            $table->string('commercial_type', 50)->default('shop');
            $table->string('market_name')->nullable();
            $table->string('shop_number', 50)->nullable();
            $table->decimal('size_sqft', 8, 2);
            $table->string('floor_number', 50)->default('Ground Floor');
            $table->integer('total_floors')->default(6);
            $table->decimal('price_total', 15, 2);
            $table->decimal('price_per_sqft', 15, 2);
            $table->decimal('frontage_ft', 6, 2)->nullable();
            $table->decimal('ceiling_height_ft', 6, 2)->nullable();
            $table->enum('completion_status', ['ready', 'ongoing', 'under_construction'])->default('ready');
            $table->date('handover_date')->nullable();
            $table->decimal('monthly_rent_yield', 15, 2)->nullable();
            $table->enum('washroom_type', ['private', 'common', 'none'])->default('common');
            $table->boolean('has_lift')->default(false);
            $table->boolean('has_generator')->default(false);
            $table->boolean('has_central_ac')->default(false);
            $table->boolean('has_fire_safety')->default(false);
            $table->boolean('has_cctv')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_escalator')->default(false);
            $table->text('suitable_for')->nullable();
            $table->timestamps();
        });

        // 5. Direct Sources (Anti-duplicate tracking)
        Schema::create('property_direct_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 50)->default('direct_owner');
            $table->string('relationship_declaration')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 50)->nullable();
            $table->decimal('asking_price', 15, 2);
            $table->boolean('is_primary_source')->default(false);
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->index('source_type');
            $table->index('verification_status');
        });

        // 6. Property Verifications (Admin audit checks)
        Schema::create('property_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained('properties')->cascadeOnDelete();
            $table->boolean('owner_identity_checked')->default(false);
            $table->boolean('location_checked')->default(false);
            $table->boolean('basic_info_checked')->default(false);
            $table->boolean('basic_documents_checked')->default(false);
            $table->json('documents_available')->nullable();
            $table->boolean('disclaimer_acknowledged')->default(true);
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('audit_notes')->nullable();
            $table->timestamps();
        });

        // 7. Property Media (Photos, WebP files, Covers)
        Schema::create('property_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('file_path');
            $table->enum('media_type', ['photo', 'road_view', 'surrounding', 'floor_plan', 'video_embed'])->default('photo');
            $table->string('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_cover')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_media');
        Schema::dropIfExists('property_verifications');
        Schema::dropIfExists('property_direct_sources');
        Schema::dropIfExists('property_commercial_details');
        Schema::dropIfExists('property_share_details');
        Schema::dropIfExists('property_flat_details');
        Schema::dropIfExists('property_land_details');
    }
};
