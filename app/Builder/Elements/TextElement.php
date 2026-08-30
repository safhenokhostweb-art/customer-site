<?php

namespace App\Builder\Elements;

class TextElement extends BaseElement
{
    public static function getType(): string { return 'text'; }
    public static function getName(): string { return 'متن'; }
    public static function getIcon(): string { return '📄'; }
    public static function getCategory(): string { return 'content'; }
    public static function getDefaultProps(): array { return ['content' => 'متن خود را اینجا بنویسید.']; }
    public static function getSchema(): array
    {
        return ['content' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 65535]];
    }
    /** Phase 2j (ADR-030/031): delegates to the shared canonical authority. */
    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        $content = self::sanitizeHtml((string) ($props['content'] ?? ''));
        return app(\SaaSPlatform\ThemeRuntime\BlockRenderer::class)
            ->dispatchStructural('text', $props, $context['inner'] ?? '', $id)
            ?? "<div id=\"tb-{$id}\" class=\"tb-text prose\">{$content}</div>";
    }
}
