<?php

namespace App\Builder\Validation;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use SaaSPlatform\ThemeRuntime\Security\HtmlSanitizer;

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

            // Canvas overhaul Unit 2b: a link is a URL string or an ADR-061
            // structured {url, target, …} link. Casting the latter to
            // string raised "Array to string conversion" and failed the
            // whole block.
            if ($type === 'link') {
                $out[$key] = is_string($value) || (is_array($value) && is_string($value['url'] ?? null))
                    ? $value
                    : ($rules['default'] ?? '');
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
     * Phase 3E-D — validates against a declarative ThemeBuilderElementDefinition
     * instead of a raw schema array. Delegates entirely to the existing
     * validate() above (via the definition's toLegacySchema()) so
     * coercion/defaults/unknown-key-stripping/non-throwing behavior is
     * byte-identical to today's — this is a convenience entry point, not a
     * second validator.
     */
    public static function validateDefinition(ThemeBuilderElementDefinition $definition, array $props): array
    {
        return self::validate($definition->toLegacySchema(), $props);
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
     *
     * Phase 3E-C: delegates to the shared canonical sanitization authority
     * instead of this class's own independent strip_tags()-only copy — see
     * App\Builder\Traits\WithSanitization::sanitizeHtml() for the full
     * rationale (the same fix, applied to this class's separate duplicate).
     */
    public static function sanitizeRichHtml(string $s): string
    {
        return HtmlSanitizer::sanitizeRichHtml($s);
    }
}
