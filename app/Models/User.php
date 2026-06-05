<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'mobile',
        'avatar',
        'bio',
        'birth_date',
        'phone',
        'fax',
        'website',
        'company_name',
        'company_role',
        'company_address',
        'company_phone',
        'notification_preferences',
        'privacy_settings',
        'password',
        'is_admin',
        'api_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birth_date'                => 'date',
            'password'                  => 'hashed',
            'is_admin'                  => 'boolean',
            'is_site_admin'             => 'boolean',
            'notification_preferences'  => 'array',
            'privacy_settings'          => 'array',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function isTenantAdmin(): bool
    {
        return (bool) ($this->is_admin      ?? false)
            || (bool) ($this->is_site_admin ?? false)
            || $this->hasRole(Role::TENANT_ADMIN);
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isTenantAdmin()) {
            return true;
        }
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('slug', $slug))
            ->exists();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class, 'user_id');
    }

    public function deletionRequests()
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? \App\Helpers\ImageHelper::url($this->avatar) : null;
    }

    public function getNotificationPreference(string $key, $default = false)
    {
        $prefs = $this->notification_preferences ?? [];
        return $prefs[$key] ?? $default;
    }

    public function getPrivacySetting(string $key, $default = false)
    {
        $settings = $this->privacy_settings ?? [];
        return $settings[$key] ?? $default;
    }
}
