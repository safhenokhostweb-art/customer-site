<?php

namespace Tests\Unit\Architecture;

use Tests\TestCase;

/**
 * Phase 3G-B-2 — static assertion of the durable cross-repo dependency
 * boundary: customer-site may depend on the shared
 * SaaSPlatform\ExtensionSdk\* declarative contract package, but only via
 * its packages/saas-platform/modules/* shared-package tree, never
 * saas-platform's own application (`app/`) directory.
 */
class ExtensionSdkDependencyDirectionTest extends TestCase
{
    public function test_composer_path_repositories_only_reference_the_shared_packages_tree(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $sawSaasPlatformRepo = false;

        foreach ($composer['repositories'] ?? [] as $repository) {
            if (($repository['type'] ?? null) === 'path' && str_contains($repository['url'], 'saas-platform')) {
                $sawSaasPlatformRepo = true;
                $this->assertStringContainsString(
                    'saas-platform/packages/saas-platform/',
                    $repository['url'],
                    'customer-site may only depend on saas-platform via its packages/saas-platform/* shared-package tree, never its application app/ directory.'
                );
            }
        }

        $this->assertTrue($sawSaasPlatformRepo, 'expected at least one saas-platform path repository to be configured');
    }

    public function test_extension_sdk_package_is_importable_from_customer_site(): void
    {
        $this->assertTrue(class_exists(\SaaSPlatform\ExtensionSdk\ExtensionProductIdentity::class));
        $this->assertTrue(class_exists(\SaaSPlatform\ExtensionSdk\ExtensionManifest::class));
    }
}
