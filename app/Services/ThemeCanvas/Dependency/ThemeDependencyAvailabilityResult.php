<?php

namespace App\Services\ThemeCanvas\Dependency;

/**
 * Phase 3E-A — outcome of checking one ThemeRelease dependency against
 * this installation's real, current state. Never a bare bool: AVAILABLE
 * is a positive proof, not merely "no evidence of a problem" — anything
 * this installation cannot actually verify is UNVERIFIABLE, never silently
 * treated as available.
 */
final class ThemeDependencyAvailabilityResult
{
    public const AVAILABLE = 'AVAILABLE';
    public const MISSING = 'MISSING';
    public const VERSION_INCOMPATIBLE = 'VERSION_INCOMPATIBLE';
    public const UNVERIFIABLE = 'UNVERIFIABLE';

    private function __construct(
        public readonly string $status,
        public readonly ?string $installedVersion = null,
        public readonly ?string $reason = null,
    ) {
    }

    public static function available(?string $installedVersion = null): self
    {
        return new self(self::AVAILABLE, $installedVersion);
    }

    public static function missing(?string $reason = null): self
    {
        return new self(self::MISSING, null, $reason);
    }

    public static function versionIncompatible(?string $installedVersion, ?string $reason = null): self
    {
        return new self(self::VERSION_INCOMPATIBLE, $installedVersion, $reason);
    }

    public static function unverifiable(?string $reason = null): self
    {
        return new self(self::UNVERIFIABLE, null, $reason);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::AVAILABLE;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'installed_version' => $this->installedVersion,
            'reason' => $this->reason,
        ];
    }
}
