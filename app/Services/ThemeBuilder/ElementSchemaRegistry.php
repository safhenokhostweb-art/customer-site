<?php

namespace App\Services\ThemeBuilder;

use Illuminate\Support\Facades\Log;

/**
 * Central registry and validator for builder element contracts.
 * Validates block JSON against schema before render. Invalid blocks are rejected
 * and a fallback is shown instead.
 */
class ElementSchemaRegistry
{
    protected array $schemas = [];

    protected array $lastErrors = [];

    public function __construct()
    {
        // BLD-14 (Full Product Commercial Audit, TASK 1/3) — documented
        // precedence contract, replacing the prior whole-type `+` merge.
        //
        // The intended rule has always been two separate concerns that a
        // single `array1 + array2` cannot express at once:
        //
        //   1. TYPE EXISTENCE: a type is known if ANY source declares it.
        //      Canvas is the authoring palette tenant admins actually use,
        //      so a Canvas-createable type must never be "Unknown element
        //      type" here merely because config/builder-element-schemas.php
        //      hasn't been hand-written for it yet (the original Phase
        //      3E-H-B bug this file's history already fixed once).
        //   2. PER-FIELD TYPE CONTRACT: when the SAME field of the SAME
        //      type is declared by more than one source, the deliberate,
        //      hand-authored contract in `config('builder-element-schemas')`
        //      must win — never a coarser, generically-inferred type
        //      derived from a Canvas UI *control* kind (e.g. a `textarea`
        //      control being inferred as `string` when the real prop is a
        //      JSON-encoded array, or a `select` control being inferred as
        //      the catch-all `mixed`, silently disabling validation).
        //
        // The prior `canvasSchemas() + ...` order got (1) right but broke
        // (2) for every type declared in both places (found via
        // `client_logos.logos` and `heading.level`, both silently coerced
        // to a Canvas-inferred type that accepted or rejected the wrong
        // shape). `mergeSchemaSources()` below keeps (1) as a union while
        // restoring (2): earlier sources in the list win field-by-field;
        // later sources only fill in types/fields no earlier source
        // declares. `ext:*` types never collide with canonical keys by
        // construction (Phase 3E-B), so their relative position doesn't
        // matter for (2).
        $this->schemas = $this->mergeSchemaSources([
            config('builder-element-schemas.elements', []),
            \App\Builder\Extension\ProductionBuilderAdapter::schemas(),
            $this->canvasSchemas(),
            $this->productionPaletteSchemas(),
        ]);
    }

    /**
     * Merge schema sources with per-field precedence (see the precedence
     * contract documented in __construct()): the first source in $sources
     * to declare a given type keeps that type's own `type`/`styles`/
     * `children`, and wins per-field for `props`; every later source only
     * contributes props the winning source didn't already declare.
     */
    private function mergeSchemaSources(array $sources): array
    {
        $merged = [];
        foreach ($sources as $source) {
            foreach ($source as $type => $definition) {
                if (!isset($merged[$type])) {
                    $merged[$type] = $definition;
                    continue;
                }
                foreach (($definition['props'] ?? []) as $propName => $rule) {
                    if (!array_key_exists($propName, $merged[$type]['props'] ?? [])) {
                        $merged[$type]['props'][$propName] = $rule;
                    }
                }
            }
        }
        return $merged;
    }

    /** @return array<string, array{type:string, props:array, styles:array, children:string}> */
    private function canvasSchemas(): array
    {
        $schemas = [];

        foreach (\App\Services\ThemeCanvas\BlockRegistry::all() as $type => $definition) {
            // module:* blocks are dispatched by ThemeCanvasRenderer to their
            // tenant-data adapters and have never entered this validator.
            if (str_starts_with($type, 'module:')) {
                continue;
            }

            $props = [];
            foreach (($definition['schema'] ?? []) as $name => $control) {
                $props[$name] = [
                    'required' => false,
                    'type' => $this->validationType($control['type'] ?? 'string', $control),
                ];
            }

            $schemas[$type] = [
                'type' => $type,
                'props' => $props,
                'styles' => ['layout', 'typography', 'background', 'effects'],
                'children' => \App\Services\ThemeCanvas\BlockRegistry::isContainerType($type) ? 'blocks' : 'none',
            ];
        }

        return $schemas;
    }

    /** Project the still-exposed legacy Production Builder palette into the
     * same validator until that UI is formally retired. */
    private function productionPaletteSchemas(): array
    {
        $schemas = [];
        foreach (config('builder-elements.elements', []) as $type => $definition) {
            $props = [];
            foreach (($definition['schema'] ?? []) as $name => $control) {
                $props[$name] = [
                    'required' => false,
                    'type' => $this->validationType($control['type'] ?? 'string', $control),
                ];
            }
            $schemas[$type] = [
                'type' => $type,
                'props' => $props,
                'styles' => ['layout', 'typography', 'background', 'effects'],
                'children' => 'none',
            ];
        }
        return $schemas;
    }

    private function validationType(string $controlType, array $control = []): string
    {
        return match ($controlType) {
            // Phase 11 — a number control whose authored default is a float
            // (e.g. hero_content_wrapper.title_size: default 3.25, step
            // 0.25) legitimately produces fractional values; the flat
            // 'integer' mapping rejected the control's own default and
            // placeholdered the whole block. Float-defaulted controls
            // validate as 'numeric'; whole-number defaults keep the strict
            // integer contract.
            'number' => is_float($control['default'] ?? null) ? 'numeric' : 'integer',
            'boolean' => 'boolean',
            'repeater' => ($control['storage'] ?? 'array') === 'json' ? 'string' : 'array',
            // Canvas textarea controls intentionally persist JSON-shaped
            // values as text.  Treating those values as arrays caused valid
            // defaults such as stats_row and client_logos to be rejected.
            'textarea', 'string', 'color', 'motion' => 'string',
            // MEDIA-4: a canonical Media Library selection persists as
            // {"media_id": "<uuid>"} — a JSON object, not a string. A plain
            // URL/path string remains valid as transitional legacy data.
            'image' => 'media',
            // A select is a UI representation, not a persisted type. Its
            // existing defaults include both numeric and string options.
            'select' => 'mixed',
            default => 'mixed',
        };
    }

    /**
     * Get schema for an element type. Returns null if type is unknown.
     */
    public function getSchema(string $type): ?array
    {
        return $this->schemas[$type] ?? null;
    }

    /**
     * Get all registered element types.
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->schemas);
    }

    /**
     * Validate a single block (element). Structure: type, props, style?, blocks|children?.
     * Returns true if valid; use getLastErrors() for details when false.
     */
    public function validate(array $block, int $depth = 0): bool
    {
        $this->lastErrors = [];

        $maxDepth = config('builder-element-schemas.limits.children_max_depth', 20);
        if ($depth > $maxDepth) {
            $this->lastErrors[] = "Max nesting depth ({$maxDepth}) exceeded.";
            return false;
        }

        if (!isset($block['type']) || !is_string($block['type'])) {
            $this->lastErrors[] = 'Block must have a string "type".';
            return false;
        }

        $type = $block['type'];
        $schema = $this->getSchema($type);

        if ($schema === null) {
            $this->lastErrors[] = "Unknown element type: {$type}.";
            return false;
        }

        $props = $block['props'] ?? $block['data'] ?? [];
        if (!is_array($props)) {
            $this->lastErrors[] = "Block \"{$type}\": props must be an array.";
            return false;
        }

        foreach ($schema['props'] ?? [] as $propName => $rules) {
            $required = $rules['required'] ?? false;
            $expectedType = $rules['type'] ?? 'string';
            $value = $props[$propName] ?? null;

            if ($value === null || $value === '') {
                if ($required) {
                    $this->lastErrors[] = "Block \"{$type}\": required prop \"{$propName}\" is missing.";
                }
                continue;
            }

            if (!$this->assertType($value, $expectedType)) {
                $this->lastErrors[] = "Block \"{$type}\": prop \"{$propName}\" must be {$expectedType}.";
            }

            $maxLen = config('builder-element-schemas.limits.string_prop_max_length', 65535);
            if (($expectedType === 'string' || $expectedType === 'media') && is_string($value) && strlen($value) > $maxLen) {
                $this->lastErrors[] = "Block \"{$type}\": prop \"{$propName}\" exceeds max length.";
            }

            $urlMaxLen = config('builder-element-schemas.limits.url_max_length', 2048);
            if (in_array($propName, ['url', 'button_url', 'link'], true) && is_string($value) && strlen($value) > $urlMaxLen) {
                $this->lastErrors[] = "Block \"{$type}\": prop \"{$propName}\" (URL) exceeds max length.";
            }
        }

        if (isset($block['style']) && !is_array($block['style'])) {
            $this->lastErrors[] = "Block \"{$type}\": style must be an array if present.";
        }

        $children = $block['blocks'] ?? $block['children'] ?? [];
        if (!empty($children) && !is_array($children)) {
            $this->lastErrors[] = "Block \"{$type}\": children/blocks must be an array.";
        }

        $childrenRule = $schema['children'] ?? 'none';
        if ($childrenRule === 'blocks' && is_array($children)) {
            $currentErrors = $this->lastErrors;
            foreach ($children as $index => $child) {
                if (!is_array($child)) {
                    $currentErrors[] = "Block \"{$type}\": child at index {$index} must be an array.";
                    continue;
                }
                $this->lastErrors = [];
                if (!$this->validate($child, $depth + 1)) {
                    foreach ($this->lastErrors as $err) {
                        $currentErrors[] = "[child {$index}] {$err}";
                    }
                }
            }
            $this->lastErrors = $currentErrors;
        }

        return empty($this->lastErrors);
    }

    protected function assertType($value, string $expected): bool
    {
        switch ($expected) {
            case 'string':
                // Legacy palette defaults predate the Canvas control model
                // and contain a small number of scalar numeric values (for
                // example row.columns). Browser controls submit strings;
                // accepting either scalar representation preserves that
                // contract without admitting arrays/objects.
                return is_string($value) || is_int($value) || is_float($value);
            case 'integer':
                return is_int($value) || self::isIntegerString($value);
            case 'numeric':
                // Companion of the float-defaulted 'number' control
                // mapping: whole numbers stay valid, the fractional
                // values the control authors by design are accepted, and
                // non-numeric strings/arrays are still rejected.
                return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'json_array':
                // BLD-14 follow-up: a Canvas `textarea` control authoring a
                // repeater-shaped value (e.g. client_logos.logos,
                // stats_row.stats) persists its DEFAULT as a JSON-encoded
                // string by construction (BlockRegistry's own defaultProps),
                // while a value built via the structured repeater UI, or
                // saved after decode/re-encode, may already be a real PHP
                // array. Every consumer of these props already accepts
                // both shapes (see e.g. renderClientLogos()'s
                // `is_array($raw) ? $raw : json_decode(...)`) — this type
                // makes the validator agree with what the renderer already
                // does, instead of rejecting one of the two shapes it
                // actually has to handle.
                if (is_array($value)) {
                    return true;
                }
                return is_string($value) && json_validate($value) && is_array(json_decode($value, true));
            case 'media':
                // Canonical: {"media_id": "<uuid>"}. Transitional: a legacy
                // URL/path string. Never a bare array without media_id —
                // that would be neither shape and must still be rejected.
                if (is_string($value)) {
                    return true;
                }
                return is_array($value) && isset($value['media_id']) && is_string($value['media_id']) && $value['media_id'] !== '';
            default:
                return true;
        }
    }

    /**
     * Phase 3F-D — the real root cause of the heading.level defect (and
     * every other select-backed integer-typed prop): NONE of these fields'
     * `<select>` controls, in either Production Builder's or Canvas's
     * blade, apply Alpine's `.number` x-model modifier (verified directly
     * against both templates) — so any value the user actually chooses via
     * the dropdown (not merely a never-touched default) arrives here as a
     * string ('2'), never the real int every hand-authored fixture/preset/
     * kit in this codebase already stores. This is a validation-boundary
     * coercion, not a type-domain change: `assertType()` still rejects
     * anything that isn't a genuine whole-number representation (non-
     * numeric strings, decimals, arrays) exactly as strictly as before —
     * it only additionally recognizes the one extra shape a real browser
     * select control legitimately produces. No persisted data changes;
     * every existing integer-valued prop already satisfies is_int() and is
     * completely unaffected.
     */
    private static function isIntegerString($value): bool
    {
        return is_string($value) && preg_match('/^-?\d+$/', $value) === 1;
    }

    /**
     * Get validation errors from the last validate() call.
     */
    public function getLastErrors(): array
    {
        return $this->lastErrors;
    }

    /**
     * Check if the registry has a schema for the given type.
     */
    public function has(string $type): bool
    {
        return isset($this->schemas[$type]);
    }
}
