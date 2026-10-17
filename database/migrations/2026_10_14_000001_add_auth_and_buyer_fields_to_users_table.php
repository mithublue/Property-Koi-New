<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 60)->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id', 100)->nullable()->index()->after('email');
            }
            if (!Schema::hasColumn('users', 'email_verification_token')) {
                $table->string('email_verification_token', 100)->nullable()->index()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'email_verification_code')) {
                $table->string('email_verification_code', 10)->nullable()->after('email_verification_token');
            }
            if (!Schema::hasColumn('users', 'email_verification_sent_at')) {
                $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_code');
            }
            if (!Schema::hasColumn('users', 'preferred_type')) {
                $table->string('preferred_type', 30)->default('any')->after('avatar_url');
            }
            if (!Schema::hasColumn('users', 'preferred_blocks')) {
                $table->string('preferred_blocks', 100)->nullable()->after('preferred_type');
            }
            if (!Schema::hasColumn('users', 'budget_range')) {
                $table->string('budget_range', 100)->nullable()->after('preferred_blocks');
            }
        });

        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'property_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');

        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'username',
                'google_id',
                'email_verification_token',
                'email_verification_code',
                'email_verification_sent_at',
                'preferred_type',
                'preferred_blocks',
                'budget_range',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
