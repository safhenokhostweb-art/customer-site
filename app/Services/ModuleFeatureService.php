<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ModuleFeatureService
{
    private const CACHE_TTL = 3600; // 60 minutes

    /**
     * Get module features from canonical config or JSON file with caching.
     *
     * Priority:
     * 1. Canonical config key (e.g., config('module-manifest-forms'))
     * 2. Legacy module-features/{slug}.json file
     *
     * PHASE 6.95: Features are filtered by context (platform/tenant/shared).
     *   - customer-site (tenant): only tenant_features + shared_features
     *   - saas-platform (platform): only platform_features + shared_features
     */
    public function getModuleFeatures(string $moduleSlug): ?array
    {
        $cacheKey = "module_features_{$moduleSlug}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($moduleSlug) {
            // Priority 1: Check canonical config
            $configKey = "module-manifest-{$moduleSlug}";
            $canonicalConfig = config($configKey);
            if ($canonicalConfig && is_array($canonicalConfig)) {
                return $this->filterFeaturesByContext($canonicalConfig);
            }

            // Priority 2: Fall back to legacy JSON file
            $path = base_path("module-features/{$moduleSlug}.json");

            if (!File::exists($path)) {
                return null;
            }

            $content = File::get($path);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return $data;
        });
    }

    /**
     * Filter features based on runtime context.
     *
     * The canonical manifest now has three sections:
     *   - platform_features:  only for saas-platform
     *   - tenant_features:    only for customer-site
     *   - shared_features:    for both
     *
     * This method merges the appropriate sections based on context.
     */
    protected function filterFeaturesByContext(array $manifest): array
    {
        // Determine context: check if PlatformContextService exists
        $isPlatform = false;
        if (class_exists(\SaaSPlatform\Modules\Forms\Services\PlatformContextService::class)) {
            try {
                $contextService = app(\SaaSPlatform\Modules\Forms\Services\PlatformContextService::class);
                $isPlatform = $contextService->isCentralAdmin();
            } catch (\Throwable $e) {
                // Service not registered, fall back to tenant context
                $isPlatform = false;
            }
        }

        // Merge appropriate features
        $features = $manifest['shared_features'] ?? [];

        if ($isPlatform) {
            // saas-platform: platform_features + shared_features
            $platformFeatures = $manifest['platform_features'] ?? [];
            $features = array_merge($features, $platformFeatures);
        } else {
            // customer-site: tenant_features + shared_features
            $tenantFeatures = $manifest['tenant_features'] ?? [];
            $features = array_merge($features, $tenantFeatures);
        }

        // Replace the flat 'features' list with context-filtered list
        $manifest['features'] = $features;

        return $manifest;
    }

    /**
     * Get all module slugs with their JSON files.
     */
    public function getAllModuleSlugs(): array
    {
        $files = File::files(base_path('module-features'));

        return collect($files)
            ->filter(fn ($file) => $file->getExtension() === 'json' && $file->getFilename() !== 'context-backup.json')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->toArray();
    }

    /**
     * Clear cache for a specific module.
     */
    public function clearCache(string $moduleSlug): void
    {
        Cache::forget("module_features_{$moduleSlug}");
    }

    /**
     * Clear all module features caches.
     */
    public function clearAllCache(): void
    {
        foreach ($this->getAllModuleSlugs() as $slug) {
            $this->clearCache($slug);
        }
    }
}
