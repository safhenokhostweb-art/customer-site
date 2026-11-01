<?php

namespace App\Services\ThemeCanvas;

/**
 * Canvas overhaul Unit 2a — the one answer to "which props of this block
 * type hold a value of control type X", derived from the block's own
 * registry schema instead of a hand-kept list.
 *
 * The lists this replaces (CustomerMediaResolver::MEDIA_PROPS /
 * MEDIA_REPEATERS) drifted: bento_grid named a field `image` while the
 * registry field is `bg_image`, so a Media Library pick there reached
 * renderBentoGrid() as an unresolved {media_id} array and the block
 * rendered as an error placeholder (production log, 2026-10-30/31). The
 * same audit found 17 of 53 image fields unresolved, including every
 * FoodMart image and the mega-menu column items, which a single
 * prop/field pair per type could not even describe.
 *
 * A path is a list of segments; `*` means "every item of this repeater".
 * Repeaters nest to any depth (nav_links[].mega_columns[].items[].image).
 * transform() returns an unchanged repeater byte-identical. A changed one
 * is written in its declared `storage` shape ('json' = JSON string,
 * otherwise an array), so the result always matches the schema even when
 * a caller passed the other shape.
 */
final class SchemaFieldMap
{
    /** @var array<string, array<string, list<array{path: list<string>, storage: list<string>}>>> control type => block type => specs */
    private static array $specs = [];

    /** @return list<list<string>> */
    public static function paths(string $blockType, string $controlType): array
    {
        return array_column(self::specs($blockType, $controlType), 'path');
    }

    /**
     * Each path plus the declared storage of every repeater it passes
     * through, in order (one entry per `*`).
     *
     * @return list<array{path: list<string>, storage: list<string>}>
     */
    private static function specs(string $blockType, string $controlType): array
    {
        if (!isset(self::$specs[$controlType][$blockType])) {
            $schema = BlockRegistry::get($blockType)['schema'] ?? [];
            $found = [];
            self::walk(is_array($schema) ? $schema : [], $controlType, [], [], $found);
            self::$specs[$controlType][$blockType] = $found;
        }

        return self::$specs[$controlType][$blockType];
    }

    /**
     * Every block type with at least one field of $controlType, with its
     * paths rendered as `prop[].field` strings (for sweeps and audits).
     *
     * @return array<string, list<string>>
     */
    public static function inventory(string $controlType): array
    {
        $out = [];
        foreach (array_keys(BlockRegistry::all()) as $type) {
            foreach (self::paths((string) $type, $controlType) as $path) {
                $out[$type][] = str_replace('.*', '[]', implode('.', $path));
            }
        }

        return $out;
    }

    /**
     * Replace every value at a $controlType path with $fn(value). Missing
     * keys are left missing; non-array items are skipped.
     *
     * @param callable(mixed): mixed $fn
     */
    public static function transform(string $blockType, array $props, string $controlType, callable $fn): array
    {
        foreach (self::specs($blockType, $controlType) as $spec) {
            $props = self::apply($props, $spec['path'], $spec['storage'], $fn);
        }

        return $props;
    }

    /**
     * Every value currently stored at a $controlType path.
     *
     * @return list<mixed>
     */
    public static function values(string $blockType, array $props, string $controlType): array
    {
        $values = [];
        self::transform($blockType, $props, $controlType, function (mixed $value) use (&$values) {
            $values[] = $value;
            return $value;
        });

        return $values;
    }

    /** Test hook, called from BlockRegistry::reset(). */
    public static function reset(): void
    {
        self::$specs = [];
    }

    /** @param list<array{path: list<string>, storage: list<string>}> $found */
    private static function walk(array $schema, string $controlType, array $prefix, array $storage, array &$found): void
    {
        foreach ($schema as $key => $control) {
            if (!is_array($control) || !is_string($key)) {
                continue;
            }
            $type = $control['type'] ?? null;
            if ($type === $controlType) {
                $found[] = ['path' => [...$prefix, $key], 'storage' => $storage];
            }
            if ($type === 'repeater' && is_array($control['fields'] ?? null)) {
                $declared = ($control['storage'] ?? 'array') === 'json' ? 'json' : 'array';
                self::walk($control['fields'], $controlType, [...$prefix, $key, '*'], [...$storage, $declared], $found);
            }
        }
    }

    /**
     * @param list<string> $path
     * @param list<string> $storage declared storage of each repeater still ahead
     * @param callable(mixed): mixed $fn
     */
    private static function apply(mixed $node, array $path, array $storage, callable $fn): mixed
    {
        if ($path === []) {
            return $fn($node);
        }

        // A repeater persisted with storage 'json' arrives as a string.
        $original = $node;
        $wasJson = false;
        if (is_string($node) && $node !== '' && ($node[0] === '[' || $node[0] === '{')) {
            $decoded = json_decode($node, true);
            if (is_array($decoded)) {
                $node = $decoded;
                $wasJson = true;
            }
        }
        if (!is_array($node)) {
            return $node;
        }

        $segment = array_shift($path);
        $declared = $segment === '*' ? array_shift($storage) : null;
        $changed = false;
        if ($segment === '*') {
            foreach ($node as $index => $item) {
                if (is_array($item) || is_string($item)) {
                    $next = self::apply($item, $path, $storage, $fn);
                    if ($next !== $item) {
                        $node[$index] = $next;
                        $changed = true;
                    }
                }
            }
        } elseif (array_key_exists($segment, $node)) {
            $next = self::apply($node[$segment], $path, $storage, $fn);
            if ($next !== $node[$segment]) {
                $node[$segment] = $next;
                $changed = true;
            }
        }

        if (!$changed) {
            return $original;
        }

        // $node is a repeater value when its segment was `*`.
        $asJson = $declared !== null ? $declared === 'json' : $wasJson;

        return $asJson ? json_encode($node, JSON_UNESCAPED_UNICODE) : $node;
    }
}
