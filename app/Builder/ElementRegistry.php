<?php

namespace App\Builder;

use App\Builder\Contracts\ElementInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Central registry for builder elements.
 * Register element classes, resolve by type, provide metadata for editor.
 * Cached for performance.
 */
class ElementRegistry
{
    protected static ?array $classes = null;

    protected static ?array $byType = null;

    /**
     * Register an element class. Class must implement ElementInterface.
     */
    public static function register(string $class): void
    {
        if (!is_subclass_of($class, ElementInterface::class)) {
            throw new \InvalidArgumentException("Element class must implement ElementInterface: {$class}");
        }
        $type = $class::getType();
        self::$classes[$type] = $class;
        self::$byType = null;
    }

    /**
     * Get element class by type. Returns null if not registered.
     */
    public static function get(string $type): ?string
    {
        self::load();
        return self::$classes[$type] ?? null;
    }

    /**
     * Get all registered element metadata for editor (label, icon, category, defaultProps, required_module).
     */
    public static function all(): array
    {
        self::load();
        $list = [];
        foreach (self::$classes as $type => $class) {
            $list[$type] = [
                'type' => $type,
                'name' => $class::getName(),
                'icon' => $class::getIcon(),
                'category' => $class::getCategory(),
                'defaultProps' => $class::getDefaultProps(),
                'required_module' => $class::getRequiredModule(),
                'defaultSize' => $class::getDefaultSize(),
            ];
        }
        return $list;
    }

    /**
     * Get elements filtered by category.
     */
    public static function forCategory(string $category): array
    {
        return array_filter(self::all(), fn ($m) => $m['category'] === $category);
    }

    /**
     * Filter elements by enabled modules (module slug => enabled bool).
     */
    public static function filterByModules(array $elementsMeta, array $enabledModules): array
    {
        return array_filter($elementsMeta, function ($m) use ($enabledModules) {
            $req = $m['required_module'] ?? null;
            if ($req === null) {
                return true;
            }
            return !empty($enabledModules[$req]);
        });
    }

    /**
     * Check if type is registered.
     */
    public static function has(string $type): bool
    {
        self::load();
        return isset(self::$classes[$type]);
    }

    /**
     * Load registered elements from config and cache.
     */
    protected static function load(): void
    {
        if (self::$classes !== null) {
            return;
        }

        $cacheKey = 'builder_element_registry';
        $cached = config('app.env') !== 'local' ? Cache::get($cacheKey) : null;

        if ($cached !== null && is_array($cached)) {
            self::$classes = $cached;
            return;
        }

        self::$classes = [];
        $list = config('builder.elements', []);
        foreach ($list as $class) {
            if (is_string($class) && is_subclass_of($class, ElementInterface::class)) {
                self::$classes[$class::getType()] = $class;
            }
        }

        if (config('app.env') !== 'local') {
            Cache::put($cacheKey, self::$classes, now()->addHours(24));
        }
    }

    /**
     * Clear cached registry (e.g. after registering new element).
     */
    public static function clearCache(): void
    {
        self::$classes = null;
        self::$byType = null;
        Cache::forget('builder_element_registry');
    }
}
