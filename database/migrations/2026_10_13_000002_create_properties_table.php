<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('property_code', 50)->unique();
            $table->enum('type', ['land', 'flat', 'land_share', 'commercial'])->default('land')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->string('area', 100)->default('Aftabnagar')->index();
            $table->string('sub_area', 100)->nullable();
            $table->string('block', 50)->nullable()->index();
            $table->string('sector', 50)->nullable();
            $table->string('road_number', 50)->nullable();
            $table->integer('road_width_ft')->default(20)->index();
            $table->string('plot_number', 100)->nullable();
            $table->string('holding_number', 50)->nullable();
            $table->json('address_visibility')->nullable();
            $table->string('facing', 30)->nullable()->index();
            $table->enum('status', [
                'draft',
                'pending_verification',
                'live',
                'visit_requested',
                'negotiation',
                'booked',
                'sold'
            ])->default('pending_verification')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_best_deal')->default(false)->index();
            $table->boolean('is_negotiable')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('google_map_pin_url', 255)->nullable();
            $table->longText('other_features')->nullable();
            $table->longText('fb_caption')->nullable();
            $table->string('fb_post_id', 100)->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->dateTime('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['block', 'plot_number']);
            $table->index(['area', 'sub_area', 'plot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
