<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول ردیابی نسخهٔ نصب‌شدهٔ هر ماژول در این سایت.
 * برای گزارش به پلتفرم و بررسی بروزرسانی استفاده می‌شود.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('module_versions', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug', 64)->unique();
            $table->string('version', 32)->default('1.0.0');
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_versions');
    }
};
