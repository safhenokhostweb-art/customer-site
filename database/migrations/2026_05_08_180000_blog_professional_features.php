<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('post_series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });

        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot');
            $table->timestamps();
            $table->index(['post_id', 'created_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('editorial_status', 32)->default('draft')->after('is_published');
            $table->string('canonical_url', 500)->nullable()->after('meta_description');
            $table->string('og_image', 500)->nullable()->after('canonical_url');
            $table->foreignId('series_id')->nullable()->after('category_id')->constrained('post_series')->nullOnDelete();
            $table->unsignedSmallInteger('part_number')->nullable()->after('series_id');
            $table->json('gallery_images')->nullable()->after('featured_image');
            $table->unsignedInteger('likes_count')->default(0)->after('view_count');
            $table->index('editorial_status');
        });

        if (Schema::hasTable('posts')) {
            DB::table('posts')->where('is_published', true)->update(['editorial_status' => 'published']);
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->insertOrIgnore([
                'name' => 'انتشار و تایید مطالب',
                'slug' => 'posts_moderate',
                'module_slug' => 'blog',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('roles') && Schema::hasTable('role_has_permissions') && Schema::hasTable('permissions')) {
            $permId = DB::table('permissions')->where('slug', 'posts_moderate')->value('id');
            $tenantAdminRoleId = DB::table('roles')->where('slug', 'tenant_admin')->value('id');
            if ($permId && $tenantAdminRoleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $tenantAdminRoleId,
                    'permission_id' => $permId,
                ]);
            }
        }

        if (Schema::hasTable('module_versions')) {
            $exists = DB::table('module_versions')->where('module_slug', 'blog')->exists();
            if ($exists) {
                DB::table('module_versions')->where('module_slug', 'blog')->update([
                    'version' => '1.4.0',
                    'updated_at' => now(),
                ]);
            } else {
                $now = now();
                DB::table('module_versions')->insert([
                    'module_slug' => 'blog',
                    'version' => '1.4.0',
                    'installed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('module_change_logs')) {
            DB::table('module_change_logs')->insertOrIgnore([
                'module_slug' => 'blog',
                'version' => '1.4.0',
                'release_type' => 'minor',
                'title' => 'وبلاگ حرفه‌ای',
                'changelog' => implode("\n", [
                    'ویرایشگر WYSIWYG (TinyMCE) برای محتوا',
                    'پیش‌نویس، زمان‌بندی، وضعیت انتشار و گردش کار تایید (پیش‌نویس / در انتظار تایید / تایید / رد / منتشر)',
                    'فیلدهای SEO: canonical، og:image اختصاصی',
                    'برچسب‌ها و مجموعهٔ مطالب (سری) با شمارهٔ قسمت',
                    'گالری تصاویر، تاریخچهٔ نسخه‌ها با بازگردانی',
                    'شمارش بازدید با یک‌بار در نشست، لایک ساده',
                    'مسیرهای فرانت: سری، فیلتر برچسب، لایک',
                ]),
                'released_at' => now(),
                'synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropIndex(['editorial_status']);
            $table->dropColumn([
                'editorial_status',
                'canonical_url',
                'og_image',
                'series_id',
                'part_number',
                'gallery_images',
                'likes_count',
            ]);
        });

        Schema::dropIfExists('post_revisions');
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('post_series');

        if (Schema::hasTable('role_has_permissions') && Schema::hasTable('permissions')) {
            $permId = DB::table('permissions')->where('slug', 'posts_moderate')->value('id');
            if ($permId) {
                DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
                DB::table('permissions')->where('id', $permId)->delete();
            }
        }
    }
};
