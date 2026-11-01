<?php

namespace App\Services\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Support\DataPurge\PurgeTarget;
use App\Support\DataPurge\TablePurger;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Clean-slate purge of the customer site's Canvas theme state: the active
 * theme, draft, legacy backup and rotating revision settings, every
 * theme_instance_revision with its media-usage rows, builder pages, page and
 * section templates, and storefront menus.
 *
 * Kept: users, products, posts, the media library itself (customer_media),
 * colour/token presets (site_theme_preset_slug, theme_preset_slug,
 * dashboard_preset_slug, theme_settings) and the admin module menu
 * (menu_mode, menu_custom_menus).
 *
 * After a purge, installBaseline() makes an empty canvas the active theme so
 * the editor opens on a blank page instead of asking for an install.
 */
class CanvasCleanSlate
{
    public const KIND = 'customer-canvas-clean-slate';

    public const BASELINE_NAME = 'بوم خالی';

    private const SETTING_KEYS = [
        'active_canvas_theme_data', 'active_canvas_theme_name', 'active_canvas_theme_slug',
        'active_canvas_theme_id', 'active_theme_revision_id', 'canvas_draft_data', 'canvas_preview_token',
        'canvas_theme_backup_name', 'canvas_theme_backup_data', 'canvas_theme_backup_at',
    ];

    private const REVISION_SLOT_PREFIX = 'canvas_revision_';

    public function __construct(private ThemeInstanceRevisionService $revisions)
    {
    }

    public static function purger(): TablePurger
    {
        return new TablePurger(self::KIND, [
            PurgeTarget::rows('customer_media_usages', fn (Builder $q) => $q->where('consumer_type', 'theme_instance_revision')),
            PurgeTarget::table('theme_instance_revisions'),
            PurgeTarget::rows('settings', fn (Builder $q) => $q->whereIn('key', self::settingKeys())),
            PurgeTarget::table('page_revisions'),
            PurgeTarget::table('pages'),
            PurgeTarget::table('page_templates'),
            PurgeTarget::table('site_theme_templates'),
            PurgeTarget::table('menu_change_logs'),
            PurgeTarget::table('menu_revisions'),
            PurgeTarget::table('menu_items'),
            PurgeTarget::table('menus'),
        ]);
    }

    /**
     * The empty canvas: one blank home page, no header, no footer. Only the
     * locale and ownership flags of the platform starter document are kept.
     */
    public static function baselineDocument(): array
    {
        return [
            'global_styles' => ['chrome_owner' => 'theme', 'locale' => 'fa', 'direction' => 'rtl', 'presentation_css_owner' => 'platform'],
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'صفحه اصلی', 'slug' => '', 'is_home' => true, 'blocks' => []]],
            'theme_assets' => [],
        ];
    }

    /** Install the empty canvas, unless a theme is already active. */
    public function installBaseline(): ?ThemeInstanceRevision
    {
        if (!empty(Setting::get('active_canvas_theme_data'))) {
            return null;
        }

        $revision = $this->revisions->record(self::baselineDocument(), ThemeInstanceRevision::TYPE_INSTALL, [
            'theme_identifier' => ThemeInstanceRevisionService::DEFAULT_IDENTIFIER,
            'theme_name' => self::BASELINE_NAME,
            'active_theme_id' => '',
            'source_theme_id' => null,
            'source_theme_version' => null,
            'source_release_id' => null,
            'metadata' => ['baseline' => 'clean-slate'],
        ]);
        self::forgetCaches();

        return $revision;
    }

    /** Setting::get() and the public renderer both cache what was purged. */
    public static function forgetCaches(): void
    {
        Cache::forget('settings');
        Cache::forget('canvas_theme_data');
    }

    /** @return list<string> */
    private static function settingKeys(): array
    {
        $slots = DB::table('settings')
            ->where('key', 'like', self::REVISION_SLOT_PREFIX.'%')
            ->pluck('key')
            ->filter(fn (string $key) => str_starts_with($key, self::REVISION_SLOT_PREFIX))
            ->all();

        return [...self::SETTING_KEYS, ...$slots];
    }
}
