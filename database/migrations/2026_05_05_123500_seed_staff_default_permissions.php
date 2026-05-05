<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $staffRoleId = DB::table('roles')->where('slug', Role::STAFF)->value('id');
        if (!$staffRoleId) {
            return;
        }

        $defaultStaffPermissions = [
            'dashboard',
            'pages',
            'categories',
            'posts',
            'product_categories',
            'products',
            'orders',
            'coupon',
            'forms',
            'captcha',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $defaultStaffPermissions)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $staffRoleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $staffRoleId = DB::table('roles')->where('slug', Role::STAFF)->value('id');
        if (!$staffRoleId) {
            return;
        }

        $defaultStaffPermissions = [
            'dashboard',
            'pages',
            'categories',
            'posts',
            'product_categories',
            'products',
            'orders',
            'coupon',
            'forms',
            'captcha',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $defaultStaffPermissions)
            ->pluck('id');

        DB::table('role_has_permissions')
            ->where('role_id', $staffRoleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
