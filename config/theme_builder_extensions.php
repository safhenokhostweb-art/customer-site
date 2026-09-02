<?php

/*
|--------------------------------------------------------------------------
| Theme Builder Extension Providers (Phase 3E-G)
|--------------------------------------------------------------------------
|
| Explicit, deterministic, config-driven list of provider classes (each
| implementing App\Builder\Extension\ThemeBuilderExtensionProviderContract)
| loaded once at application boot by
| App\Builder\Extension\ThemeBuilderExtensionLoader. No filesystem or
| Composer package scanning — only classes explicitly listed here are
| ever resolved, and only from this trusted config file, never from an
| HTTP request.
|
| Empty by default: no third-party extension exists yet. Registering a
| provider here only makes its ext:* block types/renderers available — it
| does NOT grant any tenant entitlement to use them (see
| App\Services\ThemeCanvas\Dependency\ExtensionDependencyResolver, a
| completely separate, request-scoped concern) and does NOT itself record
| the package as "installed" for dependency-availability purposes (see
| config/extensions.php's own, independently-configured 'installed' list).
|
*/

return [
    'providers' => [
        // \App\Builder\Extension\SomeVendor\SomePackageProvider::class,
    ],
];
