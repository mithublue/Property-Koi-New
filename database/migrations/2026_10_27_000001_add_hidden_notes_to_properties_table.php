<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'hidden_note')) {
                $table->longText('hidden_note')->nullable()->after('description');
            }
            if (!Schema::hasColumn('properties', 'hidden_tags')) {
                $table->json('hidden_tags')->nullable()->after('hidden_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'hidden_tags')) {
                $table->dropColumn('hidden_tags');
            }
            if (Schema::hasColumn('properties', 'hidden_note')) {
                $table->dropColumn('hidden_note');
            }
        });
    }
};
