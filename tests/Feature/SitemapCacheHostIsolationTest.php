<?php

namespace Tests\Feature;

use App\Http\Controllers\Front\SitemapController;
use App\Models\Form;
use App\Models\License;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 2.1 — host isolation and invalidation of the real sitemap cache,
 * proven against the REAL cache store (config('cache.default') is
 * CACHE_STORE=database) and asserted by reading the `cache` table directly.
 * A facade-level assertion could pass while the row was never written;
 * inspecting the table cannot.
 *
 * Requires the database because proving "the row is gone" means proving it
 * left persistent storage.
 */
class SitemapCacheHostIsolationTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();

        // phpunit.xml pins CACHE_STORE=array for suite speed, but every
        // deployed environment uses CACHE_STORE=database. This test exists
        // precisely to prove the real store's behaviour, so force it here
        // rather than asserting against a facade that never persists.
        config(['cache.default' => 'database']);
        Cache::purge();
        DB::table('cache')->delete();
    }

    private function seedLicenseFor(string $domain): License
    {
        return License::query()->create([
            'domain'            => $domain,
            'license_key'       => 'W62-' . uniqid(),
            'expires_at'        => now()->addYear(),
            'modules'           => [],
            'platform_valid'    => true,
            'last_validated_at' => now(),
        ]);
    }

    /**
     * Sitemap cache keys as stored, with the store prefix stripped so the
     * assertions read the real key SitemapController builds.
     *
     * @return array<int, string>
     */
    private function storedSitemapKeys(): array
    {
        $prefix = (string) config('cache.prefix', '');

        return DB::table('cache')
            ->where('key', 'like', '%sitemap_xml%')
            ->pluck('key')
            ->map(fn (string $key) => str_starts_with($key, $prefix) ? substr($key, strlen($prefix)) : $key)
            ->all();
    }

    /**
     * Every <loc> in a sitemap response, so host assertions are exact rather
     * than substring matches (a plain assertSee('safhenokhost.ir') would also
     * match a leaked 'panel.safhenokhost.ir').
     *
     * @return array<int, string>
     */
    private function locs(string $xml): array
    {
        preg_match_all('#<loc>(.*?)</loc>#s', $xml, $m);

        return $m[1] ?? [];
    }

    /**
     * @return array<int, string> the host of every <loc>
     */
    private function locHosts(string $xml): array
    {
        return array_values(array_unique(array_map(
            fn (string $loc) => (string) parse_url(trim($loc), PHP_URL_HOST),
            $this->locs($xml)
        )));
    }

    // ── Isolation ───────────────────────────────────────────────────────────

    public function test_two_tenant_domains_produce_two_distinct_cache_entries(): void
    {
        $this->seedLicenseFor('panel.safhenokhost.ir');
        $this->seedLicenseFor('safhenokhost.ir');

        $this->get('http://panel.safhenokhost.ir/sitemap.xml')->assertOk();
        $this->get('http://safhenokhost.ir/sitemap.xml')->assertOk();

        $keys = $this->storedSitemapKeys();

        $this->assertContains('sitemap_xml_panel.safhenokhost.ir', $keys);
        $this->assertContains('sitemap_xml_safhenokhost.ir', $keys);
    }

    public function test_the_panel_sitemap_is_never_served_to_the_customer_host(): void
    {
        $this->seedLicenseFor('panel.safhenokhost.ir');
        $this->seedLicenseFor('safhenokhost.ir');

        // Warm the cache from the panel host only.
        $this->get('http://panel.safhenokhost.ir/sitemap.xml')->assertOk();
        $this->assertContains('sitemap_xml_panel.safhenokhost.ir', $this->storedSitemapKeys());

        // The customer host must build its own entry and emit only its own URLs.
        $customer = $this->get('http://safhenokhost.ir/sitemap.xml')->assertOk();

        $this->assertSame(['safhenokhost.ir'], $this->locHosts($customer->getContent()));
        $this->assertNotContains('panel.safhenokhost.ir', $this->locs($customer->getContent()));
        $this->assertContains('sitemap_xml_safhenokhost.ir', $this->storedSitemapKeys());
    }

    public function test_an_unknown_host_is_served_but_never_written_to_the_cache(): void
    {
        // No license row and not config('app.url') -> not this installation's
        // own host, so it must not be allowed to mint a cache row.
        $response = $this->get('http://attacker-spoofed.example/sitemap.xml');

        $response->assertOk();
        $this->assertSame(['attacker-spoofed.example'], $this->locHosts($response->getContent()));
        $this->assertSame(
            [],
            $this->storedSitemapKeys(),
            'a host that is not a real tenant must never mint a cache row',
        );
    }

    public function test_a_spoofed_forwarded_host_cannot_mint_a_cache_row(): void
    {
        // bootstrap/app.php registers trustProxies(at: '*'), so X-Forwarded-Host
        // is honored by request()->getHost(). This is the exact vector that
        // made the old global key unsafe, and it must be inert now.
        $this->seedLicenseFor('safhenokhost.ir');

        $response = $this->get('http://safhenokhost.ir/sitemap.xml', [
            'X-Forwarded-Host' => 'attacker-spoofed.example',
        ]);

        $response->assertOk();
        $this->assertSame(
            [],
            $this->storedSitemapKeys(),
            'a client-controlled X-Forwarded-Host must never reach the cache',
        );
    }

    // ── Invalidation ────────────────────────────────────────────────────────

    public function test_clear_cache_for_one_host_leaves_the_other_host_untouched(): void
    {
        $this->seedLicenseFor('alpha.test');
        $this->seedLicenseFor('beta.test');

        $this->get('http://alpha.test/sitemap.xml')->assertOk();
        $this->get('http://beta.test/sitemap.xml')->assertOk();

        SitemapController::clearCache('alpha.test');

        $keys = $this->storedSitemapKeys();
        $this->assertNotContains('sitemap_xml_alpha.test', $keys);
        $this->assertContains('sitemap_xml_beta.test', $keys);
    }

    public function test_a_global_flush_removes_every_cached_host(): void
    {
        $this->seedLicenseFor('alpha.test');
        $this->seedLicenseFor('beta.test');

        $this->get('http://alpha.test/sitemap.xml')->assertOk();
        $this->get('http://beta.test/sitemap.xml')->assertOk();
        $this->assertNotEmpty($this->storedSitemapKeys());

        SitemapController::clearCache();

        $this->assertSame([], $this->storedSitemapKeys());
    }

    public function test_clear_cache_normalizes_the_host_it_is_given(): void
    {
        $this->seedLicenseFor('alpha.test');
        $this->get('http://alpha.test/sitemap.xml')->assertOk();
        $this->assertContains('sitemap_xml_alpha.test', $this->storedSitemapKeys());

        SitemapController::clearCache('https://ALPHA.test/');

        $this->assertNotContains('sitemap_xml_alpha.test', $this->storedSitemapKeys());
    }

    public function test_paginated_sitemap_parts_are_host_scoped_and_clearable(): void
    {
        $this->seedLicenseFor('alpha.test');
        $this->seedLicenseFor('beta.test');

        $this->get('http://alpha.test/sitemap-part-0.xml')->assertOk();
        $this->get('http://beta.test/sitemap-part-0.xml')->assertOk();

        $keys = $this->storedSitemapKeys();
        $this->assertContains('sitemap_xml_alpha.test_part_0', $keys);
        $this->assertContains('sitemap_xml_beta.test_part_0', $keys);

        SitemapController::clearCache('alpha.test');

        $keys = $this->storedSitemapKeys();
        $this->assertNotContains('sitemap_xml_alpha.test_part_0', $keys);
        $this->assertContains('sitemap_xml_beta.test_part_0', $keys);
    }

    // ── Observer wiring: content change must invalidate ─────────────────────

    public static function contentProvider(): array
    {
        return [
            'page'    => [Page::class,    ['title' => 'p', 'slug' => 'p', 'body' => '<p>x</p>', 'is_published' => true]],
            'post'    => [Post::class,    ['title' => 'b', 'slug' => 'b', 'body' => '<p>x</p>', 'is_published' => true]],
            'product' => [Product::class, ['title' => 'x', 'slug' => 'x', 'is_active' => true]],
            'form'    => [Form::class,    ['title' => 'f', 'slug' => 'f', 'is_active' => true]],
        ];
    }

    /**
     * @dataProvider contentProvider
     */
    public function test_saving_each_sitemap_contributing_model_flushes_the_cached_sitemap(string $modelClass, array $attributes): void
    {
        $this->seedLicenseFor('alpha.test');
        $this->get('http://alpha.test/sitemap.xml')->assertOk();
        $this->assertContains('sitemap_xml_alpha.test', $this->storedSitemapKeys());

        // posts.user_id is NOT NULL with no default; it is not part of the
        // sitemap contract, it is just a fixture requirement.
        if ($modelClass === Post::class) {
            $attributes['user_id'] = User::factory()->create()->id;
        }

        $modelClass::create($attributes);

        $this->assertNotContains(
            'sitemap_xml_alpha.test',
            $this->storedSitemapKeys(),
            $modelClass . ' is read by collectEntries() and must invalidate the sitemap',
        );
    }

    public function test_deleting_a_sitemap_contributing_model_flushes_the_cached_sitemap(): void
    {
        $this->seedLicenseFor('alpha.test');

        $page = Page::create(['title' => 'gone', 'slug' => 'gone', 'body' => '<p>x</p>', 'is_published' => true]);

        $this->get('http://alpha.test/sitemap.xml')->assertOk();
        $this->assertContains('sitemap_xml_alpha.test', $this->storedSitemapKeys());

        $page->delete();

        $this->assertNotContains('sitemap_xml_alpha.test', $this->storedSitemapKeys());
    }

    public function test_a_saved_page_actually_reappears_in_the_next_sitemap(): void
    {
        $this->seedLicenseFor('alpha.test');

        // Warm the cache while the page does not exist yet.
        $this->get('http://alpha.test/sitemap.xml')
            ->assertOk();
        $this->assertNotContains('http://alpha.test/page/brand-new', $this->locs(
            $this->get('http://alpha.test/sitemap.xml')->getContent()
        ));

        Page::create([
            'title' => 'Brand New', 'slug' => 'brand-new',
            'body' => '<p>x</p>', 'is_published' => true,
        ]);

        // The real user-visible consequence: the new URL is served, without
        // waiting out the cache TTL.
        $this->assertContains('http://alpha.test/page/brand-new', $this->locs(
            $this->get('http://alpha.test/sitemap.xml')->getContent()
        ));
    }
}
