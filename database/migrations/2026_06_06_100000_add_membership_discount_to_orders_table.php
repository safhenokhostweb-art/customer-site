<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('membership_discount_amount', 12, 2)
                ->default(0)
                ->after('discount_amount');

            $table->string('membership_plan_name')->nullable()->after('membership_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['membership_discount_amount', 'membership_plan_name']);
        });
    }
};
