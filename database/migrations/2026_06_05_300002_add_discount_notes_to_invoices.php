<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'discount')) {
                $table->decimal('discount', 14, 2)->default(0)->after('subtotal');
            }
            if (! Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(array_filter(['discount', 'notes'], fn($c) => Schema::hasColumn('invoices', $c)));
        });
    }
};
