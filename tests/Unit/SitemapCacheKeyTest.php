<?php

namespace Tests\Unit;

use App\Http\Controllers\Front\SitemapController;
use Tests\TestCase;

/**
 * Phase 2.1 — the cache-key segment itself, proven without a database.
 *
 * These assertions are deliberately about the KEY, because the key is what
 * keeps one tenant's sitemap from being served to another. Everything that
 * needs a request, a license row or a real cache store is covered by
 * tests/Feature/SitemapCacheHostIsolationTest.php.
 */
class SitemapCacheKeyTest extends TestCase
{
    public function test_it_folds_case_scheme_and_trailing_slash_into_one_key(): void
    {
        $expected = SitemapController::normalizeCacheHost('Example.COM');

        $this->assertSame('example.com', $expected);
        $this->assertSame($expected, SitemapController::normalizeCacheHost('example.com'));
        $this->assertSame($expected, SitemapController::normalizeCacheHost('EXAMPLE.com'));
        $this->assertSame($expected, SitemapController::normalizeCacheHost('https://example.com/'));
        $this->assertSame($expected, SitemapController::normalizeCacheHost('http://example.com'));
    }

    public function test_it_folds_a_trailing_dot_into_the_same_key(): void
    {
        // A fully-qualified hostname may arrive with the root dot. That is
        // the same site, so it must not become a second cache entry.
        $this->assertSame(
            SitemapController::normalizeCacheHost('example.com'),
            SitemapController::normalizeCacheHost('example.com.'),
        );
    }

    public function test_it_reuses_the_platform_domain_comparison(): void
    {
        // Sitemap normalization is License::normalizeDomain() plus exactly one
        // extra rule (trailing-dot removal), so the two can never disagree on
        // how a real domain is read — proven here rather than duplicated.
        foreach (['Example.COM', 'https://example.com/', 'example.com'] as $variant) {
            $this->assertSame(
                \App\Models\License::normalizeDomain($variant),
                SitemapController::normalizeCacheHost($variant),
                'must match the license/domain binding for ' . $variant,
            );
        }

        // The single documented addition: a fully-qualified trailing dot.
        $this->assertSame('example.com.', \App\Models\License::normalizeDomain('example.com.'));
        $this->assertSame('example.com', SitemapController::normalizeCacheHost('example.com.'));
    }

    public function test_distinct_tenants_never_share_a_key(): void
    {
        $panel   = SitemapController::normalizeCacheHost('panel.safhenokhost.ir');
        $customer = SitemapController::normalizeCacheHost('safhenokhost.ir');

        $this->assertNotSame($panel, $customer);
        $this->assertNotSame($customer, SitemapController::normalizeCacheHost('www.safhenokhost.ir'));
    }

    public function test_it_bounds_the_host_segment_so_the_primary_key_cannot_overflow(): void
    {
        // `cache`.`key` is VARCHAR(255) and config('cache.prefix') is
        // prepended to it, so an untruncated host could exceed the column.
        $long = SitemapController::normalizeCacheHost(str_repeat('a', 300) . '.example.com');

        $this->assertLessThanOrEqual(200, strlen($long));
        $this->assertSame(200, strlen($long));
    }

    public function test_an_absent_host_normalizes_to_an_empty_segment(): void
    {
        $this->assertSame('', SitemapController::normalizeCacheHost(''));
        $this->assertSame('', SitemapController::normalizeCacheHost('   '));
    }
}
