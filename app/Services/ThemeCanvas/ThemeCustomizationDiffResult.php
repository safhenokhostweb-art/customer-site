<?php

namespace App\Services\ThemeCanvas;

/**
 * Output of ThemeCustomizationDiff::diff() — facts only, no interpretation.
 * Each section is a plain, deterministically-ordered list of associative
 * arrays; an unchanged item (a block/page identical between the two
 * snapshots, or a global_styles/settings leaf with an identical value) is
 * simply absent, not listed with an "UNCHANGED" marker — an empty section
 * means exactly what it says.
 *
 * blocks: one entry per block whose id exists in either snapshot and is not
 *   identical between them. Shape:
 *   [
 *     'id' => string,
 *     'classification' => string[] (one or more of ADDED, DELETED,
 *       MODIFIED, MOVED, TYPE_CHANGED — never more than one of ADDED/
 *       DELETED, which are mutually exclusive with everything else),
 *     'base' => ['type','region','page_slug','parent_id','position','payload'] | absent for ADDED,
 *     'target' => same shape | absent for DELETED,
 *     'property_changes' => dot-path => ['change_type','base_value','target_value'] | present only when MODIFIED,
 *     'move' => ['base_parent','target_parent','base_region','target_region','base_page_slug','target_page_slug','base_position','target_position'] | present only when MOVED,
 *     'type_change' => ['base_type','target_type'] | present only when TYPE_CHANGED,
 *   ]
 *
 * pages: one entry per page slug that differs. Shape:
 *   ['slug' => string, 'classification' => ['ADDED'|'REMOVED'|'MODIFIED'],
 *    'base'|'target' => page metadata (title etc., blocks excluded — those
 *    are covered by `blocks`) | present per classification,
 *    'property_changes' => dot-path diff | present only when MODIFIED]
 *
 * globalStyles / settings: flat dot-path => ['change_type','base_value','target_value'].
 */
final class ThemeCustomizationDiffResult
{
    public function __construct(
        public readonly array $blocks,
        public readonly array $pages,
        public readonly array $globalStyles,
        public readonly array $settings,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->blocks === []
            && $this->pages === []
            && $this->globalStyles === []
            && $this->settings === [];
    }
}
