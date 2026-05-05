<?php

namespace App\Helpers;

/**
 * ساخت دادهٔ ساختاریافته (JSON-LD) برای سئو.
 */
class JsonLd
{
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => SiteHelper::siteName(),
            'url' => url('/'),
        ];
    }

    /**
     * WebSite برای صفحهٔ اصلی و جستجو.
     */
    public static function webSite(?string $searchUrl = null): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => SiteHelper::siteName(),
            'url' => url('/'),
        ];
        if ($searchUrl !== null && $searchUrl !== '') {
            $data['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $searchUrl . '?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ];
        }
        return $data;
    }

    public static function webPage(string $name, string $url, ?string $description = null): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $name,
            'url' => $url,
        ];
        if ($description !== null && $description !== '') {
            $data['description'] = $description;
        }
        return $data;
    }

    public static function article($post): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'url' => route('blog.show', $post->slug),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
        ];
        $desc = Seo::description($post);
        if ($desc !== '') {
            $data['description'] = $desc;
        }
        $image = Seo::imageUrl($post);
        if ($image !== null) {
            $data['image'] = $image;
        }
        return $data;
    }

    public static function product($product): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'url' => route('shop.show', $product->slug),
        ];
        $desc = Seo::description($product);
        if ($desc !== '') {
            $data['description'] = $desc;
        }
        $image = Seo::imageUrl($product);
        if ($image !== null) {
            $data['image'] = $image;
        }
        if (isset($product->price)) {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => (float) ($product->sale_price ?? $product->price),
                'priceCurrency' => 'IRR',
            ];
        }
        return $data;
    }

    /**
     * BreadcrumbList برای مسیر ناوبری.
     *
     * @param array<int, array{name: string, url?: string}> $items
     */
    public static function breadcrumb(array $items): array
    {
        $list = [];
        foreach ($items as $i => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'] ?? null,
            ];
        }
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }
}
