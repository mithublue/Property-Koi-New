<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Make source_user_id nullable, change source_type to varchar(50), relationship_declaration nullable
            DB::statement("ALTER TABLE `property_direct_sources` MODIFY `source_user_id` BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE `property_direct_sources` MODIFY `source_type` VARCHAR(50) NOT NULL DEFAULT 'direct_owner'");
            DB::statement("ALTER TABLE `property_direct_sources` MODIFY `relationship_declaration` VARCHAR(255) NULL");
            DB::statement("ALTER TABLE `property_direct_sources` MODIFY `asking_price` DECIMAL(15,2) NULL DEFAULT 0.00");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `property_direct_sources` MODIFY `source_type` ENUM('direct_owner','direct_agent') NOT NULL DEFAULT 'direct_owner'");
        }
    }
};
