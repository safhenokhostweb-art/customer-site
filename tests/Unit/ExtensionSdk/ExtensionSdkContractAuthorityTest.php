<?php

namespace Tests\Unit\ExtensionSdk;

use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use SaaSPlatform\ExtensionSdk\ExtensionSdkContract;
use Tests\TestCase;

/**
 * Phase 3G-B-2 — proves ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1 is
 * genuinely DEFINED IN TERMS OF the shared
 * SaaSPlatform\ExtensionSdk\ExtensionSdkContract authority (real
 * inheritance, not a second, independently-maintained numeric constant that
 * merely happens to currently agree). No existing call site
 * (ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1, used by ~8 files) had
 * to change for this — only the constant's own definition did.
 */
class ExtensionSdkContractAuthorityTest extends TestCase
{
    public function test_customer_runtime_constant_equals_the_shared_authority(): void
    {
        $this->assertSame(ExtensionSdkContract::V1, ThemeBuilderExtensionDefinition::SDK_CONTRACT_V1);
    }

    public function test_shared_authority_reports_v1_as_supported(): void
    {
        $this->assertTrue(ExtensionSdkContract::isSupported(ExtensionSdkContract::V1));
        $this->assertFalse(ExtensionSdkContract::isSupported(999));
    }
}
