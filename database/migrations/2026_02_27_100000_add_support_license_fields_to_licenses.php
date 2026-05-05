<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فیلدهای لایسنس پشتیبانی در customer-site (دریافتی از API پلتفرم).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('licenses')) {
            return;
        }
        Schema::table('licenses', function (Blueprint $table) {
            if (!Schema::hasColumn('licenses', 'support_starts_at')) {
                $table->timestamp('support_starts_at')->nullable()->after('support_expires_at');
            }
            if (!Schema::hasColumn('licenses', 'support_level')) {
                $table->string('support_level', 32)->default('basic')->after('support_starts_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('licenses')) {
            return;
        }
        Schema::table('licenses', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('licenses', 'support_starts_at')) {
                $columns[] = 'support_starts_at';
            }
            if (Schema::hasColumn('licenses', 'support_level')) {
                $columns[] = 'support_level';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
