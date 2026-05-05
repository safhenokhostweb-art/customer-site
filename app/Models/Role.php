<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const TENANT_ADMIN = 'tenant_admin';
    public const STAFF = 'staff';
    public const CUSTOMER = 'customer';

    protected $fillable = ['name', 'slug', 'guard_name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function isTenantAdmin(): bool
    {
        return $this->slug === self::TENANT_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->slug === self::CUSTOMER;
    }
}
