<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private bool $lockExistedBefore = false;

    protected function setUp(): void
    {
        parent::setUp(); // bootstrap app first — storage_path() is not available before this
        // A clean checkout has no public/build, so any admin page that calls
        // @vite would fail without this. Tests never assert on built assets.
        $this->withoutVite();
        $this->lockExistedBefore = file_exists(storage_path('installed.lock'));
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
