<?php

namespace App\Http\Middleware;

use App\Services\ThemeCanvas\LayoutSlotBindingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Canvas Slot Binding — live-page wiring (Phase 11 follow-up).
 *
 * The runtime document renders the theme's chrome. This response layer
 * annotates legacy chrome while preserving explicit root slot bindings
 * (see LayoutSlotBindingService::decorateChrome()).
 *
 * FLAG: config('canvas.slot_binding_enabled') (default true) — flipping it
 * off restores the previous response bytes with zero code change.
 */
class DecorateCanvasLayoutSlots
{
    public function __construct(
        private readonly LayoutSlotBindingService $slots,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('canvas.slot_binding_enabled', true)) {
            return $response;
        }

        // Only canvas-theme HTML responses — nothing else is ever rewritten.
        if (! $this->isEligibleHtmlResponse($response)) {
            return $response;
        }

        $original = $response->getContent();
        if (! is_string($original) || $original === '') {
            return $response;
        }

        $decorated = $this->slots->decorateChrome($original);
        if ($decorated !== $original) {
            $response->setContent($decorated);
            // The transformed response no longer has the original byte length.
            $response->headers->remove('Content-Length');
            $response->headers->remove('ETag');
        }

        return $response;
    }

    private function isEligibleHtmlResponse(Response $response): bool
    {
        if (! $response->isSuccessful()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return false;
        }

        // Canvas theme chrome responses carry the public runtime bootstrap
        // (theme-public-runtime.blade.php → ThemeCanvasRuntime). Restricting
        // on this marker keeps admin/editor/other-template pages untouched.
        return str_contains((string) $response->getContent(), 'window.ThemeCanvasRuntime');
    }
}
