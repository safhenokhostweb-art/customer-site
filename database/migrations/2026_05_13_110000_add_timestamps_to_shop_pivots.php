<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 6.2 — Align two shop pivot tables with their Eloquent declarations.
 *
 * `App\Models\Product::tags()` and `Product::attributeValues()` both call
 * `->withTimestamps()` on their `belongsToMany` definitions, which makes
 * Eloquent select `pivot_created_at`/`pivot_updated_at` columns from the
 * pivot tables during eager-load. The original migration
 * `2026_05_08_130200_shop_attributes_tags_related_bundles` defined those
 * pivots without `$table->timestamps()`, so any page that does
 * `->with('tags')` or `->with('attributeValues.attribute')` raises
 * `SQLSTATE[42S22]: Unknown column 'product_product_tag.created_at'`
 * (and the same for `product_product_attribute_value`) — including
 * `/shop` and `/shop/{slug}`. The license-gated 403 in the baseline test
 * suite masked this in CI; Wave 6.2's correct license seeding exposes it.
 *
 * This migration is strictly **additive** (only adds columns) and
 * idempotent (`hasTable` + `hasColumn` guards), so re-runs on
 * production installations are safe. The `down()` cleanly removes the
 * columns when present.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_product_tag') && ! Schema::hasColumn('product_product_tag', 'created_at')) {
            Schema::table('product_product_tag', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        if (Schema::hasTable('product_product_attribute_value') && ! Schema::hasColumn('product_product_attribute_value', 'created_at')) {
            Schema::table('product_product_attribute_value', function (Blueprint $table) {
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_product_tag') && Schema::hasColumn('product_product_tag', 'created_at')) {
            Schema::table('product_product_tag', function (Blueprint $table) {
                $table->dropColumn(['created_at', 'updated_at']);
            });
        }

        if (Schema::hasTable('product_product_attribute_value') && Schema::hasColumn('product_product_attribute_value', 'created_at')) {
            Schema::table('product_product_attribute_value', function (Blueprint $table) {
                $table->dropColumn(['created_at', 'updated_at']);
            });
        }
    }
};
