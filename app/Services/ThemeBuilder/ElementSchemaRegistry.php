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
        $this->schemas = config('builder-element-schemas.elements', []);
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
            if ($expectedType === 'string' && is_string($value) && strlen($value) > $maxLen) {
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
                return is_string($value);
            case 'integer':
                return is_int($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            default:
                return true;
        }
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
