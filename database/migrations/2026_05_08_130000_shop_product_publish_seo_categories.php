<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_categories') && ! Schema::hasColumn('product_categories', 'parent_id')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('product_categories')->nullOnDelete();
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'status')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('status', 20)->default('published')->after('order');
                $table->string('canonical_url', 500)->nullable()->after('meta_description');
                $table->string('og_image', 500)->nullable()->after('canonical_url');
                $table->string('video_url', 500)->nullable()->after('og_image');
                $table->text('shipping_text')->nullable()->after('description');
                $table->text('returns_text')->nullable()->after('shipping_text');
                $table->json('faq')->nullable()->after('returns_text');
                $table->string('size_chart_url', 500)->nullable()->after('faq');
                $table->text('size_chart_html')->nullable()->after('size_chart_url');
                $table->boolean('is_new')->default(false)->after('size_chart_html');
                $table->timestamp('sale_ends_at')->nullable()->after('is_new');
                $table->unsignedInteger('low_stock_threshold')->nullable()->after('sale_ends_at');
                $table->text('warranty_text')->nullable()->after('low_stock_threshold');
                $table->text('guarantee_text')->nullable()->after('warranty_text');
                $table->boolean('allow_backorder')->default(false)->after('guarantee_text');
                $table->unsignedInteger('min_order_qty')->default(1)->after('allow_backorder');
                $table->unsignedInteger('max_order_qty')->nullable()->after('min_order_qty');
                $table->unsignedBigInteger('views_count')->default(0)->after('max_order_qty');
                $table->boolean('is_bundle')->default(false)->after('views_count');
                $table->string('bundle_pricing', 32)->nullable()->after('is_bundle');
                $table->decimal('bundle_fixed_price', 14, 2)->nullable()->after('bundle_pricing');
                $table->decimal('bundle_discount_percent', 5, 2)->nullable()->after('bundle_fixed_price');
            });
        }

        if (Schema::hasColumn('products', 'is_active') && Schema::hasColumn('products', 'status')) {
            DB::table('products')->update([
                'status' => DB::raw('CASE WHEN is_active = 1 THEN "published" ELSE "draft" END'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'status')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn([
                    'status', 'canonical_url', 'og_image', 'video_url', 'shipping_text', 'returns_text',
                    'faq', 'size_chart_url', 'size_chart_html', 'is_new', 'sale_ends_at', 'low_stock_threshold',
                    'warranty_text', 'guarantee_text', 'allow_backorder', 'min_order_qty', 'max_order_qty',
                    'views_count', 'is_bundle', 'bundle_pricing', 'bundle_fixed_price', 'bundle_discount_percent',
                ]);
            });
        }

        if (Schema::hasTable('product_categories') && Schema::hasColumn('product_categories', 'parent_id')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
