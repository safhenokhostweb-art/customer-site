<?php

namespace Tests\Unit\ThemeCanvas\Dependency;

use App\Services\ThemeCanvas\Dependency\ThemeDependencyPresence;
use App\Services\ThemeCanvas\Dependency\ThemeDependencyTypeResolverContract;

/**
 * A controllable stand-in adapter used only by these tests — proves
 * ThemeDependencyAvailabilityResolver/ThemeUpgradeDependencyGate work
 * against the generic contract without depending on any real product
 * (Motion, module licensing, or otherwise).
 */
final class FakeDependencyResolver implements ThemeDependencyTypeResolverContract
{
    /** @param array<string, ThemeDependencyPresence> $presenceByIdentifier */
    public function __construct(
        private string $type,
        private array $presenceByIdentifier,
    ) {
    }

    public function supports(string $dependencyType): bool
    {
        return $dependencyType === $this->type;
    }

    public function resolve(string $dependencyIdentifier): ThemeDependencyPresence
    {
        return $this->presenceByIdentifier[$dependencyIdentifier] ?? ThemeDependencyPresence::absent();
    }
}
