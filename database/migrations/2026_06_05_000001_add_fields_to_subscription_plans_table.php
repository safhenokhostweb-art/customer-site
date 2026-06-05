<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('trial_days')->default(0)->after('order');
            $table->json('features')->nullable()->after('trial_days');
            $table->string('color', 20)->nullable()->after('features');
            $table->boolean('highlighted')->default(false)->after('color');
            $table->string('badge_text', 50)->nullable()->after('highlighted');
            $table->unsignedInteger('max_users')->nullable()->after('badge_text');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['trial_days', 'features', 'color', 'highlighted', 'badge_text', 'max_users']);
        });
    }
};
