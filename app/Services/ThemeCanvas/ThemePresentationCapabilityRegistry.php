<?php

namespace App\Services\ThemeCanvas;

/**
 * Theme-neutral bridge from domain capabilities to presentations declared by
 * the active theme's registered block types. It never supplies markup or data.
 */
final class ThemePresentationCapabilityRegistry
{
    /** @return array<string,array{label:string,presentations:list<array<string,mixed>>}> */
    public function availableFor(array $themeData): array
    {
        $activeTypes = $this->activeTypes($themeData);
        $result = [];

        foreach (BlockRegistry::all() as $type => $definition) {
            $presentation = $definition['modulePresentation'] ?? null;
            if (!isset($activeTypes[$type]) || !is_array($presentation)) {
                continue;
            }

            $capability = $presentation['capability'] ?? null;
            $source = $definition['dataBinding']['source'] ?? null;
            if (!is_string($capability) || !is_string($source)) {
                continue;
            }

            $result[$capability] ??= [
                'label' => match ($capability) {
                    'products.collection' => 'Products',
                    'product_categories.collection' => 'Product Categories',
                    'blog.posts' => 'Blog Posts',
                    'reviews.collection' => 'Reviews',
                    default => $capability,
                },
                'presentations' => [],
            ];
            $result[$capability]['presentations'][] = [
                'type' => $type,
                'label' => (string) ($presentation['label'] ?? $definition['name'] ?? $type),
                'source' => $source,
                'default_sort' => (string) ($definition['dataBinding']['default_sort'] ?? 'catalog_order'),
            ];
        }

        ksort($result);

        return $result;
    }

    /** @return array<string,true> */
    private function activeTypes(array $themeData): array
    {
        $types = [];
        $walk = function (array $blocks) use (&$walk, &$types): void {
            foreach ($blocks as $block) {
                if (!is_array($block)) {
                    continue;
                }
                if (is_string($block['type'] ?? null)) {
                    $types[$block['type']] = true;
                }
                if (is_array($block['children'] ?? null)) {
                    $walk($block['children']);
                }
            }
        };

        $walk(is_array($themeData['header_blocks'] ?? null) ? $themeData['header_blocks'] : []);
        $walk(is_array($themeData['footer_blocks'] ?? null) ? $themeData['footer_blocks'] : []);
        foreach (($themeData['pages'] ?? []) as $page) {
            if (is_array($page)) {
                $walk(is_array($page['blocks'] ?? null) ? $page['blocks'] : []);
            }
        }

        return $types;
    }
}
