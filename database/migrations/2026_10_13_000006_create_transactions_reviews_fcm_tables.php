<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Transactions (0.5% Fee Engine)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 50)->unique();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone', 50);
            $table->enum('seller_type', ['direct_owner', 'direct_agent'])->default('direct_owner');
            $table->foreignId('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('agreed_price', 15, 2);
            $table->decimal('platform_fee_percent', 5, 2)->default(0.50);
            $table->decimal('platform_fee_amount', 15, 2);
            $table->enum('fee_status', ['pending', 'partial', 'collected', 'waived'])->default('pending')->index();
            $table->date('closed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Historical Property Archives (Market price intelligence)
        Schema::create('historical_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->enum('property_type', ['land', 'flat', 'commercial', 'land_share']);
            $table->string('block', 50)->nullable()->index();
            $table->integer('road_width_ft')->default(20);
            $table->string('facing', 30)->nullable();
            $table->decimal('size_katha_or_sqft', 8, 2);
            $table->decimal('initial_asking_price', 15, 2);
            $table->decimal('final_negotiated_price', 15, 2)->nullable();
            $table->decimal('sold_price', 15, 2);
            $table->date('sold_date');
            $table->timestamps();
        });

        // 3. Agent & Owner Reviews
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('target_type', ['agent', 'owner'])->default('agent');
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('reviewer_name');
            $table->string('reviewer_phone', 50)->nullable();
            $table->integer('rating')->default(5);
            $table->text('review_text')->nullable();
            $table->enum('status', ['approved', 'pending', 'spam'])->default('approved');
            $table->timestamps();
        });

        // 4. Agent & Owner Internal Admin Notes
        Schema::create('agent_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->default('Admin');
            $table->text('note_text');
            $table->timestamps();
        });

        // 5. Firebase FCM Device Tokens (Web Push Notifications)
        Schema::create('fcm_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 500)->unique();
            $table->string('device_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_device_tokens');
        Schema::dropIfExists('agent_notes');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('historical_archives');
        Schema::dropIfExists('transactions');
    }
};
