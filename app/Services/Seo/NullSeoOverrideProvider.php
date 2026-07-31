<?php

namespace App\Services\Seo;

use SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface;

/**
 * Default binding — no module has anything to override, so SeoService's
 * existing PlatformUx-based logic is used unchanged. Rebound by any
 * module's own ServiceProvider that wants to contribute (e.g. seo-advanced).
 */
class NullSeoOverrideProvider implements SeoOverrideProviderInterface
{
    public function overrideFor(): ?array
    {
        return null;
    }

    public function extraJsonLd(): array
    {
        return [];
    }
}
