<?php

namespace App\Helpers;

/**
 * منوی داشبورد کاربر نهایی (End User Dashboard).
 */
class AccountMenu
{
    public const MENU_KEYS = [
        'overview' => 'خلاصه',
        'profile' => 'پروفایل',
        'orders' => 'سفارشات من',
        'subscriptions' => 'اشتراک‌های من',
        'notifications' => 'اعلان‌ها',
        'security' => 'امنیت',
    ];

    public const MENU_ROUTES = [
        'overview' => 'account.dashboard',
        'profile' => 'account.profile.index',
        'orders' => 'account.orders.index',
        'subscriptions' => 'account.subscriptions.index',
        'notifications' => 'account.notifications.index',
        'security' => 'account.security.index',
    ];

    public const MENU_ROUTE_PATTERNS = [
        'overview' => 'account.dashboard',
        'profile' => 'account.profile.*',
        'orders' => 'account.orders.*',
        'subscriptions' => 'account.subscriptions.*',
        'notifications' => 'account.notifications.*',
        'security' => 'account.security.*',
    ];

    public static function getGroups(): array
    {
        return [
            'اصلی' => ['overview', 'profile'],
            'خرید و اشتراک' => ['orders', 'subscriptions'],
            'تعامل' => ['notifications'],
            'حساب کاربری' => ['security'],
        ];
    }

    public static function isVisible(string $key): bool
    {
        if (in_array($key, ['overview', 'profile', 'notifications', 'security'])) {
            return true;
        }
        if ($key === 'orders') {
            return SiteHelper::shopEnabled();
        }
        if ($key === 'subscriptions') {
            return SiteHelper::moduleEnabled('subscription');
        }
        return true;
    }

    public static function getRoute(string $key): string
    {
        return self::MENU_ROUTES[$key] ?? '#';
    }

    public static function isActive(string $key): bool
    {
        $pattern = self::MENU_ROUTE_PATTERNS[$key] ?? null;
        return $pattern && request()->routeIs($pattern);
    }

    public static function getTitle(string $key): string
    {
        return self::MENU_KEYS[$key] ?? $key;
    }
}
