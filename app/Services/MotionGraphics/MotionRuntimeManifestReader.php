<?php

namespace App\Services\MotionGraphics;

use App\Models\License;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Phase 8 (docs/motion-customer-runtime-phase8/CUSTOMER_RUNTIME_ARCHITECTURE.md):
 * fetches, validates, and caches the site's Motion Graphics runtime
 * manifest from the platform's `tenant.api`-authenticated endpoint. This
 * is the customer-site half of the canonical integration boundary the
 * Phase 8 audit determined — same auth transport
 * (`Authorization: Bearer <license_key>` + `domain`) the pre-existing
 * (dormant) MotionRuntimeClient already used, extended to the
 * site-scoped, richer manifest shape.
 *
 * SaaS is authoritative: license validity, feature-flag state, and
 * entitlement are all re-checked platform-side on every fetch (never
 * cached as a local override) — this class caches only the *result*
 * (manifest payload), not a security decision, and only for
 * config('site.platform_config_cache_ttl') seconds.
 *
 * Fails closed to `null` for every invalid/unavailable/malformed
 * condition — never throws, so a caller (the Blade component, a health
 * check) can always treat `null` as "render nothing" without a
 * try/catch. Disabled/misconfigured sites therefore render exactly as
 * before this feature existed (task requirement: "preserve existing
 * site behavior when disabled").
 */
final class MotionRuntimeManifestReader
{
    private const SUPPORTED_SCHEMA_VERSIONS = ['1.0'];
    private const ASSET_ID_PATTERN = '/^[a-z0-9-]+$/';
    private const CHECKSUM_PATTERN = '/^[a-f0-9]{64}$/';
    private const LOCK_REASONS = ['premium_license_required'];
    /** saas-platform ADR-086: `v2`, `1.2.0`, `1.2.0-rc.1`, `2.1.0+build.7`. */
    private const VERSION_LABEL_PATTERN = '/^v?[0-9]{1,4}(\.[0-9]{1,4}){0,2}([-+][0-9A-Za-z.]{1,16})?$/';
    private const BUILD_ID_PATTERN = '/^[a-f0-9]{12}$/';
    private const KNOWN_ASSET_TYPES =['lottie', 'svg', 'html', 'canvas', 'web-component', 'motion-json'];

    public function __construct(
        private readonly MotionRuntimeCache $cache,
    ) {}

    public function read(): ?array
    {
        return $this->cache->remember(fn (): ?array => $this->fetchAndValidate());
    }

    /**
     * Bypasses the cache — used by MotionRuntimeHealthCheck, which needs
     * two independent reads to prove checksum stability.
     */
    public function readFresh(): ?array
    {
        return $this->fetchAndValidate();
    }

    /**
     * saas-platform ADR-086: the platform's current answer, written back to
     * the cache so the public runtime follows at once. Used by the canvas
     * editor's motion picker, so a motion an admin switched off, a grant
     * that was revoked or a private motion that was just granted shows (or
     * disappears) the moment the picker opens, not one cache TTL later.
     *
     * Only an actual platform answer replaces the cache. When the platform
     * cannot be reached, or answers with something invalid, the last good
     * cached copy is kept and returned, so a network blip never empties the
     * picker or the live site.
     */
    public function refresh(): ?array
    {
        [$answered, $manifest] = $this->fetch();
        if (! $answered) {
            return $this->read();
        }

        $manifest === null ? $this->cache->forget() : $this->cache->put($manifest);

        return $manifest;
    }

    private function fetchAndValidate(): ?array
    {
        return $this->fetch()[1];
    }

    /**
     * @return array{0: bool, 1: array<string, mixed>|null} whether the
     *   platform gave a definitive answer, and the validated manifest (null
     *   when the site has none)
     */
    private function fetch(): array
    {
        $license = License::current();
        $platform = rtrim((string) config('site.platform_url'), '/');
        if (! $license?->license_key || $platform === '') {
            return [true, null];
        }

        try {
            $response = Http::withToken($license->license_key)->acceptJson()->timeout(8)
                ->get($platform.'/api/motion-runtime/manifest', ['domain' => $license->domain ?: request()->getHost()]);
        } catch (\Throwable $e) {
            Log::warning('motion_runtime.fetch_failed', ['message' => $e->getMessage()]);
            return [false, null];
        }

        if (! $response->successful()) {
            return [false, null];
        }

        $manifest = $response->json();
        if (! is_array($manifest)) {
            return [false, null];
        }
        if (($manifest['available'] ?? false) !== true) {
            return [true, null];
        }

        if (! $this->isStructurallyValid($manifest)) {
            Log::warning('motion_runtime.invalid_manifest', ['site_reference' => $manifest['site_reference'] ?? null]);
            return [false, null];
        }

        $manifest['assets'] = array_map(
            fn (array $asset): array => [
                ...$asset,
                'full_reference' => $this->resolveReference($platform, $asset['optimized_reference'] ?? null),
                // ADR-079: only well-formed runtime-parameter definitions
                // survive; a malformed one is dropped, never trusted.
                'parameters' => MotionRuntimeParameters::normalizeDefinitions($asset['parameters'] ?? []),
                // saas-platform ADR-086: release identifiers and the category
                // display name. Display-only; a malformed value is dropped,
                // never trusted, and never rejects the manifest (a platform
                // that predates them sends none).
                'version_label' => $this->matching($asset['version_label'] ?? null, self::VERSION_LABEL_PATTERN, 32),
                'build_id' => $this->matching($asset['build_id'] ?? null, self::BUILD_ID_PATTERN, 12),
                'category_label' => $this->plainLabel($asset['category_label'] ?? null),
            ],
            $manifest['assets']
        );
        $manifest['locked_assets'] = $this->lockedAssets($manifest['locked_assets'] ?? []);

        return [true, $manifest];
    }

    private function matching(mixed $value, string $pattern, int $maxLength): ?string
    {
        return is_string($value) && strlen($value) <= $maxLength && preg_match($pattern, $value) === 1 ? $value : null;
    }

    /** A short single-line label with no markup characters, or null. */
    private function plainLabel(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value !== '' && mb_strlen($value) <= 80 && preg_match('/[<>\x00-\x1F\x7F]/u', $value) !== 1 ? $value : null;
    }

    private function isStructurallyValid(array $manifest): bool
    {
        if (! in_array($manifest['schema_version'] ?? null, self::SUPPORTED_SCHEMA_VERSIONS, true)) {
            return false;
        }
        if (! is_string($manifest['site_reference'] ?? null) || $manifest['site_reference'] === '') {
            return false;
        }
        if (! is_string($manifest['manifest_checksum'] ?? null) || $manifest['manifest_checksum'] === '') {
            return false;
        }
        if (! is_array($manifest['assets'] ?? null)) {
            return false;
        }

        foreach ($manifest['assets'] as $asset) {
            if (! is_array($asset)) {
                return false;
            }
            if (! is_string($asset['asset_id'] ?? null) || ! preg_match(self::ASSET_ID_PATTERN, $asset['asset_id'])) {
                return false;
            }
            if (! is_int($asset['published_version'] ?? null)) {
                return false;
            }
            if (! is_string($asset['checksum'] ?? null) || ! preg_match(self::CHECKSUM_PATTERN, $asset['checksum'])) {
                return false;
            }
            if (! in_array($asset['asset_type'] ?? null, self::KNOWN_ASSET_TYPES, true)) {
                return false;
            }
            $ref = $asset['optimized_reference'] ?? null;
            if ($ref !== null && (! is_string($ref) || ! $this->isSafeRelativeReference($ref))) {
                return false;
            }
            // Forbidden-field defense in depth: even though the platform
            // side already guarantees these are absent, never trust a
            // single layer — reject outright if any ever appear.
            foreach (['source_definition', 'ai_metadata', 'editable_schema', 'ownership', 'credentials', 'storage_path', 'prompt'] as $forbidden) {
                if (array_key_exists($forbidden, $asset)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Root-relative only (`/...`) — never `http://`, `https://`, or
     * protocol-relative `//`, so a compromised/tampered manifest can
     * never point the browser at an arbitrary remote origin. This
     * mirrors the prefix-validation the original (dormant)
     * MotionRuntimeClient already did.
     */
    /**
     * saas-platform CustomerRuntimeManifest `locked_assets`: package items the
     * platform withheld (e.g. premium motion without a premium license). Only
     * a well-formed id and a known reason survive; anything else is dropped,
     * never trusted, and never makes the manifest itself invalid (a platform
     * that predates the key sends none). Display-only: a locked entry has no
     * reference, so nothing here can ever render.
     *
     * @return list<array{asset_id: string, reason: string}>
     */
    private function lockedAssets(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $locked = [];
        foreach (array_slice($raw, 0, 100) as $entry) {
            $id = is_array($entry) ? ($entry['asset_id'] ?? null) : null;
            $reason = is_array($entry) ? ($entry['reason'] ?? null) : null;
            if (is_string($id) && preg_match(self::ASSET_ID_PATTERN, $id) && in_array($reason, self::LOCK_REASONS, true)) {
                $locked[] = ['asset_id' => $id, 'reason' => $reason];
            }
        }

        return $locked;
    }

    private function isSafeRelativeReference(string $ref): bool
    {
        return str_starts_with($ref, '/') && ! str_starts_with($ref, '//');
    }

    private function resolveReference(string $platform, ?string $ref): ?string
    {
        return $ref === null ? null : $platform.$ref;
    }
}
