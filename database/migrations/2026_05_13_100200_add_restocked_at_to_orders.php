<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 6.1 / P1-S4 — Idempotency guard for the stock-restore side effect run
 * by OrderStatusService when an order transitions to "cancelled". Without
 * this column we could double-restock a product (e.g. if an admin flips
 * paid → cancelled → paid → cancelled). NULL means "never restocked".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'restocked_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('restocked_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'restocked_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('restocked_at');
            });
        }
    }
};
