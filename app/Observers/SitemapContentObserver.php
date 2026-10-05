<?php

namespace App\Observers;

use App\Models\Form;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Services\SitemapCacheInvalidator;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 2.1 — keep the sitemap honest when sitemap-visible content changes.
 *
 * SitemapController::clearCache() had zero callers, so a published page, post,
 * product or form could stay out of (or wrongly inside) the cached sitemap for
 * the full `seo.sitemap_cache_ttl`. This observer closes that gap.
 *
 * Scope is deliberately exactly the four models SitemapController::collectEntries()
 * reads — nothing wider, so unrelated content writes never pay for a flush.
 *
 * It always flushes ALL hosts rather than one: these models have no tenant or
 * domain column (content is installation-wide), so no observer can attribute
 * a change to a specific host, and the cached entries are per-host.
 */
class SitemapContentObserver
{
    public function __construct(
        protected SitemapCacheInvalidator $invalidator
    ) {}

    /** @param  Model  $model  one of Page|Post|Product|Form */
    public function saved(Model $model): void
    {
        $this->invalidator->flushAll();
    }

    /** @param  Model  $model  one of Page|Post|Product|Form */
    public function deleted(Model $model): void
    {
        $this->invalidator->flushAll();
    }

    /**
     * @return array<int, class-string<Model>>
     */
    public static function observedModels(): array
    {
        return [Page::class, Post::class, Product::class, Form::class];
    }
}
