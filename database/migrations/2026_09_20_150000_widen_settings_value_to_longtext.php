<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Commercial Productization fase-4 — a real, confirmed bug found via an
 * actual local end-to-end theme install (not a mocked/faked test): the
 * `settings` table's `value` column is a plain TEXT column (MySQL max
 * 65,535 bytes / ~64KB). PageBuilderController::installTheme() persists
 * the entire installed theme's serialized JSON (pages + header/footer +
 * variants + global_styles) into a single `active_canvas_theme_data`
 * settings row — this theme's own real content (24 pages, mega menus,
 * Timeline, badges, real demo copy) serializes to 150KB+, so the insert
 * failed with "Data too long for column 'value'" before this fix. Any
 * theme rich enough to be commercially credible will hit this same wall;
 * LONGTEXT (max ~4GB) removes the ceiling entirely rather than raising it
 * to another number that will eventually be hit again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'value')) {
            return;
        }
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE `settings` MODIFY `value` LONGTEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('settings', 'value')) {
            return;
        }
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE `settings` MODIFY `value` TEXT NULL');
    }
};
