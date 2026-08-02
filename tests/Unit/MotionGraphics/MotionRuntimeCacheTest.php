<?php

namespace Tests\Unit\MotionGraphics;

use App\Services\MotionGraphics\MotionRuntimeCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MotionRuntimeCacheTest extends TestCase
{
    public function test_it_caches_the_callback_result(): void
    {
        Cache::flush();
        $cache = new MotionRuntimeCache();
        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
            return ['manifest_checksum' => 'abc'];
        };

        $first = $cache->remember($callback);
        $second = $cache->remember($callback);

        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function test_forget_clears_the_cached_value(): void
    {
        Cache::flush();
        $cache = new MotionRuntimeCache();
        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
            return ['manifest_checksum' => (string) $calls];
        };

        $cache->remember($callback);
        $cache->forget();
        $cache->remember($callback);

        $this->assertSame(2, $calls);
    }
}
