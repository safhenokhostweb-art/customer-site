<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();

            // video_type: youtube | mp4 | cloudflare
            $table->string('video_type', 20)->default('youtube');
            $table->text('video_url');

            $table->string('thumbnail_url')->nullable();

            // Player options
            $table->boolean('autoplay')->default(false);
            $table->boolean('loop')->default(false);
            $table->boolean('muted')->default(true);
            $table->boolean('show_controls')->default(true);

            // Overlay
            $table->string('overlay_text')->nullable();
            $table->unsignedTinyInteger('overlay_opacity')->default(30); // 0-100

            // CTA button
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_videos');
    }
};
