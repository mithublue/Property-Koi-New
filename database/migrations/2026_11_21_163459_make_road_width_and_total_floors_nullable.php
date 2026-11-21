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
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('road_width_ft')->nullable()->default(null)->change();
        });

        Schema::table('property_flat_details', function (Blueprint $table) {
            $table->integer('total_floors')->nullable()->default(null)->change();
        });

        Schema::table('property_commercial_details', function (Blueprint $table) {
            $table->integer('total_floors')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('road_width_ft')->default(20)->change();
        });

        Schema::table('property_flat_details', function (Blueprint $table) {
            $table->integer('total_floors')->default(6)->change();
        });

        Schema::table('property_commercial_details', function (Blueprint $table) {
            $table->integer('total_floors')->default(6)->change();
        });
    }
};
