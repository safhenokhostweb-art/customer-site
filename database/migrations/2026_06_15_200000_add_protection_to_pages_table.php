<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_protected')->default(false)->after('show_in_menu');
            $table->string('theme_id', 100)->nullable()->after('is_protected');
            $table->string('theme_page_key', 100)->nullable()->after('theme_id');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['is_protected', 'theme_id', 'theme_page_key']);
        });
    }
};
