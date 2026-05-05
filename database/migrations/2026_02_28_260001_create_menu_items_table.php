<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title');
            $table->string('type', 32)->default('custom_url')->comment('internal_page, blog, shop, category, custom_url');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Page id, category id, etc.');
            $table->string('url', 1024)->nullable()->comment('For custom_url or fallback');
            $table->string('icon', 64)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('open_in_new_tab')->default(false);
            $table->string('css_class', 128)->nullable();
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
