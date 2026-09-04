<?php

namespace Tests\Unit\ExtensionSdk;

use SaaSPlatform\ExtensionSdk\ExtensionProductIdentity;
use SaaSPlatform\ExtensionSdk\ExtensionSdkException;
use Tests\TestCase;

/**
 * Phase 3G-B-2 (ADR-045 point 5) — the first dedicated test of the
 * Extension PRODUCT identity grammar in customer-site (previously only
 * exercised indirectly, as a fixture-builder, by
 * ExtensionDependencyResolverTest and ~6 other suites). Proves real
 * inheritance from the shared saas-platform/modules-extension-sdk package
 * (junctioned via composer path repository, per the theme-runtime
 * precedent — ADR-029) rather than coincidental duplication, mirroring the
 * saas-platform-side ExtensionProductIdentityTest with the same vectors.
 */
class ExtensionProductIdentityTest extends TestCase
{
    public function test_valid_vendor_slugs_are_accepted(): void
    {
        foreach (['acme', 'foo', 'a1', 'multi-word-vendor', 'ab'] as $vendor) {
            $this->assertTrue(ExtensionProductIdentity::isValidVendorSlug($vendor), "expected \"{$vendor}\" to be valid");
        }
    }

    public function test_invalid_vendor_slugs_are_rejected(): void
    {
        $invalid = ['', 'Acme', 'acme_vendor', ' acme', 'acme ', 'acme/vendor', '-acme', 'acme-', 'acme--vendor', '../acme'];

        foreach ($invalid as $vendor) {
            $this->assertFalse(ExtensionProductIdentity::isValidVendorSlug($vendor), "expected \"{$vendor}\" to be invalid");
        }
    }

    public function test_valid_package_slugs_are_accepted(): void
    {
        foreach (['gallery-pro', 'maps', 'a1', 'multi-word-package'] as $package) {
            $this->assertTrue(ExtensionProductIdentity::isValidPackageSlug($package), "expected \"{$package}\" to be valid");
        }
    }

    public function test_invalid_package_slugs_are_rejected(): void
    {
        $invalid = ['', 'Gallery-Pro', 'gallery_pro', ' gallery', 'gallery ', '-gallery', 'gallery-', '../gallery'];

        foreach ($invalid as $package) {
            $this->assertFalse(ExtensionProductIdentity::isValidPackageSlug($package), "expected \"{$package}\" to be invalid");
        }
    }

    public function test_full_identifier_grammar(): void
    {
        $this->assertTrue(ExtensionProductIdentity::isValidIdentifier('acme/gallery-pro'));
        $this->assertFalse(ExtensionProductIdentity::isValidIdentifier('acme'));
        $this->assertFalse(ExtensionProductIdentity::isValidIdentifier('acme/gallery/pro'));
        $this->assertFalse(ExtensionProductIdentity::isValidIdentifier('../acme/gallery-pro'));
    }

    public function test_from_parts_produces_the_expected_instance(): void
    {
        $identity = ExtensionProductIdentity::fromParts('acme', 'gallery-pro');

        $this->assertSame('acme', $identity->vendorSlug());
        $this->assertSame('gallery-pro', $identity->packageSlug());
        $this->assertSame('acme/gallery-pro', $identity->toString());
    }

    public function test_from_parts_throws_the_shared_exception_type_on_malformed_input(): void
    {
        $this->expectException(ExtensionSdkException::class);
        ExtensionProductIdentity::fromParts('Not Valid', 'gallery-pro');
    }

    public function test_same_vendor_may_own_two_independent_products(): void
    {
        $one = ExtensionProductIdentity::fromParts('acme', 'gallery-pro');
        $two = ExtensionProductIdentity::fromParts('acme', 'forms-pro');

        $this->assertFalse($one->equals($two));
        $this->assertSame('acme/gallery-pro', $one->toString());
        $this->assertSame('acme/forms-pro', $two->toString());
    }
}
