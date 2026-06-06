<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existingCols = array_column(
            DB::select("SHOW COLUMNS FROM subscription_plans"),
            'Field'
        );

        Schema::table('subscription_plans', function (Blueprint $table) use ($existingCols) {
            // duration به string برای پشتیبانی از 'day'
            $table->string('duration', 10)->default('month')->change();

            if (! in_array('benefits', $existingCols))
                $table->json('benefits')->nullable()->after('features');
            if (! in_array('product_discount_percent', $existingCols))
                $table->unsignedInteger('product_discount_percent')->default(0)->after('benefits');
            if (! in_array('free_shipping', $existingCols))
                $table->boolean('free_shipping')->default(false)->after('product_discount_percent');
            if (! in_array('vip_support', $existingCols))
                $table->boolean('vip_support')->default(false)->after('free_shipping');
            if (! in_array('early_access', $existingCols))
                $table->boolean('early_access')->default(false)->after('vip_support');
            if (! in_array('loyalty_multiplier', $existingCols))
                $table->unsignedInteger('loyalty_multiplier')->default(1)->after('early_access');
        });

        if (! Schema::hasTable('subscription_plan_products')) {
            Schema::create('subscription_plan_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('discount_percent')->default(0);
                $table->timestamps();
                $table->unique(['subscription_plan_id', 'product_id'], 'spp_plan_product_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_products');
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['benefits', 'product_discount_percent', 'free_shipping', 'vip_support', 'early_access', 'loyalty_multiplier'],
                fn($col) => Schema::hasColumn('subscription_plans', $col)
            ));
        });
    }
};
