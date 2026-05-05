<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * حداقل مبلغ سفارش برای اعمال کوپن.
     * ردیابی کوپن و مبلغ تخفیف در سفارش.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedBigInteger('min_order_amount')->nullable()->after('discount_percent');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 14, 2)->default(0)->after('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('discount_amount');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('min_order_amount');
        });
    }
};
