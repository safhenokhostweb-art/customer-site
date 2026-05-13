<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 6.1 / P1-S2 + P1-S3 — Persistent timeline of order status transitions
 * so support staff can answer "who marked order X as paid and when?" without
 * trusting volatile system logs. Also feeds the order detail timeline UI
 * (planned for Wave 6.3, schema is forward-compatible).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_status_history')) {
            return;
        }

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->json('side_effects')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
            $table->index('to_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
