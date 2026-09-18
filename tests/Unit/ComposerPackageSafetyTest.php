<?php

namespace Tests\Unit;

use App\Support\ComposerPackageSafety;
use PHPUnit\Framework\TestCase;

/**
 * TASK 3/3 Phase 6.5F (BLD-33) — permanent regression coverage for the
 * incident class that deleted 1,091 tracked files from saas-platform's
 * canonical package source (Phase 6.5E): a Composer path-repository
 * "symlink": true package materializes on Windows as a directory
 * Junction, and a reinstall/cleanup operation that mis-detects the
 * Junction as an ordinary directory recurses through it and deletes the
 * real target.
 *
 * Every test here uses ONLY disposable temp directories created and
 * destroyed by the test itself (sys_get_temp_dir()), or reads the REAL
 * customer-site composer.json/vendor in a strictly read-only way (no
 * writes, no deletes) — never the real saas-platform/packages source as
 * a destructive test target. Extends plain PHPUnit\Framework\TestCase
 * (not Tests\TestCase) — no database, no Laravel app boot needed.
 */
class ComposerPackageSafetyTest extends TestCase
{
    private string $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = sys_get_temp_dir() . '/composer-safety-test-' . uniqid();
        mkdir($this->sandbox, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDisposableSandbox($this->sandbox);
        parent::tearDown();
    }

    /** Deletes ONLY the disposable sandbox this test created — never anything outside it. */
    private function removeDisposableSandbox(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $entry) {
            $path = "{$dir}/{$entry}";
            if (is_dir($path) && !ComposerPackageSafety::isLinkLikeEntry($path)) {
                $this->removeDisposableSandbox($path);
            } else {
                @unlink($path) || @rmdir($path);
            }
        }
        @rmdir($dir);
    }

    // ── TEST 1: canonical source sentinel survives simulated vendor cleanup ──

    public function test_canonical_source_sentinel_survives_simulated_vendor_cleanup(): void
    {
        $source = "{$this->sandbox}/fake-source/src";
        mkdir($source, 0755, true);
        file_put_contents("{$source}/Sentinel.php", '<?php // sentinel');

        $vendorMirror = "{$this->sandbox}/vendor-mirror/src";
        mkdir($vendorMirror, 0755, true);
        copy("{$source}/Sentinel.php", "{$vendorMirror}/Sentinel.php");

        // Simulate a Composer cleanup deleting the ENTIRE vendor-side
        // install — exactly what happened to the real Junction target in
        // the Phase 6.5E incident, except here vendor-mirror is a real,
        // independent copy (mirror mode), so this must be harmless.
        $this->removeDisposableSandbox("{$this->sandbox}/vendor-mirror");

        self::assertFileExists("{$source}/Sentinel.php");
        self::assertSame('<?php // sentinel', file_get_contents("{$source}/Sentinel.php"));
    }

    // ── TEST 2: Junction/link-like entry is recognized as link-like ──────

    public function test_windows_junction_is_recognized_as_link_like(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::markTestSkipped('Junction-specific behavior only applies on Windows.');
        }

        $target = "{$this->sandbox}/junction-target";
        mkdir($target, 0755, true);
        file_put_contents("{$target}/marker.txt", 'real content');

        $junction = "{$this->sandbox}/junction-link";
        exec('mklink /J ' . escapeshellarg(str_replace('/', '\\', $junction)) . ' ' . escapeshellarg(str_replace('/', '\\', $target)), $output, $exitCode);
        if ($exitCode !== 0) {
            self::markTestSkipped('Could not create a real Junction in this environment (mklink /J failed) — nothing to verify detection against.');
        }

        self::assertTrue(ComposerPackageSafety::isLinkLikeEntry($junction), 'a real Junction must be detected as link-like');
        self::assertFalse(ComposerPackageSafety::isLinkLikeEntry($target), 'an ordinary directory must NOT be detected as link-like');

        // Clean up the Junction POINTER only (rmdir on a Junction removes
        // just the reparse point, never its target) — proves the safe
        // removal path too, matching ComposerPackageSafety's own intended
        // use.
        @rmdir($junction);
        self::assertFileExists("{$target}/marker.txt", 'removing the Junction pointer must never touch its real target');
    }

    // ── TEST 3: sibling-worktree-style layout cannot alias canonical source ──

    public function test_sibling_layout_style_paths_produce_independent_copies_not_aliases(): void
    {
        $realSource = "{$this->sandbox}/saas-platform-fake/pkg";
        mkdir($realSource, 0755, true);
        file_put_contents("{$realSource}/Sentinel.php", '<?php // v1');

        // Two "consumers" at the same relative depth next to the fake
        // source — exactly the Phase 6.5E layout shape (canonical +
        // sibling worktree both resolving "../saas-platform-fake/pkg").
        foreach (['canonical-consumer', 'sibling-worktree-consumer'] as $consumer) {
            $vendorCopy = "{$this->sandbox}/{$consumer}/vendor/pkg";
            mkdir($vendorCopy, 0755, true);
            // Mirror-mode behavior: a real, independent copy — never a
            // link back to $realSource.
            copy("{$realSource}/Sentinel.php", "{$vendorCopy}/Sentinel.php");
            self::assertFalse(ComposerPackageSafety::isLinkLikeEntry($vendorCopy));
        }

        // Deleting BOTH consumers' vendor copies must leave the shared
        // source completely untouched.
        $this->removeDisposableSandbox("{$this->sandbox}/canonical-consumer");
        $this->removeDisposableSandbox("{$this->sandbox}/sibling-worktree-consumer");

        self::assertFileExists("{$realSource}/Sentinel.php");
        self::assertSame('<?php // v1', file_get_contents("{$realSource}/Sentinel.php"));
    }

    // ── TEST 4: real customer-site composer.json declares mirror mode unconditionally ──

    public function test_real_composer_json_declares_mirror_mode_for_every_path_repository(): void
    {
        $repos = ComposerPackageSafety::parsePathRepositories(base_path_for_test('composer.json'));

        self::assertNotEmpty($repos, 'expected at least the 7 saas-platform path repositories to be declared');
        self::assertSame([], ComposerPackageSafety::verifyMirrorModeDeclared($repos), 'every path repository must declare "symlink": false — configuration is context-independent (canonical, worktree, fresh clone all get the same safe behavior)');
    }

    // ── TEST 5: canonical package consumption still resolves ────────────

    public function test_canonical_package_classes_still_resolve(): void
    {
        require_once base_path_for_test('vendor/autoload.php');

        self::assertTrue(class_exists(\SaaSPlatform\CoreUtils\Providers\CoreUtilsServiceProvider::class));
        self::assertTrue(class_exists(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class));
    }

    // ── TEST 6: all seven path repositories resolve to real directories ──

    public function test_all_seven_path_repositories_resolve_to_real_directories(): void
    {
        $projectRoot = dirname(base_path_for_test('composer.json'));
        $repos = ComposerPackageSafety::parsePathRepositories(base_path_for_test('composer.json'));

        self::assertCount(7, $repos, 'expected exactly the 7 known saas-platform path repositories');
        self::assertSame([], ComposerPackageSafety::verifyDeclaredPathsResolve($projectRoot, $repos));
    }

    // ── TEST 7: no duplicate package source authority ────────────────────

    public function test_no_duplicate_package_source_tree_exists_inside_customer_site(): void
    {
        $projectRoot = dirname(base_path_for_test('composer.json'));

        self::assertDirectoryDoesNotExist(
            "{$projectRoot}/packages/saas-platform",
            'customer-site must never contain its own copy of saas-platform package SOURCE — saas-platform remains the sole canonical owner; customer-site only ever consumes via vendor/ (a mirror-mode copy, not a second source authority).',
        );
    }

    // ── TEST 8: unsafe external/absolute path target is rejected ────────

    public function test_absolute_path_repository_is_rejected(): void
    {
        $composerJsonPath = "{$this->sandbox}/composer.json";
        file_put_contents($composerJsonPath, json_encode([
            'repositories' => [
                ['type' => 'path', 'url' => 'C:\\Windows\\System32', 'options' => ['symlink' => false]],
            ],
        ]));

        $repos = ComposerPackageSafety::parsePathRepositories($composerJsonPath);
        $violations = ComposerPackageSafety::verifyNoAbsolutePaths($repos);

        self::assertNotEmpty($violations);
        self::assertStringContainsString('absolute path', $violations[0]);
    }

    // ── TEST 9: fresh-clone-style missing sibling produces a deterministic, specific failure ──

    public function test_a_missing_sibling_checkout_produces_a_deterministic_named_failure(): void
    {
        $composerJsonPath = "{$this->sandbox}/composer.json";
        file_put_contents($composerJsonPath, json_encode([
            'repositories' => [
                ['type' => 'path', 'url' => '../saas-platform-does-not-exist-here/pkg', 'options' => ['symlink' => false]],
            ],
        ]));

        $repos = ComposerPackageSafety::parsePathRepositories($composerJsonPath);
        $violations = ComposerPackageSafety::verifyDeclaredPathsResolve($this->sandbox, $repos);

        self::assertCount(1, $violations);
        self::assertStringContainsString('saas-platform-does-not-exist-here/pkg', $violations[0]);
        self::assertStringContainsString('does not resolve to a real directory', $violations[0]);
    }
}

if (!function_exists(__NAMESPACE__ . '\\base_path_for_test')) {
    function base_path_for_test(string $relative = ''): string
    {
        $root = dirname(__DIR__, 2);

        return $relative === '' ? $root : "{$root}/{$relative}";
    }
}
