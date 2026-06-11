<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * کاربر platform agent قابل حذف نیست.
     * این مکانیزم از حذف تصادفی یا عمدی ادمین پلتفرم توسط ادمین سایت جلوگیری می‌کند.
     */
    public function deleting(User $user): bool
    {
        if ($user->is_platform_agent) {
            throw new \RuntimeException('این کاربر توسط پلتفرم مدیریت می‌شود و قابل حذف نیست. برای لغو دسترسی از پنل مدیریت پلتفرم اقدام کنید.');
        }

        return true;
    }
}
