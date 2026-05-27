<?php

namespace App\Services;

use SaaSPlatform\CoreUtils\Contracts\Platform\SiteModelInterface;
use SaaSPlatform\CoreUtils\Contracts\Services\ModuleLicenseServiceInterface;

/**
 * Null implementation for customer-site: all modules are always allowed.
 * Customer-site operates as a single licensed tenant — no per-site entitlement checks.
 */
class NullModuleLicenseAdapter implements ModuleLicenseServiceInterface
{
    public function checkEntitlement(SiteModelInterface $site, string $moduleSlug): array
    {
        return ['allowed' => true, 'reason' => null];
    }

    public function logDenial(
        SiteModelInterface $site,
        string $moduleSlug,
        string $context,
        string $reason,
        array $requestContext = []
    ): void {
        // no-op: customer-site never denies access
    }
}
