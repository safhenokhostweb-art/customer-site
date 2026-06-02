<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status', 32)->default('open');   // open|assigned|closed|archived
            $table->string('type', 32)->default('support');  // support|direct
            $table->string('subject', 255)->nullable();
            $table->string('priority', 16)->default('normal'); // low|normal|high|urgent
            $table->string('channel', 32)->default('web');   // web|mobile|api
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->string('created_by_type', 64)->default('user'); // user|admin|system
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // All indexed only within tenant — no FK to platform
            $table->index(['status', 'priority']);
            $table->index(['assigned_agent_id', 'status']);
            $table->index('last_message_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('support_conversations');
        Schema::enableForeignKeyConstraints();
    }
};
