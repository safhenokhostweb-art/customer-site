<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions')) {
            return;
        }

        $roles = [
            ['name' => 'Tenant Admin', 'slug' => 'tenant_admin', 'guard_name' => 'web', 'description' => 'مدیر کامل سایت'],
            ['name' => 'Staff', 'slug' => 'staff', 'guard_name' => 'web', 'description' => 'کارمند با دسترسی محدود'],
            ['name' => 'Customer', 'slug' => 'customer', 'guard_name' => 'web', 'description' => 'مشتری بدون دسترسی ادمین'],
        ];

        foreach ($roles as $r) {
            DB::table('roles')->insertOrIgnore(array_merge($r, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $permissions = [
            ['name' => 'داشبورد', 'slug' => 'dashboard', 'module_slug' => 'dashboard'],
            ['name' => 'صفحات', 'slug' => 'pages', 'module_slug' => 'pages'],
            ['name' => 'دسته‌بندی وبلاگ', 'slug' => 'categories', 'module_slug' => 'blog'],
            ['name' => 'مطالب وبلاگ', 'slug' => 'posts', 'module_slug' => 'blog'],
            ['name' => 'محصولات', 'slug' => 'products', 'module_slug' => 'shop'],
            ['name' => 'دسته‌بندی محصولات', 'slug' => 'product_categories', 'module_slug' => 'shop'],
            ['name' => 'سفارشات', 'slug' => 'orders', 'module_slug' => 'shop'],
            ['name' => 'کوپن‌ها', 'slug' => 'coupon', 'module_slug' => 'shop'],
            ['name' => 'نظرات', 'slug' => 'reviews', 'module_slug' => 'reviews'],
            ['name' => 'معرفی دوستان', 'slug' => 'referral', 'module_slug' => 'referral'],
            ['name' => 'پلن اشتراک', 'slug' => 'subscription', 'module_slug' => 'subscription'],
            ['name' => 'پاسخگویی شبکه‌ها', 'slug' => 'whatsapp', 'module_slug' => 'messaging'],
            ['name' => 'پیامک', 'slug' => 'sms', 'module_slug' => 'sms'],
            ['name' => 'دوره‌ها', 'slug' => 'courses', 'module_slug' => 'courses'],
            ['name' => 'فاکتورها', 'slug' => 'invoice', 'module_slug' => 'invoice'],
            ['name' => 'کارت ویزیت', 'slug' => 'business_card', 'module_slug' => 'business_card'],
            ['name' => 'شبکه‌های اجتماعی', 'slug' => 'social_links', 'module_slug' => 'social_links'],
            ['name' => 'تبلیغات', 'slug' => 'advertisements', 'module_slug' => 'advertisements'],
            ['name' => 'فرم‌ساز', 'slug' => 'forms', 'module_slug' => 'forms'],
            ['name' => 'کپچا', 'slug' => 'captcha', 'module_slug' => 'captcha'],
            ['name' => 'تنظیمات', 'slug' => 'settings', 'module_slug' => 'settings'],
            ['name' => 'حذف ۴ صفر پول', 'slug' => 'currency_reform', 'module_slug' => 'currency_reform'],
            ['name' => 'لایسنس', 'slug' => 'license', 'module_slug' => 'license'],
            ['name' => 'پشتیبانی', 'slug' => 'support', 'module_slug' => 'support'],
        ];

        foreach ($permissions as $p) {
            DB::table('permissions')->insertOrIgnore(array_merge($p, [
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $staffRoleId = DB::table('roles')->where('slug', 'staff')->value('id');
        $dashboardPermId = DB::table('permissions')->where('slug', 'dashboard')->value('id');
        if ($staffRoleId && $dashboardPermId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $staffRoleId,
                'permission_id' => $dashboardPermId,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->truncate();
        }
        if (Schema::hasTable('role_user')) {
            DB::table('role_user')->truncate();
        }
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->truncate();
        }
        if (Schema::hasTable('roles')) {
            DB::table('roles')->truncate();
        }
    }
};
