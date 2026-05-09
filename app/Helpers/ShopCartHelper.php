<?php

namespace App\Helpers;

/**
 * کلید یکتای ردیف سبد: productId:variantId (variantId صفر یعنی بدون واریانت).
 */
class ShopCartHelper
{
    public static function lineKey(int $productId, ?int $variantId): string
    {
        return $productId . ':' . ($variantId ?: 0);
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function parseLineKey(string $key): array
    {
        $parts = explode(':', $key, 2);
        $pid = (int) ($parts[0] ?? 0);
        $vid = (int) ($parts[1] ?? 0);

        return [$pid, $vid > 0 ? $vid : 0];
    }

    /**
     * تبدیل سبد قدیمی (کلید عددی product_id) به قالب جدید.
     *
     * @param  array<string|int, array<string, mixed>>  $cart
     * @return array<string, array<string, mixed>>
     */
    public static function normalizeCart(array $cart): array
    {
        $out = [];
        foreach ($cart as $key => $row) {
            if (! is_array($row)) {
                continue;
            }
            $pid = isset($row['product_id']) ? (int) $row['product_id'] : (is_numeric($key) ? (int) $key : 0);
            $vid = isset($row['product_variant_id']) ? (int) $row['product_variant_id'] : 0;
            if ($pid < 1) {
                continue;
            }
            $lineKey = self::lineKey($pid, $vid > 0 ? $vid : null);
            $row['product_id'] = $pid;
            $row['product_variant_id'] = $vid > 0 ? $vid : null;
            $row['id'] = $pid;
            $out[$lineKey] = $row;
        }

        return $out;
    }

    /**
     * شناسه محصولات منحصربه‌فرد برای اعتبارسنجی کوپن.
     *
     * @param  array<string, array<string, mixed>>  $cart
     * @return list<int>
     */
    public static function distinctProductIds(array $cart): array
    {
        $ids = [];
        foreach ($cart as $row) {
            if (! empty($row['product_id'])) {
                $ids[(int) $row['product_id']] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }
}
