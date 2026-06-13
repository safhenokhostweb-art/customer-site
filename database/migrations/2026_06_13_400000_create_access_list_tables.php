<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // لیست‌های دسترسی (هر لیست یک فایل اکسل یا مجموعه ردیف)
        Schema::create('access_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('match_type', 20)->default('national_id'); // national_id | last_name | both
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('entry_count')->default(0);
            $table->timestamps();
        });

        // ردیف‌های لیست دسترسی (واردشده از اکسل)
        Schema::create('access_list_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('access_list_id');
            $table->foreign('access_list_id')->references('id')->on('access_lists')->onDelete('cascade');
            $table->string('national_id', 20)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['access_list_id', 'national_id']);
            $table->index(['access_list_id', 'last_name']);
        });

        // گیت‌های دسترسی (صفحات محافظت‌شده با لیست)
        Schema::create('access_gates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('access_list_id');
            $table->foreign('access_list_id')->references('id')->on('access_lists')->onDelete('cascade');
            $table->string('name');                               // نام نمایشی
            $table->string('slug', 100)->unique();                // شناسه یکتا برای URL
            $table->text('description')->nullable();              // متن توضیحی روی فرم تایید هویت
            $table->string('redirect_url');                       // URL مقصد پس از تایید
            $table->unsignedInteger('session_duration')->default(1440); // دقیقه (۲۴h default)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // لاگ تایید هویت
        Schema::create('access_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('access_gate_id');
            $table->foreign('access_gate_id')->references('id')->on('access_gates')->onDelete('cascade');
            $table->unsignedBigInteger('access_list_entry_id')->nullable();
            $table->foreign('access_list_entry_id')->references('id')->on('access_list_entries')->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->string('input_national_id', 20)->nullable();
            $table->string('input_last_name', 100)->nullable();
            $table->timestamps();

            $table->index(['access_gate_id', 'success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_verifications');
        Schema::dropIfExists('access_gates');
        Schema::dropIfExists('access_list_entries');
        Schema::dropIfExists('access_lists');
    }
};
