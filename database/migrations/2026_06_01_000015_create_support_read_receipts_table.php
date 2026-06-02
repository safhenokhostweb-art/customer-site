<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->uuid('conversation_id');
            $table->string('reader_id', 64);
            $table->string('reader_type', 32);
            $table->timestamp('read_at');
            $table->timestamps();

            $table->foreign('message_id')
                ->references('id')
                ->on('support_messages')
                ->cascadeOnDelete();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('support_conversations')
                ->cascadeOnDelete();

            $table->unique(['message_id', 'reader_id', 'reader_type'], 'uniq_support_read_receipt');
            $table->index(['conversation_id', 'reader_id']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_read_receipts');
    }
};
