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
    public static function render(array $props, array $context = []): string
    {
        $padding = self::spacingClass($props['padding'] ?? 'md');
        $bg = self::escapeAttr($props['background'] ?? 'transparent');
        $bgImg = self::escapeAttr($props['background_image'] ?? '');
        $useContainer = (bool)($props['container'] ?? true);
        $inner = $context['inner'] ?? '';
        $id = $context['id'] ?? '';
        $inlineStyles = [];
        if ($bgImg) {
            $inlineStyles[] = "background-image: url({$bgImg})";
            $inlineStyles[] = "background-size: cover";
        }
        $customBg = $props['_style']['background']['color'] ?? null;
        if ($customBg) $inlineStyles[] = "background-color:" . self::escapeAttr($customBg);
        $class = "tb-section tb-padding-{$padding}";
        if ($bg !== 'transparent' && !str_starts_with($bg, '#')) $class .= " tb-bg-{$bg}";
        if ($bg !== 'transparent' && str_starts_with($bg, '#')) $inlineStyles[] = "background-color:{$bg}";
        $layoutStyle = $props['_style']['layout'] ?? [];
        $effectsStyle = $props['_style']['effects'] ?? [];
        if (in_array($layoutStyle['radius'] ?? null, ['sm', 'md', 'lg'], true)) $class .= " tb-radius-" . $layoutStyle['radius'];
        if (in_array($layoutStyle['shadow'] ?? null, ['sm', 'md', 'lg'], true)) $class .= " tb-shadow-" . $layoutStyle['shadow'];
        if (($effectsStyle['hover'] ?? null) === 'lift') $class .= " tb-hover-lift";
        $styleAttr = $inlineStyles ? ' style="' . implode(';', $inlineStyles) . '"' : '';
        $open = $useContainer ? '<div class="tb-container">' : '';
        $close = $useContainer ? '</div>' : '';
        return "<section id=\"tb-{$id}\" class=\"{$class}\"{$styleAttr}><div class=\"tb-section-inner\">{$open}{$inner}{$close}</div></section>";
    }
}
