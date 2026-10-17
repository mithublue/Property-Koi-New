<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Leads Table (CRM Inquiries)
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_code', 50)->unique();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone', 50);
            $table->string('buyer_email')->nullable();
            $table->enum('enquiry_type', ['visit_request', 'more_info', 'make_offer', 'requirement'])->default('visit_request');
            $table->enum('stage', [
                'new',
                'contacted',
                'qualified',
                'visit_scheduled',
                'visited',
                'negotiation',
                'booked',
                'sold',
                'dropped'
            ])->default('new')->index();
            $table->decimal('offered_price', 15, 2)->nullable();
            $table->text('message')->nullable();
            $table->text('qualification_notes')->nullable();
            $table->string('budget_range', 100)->nullable();
            $table->string('payment_readiness', 100)->default('cash_ready');
            $table->string('source_channel', 100)->default('platform_organic');
            $table->string('utm_source', 100)->nullable();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->timestamps();
        });

        // 2. CRM Lead Notes
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->default('Admin');
            $table->text('note_text');
            $table->timestamps();
        });

        // 3. Schedule Slots
        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->date('slot_date');
            $table->string('slot_time', 5); // 24h format e.g. 09:30
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['slot_date', 'slot_time']);
        });

        // 4. Site Visits
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->date('preferred_date');
            $table->string('preferred_time_slot', 50)->default('morning');
            $table->dateTime('scheduled_at')->nullable();
            $table->foreignId('field_coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending_confirmation', 'scheduled', 'completed', 'cancelled', 'no_show'])->default('pending_confirmation')->index();
            $table->text('feedback')->nullable();
            $table->string('outcome', 100)->nullable();
            $table->timestamps();
        });

        // 5. Visit Bookings (Direct slot reservation)
        Schema::create('visit_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('slot_date');
            $table->string('slot_time', 5);
            $table->string('buyer_name');
            $table->string('buyer_phone', 50);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['slot_date', 'slot_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_bookings');
        Schema::dropIfExists('site_visits');
        Schema::dropIfExists('schedule_slots');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
    }
};
