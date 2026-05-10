<?php

namespace App\Helpers;

/**
 * مرتب‌سازی و برچسب «جدید» برای ویجت اعلان در تولبار.
 */
class NotificationUi
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public static function sortToolbarItems(array $items): array
    {
        $priorityOrder = ['urgent' => 1, 'high' => 2, 'normal' => 3, 'low' => 4];
        usort($items, function (array $a, array $b) use ($priorityOrder): int {
            $pa = $priorityOrder[$a['priority'] ?? 'normal'] ?? 3;
            $pb = $priorityOrder[$b['priority'] ?? 'normal'] ?? 3;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            $ta = isset($a['created_at']) && is_string($a['created_at']) ? strtotime($a['created_at']) : 0;
            $tb = isset($b['created_at']) && is_string($b['created_at']) ? strtotime($b['created_at']) : 0;

            return $tb <=> $ta;
        });

        return $items;
    }

    public static function isFresh(?string $iso8601, int $maxAgeSeconds = 259200): bool
    {
        if ($iso8601 === null || $iso8601 === '') {
            return false;
        }
        $t = strtotime($iso8601);

        return $t !== false && (time() - $t) < $maxAgeSeconds;
    }
}
