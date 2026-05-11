<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 / Wave 3 / P1-2.
 *
 * جدول لاگ فعالیت پنل ادمین سایت مشتری.
 *
 * این جدول کاملاً جدا از user_activity_log (که فقط برای end-user است)
 * تعریف می‌شود تا scope و سیاست retention/permission متفاوت داشته باشد.
 *
 * ساختار با activity_logs در saas-platform هم‌خوان است (action,
 * description, subject_*, properties json) تا الگوی query/UI ساده باشد.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('admin_activity_log')) {
            return;
        }

        Schema::create('admin_activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('description')->nullable();
            $table->string('subject_type', 128)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::table('admin_activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'admin_activity_log_subject_idx');
            $table->index('created_at', 'admin_activity_log_created_at_idx');
            $table->index(['admin_user_id', 'created_at'], 'admin_activity_log_admin_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_log');
    }
};
