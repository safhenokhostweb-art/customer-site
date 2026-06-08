<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'removed', 'rejected'])->default('pending');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['user_id', 'status']);
            // یک کاربر نمی‌تواند دو بار به یک اشتراک دعوت شود
            $table->unique(['subscription_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_members');
    }
};
