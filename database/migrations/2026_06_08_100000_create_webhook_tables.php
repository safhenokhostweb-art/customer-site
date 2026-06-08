<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('description')->nullable();
            $table->json('events'); // ['subscription.created', 'subscription.upgraded', ...]
            $table->string('secret', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_called_at')->nullable();
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();

            $table->index(['status', 'attempts']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('webhook_endpoints');
        Schema::enableForeignKeyConstraints();
    }
};
