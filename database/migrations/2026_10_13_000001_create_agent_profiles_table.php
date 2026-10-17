<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('agency_name')->nullable();
            $table->string('license_no', 100)->nullable();
            $table->integer('experience_years')->default(1);
            $table->integer('direct_owner_network_count')->default(0);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->decimal('admin_fixed_rating', 3, 2)->nullable();
            $table->enum('penalty_status', ['verified', 'unverified', 'penalty_spam', 'bypassed_warning'])->default('verified');
            $table->text('penalty_reason')->nullable();
            $table->boolean('is_verified_direct_agent')->default(false);
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->index('penalty_status');
            $table->index('is_verified_direct_agent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_profiles');
    }
};
