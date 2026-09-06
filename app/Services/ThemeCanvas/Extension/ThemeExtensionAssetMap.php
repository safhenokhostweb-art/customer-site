<?php

namespace App\Services\ThemeCanvas\Extension;

/**
 * The consumer half of the cross-repo Extension-asset contract saas-platform
 * produces via ExtensionThemeRuntimeAssetProjector::project() and embeds as
 * `extension_assets` inside the Theme JSON ThemeCanvasController::
 * installToSite() writes directly into this site's own `settings` table
 * (App\Http\Controllers\Front\ThemeCanvasPageController::loadThemeData()).
 *
 * That JSON crosses a live cross-application, cross-network boundary
 * (saas-platform connects to THIS database directly) — so this class never
 * trusts its shape. Any type, entry, or URL that does not match the exact
 * contract (`{type: {css: string[], js: string[]}}`, absolute http(s) URLs
 * only — never a scheme-relative, `javascript:`, `data:`, or bare relative
 * path) is silently dropped rather than throwing: malformed runtime asset
 * metadata must fail safely, it must never break a live customer page or
 * ever cause an unsafe URL to reach rendered HTML.
 */
final class ThemeExtensionAssetMap
{
    /** @param array<string, array{css: string[], js: string[]}> $byType */
    private function __construct(
        private readonly array $byType,
    ) {}

    public static function fromArray(mixed $raw): self
    {
        if (! is_array($raw)) {
            return new self([]);
        }

        $byType = [];
        foreach ($raw as $type => $entry) {
            if (! is_string($type) || $type === '' || ! is_array($entry)) {
                continue;
            }

            $css = self::sanitizeUrls($entry['css'] ?? []);
            $js = self::sanitizeUrls($entry['js'] ?? []);

            if ($css === [] && $js === []) {
                continue;
            }

            $byType[$type] = ['css' => $css, 'js' => $js];
        }

        return new self($byType);
    }

    /** @return array{css: string[], js: string[]}|null null when this type declares no manifest asset — never a regression for a HasAssets-only or plain element. */
    public function assetsFor(string $type): ?array
    {
        return $this->byType[$type] ?? null;
    }

    /** @param mixed $list @return string[] */
    private static function sanitizeUrls(mixed $list): array
    {
        if (! is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $url) {
            if (is_string($url) && self::isSafeAbsoluteUrl($url)) {
                $out[] = $url;
            }
        }

        return $out;
    }

    /** Only an absolute http(s) URL is trusted — this metadata is about to become a live <link>/<script> src on a real customer page. */
    private static function isSafeAbsoluteUrl(string $url): bool
    {
        $parts = parse_url($url);

        return $parts !== false
            && isset($parts['scheme'], $parts['host'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }
}
