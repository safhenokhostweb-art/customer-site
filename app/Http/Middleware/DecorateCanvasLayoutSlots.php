<?php

namespace App\Http\Middleware;

use App\Services\ThemeCanvas\LayoutSlotBindingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Canvas Slot Binding — live-page wiring (Phase 11 follow-up).
 *
 * WHY MIDDLEWARE (and not ThemeRuntimeDocument / canvas-theme.blade.php):
 *   Both of those files currently carry the collaborator's uncommitted WIP;
 *   editing them would entangle two in-flight changesets. The response-side
 *   layer achieves the exact same result without touching them: the runtime
 *   document already rendered the Theme's real chrome — this middleware only
 *   adds the data-layout-slot="header|footer" annotation to the chrome tags
 *   of canvas-theme responses (content-preserving; see
 *   LayoutSlotBindingService::decorateChrome()).
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
        if (! is_string($original) || $original === '' || str_contains($original, 'data-layout-slot=')) {
            return $response;
        }

        $decorated = $this->slots->decorateChrome($original);
        if ($decorated !== $original) {
            $response->setContent($decorated);
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
