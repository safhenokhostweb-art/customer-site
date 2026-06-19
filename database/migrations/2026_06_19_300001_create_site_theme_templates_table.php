<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_theme_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 40)->default('section');
            $table->string('category', 40)->default('hero');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('author', 100)->nullable();
            $table->string('version', 20)->default('1.0.0');
            $table->string('license_type', 20)->default('free');
            $table->json('blocks');
            $table->json('global_styles')->nullable();
            $table->json('demo_data')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_theme_templates');
    }
};
