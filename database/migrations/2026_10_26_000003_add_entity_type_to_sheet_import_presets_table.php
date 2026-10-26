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
        Schema::table('sheet_import_presets', function (Blueprint $table) {
            if (!Schema::hasColumn('sheet_import_presets', 'entity_type')) {
                $table->string('entity_type', 30)->default('property')->after('id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sheet_import_presets', function (Blueprint $table) {
            if (Schema::hasColumn('sheet_import_presets', 'entity_type')) {
                $table->dropColumn('entity_type');
            }
        });
    }
};
