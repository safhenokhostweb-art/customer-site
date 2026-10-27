<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 0.7 (saas-platform ADR-066) — the theme newsletter block's real
 * backend: one row per normalised contact (a lower-cased email or an
 * 09xxxxxxxxx mobile), unique so a repeat sign-up is a no-op.
 * Create-only: down() drops just this new table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('newsletter_subscribers')) {
            return;
        }
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('contact', 190)->unique();
            $table->string('channel', 10); // email | mobile
            $table->string('source', 64)->nullable(); // block id that collected it
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
