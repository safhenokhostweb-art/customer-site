<?php

namespace App\Builder\Elements;

class HeadingElement extends BaseElement
{
    public static function getType(): string { return 'heading'; }
    public static function getName(): string { return 'عنوان'; }
    public static function getIcon(): string { return '📝'; }
    public static function getCategory(): string { return 'content'; }
    public static function getDefaultProps(): array { return ['level' => 2, 'text' => 'عنوان']; }
    public static function getSchema(): array
    {
        return [
            'text' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 2000],
            'level' => ['type' => 'enum', 'values' => [1, 2, 3, 4, 5, 6], 'default' => 2],
        ];
    }
    /** Phase 2j (ADR-030/031): delegates to the shared canonical authority. */
    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        $level = max(1, min(6, (int)($props['level'] ?? 2)));
        $text = self::escape((string)($props['text'] ?? ''));
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('heading', $props, $context['inner'] ?? '', $id)
            ?? "<h{$level} id=\"tb-{$id}\" class=\"tb-heading\">{$text}</h{$level}>";
    }
}
