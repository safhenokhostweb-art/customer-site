<?php

namespace App\Services\ThemeCanvas;

use App\Models\Category;
use App\Models\Course;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Helpers\SiteHelper;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Route;

/**
 * Canvas overhaul Unit 4: the one catalog of things a Canvas link can point
 * at — CMS pages, products, product categories, blog posts, blog
 * categories, courses and the site's fixed destinations — with search for
 * the editor's picker and live resolution for the renderer.
 *
 * A picked link is stored as {type: 'entity', entity: <key>, id: <id>}
 * and resolved at every render, so renaming a page or product slug, or
 * unpublishing it, is reflected immediately without touching the theme.
 * Only published content resolves; anything missing resolves to '' (the
 * "no link" contract every renderer already understands). Shop, blog and
 * course routes are always registered but gated by the `module:` middleware,
 * so a destination is offered and resolved only while its module is enabled
 * (SiteHelper::moduleEnabled), never as a link to a blocked page.
 */
final class EntityLinkCatalog
{
    /** entity key => Persian label shown in the picker. */
    public const ENTITIES = [
        'route' => 'صفحات اصلی سایت',
        'page' => 'برگه‌ها',
        'product' => 'محصولات',
        'product_category' => 'دسته‌بندی محصولات',
        'post' => 'مقالات',
        'blog_category' => 'دسته‌بندی وبلاگ',
        'course' => 'دوره‌ها',
    ];

    /** Fixed destinations: id => [label, route name, module or null, route parameters]. */
    private const ROUTES = [
        'home' => ['صفحه اصلی', 'home', null],
        'shop' => ['فروشگاه', 'shop.index', 'shop'],
        'blog' => ['وبلاگ', 'blog.index', 'blog'],
        'courses' => ['دوره‌ها', 'courses.index', 'courses'],
        'contact' => ['تماس با ما', 'contact.show', null],
        // ADR-083: the library's "@link:about" goes through the dynamic page
        // route, so it opens whichever page the site gives the slug `about`
        // (a Canvas theme page or a CMS page), with no fixed route of its own.
        'about' => ['درباره ما', 'page.show', null, ['slug' => 'about']],
        'projects' => ['پروژه‌ها', 'page.show', null, ['slug' => 'projects']],
    ];

    /** entity => [route that renders it, module that gates it]. */
    private const ENTITY_ROUTES = [
        'page' => ['page.show', null],
        'product' => ['shop.show', 'shop'],
        'product_category' => ['shop.index', 'shop'],
        'post' => ['blog.show', 'blog'],
        'blog_category' => ['blog.category', 'blog'],
        'course' => ['courses.show', 'courses'],
    ];

    /** @var array<string, string> request-scoped "entity:id" => url */
    private array $resolved = [];

    public static function isReference(mixed $value): bool
    {
        return is_array($value) && ($value['type'] ?? null) === 'entity'
            && is_string($value['entity'] ?? null) && array_key_exists($value['entity'], self::ENTITIES)
            && (is_int($value['id'] ?? null) || (is_string($value['id'] ?? null) && $value['id'] !== ''));
    }

    /** The live URL of a stored reference, or '' when it no longer resolves. */
    public function resolve(array $reference): string
    {
        if (!self::isReference($reference)) {
            return '';
        }
        $key = $reference['entity'] . ':' . $reference['id'];

        return $this->resolved[$key] ??= $this->lookup($reference['entity'], $reference['id']);
    }

    /**
     * Picker search: up to $limit matches per entity type (or one type),
     * newest-relevant first, each with the live URL it would link to.
     *
     * @return list<array{entity: string, entity_label: string, id: int|string, title: string, url: string}>
     */
    public function search(string $query, ?string $entity = null, int $limit = 8): array
    {
        $query = trim($query);
        $types = $entity !== null && array_key_exists($entity, self::ENTITIES) ? [$entity] : array_keys(self::ENTITIES);
        $out = [];
        foreach ($types as $type) {
            foreach ($this->candidates($type, $query, $limit) as [$id, $title]) {
                $url = $this->lookup($type, $id);
                if ($url === '') {
                    continue;
                }
                $out[] = ['entity' => $type, 'entity_label' => self::ENTITIES[$type], 'id' => $id, 'title' => $title, 'url' => $url];
            }
        }

        return $out;
    }

    /** @return list<array{0: int|string, 1: string}> */
    private function candidates(string $type, string $q, int $limit): array
    {
        if ($type === 'route') {
            $rows = [];
            foreach (self::ROUTES as $id => [$label, $route, $module]) {
                if (self::available($route, $module) && ($q === '' || mb_stripos($label, $q) !== false || stripos($id, $q) !== false)) {
                    $rows[] = [$id, $label];
                }
            }
            return $rows;
        }

        if (!self::entityAvailable($type)) {
            return [];
        }
        $base = match ($type) {
            'page' => Page::published(),
            'product' => Product::published(),
            'product_category' => ProductCategory::query(),
            'post' => Post::published(),
            'blog_category' => Category::query(),
            'course' => Course::active(),
            default => null,
        };
        if ($base === null) {
            return [];
        }
        $titleColumn = in_array($type, ['product_category', 'blog_category'], true) ? 'name' : 'title';
        if ($q !== '') {
            $base->where(fn ($w) => $w->where($titleColumn, 'like', '%' . $q . '%')->orWhere('slug', 'like', '%' . $q . '%'));
        }

        return $base->orderByDesc('id')->limit($limit)->get()
            ->map(fn ($m) => [(int) $m->id, (string) ($m->{$titleColumn} ?? '')])
            ->all();
    }

    private function lookup(string $type, int|string $id): string
    {
        if ($type === 'route') {
            [, $route, $module] = self::ROUTES[$id] ?? [null, null, null];
            return $route !== null && self::available($route, $module) ? route($route, self::ROUTES[$id][3] ?? []) : '';
        }
        if (!self::entityAvailable($type) || (!is_int($id) && !ctype_digit((string) $id))) {
            return '';
        }
        $id = (int) $id;

        return match ($type) {
            'page' => ($m = Page::published()->find($id)) ? route('page.show', $m->slug) : '',
            'product' => ($m = Product::published()->find($id)) ? route('shop.show', $m->slug) : '',
            'product_category' => ($m = ProductCategory::find($id)) ? route('shop.index', ['category' => $m->slug ?: $m->id]) : '',
            'post' => ($m = Post::published()->find($id)) ? route('blog.show', $m->slug) : '',
            'blog_category' => ($m = Category::find($id)) ? route('blog.category', $m->slug) : '',
            'course' => ($m = Course::active()->find($id)) ? route('courses.show', $m->slug) : '',
            default => '',
        };
    }

    private static function entityAvailable(string $type): bool
    {
        [$route, $module] = self::ENTITY_ROUTES[$type] ?? [null, null];

        return $route !== null && self::available($route, $module);
    }

    private static function available(string $route, ?string $module): bool
    {
        return Route::has($route) && ($module === null || SiteHelper::moduleEnabled($module));
    }
}
