<?php

namespace Tests\Unit\MotionGraphics;

use App\Services\MotionGraphics\MotionRuntimeAssetResolver;
use Tests\TestCase;

class MotionRuntimeAssetResolverTest extends TestCase
{
    public function test_it_resolves_an_asset_by_id(): void
    {
        $manifest = ['assets' => [
            ['asset_id' => 'motion-1', 'checksum' => 'a'],
            ['asset_id' => 'motion-2', 'checksum' => 'b'],
        ]];

        $resolver = new MotionRuntimeAssetResolver();

        $this->assertSame('a', $resolver->resolve($manifest, 'motion-1')['checksum']);
        $this->assertSame('b', $resolver->resolve($manifest, 'motion-2')['checksum']);
    }

    public function test_it_returns_null_for_an_unknown_asset_id(): void
    {
        $manifest = ['assets' => [['asset_id' => 'motion-1']]];
        $resolver = new MotionRuntimeAssetResolver();

        $this->assertNull($resolver->resolve($manifest, 'motion-missing'));
    }

    public function test_it_returns_null_for_a_null_manifest(): void
    {
        $resolver = new MotionRuntimeAssetResolver();

        $this->assertNull($resolver->resolve(null, 'motion-1'));
    }

    public function test_it_returns_null_for_an_empty_assets_list(): void
    {
        $resolver = new MotionRuntimeAssetResolver();

        $this->assertNull($resolver->resolve(['assets' => []], 'motion-1'));
    }
}
