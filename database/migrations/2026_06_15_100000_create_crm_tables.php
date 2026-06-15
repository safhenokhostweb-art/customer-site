<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // linked customer
            $table->string('name');
            $table->string('mobile', 20)->nullable()->index();
            $table->string('email', 191)->nullable();
            $table->string('company', 191)->nullable();
            $table->string('position', 100)->nullable();
            $table->enum('source', ['manual', 'order', 'ticket', 'form', 'import'])->default('manual');
            $table->enum('status', ['lead', 'prospect', 'customer', 'churned'])->default('lead');
            $table->string('city', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
        });

        Schema::create('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id')->index();
            $table->string('title');
            $table->enum('stage', ['lead', 'contacted', 'proposal', 'negotiation', 'won', 'lost'])->default('lead');
            $table->unsignedBigInteger('value')->default(0); // تومان
            $table->unsignedTinyInteger('probability')->default(50); // 0–100
            $table->date('expected_close_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['stage', 'created_at']);
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->onDelete('cascade');
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id')->index();
            $table->unsignedBigInteger('deal_id')->nullable()->index();
            $table->unsignedBigInteger('admin_id')->nullable(); // who logged it
            $table->enum('type', ['call', 'email', 'meeting', 'note', 'task'])->default('note');
            $table->string('subject', 255);
            $table->text('body')->nullable();
            $table->enum('outcome', ['none', 'interested', 'not_interested', 'follow_up'])->default('none');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['contact_id', 'created_at']);
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->onDelete('cascade');
            $table->foreign('deal_id')->references('id')->on('crm_deals')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_contacts');
    }
};
