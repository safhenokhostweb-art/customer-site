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
 * data. It walks the SAME media-field inventory the render-time resolver
 * uses (SchemaFieldMap: every `image` control in the registry schema, at
 * any repeater depth) and rewrites any URL hosted on the trusted
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
    // Commercial Productization fase-4: the `video` block's `url` prop is
    // an `image` control (SchemaFieldMap) and so real, intentional
    // media needing localization (this theme's real local demo video) —
    // see CustomerMediaService::importVideoFromRemoteBytes()'s docblock
    // for the full gap this closes.
    private const ALLOWED_VIDEO_CONTENT_TYPES = ['video/webm', 'video/mp4'];

    /** @var array<string, string|array{media_id: string}> per-run memoization — one theme install rarely repeats a URL, but a shared hero/logo across many blocks can. */
    private array $resolved = [];

    /** @var array<int, array{0: string, 1: string}> [disk, storage_key] pairs this run itself newly wrote — rolled back on failure, never touches media from a prior successful run. */
    private array $newlyWrittenFiles = [];

    /** @var array{theme_identifier?: string, release_namespace?: string} */
    private array $importScope = [];

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
    public function localize(array $themeData, array $context = []): array
    {
        $origin = $this->trustedOrigin();

        // A scoped service instance can live for more than one install in a
        // queue/Octane process. Per-run caches must never carry a media UUID
        // from one site/theme/release into the next invocation.
        $this->resolved = [];
        $this->newlyWrittenFiles = [];
        $themeIdentifier = trim((string) ($context['theme_identifier'] ?? ''));
        $this->importScope = $themeIdentifier !== '' ? [
            'theme_identifier' => $themeIdentifier,
            'release_namespace' => isset($context['source_release_id']) && $context['source_release_id'] !== null
                ? (string) $context['source_release_id']
                : 'unversioned',
        ] : [];

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

    /** @param array{scheme: string, host: string, port: int}|null $origin */
    private function localizeBlockList(array $blocks, ?array $origin): array
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
            // Real, proven gap (khab-shimana-tablet certification,
            // 2026-09-21): BlockRenderer::renderBlock() resolves a block's
            // children via `$block['children'] ?? $block['blocks'] ??
            // ($props['blocks'] ?? [])` — a THIRD shape, children nested
            // under props.blocks (how a Theme author's own `grid`/
            // `flex_container`/`section` composition is naturally built:
            // `['type' => 'flex_container', 'props' => ['blocks' => [...]]]`,
            // no top-level `blocks`/`children` key at all). This method only
            // checked the first two shapes above, so any master-theme image
            // nested inside such a container (e.g. a hand-composed product
            // card: flex_container > image) silently never reached
            // localization and shipped as a permanent saas-platform hotlink
            // — confirmed via a real install (grep of rendered HTML showed
            // the top-level hero/gallery images correctly localized to
            // customer-media while every flex_container-nested product photo
            // stayed on saas-platform.test). Checked separately from
            // `$block['blocks']` above (not `??`) because a block can only
            // ever populate one of the three shapes, so this is never
            // redundant work, only the missing case.
            if (isset($block['props']['blocks']) && is_array($block['props']['blocks'])) {
                $block['props']['blocks'] = $this->localizeBlockList($block['props']['blocks'], $origin);
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

    /** @param array{scheme: string, host: string, port: int}|null $origin */
    private function localizeBlockProps(string $type, array $props, ?array $origin): array
    {
        return SchemaFieldMap::transform(
            $type,
            $props,
            CustomerMediaResolver::CONTROL_TYPE,
            fn (mixed $value) => is_string($value) && $value !== '' ? $this->localizeUrl($value, $origin) : $value,
        );
    }

    /**
     * @param array{scheme: string, host: string, port: int}|null $origin
     * @return string|array{media_id: string}
     */
    private function localizeUrl(string $url, ?array $origin): string|array
    {
        if (array_key_exists($url, $this->resolved)) {
            return $this->resolved[$url];
        }

        return $this->resolved[$url] = $this->attemptLocalize($url, $origin) ?? $url;
    }

    /**
     * @param array{scheme: string, host: string, port: int}|null $origin
     * @return array{media_id: string}|null null ONLY when $url never
     *   matched the trusted origin at all (genuinely not our concern —
     *   left untouched). Once a URL matches, every further failure throws
     *   ThemeMediaLocalizationException instead of returning null — see
     *   the class docblock's atomic-failure policy.
     * @throws ThemeMediaLocalizationException
     */
    private function attemptLocalize(string $url, ?array $origin): ?array
    {
        // 1. Check if $url is a local demo static file asset (e.g. /storage/solar-assets/...)
        if ($localAsset = $this->resolveLocalDemoBytes($url)) {
            return $this->localizeLocalDemoAsset($url, $localAsset['bytes'], $localAsset['filename']);
        }

        // 2. Remote Platform media URL check
        if ($origin === null) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if (!$this->originMatches($parts, $origin)) {
            $this->logOriginNearMissIfHostMatches($url, $parts, $origin);
            return null; // not the trusted Platform origin — a customer's own external image, left completely untouched
        }

        // Past this point, $url matched the operator-configured trusted
        // origin exactly — it is DEFINITELY required Platform-owned Theme
        // media, never a customer's own external content. Every failure
        // from here throws.

        if ($existing = $this->media->findBySourceUrl($url, $this->importScope)) {
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
        $isVideo = in_array($contentType, self::ALLOWED_VIDEO_CONTENT_TYPES, true);
        if (!$isVideo && !in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
            throw new ThemeMediaLocalizationException("Trusted Platform media \"{$url}\" has an unexpected content type \"{$contentType}\".");
        }

        try {
            $media = $isVideo
                ? $this->media->importVideoFromRemoteBytes(
                    $response->body(),
                    basename((string) ($parts['path'] ?? 'theme-media')),
                    $contentType,
                    array_merge(['source_url' => $url], $this->importScope)
                )
                : $this->media->importFromRemoteBytes(
                    $response->body(),
                    basename((string) ($parts['path'] ?? 'theme-media')),
                    $contentType,
                    array_merge(['source_url' => $url], $this->importScope)
                );
        } catch (Throwable $e) {
            throw new ThemeMediaLocalizationException("Failed to store trusted Platform media \"{$url}\": {$e->getMessage()}", previous: $e);
        }

        $this->newlyWrittenFiles[] = [$media->disk, $media->storage_key];

        return ['media_id' => $media->uuid];
    }

    /**
     * @return array{bytes: string, filename: string}|null
     */
    private function resolveLocalDemoBytes(string $url): ?array
    {
        if (preg_match('#^[a-z]+://#i', $url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path || !is_string($path)) {
            return null;
        }

        $relPath = ltrim(preg_replace('#^/?storage/#', '', $path), '/');
        $rawRelPath = ltrim($path, '/');

        // 1. Check Storage::disk('public') (supports fake storage in tests)
        try {
            if ($relPath !== '' && Storage::disk('public')->exists($relPath)) {
                $bytes = Storage::disk('public')->get($relPath);
                if ($bytes !== null && $bytes !== '') {
                    return ['bytes' => $bytes, 'filename' => basename($relPath)];
                }
            }
        } catch (Throwable) {
        }

        // 2. Check storage_path('app/public/' . $relPath)
        if ($relPath !== '') {
            $localDiskFile = storage_path('app/public/' . $relPath);
            if (is_file($localDiskFile) && is_readable($localDiskFile)) {
                $bytes = @file_get_contents($localDiskFile);
                if ($bytes !== false && $bytes !== '') {
                    return ['bytes' => $bytes, 'filename' => basename($localDiskFile)];
                }
            }
        }

        // 3. Check public_path
        foreach (array_unique([$rawRelPath, $relPath]) as $p) {
            if ($p !== '') {
                $pubFile = public_path($p);
                if (is_file($pubFile) && is_readable($pubFile)) {
                    $bytes = @file_get_contents($pubFile);
                    if ($bytes !== false && $bytes !== '') {
                        return ['bytes' => $bytes, 'filename' => basename($pubFile)];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array{media_id: string}
     * @throws ThemeMediaLocalizationException
     */
    private function localizeLocalDemoAsset(string $url, string $bytes, string $filename): array
    {
        if ($existing = $this->media->findBySourceUrl($url, $this->importScope)) {
            return ['media_id' => $existing->uuid];
        }

        $probe = @getimagesizefromstring($bytes);
        $mime = is_array($probe) ? ($probe['mime'] ?? '') : '';
        $isVideo = in_array($mime, self::ALLOWED_VIDEO_CONTENT_TYPES, true);

        if (!$isVideo && !in_array($mime, self::ALLOWED_CONTENT_TYPES, true)) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png'         => 'image/png',
                'webp'        => 'image/webp',
                'gif'         => 'image/gif',
                'webm'        => 'video/webm',
                'mp4'         => 'video/mp4',
                default       => $mime ?: 'application/octet-stream',
            };
            $isVideo = in_array($mime, self::ALLOWED_VIDEO_CONTENT_TYPES, true);
        }

        try {
            $media = $isVideo
                ? $this->media->importVideoFromRemoteBytes(
                    $bytes,
                    $filename,
                    $mime,
                    array_merge(['source_url' => $url], $this->importScope)
                )
                : $this->media->importFromRemoteBytes(
                    $bytes,
                    $filename,
                    $mime,
                    array_merge(['source_url' => $url], $this->importScope)
                );
        } catch (Throwable $e) {
            throw new ThemeMediaLocalizationException("Failed to store local demo media \"{$url}\": {$e->getMessage()}", previous: $e);
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

    /**
     * DIAGNOSABILITY FIX for MASTER_THEME_MEDIA_LOCALIZATION_GAP: before
     * this, ANY origin mismatch — a customer's own genuinely external
     * image (expected, must stay silent) or the exact same Platform host
     * reached via a slightly different scheme/port (a real
     * misconfiguration/drift bug) — was equally silent. That made the
     * drift case, which produces a permanently broken hotlink with a
     * legitimate Platform-owned asset, indistinguishable from ordinary,
     * correct behavior; nothing in logs ever pointed at it.
     *
     * A genuinely different host is still left completely silent here —
     * that is normal, expected, high-volume (every customer's own
     * external images/links) and logging it would be useless noise, and
     * would defeat the entire "external content is left untouched"
     * contract. Only the narrow, high-signal case — same host as the
     * configured trusted origin, but scheme and/or port differ — is
     * logged, specifically and by name, never folded into a generic
     * catch-all. This never changes trust: the URL is still left
     * untouched (fail closed); this only makes the decision visible.
     *
     * @param array<string, mixed> $urlParts parse_url() output for the candidate media URL
     * @param array{scheme: string, host: string, port: int} $origin
     */
    private function logOriginNearMissIfHostMatches(string $url, array $urlParts, array $origin): void
    {
        $host = strtolower((string) ($urlParts['host'] ?? ''));
        if ($host === '' || $host !== $origin['host']) {
            return; // genuinely different host — a customer's own external content, not a drift bug
        }

        Log::warning('theme_media_localization.origin_near_miss', [
            'url' => $url,
            'url_scheme' => $urlParts['scheme'] ?? null,
            'url_port' => $urlParts['port'] ?? (($urlParts['scheme'] ?? null) === 'https' ? 443 : (($urlParts['scheme'] ?? null) === 'http' ? 80 : null)),
            'configured_platform_url' => (string) config('site.platform_url', ''),
            'trusted_scheme' => $origin['scheme'],
            'trusted_host' => $origin['host'],
            'trusted_port' => $origin['port'],
            'note' => 'This URL\'s host matches the configured trusted Platform origin, but its scheme and/or port do not — it will NOT be localized and will remain a permanent external hotlink. This is almost always PLATFORM_URL drifting out of sync with how the Theme\'s own media URLs are actually generated (http vs https, a non-default port, etc.), not a genuine third-party image.',
        ]);
    }
}
