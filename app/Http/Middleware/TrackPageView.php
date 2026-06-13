<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsPageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    private array $skipPaths = [
        'admin', 'api', '_ignition', 'horizon', 'telescope',
        'canvas-preview', 'canvas/', 'livewire', 'sanctum',
        'broadcasting', 'storage', 'lang/', 'smart-image',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->record($request);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (!$request->isMethod('GET')) return false;
        if ($response->getStatusCode() !== 200) return false;
        if ($request->ajax() || $request->expectsJson()) return false;

        $path = ltrim($request->path(), '/');
        foreach ($this->skipPaths as $skip) {
            if (str_starts_with($path, $skip)) return false;
        }

        // Rate-limit: one record per session+path per 30 minutes
        $cacheKey = 'pv:' . session()->getId() . ':' . md5($path);
        if (cache()->has($cacheKey)) return false;
        cache()->put($cacheKey, 1, now()->addMinutes(30));

        return true;
    }

    private function record(Request $request): void
    {
        try {
            $ua = $request->userAgent() ?? '';

            AnalyticsPageView::create([
                'path'        => substr($request->path(), 0, 500),
                'referrer'    => substr($request->header('Referer', ''), 0, 500) ?: null,
                'session_id'  => substr(session()->getId(), 0, 64),
                'customer_id' => auth()->id(),
                'device'      => $this->detectDevice($ua),
                'browser'     => $this->detectBrowser($ua),
                'visited_at'  => now(),
            ]);
        } catch (\Throwable) {
            // Never crash the request for analytics
        }
    }

    private function detectDevice(string $ua): string
    {
        if (preg_match('/Mobile|Android|iPhone/i', $ua)) return 'mobile';
        if (preg_match('/iPad|Tablet/i', $ua)) return 'tablet';
        return 'desktop';
    }

    private function detectBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg/')) return 'Edge';
        if (str_contains($ua, 'Chrome')) return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari')) return 'Safari';
        return 'Other';
    }
}
