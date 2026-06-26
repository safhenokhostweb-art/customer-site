<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->string('accent_color',      32)->nullable()->default('#7c3aed')->after('secondary_color');
            $table->string('background_color',  32)->nullable()->default('#f9fafb')->after('accent_color');
            $table->string('surface_color',     32)->nullable()->default('#ffffff')->after('background_color');
            $table->string('text_color',        32)->nullable()->default('#111827')->after('surface_color');
            $table->string('muted_color',       32)->nullable()->default('#6b7280')->after('text_color');
            $table->string('border_color',      32)->nullable()->default('#e5e7eb')->after('muted_color');
            $table->string('link_color',        32)->nullable()->default('#4f46e5')->after('border_color');
            $table->string('header_bg',         32)->nullable()->default('#1e293b')->after('link_color');
            $table->string('footer_bg',         32)->nullable()->default('#1e293b')->after('header_bg');
        });
    }

    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->dropColumn([
                'accent_color', 'background_color', 'surface_color',
                'text_color', 'muted_color', 'border_color',
                'link_color', 'header_bg', 'footer_bg',
            ]);
        });
    }
};
