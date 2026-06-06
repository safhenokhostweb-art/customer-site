<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('apply_to_all_products')->default(false)->after('product_discount_percent');
            $table->boolean('coupon_override')->default(false)->after('apply_to_all_products');
        });

        Schema::table('subscription_plan_products', function (Blueprint $table) {
            $table->boolean('override_coupon_exclusion')->default(false)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plan_products', function (Blueprint $table) {
            $table->dropColumn('override_coupon_exclusion');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['apply_to_all_products', 'coupon_override']);
        });
    }
};
