<?php

namespace Tests\Unit\Builder\Extension\Fixtures;

use App\Builder\Contracts\ElementInterface;
use App\Builder\Contracts\HasContainerCapability;

/**
 * Phase 3E-G test-only fixture — a container-capable synthetic extension
 * block (`ext:acme:demo_container`), proving container metadata
 * (is_container/allowed_children, 3E-D) and ownership classification
 * ("extension", 3E-F) both survive the full V1 registration path exactly
 * like a host-only or canonical-adapter element's would.
 */
final class DemoContainerElement implements ElementInterface, HasContainerCapability
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
        return 'ext:acme:demo_container';
    }

    public static function getName(): string
    {
        return 'Acme Demo Container';
    }

    public static function getIcon(): string
    {
        return '📦';
    }

    public static function getCategory(): string
    {
        return 'layout';
    }

    public static function getDefaultProps(): array
    {
        return [];
    }

    public static function getSchema(): array
    {
        return [];
    }

    public static function render(array $props, array $context = []): string
    {
        $id = $context['id'] ?? '';
        $inner = $context['inner'] ?? '';

        return "<div id=\"tb-{$id}\" class=\"ext-acme-demo-container\">{$inner}</div>";
    }

    public static function isContainer(): bool
    {
        return true;
    }

    public static function getAllowedChildren(): ?array
    {
        return null;
    }
}
