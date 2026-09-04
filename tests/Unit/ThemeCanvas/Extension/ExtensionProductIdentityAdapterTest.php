<?php

namespace Tests\Unit\ThemeCanvas\Extension;

use App\Services\ThemeCanvas\Extension\ExtensionIdentityRegistrationException;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;
use Tests\TestCase;

/**
 * Phase 3G-B-2 — proves the TRANSITIONAL App\Services\ThemeCanvas\Extension\
 * ExtensionProductIdentity adapter still behaves exactly as its ~13 existing
 * call sites expect (::fromParts()/::fromIdentifier(), the public
 * ->vendorSlug/->packageSlug/->identifier properties, and the original
 * ExtensionIdentityRegistrationException type/messages) even though every
 * validation branch now delegates to the shared
 * SaaSPlatform\ExtensionSdk\ExtensionProductIdentity authority with no
 * regex duplicated in this namespace any more.
 */
class ExtensionProductIdentityAdapterTest extends TestCase
{
    public function test_from_parts_populates_the_expected_public_properties(): void
    {
        $identity = ExtensionProductIdentity::fromParts('acme', 'gallery-pro');

        $this->assertSame('acme', $identity->vendorSlug);
        $this->assertSame('gallery-pro', $identity->packageSlug);
        $this->assertSame('acme/gallery-pro', $identity->identifier);
    }

    public function test_from_identifier_matches_from_parts(): void
    {
        $viaIdentifier = ExtensionProductIdentity::fromIdentifier('acme/gallery-pro');
        $viaParts = ExtensionProductIdentity::fromParts('acme', 'gallery-pro');

        $this->assertSame($viaParts->identifier, $viaIdentifier->identifier);
    }

    public function test_malformed_vendor_throws_the_original_exception_type(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionProductIdentity::fromParts('Not Valid', 'gallery-pro');
    }

    public function test_malformed_package_throws_the_original_exception_type(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionProductIdentity::fromParts('acme', 'Not Valid!');
    }

    public function test_malformed_identifier_shape_throws_the_original_exception_type(): void
    {
        $this->expectException(ExtensionIdentityRegistrationException::class);
        ExtensionProductIdentity::fromIdentifier('acme/gallery/pro');
    }

    public function test_is_valid_identifier_and_is_valid_package_slug_still_work(): void
    {
        $this->assertTrue(ExtensionProductIdentity::isValidIdentifier('acme/gallery-pro'));
        $this->assertFalse(ExtensionProductIdentity::isValidIdentifier('acme'));
        $this->assertTrue(ExtensionProductIdentity::isValidPackageSlug('gallery-pro'));
        $this->assertFalse(ExtensionProductIdentity::isValidPackageSlug('Gallery_Pro'));
    }
}
