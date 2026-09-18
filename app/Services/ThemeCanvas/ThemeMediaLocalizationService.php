<?php

namespace App\Services\ThemeCanvas;

use App\Services\CustomerMediaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
 *  - Trust comes ONLY from operator-controlled configuration
 *    (config('site.platform_url'), the same single canonical Platform
 *    origin PlatformApiService/licensing/marketplace already use — never a
 *    second, competing source of truth), never from anything a Theme
 *    payload itself declares. See trustedOrigin()/originMatches() —
 *    TASK 3/3 Phase 6.5D (BLD-30): a real certification found the
 *    original https-only rule rejected every real URL in this actual
 *    environment (PLATFORM_URL=http://saas-platform.test locally), so the
 *    REAL canonical Universal Theme fixture never once localized. Fixed
 *    with an environment-aware origin policy, not by weakening security
 *    globally:
 *      - production (app()->environment('production')): the configured
 *        origin's scheme MUST be https, and the media URL's
 *        scheme+host+port must match it exactly. A misconfigured
 *        http:// PLATFORM_URL in production yields NO trusted origin at
 *        all (fail closed) rather than silently trusting http.
 *      - non-production: the media URL's scheme+host+port must match the
 *        CONFIGURED origin's scheme+host+port exactly, whatever that
 *        scheme is (http is allowed ONLY because and to the exact extent
 *        the operator's own PLATFORM_URL says so for this environment —
 *        an arbitrary http:// or https:// URL to any OTHER host is still
 *        always rejected).
 *  - No userinfo in the URL; redirects are never followed (a redirect off
 *    the trusted origin must fail closed, not be silently trusted).
 *  - Bounded timeout, bounded byte size, and real MIME/dimension validation
 *    on the downloaded bytes (CustomerMediaService::importFromRemoteBytes())
 *    — a declared Content-Type header is never trusted alone.
 *  - Deduplicated per site by source URL, so re-installing/upgrading the
 *    same theme never creates duplicate media rows or re-fetches unchanged
 *    images.
 *  - Atomic, loud failure (TASK 3/3 Phase 6.5D, BLD-31): any URL that
 *    matched the trusted origin is, by definition, required Platform-owned
 *    Theme media — there is no legitimate "customer's own external
 *    content" case once a URL has already passed the origin check. A
 *    fetch/import failure for such a URL throws
 *    ThemeMediaLocalizationException, and the whole localize() call rolls
 *    back every CustomerMedia row AND every physical file it wrote during
 *    THIS run (already-existing, previously-localized media from a prior
 *    successful run is untouched) before re-throwing — an install can
 *    never silently claim to be fully self-contained while actually still
 *    depending on the Platform. URLs that never matched the trusted origin
 *    at all (a customer's own genuinely external image) are still left
 *    completely untouched, exactly as before.
 */
class ThemeMediaLocalizationService
{
    private const TIMEOUT_SECONDS = 8;
    private const ALLOWED_CONTENT_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @var array<string, string|array{media_id: string}> per-run memoization — one theme install rarely repeats a URL, but a shared hero/logo across many blocks can. */
    private array $resolved = [];

    /** @var array<int, array{0: string, 1: string}> [disk, storage_key] pairs this run itself newly wrote — rolled back on failure, never touches media from a prior successful run. */
    private array $newlyWrittenFiles = [];

    public function __construct(private readonly CustomerMediaService $media)
    {
    }

    /**
     * @param array{header_blocks?: array, footer_blocks?: array, header_variants?: array, footer_variants?: array, pages: array} $themeData
     * @throws ThemeMediaLocalizationException if any URL that matched the
     *   trusted Platform origin could not be localized — see the class
     *   docblock's "atomic, loud failure" policy. Every CustomerMedia row
     *   and physical file this call itself created is rolled back before
     *   the exception propagates; media localized by a prior successful
     *   run is never touched.
     */
    public function localize(array $themeData): array
    {
        $origin = $this->trustedOrigin();
        if ($origin === null) {
            return $themeData; // no trusted Platform origin configured/resolvable — feature stays inert, install is never blocked by it
        }

        $this->newlyWrittenFiles = [];

        try {
            return DB::transaction(function () use ($themeData, $origin) {
                foreach (['header_blocks', 'footer_blocks'] as $key) {
                    if (isset($themeData[$key]) && is_array($themeData[$key])) {
                        $themeData[$key] = $this->localizeBlockList($themeData[$key], $origin);
                    }
                }

                foreach (['header_variants', 'footer_variants'] as $key) {
                    if (isset($themeData[$key]) && is_array($themeData[$key])) {
                        $themeData[$key] = array_map(function ($variant) use ($origin) {
                            if (is_array($variant) && isset($variant['blocks']) && is_array($variant['blocks'])) {
                                $variant['blocks'] = $this->localizeBlockList($variant['blocks'], $origin);
                            }
                            return $variant;
                        }, $themeData[$key]);
                    }
                }

                if (isset($themeData['pages']) && is_array($themeData['pages'])) {
                    $themeData['pages'] = array_map(function ($page) use ($origin) {
                        if (is_array($page) && isset($page['blocks']) && is_array($page['blocks'])) {
                            $page['blocks'] = $this->localizeBlockList($page['blocks'], $origin);
                        }
                        return $page;
                    }, $themeData['pages']);
                }

                return $themeData;
            });
        } catch (Throwable $e) {
            // DB::transaction() already rolled back every CustomerMedia row
            // this run created; the transaction cannot roll back the
            // physical files importFromRemoteBytes() already wrote before
            // the failure, so those are explicitly cleaned up here —
            // MISSING_PHYSICAL_FILES/ORPHAN_CUSTOMER_MEDIA_ON_FAILURE must
            // both be zero, not just the DB half.
            foreach ($this->newlyWrittenFiles as [$disk, $storageKey]) {
                try {
                    Storage::disk($disk)->delete($storageKey);
                } catch (Throwable) {
                    // best-effort cleanup; the real failure is re-thrown below regardless
                }
            }
            $this->newlyWrittenFiles = [];

            throw $e instanceof ThemeMediaLocalizationException ? $e : new ThemeMediaLocalizationException(
                'Theme media localization failed: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /** @param array{scheme: string, host: string, port: int} $origin */
    private function localizeBlockList(array $blocks, array $origin): array
    {
        foreach ($blocks as $index => $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? null;
            $props = $block['props'] ?? [];
            if (is_string($type) && is_array($props)) {
                $block['props'] = $this->localizeBlockProps($type, $props, $origin);
            }

            // Recurse into real nested block trees (sections/columns/tabs/
            // accordion panels) — mirrors the same traversal
            // RenderIntegrityCheck already uses, so container blocks whose
            // children carry media (a hero inside a section, a gallery
            // inside a tab panel) are localized too, not just top-level
            // blocks.
            if (isset($block['children']) && is_array($block['children'])) {
                $block['children'] = $this->localizeBlockList($block['children'], $origin);
            }
            if (isset($block['blocks']) && is_array($block['blocks'])) {
                $block['blocks'] = $this->localizeBlockList($block['blocks'], $origin);
            }
            if (isset($block['props']['tabs']) && is_array($block['props']['tabs'])) {
                $block['props']['tabs'] = array_map(function ($tab) use ($origin) {
                    if (is_array($tab) && isset($tab['blocks']) && is_array($tab['blocks'])) {
                        $tab['blocks'] = $this->localizeBlockList($tab['blocks'], $origin);
                    }
                    return $tab;
                }, $block['props']['tabs']);
            }
            if ($type === 'accordion' && isset($block['props']['items']) && is_array($block['props']['items'])) {
                $block['props']['items'] = array_map(function ($item) use ($origin) {
                    if (is_array($item) && isset($item['blocks']) && is_array($item['blocks'])) {
                        $item['blocks'] = $this->localizeBlockList($item['blocks'], $origin);
                    }
                    return $item;
                }, $block['props']['items']);
            }

            $blocks[$index] = $block;
        }

        return $blocks;
    }

    /** @param array{scheme: string, host: string, port: int} $origin */
    private function localizeBlockProps(string $type, array $props, array $origin): array
    {
        foreach (CustomerMediaResolver::MEDIA_PROPS[$type] ?? [] as $propKey) {
            if (isset($props[$propKey]) && is_string($props[$propKey]) && $props[$propKey] !== '') {
                $props[$propKey] = $this->localizeUrl($props[$propKey], $origin);
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
                        $items[$i][$repeater['field']] = $this->localizeUrl($item[$repeater['field']], $origin);
                    }
                }
                $props[$repeater['prop']] = $repeater['storage'] === 'json' ? json_encode($items, JSON_UNESCAPED_UNICODE) : $items;
            }
        }

        return $props;
    }

    /**
     * @param array{scheme: string, host: string, port: int} $origin
     * @return string|array{media_id: string}
     */
    private function localizeUrl(string $url, array $origin): string|array
    {
        if (array_key_exists($url, $this->resolved)) {
            return $this->resolved[$url];
        }

        return $this->resolved[$url] = $this->attemptLocalize($url, $origin) ?? $url;
    }

    /**
     * @param array{scheme: string, host: string, port: int} $origin
     * @return array{media_id: string}|null null ONLY when $url never
     *   matched the trusted origin at all (genuinely not our concern —
     *   left untouched). Once a URL matches, every further failure throws
     *   ThemeMediaLocalizationException instead of returning null — see
     *   the class docblock's atomic-failure policy.
     * @throws ThemeMediaLocalizationException
     */
    private function attemptLocalize(string $url, array $origin): ?array
    {
        $parts = parse_url($url);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if (!$this->originMatches($parts, $origin)) {
            return null; // not the trusted Platform origin — a customer's own external image, left completely untouched
        }

        // Past this point, $url matched the operator-configured trusted
        // origin exactly — it is DEFINITELY required Platform-owned Theme
        // media, never a customer's own external content. Every failure
        // from here throws.

        if ($existing = $this->media->findBySourceUrl($url)) {
            return ['media_id' => $existing->uuid];
        }

        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($url);
        } catch (Throwable $e) {
            throw new ThemeMediaLocalizationException("Failed to fetch trusted Platform media \"{$url}\": {$e->getMessage()}", previous: $e);
        }

        if (!$response->successful()) {
            throw new ThemeMediaLocalizationException("Trusted Platform media \"{$url}\" returned HTTP {$response->status()}.");
        }

        $contentType = strtolower(trim(explode(';', $response->header('Content-Type') ?? '')[0]));
        if (!in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
            throw new ThemeMediaLocalizationException("Trusted Platform media \"{$url}\" has an unexpected content type \"{$contentType}\".");
        }

        try {
            $media = $this->media->importFromRemoteBytes(
                $response->body(),
                basename((string) ($parts['path'] ?? 'theme-media')),
                $contentType,
                ['source_url' => $url]
            );
        } catch (Throwable $e) {
            throw new ThemeMediaLocalizationException("Failed to store trusted Platform media \"{$url}\": {$e->getMessage()}", previous: $e);
        }

        $this->newlyWrittenFiles[] = [$media->disk, $media->storage_key];

        return ['media_id' => $media->uuid];
    }

    /**
     * The ONE canonical, operator-controlled trusted Platform origin — a
     * Theme payload can never declare or influence this. Derived from
     * config('site.platform_url') (PLATFORM_URL), the SAME canonical
     * Platform-origin config PlatformApiService/licensing/marketplace
     * already use — no second, competing source of truth.
     *
     * @return array{scheme: string, host: string, port: int}|null null
     *   when no Platform origin is configured, or (fail-closed) when
     *   running in production with a non-https configured origin — a
     *   misconfiguration must never silently become a trusted fetch
     *   target.
     */
    private function trustedOrigin(): ?array
    {
        $platformUrl = (string) config('site.platform_url', '');
        if ($platformUrl === '') {
            return null;
        }

        $parts = parse_url($platformUrl);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (app()->environment('production') && $scheme !== 'https') {
            Log::critical('theme_media_localization.insecure_platform_url_in_production', ['platform_url_host' => $host]);
            return null;
        }

        return ['scheme' => $scheme, 'host' => $host, 'port' => $port];
    }

    /**
     * @param array<string, mixed> $urlParts parse_url() output for the candidate media URL
     * @param array{scheme: string, host: string, port: int} $origin
     */
    private function originMatches(array $urlParts, array $origin): bool
    {
        $scheme = strtolower((string) ($urlParts['scheme'] ?? ''));
        $host = strtolower((string) ($urlParts['host'] ?? ''));
        $port = $urlParts['port'] ?? match ($scheme) {
            'https' => 443,
            'http' => 80,
            default => null,
        };

        if ($scheme === '' || $host === '' || $port === null) {
            return false;
        }

        // Defense in depth: production never trusts a non-https fetch even
        // if trustedOrigin() itself were ever somehow misconfigured — this
        // check is independent of (and redundant with) the fail-closed
        // check already inside trustedOrigin().
        if (app()->environment('production') && $scheme !== 'https') {
            return false;
        }

        return $scheme === $origin['scheme'] && $host === $origin['host'] && $port === $origin['port'];
    }
}
