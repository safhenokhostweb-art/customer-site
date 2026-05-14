<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_activity_log')) {
            return;
        }
        if (Schema::hasColumn('admin_activity_log', 'causer_id')) {
            return;
        }

        Schema::table('admin_activity_log', function (Blueprint $table) {
            $table->foreignId('causer_id')->nullable()->after('admin_user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_activity_log') || ! Schema::hasColumn('admin_activity_log', 'causer_id')) {
            return;
        }

        Schema::table('admin_activity_log', function (Blueprint $table) {
            $table->dropForeign(['causer_id']);
            $table->dropColumn('causer_id');
        });
    }
};
