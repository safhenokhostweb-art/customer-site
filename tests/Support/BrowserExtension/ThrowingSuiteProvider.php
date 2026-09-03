<?php

namespace Tests\Support\BrowserExtension;

use App\Builder\Definition\ThemeBuilderElementDefinition;
use App\Builder\Definition\ThemeBuilderExtensionBlock;
use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\Extension\ThemeBuilderExtensionProviderContract;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use Tests\Unit\Builder\Extension\Fixtures\ThrowingDemoElement;

/**
 * Phase 3E-H-D — test-only provider for the renderer-failure-safety
 * browser scenario. See DemoSuiteProvider's docblock for the registration
 * mechanism (BROWSER_TEST_EXTENSION_PROVIDERS env var only).
 */
final class ThrowingSuiteProvider implements ThemeBuilderExtensionProviderContract
{
    public function definition(): ThemeBuilderExtensionDefinition
    {
        return ThemeBuilderExtensionDefinition::make(
            ExtensionProductIdentity::fromParts('acme', 'throwing-suite'),
            '1.0.0',
            ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1,
            [
                ThemeBuilderExtensionBlock::make(
                    'ext:acme:demo_throwing',
                    ThemeBuilderElementDefinition::fromElementClass(ThrowingDemoElement::class),
                    ThrowingDemoElement::class,
                ),
            ],
        );
    }
}
