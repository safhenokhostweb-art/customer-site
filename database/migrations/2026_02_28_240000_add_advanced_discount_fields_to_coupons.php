<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('percent')->after('code');
            $table->unsignedBigInteger('discount_amount')->nullable()->after('discount_percent');
            $table->timestamp('starts_at')->nullable()->after('expires_at');
            $table->json('product_ids')->nullable()->after('usage_limit');
            $table->json('category_ids')->nullable()->after('product_ids');
            $table->boolean('first_order_only')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_amount', 'starts_at', 'product_ids', 'category_ids', 'first_order_only']);
        });
    }
};
