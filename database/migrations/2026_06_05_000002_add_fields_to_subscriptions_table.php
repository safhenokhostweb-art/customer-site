<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete()->after('subscription_plan_id');
            $table->timestamp('trial_ends_at')->nullable()->after('ends_at');
            $table->timestamp('cancelled_at')->nullable()->after('trial_ends_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->timestamp('renewed_at')->nullable()->after('cancellation_reason');
            $table->boolean('auto_renew')->default(false)->after('renewed_at');
            $table->text('notes')->nullable()->after('auto_renew');

            $table->index(['user_id', 'status']);
            $table->index(['ends_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['ends_at', 'status']);
            $table->dropColumn(['invoice_id', 'trial_ends_at', 'cancelled_at', 'cancellation_reason', 'renewed_at', 'auto_renew', 'notes']);
        });
    }
};
