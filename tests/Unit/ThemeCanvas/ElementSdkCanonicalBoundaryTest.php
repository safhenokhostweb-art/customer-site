<?php

namespace Tests\Unit\ThemeCanvas;

use App\Builder\ElementRegistry;
use SaaSPlatform\ThemeRuntime\BlockRenderer as CanonicalBlockRenderer;
use Tests\TestCase;

/**
 * Phase 2i found: the Element SDK (App\Builder\ElementRegistry +
 * ElementRenderer, populated from config('builder.elements')) is checked
 * BEFORE render*() dispatch in BlockRenderer::renderBlockUnsafe(), and 10
 * registered Element classes (heading, text, image, button, divider,
 * section, container, grid, columns, hero) collide with canonical shared
 * BlockRenderer method names — meaning, for these types, the Element SDK
 * was the real live renderer, entirely bypassing the shared canonical
 * authority (an undissolved second authority, Phase 2i's largest reported
 * remaining gap).
 *
 * Phase 2j resolves this WITHOUT removing the Element SDK from the dispatch
 * path (its schema/validation/dynamic-data/asset-collection pipeline is
 * real and valuable — see Section 3 of the phase spec) and WITHOUT moving
 * canonical authority back into customer-site: every one of the 10
 * colliding Element classes' `render()` now delegates its structural
 * markup to `SaaSPlatform\ThemeRuntime\BlockRenderer::dispatchStructural()`,
 * falling back to its own minimal markup only if canonical has no matching
 * method. The Element SDK still runs first in dispatch order — that
 * ordering is no longer a problem because it no longer produces a SECOND,
 * independent implementation of canonical structure.
 *
 * This test enforces both directions: (1) the known set of Element/
 * canonical name collisions is exactly this set — a NEW collision must be
 * investigated and either delegate or be justified, never silently added;
 * (2) every currently-known collision's Element class source literally
 * calls dispatchStructural() — a future edit that quietly reverts one back
 * to an independent implementation fails loudly.
 *
 * A justified-independent type is the narrow second resolution the first
 * rule allows: an Element that must keep its own renderer because the
 * canonical one cannot express its authoring model. Each entry carries a
 * non-empty reason and must genuinely stay independent (tested in both
 * directions) — it is a documented decision, not a hole in the guard.
 */
class ElementSdkCanonicalBoundaryTest extends TestCase
{
    /**
     * Known collisions between the Element SDK's registered types and
     * canonical shared BlockRenderer method names. Every one is resolved
     * via delegation (see class docblock) — this is not an allowlist of
     * "acceptable duplication."
     */
    private const KNOWN_COLLISIONS = [
        'heading' => \App\Builder\Elements\HeadingElement::class,
        'text' => \App\Builder\Elements\TextElement::class,
        'image' => \App\Builder\Elements\ImageElement::class,
        'button' => \App\Builder\Elements\ButtonElement::class,
        'divider' => \App\Builder\Elements\DividerElement::class,
        'section' => \App\Builder\Elements\SectionElement::class,
        'container' => \App\Builder\Elements\ContainerElement::class,
        'grid' => \App\Builder\Elements\GridElement::class,
        'columns' => \App\Builder\Elements\ColumnsElement::class,
        'hero' => \App\Builder\Elements\HeroElement::class,
    ];

    /**
     * Collisions that intentionally stay host-owned, each with the reason
     * delegation is not possible.
     */
    private const JUSTIFIED_INDEPENDENT = [
        // Canonical renderSpacer() reads a numeric pixel height and clamps
        // it to 0–600px. The host spacer's authoring model is named sizes
        // (none/xs/sm/md/lg/xl), a custom px height up to 2000 and a vh
        // unit — none of which the canonical method can express. Delegating
        // would silently drop vh and clamp large custom heights, so the
        // host keeps its own renderer until the canonical spacer gains that
        // model.
        'spacer' => 'Named sizes, custom px up to 2000 and vh units — the canonical 0–600px numeric model cannot express them.',
    ];

    public function test_element_sdk_canonical_collisions_are_exactly_the_known_set(): void
    {
        $canonical = new \ReflectionClass(CanonicalBlockRenderer::class);
        $canonicalTypes = [];
        foreach ($canonical->getMethods() as $m) {
            if ($m->getDeclaringClass()->getName() !== $canonical->getName()) {
                continue;
            }
            if (!str_starts_with($m->getName(), 'render') || in_array($m->getName(), ['renderBlocks', 'renderBlock'], true)) {
                continue;
            }
            $type = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($m->getName(), 6)));
            $canonicalTypes[$type] = true;
        }

        $registeredInSdk = array_keys(ElementRegistry::all());
        $collisions = array_values(array_intersect($registeredInSdk, array_keys($canonicalTypes)));
        sort($collisions);

        $expected = array_merge(array_keys(self::KNOWN_COLLISIONS), array_keys(self::JUSTIFIED_INDEPENDENT));
        sort($expected);

        $newCollisions = array_diff($collisions, $expected);
        $this->assertEmpty(
            $newCollisions,
            'A NEW type is registered in the Element SDK that also has a canonical shared renderer, '
            . 'beyond the known/resolved set: ' . implode(', ', $newCollisions)
            . '. Give its render() a dispatchStructural() delegation call (see the other 10 Element '
            . 'classes for the pattern) before registering it, or add it to JUSTIFIED_INDEPENDENT with '
            . 'a concrete reason the canonical renderer cannot serve.'
        );
    }

    public function test_each_justified_independent_collision_is_registered_and_stays_independent(): void
    {
        foreach (self::JUSTIFIED_INDEPENDENT as $type => $reason) {
            $this->assertTrue(ElementRegistry::has($type), "Justified type \"{$type}\" is not registered in the Element SDK.");
            $this->assertNotSame('', trim($reason), "Justified type \"{$type}\" has no documented reason.");

            $ref = new \ReflectionMethod(ElementRegistry::get($type), 'render');
            $lines = file($ref->getFileName());
            $src = implode('', array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
            $this->assertStringNotContainsString(
                'dispatchStructural(',
                $src,
                "Justified type \"{$type}\" now delegates; move it to KNOWN_COLLISIONS instead of keeping the justification."
            );
        }
    }

    public function test_every_known_collision_delegates_structural_rendering_to_canonical(): void
    {
        $undelegated = [];
        foreach (self::KNOWN_COLLISIONS as $type => $class) {
            $ref = new \ReflectionMethod($class, 'render');
            $lines = file($ref->getFileName());
            $src = implode('', array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
            if (!str_contains($src, 'dispatchStructural(')) {
                $undelegated[] = $type;
            }
        }

        $this->assertEmpty(
            $undelegated,
            'These Element classes no longer delegate to the canonical shared renderer — '
            . 'a second independent structural implementation has silently returned: ' . implode(', ', $undelegated)
        );
    }

    public function test_delegated_elements_produce_the_same_markup_canonical_produces_directly(): void
    {
        $canonicalRenderer = app(CanonicalBlockRenderer::class);

        $cases = [
            'heading' => [['level' => 2, 'text' => 'تست عنوان'], 'heading', ['level' => 2, 'text' => 'تست عنوان']],
            'divider' => [[], 'divider', []],
            'button' => [['text' => 'کلیک', 'url' => 'https://example.com', 'style' => 'primary'], 'button', ['text' => 'کلیک', 'url' => 'https://example.com', 'style' => 'primary']],
        ];

        foreach ($cases as $type => [$elementProps, $canonicalType, $canonicalProps]) {
            $class = self::KNOWN_COLLISIONS[$type];
            $id = 'parity-' . $type;
            $elementHtml = $class::render($elementProps, ['id' => $id, 'inner' => '']);
            $canonicalHtml = $canonicalRenderer->dispatchStructural($canonicalType, $canonicalProps, '', $id);

            $this->assertSame($canonicalHtml, $elementHtml, "Element SDK output for '{$type}' diverged from canonical direct output.");
        }
    }
}
