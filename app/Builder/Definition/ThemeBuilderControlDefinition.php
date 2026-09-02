<?php

namespace App\Builder\Definition;

/**
 * Phase 3E-D — one control (editor widget + validation rule) within a
 * ThemeBuilderElementDefinition's declarative schema. Built FROM the exact
 * field-rule shape every element's existing getSchema() already returns
 * (`type`, `required`, `default`, `max`, `values`) — this is a formalizing
 * wrapper around that existing shape, not a second schema format. An
 * optional `control` key (new, additive) names the editor widget directly;
 * when absent it is inferred from the existing validation `type`.
 *
 * Declarative only — never carries rendering logic, a raw-HTML-trust flag,
 * or a callback. See ElementDefinitionException for the closed set of
 * rejection reasons.
 */
final class ThemeBuilderControlDefinition
{
    public const TEXT = 'text';
    public const TEXTAREA = 'textarea';
    public const NUMBER = 'number';
    public const TOGGLE = 'toggle';
    public const SELECT = 'select';
    public const COLOR = 'color';
    public const MEDIA = 'media';
    public const URL = 'url';
    public const SPACING = 'spacing';
    public const RESPONSIVE = 'responsive';

    /**
     * V1 vocabulary only (Phase 3E-A: repeater/group have no current real
     * element usage to justify them — deferred, not built speculatively).
     */
    private const V1_CONTROL_TYPES = [
        self::TEXT, self::TEXTAREA, self::NUMBER, self::TOGGLE, self::SELECT,
        self::COLOR, self::MEDIA, self::URL, self::SPACING, self::RESPONSIVE,
    ];

    private function __construct(
        public readonly string $key,
        public readonly string $controlType,
        public readonly string $validationType,
        public readonly ?string $label,
        public readonly mixed $default,
        public readonly bool $required,
        public readonly ?int $max,
        public readonly ?array $options,
    ) {
    }

    public static function fromSchemaField(string $key, array $rules): self
    {
        $validationType = $rules['type'] ?? 'string';
        $controlType = $rules['control'] ?? self::inferControlType($validationType);

        if (!self::isKnownControlType($controlType)) {
            throw ElementDefinitionException::unknownControlType($key, $controlType);
        }

        $options = $rules['values'] ?? $rules['options'] ?? null;

        if ($controlType === self::SELECT && (!is_array($options) || $options === [])) {
            throw ElementDefinitionException::invalidSelectOptions($key);
        }

        return new self(
            key: $key,
            controlType: $controlType,
            validationType: $validationType,
            label: $rules['label'] ?? null,
            default: $rules['default'] ?? null,
            required: (bool) ($rules['required'] ?? false),
            max: $rules['max'] ?? null,
            options: $options,
        );
    }

    public static function isKnownControlType(string $controlType): bool
    {
        return in_array($controlType, self::V1_CONTROL_TYPES, true);
    }

    private static function inferControlType(string $validationType): string
    {
        return match ($validationType) {
            'boolean' => self::TOGGLE,
            'enum' => self::SELECT,
            'integer' => self::NUMBER,
            default => self::TEXT,
        };
    }
}
