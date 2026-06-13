<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores AI-generated translations for all translatable models
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type', 100);
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 10);   // ar | en
            $table->string('field_name', 100);
            $table->longText('translated_text');
            $table->char('source_hash', 32); // md5 of original content
            $table->string('ai_model', 100)->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field_name'], 'ct_unique');
            $table->index(['translatable_type', 'translatable_id'], 'ct_model_idx');
        });

        // Queue of fields waiting to be translated
        Schema::create('translation_queue', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type', 100);
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 10);
            $table->string('field_name', 100);
            $table->string('status', 20)->default('pending'); // pending | processing | done | failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field_name'], 'tq_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_queue');
        Schema::dropIfExists('content_translations');
    }
};
