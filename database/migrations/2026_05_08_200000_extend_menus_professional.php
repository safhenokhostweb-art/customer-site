<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate alters so `after()` is valid once prior columns exist (idempotent partial runs).
        Schema::table('menus', function (Blueprint $table) {
            if (! Schema::hasColumn('menus', 'status')) {
                $table->string('status', 16)->default('published')->after('location')->comment('draft, published');
            }
        });
        Schema::table('menus', function (Blueprint $table) {
            if (! Schema::hasColumn('menus', 'go_live_at')) {
                $table->timestamp('go_live_at')->nullable()->after('status')->comment('انتشار زمان‌بندی‌شده');
            }
        });
        Schema::table('menus', function (Blueprint $table) {
            if (! Schema::hasColumn('menus', 'preview_token')) {
                $table->string('preview_token', 64)->nullable()->after('go_live_at');
            }
        });

        Schema::table('menu_items', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_items', 'title_en')) {
                $table->string('title_en', 255)->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'labels')) {
                $table->json('labels')->nullable()->comment('برچسب‌های چندزبانه اختیاری');
            }
            if (! Schema::hasColumn('menu_items', 'utm_query')) {
                $table->string('utm_query', 512)->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'utm_json')) {
                $table->json('utm_json')->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'rel_nofollow')) {
                $table->boolean('rel_nofollow')->default(false);
            }
            if (! Schema::hasColumn('menu_items', 'badge_label')) {
                $table->string('badge_label', 64)->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'image_url')) {
                $table->string('image_url', 1024)->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'mega_column')) {
                $table->unsignedTinyInteger('mega_column')->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'featured_html')) {
                $table->text('featured_html')->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'auto_list_children')) {
                $table->boolean('auto_list_children')->default(false);
            }
            if (! Schema::hasColumn('menu_items', 'visible_modules')) {
                $table->json('visible_modules')->nullable()->comment('slug ماژول‌ها؛ خالی = همیشه');
            }
        });

        if (! Schema::hasTable('menu_revisions')) {
            Schema::create('menu_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->longText('snapshot_json');
                $table->string('note', 255)->nullable();
                $table->timestamps();
                $table->index(['menu_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('menu_change_logs')) {
            Schema::create('menu_change_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
                $table->string('action', 64);
                $table->json('diff_json')->nullable();
                $table->timestamps();
                $table->index(['menu_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_change_logs');
        Schema::dropIfExists('menu_revisions');

        Schema::table('menu_items', function (Blueprint $table) {
            $cols = [
                'title_en', 'labels', 'utm_query', 'utm_json', 'rel_nofollow',
                'badge_label', 'image_url', 'mega_column', 'featured_html',
                'auto_list_children', 'visible_modules',
            ];
            $toDrop = array_values(array_filter($cols, fn (string $c) => Schema::hasColumn('menu_items', $c)));
            if ($toDrop !== []) {
                $table->dropColumn($toDrop);
            }
        });

        Schema::table('menus', function (Blueprint $table) {
            $cols = ['status', 'go_live_at', 'preview_token'];
            $toDrop = array_values(array_filter($cols, fn (string $c) => Schema::hasColumn('menus', $c)));
            if ($toDrop !== []) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
