<?php

namespace App\Services;

use App\Http\Controllers\Front\SitemapController;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2.1 — the single, failure-tolerant entry point for sitemap cache
 * invalidation.
 *
 * Two reasons this exists instead of observers calling
 * SitemapController::clearCache() directly:
 *
 *  1. Content models carry no tenant column. Page/Post/Product/Form are
 *     installation-wide, while every sitemap cache entry is now host-scoped,
 *     so a content change can never know which host it belongs to — it has to
 *     flush all of them. That decision belongs in one place, not in each
 *     observer.
 *
 *  2. A cache-driver problem must never turn a content save into a 500.
 *     Sitemap freshness is a performance concern; a sitemap served stale for
 *     the remainder of its TTL is a strictly better outcome than a lost
 *     product update, so every failure here is logged and swallowed.
 */
class SitemapCacheInvalidator
{
    /**
     * Flush every host-scoped sitemap entry this installation has cached.
     */
    public function flushAll(): void
    {
        $this->guard('all', fn () => SitemapController::clearCache());
    }

    /**
     * Flush a single known host (caller must genuinely know its domain).
     */
    public function flushHost(?string $host = null): void
    {
        $this->guard((string) $host, fn () => SitemapController::clearCache($host));
    }

    private function guard(string $host, callable $flush): void
    {
        try {
            $flush();
        } catch (\Throwable $e) {
            Log::warning('sitemap cache flush failed', [
                'host'      => $host,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
