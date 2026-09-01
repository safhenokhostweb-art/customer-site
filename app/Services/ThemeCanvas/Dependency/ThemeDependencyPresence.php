<?php

namespace App\Services\ThemeCanvas\Dependency;

/**
 * Phase 3E-A — the raw fact a ThemeDependencyTypeResolverContract adapter
 * reports: is this identifier present at all, and (only if this dependency
 * type actually tracks one) what version. Deliberately separate from
 * ThemeDependencyAvailabilityResult — an adapter only ever states facts it
 * can actually prove; constraint evaluation (does the installed version
 * satisfy the release's requirement) is centralized once in
 * ThemeDependencyAvailabilityResolver, never duplicated per adapter.
 */
final class ThemeDependencyPresence
{
    private function __construct(
        public readonly bool $present,
        public readonly ?string $installedVersion,
    ) {
    }

    public static function present(?string $installedVersion = null): self
    {
        return new self(true, $installedVersion);
    }

    public static function absent(): self
    {
        return new self(false, null);
    }
}
