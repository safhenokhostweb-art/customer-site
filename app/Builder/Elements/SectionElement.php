<?php

namespace App\Builder\Elements;

class SectionElement extends BaseElement
{
    public static function getType(): string { return 'section'; }
    public static function getName(): string { return 'بخش'; }
    public static function getIcon(): string { return '▣'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getDefaultProps(): array { return ['padding' => 'md', 'background' => 'transparent', 'container' => true]; }
    public static function getSchema(): array
    {
        return [
            'padding' => ['type' => 'enum', 'values' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'], 'default' => 'md'],
            'background' => ['type' => 'string', 'required' => false, 'default' => 'transparent', 'max' => 100],
            'container' => ['type' => 'boolean', 'required' => false, 'default' => true],
            'background_image' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 2048],
        ];
    }
    /**
     * Phase 2j (ADR-030/031): delegates to the shared canonical authority,
     * which now absorbs this element's own _style.background/layout/effects
     * capability (identical logic, promoted rather than duplicated).
     */
    public static function render(array $props, array $context = []): string
    {
        $padding = self::spacingClass($props['padding'] ?? 'md');
        $useContainer = (bool)($props['container'] ?? true);
        $inner = $context['inner'] ?? '';
        $id = $context['id'] ?? '';
        $class = "tb-section tb-padding-{$padding}";
        $open = $useContainer ? '<div class="tb-container">' : '';
        $close = $useContainer ? '</div>' : '';
        $fallback = "<section id=\"tb-{$id}\" class=\"{$class}\"><div class=\"tb-section-inner\">{$open}{$inner}{$close}</div></section>";
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('section', $props, $inner, $id)
            ?? $fallback;
    }
}
