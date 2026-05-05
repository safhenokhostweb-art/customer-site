<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('builder_content')->nullable()->after('body')->comment('Visual builder: sections/rows/columns/elements');
            $table->string('builder_type', 20)->default('legacy')->after('builder_content')->comment('legacy=body text, builder=visual builder');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['builder_content', 'builder_type']);
        });
    }
};
