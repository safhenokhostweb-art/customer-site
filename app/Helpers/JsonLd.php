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

    /**
     * FAQPage برای بلوک FAQ در رندر عمومی Canvas.
     *
     * فقط Q/Aهایی را نگه می‌دارد که هر دو بخش (پرسش و پاسخ) واقعاً و
     * غیرخالی باشند؛ ورودی block از دادهٔ نامطمئن (authoring JSON) می‌آید،
     * پس هر item نامعتبر یا ناقص بدون ساختن داده حذف می‌شود. اگر هیچ مورد
     * معتبری نماند، آرایهٔ خالی برمی‌گردد — یعنی اصلاً چیزی چاپ نمی‌شود،
     * که بهتر از FAQPage بدون محتوای واقعی است.
     *
     * @param  array<int,mixed>  $items  آیتم‌های خام شکل ['question' => string, 'answer' => string]
     * @return array<string,mixed>
     */
    public static function faqPage(array $items): array
    {
        $entities = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $question = trim((string) ($item['question'] ?? ''));
            $answer   = trim((string) ($item['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        }

        if ($entities === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
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

    public static function product($product, ?float $offerPrice = null, ?string $availability = null, ?float $ratingAvg = null, int $ratingCount = 0): array
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
        $images = [];
        if ($product->relationLoaded('images') && $product->images->isNotEmpty()) {
            foreach ($product->images as $img) {
                $u = \App\Helpers\ImageHelper::url($img->path);
                if ($u) {
                    $images[] = $u;
                }
            }
        }
        $fallbackImg = Seo::imageUrl($product);
        if ($fallbackImg !== null) {
            $images[] = $fallbackImg;
        }
        $images = array_values(array_unique(array_filter($images)));
        if (count($images) === 1) {
            $data['image'] = $images[0];
        } elseif (count($images) > 1) {
            $data['image'] = $images;
        }
        $price = $offerPrice ?? (float) $product->effective_price;
        $avail = $availability ?? 'https://schema.org/InStock';
        $data['offers'] = [
            '@type' => 'Offer',
            'price' => round($price, 2),
            'priceCurrency' => 'IRR',
            'availability' => $avail,
            'url' => route('shop.show', $product->slug),
        ];
        if (! empty($product->sale_ends_at)) {
            $data['offers']['priceValidUntil'] = $product->sale_ends_at->format('Y-m-d');
        }
        if ($ratingCount > 0 && $ratingAvg !== null) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($ratingAvg, 2),
                'reviewCount' => $ratingCount,
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
