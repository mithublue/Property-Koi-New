<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('properties', 'custom_fields')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->json('custom_fields')->nullable()->after('other_features');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('properties', 'custom_fields')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropColumn('custom_fields');
            });
        }
    }
};
