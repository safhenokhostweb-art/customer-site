<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('filterable')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_attribute_values')) {
            Schema::create('product_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
                $table->string('value');
                $table->string('slug')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_product_attribute_value')) {
            Schema::create('product_product_attribute_value', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                // FK index name is explicitly shortened to fit MySQL's 64-char identifier
                // limit. The auto-generated default
                // 'product_product_attribute_value_product_attribute_value_id_foreign'
                // is 67 chars and breaks migrate:fresh on MySQL during test runs.
                // The FK semantics (cascade on delete, referenced table/column) are unchanged.
                $table->unsignedBigInteger('product_attribute_value_id');
                $table->foreign('product_attribute_value_id', 'ppav_attr_value_fk')
                    ->references('id')
                    ->on('product_attribute_values')
                    ->cascadeOnDelete();
                $table->unique(['product_id', 'product_attribute_value_id'], 'product_attr_val_unique');
            });
        }

        if (! Schema::hasTable('product_tags')) {
            Schema::create('product_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('tag_type', 32)->default('label');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_product_tag')) {
            Schema::create('product_product_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_tag_id')->constrained()->cascadeOnDelete();
                $table->unique(['product_id', 'product_tag_id'], 'product_tag_unique');
            });
        }

        if (! Schema::hasTable('product_related')) {
            Schema::create('product_related', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
                $table->string('relation_type', 32);
                $table->timestamps();
                $table->unique(['product_id', 'related_product_id', 'relation_type'], 'product_related_unique');
            });
        } elseif (! Schema::hasColumns('product_related', ['created_at', 'updated_at'])) {
            Schema::table('product_related', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_bundles')) {
            Schema::create('product_bundles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('child_product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->timestamps();
                $table->unique(['parent_product_id', 'child_product_id'], 'product_bundle_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_related');
        Schema::dropIfExists('product_product_tag');
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('product_product_attribute_value');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};
