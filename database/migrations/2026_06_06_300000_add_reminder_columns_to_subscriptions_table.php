<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('reminder_7d_sent_at')->nullable()->after('auto_renew');
            $table->timestamp('reminder_1d_sent_at')->nullable()->after('reminder_7d_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['reminder_7d_sent_at', 'reminder_1d_sent_at']);
        });
    }
};
