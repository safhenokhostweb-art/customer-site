<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_email')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('customer_email', 'customer_mobile');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'customer_email')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->renameColumn('customer_email', 'customer_mobile');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_mobile')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('customer_mobile', 'customer_email');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'customer_mobile')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->renameColumn('customer_mobile', 'customer_email');
            });
        }
    }
};
