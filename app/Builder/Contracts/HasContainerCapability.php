<?php

namespace App\Builder\Contracts;

/**
 * Optional: element declares whether it may contain children, and
 * optionally which child types are allowed. Matches the same optional-
 * interface pattern already established by HasAssets/HasDynamicData/
 * HasValidation — absence means "not a container" (the same safe default
 * ElementRenderer already applies to the other optional interfaces).
 *
 * Phase 3E-D: this DECLARES capability only. The Builder host enforces
 * nesting; an element must never manipulate the block tree itself, and
 * declaring this interface changes no rendering or persistence behavior.
 */
interface HasContainerCapability
{
    public static function isContainer(): bool;

    /**
     * @return string[]|null Allowed child element types, or null for "any type" (still subject to isContainer()).
     */
    public static function getAllowedChildren(): ?array;
}
