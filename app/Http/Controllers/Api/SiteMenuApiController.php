<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use App\Services\PlatformUxService;
use Illuminate\Http\JsonResponse;

/**
 * JSON ساخت‌یافته برای مصرف headless (منوهای منتشرشده).
 */
class SiteMenuApiController extends Controller
{
    public function __construct(
        protected MenuService $menuService,
        protected PlatformUxService $platformUx
    ) {}

    public function index(): JsonResponse
    {
        if (! \App\Helpers\SiteHelper::moduleEnabled('menu')) {
            abort(404);
        }
        $menus = $this->platformUx->getMenus();
        $menus = $this->platformUx->enrichMenuPayload($menus);

        return response()->json([
            'menus' => $menus,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
