<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('manager_name', 100)->nullable();
            $table->string('manager_mobile', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0); // رزرو برای سفارش
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('reorder_quantity')->default(10);
            $table->string('location', 100)->nullable(); // قفسه / ردیف
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'return'])->default('in');
            $table->string('reference_type', 50)->nullable(); // order / manual / transfer
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->integer('quantity_change'); // مثبت یا منفی
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();
            $table->index(['product_id', 'moved_at']);
            $table->index(['warehouse_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('warehouses');
    }
};
