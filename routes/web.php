<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\PageController as FrontPageController;
use App\Http\Controllers\Front\ThemeCanvasPageController;
use App\Http\Controllers\Front\BlogController;
use App\Http\Controllers\Front\ShopController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\PostSeriesController as AdminPostSeriesController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductAttributeController as AdminProductAttributeController;
use App\Http\Controllers\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Admin\ShopProductReviewController as AdminShopProductReviewController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\ModuleChangeLogController as AdminModuleChangeLogController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\UnifiedSupportController as AdminUnifiedSupportController;
use App\Http\Controllers\GuideSearchController;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\Front\PlatformProductsController;
use App\Http\Controllers\Front\SiteContentController as FrontSiteContentController;
use App\Http\Controllers\Front\AdvancedSearchController;
use App\Http\Controllers\Front\PageBuilderController;
use App\Http\Controllers\Front\InvoiceController as FrontInvoiceController;
use App\Http\Controllers\Front\BusinessCardController as FrontBusinessCardController;
use App\Http\Controllers\Front\CourseController as FrontCourseController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\MessagingController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\BusinessCardController;
use App\Http\Controllers\Admin\SocialLinkController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\SeoAdvancedController;
use App\Http\Controllers\Admin\PlatformModuleController;
use SaaSPlatform\Modules\Forms\Http\Controllers\FormController;
use SaaSPlatform\Modules\Forms\Http\Controllers\FormSubmissionController;
use SaaSPlatform\Modules\Forms\Http\Controllers\FormBuilderController;
use SaaSPlatform\Modules\Forms\Http\Controllers\FormAnalyticsController;
use SaaSPlatform\Modules\Forms\Http\Controllers\FormAutomationController;
use SaaSPlatform\Modules\Forms\Http\Controllers\FormTemplateController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\Admin\CurrencyReformController;
use App\Http\Controllers\Admin\CaptchaController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Api\SiteMenuApiController;
use App\Http\Controllers\Admin\ThemeDesignController;
use App\Http\Controllers\Admin\ThemeMarketplaceController as AdminThemeMarketplaceController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\TicketController as AccountTicketController;
use App\Http\Controllers\Admin\UserTicketController as AdminUserTicketController;
use App\Http\Controllers\Admin\UserPanelController as AdminUserPanelController;
use App\Http\Controllers\Admin\ConversationMergePermissionController as AdminConversationMergePermissionController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\SubscriptionController as AccountSubscriptionController;
use App\Http\Controllers\Account\FormController as AccountFormController;
use App\Http\Controllers\Account\NotificationController as AccountNotificationController;
use App\Http\Controllers\Account\SecurityController as AccountSecurityController;
use App\Http\Controllers\Account\ProfileController as AccountProfileController;
use App\Http\Controllers\Account\ProfileAvatarController as AccountProfileAvatarController;
use App\Http\Controllers\Account\ProfileAddressController as AccountProfileAddressController;
use App\Http\Controllers\Account\ProfileActivityController as AccountProfileActivityController;
use App\Http\Controllers\Account\ProfileDeletionController as AccountProfileDeletionController;
use App\Http\Controllers\Account\PointsController as AccountPointsController;
use App\Http\Controllers\Account\ReferralController as AccountReferralController;

/*
|--------------------------------------------------------------------------
| Install wizard (only when NOT installed; when installed, redirect to /)
|--------------------------------------------------------------------------
*/
Route::middleware('install.check')->group(function () {
    Route::get('/install', [InstallController::class, 'welcome'])->name('install.welcome');
    Route::get('/install/database', [InstallController::class, 'database'])->name('install.database');
    Route::post('/install/database', [InstallController::class, 'storeDatabase'])->name('install.store-database');
    Route::get('/install/admin', [InstallController::class, 'admin'])->name('install.admin');
    Route::post('/install/admin', [InstallController::class, 'storeAdmin'])->name('install.store-admin');
    Route::get('/install/settings', [InstallController::class, 'settings'])->name('install.settings');
    Route::post('/install/settings', [InstallController::class, 'storeSettings'])->name('install.store-settings');
    Route::get('/install/done', [InstallController::class, 'done'])->name('install.done');
});

/*
|--------------------------------------------------------------------------
| Main app (redirect to /install when not installed)
|--------------------------------------------------------------------------
*/
    Route::middleware('redirect.if.not.installed')->group(function () {

    /*
    | Passthrough برای callback بانک — بانک کاربر را به این آدرس برمی‌گرداند،
    | سپس به پلتفرم فوروارد می‌شود. فقط وقتی این سایت روی دامنهٔ اصلی
    | (مثلاً safhenokhost.ir) است و PAYMENT_CALLBACK_URL در پلتفرم به این آدرس اشاره دارد.
    */
    Route::get('/payment/callback', PaymentCallbackController::class)->name('payment.callback');
    Route::get('/site-api/menus', [SiteMenuApiController::class, 'index'])->name('site-api.menus');

    // Auth
    Route::get('/login',    [LoginController::class,   'showLoginForm'])->name('login');
    Route::post('/login',   [LoginController::class,   'login']);
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register')->middleware('guest');
    Route::post('/register',[RegisterController::class, 'register'])->middleware('guest');
    Route::post('/logout',  [LoginController::class,   'logout'])->name('logout')->middleware('auth');

    // End User Dashboard (حساب من)
    Route::prefix('account')->name('account.')->middleware('auth')->group(function () {
        Route::get('/', AccountDashboardController::class)->name('dashboard');
        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
        Route::get('/subscriptions', [AccountSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/plans', [AccountSubscriptionController::class, 'plans'])->name('subscriptions.plans');
        Route::post('/subscriptions/subscribe', [AccountSubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
        Route::post('/subscriptions/cancel-pending', [AccountSubscriptionController::class, 'cancelPending'])->name('subscriptions.cancel-pending');
        Route::delete('/subscriptions/{subscription}/delete-pending', [AccountSubscriptionController::class, 'deletePending'])->name('subscriptions.delete-pending');
        Route::get('/subscriptions/{subscription}', [AccountSubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('/subscriptions/{subscription}/cancel', [AccountSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('/subscriptions/{subscription}/toggle-auto-renew', [AccountSubscriptionController::class, 'toggleAutoRenew'])->name('subscriptions.toggle-auto-renew');
        Route::get('/subscriptions/{subscription}/upgrade-plans', [AccountSubscriptionController::class, 'upgradePlans'])->name('subscriptions.upgrade-plans');
        Route::post('/subscriptions/{subscription}/upgrade', [AccountSubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
        // Group Subscription
        Route::get('/subscriptions/{subscription}/members', [\App\Http\Controllers\Account\GroupSubscriptionController::class, 'index'])->name('subscriptions.members');
        Route::post('/subscriptions/{subscription}/members/invite', [\App\Http\Controllers\Account\GroupSubscriptionController::class, 'invite'])->name('subscriptions.members.invite');
        Route::post('/subscriptions/{subscription}/members/{member}/remove', [\App\Http\Controllers\Account\GroupSubscriptionController::class, 'remove'])->name('subscriptions.members.remove');
        Route::post('/subscriptions/invites/{member}/accept', [\App\Http\Controllers\Account\GroupSubscriptionController::class, 'accept'])->name('subscriptions.invites.accept');
        Route::post('/subscriptions/invites/{member}/reject', [\App\Http\Controllers\Account\GroupSubscriptionController::class, 'reject'])->name('subscriptions.invites.reject');
        Route::get('/forms', [AccountFormController::class, 'index'])->name('forms.index');
        Route::get('/notifications', [AccountNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [AccountNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [AccountNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::get('/security', [AccountSecurityController::class, 'index'])->name('security.index');
        Route::put('/security/password', [AccountSecurityController::class, 'updatePassword'])->name('security.password');
        Route::delete('/security/sessions/{sessionId}', [AccountSecurityController::class, 'destroySession'])->name('security.sessions.destroy');
        Route::get('/profile', [AccountProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [AccountProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/avatar', [AccountProfileAvatarController::class, 'update'])->name('profile.avatar.update');
        Route::delete('/profile/avatar', [AccountProfileAvatarController::class, 'destroy'])->name('profile.avatar.destroy');
        Route::get('/profile/addresses', [AccountProfileAddressController::class, 'index'])->name('profile.addresses.index');
        Route::post('/profile/addresses', [AccountProfileAddressController::class, 'store'])->name('profile.addresses.store');
        Route::put('/profile/addresses/{address}', [AccountProfileAddressController::class, 'update'])->name('profile.addresses.update');
        Route::patch('/profile/addresses/{address}/default', [AccountProfileAddressController::class, 'setDefault'])->name('profile.addresses.default');
        Route::delete('/profile/addresses/{address}', [AccountProfileAddressController::class, 'destroy'])->name('profile.addresses.destroy');
        Route::get('/profile/activity', [AccountProfileActivityController::class, 'index'])->name('profile.activity.index');
        Route::get('/points', [AccountPointsController::class, 'index'])->name('points.index');
        Route::get('/referrals', [AccountReferralController::class, 'index'])->name('referrals.index');
        Route::get('/profile/deletion', [AccountProfileDeletionController::class, 'index'])->name('profile.deletion.index');
        Route::post('/profile/deletion', [AccountProfileDeletionController::class, 'store'])->name('profile.deletion.store');
        Route::post('/profile/deletion/cancel', [AccountProfileDeletionController::class, 'cancel'])->name('profile.deletion.cancel');

        // اقساط کاربر
        Route::middleware('module:shop')->group(function () {
            Route::get('/installments', [\App\Http\Controllers\Account\InstallmentAccountController::class, 'index'])->name('installments');
            Route::get('/installments/{order}', [\App\Http\Controllers\Account\InstallmentAccountController::class, 'show'])->name('installments.show');
        });

        // تیکت‌های پشتیبانی کاربر نهایی
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                     [AccountTicketController::class, 'index'])->name('index');
            Route::get('/create',               [AccountTicketController::class, 'create'])->name('create');
            Route::post('/',                    [AccountTicketController::class, 'store'])->name('store');
            Route::get('/check-activity',       [AccountTicketController::class, 'checkActivity'])->name('check-activity');
            Route::get('/{id}',                 [AccountTicketController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/poll',            [AccountTicketController::class, 'poll'])->whereNumber('id')->name('poll');
            Route::post('/{id}/reply',          [AccountTicketController::class, 'reply'])->whereNumber('id')->name('reply');
        });
    });

    // Canvas draft preview (token-gated, no auth required)
    Route::get('/canvas-preview', [ThemeDesignController::class, 'canvasPreviewPublic'])->name('canvas.preview');

    // Front: home + pages — ThemeCanvasPageController delegates to HomeController/FrontPageController
    // when no active canvas theme is installed (active_canvas_theme_data setting is empty).
    Route::get('/', [ThemeCanvasPageController::class, 'index'])->name('home');
    Route::get('/page/{slug}', [ThemeCanvasPageController::class, 'show'])->name('page.show');
    Route::middleware(['auth', 'admin'])->prefix('builder')->name('builder.')->group(function () {
        Route::get('/test', [PageBuilderController::class, 'test'])->name('test');
        Route::get('/edit/{page}', [PageBuilderController::class, 'edit'])->name('edit')->whereNumber('page');
        Route::get('/page/{page}/edit', [PageBuilderController::class, 'edit'])->name('page.edit');
        Route::get('/page/{page}', [PageBuilderController::class, 'show'])->name('page.show');
        Route::put('/page/{page}', [PageBuilderController::class, 'update'])->name('page.update');
        Route::post('/preview', [PageBuilderController::class, 'preview'])->name('preview');
        Route::get('/templates', [PageBuilderController::class, 'templates'])->name('templates.index');
        Route::post('/templates', [PageBuilderController::class, 'saveTemplate'])->name('templates.store');
    });
    Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
    Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');
    Route::get('/guide-search', [GuideSearchController::class, 'index'])->name('guide.search');
    Route::middleware('module:advanced_search')->group(function () {
        Route::get('/search', [AdvancedSearchController::class, 'index'])->name('search.index');
        Route::get('/api/search/suggest', [AdvancedSearchController::class, 'suggest'])->name('search.suggest');
        Route::get('/api/search/results', [AdvancedSearchController::class, 'results'])->name('search.results');
    });
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
    Route::get('/sitemap-part-{index}.xml', [SitemapController::class, 'part'])->name('sitemap.part')->whereNumber('index');
    Route::middleware('module:invoice')->group(function () {
        Route::get('/invoice/{invoiceNumber}', [FrontInvoiceController::class, 'show'])->name('invoice.show');
        Route::get('/invoice/{invoiceNumber}/pay', [FrontInvoiceController::class, 'pay'])->name('invoice.pay');
        Route::get('/invoice/{invoiceNumber}/pdf', [FrontInvoiceController::class, 'download'])->name('invoice.download');
    });
    Route::middleware(['module:subscription', 'auth'])->group(function () {
        Route::get('/subscription/pay/{invoiceNumber}', [\App\Http\Controllers\Front\SubscriptionPaymentController::class, 'pay'])->name('subscriptions.pay');
        Route::post('/subscription/confirm/{invoiceNumber}', [\App\Http\Controllers\Front\SubscriptionPaymentController::class, 'confirm'])->name('subscriptions.confirm');
        Route::get('/subscription/callback/{invoiceNumber}', [\App\Http\Controllers\Front\SubscriptionPaymentController::class, 'callback'])->name('subscriptions.callback');
    });
    Route::middleware('module:subscription')->group(function () {
        Route::get('/plans', [\App\Http\Controllers\Front\PricingController::class, 'index'])->name('pricing.index');
    });
    Route::middleware('module:business_card')->group(function () {
        Route::get('/card/{slug}', [FrontBusinessCardController::class, 'show'])->name('business-card.show');
    });

    // محصولات پلتفرم (لایسنس، ماژول، پشتیبانی) — فقط برای سایت مالک
    Route::get('/platform-products', [PlatformProductsController::class, 'index'])->name('platform-products.index');

    // ─── خرید قسطی (front) ───────────────────────────────────────────────────
    Route::middleware(['module:shop', 'auth'])->group(function () {
        Route::get('/installment/plans',   [\App\Http\Controllers\Front\InstallmentController::class, 'plans'])->name('installment.plans');
        Route::post('/installment/initiate', [\App\Http\Controllers\Front\InstallmentController::class, 'initiate'])->name('installment.initiate');
        Route::get('/installment/callback/{installmentOrderId}', [\App\Http\Controllers\Front\InstallmentController::class, 'downPaymentCallback'])->name('installment.down-payment.callback');
        Route::get('/installment/{installmentOrderId}/pay/{paymentId}', [\App\Http\Controllers\Front\InstallmentController::class, 'payInstallment'])->name('installment.pay');
        Route::get('/installment/{installmentOrderId}/callback/{paymentId}', [\App\Http\Controllers\Front\InstallmentController::class, 'installmentCallback'])->name('installment.payment.callback');
    });

    // ─── گیت‌های دسترسی (احراز هویت از طریق کد ملی / نام خانوادگی) ───────────
    Route::get('/gate/{slug}',  [\App\Http\Controllers\Front\AccessGateController::class, 'show'])->name('access-gate.show');
    Route::post('/gate/{slug}', [\App\Http\Controllers\Front\AccessGateController::class, 'verify'])->name('access-gate.verify');

    // ─── تغییر زبان سایت ─────────────────────────────────────────────────────
    Route::get('/lang/{locale}', function (string $locale) {
        $supported = ['fa', 'ar', 'en'];
        if (in_array($locale, $supported)) {
            session(['site_locale' => $locale]);
        }
        return redirect()->back()->withInput();
    })->where('locale', 'fa|ar|en')->name('lang.switch');

    // ─── دستیار محتوا — نمایش عمومی محتوای منتشرشده ──────────────────────────
    Route::get('/content',        [FrontSiteContentController::class, 'index'])->name('content.index');
    Route::get('/content/{slug}', [FrontSiteContentController::class, 'show'])->name('content.show');

    Route::middleware('module:blog')->group(function () {
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
        Route::get('/blog/series/{slug}', [BlogController::class, 'series'])->name('blog.series');
        Route::post('/blog/{slug}/like', [BlogController::class, 'like'])->name('blog.like');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    });

    Route::middleware('module:courses')->group(function () {
        Route::get('/courses', [FrontCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{slug}', [FrontCourseController::class, 'show'])->name('courses.show');
        Route::middleware('auth')->group(function () {
            Route::post('/courses/{slug}/enroll', [FrontCourseController::class, 'enroll'])->name('courses.enroll');
            Route::get('/my-learning', [FrontCourseController::class, 'myLearning'])->name('courses.my-learning');
            Route::get('/courses/{slug}/learn/{classOrder?}', [FrontCourseController::class, 'learn'])->name('courses.learn');
            Route::post('/courses/{slug}/learn/{classId}/complete', [FrontCourseController::class, 'markComplete'])->name('courses.class.complete');
        });
    });

    Route::middleware('module:shop')->group(function () {
        Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
        Route::get('/shop/compare', [ShopController::class, 'compare'])->name('shop.compare');
        Route::post('/shop/compare/toggle/{product}', [ShopController::class, 'compareToggle'])->middleware('throttle:shop-cart')->name('shop.compare.toggle');
        Route::get('/shop/wishlist', [ShopController::class, 'wishlist'])->name('shop.wishlist');
        Route::post('/shop/wishlist/toggle/{product}', [ShopController::class, 'wishlistToggle'])->middleware('throttle:shop-cart')->name('shop.wishlist.toggle');
        // Phase 5 / Wave 5.3 / P1-5: rate-limited shop write endpoints (limiters in AppServiceProvider).
        Route::post('/shop/stock-notify', [ShopController::class, 'stockNotify'])->middleware('throttle:shop-stock-notify')->name('shop.stock-notify');
        Route::post('/shop/{slug}/reviews', [ShopController::class, 'storeReview'])->middleware('throttle:shop-review')->name('shop.reviews.store');
        Route::get('/shop/cart', [ShopController::class, 'cart'])->name('shop.cart');
        Route::post('/shop/cart/add', [ShopController::class, 'addToCart'])->middleware('throttle:shop-cart')->name('shop.cart.add');
        Route::post('/shop/cart/update', [ShopController::class, 'updateCart'])->middleware('throttle:shop-cart')->name('shop.cart.update');
        Route::post('/shop/cart/remove', [ShopController::class, 'removeFromCart'])->middleware('throttle:shop-cart')->name('shop.cart.remove');
        Route::post('/shop/checkout', [ShopController::class, 'checkout'])->middleware('throttle:shop-checkout')->name('shop.checkout');
        Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');
    });

    // Admin panel (Persian)
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'license.valid', 'permission:dashboard'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('image/upload', [ImageUploadController::class, 'store'])->name('image.upload');
        Route::get('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'update'])->name('profile.update');
        Route::middleware('permission:pages')->group(function () {
            Route::resource('pages', AdminPageController::class);
        });
        Route::middleware(['module:blog'])->group(function () {
            Route::middleware('permission:categories')->group(function () {
                Route::resource('categories', AdminCategoryController::class)->except('show');
            });
            Route::middleware('permission:posts')->group(function () {
                Route::get('posts/{post}/revisions', [AdminPostController::class, 'revisions'])->name('posts.revisions');
                Route::get('posts/{post}/preview',   [AdminPostController::class, 'preview'])->name('posts.preview');
                Route::resource('posts', AdminPostController::class)->except(['show']);
                Route::resource('post-series', AdminPostSeriesController::class)->except(['show']);
            });
            Route::middleware('permission:posts_moderate')->group(function () {
                Route::post('posts/{post}/approve', [AdminPostController::class, 'approve'])->name('posts.approve');
                Route::post('posts/{post}/reject', [AdminPostController::class, 'reject'])->name('posts.reject');
                Route::post('posts/{post}/revisions/{revision}/restore', [AdminPostController::class, 'restoreRevision'])->name('posts.revisions.restore');
            });
        });
        Route::middleware(['module:shop'])->group(function () {
            Route::middleware('permission:product_categories')->group(function () {
                Route::resource('product-categories', AdminProductCategoryController::class)->except('show');
            });
            Route::middleware('permission:products')->group(function () {
                Route::resource('products', AdminProductController::class);
                Route::resource('product-attributes', AdminProductAttributeController::class)->except(['show']);
                Route::get('shop-product-reviews', [AdminShopProductReviewController::class, 'index'])->name('shop-product-reviews.index');
                Route::patch('shop-product-reviews/{product_review}', [AdminShopProductReviewController::class, 'update'])->name('shop-product-reviews.update');
                Route::delete('shop-product-reviews/{product_review}', [AdminShopProductReviewController::class, 'destroy'])->name('shop-product-reviews.destroy');
            });
            Route::middleware('permission:orders')->group(function () {
                Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
                Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
                Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
            });
            Route::middleware('permission:coupon')->group(function () {
                Route::resource('coupons', CouponController::class);
            });
        });
        Route::middleware('permission:settings')->group(function () {
            Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [AdminSettingController::class, 'update'])->name('settings.update');
            Route::post('settings/favicon', [AdminSettingController::class, 'uploadFavicon'])->name('settings.favicon');
            Route::get('menus/{menu}/preview', [AdminMenuController::class, 'preview'])->name('menus.preview');
            Route::post('menus/{menu}/publish', [AdminMenuController::class, 'publish'])->name('menus.publish');
            Route::post('menus/{menu}/preview-token', [AdminMenuController::class, 'regeneratePreviewLink'])->name('menus.preview-token');
            Route::post('menus/{menu}/reorder-tree', [AdminMenuController::class, 'reorderTree'])->name('menus.reorder-tree');
            Route::post('menus/{menu}/items/{item}/copy-branch', [AdminMenuController::class, 'copyBranch'])->name('menus.items.copy-branch');
            Route::post('menus/{menu}/revisions/{revision}/restore', [AdminMenuController::class, 'restoreRevision'])->name('menus.revisions.restore');
            Route::resource('menus', AdminMenuController::class)->except('show');
            Route::post('menus/{menu}/items', [AdminMenuController::class, 'storeItem'])->name('menus.items.store');
            Route::put('menus/{menu}/items/{item}', [AdminMenuController::class, 'updateItem'])->name('menus.items.update');
            Route::delete('menus/{menu}/items/{item}', [AdminMenuController::class, 'destroyItem'])->name('menus.items.destroy');
            Route::post('menus/{menu}/reorder', [AdminMenuController::class, 'reorder'])->name('menus.reorder');
            Route::get('theme-design',               [ThemeDesignController::class, 'index'])->name('theme-design.index');
            Route::put('theme-design',               [ThemeDesignController::class, 'update'])->name('theme-design.update');
            Route::post('theme-design/preset',       [ThemeDesignController::class, 'activatePreset'])->name('theme-design.preset');
            Route::post('theme-design/reset-preset', [ThemeDesignController::class, 'resetPreset'])->name('theme-design.reset-preset');
            Route::get('theme-design/canvas',              [ThemeDesignController::class, 'canvasTheme'])->name('theme-design.canvas');
            Route::post('theme-design/canvas/deactivate',  [ThemeDesignController::class, 'canvasDeactivate'])->name('theme-design.canvas-deactivate');
            Route::get('theme-design/canvas/export',        [ThemeDesignController::class, 'canvasExport'])->name('theme-design.canvas-export');
            Route::post('theme-design/canvas/import',       [ThemeDesignController::class, 'canvasImport'])->name('theme-design.canvas-import');
            // Canvas Editor (drag-and-drop)
            Route::get('theme-design/canvas/edit',                        [ThemeDesignController::class, 'canvasEdit'])->name('theme-design.canvas-edit');
            Route::put('theme-design/canvas/save-blocks',                 [ThemeDesignController::class, 'canvasSaveBlocks'])->name('theme-design.canvas-save-blocks');
            Route::post('theme-design/canvas/pages',                      [ThemeDesignController::class, 'canvasAddPage'])->name('theme-design.canvas-add-page');
            Route::delete('theme-design/canvas/pages/{key}',              [ThemeDesignController::class, 'canvasDeletePage'])->name('theme-design.canvas-delete-page');
            Route::put('theme-design/canvas/global-styles',               [ThemeDesignController::class, 'canvasSaveGlobalStyles'])->name('theme-design.canvas-global-styles');
            Route::post('theme-design/canvas/publish',                    [ThemeDesignController::class, 'canvasPublish'])->name('theme-design.canvas-publish');
            Route::post('theme-design/canvas/discard-draft',              [ThemeDesignController::class, 'canvasDiscardDraft'])->name('theme-design.canvas-discard-draft');

            // Theme Marketplace (TB-2)
            Route::prefix('theme-marketplace')->name('theme-marketplace.')->group(function () {
                Route::get('/',                        [AdminThemeMarketplaceController::class, 'index'])->name('index');
                Route::get('/my-licenses',             [AdminThemeMarketplaceController::class, 'myLicenses'])->name('my-licenses');
                Route::post('/{themeId}/purchase',     [AdminThemeMarketplaceController::class, 'purchase'])->name('purchase');
                Route::post('/{themeId}/install',      [AdminThemeMarketplaceController::class, 'install'])->name('install');
            });
        });
        Route::get('builder-health', [\App\Http\Controllers\Admin\BuilderHealthController::class, 'index'])->name('builder-health.index');
        Route::middleware(['module:currency_reform', 'permission:currency_reform'])->group(function () {
            Route::get('currency-reform', [CurrencyReformController::class, 'index'])->name('currency-reform.index');
            Route::post('currency-reform', [CurrencyReformController::class, 'update'])->name('currency-reform.update');
        });
        Route::middleware('permission:license')->group(function () {
            Route::get('license', [AdminLicenseController::class, 'index'])->name('license.index');
            Route::get('module-store', [\App\Http\Controllers\Admin\ModuleStoreController::class, 'index'])->name('module-store.index');
            Route::get('modules', [AdminModuleController::class, 'index'])->name('modules.index');
            Route::get('modules/changelog', [AdminModuleChangeLogController::class, 'index'])->name('modules.changelog');
            Route::get('modules/{slug}', [AdminModuleController::class, 'show'])->name('modules.show')->where('slug', '[a-z0-9_]+');
            Route::post('modules/{slug}', [AdminModuleController::class, 'update'])->name('modules.update')->where('slug', '[a-z0-9_]+');
            Route::post('license', [AdminLicenseController::class, 'update'])->name('license.update');
            Route::post('license/check-update', [AdminLicenseController::class, 'checkUpdate'])->name('license.check-update');
            Route::post('license/refresh-config', [AdminLicenseController::class, 'refreshConfig'])->name('license.refresh-config');
            Route::get('license/refresh-config', [AdminLicenseController::class, 'refreshConfigGet'])->name('license.refresh-config.get');
            Route::get('license/test-config', [AdminLicenseController::class, 'testSiteConfig'])->name('license.test-config');
            Route::post('license/refresh-modules', [AdminLicenseController::class, 'refreshModules'])->name('license.refresh-modules');
        });

        // Phase 5 / Wave 3 / P1-2: لاگ فعالیت ادمین (فقط نمایش؛ نوشتن از سرویس‌ها انجام می‌شود).
        Route::get('activity-log', [\App\Http\Controllers\Admin\AdminActivityLogController::class, 'index'])->name('activity-log.index');
        Route::middleware(['module:reviews', 'permission:reviews'])->group(function () {
            Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
            Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
        });
        Route::middleware(['module:referral', 'permission:referral'])->group(function () {
            Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');
        });
        Route::middleware(['module:subscription', 'permission:subscription'])->group(function () {
            Route::get('subscriptions/dashboard', [\App\Http\Controllers\Admin\SubscriptionDashboardController::class, 'index'])->name('subscriptions.dashboard');
            Route::resource('subscriptions', SubscriptionPlanController::class)->parameters(['subscriptions' => 'subscription']);
            Route::get('subscribers', [\App\Http\Controllers\Admin\SubscriberController::class, 'index'])->name('subscribers.index');
            Route::get('subscribers/export', [\App\Http\Controllers\Admin\SubscriberController::class, 'export'])->name('subscribers.export');
            Route::get('subscribers/assign', [\App\Http\Controllers\Admin\SubscriberController::class, 'createAssign'])->name('subscribers.assign.form');
            Route::post('subscribers/assign', [\App\Http\Controllers\Admin\SubscriberController::class, 'assign'])->name('subscribers.assign');
            Route::get('subscribers/pending', [\App\Http\Controllers\Admin\SubscriberController::class, 'pendingRequests'])->name('subscribers.pending');
            Route::get('subscribers/{subscriber}', [\App\Http\Controllers\Admin\SubscriberController::class, 'show'])->name('subscribers.show');
            Route::post('subscribers/{subscriber}/cancel', [\App\Http\Controllers\Admin\SubscriberController::class, 'cancel'])->name('subscribers.cancel');
            Route::post('subscribers/{subscriber}/reactivate', [\App\Http\Controllers\Admin\SubscriberController::class, 'reactivate'])->name('subscribers.reactivate');
            Route::post('subscribers/{subscriber}/approve', [\App\Http\Controllers\Admin\SubscriberController::class, 'approveRequest'])->name('subscribers.approve');
            Route::delete('subscribers/{subscriber}/destroy', [\App\Http\Controllers\Admin\SubscriberController::class, 'destroyRequest'])->name('subscribers.destroy');
            Route::delete('subscribers/{subscriber}', [\App\Http\Controllers\Admin\SubscriberController::class, 'destroy'])->name('subscribers.delete');
            Route::post('subscribers/{subscriber}/activate-free', [\App\Http\Controllers\Admin\SubscriberController::class, 'activateFree'])->name('subscribers.activate-free');
            Route::post('subscribers/{subscriber}/extend', [\App\Http\Controllers\Admin\SubscriberController::class, 'extend'])->name('subscribers.extend');
            Route::post('subscribers/{subscriber}/change-plan', [\App\Http\Controllers\Admin\SubscriberController::class, 'changePlan'])->name('subscribers.change-plan');
            Route::delete('subscribers/{subscriber}/members/{member}', [\App\Http\Controllers\Admin\SubscriberController::class, 'removeMember'])->name('subscribers.members.remove');
        });
        Route::middleware(['permission:subscription'])->group(function () {
            Route::resource('webhooks', \App\Http\Controllers\Admin\WebhookController::class)->parameters(['webhooks' => 'webhook']);
            Route::post('webhooks/{webhook}/rotate-secret', [\App\Http\Controllers\Admin\WebhookController::class, 'rotateSecret'])->name('webhooks.rotate-secret');
        });
        Route::middleware(['module:whatsapp', 'permission:whatsapp'])->group(function () {
            Route::get('whatsapp', [MessagingController::class, 'index'])->name('whatsapp.index');
            Route::post('messaging/widget-logo', [MessagingController::class, 'updateWidgetLogo'])->name('messaging.widget-logo');
            Route::post('messaging', [MessagingController::class, 'store'])->name('messaging.store');
            Route::get('messaging/{channel}/edit', [MessagingController::class, 'edit'])->name('messaging.edit');
            Route::put('messaging/{channel}', [MessagingController::class, 'update'])->name('messaging.update');
            Route::delete('messaging/{channel}', [MessagingController::class, 'destroy'])->name('messaging.destroy');
        });
        Route::middleware(['module:sms', 'permission:sms'])->group(function () {
            Route::get('sms', [SmsController::class, 'index'])->name('sms.index');
            Route::post('sms', [SmsController::class, 'update'])->name('sms.update');
        });
        Route::middleware(['module:courses', 'permission:courses'])->group(function () {
            Route::resource('courses', CourseController::class);
        });
        Route::middleware(['module:invoice', 'permission:invoice'])->group(function () {
            Route::resource('invoices', AdminInvoiceController::class)->except('edit', 'update');
            Route::get('invoices/{invoice}/pdf', [AdminInvoiceController::class, 'download'])->name('admin.invoices.download');
        });
        Route::middleware(['module:business_card', 'permission:business_card'])->group(function () {
            Route::resource('business-cards', BusinessCardController::class)->parameters(['business-cards' => 'businessCard']);
        });
        Route::middleware(['module:social_links', 'permission:social_links'])->group(function () {
            Route::resource('social-links', SocialLinkController::class)->parameters(['social-links' => 'socialLink']);
        });
        Route::middleware(['module:advertisements', 'permission:advertisements'])->group(function () {
            Route::resource('advertisements', AdvertisementController::class);
        });
        Route::middleware('module:accounting')->group(function () {
            Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');
        });
        Route::middleware('module:seo_advanced')->group(function () {
            Route::get('seo-advanced', [SeoAdvancedController::class, 'index'])->name('seo-advanced.index');
        });
        Route::middleware('module:analytics')->group(function () {
            Route::get('analytics', [PlatformModuleController::class, 'show'])
                ->name('analytics.index')->defaults('slug', 'analytics');
        });
        Route::middleware('module:announcements')->group(function () {
            Route::get('announcements', [PlatformModuleController::class, 'show'])
                ->name('announcements.index')->defaults('slug', 'announcements');
        });
        Route::middleware('module:backup')->group(function () {
            Route::get('backup', [PlatformModuleController::class, 'show'])
                ->name('backup.index')->defaults('slug', 'backup');
        });
        Route::middleware('module:brand')->group(function () {
            Route::get('brand', [PlatformModuleController::class, 'show'])
                ->name('brand.index')->defaults('slug', 'brand');
        });
        Route::middleware('module:cloud')->group(function () {
            Route::get('cloud', [PlatformModuleController::class, 'show'])
                ->name('cloud.index')->defaults('slug', 'cloud');
        });
        Route::middleware('module:contract')->group(function () {
            Route::get('contract', [PlatformModuleController::class, 'show'])
                ->name('contract.index')->defaults('slug', 'contract');
        });
        Route::middleware('module:crm')->group(function () {
            Route::get('crm', [PlatformModuleController::class, 'show'])
                ->name('crm.index')->defaults('slug', 'crm');
        });
        Route::middleware('module:discount_advanced')->group(function () {
            Route::get('discount-advanced', [PlatformModuleController::class, 'show'])
                ->name('discount-advanced.index')->defaults('slug', 'discount_advanced');
        });
        Route::middleware('module:gallery')->group(function () {
            Route::get('gallery', [PlatformModuleController::class, 'show'])
                ->name('gallery.index')->defaults('slug', 'gallery');
        });
        Route::middleware('module:i18n')->group(function () {
            Route::get('i18n', [PlatformModuleController::class, 'show'])
                ->name('i18n.index')->defaults('slug', 'i18n');
        });
        Route::middleware('module:inventory')->group(function () {
            Route::get('inventory', [PlatformModuleController::class, 'show'])
                ->name('inventory.index')->defaults('slug', 'inventory');
        });
        Route::middleware('module:marketing_automation')->group(function () {
            Route::get('marketing-automation', [PlatformModuleController::class, 'show'])
                ->name('marketing-automation.index')->defaults('slug', 'marketing_automation');
        });
        Route::middleware('module:marketplace_sync')->group(function () {
            Route::get('marketplace-sync', [PlatformModuleController::class, 'show'])
                ->name('marketplace-sync.index')->defaults('slug', 'marketplace_sync');
        });
        Route::middleware('module:multi_currency')->group(function () {
            Route::get('multi-currency', [PlatformModuleController::class, 'show'])
                ->name('multi-currency.index')->defaults('slug', 'multi_currency');
        });
        Route::middleware('module:omnichannel')->group(function () {
            Route::get('omnichannel', [PlatformModuleController::class, 'show'])
                ->name('omnichannel.index')->defaults('slug', 'omnichannel');
        });
        Route::middleware('module:podcast')->group(function () {
            Route::get('podcast', [PlatformModuleController::class, 'show'])
                ->name('podcast.index')->defaults('slug', 'podcast');
        });
        Route::middleware('module:portfolio')->group(function () {
            Route::get('portfolio', [PlatformModuleController::class, 'show'])
                ->name('portfolio.index')->defaults('slug', 'portfolio');
        });
        Route::middleware('module:product_bundling')->group(function () {
            Route::get('product-bundling', [PlatformModuleController::class, 'show'])
                ->name('product-bundling.index')->defaults('slug', 'product_bundling');
        });
        Route::middleware('module:projects')->group(function () {
            Route::get('projects', [PlatformModuleController::class, 'show'])
                ->name('projects.index')->defaults('slug', 'projects');
        });
        Route::middleware('module:qa')->group(function () {
            Route::get('qa', [PlatformModuleController::class, 'show'])
                ->name('qa.index')->defaults('slug', 'qa');
        });
        Route::middleware('module:recruitment')->group(function () {
            Route::get('recruitment', [PlatformModuleController::class, 'show'])
                ->name('recruitment.index')->defaults('slug', 'recruitment');
        });
        Route::middleware('module:services')->group(function () {
            Route::get('services', [PlatformModuleController::class, 'show'])
                ->name('services.index')->defaults('slug', 'services');
        });
        Route::middleware('module:shipping')->group(function () {
            Route::get('shipping', [PlatformModuleController::class, 'show'])
                ->name('shipping.index')->defaults('slug', 'shipping');
        });
        Route::middleware('module:tasks')->group(function () {
            Route::get('tasks', [PlatformModuleController::class, 'show'])
                ->name('tasks.index')->defaults('slug', 'tasks');
        });
        Route::middleware('module:video')->group(function () {
            Route::get('video', [PlatformModuleController::class, 'show'])
                ->name('video.index')->defaults('slug', 'video');
        });
        Route::middleware('module:wallet')->group(function () {
            Route::get('wallet', [PlatformModuleController::class, 'show'])
                ->name('wallet.index')->defaults('slug', 'wallet');
        });
        Route::middleware(['module:forms', 'permission:forms'])->group(function () {
            Route::resource('forms', FormController::class)->names([
                'index'   => 'forms.index',
                'create'  => 'forms.create',
                'store'   => 'forms.store',
                'edit'    => 'forms.edit',
                'update'  => 'forms.update',
                'destroy' => 'forms.destroy',
            ]);
            Route::get('forms/{form}/preview', [FormController::class, 'preview'])->name('forms.preview');
            Route::post('forms/{form}/duplicate', [FormController::class, 'duplicate'])->name('forms.duplicate');
            Route::post('forms/{form}/fields', [FormController::class, 'storeField'])->name('forms.fields.store');
            Route::delete('forms/fields/{field}', [FormController::class, 'destroyField'])->name('forms.fields.destroy');
            Route::patch('forms/fields/{field}/toggle', [FormController::class, 'toggleField'])->name('forms.fields.toggle');

            // Enterprise Builder (Phase 10.2.6 — mirrored from package routes with tenant middleware)
            Route::get('forms/{form}/builder', [FormBuilderController::class, 'show'])->name('forms.builder');
            Route::post('forms/{form}/builder/fields', [FormBuilderController::class, 'storeField'])->name('forms.builder.fields.store');
            Route::put('forms/{form}/builder/fields/{field}', [FormBuilderController::class, 'updateField'])->name('forms.builder.fields.update');
            Route::delete('forms/{form}/builder/fields/{field}', [FormBuilderController::class, 'destroyField'])->name('forms.builder.fields.destroy');
            Route::post('forms/{form}/builder/fields/{field}/duplicate', [FormBuilderController::class, 'duplicateField'])->name('forms.builder.fields.duplicate');
            Route::post('forms/{form}/builder/sort', [FormBuilderController::class, 'sortFields'])->name('forms.builder.sort');
            Route::put('forms/{form}/builder/settings', [FormBuilderController::class, 'updateSettings'])->name('forms.builder.settings');

            // Analytics, Automation, Templates (tenant-accessible)
            Route::get('forms/{form}/analytics', [FormAnalyticsController::class, 'show'])->name('forms.analytics');
            Route::get('forms/{form}/automation', [FormAutomationController::class, 'index'])->name('forms.automation.index');
            Route::post('forms/{form}/automation', [FormAutomationController::class, 'store'])->name('forms.automation.store');
            Route::put('forms/{form}/automation/{automation}', [FormAutomationController::class, 'update'])->name('forms.automation.update');
            Route::delete('forms/{form}/automation/{automation}', [FormAutomationController::class, 'destroy'])->name('forms.automation.destroy');
            Route::patch('forms/{form}/automation/{automation}/toggle', [FormAutomationController::class, 'toggle'])->name('forms.automation.toggle');
            Route::post('forms/{form}/save-as-template', [FormTemplateController::class, 'store'])->name('forms.templates.store');
            Route::post('forms/{form}/apply-template', [FormTemplateController::class, 'apply'])->name('forms.templates.apply');

            // Submissions
            Route::get('forms/submissions', [FormSubmissionController::class, 'index'])->name('forms.submissions.index');
            Route::get('forms/submissions/export', [FormSubmissionController::class, 'export'])->name('forms.submissions.export');
            Route::post('forms/submissions/column-order', [FormSubmissionController::class, 'updateColumnOrder'])->name('forms.submissions.column-order');
        });
        Route::middleware('permission:captcha')->group(function () {
            Route::get('captcha', [CaptchaController::class, 'index'])->name('captcha.index');
            Route::post('captcha', [CaptchaController::class, 'update'])->name('captcha.update');
        });

        // اعلان‌های تولبار سایت (ماژول notifications) + مرکز اعلان‌ها برای کاربران
        Route::resource('notifications', \App\Http\Controllers\Admin\SiteNotificationController::class)
            ->except('show');
        Route::patch('notifications/{site_notification}/toggle', [\App\Http\Controllers\Admin\SiteNotificationController::class, 'toggle'])
            ->name('notifications.toggle');
        Route::post('notifications/bulk', [\App\Http\Controllers\Admin\SiteNotificationController::class, 'bulk'])
            ->name('notifications.bulk');

        // ارسال اعلان به کاربران (inbox «حساب من») و آرشیو
        Route::get('notifications/send-users', [AdminNotificationController::class, 'sendToUsersForm'])->name('notifications.send-users.form');
        Route::post('notifications/send-users', [AdminNotificationController::class, 'sendToUsers'])->name('notifications.send-users');
        Route::get('notifications/send-users/archive', [AdminNotificationController::class, 'archive'])->name('notifications.archive');

        // ویدیوهای صفحه نخست
        Route::middleware('permission:settings')->group(function () {
            Route::resource('homepage-videos', \App\Http\Controllers\Admin\HomepageVideoController::class)
                ->parameters(['homepage-videos' => 'homepageVideo']);
            Route::post('homepage-videos/reorder', [\App\Http\Controllers\Admin\HomepageVideoController::class, 'reorder'])
                ->name('homepage-videos.reorder');
        });

        // مدیریت لیست‌های دسترسی (FORM-EXT)
        Route::prefix('access-lists')->name('access-lists.')->group(function () {
            // Lists
            Route::get('lists',                          [\App\Http\Controllers\Admin\AccessListController::class, 'listsIndex'])->name('lists.index');
            Route::get('lists/create',                   [\App\Http\Controllers\Admin\AccessListController::class, 'listsCreate'])->name('lists.create');
            Route::post('lists',                         [\App\Http\Controllers\Admin\AccessListController::class, 'listsStore'])->name('lists.store');
            Route::get('lists/{list}/edit',              [\App\Http\Controllers\Admin\AccessListController::class, 'listsEdit'])->name('lists.edit');
            Route::put('lists/{list}',                   [\App\Http\Controllers\Admin\AccessListController::class, 'listsUpdate'])->name('lists.update');
            Route::delete('lists/{list}',                [\App\Http\Controllers\Admin\AccessListController::class, 'listsDestroy'])->name('lists.destroy');
            Route::post('lists/{list}/import',           [\App\Http\Controllers\Admin\AccessListController::class, 'listsImport'])->name('lists.import');
            // Gates
            Route::get('gates',                          [\App\Http\Controllers\Admin\AccessListController::class, 'gatesIndex'])->name('gates.index');
            Route::get('gates/create',                   [\App\Http\Controllers\Admin\AccessListController::class, 'gatesCreate'])->name('gates.create');
            Route::post('gates',                         [\App\Http\Controllers\Admin\AccessListController::class, 'gatesStore'])->name('gates.store');
            Route::get('gates/{gate}/edit',              [\App\Http\Controllers\Admin\AccessListController::class, 'gatesEdit'])->name('gates.edit');
            Route::put('gates/{gate}',                   [\App\Http\Controllers\Admin\AccessListController::class, 'gatesUpdate'])->name('gates.update');
            Route::delete('gates/{gate}',                [\App\Http\Controllers\Admin\AccessListController::class, 'gatesDestroy'])->name('gates.destroy');
        });

        // مدیریت ترجمه‌های هوش مصنوعی
        Route::middleware('permission:settings')->group(function () {
            Route::get('translations',             [\App\Http\Controllers\Admin\TranslationController::class, 'index'])->name('translations.index');
            Route::post('translations/process',    [\App\Http\Controllers\Admin\TranslationController::class, 'processBatch'])->name('translations.process');
            Route::post('translations/retry',      [\App\Http\Controllers\Admin\TranslationController::class, 'retryFailed'])->name('translations.retry');
            Route::post('translations/clear-done', [\App\Http\Controllers\Admin\TranslationController::class, 'clearDone'])->name('translations.clear-done');
            Route::get('translations/stats',       [\App\Http\Controllers\Admin\TranslationController::class, 'stats'])->name('translations.stats');
        });

        // مدیریت خرید قسطی
        Route::prefix('installments')->name('installments.')->group(function () {
            // طرح‌ها
            Route::get('plans',                                    [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'plansIndex'])->name('plans.index');
            Route::get('plans/create',                             [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'plansCreate'])->name('plans.create');
            Route::post('plans',                                   [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'plansStore'])->name('plans.store');
            Route::get('plans/{plan}/edit',                        [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'plansEdit'])->name('plans.edit');
            Route::put('plans/{plan}',                             [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'plansUpdate'])->name('plans.update');
            Route::delete('plans/{plan}',                          [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'plansDestroy'])->name('plans.destroy');
            // سفارشات
            Route::get('orders',                                   [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'ordersIndex'])->name('orders.index');
            Route::get('orders/{order}',                           [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'ordersShow'])->name('orders.show');
            Route::post('mark-overdue',                            [\App\Http\Controllers\Admin\InstallmentAdminController::class, 'markOverdue'])->name('mark-overdue');
        });

        Route::get('/', fn () => redirect()->route('admin.dashboard'));
    });

    // پنل کاربری — مشاهده کاربران و چت مستقیم
    Route::middleware(['auth', 'admin', 'throttle:60,1'])
        ->prefix('admin/users')
        ->name('admin.users.')
        ->group(function () {
            Route::get('/',                  [AdminUserPanelController::class, 'index'])->name('index');
            Route::get('/{id}/chat',         [AdminUserPanelController::class, 'chat'])->whereNumber('id')->name('chat');
            Route::post('/{id}/start-chat',  [AdminUserPanelController::class, 'startChat'])->whereNumber('id')->name('startChat');
        });

    // مدیریت دسترسی ادغام مکالمات موبایل (فقط ادمین سایت)
    Route::middleware(['auth', 'admin'])
        ->prefix('admin/conversation-merge')
        ->name('admin.conversation-merge.')
        ->group(function () {
            Route::get('/permissions',              [AdminConversationMergePermissionController::class, 'index'])->name('permissions');
            Route::post('/permissions',             [AdminConversationMergePermissionController::class, 'grant'])->name('grant');
            Route::delete('/permissions/{userId}',  [AdminConversationMergePermissionController::class, 'revoke'])->whereNumber('userId')->name('revoke');
        });

    // تیکت‌های کاربران نهایی (ادمین سایت ↔ کاربران)
    Route::middleware(['auth', 'admin', 'throttle:60,1'])
        ->prefix('admin/user-tickets')
        ->name('admin.user-tickets.')
        ->group(function () {
            Route::get('/',               [AdminUserTicketController::class, 'index'])->name('index');
            Route::get('/create',         [AdminUserTicketController::class, 'create'])->name('create');
            Route::post('/',              [AdminUserTicketController::class, 'store'])->name('store');
            Route::get('/{id}',           [AdminUserTicketController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/poll',      [AdminUserTicketController::class, 'poll'])->whereNumber('id')->name('poll');
            Route::get('/check-new',      [AdminUserTicketController::class, 'checkNew'])->name('check-new');
            Route::post('/{id}/reply',    [AdminUserTicketController::class, 'reply'])->whereNumber('id')->name('reply');
            Route::patch('/{id}/status',  [AdminUserTicketController::class, 'updateStatus'])->whereNumber('id')->name('status');
            Route::patch('/{id}/assign',  [AdminUserTicketController::class, 'assign'])->whereNumber('id')->name('assign');
            Route::patch('/{id}/priority',[AdminUserTicketController::class, 'priority'])->whereNumber('id')->name('priority');
            Route::delete('/{id}',        [AdminUserTicketController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::post('/bulk',          [AdminUserTicketController::class, 'bulkAction'])->name('bulk');
        });

    // ─── Unified Support Inbox (ادمین ↔ پلتفرم + ادمین ↔ کاربر در یک صفحه) ─────
    Route::middleware(['auth', 'admin', 'throttle:60,1'])
        ->prefix('admin/inbox')
        ->name('admin.inbox.')
        ->group(function () {
            Route::get('/',                               [AdminUnifiedSupportController::class, 'index'])->name('index');
            Route::get('/create',                        [AdminUnifiedSupportController::class, 'create'])->name('create');
            Route::post('/',                             [AdminUnifiedSupportController::class, 'store'])->name('store');
            Route::get('/{channel}/{id}',                [AdminUnifiedSupportController::class, 'show'])->where('channel', 'platform|user')->whereNumber('id')->name('show');
            Route::get('/{channel}/{id}/poll',           [AdminUnifiedSupportController::class, 'poll'])->where('channel', 'platform|user')->whereNumber('id')->name('poll');
            Route::post('/{channel}/{id}/reply',         [AdminUnifiedSupportController::class, 'reply'])->where('channel', 'platform|user')->whereNumber('id')->name('reply');
            Route::patch('/user/{id}/status',            [AdminUnifiedSupportController::class, 'updateStatus'])->whereNumber('id')->name('user.status');
            Route::patch('/user/{id}/assign',            [AdminUnifiedSupportController::class, 'assign'])->whereNumber('id')->name('user.assign');
            Route::patch('/user/{id}/priority',          [AdminUnifiedSupportController::class, 'priority'])->whereNumber('id')->name('user.priority');
            Route::post('/bulk',                         [AdminUnifiedSupportController::class, 'bulkAction'])->name('bulk');
        });

    // Support module — defined OUTSIDE the admin prefix group so names are tickets.site.*
    // (not admin.tickets.site.*), overriding the package's /tickets routes in the name registry.
    // AdminMenu uses tickets.site.index; Blade views in the tickets module also use tickets.site.*.
    Route::middleware(['auth', 'admin', 'license.valid', 'permission:dashboard', 'permission:support', 'throttle:30,1'])
        ->prefix('admin/support')
        ->group(function () {
            Route::get('/', [AdminSupportController::class, 'index'])->name('tickets.site.index');
            Route::get('/create', [AdminSupportController::class, 'create'])->name('tickets.site.create');
            Route::post('/', [AdminSupportController::class, 'store'])->name('tickets.site.store');
            Route::get('/history', [AdminSupportController::class, 'history'])->name('admin.support.history');
            Route::get('/settings', [AdminSupportController::class, 'chatSettings'])->name('tickets.site.settings');
            Route::get('/attachments/{path}', [AdminSupportController::class, 'downloadAttachment'])
                ->where('path', '.*')
                ->name('tickets.site.attachment.download');
            Route::get('/{ticketId}', [AdminSupportController::class, 'show'])->whereNumber('ticketId')->name('tickets.site.show');
            Route::get('/{ticketId}/poll', [AdminSupportController::class, 'poll'])->whereNumber('ticketId')->name('tickets.site.poll');
            Route::post('/{ticketId}/reply', [AdminSupportController::class, 'reply'])->whereNumber('ticketId')->name('admin.support.reply');
            Route::post('/{ticketId}/replies', [AdminSupportController::class, 'reply'])->whereNumber('ticketId')->name('tickets.site.replies.store');
        });

    // ─── Education Center — مرکز آموزش ──────────────────────────────────────
    Route::middleware(['auth', 'admin'])
        ->prefix('admin/education-center')
        ->name('admin.education-center.')
        ->group(function () {
            // مشاهده — read-only (sync از پلتفرم)
            Route::get('/', [\App\Http\Controllers\Admin\EducationCenterController::class, 'index'])->name('index');

            // مدیریت محتوا — CRUD
            Route::get('/manage', [\App\Http\Controllers\Admin\EducationCenterController::class, 'manage'])->name('manage');

            // مقالات
            Route::get('/articles/create',     [\App\Http\Controllers\Admin\EducationCenterController::class, 'createArticle'])->name('articles.create');
            Route::post('/articles',            [\App\Http\Controllers\Admin\EducationCenterController::class, 'storeArticle'])->name('articles.store');
            Route::get('/articles/{id}/edit',   [\App\Http\Controllers\Admin\EducationCenterController::class, 'editArticle'])->name('articles.edit');
            Route::put('/articles/{id}',        [\App\Http\Controllers\Admin\EducationCenterController::class, 'updateArticle'])->name('articles.update');
            Route::delete('/articles/{id}',     [\App\Http\Controllers\Admin\EducationCenterController::class, 'destroyArticle'])->name('articles.destroy');
            Route::post('/articles/{id}/publish', [\App\Http\Controllers\Admin\EducationCenterController::class, 'publishArticle'])->name('articles.publish');

            // راهنماها (Walkthroughs)
            Route::get('/walkthroughs/create',      [\App\Http\Controllers\Admin\EducationCenterController::class, 'createWalkthrough'])->name('walkthroughs.create');
            Route::post('/walkthroughs',             [\App\Http\Controllers\Admin\EducationCenterController::class, 'storeWalkthrough'])->name('walkthroughs.store');
            Route::get('/walkthroughs/{id}/edit',    [\App\Http\Controllers\Admin\EducationCenterController::class, 'editWalkthrough'])->name('walkthroughs.edit');
            Route::put('/walkthroughs/{id}',         [\App\Http\Controllers\Admin\EducationCenterController::class, 'updateWalkthrough'])->name('walkthroughs.update');
            Route::delete('/walkthroughs/{id}',      [\App\Http\Controllers\Admin\EducationCenterController::class, 'destroyWalkthrough'])->name('walkthroughs.destroy');

            // مسیرهای یادگیری
            Route::get('/learning-paths/create',     [\App\Http\Controllers\Admin\EducationCenterController::class, 'createLearningPath'])->name('learning-paths.create');
            Route::post('/learning-paths',            [\App\Http\Controllers\Admin\EducationCenterController::class, 'storeLearningPath'])->name('learning-paths.store');
            Route::get('/learning-paths/{id}/edit',   [\App\Http\Controllers\Admin\EducationCenterController::class, 'editLearningPath'])->name('learning-paths.edit');
            Route::put('/learning-paths/{id}',        [\App\Http\Controllers\Admin\EducationCenterController::class, 'updateLearningPath'])->name('learning-paths.update');
            Route::delete('/learning-paths/{id}',     [\App\Http\Controllers\Admin\EducationCenterController::class, 'destroyLearningPath'])->name('learning-paths.destroy');

            // مشاهده مقاله (باید آخر باشد — slug ممکن است با id تداخل داشته باشد)
            Route::get('/{slug}', [\App\Http\Controllers\Admin\EducationCenterController::class, 'show'])->name('show');
        });

    // ─── Site Content — دستیار محتوا ────────────────────────────────────────
    Route::middleware(['auth', 'admin'])
        ->prefix('admin/content')
        ->name('admin.site-content.')
        ->group(function () {
            Route::get('/',                  [\App\Http\Controllers\Admin\SiteContentController::class, 'index'])->name('index');
            Route::get('/create',            [\App\Http\Controllers\Admin\SiteContentController::class, 'create'])->name('create');
            Route::post('/',                 [\App\Http\Controllers\Admin\SiteContentController::class, 'store'])->name('store');
            Route::get('/{siteContent}/edit',[\App\Http\Controllers\Admin\SiteContentController::class, 'edit'])->name('edit');
            Route::put('/{siteContent}',     [\App\Http\Controllers\Admin\SiteContentController::class, 'update'])->name('update');
            Route::delete('/{siteContent}',  [\App\Http\Controllers\Admin\SiteContentController::class, 'destroy'])->name('destroy');
            Route::post('/{siteContent}/publish', [\App\Http\Controllers\Admin\SiteContentController::class, 'publish'])->name('publish');
            Route::post('/ai-generate',      [\App\Http\Controllers\Admin\SiteContentController::class, 'aiGenerate'])->name('ai-generate');
        });
});

