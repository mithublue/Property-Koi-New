<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buyer Demands (Requirements)
        Schema::create('buyer_demands', function (Blueprint $table) {
            $table->id();
            $table->string('requirement_code', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone', 50);
            $table->string('buyer_email')->nullable();
            $table->enum('property_type', ['land', 'flat', 'land_share', 'commercial', 'any'])->default('land')->index();
            $table->json('preferred_areas')->nullable();
            $table->json('preferred_blocks')->nullable();
            $table->integer('min_road_width_ft')->nullable();
            $table->json('preferred_facings')->nullable();
            $table->decimal('min_size', 8, 2)->nullable();
            $table->decimal('max_size', 8, 2)->nullable();
            $table->decimal('min_budget', 15, 2)->nullable();
            $table->decimal('max_budget', 15, 2)->nullable();
            $table->string('purpose', 100)->default('own_house');
            $table->string('purchase_timeline', 100)->default('within_3_months');
            $table->enum('status', ['active', 'matched', 'fulfilled', 'closed'])->default('active')->index();
            $table->string('notify_channels', 191)->default('push,email,telegram');
            $table->string('telegram_chat_id', 100)->nullable();
            $table->string('unsubscribe_token', 64)->nullable()->unique();
            $table->dateTime('last_notified_at')->nullable();
            $table->unsignedInteger('match_count')->default(0);
            $table->text('notes')->nullable();
            $table->json('criteria_details')->nullable();
            $table->timestamps();
        });

        // 2. Demand Notifications Log (Anti-Spam & Delivery history)
        Schema::create('demand_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_demand_id')->constrained('buyer_demands')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('match_score', 5, 2)->default(100.00);
            $table->string('channel', 50); // 'email', 'telegram', 'push', 'sms'
            $table->string('status', 50)->default('sent');
            $table->text('delivery_details')->nullable();
            $table->timestamps();

            $table->unique(['buyer_demand_id', 'property_id', 'channel'], 'uq_demand_prop_chan');
            $table->index('channel');
            $table->index('status');
        });

        // 3. Buyer Direct Interactions (WhatsApp click, site visit, make an offer)
        Schema::create('buyer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->enum('interaction_type', ['whatsapp_contact', 'make_offer', 'site_visit'])->index();
            $table->decimal('offered_price', 15, 2)->nullable();
            $table->date('slot_date')->nullable();
            $table->string('slot_time', 10)->nullable();
            $table->string('status', 50)->default('submitted');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_interactions');
        Schema::dropIfExists('demand_notifications');
        Schema::dropIfExists('buyer_demands');
    }
};
