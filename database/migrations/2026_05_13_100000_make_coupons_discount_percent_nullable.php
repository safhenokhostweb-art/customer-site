<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 6.1 / P0-S1 — Allow `coupons.discount_percent` to be NULL for fixed-amount
 * coupons. The original 2026_02_26 migration created the column as NOT NULL, but
 * `Admin\CouponController` writes NULL when `discount_type=fixed`, which fails on
 * strict-mode MySQL. This migration is additive and reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupons') || ! Schema::hasColumn('coupons', 'discount_percent')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->nullable()->change();
        });

        // Keep historical rows readable: legacy rows that stored 0 alongside a
        // fixed-amount coupon are normalized to NULL so list views display the
        // active discount type cleanly. Skipped if discount_type is missing
        // (older schema snapshots).
        if (Schema::hasColumn('coupons', 'discount_type') && Schema::hasColumn('coupons', 'discount_amount')) {
            DB::table('coupons')
                ->where('discount_type', 'fixed')
                ->where('discount_percent', 0)
                ->whereNotNull('discount_amount')
                ->update(['discount_percent' => null]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('coupons') || ! Schema::hasColumn('coupons', 'discount_percent')) {
            return;
        }

        // Restore NOT NULL by collapsing NULLs back to 0 so the constraint holds.
        DB::table('coupons')->whereNull('discount_percent')->update(['discount_percent' => 0]);

        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->nullable(false)->default(0)->change();
        });
    }
};
