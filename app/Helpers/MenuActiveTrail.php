<?php

namespace App\Helpers;

/**
 * علامت‌گذاری آیتم فعال و مسیر اجداد برای رندر منو در Blade.
 */
class MenuActiveTrail
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public static function decorate(array $items, string $currentAbsoluteUrl): array
    {
        $currentPath = self::normalizePath(parse_url($currentAbsoluteUrl, PHP_URL_PATH) ?? '/');

        return self::decorateLevel($items, $currentPath);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected static function decorateLevel(array $items, string $currentPath): array
    {
        return array_values(array_map(function (array $item) use ($currentPath) {
            $children = $item['children'] ?? [];
            $children = is_array($children) ? self::decorateLevel($children, $currentPath) : [];
            $active = self::urlMatchesPath((string) ($item['url'] ?? ''), $currentPath);
            $childTrail = false;
            foreach ($children as $c) {
                if (! empty($c['active']) || ! empty($c['active_trail'])) {
                    $childTrail = true;
                    break;
                }
            }
            $item['children'] = $children;
            $item['active'] = $active;
            $item['active_trail'] = $active || $childTrail;

            return $item;
        }, $items));
    }

    protected static function normalizePath(string $path): string
    {
        $path = '/'.trim($path, '/');
        if ($path === '' || $path === '/') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }

    public static function urlMatchesPath(string $itemUrl, string $currentPath): bool
    {
        $itemUrl = trim($itemUrl);
        if ($itemUrl === '' || $itemUrl === '#') {
            return false;
        }
        if (str_starts_with($itemUrl, 'http://') || str_starts_with($itemUrl, 'https://')) {
            $path = parse_url($itemUrl, PHP_URL_PATH) ?? '/';
        } else {
            $path = parse_url($itemUrl, PHP_URL_PATH) ?? $itemUrl;
        }
        $path = self::normalizePath((string) $path);
        $cur = self::normalizePath($currentPath);

        if ($path === '/' && $cur === '/') {
            return true;
        }
        if ($path === '/' || $cur === '/') {
            return $path === $cur;
        }

        return $cur === $path || str_starts_with($cur, $path.'/');
    }
}
