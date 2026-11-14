<?php

use App\Modules\SolarCalculator\Support\SolarCalculatorDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installs the default formulas, settings and exchange-rate currencies
 * (insert-or-ignore: never overwrites an admin's edits), and the
 * `solar_calculator` admin permission. Every install gets the tables and
 * defaults; only a site licensed for `solar_calculator` can reach them.
 */
return new class extends Migration {
    public function up(): void
    {
        SolarCalculatorDefaults::install();

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->insertOrIgnore([
                'name' => 'ماشین‌حساب خورشیدی',
                'slug' => 'solar_calculator',
                'module_slug' => 'solar_calculator',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $id = DB::table('permissions')->where('slug', 'solar_calculator')->value('id');
            if ($id && Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            }
            DB::table('permissions')->where('slug', 'solar_calculator')->delete();
        }
        // The default rows live in tables the previous migration drops.
    }
};
