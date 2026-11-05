<?php

namespace App\Services\MotionGraphics;

/**
 * ADR-079 (saas-platform): the customer-site half of the motion runtime
 * parameter contract. The platform declares, per asset, which texts,
 * colours and animation switches a site owner may change (manifest
 * `assets[].parameters`); the Canvas inspector edits them into the block's
 * `motion_params`; the renderer passes the valid, non-default ones to the
 * platform as query parameters of the asset URL, where they are validated
 * again and applied.
 *
 * Mirrors the platform's MotionSvgComposer rules on purpose (defence in
 * depth, like the reader's forbidden-field re-check): a malformed definition
 * is dropped, never guessed, and only declared keys with valid values ever
 * leave this site.
 */
final class MotionRuntimeParameters
{
    private const TYPES = ['text', 'color', 'number', 'boolean', 'select'];
    private const GROUPS = ['content', 'style', 'motion'];
    private const KEY = '/^[a-z][a-z0-9_]{0,39}$/';
    private const MAX = 40;

    /**
     * @return list<array<string, mixed>>
     */
    public static function normalizeDefinitions(mixed $definitions): array
    {
        if (! is_array($definitions)) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($definitions as $def) {
            if (! is_array($def) || count($out) >= self::MAX) {
                continue;
            }
            $key = $def['key'] ?? null;
            $type = $def['type'] ?? null;
            $label = $def['label'] ?? null;
            if (! is_string($key) || ! preg_match(self::KEY, $key) || isset($seen[$key])
                || ! in_array($type, self::TYPES, true) || ! is_string($label) || trim($label) === '') {
                continue;
            }
            $clean = [
                'key' => $key,
                'type' => $type,
                'label' => mb_substr(trim($label), 0, 60),
                'group' => in_array($def['group'] ?? null, self::GROUPS, true) ? $def['group'] : 'content',
            ];
            $clean = match ($type) {
                'text' => is_string($def['default'] ?? null) && $def['default'] !== ''
                    ? [...$clean, 'default' => $def['default'], 'max' => max(1, min(80, (int) ($def['max'] ?? 40)))] : null,
                'color' => is_string($def['default'] ?? null) && preg_match('/^#[0-9a-f]{6}$/', $def['default'])
                    ? [...$clean, 'default' => $def['default']] : null,
                'number' => self::numberDefinition($def, $clean),
                'boolean' => is_bool($def['default'] ?? null) ? [...$clean, 'default' => $def['default']] : null,
                'select' => self::selectDefinition($def, $clean),
            };
            if ($clean !== null) {
                $out[] = $clean;
                $seen[$key] = true;
            }
        }

        return $out;
    }

    /**
     * Valid, non-default values for declared keys only, sorted by key
     * (so equal customisations produce the same URL), as query strings.
     *
     * @param list<array<string, mixed>> $definitions
     * @return array<string, string>
     */
    public static function query(array $definitions, mixed $values): array
    {
        if (is_string($values)) {
            $values = json_decode($values, true);
        }
        if (! is_array($values)) {
            return [];
        }
        $query = [];
        foreach ($definitions as $def) {
            if (! array_key_exists($def['key'], $values)) {
                continue;
            }
            $raw = $values[$def['key']];
            $value = match ($def['type']) {
                'text' => self::text($raw, (int) $def['max']),
                'color' => is_string($raw) && preg_match('/^#[0-9a-fA-F]{6}$/', $raw) ? strtolower($raw) : null,
                'number' => (is_int($raw) || is_float($raw) || (is_string($raw) && is_numeric($raw)))
                    && (float) $raw >= $def['min'] && (float) $raw <= $def['max'] ? $raw + 0 : null,
                'boolean' => is_bool($raw) ? $raw : (in_array($raw, ['1', '0', 1, 0, 'true', 'false'], true) ? in_array($raw, ['1', 1, 'true'], true) : null),
                'select' => is_string($raw) && array_key_exists($raw, $def['options']) ? $raw : null,
            };
            if ($value === null || $value === $def['default'] || ($def['type'] === 'number' && (float) $value === (float) $def['default'])) {
                continue;
            }
            $query[$def['key']] = match (true) {
                is_bool($value) => $value ? '1' : '0',
                default => (string) $value,
            };
        }
        ksort($query);

        return $query;
    }

    private static function numberDefinition(array $def, array $clean): ?array
    {
        foreach (['min', 'max', 'default'] as $k) {
            if (! is_int($def[$k] ?? null) && ! is_float($def[$k] ?? null)) {
                return null;
            }
        }
        if ($def['min'] >= $def['max'] || $def['default'] < $def['min'] || $def['default'] > $def['max']) {
            return null;
        }

        return [...$clean, 'default' => $def['default'], 'min' => $def['min'], 'max' => $def['max'],
            'step' => is_int($def['step'] ?? null) || is_float($def['step'] ?? null) ? $def['step'] : 1,
            'format' => ($def['format'] ?? null) === 'percent' ? 'percent' : 'plain'];
    }

    private static function selectDefinition(array $def, array $clean): ?array
    {
        $options = [];
        foreach ((array) ($def['options'] ?? []) as $value => $label) {
            if (is_string($value) && preg_match('/^[a-z0-9_]{1,20}$/', $value) && is_string($label) && trim($label) !== '') {
                $options[$value] = mb_substr(trim($label), 0, 40);
            }
        }
        $default = $def['default'] ?? null;

        return $options !== [] && is_string($default) && array_key_exists($default, $options)
            ? [...$clean, 'default' => $default, 'options' => $options] : null;
    }

    private static function text(mixed $raw, int $max): ?string
    {
        if (! is_string($raw) || ! mb_check_encoding($raw, 'UTF-8')) {
            return null;
        }
        $text = preg_replace('/(?![\x{200C}\x{200D}])[\p{Cc}\p{Cf}\p{Co}\p{Cs}]/u', '', $raw) ?? '';
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $text === '' ? null : mb_substr($text, 0, $max);
    }
}
