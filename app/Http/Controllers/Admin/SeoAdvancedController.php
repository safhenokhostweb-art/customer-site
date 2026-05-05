<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\PlatformUx;
use App\Helpers\SiteHelper;
use App\Models\License;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;

/**
 * صفحهٔ اختصاصی ماژول «سئو پیشرفته» در پنل ادمین سایت مشتری.
 * این صفحه فقط وقتی قابل‌دسترس است که ماژول seo_advanced برای سایت از طرف پلتفرم فعال شده باشد.
 */
class SeoAdvancedController extends Controller
{
    public function index()
    {
        $license = License::current();
        $enabled = SiteHelper::moduleEnabled('seo_advanced');

        $seoConfig = PlatformUx::seo();

        $pagesCount = Page::count();
        $publishedPagesCount = Page::published()->count();

        $postsCount = Post::count();
        $publishedPostsCount = Post::published()->count();

        $productsCount = Product::count();
        $activeProductsCount = Product::active()->count();

        return view('admin.seo-advanced.index', compact(
            'license',
            'enabled',
            'seoConfig',
            'pagesCount',
            'publishedPagesCount',
            'postsCount',
            'publishedPostsCount',
            'productsCount',
            'activeProductsCount'
        ));
    }
}

