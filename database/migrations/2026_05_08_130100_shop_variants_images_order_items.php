<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('sku', 100)->nullable();
                $table->string('label')->nullable();
                $table->decimal('price_override', 14, 2)->nullable();
                $table->decimal('sale_price', 14, 2)->nullable();
                $table->unsignedInteger('stock')->nullable();
                $table->string('image', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedInteger('min_order_qty')->default(1);
                $table->unsignedInteger('max_order_qty')->nullable();
                $table->boolean('allow_backorder')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('path', 500);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
                $table->string('variant_label', 255)->nullable()->after('product_variant_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['product_variant_id']);
                $table->dropColumn(['product_variant_id', 'variant_label']);
            });
        }
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
    }
};
