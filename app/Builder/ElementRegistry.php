<?php

namespace App\Builder;

use App\Builder\Contracts\ElementInterface;
use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;
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
        self::assertClaimable($type, $class);
        self::$classes[$type] = $class;
        self::$byType = null;
    }

    /**
     * Phase 3E-B — the minimum identity boundary needed before third-party
     * extension registration exists: a Builder element must never silently
     * shadow another already-registered element under the same type, and
     * must never be able to claim an identity reserved for (or already
     * claimed by) the ext: extension namespace (Phase 3E-B's
     * ExtensionBlockTypeRegistry). Deliberately does NOT check against
     * canonical/ThemeCanvas bare type names: 10 of the 13 current elements
     * intentionally share a bare canonical name by design (Phase 2j —
     * they delegate their structural rendering to the canonical
     * implementation of that same name), so rejecting that overlap here
     * would break all of them, not fix a defect.
     */
    private static function assertClaimable(string $type, string $class): void
    {
        if (isset(self::$classes[$type]) && self::$classes[$type] !== $class) {
            throw new \InvalidArgumentException(
                "Element type \"{$type}\" is already registered to " . self::$classes[$type]
                . " — cannot re-register it to {$class}."
            );
        }

        if (str_starts_with($type, 'ext:') || ExtensionBlockTypeRegistry::has($type)) {
            throw new \InvalidArgumentException(
                "Element type \"{$type}\" falls in the reserved third-party extension namespace "
                . 'and cannot be claimed by a Builder element.'
            );
        }
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
     * Phase 3E-D — the declarative ThemeBuilderElementDefinition for one
     * registered type, or null if unregistered. Pure introspection over the
     * already-registered class; does not change registration/collision
     * behavior (assertClaimable() above is untouched and still the sole
     * gate on what may register).
     */
    public static function definition(string $type): ?ThemeBuilderElementDefinition
    {
        $class = self::get($type);
        return $class === null ? null : ThemeBuilderElementDefinition::fromElementClass($class);
    }

    /**
     * Phase 3E-F — the one deterministic ownership classification for a
     * registered type: ThemeBuilderElementDefinition::OWNERSHIP_CANONICAL_ADAPTER
     * / OWNERSHIP_HOST_ONLY / OWNERSHIP_EXTENSION, or null if unregistered.
     * ElementRegistry itself stores no separate ownership flag — this
     * simply surfaces the definition's own derived classification, so
     * there is exactly one place that decision can ever be computed.
     */
    public static function ownershipOf(string $type): ?string
    {
        return self::definition($type)?->ownership;
    }

    /**
     * Every registered type's declarative definition, keyed by type —
     * deterministic (same registration order as all()/self::$classes).
     */
    public static function definitions(): array
    {
        self::load();
        $out = [];
        foreach (self::$classes as $type => $class) {
            $out[$type] = ThemeBuilderElementDefinition::fromElementClass($class);
        }
        return $out;
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
                $type = $class::getType();
                self::assertClaimable($type, $class);
                self::$classes[$type] = $class;
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
