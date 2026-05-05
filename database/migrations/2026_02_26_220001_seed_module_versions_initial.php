<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * مقداردهی اولیهٔ نسخهٔ ماژول‌ها با 1.0.0.
 * ماژول‌ها از config/modules.php خوانده می‌شوند.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('module_versions')) {
            return;
        }

        $modules = array_keys(config('modules.modules', []));
        $now = now();

        foreach ($modules as $slug) {
            if (DB::table('module_versions')->where('module_slug', $slug)->exists()) {
                continue;
            }
            DB::table('module_versions')->insert([
                'module_slug' => $slug,
                'version' => '1.0.0',
                'installed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // خالی — دادهٔ seed را حذف نمی‌کنیم تا down امن بماند
    }
};
