<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->timestamp('rewarded_at')->nullable()->after('status');
            $table->unsignedInteger('points_awarded')->default(0)->after('rewarded_at');
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn(['rewarded_at', 'points_awarded']);
        });
    }
};
