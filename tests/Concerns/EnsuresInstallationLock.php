<?php

namespace Tests\Concerns;

/**
 * Ownership-safe `storage/installed.lock` management for feature tests that
 * hit routes behind the `redirect.if.not.installed` middleware group.
 *
 * Why this trait exists:
 *   - `RedirectIfNotInstalled` sends any request to the installer wizard when
 *     `storage/installed.lock` is missing, so a public-page test that forgets
 *     the lock does not fail with a clear assertion — it fails with a bare
 *     `302 is not identical to 200`, which reads like a routing bug.
 *   - `storage/installed.lock` is listed in `.gitignore`, so it is NOT tracked
 *     in the repository. It is a local/CI artifact, and a fresh checkout (or a
 *     CI container) legitimately has no lock at all.
 *
 * Why it is a trait and not just a `markSiteAsInstalled()` call:
 *   Test classes that UNCONDITIONALLY delete the lock in tearDown poison every
 *   test class scheduled after them. The damage is invisible in the failing
 *   class and only shows up as an unrelated 302 somewhere else in the suite, so
 *   the run order silently decides which test is red. Ownership tracking makes
 *   the precondition self-contained: a test class can no longer remove a lock
 *   another class still depends on.
 *
 * The rule this enforces:
 *   - Create the file if it is missing; never clobber one that already exists.
 *   - Delete it ONLY if this process created it, and ONLY once the last holder
 *     releases it. A lock that predates the run is left untouched, so tests
 *     never corrupt a developer's working tree.
 * Usage:
 *   class MyFeatureTest extends TestCase
 *   {
 *       use RefreshDatabase, EnsuresInstallationLock;
 *
 *       protected function setUp(): void
 *       {
 *           parent::setUp();
 *           $this->ensureInstallationLock();
 *       }
 *
 *       protected function tearDown(): void
 *       {
 *           $this->releaseInstallationLock();
 *           parent::tearDown();
 *       }
 *   }
 */
trait EnsuresInstallationLock
{
    /**
     * Number of live holders in this process. Static because the file is a
     * single shared resource: a per-instance counter cannot see the other test
     * instances that are relying on the same lock.
     */
    private static int $installationLockHolders = 0;

    /**
     * True only if THIS process created the file. A pre-existing lock is
     * someone else's state and must be left alone on the way out.
     */
    private static bool $installationLockCreatedByUs = false;

    /** Guards double-release from a single test instance. */
    private bool $holdsInstallationLock = false;

    /**
     * Create `storage/installed.lock` if missing, and take ownership of it.
     *
     * Safe to call more than once per test: the second call only bumps the
     * holder count for a lock already held.
     */
    protected function ensureInstallationLock(): void
    {
        $path = storage_path('installed.lock');

        if (! file_exists($path)) {
            file_put_contents($path, '1');
            self::$installationLockCreatedByUs = true;
        }

        self::$installationLockHolders++;
        $this->holdsInstallationLock = true;
    }

    /**
     * Release ownership. Removes the file only when this process created it
     * AND no other test in this process still needs it.
     *
     * The holder count is decremented even when the file is left in place, so
     * repeated runs cannot drift the counter upward and strand the lock.
     */
    protected function releaseInstallationLock(): void
    {
        if (! $this->holdsInstallationLock) {
            return;
        }

        $this->holdsInstallationLock = false;
        self::$installationLockHolders = max(0, self::$installationLockHolders - 1);

        if (self::$installationLockHolders === 0 && self::$installationLockCreatedByUs) {
            @unlink(storage_path('installed.lock'));
            self::$installationLockCreatedByUs = false;
        }
    }
}
