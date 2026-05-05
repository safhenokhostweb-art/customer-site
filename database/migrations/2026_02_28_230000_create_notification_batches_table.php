<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_batches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('type', 50)->default('info');
            $table->string('link', 500)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('sent_at');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->foreignId('notification_batch_id')->nullable()->after('user_id')->constrained('notification_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropForeign(['notification_batch_id']);
        });
        Schema::dropIfExists('notification_batches');
    }
};
