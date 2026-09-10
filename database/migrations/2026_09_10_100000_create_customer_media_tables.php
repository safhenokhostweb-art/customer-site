<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_media')) {
        Schema::create('customer_media', function (Blueprint $table) {
            $table->id();
            // PLATFORM_SITE_ID is the installation's immutable tenant boundary.
            $table->unsignedBigInteger('site_id');
            $table->uuid('uuid')->unique();
            $table->string('disk', 64);
            $table->string('storage_key', 512)->unique();
            $table->string('original_filename', 255);
            $table->string('extension', 16);
            $table->string('category', 16);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->string('title', 255)->nullable();
            $table->string('alt_text', 500)->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'category', 'deleted_at']);
            $table->index(['site_id', 'created_at']);
        });
        }

        if (! Schema::hasTable('customer_media_usages')) {
        Schema::create('customer_media_usages', function (Blueprint $table) {
            $table->id();
            // Historical usage is deletion evidence. A future maintenance
            // operation must never erase it through a cascading FK.
            $table->foreignId('customer_media_id')->constrained('customer_media')->restrictOnDelete();
            // The consumer is deliberately explicit, rather than a polymorphic
            // relation: media appears in JSON trees as well as Eloquent rows.
            $table->string('consumer_type', 64);
            $table->unsignedBigInteger('consumer_id');
            $table->string('reference_path', 512);
            $table->timestamps();

            $table->unique(['customer_media_id', 'consumer_type', 'consumer_id', 'reference_path'], 'customer_media_usage_unique');
            $table->index(['consumer_type', 'consumer_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_media_usages');
        Schema::dropIfExists('customer_media');
    }
};
