<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('sender_id', 64);
            $table->string('sender_type', 32); // user|admin|support_agent|bot|system
            $table->string('message_type', 32)->default('text'); // text|image|file|voice|system|note
            $table->text('content');
            $table->uuid('reply_to_message_id')->nullable();
            $table->boolean('is_internal_note')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('support_conversations')
                ->cascadeOnDelete();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'sender_type']);
            $table->index('reply_to_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
