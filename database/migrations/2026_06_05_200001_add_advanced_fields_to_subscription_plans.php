<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('image', 500)->nullable()->after('badge_text');
            $table->decimal('special_price', 14, 2)->nullable()->after('price');
            $table->string('discount_code', 50)->nullable()->after('special_price');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('discount_code');
        });

        // اضافه کردن وضعیت pending به subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('pending_reason', 255)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['image', 'special_price', 'discount_code', 'discount_percent']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('pending_reason');
        });
    }
};
