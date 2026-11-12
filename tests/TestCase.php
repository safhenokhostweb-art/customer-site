<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private bool $lockExistedBefore = false;

    protected function setUp(): void
    {
        parent::setUp(); // bootstrap app first — storage_path() is not available before this
        $this->ensureViteManifest();
        $this->lockExistedBefore = file_exists(storage_path('installed.lock'));
    }

    /**
     * A clean checkout (CI, fresh clone) has no public/build, and @vite
     * throws ViteManifestNotFoundException without a manifest. Building real
     * assets in the test job is slow and pointless: tests only assert that
     * the platform entries render, so a stub manifest with those entries is
     * enough. A real local build is left untouched.
     */
    private function ensureViteManifest(): void
    {
        $manifest = public_path('build/manifest.json');
        if (file_exists($manifest) || file_exists(public_path('hot'))) {
            return;
        }

        if (! is_dir(dirname($manifest))) {
            mkdir(dirname($manifest), 0777, true);
        }

        file_put_contents($manifest, json_encode([
            'resources/css/app.css' => ['file' => 'assets/app-test.css', 'src' => 'resources/css/app.css', 'isEntry' => true],
            'resources/js/app.js' => ['file' => 'assets/app-test.js', 'src' => 'resources/js/app.js', 'isEntry' => true],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function tearDown(): void
    {
        $shouldRestore = $this->lockExistedBefore && ! file_exists(storage_path('installed.lock'));
        parent::tearDown();
        if ($shouldRestore) {
            file_put_contents(storage_path('installed.lock'), '1');
        }
    }
}
