<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\ThemeCanvas\CanvasCleanSlate;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Concerns\EnsuresInstallationLock;
use Tests\TestCase;

/**
 * canvas:clean-slate removes the site's Canvas theme state, builder pages and
 * menus behind a verified, restorable snapshot, then installs the empty
 * canvas, so the editor opens on a blank page. Media files, colour/token
 * presets and the admin module menu are kept.
 */
class CanvasCleanSlateCommandTest extends TestCase
{
    use EnsuresInstallationLock, RefreshDatabase;

    private const DUMPED = [
        'theme_instance_revisions', 'settings', 'pages', 'menus', 'menu_items', 'customer_media', 'customer_media_usages',
    ];

    /** @var list<string> snapshot directories that existed before the test */
    private array $existingSnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureInstallationLock();
        $this->withoutVite();
        config(['site.owner_site' => true]);
        $this->existingSnapshots = $this->snapshots();
    }

    protected function tearDown(): void
    {
        foreach (array_diff($this->snapshots(), $this->existingSnapshots) as $dir) {
            File::deleteDirectory($dir);
        }
        $this->releaseInstallationLock();
        parent::tearDown();
    }

    public function test_dry_run_lists_rows_and_changes_nothing(): void
    {
        $this->legacySite();
        $before = $this->dump();

        $this->artisan('canvas:clean-slate', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame($before, $this->dump());
        $this->assertSame([], $this->newSnapshots());
    }

    public function test_purge_removes_legacy_state_and_the_editor_opens_on_a_blank_canvas(): void
    {
        $this->legacySite();

        $this->artisan('canvas:clean-slate', ['--force' => true])->assertExitCode(0);

        foreach (['pages', 'menus', 'menu_items'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} should be empty");
        }
        foreach (['canvas_draft_data', 'canvas_revision_1', 'canvas_revision_1_at', 'canvas_theme_backup_data'] as $key) {
            $this->assertDatabaseMissing('settings', ['key' => $key]);
        }
        $this->assertSame('', Setting::get('active_canvas_theme_slug'));
        $this->assertSame(CanvasCleanSlate::BASELINE_NAME, Setting::get('active_canvas_theme_name'));

        // Kept: tokens, the admin module menu, the media library, other consumers' usages.
        $this->assertSame('ocean', Setting::get('site_theme_preset_slug'));
        $this->assertSame('local', Setting::get('menu_mode'));
        $this->assertSame(1, DB::table('customer_media')->count());
        $this->assertSame(['product'], DB::table('customer_media_usages')->pluck('consumer_type')->all());

        // The only revision left is the baseline, and it is what is live.
        $baseline = ThemeInstanceRevision::sole();
        $this->assertSame('clean-slate', $baseline->metadata['baseline'] ?? null);
        $this->assertSame(CanvasCleanSlate::baselineDocument(), json_decode(Setting::get('active_canvas_theme_data'), true));
        $this->assertSame((string) $baseline->id, (string) Setting::get('active_theme_revision_id'));

        $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-edit'))
            ->assertOk()
            // The editor also lists the two system sign-in/sign-up pages (auth_canvas).
            ->assertViewHas('pages', fn (array $pages) => array_keys($pages) === ['home', '__header__', '__footer__', 'login', 'register']
                && $pages['home']['blocks'] === []
                && $pages['__header__']['blocks'] === []
                && $pages['__footer__']['blocks'] === []);

        $this->assertCount(1, $this->newSnapshots());
        CanvasCleanSlate::purger()->verify($this->newSnapshots()[0]);
    }

    public function test_no_baseline_leaves_the_site_without_an_active_theme(): void
    {
        $this->legacySite();

        $this->artisan('canvas:clean-slate', ['--force' => true, '--no-baseline' => true])->assertExitCode(0);

        $this->assertSame(0, ThemeInstanceRevision::count());
        $this->assertEmpty(Setting::get('active_canvas_theme_data'));
        $this->actingAs($this->admin())
            ->get(route('admin.theme-design.canvas-edit'))
            ->assertRedirect(route('admin.theme-design.canvas'));
    }

    public function test_restore_puts_back_exactly_what_was_purged(): void
    {
        $this->legacySite();
        $before = $this->dump();

        $this->artisan('canvas:clean-slate', ['--force' => true])->assertExitCode(0);
        $legacySnapshot = $this->newSnapshots()[0];
        // A second purge in the same second would collide on the directory name.
        $this->travel(1)->seconds();

        // The baseline must go first: restore never merges into live rows.
        $this->artisan('canvas:clean-slate', ['--restore' => $legacySnapshot, '--force' => true])->assertExitCode(1);
        $this->artisan('canvas:clean-slate', ['--force' => true, '--no-baseline' => true])->assertExitCode(0);
        $this->artisan('canvas:clean-slate', ['--restore' => $legacySnapshot, '--force' => true])->assertExitCode(0);

        $this->assertSame($before, $this->dump());
    }

    public function test_production_requires_the_database_name_and_non_interactive_runs_require_force(): void
    {
        $this->legacySite();
        $before = $this->dump();

        $this->artisan('canvas:clean-slate', ['--no-interaction' => true])->assertExitCode(1);

        $this->app['env'] = 'production';
        $this->artisan('canvas:clean-slate', ['--force' => true])->assertExitCode(1);
        $this->artisan('canvas:clean-slate', ['--force' => true, '--confirm-database' => 'some_other_db'])->assertExitCode(1);

        $this->assertSame($before, $this->dump());
        $this->assertSame([], $this->newSnapshots());
    }

    /** A site carrying the legacy state the purge exists to remove. */
    private function legacySite(): void
    {
        $revision = app(ThemeInstanceRevisionService::class)->record([
            'header_blocks' => [['id' => 'legacy_header', 'type' => 'heading', 'props' => ['text' => 'Legacy']]],
            'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'Home', 'slug' => '', 'blocks' => [['id' => 'legacy_hero', 'type' => 'hero', 'props' => ['title' => 'Legacy']]]]],
        ], ThemeInstanceRevision::TYPE_PUBLISH, ['theme_identifier' => 'foodmart', 'theme_name' => 'FoodMart', 'active_theme_id' => '4']);

        Setting::set('canvas_draft_data', '{"pages":{}}', 'theme');
        Setting::set('canvas_revision_1', '{"pages":{}}', 'theme');
        Setting::set('canvas_revision_1_at', '2026-10-01 10:00:00', 'theme');
        Setting::set('canvas_theme_backup_data', '{"pages":{}}', 'theme');
        Setting::set('site_theme_preset_slug', 'ocean', 'theme');
        Setting::set('menu_mode', 'local', 'menus');

        $media = DB::table('customer_media')->insertGetId([
            'site_id' => 1, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'disk' => 'public',
            'storage_key' => 'media/legacy.jpg', 'original_filename' => 'legacy.jpg', 'extension' => 'jpg',
            'category' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('customer_media_usages')->insert([
            ['customer_media_id' => $media, 'consumer_type' => 'theme_instance_revision', 'consumer_id' => $revision->id, 'reference_path' => 'pages.home.blocks.0.props.image', 'created_at' => now(), 'updated_at' => now()],
            ['customer_media_id' => $media, 'consumer_type' => 'product', 'consumer_id' => 7, 'reference_path' => 'image', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('pages')->insert(['title' => 'Legacy about', 'slug' => 'legacy-about', 'created_at' => now(), 'updated_at' => now()]);
        $menu = DB::table('menus')->insertGetId(['name' => 'Main', 'location' => 'primary', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('menu_items')->insert(['menu_id' => $menu, 'title' => 'Home', 'url' => '/', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function admin(): User
    {
        License::create([
            'license_key' => 'CLEAN-SLATE-'.uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    /** @return list<string> */
    private function snapshots(): array
    {
        $root = storage_path('app/clean-slate');

        return is_dir($root) ? File::directories($root) : [];
    }

    /** @return list<string> */
    private function newSnapshots(): array
    {
        return array_values(array_diff($this->snapshots(), $this->existingSnapshots));
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function dump(): array
    {
        $dump = [];
        foreach (self::DUMPED as $table) {
            $dump[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        return $dump;
    }
}
