<?php

namespace App\Services\ThemeCanvas;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Validates and localizes the versioned, theme-global asset manifest.
 *
 * Input contract (version 1):
 *   {"version":1,"assets":[
 *     {"kind":"css|js|file","source":"https://configured-platform/...",
 *      "path":"relative/path.ext","sha256":"64 lowercase hex chars"}
 *   ]}
 *
 * Array order is load order. Sources must match config('site.platform_url')
 * exactly; redirects and third-party URLs are never followed. The stored
 * manifest contains only immutable customer-local references, never source
 * URLs. A manifest hash namespaces every file, so a later revision cannot
 * overwrite bytes referenced by an earlier revision.
 */
class ThemeAssetLocalizationService
{
    private const VERSION = 1;

    private const MAX_ASSETS = 64;

    private const MAX_ASSET_BYTES = 8_388_608;

    private const MAX_TOTAL_BYTES = 33_554_432;

    private const TIMEOUT_SECONDS = 12;

    private const CONTENT_TYPES = [
        'css' => ['text/css'],
        'js' => ['application/javascript', 'text/javascript', 'application/x-javascript'],
        'file' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'font/woff', 'font/woff2', 'application/font-woff',
            'font/ttf', 'application/x-font-ttf', 'application/font-sfnt',
            'application/octet-stream',
        ],
    ];

    /**
     * @throws ThemeAssetLocalizationException
     */
    public function localize(array $manifest, array $context = []): array
    {
        if ($manifest === []) {
            return [];
        }
        if (($manifest['localized'] ?? null) === true) {
            return $this->validateLocalizedManifest($manifest);
        }

        $assets = $this->validateManifestShape($manifest);
        $origin = $this->trustedOrigin();
        if ($origin === null) {
            throw new ThemeAssetLocalizationException('Theme assets require a valid configured Platform origin.');
        }

        $normalized = [];
        $downloaded = [];
        $totalBytes = 0;

        foreach ($assets as $index => $descriptor) {
            $item = $this->validateDescriptor($descriptor, $index, $origin);

            try {
                $response = Http::withOptions(['allow_redirects' => false])
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->get($item['source']);
            } catch (Throwable $e) {
                throw new ThemeAssetLocalizationException("Failed to fetch theme asset #{$index}: {$e->getMessage()}", previous: $e);
            }

            if (! $response->successful()) {
                throw new ThemeAssetLocalizationException("Theme asset #{$index} returned HTTP {$response->status()}.");
            }

            $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            if (! in_array($contentType, self::CONTENT_TYPES[$item['kind']], true)) {
                throw new ThemeAssetLocalizationException("Theme asset #{$index} has disallowed content type \"{$contentType}\".");
            }

            $body = $response->body();
            $bytes = strlen($body);
            $totalBytes += $bytes;
            if ($bytes === 0 || $bytes > self::MAX_ASSET_BYTES || $totalBytes > self::MAX_TOTAL_BYTES) {
                throw new ThemeAssetLocalizationException("Theme asset #{$index} violates byte-size limits.");
            }

            $actualHash = hash('sha256', $body);
            if (! hash_equals($item['sha256'], $actualHash)) {
                throw new ThemeAssetLocalizationException("Theme asset #{$index} failed SHA-256 verification.");
            }

            $normalized[] = $item;
            $downloaded[] = $body;
        }

        $manifestHash = hash('sha256', json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $theme = $this->safeNamespace((string) ($context['theme_identifier'] ?? 'theme'));
        $release = $this->safeNamespace((string) ($context['source_release_id'] ?? 'unversioned'));
        $prefix = "theme-assets/{$theme}/{$release}/{$manifestHash}";
        $disk = Storage::disk('public');
        $newKeys = [];
        $localized = [];

        try {
            foreach ($normalized as $index => $item) {
                $key = "{$prefix}/{$item['path']}";
                if ($disk->exists($key)) {
                    $existingHash = hash('sha256', (string) $disk->get($key));
                    if (! hash_equals($item['sha256'], $existingHash)) {
                        throw new ThemeAssetLocalizationException("Immutable theme asset collision at \"{$item['path']}\".");
                    }
                } else {
                    if (! $disk->put($key, $downloaded[$index], ['visibility' => 'public'])) {
                        throw new ThemeAssetLocalizationException("Failed to store theme asset #{$index}.");
                    }
                    $newKeys[] = $key;
                }

                $localized[] = [
                    'kind' => $item['kind'],
                    'path' => $item['path'],
                    'sha256' => $item['sha256'],
                    'storage_key' => $key,
                ];
            }
        } catch (Throwable $e) {
            foreach ($newKeys as $key) {
                $disk->delete($key);
            }
            throw $e instanceof ThemeAssetLocalizationException ? $e : new ThemeAssetLocalizationException(
                'Theme asset storage failed: '.$e->getMessage(),
                previous: $e,
            );
        }

        return [
            'version' => self::VERSION,
            'localized' => true,
            'manifest_sha256' => $manifestHash,
            'assets' => $localized,
        ];
    }

    /** Returns no assets unless the entire stored manifest is authentic customer-local output. */
    public function runtimeAssets(mixed $manifest): array
    {
        if (! is_array($manifest) || ($manifest['version'] ?? null) !== self::VERSION
            || ($manifest['localized'] ?? null) !== true || ! is_array($manifest['assets'] ?? null)) {
            return ['css' => [], 'js' => []];
        }

        $result = ['css' => [], 'js' => [], 'files' => []];
        $disk = Storage::disk('public');
        foreach ($manifest['assets'] as $asset) {
            if (! is_array($asset) || ! in_array($asset['kind'] ?? null, ['css', 'js', 'file'], true)
                || ! $this->isSafePath($asset['path'] ?? null)
                || ! is_string($asset['sha256'] ?? null) || ! preg_match('/\A[a-f0-9]{64}\z/', $asset['sha256'])
                || ! is_string($asset['storage_key'] ?? null)
                || ! str_starts_with($asset['storage_key'], 'theme-assets/')
                || ! $disk->exists($asset['storage_key'])) {
                return ['css' => [], 'js' => [], 'files' => []];
            }
            if ($asset['kind'] !== 'file') {
                $result[$asset['kind']][] = $disk->url($asset['storage_key']);
            } else {
                // Phase 3: theme markup may reference declared file assets by
                // their manifest-relative path (e.g. src="images/logo.png" or
                // an inline background url('images/x.png')). Expose the exact,
                // integrity-verified path → storage-URL map so the runtime can
                // resolve ONLY declared paths — anything unreferenced by the
                // manifest never resolves, so this cannot become a general
                // path-rewriting channel.
                $result['files'][$asset['path']] = $disk->url($asset['storage_key']);
            }
        }

        return $result;
    }

    /** Validate an already-localized manifest before carrying it into another immutable revision. */
    private function validateLocalizedManifest(array $manifest): array
    {
        if (($manifest['version'] ?? null) !== self::VERSION
            || ! is_string($manifest['manifest_sha256'] ?? null)
            || ! preg_match('/\A[a-f0-9]{64}\z/', $manifest['manifest_sha256'])
            || ! is_array($manifest['assets'] ?? null) || ! array_is_list($manifest['assets'])
            || count($manifest['assets']) > self::MAX_ASSETS) {
            throw new ThemeAssetLocalizationException('Malformed localized theme_assets manifest.');
        }

        $disk = Storage::disk('public');
        foreach ($manifest['assets'] as $index => $asset) {
            if (! is_array($asset) || array_diff(array_keys($asset), ['kind', 'path', 'sha256', 'storage_key']) !== []
                || ! in_array($asset['kind'] ?? null, ['css', 'js', 'file'], true)
                || ! $this->isSafePath($asset['path'] ?? null)
                || ! is_string($asset['sha256'] ?? null) || ! preg_match('/\A[a-f0-9]{64}\z/', $asset['sha256'])
                || ! is_string($asset['storage_key'] ?? null) || ! str_starts_with($asset['storage_key'], 'theme-assets/')
                || $this->containsTraversal($asset['storage_key']) || ! $disk->exists($asset['storage_key'])
                || ! hash_equals($asset['sha256'], hash('sha256', (string) $disk->get($asset['storage_key'])))) {
                throw new ThemeAssetLocalizationException("Localized theme asset #{$index} is missing, invalid, or has changed.");
            }
        }

        return $manifest;
    }

    private function validateManifestShape(array $manifest): array
    {
        if (($manifest['version'] ?? null) !== self::VERSION || ! is_array($manifest['assets'] ?? null)
            || ! array_is_list($manifest['assets']) || count($manifest['assets']) > self::MAX_ASSETS) {
            throw new ThemeAssetLocalizationException('Malformed theme_assets manifest.');
        }

        return $manifest['assets'];
    }

    private function validateDescriptor(mixed $descriptor, int $index, array $origin): array
    {
        if (! is_array($descriptor) || array_diff(array_keys($descriptor), ['kind', 'source', 'path', 'sha256']) !== []) {
            throw new ThemeAssetLocalizationException("Malformed theme asset descriptor #{$index}.");
        }
        $kind = $descriptor['kind'] ?? null;
        $source = $descriptor['source'] ?? null;
        $path = $descriptor['path'] ?? null;
        $sha256 = $descriptor['sha256'] ?? null;
        if (! in_array($kind, ['css', 'js', 'file'], true) || ! is_string($source)
            || ! $this->isSafePath($path) || ! is_string($sha256) || ! preg_match('/\A[a-f0-9]{64}\z/', $sha256)) {
            throw new ThemeAssetLocalizationException("Invalid theme asset descriptor #{$index}.");
        }
        if (($kind === 'css' && ! str_ends_with(strtolower($path), '.css'))
            || ($kind === 'js' && ! str_ends_with(strtolower($path), '.js'))) {
            throw new ThemeAssetLocalizationException("Theme asset #{$index} kind does not match its path.");
        }

        $parts = parse_url($source);
        if (! is_array($parts) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || ! $this->originMatches($parts, $origin) || $this->containsTraversal((string) ($parts['path'] ?? ''))) {
            throw new ThemeAssetLocalizationException("Theme asset #{$index} has an unsafe source.");
        }

        return compact('kind', 'source', 'path', 'sha256');
    }

    private function trustedOrigin(): ?array
    {
        $parts = parse_url((string) config('site.platform_url', ''));
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (! in_array($scheme, ['http', 'https'], true) || (app()->environment('production') && $scheme !== 'https')) {
            return null;
        }

        return ['scheme' => $scheme, 'host' => strtolower($parts['host']), 'port' => $parts['port'] ?? ($scheme === 'https' ? 443 : 80)];
    }

    private function originMatches(array $parts, array $origin): bool
    {
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : ($scheme === 'http' ? 80 : null));

        return $scheme === $origin['scheme'] && strtolower((string) ($parts['host'] ?? '')) === $origin['host'] && $port === $origin['port'];
    }

    private function isSafePath(mixed $path): bool
    {
        return is_string($path) && $path !== '' && strlen($path) <= 240
            && ! str_starts_with($path, '/') && ! str_contains($path, '\\')
            && ! $this->containsTraversal($path)
            && preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._\/-]*\z/', $path) === 1;
    }

    private function containsTraversal(string $path): bool
    {
        $decoded = rawurldecode($path);

        return str_contains($decoded, "\0") || str_contains($decoded, '\\')
            || in_array('..', explode('/', $decoded), true);
    }

    private function safeNamespace(string $value): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', trim($value)) ?: 'theme';

        return trim($safe, '.-') ?: 'theme';
    }
}
