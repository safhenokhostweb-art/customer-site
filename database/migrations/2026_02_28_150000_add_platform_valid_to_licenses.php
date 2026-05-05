<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * وضعیت اعتبارسنجی با پلتفرم: وقتی لایسنس از سمت پلتفرم ابطال شود، سایت مشتری با re-validate متوجه شده و ماژول‌ها را مخفی می‌کند.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('licenses')) {
            return;
        }
        Schema::table('licenses', function (Blueprint $table) {
            if (!Schema::hasColumn('licenses', 'platform_valid')) {
                $table->boolean('platform_valid')->default(true)->after('support_level');
            }
            if (!Schema::hasColumn('licenses', 'last_validated_at')) {
                $table->timestamp('last_validated_at')->nullable()->after('platform_valid');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('licenses')) {
            return;
        }
        Schema::table('licenses', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('licenses', 'platform_valid')) {
                $cols[] = 'platform_valid';
            }
            if (Schema::hasColumn('licenses', 'last_validated_at')) {
                $cols[] = 'last_validated_at';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
