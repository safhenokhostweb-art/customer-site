<?php

namespace App\Builder\Definition;

use App\Builder\Contracts\ElementInterface;
use App\Builder\Contracts\HasAssets;
use App\Builder\Contracts\HasContainerCapability;
use App\Builder\Contracts\HasDynamicData;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeIdentity;
use InvalidArgumentException;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalBlockRegistry;

/**
 * Phase 3E-D — the declarative description of one Builder element type:
 * persisted type, editor metadata, and control schema. Deliberately carries
 * NO rendering logic (render() stays on the element class itself, invoked
 * through the existing ElementRenderer pipeline) and is never consulted by
 * ThemeCustomizationDiff/ThemeUpgradeReconciler (reconciliation stays
 * type-blind, operating on serialized data alone).
 *
 * fromElementClass() is pure introspection over the EXISTING
 * ElementInterface contract (+ the already-established optional
 * HasAssets/HasDynamicData/HasContainerCapability interfaces) — it changes
 * no element's own render()/getSchema()/etc. Every one of the current 13
 * registered elements produces a definition without any class needing to
 * change, except the 6 that now also implement HasContainerCapability to
 * declare their existing (unchanged) container status explicitly instead
 * of relying on a hardcoded external list.
 *
 * Phase 3E-F — `ownership` gives every definition ONE deterministic
 * classification (canonical adapter / host-only / extension), DERIVED from
 * the existing authoritative sources (canonical BlockRegistry's own type
 * map, and the `ext:` namespace convention) rather than stored as a
 * separately-maintained flag anywhere — there is nothing new to keep in
 * sync, and no fourth registry. See ElementRegistry::ownershipOf().
 */
final class ThemeBuilderElementDefinition
{
    public const OWNERSHIP_CANONICAL_ADAPTER = 'canonical_adapter';
    public const OWNERSHIP_HOST_ONLY = 'host_only';
    public const OWNERSHIP_EXTENSION = 'extension';

    /** @param array<string, ThemeBuilderControlDefinition> $controls keyed by control key */
    private function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $category,
        public readonly string $icon,
        public readonly array $controls,
        public readonly bool $isContainer,
        public readonly ?array $allowedChildren,
        public readonly bool $supportsDynamicData,
        public readonly bool $hasAssets,
        public readonly string $ownership,
    ) {
    }

    public static function fromElementClass(string $class): self
    {
        if (!is_subclass_of($class, ElementInterface::class)) {
            throw new InvalidArgumentException("Class must implement ElementInterface: {$class}");
        }

        $controls = [];
        foreach ($class::getSchema() as $key => $rules) {
            $controls[] = ThemeBuilderControlDefinition::fromSchemaField((string) $key, $rules);
        }

        $isContainerCapable = is_subclass_of($class, HasContainerCapability::class);

        return self::fromControls(
            type: $class::getType(),
            label: $class::getName(),
            category: $class::getCategory(),
            icon: $class::getIcon(),
            controls: $controls,
            isContainer: $isContainerCapable && $class::isContainer(),
            allowedChildren: $isContainerCapable ? $class::getAllowedChildren() : null,
            supportsDynamicData: is_subclass_of($class, HasDynamicData::class) && $class::supportsDynamicData(),
            hasAssets: is_subclass_of($class, HasAssets::class),
        );
    }

    /** @param ThemeBuilderControlDefinition[] $controls */
    public static function fromControls(
        string $type,
        string $label,
        string $category,
        string $icon,
        array $controls,
        bool $isContainer = false,
        ?array $allowedChildren = null,
        bool $supportsDynamicData = false,
        bool $hasAssets = false,
    ): self {
        $keyed = [];
        foreach ($controls as $control) {
            if (!$control instanceof ThemeBuilderControlDefinition) {
                throw new InvalidArgumentException('Every control must be a ThemeBuilderControlDefinition instance.');
            }
            if (array_key_exists($control->key, $keyed)) {
                throw ElementDefinitionException::duplicateControlKey($control->key);
            }
            $keyed[$control->key] = $control;
        }

        return new self($type, $label, $category, $icon, $keyed, $isContainer, $allowedChildren, $supportsDynamicData, $hasAssets, self::classify($type));
    }

    /**
     * Phase 3E-F — the ONE deterministic ownership rule, a pure function of
     * the type string and the already-populated canonical registry (no
     * mutable state of its own, no registration side effect, safe to call
     * in any boot order): `ext:`-prefixed is always an extension type;
     * otherwise a type the canonical BlockRegistry itself defines is a
     * canonical adapter (10 of the 13 elements delegate their rendering to
     * that same canonical implementation); anything else registered here
     * is host-only (spacer/row/column today).
     */
    private static function classify(string $type): string
    {
        if (str_starts_with($type, ExtensionBlockTypeIdentity::NAMESPACE_PREFIX)) {
            return self::OWNERSHIP_EXTENSION;
        }

        if (CanonicalBlockRegistry::isCanonicalType($type)) {
            return self::OWNERSHIP_CANONICAL_ADAPTER;
        }

        return self::OWNERSHIP_HOST_ONLY;
    }

    /**
     * Converts back to the legacy schema-array shape ElementValidator
     * already consumes, so validation/coercion/defaults/unknown-key-
     * stripping stays byte-identical to today's behavior — not
     * reimplemented, just re-expressed from the same source data.
     */
    public function toLegacySchema(): array
    {
        $schema = [];
        foreach ($this->controls as $key => $control) {
            $rules = [
                'type' => $control->validationType,
                'required' => $control->required,
            ];
            if ($control->default !== null) {
                $rules['default'] = $control->default;
            }
            if ($control->max !== null) {
                $rules['max'] = $control->max;
            }
            if ($control->options !== null) {
                $rules['values'] = $control->options;
            }
            $schema[$key] = $rules;
        }

        return $schema;
    }
}
