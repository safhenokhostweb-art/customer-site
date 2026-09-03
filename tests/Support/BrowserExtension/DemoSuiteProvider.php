<?php

namespace Tests\Support\BrowserExtension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\Extension\ThemeBuilderExtensionProviderContract;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use Tests\Unit\Builder\Extension\Fixtures\DemoCardElement;
use Tests\Unit\Builder\Extension\Fixtures\DemoContainerElement;

/**
 * Phase 3E-H-D — test-only provider exposing the same `acme/demo-suite`
 * fixtures already proven by PHPUnit (3E-G/3E-H-B/3E-H-C:
 * ThemeBuilderExtensionRegistrarTest, ProductionBuilderAdapterTest,
 * RuntimeGapClosureTest), now for real-browser verification through the
 * customer-site Playwright harness (tests/browser).
 *
 * Registered ONLY when config('theme_builder_extensions.providers')
 * resolves this class — which happens ONLY via the
 * BROWSER_TEST_EXTENSION_PROVIDERS environment variable the browser
 * harness sets (see config/theme_builder_extensions.php). Never a
 * production default; never registered by any real deployment.
 */
final class DemoSuiteProvider implements ThemeBuilderExtensionProviderContract
{
    public function definition(): ThemeBuilderExtensionDefinition
    {
        return ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('acme', 'demo-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [
                ThemeBuilderExtensionBlock::make(
                    'ext:acme:demo_card',
                    ThemeBuilderElementDefinition::fromElementClass(DemoCardElement::class),
                    DemoCardElement::class,
                ),
                ThemeBuilderExtensionBlock::make(
                    'ext:acme:demo_container',
                    ThemeBuilderElementDefinition::fromElementClass(DemoContainerElement::class),
                    DemoContainerElement::class,
                ),
            ],
        );
    }
}
