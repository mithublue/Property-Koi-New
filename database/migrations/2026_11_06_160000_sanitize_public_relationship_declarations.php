<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sanitize property_direct_sources relationship_declaration
        DB::table('property_direct_sources')
            ->where(function ($q) {
                $q->where('relationship_declaration', 'like', '%API%')
                  ->orWhere('relationship_declaration', 'like', '%Google%')
                  ->orWhere('relationship_declaration', 'like', '%sheet%')
                  ->orWhere('relationship_declaration', 'like', '%sync%');
            })
            ->update([
                'relationship_declaration' => DB::raw("CASE WHEN source_type IN ('owner', 'direct_owner') THEN 'সরাসরি প্রপার্টির মালিক (Direct Owner)' ELSE 'অনুমোদিত ডিরেক্ট এজেন্ট (Direct Agent)' END")
            ]);

        // 2. Sanitize properties description if it contains API or Google sheet default text
        DB::table('properties')
            ->where('description', 'like', '%API মারফত%')
            ->orWhere('description', 'like', '%Google Sheets%')
            ->update([
                'description' => DB::raw("COALESCE(title, 'চমৎকার লোকেশনে আকর্ষণীয় প্রপার্টি।')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
