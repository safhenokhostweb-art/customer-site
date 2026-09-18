<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\License;
use App\Models\Setting;
use App\Services\CustomerMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5E (BLD-32) — CustomerMediaResolver::resolveValue() used
 * to issue one uncached `CustomerMedia::where('uuid', ...)` query per
 * `{media_id}` prop occurrence resolved during a render, with no
 * deduplication even for the exact same UUID appearing twice (dynamic
 * proof during Phase 6.5D final certification: 1 occurrence -> 1 query, 5
 * distinct -> 5 queries, the same UUID x3 -> 3 queries). Phase 6.5D's own
 * fix (real media localization finally working) made this a real,
 * previously-hidden cost for any Theme install with genuine Platform stock
 * imagery. Fixed with a request-scoped resolver cache plus a bulk preload
 * step at the one real public-render call site (ThemeCanvasPageController)
 * — see CustomerMediaResolver's own docblock for the full contract.
 */
class CustomerMediaResolverPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true, 'services.customer_provision_secret' => 'resolver-perf-secret']);
        License::create([
            'license_key' => 'RP-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);
    }

    private function uploadRealMedia(string $name = 'hero.png'): CustomerMedia
    {
        return app(CustomerMediaService::class)->upload(
            UploadedFile::fake()->image($name, 200, 200),
            ['title' => $name]
        );
    }

    /** Installs a Theme whose home page already carries real {media_id} refs — bypasses localization entirely (only string URLs get localized), isolating this test to the resolver's own read-time cost. */
    private function installWithHeroBlocks(array $blocks): void
    {
        $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'resolver-perf-theme',
            'source_theme_id' => '1',
            'theme_name' => 'Resolver Perf Theme',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => $blocks]],
        ], ['X-Provision-Secret' => 'resolver-perf-secret'])->assertOk()->assertJson(['success' => true]);

        Setting::set('active_canvas_theme_name', 'Resolver Perf Theme', 'theme');
    }

    /**
     * Writes active_canvas_theme_data directly, bypassing the real install
     * endpoint entirely — used ONLY for the forged/missing/cross-site
     * media tests below. This is deliberate: CustomerMediaService::
     * registerThemeRevisionUsages() (called from the real install path)
     * already enforces firstOrFail() at INSTALL time — a forged/foreign
     * media_id cannot be installed at all, a real and intentional,
     * pre-existing safety gate this phase does not touch. What section 9
     * actually asks to prove is the SEPARATE, render-time safety net for
     * a media_id that was valid when referenced but became missing/
     * foreign afterward (deleted, reassigned) — exactly what
     * CustomerMediaResolver::resolveValue()'s own docblock has always
     * promised ("missing/deleted/foreign/malformed -> ''"). Bypassing the
     * install gate here isolates the test to that render-time contract.
     */
    private function renderWithRawThemeData(array $themeData): \Illuminate\Testing\TestResponse
    {
        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Raw Theme', 'theme');
        \Illuminate\Support\Facades\Cache::forget('canvas_theme_data');

        return $this->get('/');
    }

    private function countCustomerMediaQueries(): int
    {
        $count = 0;
        DB::listen(function ($query) use (&$count) {
            if (str_contains($query->sql, '`customer_media`') || str_contains($query->sql, 'customer_media')) {
                $count++;
            }
        });
        $this->get('/')->assertOk();

        return $count;
    }

    private function heroBlock(string $id, string $uuid): array
    {
        return ['id' => $id, 'type' => 'hero', 'props' => ['title' => $id, 'background_image' => ['media_id' => $uuid]]];
    }

    // ── Same media ID repeated (section 10) ─────────────────────────────

    public function test_the_same_media_id_referenced_once_costs_one_query(): void
    {
        $media = $this->uploadRealMedia();
        $this->installWithHeroBlocks([$this->heroBlock('b0', $media->uuid)]);

        $count = $this->countCustomerMediaQueries();

        self::assertSame(1, $count);
    }

    public function test_the_same_media_id_referenced_ten_times_costs_the_same_single_query_as_referencing_it_once(): void
    {
        $media = $this->uploadRealMedia();
        $blocks = [];
        for ($i = 0; $i < 10; $i++) {
            $blocks[] = $this->heroBlock("b{$i}", $media->uuid);
        }
        $this->installWithHeroBlocks($blocks);

        $count = $this->countCustomerMediaQueries();

        self::assertSame(1, $count, 'the SAME media UUID referenced 10 times must not issue 10 separate lookups');
    }

    // ── Distinct media IDs (section 11) ─────────────────────────────────

    public function test_five_distinct_media_ids_cost_one_bulk_query_not_five(): void
    {
        $blocks = [];
        for ($i = 0; $i < 5; $i++) {
            $blocks[] = $this->heroBlock("b{$i}", $this->uploadRealMedia("img{$i}.png")->uuid);
        }
        $this->installWithHeroBlocks($blocks);

        $count = $this->countCustomerMediaQueries();

        self::assertSame(1, $count, '5 distinct media UUIDs on one page must resolve through one bulk query, not one query per UUID');
    }

    public function test_ten_distinct_media_ids_cost_one_bulk_query(): void
    {
        $blocks = [];
        for ($i = 0; $i < 10; $i++) {
            $blocks[] = $this->heroBlock("b{$i}", $this->uploadRealMedia("img{$i}.png")->uuid);
        }
        $this->installWithHeroBlocks($blocks);

        $count = $this->countCustomerMediaQueries();

        self::assertSame(1, $count, '10 distinct media UUIDs on one page must still resolve through exactly one bulk query — O(1) per document, not O(unique_media)');
    }

    // ── Forged / missing / cross-site media identity (section 9) ───────

    public function test_a_forged_media_id_that_does_not_exist_resolves_safely_to_empty_and_does_not_throw(): void
    {
        $themeData = ['pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
            $this->heroBlock('b0', 'not-a-real-uuid-00000000-0000-0000-0000-000000000000'),
        ]]]];

        $response = $this->renderWithRawThemeData($themeData);

        $response->assertOk();
        // No broken <img> pointing at a forged identity — the prop resolves
        // to an empty URL, exactly as a deleted/never-existed media_id
        // already did before Phase 6.5E's caching was added.
        self::assertStringNotContainsString('not-a-real-uuid', $response->getContent());
    }

    public function test_a_media_id_belonging_to_a_different_site_cannot_be_resolved(): void
    {
        // A real CustomerMedia row, valid at install time, whose ownership
        // later changed (reassigned site_id) — the render-time equivalent
        // of another tenant's data landing in this site's own table
        // (single-site-per-deployment architecture: this simulates cross-
        // site data at rest rather than a second live tenant, which this
        // deployment model doesn't have).
        $foreign = $this->uploadRealMedia('foreign.png');
        $foreign->site_id = $foreign->site_id + 1;
        $foreign->save();

        $themeData = ['pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
            $this->heroBlock('b0', $foreign->uuid),
        ]]]];

        $response = $this->renderWithRawThemeData($themeData);

        $response->assertOk();
        self::assertStringNotContainsString($foreign->storage_key, $response->getContent());
    }

    public function test_a_missing_media_id_is_cached_as_a_negative_result_and_never_reissues_a_lookup(): void
    {
        $blocks = [];
        for ($i = 0; $i < 5; $i++) {
            $blocks[] = $this->heroBlock("b{$i}", 'ghost-' . $i . '-0000-0000-0000-000000000000');
        }
        $themeData = ['pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => $blocks]]];
        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Raw Theme', 'theme');
        \Illuminate\Support\Facades\Cache::forget('canvas_theme_data');

        // Even 5 DISTINCT missing UUIDs must still cost exactly one bulk
        // query (the whereIn() finds none of them, all 5 get cached as
        // negative results from that single query) — not one failed
        // lookup attempt per missing UUID.
        $count = $this->countCustomerMediaQueries();

        self::assertSame(1, $count);
    }
}
