<?php

namespace App\Builder\Validation;

/**
 * Validates props against element schema, applies defaults, strips unknown keys, sanitizes.
 * Invalid props must not break rendering; we return sanitized props and list of errors.
 */
class ElementValidator
{
    /**
     * Validate and prepare props for render.
     * Returns [ 'props' => array (with defaults, only known keys), 'errors' => string[] ].
     */
    public static function validate(array $schema, array $props): array
    {
        $errors = [];
        $out = [];

        foreach ($schema as $key => $rules) {
            $value = array_key_exists($key, $props) ? $props[$key] : ($rules['default'] ?? null);
            $required = $rules['required'] ?? false;

            if ($value === null || $value === '') {
                if ($required) {
                    $errors[] = "Field \"{$key}\" is required.";
                }
                $out[$key] = $rules['default'] ?? null;
                continue;
            }

            $type = $rules['type'] ?? 'string';
            $max = $rules['max'] ?? null;
            $values = $rules['values'] ?? null;

            if ($type === 'enum' && $values !== null) {
                $normalized = $value;
                $allInt = count(array_filter($values, 'is_int')) === count($values);
                if ($allInt && (is_string($value) || is_float($value))) {
                    $normalized = (int) $value;
                }
                if (!in_array($normalized, $values, true)) {
                    $errors[] = "Field \"{$key}\" must be one of: " . implode(', ', array_map('strval', $values));
                    $out[$key] = $rules['default'] ?? $values[0];
                    continue;
                }
                $out[$key] = $normalized;
                continue;
            }

            if ($type === 'string' && !is_string($value)) {
                $value = (string) $value;
            }
            if ($type === 'integer' && !is_int($value)) {
                $value = (int) $value;
            }
            if ($type === 'boolean' && !is_bool($value)) {
                $value = (bool) $value;
            }
            if ($type === 'array' && !is_array($value)) {
                $errors[] = "Field \"{$key}\" must be an array.";
                $out[$key] = $rules['default'] ?? [];
                continue;
            }

            if ($max !== null && is_string($value) && strlen($value) > $max) {
                $errors[] = "Field \"{$key}\" exceeds max length ({$max}).";
                $value = mb_substr($value, 0, $max);
            }

            $out[$key] = $value;
        }

        return ['props' => $out, 'errors' => $errors];
    }

    /**
     * Apply defaults and keep only keys defined in schema. No validation errors returned.
     */
    public static function applySchema(array $schema, array $props): array
    {
        $out = [];
        foreach ($schema as $key => $rules) {
            $out[$key] = array_key_exists($key, $props) ? $props[$key] : ($rules['default'] ?? null);
        }
        return $out;
    }

    /**
     * Sanitize string for HTML content (escape).
     */
    public static function sanitizeString(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize rich HTML (allow limited tags).
     */
    public static function sanitizeRichHtml(string $s): string
    {
        return strip_tags($s, '<p><br><a><strong><em><ul><ol><li><h2><h3>');
    }
}
