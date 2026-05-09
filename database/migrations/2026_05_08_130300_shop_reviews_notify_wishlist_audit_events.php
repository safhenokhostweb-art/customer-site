<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            Schema::create('product_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('guest_name', 120)->nullable();
                $table->string('guest_email', 190)->nullable();
                $table->unsignedTinyInteger('rating');
                $table->string('title', 255)->nullable();
                $table->text('body')->nullable();
                $table->boolean('approved')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_stock_notifications')) {
            Schema::create('product_stock_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('email', 190)->nullable();
                $table->string('session_id', 64)->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();
                $table->index(['product_id', 'product_variant_id']);
            });
        }

        if (! Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('session_id', 64)->nullable();
                $table->timestamps();
                $table->unique('user_id');
                $table->unique('session_id');
            });
        }

        if (! Schema::hasTable('wishlist_items')) {
            Schema::create('wishlist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['wishlist_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('product_audit_logs')) {
            Schema::create('product_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->json('changes');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('shop_cart_events')) {
            Schema::create('shop_cart_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('event_type', 32);
                $table->string('session_id', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['product_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_cart_events');
        Schema::dropIfExists('product_audit_logs');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('product_stock_notifications');
        Schema::dropIfExists('product_reviews');
    }
};
