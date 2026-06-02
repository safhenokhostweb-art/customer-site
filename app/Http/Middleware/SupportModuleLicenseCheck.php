<?php

namespace App\Http\Middleware;

use App\Services\SupportModuleLicenseGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PHASE M3.2 — Blocks support routes when the support module license is inactive.
 *
 * Behavior when inactive:
 * - API requests → 403 JSON
 * - Web requests → redirect to dashboard with error flash
 *
 * Existing support data is NEVER deleted — only runtime access is blocked.
 */
class SupportModuleLicenseCheck
{
    public function __construct(
        private SupportModuleLicenseGate $gate
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->gate->isModuleEnabled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'support_module_inactive',
                    'message' => 'ماژول پشتیبانی برای این سایت فعال نیست.',
                ], 403);
            }

            return redirect()->route('dashboard')->with(
                'error',
                'ماژول پشتیبانی در لایسنس شما فعال نشده است. لطفاً با پشتیبانی تماس بگیرید.'
            );
        }

        return $next($request);
    }
}
