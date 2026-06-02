<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('support_conversation_participants');
        Schema::create('support_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_id');
            $table->string('participant_id', 64);
            $table->string('participant_type', 32); // user|admin|support_agent|bot
            $table->string('role', 32)->default('participant'); // owner|participant|observer
            $table->string('display_name', 128)->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamps();

            // All scoped to this tenant's conversation — no cross-platform FK
            $table->foreign('conversation_id')
                ->references('id')
                ->on('support_conversations')
                ->cascadeOnDelete();

            $table->unique(['conversation_id', 'participant_id', 'participant_type'], 'uniq_support_participant');
            $table->index(
    ['participant_id', 'participant_type'],
    'scp_participant_idx'
);
            $table->index(['conversation_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_conversation_participants');
    }
};
