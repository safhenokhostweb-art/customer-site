<?php

namespace Tests\Unit\ThemeCanvas\Extension;

use App\Services\ThemeCanvas\Extension\ThemeExtensionAssetMap;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ThemeExtensionAssetMap is the ONE boundary that decides whether a value
 * crossing from saas-platform's cross-network settings write ever becomes a
 * live <link>/<script> src on a customer page. Every case here is a shape
 * saas-platform's own producer (ExtensionThemeRuntimeAssetProjector) should
 * never emit — this class must still stay safe if it ever does (a bad
 * deploy, a corrupted settings row, a future producer bug).
 */
class ThemeExtensionAssetMapTest extends TestCase
{
    public function test_valid_map_resolves_assets_for_type(): void
    {
        $map = ThemeExtensionAssetMap::fromArray([
            'ext:acme:gallery' => [
                'css' => ['https://saas.example.test/vendor/acme/gallery/1.0.0/g.css'],
                'js' => ['https://saas.example.test/vendor/acme/gallery/1.0.0/g.js'],
            ],
        ]);

        $this->assertSame(
            ['css' => ['https://saas.example.test/vendor/acme/gallery/1.0.0/g.css'], 'js' => ['https://saas.example.test/vendor/acme/gallery/1.0.0/g.js']],
            $map->assetsFor('ext:acme:gallery')
        );
    }

    public function test_unknown_type_returns_null(): void
    {
        $map = ThemeExtensionAssetMap::fromArray(['ext:acme:gallery' => ['css' => ['https://x.test/a.css'], 'js' => []]]);

        $this->assertNull($map->assetsFor('ext:acme:other'));
    }

    #[DataProvider('malformedTopLevelProvider')]
    public function test_malformed_top_level_input_yields_empty_map(mixed $raw): void
    {
        $map = ThemeExtensionAssetMap::fromArray($raw);

        $this->assertNull($map->assetsFor('ext:acme:gallery'));
    }

    public static function malformedTopLevelProvider(): array
    {
        return [
            'null' => [null],
            'string' => ['not-an-array'],
            'int' => [42],
            'list, not a map' => [['a', 'b']],
        ];
    }

    public function test_non_array_entry_for_a_type_is_dropped(): void
    {
        $map = ThemeExtensionAssetMap::fromArray(['ext:acme:gallery' => 'not-an-object']);

        $this->assertNull($map->assetsFor('ext:acme:gallery'));
    }

    public function test_unsafe_url_schemes_are_rejected(): void
    {
        $map = ThemeExtensionAssetMap::fromArray([
            'ext:acme:gallery' => [
                'css' => ['javascript:alert(1)', 'data:text/css,body{}', '//evil.test/x.css', 'ftp://x.test/x.css'],
                'js' => [],
            ],
        ]);

        // Every candidate URL was unsafe -> the whole entry contributes nothing.
        $this->assertNull($map->assetsFor('ext:acme:gallery'));
    }

    public function test_relative_and_non_string_entries_are_dropped_but_valid_siblings_survive(): void
    {
        $map = ThemeExtensionAssetMap::fromArray([
            'ext:acme:gallery' => [
                'css' => ['/relative/not-allowed.css', 123, null, 'https://saas.example.test/ok.css'],
                'js' => 'not-even-an-array',
            ],
        ]);

        $this->assertSame(['css' => ['https://saas.example.test/ok.css'], 'js' => []], $map->assetsFor('ext:acme:gallery'));
    }

    public function test_entry_with_no_surviving_urls_is_absent_entirely(): void
    {
        $map = ThemeExtensionAssetMap::fromArray([
            'ext:acme:gallery' => ['css' => ['javascript:x'], 'js' => []],
        ]);

        $this->assertNull($map->assetsFor('ext:acme:gallery'));
    }

    public function test_empty_map_never_throws(): void
    {
        $map = ThemeExtensionAssetMap::fromArray([]);

        $this->assertNull($map->assetsFor('ext:acme:anything'));
    }
}
