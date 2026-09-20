<?php

namespace Tests\Unit\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Commercial Productization fase-4 — a real, confirmed bug found via an
 * actual local end-to-end theme install (not a mocked test): the
 * `settings` table's `value` column was a plain TEXT column (MySQL max
 * ~64KB). PageBuilderController::installTheme() persists the entire
 * installed theme's serialized JSON into a single
 * `active_canvas_theme_data` settings row — this theme's own real content
 * (24 pages, mega menus, Timeline, badges, real demo copy) serializes to
 * 150KB+, so the insert failed with "Data too long for column 'value'"
 * before the 2026_09_20_150000_widen_settings_value_to_longtext migration.
 */
class SettingsValueColumnWidthTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_value_column_is_longtext_not_the_original_64kb_capped_text(): void
    {
        $column = DB::selectOne("SHOW COLUMNS FROM settings WHERE Field = 'value'");

        self::assertNotNull($column);
        self::assertSame('longtext', strtolower($column->Type), "settings.value must be LONGTEXT — a real Theme's serialized data can exceed TEXT's ~64KB limit");
    }

    public function test_a_realistically_large_json_value_actually_persists_and_round_trips(): void
    {
        // ~150KB — the real size this theme's own serialized install
        // payload was measured at when the original bug was found.
        $large = json_encode(['pages' => array_fill(0, 24, ['blocks' => str_repeat('x', 6000)])]);
        self::assertGreaterThan(65535, strlen($large), 'the fixture itself must exceed the original TEXT column limit to be a real test');

        DB::table('settings')->updateOrInsert(
            ['key' => 'settings_value_width_test'],
            ['value' => $large, 'group' => 'test']
        );

        $stored = DB::table('settings')->where('key', 'settings_value_width_test')->value('value');
        self::assertSame($large, $stored);
    }
}
