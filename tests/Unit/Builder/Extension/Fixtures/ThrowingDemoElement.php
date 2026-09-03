<?php

namespace Tests\Unit\Builder\Extension\Fixtures;

use App\Builder\Contracts\ElementInterface;

/**
 * Phase 3E-H-C test-only fixture — an extension element whose render()
 * always throws, used to prove the existing renderer-failure safety net
 * (BlockRenderer::renderBlock()'s try/catch → renderPlaceholder('render_error'))
 * already covers a REGISTERED extension type that misbehaves, distinctly
 * from an unregistered/unavailable one.
 */
final class ThrowingDemoElement implements ElementInterface
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
        return 'ext:acme:demo_throwing';
    }

    public static function getName(): string
    {
        return 'Acme Demo Throwing';
    }

    public static function getIcon(): string
    {
        return '💥';
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

    public static function render(array $props, array $context = []): string
    {
        throw new \RuntimeException('Acme demo throwing element always fails.');
    }
}
