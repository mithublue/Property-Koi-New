<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('guides', 'synced_to_live_at')) {
            Schema::table('guides', function (Blueprint $table) {
                $table->timestamp('synced_to_live_at')->nullable()->after('is_published');
            });
        }

        // Mark existing legacy seed guides as synced, while leaving newly generated guides as un-synced (NULL)
        DB::table('guides')
            ->where('created_at', '<', '2026-11-01 00:00:00')
            ->whereNull('synced_to_live_at')
            ->update(['synced_to_live_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('guides', 'synced_to_live_at')) {
            Schema::table('guides', function (Blueprint $table) {
                $table->dropColumn('synced_to_live_at');
            });
        }
    }
};
