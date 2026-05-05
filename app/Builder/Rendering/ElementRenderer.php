<?php

namespace App\Builder\Rendering;

use App\Builder\Contracts\HasAssets;
use App\Builder\ElementRegistry;
use App\Builder\Services\DynamicDataResolver;
use App\Builder\Validation\ElementValidator;
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

    public function setContext(array $context): self
    {
        $this->context = $context;
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
            $schema = $class::getSchema();
            $result = ElementValidator::validate($schema, $props);
            $props = $result['props'];
            if (!empty($result['errors'])) {
                Log::debug('Builder element validation warnings', ['type' => $type, 'errors' => $result['errors']]);
            }

            if ($class::supportsDynamicData() && $this->resolver !== null) {
                $props = $this->resolver->setContext($context)->resolve($props);
            }

            $props['_style'] = $context['style'] ?? [];
            $html = $class::render($props, $context);

            if (is_subclass_of($class, HasAssets::class)) {
                $this->collectAssets($class::getAssets());
            }

            return $html;
        } catch (\Throwable $e) {
            Log::error('Builder element render failed', [
                'type' => $type,
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->placeholder($id, $type);
        }
    }

    protected function placeholder(string $id, string $type): string
    {
        $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        $safeType = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        return '<div id="tb-' . $safeId . '" class="tb-placeholder tb-placeholder-error" data-type="' . $safeType . '" data-reason="render_error" aria-hidden="true"><span class="tb-placeholder-label">Element failed to render</span></div>';
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
