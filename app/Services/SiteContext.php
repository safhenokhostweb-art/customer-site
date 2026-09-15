<?php

namespace App\Services;

use LogicException;
use SaaSPlatform\CoreUtils\Contracts\SiteContextContract;

/**
 * TEST-HARNESS-DB-01 follow-up: customer-site is a single-tenant
 * installation with no `App\Models\Site` / multi-site concept at all — the
 * "current platform Site" idea SiteContextContract (SaaSPlatform\CoreUtils)
 * represents is meaningless here. It was never bound in this app, so any
 * shared package code depending on it (e.g. the Forms module's
 * SiteTenantScope, which calls `app(SiteContextContract::class)->siteId()`
 * to tenant-scope form/submission queries on saas-platform) threw
 * `BindingResolutionException: ... is not instantiable` — a real production
 * bug (the admin Forms index 500s), not just a test gap.
 *
 * Always reporting "no site" is the correct behavior for this host:
 * SiteTenantScope::apply() already treats a null site id as "do not
 * tenant-scope this query" (see its own null check), which is exactly
 * right for a single-tenant install — every row already belongs to this
 * one installation, there is nothing to scope against.
 */
final class SiteContext implements SiteContextContract
{
    private ?int $siteId = null;

    public function siteId(): ?int { return $this->siteId; }
    public function hasSite(): bool { return $this->siteId !== null; }
    public function requireSiteId(): int { return $this->siteId ?? throw new LogicException('A site context is required.'); }

    public function setSiteId(int $siteId): void
    {
        if ($siteId < 1) throw new LogicException('A site context requires a positive site identifier.');
        $this->siteId = $siteId;
    }

    public function clear(): void { $this->siteId = null; }
    public function snapshot(): ?array { return $this->siteId === null ? null : ['site_id' => $this->siteId]; }
}
