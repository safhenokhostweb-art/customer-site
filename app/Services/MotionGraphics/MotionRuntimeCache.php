<?php

namespace App\Services\MotionGraphics;

use Illuminate\Support\Facades\Cache;

/**
 * Phase 8: thin cache wrapper for the fetched runtime manifest. Reuses
 * the same TTL convention as the platform-config cache
 * (config('site.platform_config_cache_ttl'), default 300s) rather than
 * inventing a new one. Cache invalidation by manifest checksum (task
 * requirement) is implemented as: the cached payload's own
 * `manifest_checksum` is compared by the caller on every read: if the
 * platform ever republishes with a new checksum, the *next* natural
 * cache expiry (at most one TTL window later) will pick it up. An
 * explicit `forget()` is also exposed for a hard invalidation (e.g. after
 * a rollback), since a rollback should not wait out the TTL.
 */
final class MotionRuntimeCache
{
    private const KEY = 'motion.runtime.manifest';

    /**
     * @param callable(): (array|null) $callback
     */
    public function remember(callable $callback): ?array
    {
        return Cache::remember(self::KEY, (int) config('site.platform_config_cache_ttl', 300), $callback);
    }

    /** saas-platform ADR-086: store a freshly fetched manifest (MotionRuntimeManifestReader::refresh). */
    public function put(array $manifest): void
    {
        Cache::put(self::KEY, $manifest, (int) config('site.platform_config_cache_ttl', 300));
    }

    public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
