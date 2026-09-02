<?php

namespace App\Builder\Extension;

use App\Builder\Definition\ThemeBuilderExtensionRegistrationException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Phase 3E-G — the one deterministic application-boot loading path for
 * extension packages: an explicit, config-driven provider class list
 * (`config('theme_builder_extensions.providers')`) — no filesystem or
 * Composer scanning, no arbitrary class name accepted from an HTTP
 * request. Empty by default (no real extension exists yet), making this
 * a total no-op in production today; wiring is proven correct by tests
 * that override the config directly.
 *
 * Idempotent by construction: re-running boot() with the same provider
 * list re-registers the same packages, which ExtensionBlockTypeRegistry/
 * ElementRegistry already treat as safe no-ops (3E-B) — nothing here
 * tracks "already booted" state of its own.
 */
final class ThemeBuilderExtensionLoader
{
    public static function boot(Application $app): void
    {
        $providers = config('theme_builder_extensions.providers', []);
        if (!is_array($providers)) {
            return;
        }

        foreach ($providers as $providerClass) {
            if (!is_string($providerClass) || !is_subclass_of($providerClass, ThemeBuilderExtensionProviderContract::class)) {
                throw ThemeBuilderExtensionRegistrationException::invalidProvider(is_string($providerClass) ? $providerClass : gettype($providerClass));
            }

            $provider = $app->make($providerClass);
            ThemeBuilderExtensionRegistrar::register($provider->definition());
        }
    }
}
