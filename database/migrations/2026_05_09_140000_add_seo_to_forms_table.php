<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('is_active');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('canonical_url')->nullable()->after('meta_description');
            $table->string('meta_robots', 80)->nullable()->after('canonical_url');
            $table->text('intro_text')->nullable()->after('meta_robots');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'canonical_url',
                'meta_robots',
                'intro_text',
            ]);
        });
    }
};
