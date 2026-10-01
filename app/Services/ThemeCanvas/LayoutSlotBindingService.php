<?php

namespace App\Services\ThemeCanvas;

use App\Services\BuilderLayoutService;
use App\Services\ThemeBuilder\BlockRenderer;
use SaaSPlatform\ThemeRuntime\BlockRenderer as SharedBlockRenderer;

/**
 * Layout Slot Binding — Phase 11 follow-up (Test-Layout-Validation).
 *
 * WHAT THIS PROVES
 *   Header and footer slot content is rendered as LAYOUT ELEMENTS — injected
 *   into the page chrome's <header> / <footer> tags — and never leaks into
 *   the inline content stream. The hero of the validation fixture stays an
 *   ordinary inline block.
 *
 * HOW THE BINDING WORKS
 *   1. `bindFixture()` accepts the validation fixture (see
 *      tests/Feature/ThemeCanvas/fixtures/layout-slot-validation.json) and
 *      resolves each slot's block from the canonical BlockCatalog presets —
 *      only real, registry-registered block types (nav_header / nav_footer),
 *      never fake types.
 *   2. `renderSlot()` renders a bound slot through the SAME public-mode
 *      renderer pipeline the real page uses (public renderer context, no
 *      editor hooks — the exact contract ThemeRuntimeDocument::build()
 *      establishes for MODE_PUBLIC), then wraps the output with an explicit
 *      slot marker attribute (`data-layout-slot="header|footer"`) so any
 *      consumer (browser, test, or the validation command) can verify
 *      exactly WHERE the markup landed: inside the <header>/<footer> chrome
 *      tags, not inline.
 *
 * This service deliberately does NOT touch ThemeRuntimeDocument /
 * canvas-theme.blade.php / ThemeCanvasPageController — all currently carry a
 * collaborator's uncommitted WIP. It renders through the shared renderer and
 * only ADDS the slot-marker layer, so it composes with the real page
 * pipeline without editing any WIP file.
 */
final class LayoutSlotBindingService
{
    public const SLOT_HEADER = 'header';

    public const SLOT_FOOTER = 'footer';

    /**
     * Slot → chrome tag map: where each slot's markup must land.
     */
    private const SLOT_TAGS = [
        self::SLOT_HEADER => 'header',
        self::SLOT_FOOTER => 'footer',
    ];

    /**
     * Block types that count as a chrome ROOT block for slot annotation
     * (prefix match on data-tb-block-type).
     */
    private const CHROME_BLOCK_TYPES = [
        'header', // nav_header, foodmart_header, header_modern_v1, …
        'footer', // nav_footer, foodmart_footer, …
    ];

    public function __construct(
        private readonly ThemeCanvasRenderer $renderer,
        private readonly BuilderLayoutService $layout,
    ) {}

    /**
     * Resolve the validation fixture's slots into concrete, catalog-backed
     * slot bindings.
     *
     * @return array{
     *     title: string,
     *     slots: array<string, array{slot:string, tag:string, catalog_preset:string, block:array}>,
     *     inline_content: list<array>,
     * }
     */
    public function bindFixture(array $fixture): array
    {
        $bindings = [];

        foreach ([self::SLOT_HEADER, self::SLOT_FOOTER] as $slot) {
            $spec = $fixture['slots'][$slot] ?? null;
            if (!is_array($spec)) {
                throw new \InvalidArgumentException("Fixture is missing the [{$slot}] slot definition.");
            }

            $presetKey = (string) ($spec['catalog_preset'] ?? '');
            $preset = BlockCatalog::get($presetKey);
            if ($preset === null) {
                throw new \InvalidArgumentException("Unknown BlockCatalog preset [{$presetKey}] for slot [{$slot}].");
            }

            $expectedType = (string) ($spec['block_type'] ?? '');
            if ($expectedType !== '' && $preset['block_type'] !== $expectedType) {
                throw new \InvalidArgumentException(
                    "Slot [{$slot}] expects block type [{$expectedType}] but preset [{$presetKey}] is [{$preset['block_type']}]."
                );
            }

            $bindings[$slot] = [
                'slot' => $slot,
                'tag' => self::SLOT_TAGS[$slot],
                'catalog_preset' => $presetKey,
                'block' => [
                    'id' => 'slot_' . $slot,
                    'type' => $preset['block_type'],
                    'props' => $preset['props'],
                    'children' => [],
                ],
            ];
        }

        return [
            'title' => (string) ($fixture['title'] ?? 'Test-Layout-Validation'),
            'slots' => $bindings,
            'inline_content' => array_values($fixture['inline_content'] ?? []),
        ];
    }

    /**
     * Render one bound slot as a Layout Element: public-mode renderer pass +
     * slot marker attribute, ready for injection into the chrome tag.
     */
    public function renderSlot(array $binding, array $themeData): string
    {
        $slot = $binding['slot'];
        $tag = self::SLOT_TAGS[$slot] ?? throw new \InvalidArgumentException("Unknown slot [{$slot}].");

        $html = $this->renderInPublicMode(
            fn (): string => $this->renderer->setThemeData($themeData)->renderBlock($binding['block'])
        );

        return sprintf('<%1$s data-layout-slot="%2$s">%3$s</%1$s>', $tag, $slot, $html);
    }

    /**
     * Render the fixture's inline content blocks through the same public
     * pipeline — these must stay OUT of the chrome slots.
     */
    public function renderInlineContent(array $inlineBlocks, array $themeData): string
    {
        return $this->renderInPublicMode(
            fn (): string => $this->renderer->setThemeData($themeData)->renderBlocks($inlineBlocks)
        );
    }

    /**
     * Full validation document: proves where each piece of markup landed.
     *
     * @return array<string, mixed>
     */
    public function validate(array $fixture): array
    {
        $bound = $this->bindFixture($fixture);
        $themeData = $this->layout->currentThemeData();

        $slotResults = [];
        $failures = [];

        foreach ($bound['slots'] as $slot => $binding) {
            $tag = self::SLOT_TAGS[$slot];
            $html = $this->renderSlot($binding, $themeData);

            $marker = sprintf('data-layout-slot="%s"', $slot);
            $markerOk = str_contains($html, $marker);
            $tagOk = str_starts_with($html, '<' . $tag);
            $empty = trim($html) === sprintf('<%1$s %2$s></%1$s>', $tag, $marker);

            $slotResults[$slot] = [
                'slot' => $slot,
                'tag' => $tag,
                'catalog_preset' => $binding['catalog_preset'],
                'block_type' => $binding['block']['type'],
                'rendered_into' => $tagOk ? $tag : null,
                'marker' => $marker,
                'marker_present' => $markerOk,
                'has_inner_markup' => !$empty,
                'html' => $html,
            ];

            if (!$tagOk) {
                $failures[] = "Slot [{$slot}] did not render into the <{$tag}> chrome tag.";
            }
            if (!$markerOk) {
                $failures[] = "Slot [{$slot}] is missing its data-layout-slot marker.";
            }
            if ($empty) {
                $failures[] = "Slot [{$slot}] produced no inner markup.";
            }
        }

        $inlineHtml = $this->renderInlineContent($bound['inline_content'], $themeData);
        $heroTitle = 'Test-Layout-Validation Hero';

        $leak = false;
        foreach ($slotResults as $slotResult) {
            if (str_contains($slotResult['html'], $heroTitle)) {
                $leak = true;
                $failures[] = "Inline hero leaked into the [{$slotResult['slot']}] slot.";
            }
        }

        $inline = [
            'count' => count($bound['inline_content']),
            'html' => $inlineHtml,
            'contains_hero' => str_contains($inlineHtml, $heroTitle),
            'hero_leaked_into_slots' => $leak,
        ];

        if (!$inline['contains_hero']) {
            $failures[] = 'Inline hero did not render in the content stream.';
        }
        if ($leak) {
            $failures[] = 'Inline hero leaked into a chrome slot.';
        }

        return [
            'title' => $bound['title'],
            'slots' => $slotResults,
            'inline_content' => $inline,
            'binding_ok' => $failures === [],
            'failures' => $failures,
        ];
    }

    /**
     * Inject the data-layout-slot markers into the page chrome's EXISTING
     * <header>/<footer> tags (content-preserving annotation layer).
     *
     * This is the LIVE-PAGE wiring entry point: the runtime document / blade
     * chrome already rendered the Theme's real header & footer blocks — this
     * method only ADDS the slot annotation attributes to those chrome tags,
     * so the markup stays byte-identical apart from the markers (fallback
     * safe: the flag-off path and any match failure return the HTML
     * unchanged). Idempotent: HTML that already carries a marker is
     * returned untouched.
     */
    public function decorateChrome(string $html): string
    {
        if ($html === '' || str_contains($html, 'data-layout-slot=')) {
            return $html;
        }

        foreach (self::SLOT_TAGS as $slot => $tag) {
            $html = $this->decorateChromeTag($html, $tag, $slot);
        }

        return $html;
    }

    /**
     * Chrome-tag shapes that legitimately carry a slot, tried in order:
     *
     *  1. canvas-site-* classes — canvas-theme.blade.php's own chrome
     *     wrapper tags (role=banner/contentinfo layouts).
     *  2. the chrome ROOT BLOCK the theme runtime renders — the first
     *     <header>/<footer> element whose data-tb-block-type is a real
     *     header/footer family block (foodmart_header / nav_header /
     *     foodmart_footer / nav_footer / …). This is how active themes
     *     actually emit their chrome (verified live: FoodMart renders
     *     <header data-tb-block-id=… data-tb-block-type="foodmart_header">).
     *
     * Deliberately NO bare-tag fallback: an arbitrary <header> inside some
     * widget must never receive the marker. No shape matches → unchanged.
     */
    private function decorateChromeTag(string $html, string $tag, string $slot): string
    {
        $marker = sprintf('data-layout-slot="%s"', $slot);

        $classPattern = sprintf('/<%1$s\b([^>]*class="[^"]*canvas-site-%1$s[^"]*"[^>]*)(?<!\/)>/', $tag);
        $decorated = preg_replace(
            $classPattern,
            '<' . $tag . '$1 ' . $marker . '>',
            $html,
            1,
        );
        if (is_string($decorated) && $decorated !== $html) {
            return $decorated;
        }

        // Chrome root block: <tag … data-tb-block-type="<…header/footer…>" …>
        // (class attr may appear before or after; marker appended inside the tag).
        $blockPattern = sprintf(
            '/<%1$s\b(?![^>]*data-layout-slot=)([^>]*data-tb-block-type="[^"]*(?:%2$s)[^"]*"[^>]*)(?<!\/)>/i',
            $tag,
            implode('|', array_map('preg_quote', self::CHROME_BLOCK_TYPES)),
        );
        $decorated = preg_replace(
            $blockPattern,
            '<' . $tag . '$1 ' . $marker . '>',
            $html,
            1,
        );

        return is_string($decorated) ? $decorated : $html;
    }

    /**
     * Run a render callback in the shared public runtime mode and always
     * restore the renderer's previous state — a slot render must never leak
     * mode/context changes into a surrounding request render.
     *
     * State is switched through the renderer's public API and snapshotted/
     * restored via reflection on the exact declaring classes (the host
     * redeclares $mode; the shared parent owns $context).
     */
    private function renderInPublicMode(callable $render): string
    {
        // Host redeclares $mode on App\Services\ThemeBuilder\BlockRenderer; the
        // shared parent owns $context. Read/restore via the exact declaring
        // classes, switch through the public API.
        $readMode = new \ReflectionProperty(BlockRenderer::class, 'mode');
        $readMode->setAccessible(true);
        $readContext = new \ReflectionProperty(SharedBlockRenderer::class, 'context');
        $readContext->setAccessible(true);

        $previousMode = $readMode->getValue($this->renderer);
        $previousContext = $readContext->getValue($this->renderer);

        try {
            $this->renderer
                ->setMode('public')
                ->setContext(['editor_mode' => false, 'runtime_mode' => 'public']);

            return (string) $render();
        } finally {
            $readMode->setValue($this->renderer, $previousMode);
            $readContext->setValue($this->renderer, $previousContext);
        }
    }
}
