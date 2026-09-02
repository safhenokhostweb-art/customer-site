<?php

namespace App\Builder\Elements;

use App\Builder\Contracts\HasContainerCapability;

class ContainerElement extends BaseElement implements HasContainerCapability
{
    public static function getType(): string { return 'container'; }
    public static function getName(): string { return 'کانتینر'; }
    public static function getIcon(): string { return '▦'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['maxWidth' => '7xl']; }
    public static function getSchema(): array { return ['maxWidth' => ['type' => 'string', 'required' => false, 'default' => '7xl', 'max' => 20]]; }

    /** Phase 3E-D: generic wrapper — no child-type restriction. */
    public static function isContainer(): bool { return true; }
    public static function getAllowedChildren(): ?array { return null; }
    /** Phase 2j (ADR-030/031): delegates to the shared canonical authority. */
    public static function render(array $props, array $context = []): string
    {
        $inner = $context['inner'] ?? ''; $id = $context['id'] ?? '';
        $max = self::escapeAttr($props['maxWidth'] ?? '7xl');
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('container', $props, $inner, $id)
            ?? "<div id=\"tb-{$id}\" class=\"tb-container tb-max-{$max}\">{$inner}</div>";
    }
}
