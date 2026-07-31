<?php

namespace App\Services\Seo;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use SaaSPlatform\CoreUtils\Contracts\Services\SitemapUrlProviderInterface;

/**
 * Phase 18-C — Part 3: the real, Core-native source of sitemap URLs —
 * static pages, blog posts, and shop products. Bound as the default (not
 * a Null Object, unlike SeoOverrideProviderInterface in Phase 18-B) since
 * Core is the only side that can genuinely answer "what are this site's
 * public URLs" — a sitemap module has no business knowing about Page/
 * Post/Product directly (see the Phase 18-C audit: "بدون Hardcode کردن
 * مدل‌های Core").
 */
class CoreSitemapUrlProvider implements SitemapUrlProviderInterface
{
    public function urls(): array
    {
        $urls = [['loc' => url('/'), 'changefreq' => 'daily', 'priority' => 1.0]];

        if (Schema::hasTable('pages')) {
            foreach (Page::published()->get() as $page) {
                $urls[] = [
                    'loc' => route('page.show', $page->slug),
                    'lastmod' => optional($page->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => 0.7,
                ];
            }
        }

        if (Schema::hasTable('posts')) {
            foreach (Post::published()->get() as $post) {
                $urls[] = [
                    'loc' => route('blog.show', $post->slug),
                    'lastmod' => optional($post->updated_at ?? $post->published_at)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => 0.6,
                ];
            }
        }

        if (Schema::hasTable('products')) {
            foreach (Product::active()->published()->get() as $product) {
                $urls[] = [
                    'loc' => route('shop.show', $product->slug),
                    'lastmod' => optional($product->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => 0.8,
                ];
            }
        }

        return $urls;
    }
}
