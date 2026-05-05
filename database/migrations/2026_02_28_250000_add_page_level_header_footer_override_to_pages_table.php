<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('builder_header_template_override', 64)->nullable()->after('builder_type')
                ->comment('Page override: null = use site default; key = specific template');
            $table->string('builder_footer_template_override', 64)->nullable()->after('builder_header_template_override');
            $table->boolean('builder_disable_header')->default(false)->after('builder_footer_template_override');
            $table->boolean('builder_disable_footer')->default(false)->after('builder_disable_header');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'builder_header_template_override',
                'builder_footer_template_override',
                'builder_disable_header',
                'builder_disable_footer',
            ]);
        });
    }
};
