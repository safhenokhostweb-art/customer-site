<?php

namespace App\Services;

use App\Models\User;

/**
 * سرویس احراز هویت و دسترسی برای سایت مشتری (Tenant Admin, Staff, Customer).
 */
class AuthorizationService
{
    /**
     * آیا کاربر دسترسی به permission دارد؟
     */
    public function can(User $user, string $permission): bool
    {
        if ($user->isTenantAdmin()) {
            return true;
        }
        return $user->hasPermission($permission);
    }

    /**
     * آیا کاربر می‌تواند به پنل ادمین دسترسی داشته باشد؟
     */
    public function canAccessAdmin(User $user): bool
    {
        return $user->isTenantAdmin() || $user->hasRole(\App\Models\Role::STAFF);
    }
}
