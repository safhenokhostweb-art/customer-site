<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('position')->default('top')->after('link_text'); // top, left, right
            $table->string('image_position')->default('right')->after('position'); // background, left, right
            $table->string('image_path')->nullable()->after('image_position');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['position', 'image_position', 'image_path']);
        });
    }
};
