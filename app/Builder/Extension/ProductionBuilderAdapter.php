<?php

namespace App\Builder\Extension;

use App\Builder\Definition\ThemeBuilderControlDefinition;
use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\ElementRegistry;

/**
 * Phase 3E-H-B — the pure, stateless read-model bridging registered
 * Extension SDK definitions into the two production Builder read paths
 * (the authoring palette, `config('builder-elements.elements')`, and the
 * render-time validation gate, `config('builder-element-schemas.elements')`).
 *
 * This is deliberately NOT a registry: it owns no state of its own and
 * mutates nothing. It is a transformation over the already-committed
 * `ElementRegistry::definitions()` result, computed fresh on every call.
 * Both production surfaces call it, so a package that finishes atomic
 * registration (3E-G) becomes visible through both, or neither, together —
 * there is no separate mutable palette/schema store to fall out of sync.
 *
 * EXTENSION-ONLY by design (§4 of the 3E-H-B task): only definitions whose
 * `ownership` is `OWNERSHIP_EXTENSION` are surfaced here. The 10 canonical-
 * delegating and 3 host-only built-in `App\Builder\Elements\*` types are
 * deliberately excluded — they already have their own, richer, hand-authored
 * entries in `config/builder-elements.php`/`config/builder-element-schemas.php`,
 * and merging `ElementRegistry`'s definitions for them would risk shadowing
 * or double-defining 12 of the 13 with a strictly poorer (auto-generated)
 * representation. Only genuinely new `ext:*` identities — which by
 * construction (3E-B) can never collide with an existing bare production
 * key — are ever added.
 */
final class ProductionBuilderAdapter
{
    /**
     * Production palette entries for every registered extension definition,
     * in the exact per-type shape `config('builder-elements.elements')`
     * itself uses: name/icon/category/required_module/defaultProps/schema.
     *
     * @return array<string, array{name:string,icon:string,category:string,required_module:?string,defaultProps:array,schema:array}>
     */
    public static function palette(): array
    {
        $out = [];
        foreach (ElementRegistry::definitions() as $type => $definition) {
            if ($definition->ownership !== ThemeBuilderElementDefinition::OWNERSHIP_EXTENSION) {
                continue;
            }

            $defaultProps = [];
            $schema = [];
            foreach ($definition->controls as $key => $control) {
                $defaultProps[$key] = $control->default;
                $schema[$key] = [
                    'label' => $control->label ?? $key,
                    'type' => self::editorControlType($control),
                    'default' => $control->default,
                ];
                if ($control->options !== null) {
                    $schema[$key]['options'] = $control->options;
                }
            }

            $out[$type] = [
                'name' => $definition->label,
                'icon' => $definition->icon,
                'category' => $definition->category,
                // Registration alone never grants entitlement/module access
                // (3E-E) — an extension palette entry is deliberately never
                // module-gated by this adapter; that remains a separate,
                // not-yet-existing concern, never invented here.
                'required_module' => null,
                'defaultProps' => $defaultProps,
                'schema' => $schema,
            ];
        }

        return $out;
    }

    /**
     * Render-time schema-gate entries for every registered extension
     * definition, in the exact shape `ElementSchemaRegistry`/
     * `config('builder-element-schemas.elements')` require:
     * type/props/styles/children.
     *
     * @return array<string, array{type:string,props:array,styles:array,children:string}>
     */
    public static function schemas(): array
    {
        $out = [];
        foreach (ElementRegistry::definitions() as $type => $definition) {
            if ($definition->ownership !== ThemeBuilderElementDefinition::OWNERSHIP_EXTENSION) {
                continue;
            }

            $props = [];
            foreach ($definition->controls as $key => $control) {
                $props[$key] = [
                    'required' => $control->required,
                    'type' => self::schemaValidationType($control),
                ];
            }

            $out[$type] = [
                'type' => $type,
                'props' => $props,
                // Not read by ElementSchemaRegistry::validate() today (only
                // declared/descriptive for every existing type too) — kept
                // for shape parity with existing entries.
                'styles' => ['layout', 'typography', 'background', 'effects'],
                // Reflects the definition's own declared is_container
                // honestly. This governs recursive VALIDATION of nested
                // blocks only — it does NOT make BlockRenderer::
                // renderBlockUnsafe()'s separate, untouched, hardcoded
                // container-type list render this type's children; that
                // remains an open integration gap, tracked for 3E-H-C.
                'children' => $definition->isContainer ? 'blocks' : 'none',
            ];
        }

        return $out;
    }

    /**
     * Maps the V1 control vocabulary (3E-D) to the closest production
     * palette editor-widget type actually in use (verified against every
     * distinct 'type' value in config/builder-elements.php: string, select,
     * color, richtext, image, textarea, boolean, number, info). Production
     * has no dedicated 'url'/'spacing'/'responsive' widget — these degrade
     * to the closest faithful representation (never silently weakening the
     * underlying VALUE validation, which is driven by schemaValidationType()
     * below, not by this cosmetic mapping).
     */
    private static function editorControlType(ThemeBuilderControlDefinition $control): string
    {
        return match ($control->controlType) {
            ThemeBuilderControlDefinition::TEXT => 'string',
            ThemeBuilderControlDefinition::TEXTAREA => 'textarea',
            ThemeBuilderControlDefinition::NUMBER => 'number',
            ThemeBuilderControlDefinition::TOGGLE => 'boolean',
            ThemeBuilderControlDefinition::SELECT => 'select',
            ThemeBuilderControlDefinition::COLOR => 'color',
            ThemeBuilderControlDefinition::MEDIA => 'image',
            ThemeBuilderControlDefinition::URL => 'string', // production has no dedicated url widget; matches how existing url-bearing fields (e.g. button url) are already represented
            ThemeBuilderControlDefinition::SPACING => $control->options !== null ? 'select' : 'string', // no 'spacing' widget in production; degrades to the closest faithful representative of its actual value constraint
            ThemeBuilderControlDefinition::RESPONSIVE => 'string', // no per-breakpoint widget in production; value-shape correctness is preserved via schemaValidationType(), not this cosmetic mapping
            default => 'string',
        };
    }

    /**
     * Maps the 3E-D validation-type vocabulary (string/integer/boolean/
     * enum/array) to the narrower set config('builder-element-schemas.php')
     * actually enforces (string/integer/boolean/array — confirmed via
     * ElementSchemaRegistry::assertType(), whose default branch already
     * passes anything outside that set, so 'enum' safely degrades to a
     * permissive pass at THIS gate without weakening anything — the real
     * enum-membership check still happens downstream in ElementValidator,
     * unchanged, exactly as it already does for every existing type today).
     */
    private static function schemaValidationType(ThemeBuilderControlDefinition $control): string
    {
        return match ($control->validationType) {
            'string', 'integer', 'boolean', 'array' => $control->validationType,
            default => 'string',
        };
    }
}
