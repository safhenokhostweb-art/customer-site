<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('upgrade_from_subscription_id')->nullable()->after('pending_reason');
            $table->decimal('credit_amount', 10, 0)->default(0)->after('upgrade_from_subscription_id');

            $table->foreign('upgrade_from_subscription_id')
                  ->references('id')->on('subscriptions')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['upgrade_from_subscription_id']);
            $table->dropColumn(['upgrade_from_subscription_id', 'credit_amount']);
        });
    }
};
