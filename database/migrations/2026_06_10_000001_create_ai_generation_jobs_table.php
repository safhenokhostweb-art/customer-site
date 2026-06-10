<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('mode', 20)->default('full'); // full | steps
            $table->text('prompt');
            $table->text('extra_context')->nullable();
            $table->integer('step_count')->default(5);
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending')->index();
            $table->longText('result')->nullable();
            $table->text('error')->nullable();
            $table->string('provider', 50)->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_jobs');
    }
};
