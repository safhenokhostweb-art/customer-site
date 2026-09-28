<?php

namespace App\Services\ThemeCanvas\DataBinding;

use App\Services\ThemeCanvas\BlockRegistry;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class ThemeDataBindingRegistry
{
    /** @var array<string,ThemeDataSource> */
    private array $sources;

    public function __construct(ProductDataSource $products, ProductCategoryDataSource $categories, BlogPostDataSource $blogPosts, ReviewDataSource $reviews)
    {
        $this->sources = [
            $products->source() => $products,
            $categories->source() => $categories,
            $blogPosts->source() => $blogPosts,
            $reviews->source() => $reviews,
        ];
    }

    /** @return array<string,array{sorts:array<int,string>,default_limit:int}> */
    public static function capabilities(): array
    {
        return [
            'products' => ['sorts' => ProductDataSource::SORTS, 'default_limit' => 10],
            'product_categories' => ['sorts' => ProductCategoryDataSource::SORTS, 'default_limit' => 12],
            'blog_posts' => ['sorts' => BlogPostDataSource::SORTS, 'default_limit' => 3],
            'reviews' => ['sorts' => ReviewDataSource::SORTS, 'default_limit' => 3],
        ];
    }

    /** @param array<int,array<string,mixed>> ...$blockLists @return array<string,array<string,mixed>> */
    public function resolveBlockLists(array ...$blockLists): array
    {
        $resolved = [];
        foreach ($blockLists as $blocks) {
            $this->walk($blocks, $resolved);
        }

        return $resolved;
    }

    /**
     * Resolve a dynamic widget through the same tenant-scoped, allowlisted
     * sources used by legacy bindings. The shared runtime never receives a
     * database handle or arbitrary query callback.
     *
     * @return array<string,mixed>
     */
    public function resolveDynamicSource(string $source, array $query): array
    {
        return $this->resolve([
            'source' => $source,
            'query' => $query,
            'empty' => 'empty_state',
        ], $source);
    }

    /** @param array<int,array<string,mixed>> $blocks @param array<string,array<string,mixed>> $resolved */
    private function walk(array $blocks, array &$resolved): void
    {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $id = (string) ($block['id'] ?? '');
            if ($id !== '' && array_key_exists('binding', $block)) {
                $definition = BlockRegistry::get((string) ($block['type'] ?? ''));
                $allowedSource = is_array($definition['dataBinding'] ?? null)
                    ? ($definition['dataBinding']['source'] ?? null)
                    : null;
                $resolved[$id] = $this->resolve($block['binding'], is_string($allowedSource) ? $allowedSource : null);
            }
            if (is_array($block['children'] ?? null)) {
                $this->walk($block['children'], $resolved);
            }
        }
    }

    /** @return array<string,mixed> */
    private function resolve(mixed $binding, ?string $allowedSource): array
    {
        try {
            if (!is_array($binding)) {
                throw new InvalidArgumentException('Binding must be an object.');
            }
            $this->assertOnlyKeys($binding, ['source', 'query', 'empty']);
            $source = $binding['source'] ?? null;
            if (!is_string($source) || !isset($this->sources[$source]) || $source !== $allowedSource) {
                throw new InvalidArgumentException('Unknown binding source.');
            }
            $query = $binding['query'] ?? [];
            if (!is_array($query)) {
                throw new InvalidArgumentException('Binding query must be an object.');
            }
            $this->assertOnlyKeys($query, ['sort', 'limit', 'category_id', 'collection_slug', 'selected_ids']);
            $limit = filter_var($query['limit'] ?? 10, FILTER_VALIDATE_INT);
            if ($limit === false || $limit < 1 || $limit > 24) {
                throw new InvalidArgumentException('Binding limit is outside the allowed range.');
            }
            $query['limit'] = $limit;
            if (isset($query['category_id']) && (!is_numeric($query['category_id']) || (int) $query['category_id'] < 1)) {
                throw new InvalidArgumentException('Invalid category filter.');
            }
            if (isset($query['collection_slug'])) {
                if ($source !== 'products' || !is_string($query['collection_slug'])
                    || preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $query['collection_slug']) !== 1) {
                    throw new InvalidArgumentException('Invalid collection filter.');
                }
            }
            if (isset($query['selected_ids']) && (!is_array($query['selected_ids']) || count($query['selected_ids']) > 24)) {
                throw new InvalidArgumentException('Invalid manual selection.');
            }
            foreach ($query['selected_ids'] ?? [] as $selectedId) {
                if (filter_var($selectedId, FILTER_VALIDATE_INT) === false || (int) $selectedId < 1) {
                    throw new InvalidArgumentException('Manual selection contains an invalid ID.');
                }
            }
            // Phase 4B.6 — validate against the RESOLVED source's own sort
            // allowlist (was hard-coded to the Product family, so the
            // registered blog_posts source always rejected as invalid).
            $sorts = $this->sources[$source]::SORTS;
            $defaultSort = $sorts[0];
            $sort = (string) ($query['sort'] ?? $defaultSort);
            if (!in_array($sort, $sorts, true)) {
                throw new InvalidArgumentException('Unknown binding sort.');
            }
            $query['sort'] = $sort;
            $empty = $binding['empty'] ?? 'hide';
            if (!in_array($empty, ['hide', 'empty_state', 'manual'], true)) {
                throw new InvalidArgumentException('Unknown empty-data policy.');
            }

            return ['source' => $source, 'status' => 'resolved', 'empty' => $empty]
                + $this->sources[$source]->resolve($query);
        } catch (\Throwable $exception) {
            Log::warning('Theme data binding rejected', ['message' => $exception->getMessage()]);

            return ['source' => is_array($binding) && is_string($binding['source'] ?? null) ? $binding['source'] : '', 'status' => 'invalid', 'empty' => 'hide', 'items' => []];
        }
    }

    /** @param array<string,mixed> $value @param array<int,string> $allowed */
    private function assertOnlyKeys(array $value, array $allowed): void
    {
        if (array_diff(array_keys($value), $allowed) !== []) {
            throw new InvalidArgumentException('Binding contains unsupported keys.');
        }
    }
}
