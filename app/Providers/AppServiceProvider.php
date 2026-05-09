<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);

        View::composer('themes.default.layout', function ($view) {
            $data = $view->getData();
            if (!isset($data['pages']) && \Illuminate\Support\Facades\Schema::hasTable('pages')) {
                $view->with('pages', Page::published()->menu()->get());
            }
            // Page-level header/footer override: pass current page when on page.show
            if (!array_key_exists('currentPage', $data)) {
                $currentPage = null;
                if (isset($data['page']) && $data['page'] instanceof Page) {
                    $currentPage = $data['page'];
                } elseif (request()->routeIs('page.show') && request()->route('slug')) {
                    $currentPage = Page::published()->where('slug', request()->route('slug'))->first();
                }
                $view->with('currentPage', $currentPage);
            }
        });
    }
}
