<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\DataBinding\DynamicApiDataSource;
use App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use SaaSPlatform\ThemeRuntime\DataBinding\DataFetcher;
use SaaSPlatform\ThemeRuntime\DataBinding\DataSourceConfig;
use Tests\Support\CreatesFoodMartBindingFixtures;
use Tests\TestCase;

class Phase3CDynamicBindingTest extends TestCase
{
    use CreatesFoodMartBindingFixtures;
    use RefreshDatabase;

    public function test_internal_product_query_filters_by_collection_slug_and_renders_success(): void
    {
        $this->createFoodMartBindingFixtures();
        $registry = app(ThemeDataBindingRegistry::class);
        $resolved = $registry->resolveDynamicSource('products', [
            'collection_slug' => 'fixture-fruit',
            'sort' => 'catalog_order',
            'limit' => 12,
        ]);

        self::assertSame('resolved', $resolved['status']);
        self::assertContains('Fixture Sale Apple', array_column($resolved['items'], 'title'));
        self::assertNotContains('Fixture Plain Bread', array_column($resolved['items'], 'title'));

        $renderer = app(ThemeCanvasRenderer::class)->setContext([
            'data_fetcher' => new DataFetcher(
                internalResolver: static fn (): array => $resolved['items'],
            ),
        ]);
        $html = $renderer->renderBlock([
            'id' => 'collection-products',
            'type' => 'dynamic_product_grid',
            'props' => [
                'source_type' => 'internal',
                'endpoint_url' => 'products',
                'query_params' => '{"collection_slug":"fixture-fruit"}',
            ],
        ]);

        self::assertStringContainsString('data-state="success"', $html);
        self::assertStringContainsString('Fixture Sale Apple', $html);
        self::assertStringNotContainsString('Fixture Plain Bread', $html);
    }

    public function test_api_feed_uses_exact_host_allowlist_and_escapes_response_content(): void
    {
        config(['theme-runtime.dynamic_api_hosts' => ['feeds.example.test']]);
        Http::fake([
            'https://feeds.example.test/*' => Http::response([
                'items' => [[
                    'title' => '<script>alert(1)</script>News',
                    'excerpt' => 'Safe summary',
                    'url' => 'javascript:alert(1)',
                ]],
            ]),
        ]);

        $api = app(DynamicApiDataSource::class);
        $source = DataSourceConfig::fromArray([
            'source_type' => 'api',
            'endpoint_url' => 'https://feeds.example.test/posts',
            'query_params' => '{"limit":4}',
        ]);
        $items = $api->fetch($source);
        self::assertCount(1, $items);

        $renderer = app(ThemeCanvasRenderer::class)->setContext([
            'data_fetcher' => new DataFetcher(apiResolver: static fn (): array => $items),
        ]);
        $html = $renderer->renderBlock([
            'id' => 'remote-feed',
            'type' => 'dynamic_feed_slider',
            'props' => [
                'source_type' => 'api',
                'endpoint_url' => 'https://feeds.example.test/posts',
                'query_params' => '{"limit":4}',
            ],
        ]);

        self::assertStringContainsString('data-state="success"', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;News', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringNotContainsString('href="javascript:', $html);
        Http::assertSentCount(1);
    }

    public function test_api_feed_rejects_non_allowlisted_hosts_without_a_request(): void
    {
        config(['theme-runtime.dynamic_api_hosts' => ['feeds.example.test']]);
        Http::fake();

        $this->expectException(\InvalidArgumentException::class);
        try {
            app(DynamicApiDataSource::class)->fetch(DataSourceConfig::fromArray([
                'source_type' => 'api',
                'endpoint_url' => 'https://untrusted.example.test/posts',
                'query_params' => '{}',
            ]));
        } finally {
            Http::assertNothingSent();
        }
    }
}
