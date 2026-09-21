<?php

namespace Tests\Unit\Services;

use App\Services\ThemeMarketplacePresenter;
use Tests\TestCase;

/**
 * Phase 1h — ThemeRelease-authoritative field selection. Proves the exact
 * preference/fallback rules the customer-site UI now depends on, independent
 * of any HTTP/Blade rendering concern.
 */
class ThemeMarketplacePresenterTest extends TestCase
{
    // ── version display ──────────────────────────────────────────────────

    public function test_current_version_is_preferred_over_legacy_version(): void
    {
        $theme = ['current_version' => '1.2.3', 'version' => 7];

        $this->assertSame('1.2.3', ThemeMarketplacePresenter::currentVersion($theme));
    }

    public function test_legacy_version_fallback_works_when_current_version_is_null(): void
    {
        $theme = ['current_version' => null, 'version' => 7];

        $this->assertSame('7', ThemeMarketplacePresenter::currentVersion($theme));
    }

    public function test_current_version_is_null_when_both_are_unavailable(): void
    {
        $this->assertNull(ThemeMarketplacePresenter::currentVersion(['current_version' => null, 'version' => null]));
        $this->assertNull(ThemeMarketplacePresenter::currentVersion(null));
    }

    public function test_current_version_does_not_crash_when_legacy_version_key_is_entirely_absent(): void
    {
        // The real Platform API contract (ThemeMarketplaceApiController::formatTheme)
        // never sends `version` at all — only `current_version`, nullable. A
        // direct unguarded access to the missing legacy key used to throw
        // "Undefined array key" for any unreleased theme.
        $this->assertNull(ThemeMarketplacePresenter::currentVersion(['current_version' => null]));
        $this->assertSame('1.2.3', ThemeMarketplacePresenter::currentVersion(['current_version' => '1.2.3']));
    }

    public function test_current_version_does_not_crash_when_preferred_key_is_entirely_absent(): void
    {
        $this->assertSame('7', ThemeMarketplacePresenter::currentVersion(['version' => 7]));
        $this->assertNull(ThemeMarketplacePresenter::currentVersion([]));
    }

    public function test_installed_version_is_preferred_over_legacy_installed_theme_version(): void
    {
        $license = ['installed_version' => '2.0.0', 'installed_theme_version' => 3];

        $this->assertSame('2.0.0', ThemeMarketplacePresenter::installedVersion($license));
    }

    public function test_installed_version_falls_back_to_legacy_when_ledger_value_is_null(): void
    {
        $license = ['installed_version' => null, 'installed_theme_version' => 3];

        $this->assertSame('3', ThemeMarketplacePresenter::installedVersion($license));
    }

    public function test_uninstalled_new_license_without_legacy_key_returns_null(): void
    {
        $this->assertNull(ThemeMarketplacePresenter::installedVersion(['installed_version' => null]));
    }

    // ── update-available logic (CRITICAL per task) ───────────────────────

    public function test_ledger_says_current_is_trusted_even_if_legacy_disagrees(): void
    {
        // A real, deliberate divergence: the ledger says up to date, but the
        // legacy flag (stale, or computed differently) says outdated. Ledger
        // must win — never a false update warning.
        $license = ['is_outdated_by_ledger' => false, 'is_outdated' => true];

        $this->assertFalse(ThemeMarketplacePresenter::isOutdated($license));
    }

    public function test_ledger_says_outdated_is_trusted_even_if_legacy_disagrees(): void
    {
        $license = ['is_outdated_by_ledger' => true, 'is_outdated' => false];

        $this->assertTrue(ThemeMarketplacePresenter::isOutdated($license));
    }

    public function test_null_ledger_state_safely_falls_back_to_legacy(): void
    {
        $this->assertTrue(ThemeMarketplacePresenter::isOutdated(['is_outdated_by_ledger' => null, 'is_outdated' => true]));
        $this->assertFalse(ThemeMarketplacePresenter::isOutdated(['is_outdated_by_ledger' => null, 'is_outdated' => false]));
    }

    public function test_a_legacy_only_api_response_with_no_ledger_key_at_all_still_works(): void
    {
        // Simulates a not-yet-upgraded API response (defensive: the field is
        // simply absent from the array, not explicitly null).
        $license = ['is_outdated' => true];

        $this->assertTrue(ThemeMarketplacePresenter::isOutdated($license));
        $this->assertFalse(ThemeMarketplacePresenter::isOutdatedStateFromLedger($license));
    }

    public function test_ledger_state_flag_correctly_reports_whether_the_ledger_actually_answered(): void
    {
        $this->assertTrue(ThemeMarketplacePresenter::isOutdatedStateFromLedger(['is_outdated_by_ledger' => false]));
        $this->assertFalse(ThemeMarketplacePresenter::isOutdatedStateFromLedger(['is_outdated_by_ledger' => null]));
    }

    // ── release information ───────────────────────────────────────────────

    public function test_release_notes_is_preferred_over_legacy_version_notes(): void
    {
        $theme = ['release_notes' => 'New hero section.', 'version_notes' => 'Old notes.'];

        $this->assertSame('New hero section.', ThemeMarketplacePresenter::releaseNotes($theme));
    }

    public function test_release_notes_falls_back_to_version_notes_when_null(): void
    {
        $theme = ['release_notes' => null, 'version_notes' => 'Old notes.'];

        $this->assertSame('Old notes.', ThemeMarketplacePresenter::releaseNotes($theme));
    }

    // ── dependencies ──────────────────────────────────────────────────────

    public function test_dependency_status_defaults_to_unknown_when_absent(): void
    {
        $this->assertSame('unknown', ThemeMarketplacePresenter::dependencyStatus(['dependencies' => []]));
    }

    public function test_dependency_type_label_falls_back_to_the_raw_type_for_unknown_types(): void
    {
        $this->assertSame('ماژول', ThemeMarketplacePresenter::dependencyTypeLabel('module'));
        $this->assertSame('a-brand-new-product-class', ThemeMarketplacePresenter::dependencyTypeLabel('a-brand-new-product-class'));
    }
}
