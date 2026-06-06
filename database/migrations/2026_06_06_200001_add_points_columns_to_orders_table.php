<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('points_discount_amount', 12, 2)->default(0)->after('membership_plan_name');
            $table->unsignedInteger('points_spent')->default(0)->after('points_discount_amount');
            $table->unsignedInteger('points_earned')->default(0)->after('points_spent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_discount_amount', 'points_spent', 'points_earned']);
        });
    }
};
