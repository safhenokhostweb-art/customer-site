<?php

namespace Tests\Unit\Builder\Extension\Fixtures;

use App\Builder\Contracts\ElementInterface;

/**
 * Deliberately does NOT implement HasAssets — this is the mandatory
 * acceptance scenario fixture: an ext:* renderer whose OWN class declares
 * zero assets, proving manifest-declared CSS/JS (fed in by the host
 * BlockRenderer via ElementRenderer::setExtensionAssetMap()) is emitted
 * independently of HasAssets, not merely as a duplicate reporting path for
 * it.
 */
final class DemoManifestOnlyElement implements ElementInterface
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
        return 'ext:e2etest:gallery';
    }

    public static function getName(): string
    {
        return 'E2E Test Gallery (manifest-asset-only)';
    }

    public static function getIcon(): string
    {
        return '🖼️';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getDefaultProps(): array
    {
        return ['title' => ''];
    }

    public static function getSchema(): array
    {
        return [
            'title' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 200],
        ];
    }

    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        $title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES, 'UTF-8');

        return "<div id=\"tb-{$id}\" class=\"ext-e2etest-gallery\">{$title}</div>";
    }
}
