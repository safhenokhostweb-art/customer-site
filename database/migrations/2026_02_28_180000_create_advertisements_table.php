<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // banner, scrolling_text, typewriter, marquee, blink, fade
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('image_path')->nullable();
            $table->string('link')->nullable();
            $table->string('position', 50)->default('header'); // header, footer, sidebar, content_top, content_bottom
            $table->unsignedInteger('order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('options')->nullable(); // سرعت، رنگ، فونت و ...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('advertisements');
        Schema::enableForeignKeyConstraints();
    }
};
