<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_presence', function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_id');
            $table->string('participant_id', 64);
            $table->string('participant_type', 32);
            $table->string('status', 16)->default('offline'); // online|away|offline
            $table->boolean('is_typing')->default(false);
            $table->timestamp('typing_started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('socket_id', 128)->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('support_conversations')
                ->cascadeOnDelete();

            $table->unique(['conversation_id', 'participant_id', 'participant_type'], 'uniq_support_presence');
            $table->index(['participant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_presence');
    }
};
