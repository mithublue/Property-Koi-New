<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('guide_comments')->cascadeOnDelete();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->string('author_phone')->nullable();
            $table->text('comment');
            $table->boolean('is_approved')->default(false)->index();
            $table->boolean('is_admin_reply')->default(false)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['guide_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_comments');
    }
};
