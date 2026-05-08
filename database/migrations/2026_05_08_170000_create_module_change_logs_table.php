<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('module_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug', 64);
            $table->string('version', 32);
            $table->string('release_type', 16)->default('patch');
            $table->string('title', 255)->nullable();
            $table->text('changelog')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['module_slug', 'version']);
            $table->index(['module_slug', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_change_logs');
    }
};
