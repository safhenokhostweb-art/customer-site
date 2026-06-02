<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->uuid('conversation_id');
            $table->string('original_name', 255);
            $table->string('stored_path', 500);  // local tenant filesystem only
            $table->string('disk', 64)->default('local');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->string('file_hash', 64)->nullable();
            $table->string('uploader_id', 64);
            $table->string('uploader_type', 32);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->foreign('message_id')
                ->references('id')
                ->on('support_messages')
                ->cascadeOnDelete();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('support_conversations')
                ->cascadeOnDelete();

            $table->index(['conversation_id', 'created_at']);
            $table->index('uploader_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_message_attachments');
    }
};
