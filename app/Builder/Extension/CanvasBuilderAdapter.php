<?php

namespace App\Builder\Extension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\ElementRegistry;

/**
 * Phase 3F-C — the Canvas-authoring sibling of ProductionBuilderAdapter: a
 * pure, stateless read-model bridging registered Extension SDK definitions
 * into `App\Services\ThemeCanvas\BlockRegistry`'s palette/schema shape
 * (name/category/schema/defaultProps), so the existing Canvas nested-tree
 * editor (customer-site and, through the same shared class, saas-platform's
 * own Canvas — see BlockRegistry usage) can author `ext:*` blocks without a
 * second registry or a second control-schema format.
 *
 * EXTENSION-ONLY by design, identical reasoning to ProductionBuilderAdapter:
 * only definitions whose `ownership` is OWNERSHIP_EXTENSION are surfaced —
 * every built-in/canonical/host-only type already has its own hand-authored
 * entry in ThemeCanvas\BlockRegistry, and this must never shadow one (by
 * construction, 3E-B, an `ext:*` identity can never collide with a bare
 * key anyway).
 *
 * Deliberately reuses ProductionBuilderAdapter::editorControlType() rather
 * than carrying its own copy of the V1-control → editor-widget mapping —
 * both production surfaces target the exact same widget vocabulary
 * (string/textarea/number/boolean/select/color/image), so there is exactly
 * one place that mapping can ever drift.
 */
final class CanvasBuilderAdapter
{
    /**
     * Canvas palette entries for every registered extension definition, in
     * the exact per-type shape `App\Services\ThemeCanvas\BlockRegistry::all()`
     * itself uses: name/category/schema/defaultProps.
     *
     * @return array<string, array{name:string,category:string,schema:array,defaultProps:array}>
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
                    'type' => ProductionBuilderAdapter::editorControlType($control),
                    'label' => $control->label ?? $key,
                    'default' => $control->default,
                ];
                if ($control->options !== null) {
                    $schema[$key]['options'] = $control->options;
                }
            }

            $out[$type] = [
                'name' => $definition->label,
                'category' => $definition->category,
                'schema' => $schema,
                'defaultProps' => $defaultProps,
            ];
        }

        return $out;
    }
}
