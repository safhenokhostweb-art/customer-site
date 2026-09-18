<?php

namespace App\Services\ThemeCanvas;

use App\Services\CustomerMediaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * MASTER_THEME_MEDIA_LOCALIZATION_GAP — canonical, bounded fix (TASK 3/3
 * Phase 6.5, BLD-21).
 *
 * Before this, a Theme installed from saas-platform kept every stock/master
 * image hotlinked to saas-platform's own storage domain forever:
 * CustomerMediaResolver::resolveValue() treats any plain string URL as
 * "legacy, pass through unchanged" by design (it has no way to know whether
 * a string is a customer's own externally-hosted image or a master-theme
 * asset it should have imported) — nothing ever pulled these into the
 * customer's own CustomerMediaService.
 *
 * This service runs ONCE, at install time
 * (Front\PageBuilderController::installTheme()), strictly BEFORE
 * ThemeInstanceRevisionService::record() creates the immutable revision —
 * so localized {media_id} references are what actually gets frozen into
 * revision history, never a later, riskier rewrite of already-immutable
 * data. It walks the SAME canonical media-prop inventory
 * CustomerMediaResolver::MEDIA_PROPS/MEDIA_REPEATERS already defines (no
 * second, drifting list) and rewrites any URL hosted on the trusted
 * allowlist into a real, locally-owned {"media_id": "<uuid>"} reference.
 *
 * Security posture — this is NOT a general-purpose remote-fetch capability:
 *  - Only https URLs whose host exactly matches
 *    config('services.trusted_media_hosts') (a fixed, operator-controlled
 *    allowlist — never a customer- or theme-author-supplied value) are ever
 *    fetched. Any other host is left completely untouched.
 *  - No userinfo in the URL; redirects are never followed (a redirect off
 *    the allowlisted host must fail closed, not be silently trusted).
 *  - Bounded timeout, bounded byte size, and real MIME/dimension validation
 *    on the downloaded bytes (CustomerMediaService::importFromRemoteBytes())
 *    — a declared Content-Type header is never trusted alone.
 *  - Deduplicated per site by source URL, so re-installing/upgrading the
 *    same theme never creates duplicate media rows or re-fetches unchanged
 *    images.
 *  - Graceful, per-image failure: any network/validation failure for one
 *    URL is logged and that one field is left as the original URL — a
 *    single bad image must never block an entire theme install.
 */
class ThemeMediaLocalizationService
{
    private const TIMEOUT_SECONDS = 8;
    private const ALLOWED_CONTENT_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @var array<string, string|array{media_id: string}> per-run memoization — one theme install rarely repeats a URL, but a shared hero/logo across many blocks can. */
    private array $resolved = [];

    public function __construct(private readonly CustomerMediaService $media)
    {
    }

    /**
     * @param array{header_blocks?: array, footer_blocks?: array, header_variants?: array, footer_variants?: array, pages: array} $themeData
     */
    public function localize(array $themeData): array
    {
        $hosts = $this->trustedHosts();
        if ($hosts === []) {
            return $themeData; // no allowlist configured — feature stays inert, install is never blocked by it
        }

        foreach (['header_blocks', 'footer_blocks'] as $key) {
            if (isset($themeData[$key]) && is_array($themeData[$key])) {
                $themeData[$key] = $this->localizeBlockList($themeData[$key], $hosts);
            }
        }

        foreach (['header_variants', 'footer_variants'] as $key) {
            if (isset($themeData[$key]) && is_array($themeData[$key])) {
                $themeData[$key] = array_map(function ($variant) use ($hosts) {
                    if (is_array($variant) && isset($variant['blocks']) && is_array($variant['blocks'])) {
                        $variant['blocks'] = $this->localizeBlockList($variant['blocks'], $hosts);
                    }
                    return $variant;
                }, $themeData[$key]);
            }
        }

        if (isset($themeData['pages']) && is_array($themeData['pages'])) {
            $themeData['pages'] = array_map(function ($page) use ($hosts) {
                if (is_array($page) && isset($page['blocks']) && is_array($page['blocks'])) {
                    $page['blocks'] = $this->localizeBlockList($page['blocks'], $hosts);
                }
                return $page;
            }, $themeData['pages']);
        }

        return $themeData;
    }

    /** @param string[] $hosts */
    private function localizeBlockList(array $blocks, array $hosts): array
    {
        foreach ($blocks as $index => $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? null;
            $props = $block['props'] ?? [];
            if (is_string($type) && is_array($props)) {
                $block['props'] = $this->localizeBlockProps($type, $props, $hosts);
            }

            // Recurse into real nested block trees (sections/columns/tabs/
            // accordion panels) — mirrors the same traversal
            // RenderIntegrityCheck already uses, so container blocks whose
            // children carry media (a hero inside a section, a gallery
            // inside a tab panel) are localized too, not just top-level
            // blocks.
            if (isset($block['children']) && is_array($block['children'])) {
                $block['children'] = $this->localizeBlockList($block['children'], $hosts);
            }
            if (isset($block['blocks']) && is_array($block['blocks'])) {
                $block['blocks'] = $this->localizeBlockList($block['blocks'], $hosts);
            }
            if (isset($block['props']['tabs']) && is_array($block['props']['tabs'])) {
                $block['props']['tabs'] = array_map(function ($tab) use ($hosts) {
                    if (is_array($tab) && isset($tab['blocks']) && is_array($tab['blocks'])) {
                        $tab['blocks'] = $this->localizeBlockList($tab['blocks'], $hosts);
                    }
                    return $tab;
                }, $block['props']['tabs']);
            }
            if ($type === 'accordion' && isset($block['props']['items']) && is_array($block['props']['items'])) {
                $block['props']['items'] = array_map(function ($item) use ($hosts) {
                    if (is_array($item) && isset($item['blocks']) && is_array($item['blocks'])) {
                        $item['blocks'] = $this->localizeBlockList($item['blocks'], $hosts);
                    }
                    return $item;
                }, $block['props']['items']);
            }

            $blocks[$index] = $block;
        }

        return $blocks;
    }

    /** @param string[] $hosts */
    private function localizeBlockProps(string $type, array $props, array $hosts): array
    {
        foreach (CustomerMediaResolver::MEDIA_PROPS[$type] ?? [] as $propKey) {
            if (isset($props[$propKey]) && is_string($props[$propKey]) && $props[$propKey] !== '') {
                $props[$propKey] = $this->localizeUrl($props[$propKey], $hosts);
            }
        }

        if ($repeater = CustomerMediaResolver::MEDIA_REPEATERS[$type] ?? null) {
            $raw = $props[$repeater['prop']] ?? null;
            $items = match (true) {
                is_array($raw) => $raw,
                is_string($raw) && $raw !== '' => json_decode($raw, true) ?? [],
                default => null,
            };
            if (is_array($items)) {
                foreach ($items as $i => $item) {
                    if (is_array($item) && isset($item[$repeater['field']]) && is_string($item[$repeater['field']]) && $item[$repeater['field']] !== '') {
                        $items[$i][$repeater['field']] = $this->localizeUrl($item[$repeater['field']], $hosts);
                    }
                }
                $props[$repeater['prop']] = $repeater['storage'] === 'json' ? json_encode($items, JSON_UNESCAPED_UNICODE) : $items;
            }
        }

        return $props;
    }

    /** @param string[] $hosts @return string|array{media_id: string} */
    private function localizeUrl(string $url, array $hosts): string|array
    {
        if (array_key_exists($url, $this->resolved)) {
            return $this->resolved[$url];
        }

        return $this->resolved[$url] = $this->attemptLocalize($url, $hosts) ?? $url;
    }

    /** @param string[] $hosts @return array{media_id: string}|null */
    private function attemptLocalize(string $url, array $hosts): ?array
    {
        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || !in_array($host, $hosts, true)) {
            return null;
        }

        if ($existing = $this->media->findBySourceUrl($url)) {
            return ['media_id' => $existing->uuid];
        }

        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($url);
        } catch (Throwable $e) {
            Log::warning('theme_media_localization.fetch_failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }

        if (!$response->successful()) {
            Log::warning('theme_media_localization.fetch_non_2xx', ['url' => $url, 'status' => $response->status()]);
            return null;
        }

        $contentType = strtolower(trim(explode(';', $response->header('Content-Type') ?? '')[0]));
        if (!in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
            Log::warning('theme_media_localization.unexpected_content_type', ['url' => $url, 'content_type' => $contentType]);
            return null;
        }

        try {
            $media = $this->media->importFromRemoteBytes(
                $response->body(),
                basename((string) ($parts['path'] ?? 'theme-media')),
                $contentType,
                ['source_url' => $url]
            );
        } catch (Throwable $e) {
            Log::warning('theme_media_localization.import_failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }

        return ['media_id' => $media->uuid];
    }

    /** @return string[] */
    private function trustedHosts(): array
    {
        $configured = config('services.trusted_media_hosts', []);
        return array_values(array_filter(array_map(
            fn ($host) => strtolower(trim((string) $host)),
            is_array($configured) ? $configured : []
        )));
    }
}
