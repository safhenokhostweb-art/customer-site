<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\PageController as FrontPageController;
use App\Http\Controllers\Front\BlogController;
use App\Http\Controllers\Front\ShopController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductCategoryController as AdminProductCategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\ModuleChangeLogController as AdminModuleChangeLogController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\GuideSearchController;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\Front\PlatformProductsController;
use App\Http\Controllers\Front\AdvancedSearchController;
use App\Http\Controllers\Front\PageBuilderController;
use App\Http\Controllers\Front\InvoiceController as FrontInvoiceController;
use App\Http\Controllers\Front\BusinessCardController as FrontBusinessCardController;
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
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormSubmissionController;
use App\Http\Controllers\Front\FormController as FrontFormController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\Admin\CurrencyReformController;
use App\Http\Controllers\Admin\CaptchaController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\ThemeDesignController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
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

    // Auth
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

    // End User Dashboard (حساب من)
    Route::prefix('account')->name('account.')->middleware('auth')->group(function () {
        Route::get('/', AccountDashboardController::class)->name('dashboard');
        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
        Route::post('/subscriptions/{subscription}/cancel', [AccountSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::get('/subscriptions', [AccountSubscriptionController::class, 'index'])->name('subscriptions.index');
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
        Route::get('/profile/deletion', [AccountProfileDeletionController::class, 'index'])->name('profile.deletion.index');
        Route::post('/profile/deletion', [AccountProfileDeletionController::class, 'store'])->name('profile.deletion.store');
        Route::post('/profile/deletion/cancel', [AccountProfileDeletionController::class, 'cancel'])->name('profile.deletion.cancel');
    });

    // Front: corporate + blog + optional shop
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/page/{slug}', [FrontPageController::class, 'show'])->name('page.show');
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
    });
    Route::middleware('module:business_card')->group(function () {
        Route::get('/card/{slug}', [FrontBusinessCardController::class, 'show'])->name('business-card.show');
    });

    Route::middleware('module:forms')->group(function () {
        Route::get('/forms', [FrontFormController::class, 'index'])->name('forms.index');
        Route::get('/forms/{slug}', [FrontFormController::class, 'show'])->name('forms.show');
        Route::post('/forms/{slug}', [FrontFormController::class, 'submit'])->name('forms.submit');
    });

    // محصولات پلتفرم (لایسنس، ماژول، پشتیبانی) — فقط برای سایت مالک
    Route::get('/platform-products', [PlatformProductsController::class, 'index'])->name('platform-products.index');

    Route::middleware('module:blog')->group(function () {
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    });

    Route::middleware('module:shop')->group(function () {
        Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
        Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');
        Route::post('/shop/cart/add', [ShopController::class, 'addToCart'])->name('shop.cart.add');
        Route::post('/shop/cart/update', [ShopController::class, 'updateCart'])->name('shop.cart.update');
        Route::post('/shop/cart/remove/{productId}', [ShopController::class, 'removeFromCart'])->name('shop.cart.remove');
        Route::get('/shop/cart', [ShopController::class, 'cart'])->name('shop.cart');
        Route::post('/shop/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');
    });

    // Admin panel (Persian)
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'license.valid', 'permission:dashboard'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::middleware('permission:pages')->group(function () {
            Route::resource('pages', AdminPageController::class);
        });
        Route::middleware(['module:blog'])->group(function () {
            Route::middleware('permission:categories')->group(function () {
                Route::resource('categories', AdminCategoryController::class)->except('show');
            });
            Route::middleware('permission:posts')->group(function () {
                Route::resource('posts', AdminPostController::class);
            });
        });
        Route::middleware(['module:shop'])->group(function () {
            Route::middleware('permission:product_categories')->group(function () {
                Route::resource('product-categories', AdminProductCategoryController::class)->except('show');
            });
            Route::middleware('permission:products')->group(function () {
                Route::resource('products', AdminProductController::class);
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
            Route::resource('menus', AdminMenuController::class)->except('show');
            Route::post('menus/{menu}/items', [AdminMenuController::class, 'storeItem'])->name('menus.items.store');
            Route::put('menus/{menu}/items/{item}', [AdminMenuController::class, 'updateItem'])->name('menus.items.update');
            Route::delete('menus/{menu}/items/{item}', [AdminMenuController::class, 'destroyItem'])->name('menus.items.destroy');
            Route::post('menus/{menu}/reorder', [AdminMenuController::class, 'reorder'])->name('menus.reorder');
            Route::get('theme-design', [ThemeDesignController::class, 'index'])->name('theme-design.index');
            Route::put('theme-design', [ThemeDesignController::class, 'update'])->name('theme-design.update');
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
        Route::middleware('permission:support')->group(function () {
            Route::get('support', [AdminSupportController::class, 'index'])->name('support.index');
            Route::post('support', [AdminSupportController::class, 'store'])->name('support.store');
        });
        Route::middleware(['module:reviews', 'permission:reviews'])->group(function () {
            Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
            Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
            Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
        });
        Route::middleware(['module:referral', 'permission:referral'])->group(function () {
            Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');
        });
        Route::middleware(['module:subscription', 'permission:subscription'])->group(function () {
            Route::resource('subscriptions', SubscriptionPlanController::class)->parameters(['subscriptions' => 'subscription']);
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
            Route::get('forms', [FormController::class, 'index'])->name('forms.index');
            Route::get('forms/create', [FormController::class, 'create'])->name('forms.create');
            Route::post('forms', [FormController::class, 'store'])->name('forms.store');
            Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
            Route::put('forms/{form}', [FormController::class, 'update'])->name('forms.update');
            Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
            Route::get('forms/{form}/preview', [FormController::class, 'preview'])->name('forms.preview');
            Route::post('forms/{form}/fields', [FormController::class, 'storeField'])->name('forms.fields.store');
            Route::delete('forms/fields/{field}', [FormController::class, 'destroyField'])->name('forms.fields.destroy');
            Route::patch('forms/fields/{field}/toggle', [FormController::class, 'toggleField'])->name('forms.fields.toggle');
            Route::get('forms/submissions', [FormSubmissionController::class, 'index'])->name('forms.submissions.index');
            Route::get('forms/submissions/export', [FormSubmissionController::class, 'export'])->name('forms.submissions.export');
            Route::post('forms/submissions/column-order', [FormSubmissionController::class, 'updateColumnOrder'])->name('forms.submissions.column-order');
        });
        Route::middleware('permission:captcha')->group(function () {
            Route::get('captcha', [CaptchaController::class, 'index'])->name('captcha.index');
            Route::post('captcha', [CaptchaController::class, 'update'])->name('captcha.update');
            Route::post('image/upload', [ImageUploadController::class, 'store'])->name('image.upload');
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

        Route::get('/', fn () => redirect()->route('admin.dashboard'));
    });
});
