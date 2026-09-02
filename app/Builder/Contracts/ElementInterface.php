<?php

namespace App\Builder\Contracts;

/**
 * Contract for every page builder element.
 * All elements must be self-contained and implement this interface.
 *
 * Phase 3E-G (correction): getRequiredModule()/getDefaultSize() were found
 * to be called unconditionally by ElementRegistry::all() for EVERY
 * registered element — undeclared here until now, safe only because every
 * built-in element happened to extend App\Builder\Elements\BaseElement,
 * which supplies both. They are universal editor/availability metadata,
 * not an opt-in capability (every element has SOME answer, even if
 * "none"/"full"), so they belong in this contract exactly like
 * getIcon()/getCategory() already do — a standalone renderer that does
 * NOT extend BaseElement must now implement them directly, and
 * ElementRenderer/ElementRegistry never again call a method this
 * interface does not guarantee.
 *
 * supportsDynamicData() was NOT added here, deliberately: it is already
 * the exact concept the optional App\Builder\Contracts\HasDynamicData
 * interface exists for, and ElementRenderer's unconditional call to it was
 * itself the bug, now fixed there (gated by is_subclass_of(HasDynamicData)
 * like every other optional capability) rather than promoted into this
 * mandatory contract.
 */
interface ElementInterface
{
    public static function getType(): string;

    public static function getName(): string;

    public static function getIcon(): string;

    public static function getCategory(): string;

    public static function getDefaultProps(): array;

    /**
     * Property schema: [ propName => [ 'type' => string|enum|integer|boolean|array, 'required' => bool, 'default' => mixed, 'max' => int?, 'values' => array? ] ]
     */
    public static function getSchema(): array;

    /**
     * Render element HTML. Context may contain: id, inner (for containers), style, menu_html, social_links, etc.
     */
    public static function render(array $props, array $context = []): string;

    /**
     * Required module slug for this element to be available; null = always available.
     */
    public static function getRequiredModule(): ?string;

    /**
     * Editor metadata: default width/size (e.g. "full", "half").
     */
    public static function getDefaultSize(): string;
}
