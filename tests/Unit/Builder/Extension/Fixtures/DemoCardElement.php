<?php

namespace Tests\Unit\Builder\Extension\Fixtures;

use App\Builder\Contracts\ElementInterface;
use App\Builder\Contracts\HasAssets;
use SaaSPlatform\ThemeRuntime\Security\HtmlSanitizer;

/**
 * Phase 3E-G test-only fixture — a non-container synthetic extension block
 * (`ext:acme:demo_card`) proving the V1 registration path end-to-end,
 * including that an extension renderer participates in the SAME canonical
 * sanitization authority as the built-in Element SDK (3E-C) — never a
 * local/duplicate sanitizer.
 *
 * Deliberately does NOT extend App\Builder\Elements\BaseElement — proving
 * the corrected V1 contract (Phase 3E-G correction): ElementInterface now
 * formally declares getRequiredModule()/getDefaultSize() (implemented
 * directly below, since they are universal metadata, not an opt-in
 * capability), and this fixture implements NOTHING beyond what
 * ElementInterface + the optional HasAssets capability it actually uses
 * require — no supportsDynamicData() implementation, because
 * ElementRenderer now correctly gates that call behind
 * is_subclass_of(HasDynamicData::class), which this class does not
 * implement (it has no dynamic-data capability, and needs none).
 */
final class DemoCardElement implements ElementInterface, HasAssets
{
    public static function getRequiredModule(): ?string
    {
        return null;
    }

    public static function getDefaultSize(): string
    {
        return 'full';
    }

    public static function getType(): string
    {
        return 'ext:acme:demo_card';
    }

    public static function getName(): string
    {
        return 'Acme Demo Card';
    }

    public static function getIcon(): string
    {
        return '🃏';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getDefaultProps(): array
    {
        return ['title' => '', 'content' => ''];
    }

    public static function getSchema(): array
    {
        return [
            'title' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 200],
            'content' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 5000, 'control' => 'textarea'],
        ];
    }

    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        $title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $content = HtmlSanitizer::sanitizeRichHtml((string) ($props['content'] ?? ''));

        return "<div id=\"tb-{$id}\" class=\"ext-acme-demo-card\"><h3>{$title}</h3><div class=\"ext-acme-demo-card-content\">{$content}</div></div>";
    }

    public static function getAssets(): array
    {
        return ['css' => ['/vendor/acme/demo-card.css'], 'js' => []];
    }
}
