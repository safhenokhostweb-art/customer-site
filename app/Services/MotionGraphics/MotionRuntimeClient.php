<?php

namespace App\Services\MotionGraphics;

use App\Models\License;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class MotionRuntimeClient
{
    public function published(string $name): ?array
    {
        if (! preg_match('/^[a-z0-9-]+$/', $name)) return null;
        $license = License::current();
        $platform = rtrim((string) config('site.platform_url'), '/');
        if (! $license?->license_key || $platform === '') return null;

        return Cache::remember('motion.runtime.'.sha1($name), 300, function () use ($name, $license, $platform): ?array {
            $response = Http::withToken($license->license_key)->acceptJson()->timeout(8)
                ->get($platform.'/api/motion/'.$name, ['domain' => $license->domain ?: request()->getHost()]);
            if (! $response->successful()) return null;
            $manifest = $response->json();
            if (($manifest['name'] ?? null) !== $name || ! is_int($manifest['version'] ?? null)) return null;

            $prefix = $platform.'/assets/motion/'.$name.'/v'.$manifest['version'].'/';
            $assets = collect($manifest['assets'] ?? [])->filter(fn ($asset) =>
                isset($asset['url'], $asset['sha256'], $asset['mime'])
                && str_starts_with($asset['url'], '/assets/motion/'.$name.'/v'.$manifest['version'].'/')
                && preg_match('/^[a-f0-9]{64}$/', $asset['sha256'])
            )->map(fn ($asset) => [...$asset, 'url' => $platform.$asset['url']])->values()->all();

            return ['name' => $name, 'version' => $manifest['version'], 'assets' => $assets, 'asset_prefix' => $prefix];
        });
    }
}
