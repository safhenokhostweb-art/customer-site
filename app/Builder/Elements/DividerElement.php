<?php

namespace App\Builder\Elements;

/**
 * Horizontal divider.
 */
class DividerElement extends BaseElement
{
    public static function getType(): string
    {
        return 'divider';
    }

    public static function getName(): string
    {
        return 'جداکننده';
    }

    public static function getIcon(): string
    {
        return '➖';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getDefaultProps(): array
    {
        return [];
    }

    public static function getSchema(): array
    {
        return [];
    }

    /**
     * Phase 2j (ADR-030/031): delegates structural markup to the shared
     * canonical ThemeRuntime authority instead of maintaining a second,
     * independent HTML implementation. This element's own responsibility
     * is now purely schema/registration/editor metadata.
     */
    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('divider', $props, $context['inner'] ?? '', $id)
            ?? "<hr id=\"tb-{$id}\" class=\"tb-divider\">";
    }
}
