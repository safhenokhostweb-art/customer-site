<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('posts') && !Schema::hasColumn('posts', 'builder_content')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->json('builder_content')->nullable()->after('body');
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'builder_content')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('builder_content')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        Schema::table('posts',    fn (Blueprint $t) => $t->dropColumnIfExists('builder_content'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumnIfExists('builder_content'));
    }
};
