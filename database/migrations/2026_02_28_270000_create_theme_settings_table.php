<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->comment('Optional tenant scope');
            $table->string('primary_color', 32)->nullable()->default('#4f46e5');
            $table->string('secondary_color', 32)->nullable()->default('#64748b');
            $table->string('font_family_heading', 128)->nullable()->default('Vazirmatn, Tahoma, sans-serif');
            $table->string('font_family_body', 128)->nullable()->default('Vazirmatn, Tahoma, sans-serif');
            $table->string('base_font_size', 24)->nullable()->default('16px');
            $table->string('container_width', 32)->nullable()->default('1200px');
            $table->string('border_radius', 32)->nullable()->default('8px');
            $table->string('button_style', 32)->nullable()->default('solid')->comment('solid, outline, rounded');
            $table->string('section_spacing', 32)->nullable()->default('md')->comment('xs, sm, md, lg, xl');
            $table->timestamps();
            $table->unique('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
