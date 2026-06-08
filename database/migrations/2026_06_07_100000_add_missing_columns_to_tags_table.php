<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'color')) {
                $table->string('color', 20)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('tags', 'description')) {
                $table->text('description')->nullable()->after('color');
            }
            if (! Schema::hasColumn('tags', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
            if (! Schema::hasColumn('tags', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['color', 'description', 'is_active']);
        });
    }
};
