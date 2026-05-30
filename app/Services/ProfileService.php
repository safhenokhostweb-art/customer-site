<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;

/**
 * سرویس متمرکز به‌روزرسانی پروفایل کاربر.
 * برای توسعهٔ بعدی (event، فیلدهای سفارشی و غیره) از این سرویس استفاده کنید.
 */
class ProfileService
{
    public function updateProfile(User $user, array $data): void
    {
        $notificationPrefs = [
            'notify_email' => (bool) ($data['notify_email'] ?? true),
            'notify_sms' => (bool) ($data['notify_sms'] ?? false),
            'marketing' => (bool) ($data['marketing'] ?? false),
        ];
        $privacySettings = [
            'show_email' => (bool) ($data['show_email'] ?? false),
            'show_phone' => (bool) ($data['show_phone'] ?? false),
        ];

        $birthDate = null;
        if (!empty($data['birth_date'])) {
            $birthDate = \App\Helpers\Jalali::toCarbon($data['birth_date']);
        }

        $user->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'bio' => $data['bio'] ?? null,
            'birth_date' => $birthDate,
            'phone' => $data['phone'] ?? null,
            'fax' => $data['fax'] ?? null,
            'website' => $this->normalizeWebsite($data['website'] ?? null),
            'company_name' => $data['company_name'] ?? null,
            'company_role' => $data['company_role'] ?? null,
            'company_address' => $data['company_address'] ?? null,
            'company_phone' => $data['company_phone'] ?? null,
            'notification_preferences' => $notificationPrefs,
            'privacy_settings' => $privacySettings,
        ]);

        UserActivityLog::log('profile_updated', 'به‌روزرسانی پروفایل', null, null, $user->id);
    }

    protected function normalizeWebsite(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }
        return $value;
    }
}
