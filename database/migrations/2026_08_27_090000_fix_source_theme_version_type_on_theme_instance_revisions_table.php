<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1h — fixes theme_instance_revisions.source_theme_version, which has
 * been unusable since its creation: the platform's commercial version
 * authority (ThemeRelease.version) is a semver string ("1.2.3"), but this
 * column was created as unsignedInteger, so it could never legally hold
 * that value — confirmed always NULL in every environment, since nothing
 * could write to it without truncating/fabricating data. Changed to a
 * nullable string; no data migration needed since the column has no
 * non-null values to preserve.
 *
 * Also adds source_release_id: the specific ThemeRelease row's id, so
 * lineage can be traced back to an exact immutable release (not just its
 * version string, which alone can't disambiguate a rollback to an older
 * version from a genuinely new release carrying the same number).
 *
 * doctrine/dbal is not installed in this project, so the column is dropped
 * and re-added rather than using Blueprint::change() — safe here only
 * because it has never held a non-null value (verified before writing this
 * migration), which would not be true for a real data column.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('theme_instance_revisions', function (Blueprint $table) {
            $table->dropColumn('source_theme_version');
        });

        Schema::table('theme_instance_revisions', function (Blueprint $table) {
            $table->string('source_theme_version', 32)->nullable()->after('source_theme_id');
            $table->unsignedBigInteger('source_release_id')->nullable()->after('source_theme_version');
        });
    }

    public function down(): void
    {
        Schema::table('theme_instance_revisions', function (Blueprint $table) {
            $table->dropColumn(['source_theme_version', 'source_release_id']);
        });

        Schema::table('theme_instance_revisions', function (Blueprint $table) {
            $table->unsignedInteger('source_theme_version')->nullable()->after('source_theme_id');
        });
    }
};
