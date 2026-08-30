<?php

namespace App\Services\ThemeCanvas;

/**
 * Result of a ThemeInstanceBaseRelease lookup. Either RESOLVED with the
 * exact immutable ThemeRelease identity a Theme instance state is based
 * on, or NON_RECONCILABLE — never a partial/approximate guess.
 */
final class ThemeBaseReleaseResolution
{
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_NON_RECONCILABLE = 'non_reconcilable';

    private function __construct(
        public readonly string $status,
        public readonly ?string $themeIdentifier = null,
        public readonly ?int $sourceThemeId = null,
        public readonly ?string $sourceThemeVersion = null,
        public readonly ?int $sourceReleaseId = null,
        public readonly ?int $resolvedFromRevisionId = null,
        public readonly ?int $resolvedFromRevisionNumber = null,
    ) {
    }

    public static function resolved(
        string $themeIdentifier,
        ?int $sourceThemeId,
        ?string $sourceThemeVersion,
        int $sourceReleaseId,
        int $resolvedFromRevisionId,
        int $resolvedFromRevisionNumber
    ): self {
        return new self(
            self::STATUS_RESOLVED,
            $themeIdentifier,
            $sourceThemeId,
            $sourceThemeVersion,
            $sourceReleaseId,
            $resolvedFromRevisionId,
            $resolvedFromRevisionNumber
        );
    }

    public static function nonReconcilable(): self
    {
        return new self(self::STATUS_NON_RECONCILABLE);
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}
