<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_themes')) {
            return;
        }

        Schema::create('site_themes', function (Blueprint $table) {
            $table->id();
            $table->string('theme_slug', 100)->unique();
            $table->string('theme_name', 255);
            $table->string('source_theme_id', 100)->nullable();
            $table->string('source_theme_version', 50)->nullable();
            $table->unsignedBigInteger('source_release_id')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedBigInteger('active_revision_id')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_themes');
    }
};
