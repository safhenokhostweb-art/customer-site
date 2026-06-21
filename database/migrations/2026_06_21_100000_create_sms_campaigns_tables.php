<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();
            $table->string('name');
            $table->text('message');
            // all=همه مشتریان, crm=مخاطبین CRM, users=کاربران ثبت‌نام‌شده, manual=دستی
            $table->enum('audience_type', ['all', 'crm', 'users', 'manual'])->default('all');
            $table->json('audience_filter')->nullable(); // e.g. {crm_status: "customer"}
            $table->enum('status', ['draft', 'scheduled', 'running', 'completed', 'failed', 'cancelled'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->string('mobile', 20);
            $table->string('name')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('sms_campaigns')->onDelete('cascade');
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_campaign_recipients');
        Schema::dropIfExists('sms_campaigns');
    }
};
