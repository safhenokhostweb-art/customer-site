<?php

namespace App\Builder\Rendering;

use App\Builder\Contracts\HasAssets;
use App\Builder\Contracts\HasDynamicData;
use App\Builder\ElementRegistry;
use App\Builder\Services\DynamicDataResolver;
use App\Builder\Validation\ElementValidator;
use App\Services\ThemeCanvas\Extension\ThemeExtensionAssetMap;
use Illuminate\Support\Facades\Log;

/**
 * Rendering pipeline: resolve element → validate props → resolve dynamic data → render.
 * Never throws; on failure returns placeholder "Element failed to render".
 */
class ElementRenderer
{
    protected array $context = [];

    protected array $loadedAssets = ['css' => [], 'js' => []];

    protected ?DynamicDataResolver $resolver = null;

    /**
     * Manifest-declared Extension assets (ThemeExtensionAssetMap), set by
     * the host BlockRenderer from the Theme's own `extension_assets`
     * payload. Null (the default) is a complete no-op — every pre-existing
     * caller that never calls setExtensionAssetMap() behaves exactly as
     * before.
     */
    protected ?ThemeExtensionAssetMap $extensionAssetMap = null;

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /** @param array<string, array{css?: string[], js?: string[]}> $rawAssetMap raw, untrusted — parsed defensively by ThemeExtensionAssetMap */
    public function setExtensionAssetMap(array $rawAssetMap): self
    {
        $this->extensionAssetMap = ThemeExtensionAssetMap::fromArray($rawAssetMap);
        return $this;
    }

    public function setDynamicDataResolver(?DynamicDataResolver $resolver): self
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * Render a single element by type. Context should include id, inner (for containers), style, etc.
     */
    public function render(string $type, array $props, array $context = []): string
    {
        $context = array_merge($this->context, $context);
        $id = $context['id'] ?? '';

        $class = ElementRegistry::get($type);
        if ($class === null) {
            Log::warning('Builder element type not in registry', ['type' => $type]);
            return $this->placeholder($id, $type);
        }

        try {
            // Phase 2j: `_style` is a cross-cutting runtime convention
            // (custom per-block styling), not an authored element schema
            // field — ElementValidator::validate() rebuilds props from only
            // the schema's own declared keys and silently dropped it,
            // discarding real authored styling whenever it arrived embedded
            // in props._style (as canonical Theme content actually stores
            // it) rather than as a sibling block.style key. Found by
            // CanonicalStructuralParityTest as a real divergence, not
            // merely a markup difference — this was data loss.
            $incomingStyle = is_array($props['_style'] ?? null) ? $props['_style'] : ($context['style'] ?? []);

            $schema = $class::getSchema();
            $result = ElementValidator::validate($schema, $props);
            $props = $result['props'];
            if (!empty($result['errors'])) {
                Log::debug('Builder element validation warnings', ['type' => $type, 'errors' => $result['errors']]);
            }

            // Phase 3E-G (correction): gated by HasDynamicData exactly like
            // ThemeBuilderElementDefinition::fromElementClass() already
            // does — supportsDynamicData() is an OPTIONAL capability, not
            // a method every ElementInterface implementation is
            // guaranteed to have. Previously called unconditionally here,
            // safe only because every built-in element extends BaseElement.
            if (is_subclass_of($class, HasDynamicData::class) && $class::supportsDynamicData() && $this->resolver !== null) {
                $props = $this->resolver->setContext($context)->resolve($props);
            }

            $props['_style'] = $incomingStyle;
            $html = $class::render($props, $context);

            if (is_subclass_of($class, HasAssets::class)) {
                $this->collectAssets($class::getAssets());
            }

            // Manifest-declared Extension assets are independent of
            // HasAssets — a renderer is never required to implement
            // HasAssets merely to repeat the same assets its Extension's
            // manifest already declares. collectAssets()'s own in_array()
            // dedup means a URL declared by BOTH sources is still emitted
            // exactly once.
            $manifestAssets = $this->extensionAssetMap?->assetsFor($type);
            if ($manifestAssets !== null) {
                $this->collectAssets($manifestAssets);
            }

            return $html;
        } catch (\Throwable $e) {
            Log::error('Builder element render failed', [
                'type' => $type,
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Unit 2e: the cause, for the editor only (never a public page).
            $detail = !empty($context['editor_mode']) && ($context['mode'] ?? null) !== 'public'
                ? \App\Services\ThemeBuilder\PlaceholderDetail::fromThrowable($e)
                : '';
            return $this->placeholder($id, $type, $detail);
        }
    }

    protected function placeholder(string $id, string $type, string $detail = ''): string
    {
        $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        $safeType = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        $detailHtml = \App\Services\ThemeBuilder\PlaceholderDetail::html('render_error', $detail);
        return '<div id="tb-' . $safeId . '" class="tb-placeholder tb-placeholder-error" data-type="' . $safeType . '" data-reason="render_error" aria-hidden="true"><span class="tb-placeholder-label">Element failed to render</span>' . $detailHtml . '</div>';
    }

    protected function collectAssets(array $assets): void
    {
        foreach ($assets['css'] ?? [] as $href) {
            if (!in_array($href, $this->loadedAssets['css'], true)) {
                $this->loadedAssets['css'][] = $href;
            }
        }
        foreach ($assets['js'] ?? [] as $src) {
            if (!in_array($src, $this->loadedAssets['js'], true)) {
                $this->loadedAssets['js'][] = $src;
            }
        }
    }

    /**
     * Get collected CSS/JS for this request (load once per page).
     */
    public function getCollectedAssets(): array
    {
        return $this->loadedAssets;
    }
}
