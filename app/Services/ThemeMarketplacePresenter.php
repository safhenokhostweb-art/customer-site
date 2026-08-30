<?php

namespace App\Services;

/**
 * Phase 1h — single place that decides how to read a theme-marketplace API
 * payload (theme or license array) during the ThemeRelease cutover, so no
 * Blade view repeats its own `?? ` fallback chain. ThemeRelease-authoritative
 * fields (current_version, installed_version, is_outdated_by_ledger,
 * release_notes, changelog, released_at, dependencies, dependency_status)
 * are preferred everywhere; the legacy fields (version, installed_theme_version,
 * is_outdated, version_notes, requirements) are used only as the documented,
 * observable fallback when the new field is null — never silently, and never
 * once the ledger has an answer.
 */
class ThemeMarketplacePresenter
{
    /** Known dependency types today — an open set: dependencyTypeLabel()
     *  falls back to the raw type string for anything not listed here, so a
     *  future product class renders safely with no code change required. */
    private static array $dependencyTypeLabels = [
        'module'        => 'ماژول',
        'motion_asset'  => 'دارایی موشن',
        'motion_pack'   => 'پک موشن',
        'icon_asset'    => 'آیکون',
        'icon_pack'     => 'پک آیکون',
        'block_pack'    => 'پک بلوک',
        'design_system' => 'سیستم طراحی',
    ];

    public static function currentVersion(?array $theme): ?string
    {
        if (!$theme) {
            return null;
        }
        return $theme['current_version'] ?? ($theme['version'] ? (string) $theme['version'] : null);
    }

    public static function isReleased(?array $theme): bool
    {
        return ($theme['release_status'] ?? null) === 'released' || self::currentVersion($theme) !== null;
    }

    public static function installedVersion(array $license): ?string
    {
        return $license['installed_version']
            ?? ($license['installed_theme_version'] ? (string) $license['installed_theme_version'] : null);
    }

    /**
     * Ledger-preferred update-state check. Falls back to the legacy boolean
     * ONLY when the ledger genuinely has no answer yet (null) — never when it
     * says "not outdated" — so a stale ledger state can never manufacture a
     * false "update available" warning, and an unknown ledger state still
     * degrades to the same behavior customer-site already trusted before
     * this migration.
     */
    public static function isOutdated(array $license): bool
    {
        $byLedger = $license['is_outdated_by_ledger'] ?? null;

        if ($byLedger !== null) {
            return (bool) $byLedger;
        }

        return (bool) ($license['is_outdated'] ?? false);
    }

    /** True only when the ledger itself answered — for UI that wants to
     *  distinguish "ledger confirmed current" from "ledger has no data yet,
     *  showing the legacy answer instead" without overstating confidence. */
    public static function isOutdatedStateFromLedger(array $license): bool
    {
        return ($license['is_outdated_by_ledger'] ?? null) !== null;
    }

    public static function releaseNotes(?array $theme): ?string
    {
        if (!$theme) {
            return null;
        }
        return $theme['release_notes'] ?? $theme['version_notes'] ?? null;
    }

    public static function changelog(?array $theme): ?string
    {
        return $theme['changelog'] ?? null;
    }

    public static function releasedAt(?array $theme): ?string
    {
        return $theme['released_at'] ?? null;
    }

    /** @return array<int, array{type: string, identifier: string, is_required: bool, version_constraint: ?string, metadata: array}> */
    public static function dependencies(?array $theme): array
    {
        return $theme['dependencies'] ?? [];
    }

    /** 'resolved' | 'unresolved' | 'unknown' — never inferred, always the
     *  API's own explicit field, since only the release ledger actually
     *  knows which case applies (see saas-platform ThemeMarketplaceApiController). */
    public static function dependencyStatus(?array $theme): string
    {
        return $theme['dependency_status'] ?? 'unknown';
    }

    public static function dependencyTypeLabel(string $type): string
    {
        return self::$dependencyTypeLabels[$type] ?? $type;
    }
}
