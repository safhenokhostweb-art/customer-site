<?php

namespace App\Services\ThemeCanvas;

use RuntimeException;

/**
 * Thrown by ThemeCustomizationDiff when a snapshot is invalid input — today
 * that is exactly one case: a block id that appears more than once within a
 * single snapshot (header/footer/any page, at any nesting depth). Block
 * identity (Phase 3D-B) is the entire foundation matching is built on, so a
 * duplicate is never silently resolved by picking one occurrence — it is a
 * deterministic, typed failure.
 */
final class ThemeCustomizationDiffException extends RuntimeException
{
    public static function duplicateBlockId(string $snapshotLabel, string $id): self
    {
        return new self(
            "Duplicate block id \"{$id}\" found in the {$snapshotLabel} snapshot — "
            . 'block identity must be unique within a single Theme snapshot.'
        );
    }
}
