<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Contracts\Foundation\Application;

/**
 * سرویس مرکزی برای دسترسی به tenant (سایت) فعلی در customer-site.
 * هر نصب customer-site یک tenant است؛ شناسایی از License و domain.
 */
class TenantContext
{
    protected ?License $license = null;

    protected ?string $domain = null;

    public function __construct(
        protected Application $app
    ) {}

    /**
     * لایسنس فعال (tenant) فعلی.
     */
    public function current(): ?License
    {
        if ($this->license !== null) {
            return $this->license;
        }

        $license = License::current();
        if ($license && $license->isValid()) {
            $this->license = $license;
            $this->domain = request()->getHost();
            return $license;
        }

        return null;
    }

    /**
     * tenant را صریحاً تنظیم کن.
     */
    public function set(?License $license): void
    {
        $this->license = $license;
        $this->domain = $license ? request()->getHost() : null;
    }

    /**
     * context را پاک کن.
     */
    public function clear(): void
    {
        $this->license = null;
        $this->domain = null;
    }

    /**
     * دامنهٔ tenant فعلی.
     */
    public function domain(): ?string
    {
        $this->current();
        return $this->domain;
    }

    /**
     * آیا tenant (لایسنس معتبر) وجود دارد؟
     */
    public function hasTenant(): bool
    {
        return $this->current() !== null;
    }

    /**
     * ماژول‌های مجاز tenant فعلی.
     *
     * @return array<string>
     */
    public function allowedModules(): array
    {
        $license = $this->current();
        if (! $license || ! is_array($license->modules)) {
            return [];
        }
        return $license->modules;
    }

    /**
     * آیا ماژول برای tenant فعلی مجاز است؟
     */
    public function hasModule(string $slug): bool
    {
        return in_array($slug, $this->allowedModules(), true);
    }
}
