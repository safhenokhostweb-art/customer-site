<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use Illuminate\Support\Facades\Http;

/**
 * نمایش محصولات پلتفرم (لایسنس، ماژول، پشتیبانی) در سایت مالک.
 * محصولات از API پلتفرم دریافت و لینک خرید به پنل داده می‌شود.
 */
class PlatformProductsController extends Controller
{
    public function index()
    {
        if (! SiteHelper::isOwnerSite()) {
            abort(404);
        }

        $platformUrl = rtrim(config('site.platform_url', ''), '/');
        if ($platformUrl === '') {
            return view('platform-products.empty', ['message' => 'PLATFORM_URL تنظیم نشده است.']);
        }

        try {
            $response = Http::timeout(10)->get($platformUrl . '/api/products');
            $data = $response->successful() ? $response->json() : [];
        } catch (\Throwable $e) {
            $data = ['products' => []];
        }

        $products = $data['products'] ?? [];
        $storeUrl = $platformUrl . '/store';

        return view('platform-products.index', compact('products', 'storeUrl', 'platformUrl'));
    }
}
