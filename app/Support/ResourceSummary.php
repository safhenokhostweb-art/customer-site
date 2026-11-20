<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

/**
 * Reads the resource summary the platform pushes into settings
 * (site_resource_summary) and answers what the customer should be told.
 *
 * 2026-11-19 (P1): the platform now sends a REAL measured disk figure (or
 * storage_measured=false), its own warning/critical thresholds and the real
 * soft-quota grace state (soft_exceeded_at, soft_grace_ends_at,
 * soft_grace_expired). The views used to hard-code 80/95 and always claim
 * "a 7-day grace period is active" with no date behind it.
 */
final class ResourceSummary
{
    /** A summary older than this is shown as stale. */
    public const STALE_AFTER_HOURS = 48;

    private function __construct(private readonly array $data)
    {
    }

    public static function fromSettings(): ?self
    {
        try {
            $raw = \App\Models\Setting::get('site_resource_summary', null);
        } catch (Throwable) {
            return null;
        }

        return self::fromJson(is_string($raw) ? $raw : null);
    }

    public static function fromJson(?string $json): ?self
    {
        $data = $json ? json_decode($json, true) : null;

        return is_array($data) ? new self($data) : null;
    }

    /** Older summaries (before the platform sent the flag) carried a percentage only. */
    public function isMeasured(): bool
    {
        if (array_key_exists('storage_measured', $this->data)) {
            return (bool) $this->data['storage_measured'];
        }

        return isset($this->data['storage_pct']);
    }

    public function percent(): ?float
    {
        return $this->isMeasured() && isset($this->data['storage_pct']) ? (float) $this->data['storage_pct'] : null;
    }

    public function warningPercent(): int
    {
        return (int) ($this->data['warning_percent'] ?? 80);
    }

    public function criticalPercent(): int
    {
        return (int) ($this->data['critical_percent'] ?? 95);
    }

    /** 'critical' | 'warning' | 'soft' | null */
    public function level(): ?string
    {
        $pct = $this->percent();
        if ($pct === null) {
            return null;
        }
        if ($pct >= $this->criticalPercent()) {
            return 'critical';
        }
        if ($pct >= $this->warningPercent()) {
            return 'warning';
        }

        return $this->softExceeded() ? 'soft' : null;
    }

    public function usedBytes(): int
    {
        return (int) ($this->data['storage_bytes'] ?? 0);
    }

    public function hardLimitBytes(): int
    {
        return (int) ($this->data['disk_hard_limit_bytes'] ?? $this->data['storage_limit'] ?? 1073741824);
    }

    public function softLimitBytes(): int
    {
        return (int) ($this->data['disk_soft_limit_bytes'] ?? ($this->hardLimitBytes() * 0.70));
    }

    public function softExceeded(): bool
    {
        if (! $this->isMeasured()) {
            return false;
        }
        if (array_key_exists('soft_exceeded_at', $this->data)) {
            return $this->data['soft_exceeded_at'] !== null;
        }

        return $this->softLimitBytes() > 0 && $this->usedBytes() >= $this->softLimitBytes();
    }

    public function graceDays(): int
    {
        return (int) ($this->data['soft_grace_days'] ?? 7);
    }

    public function graceEndsAt(): ?Carbon
    {
        return $this->date($this->data['soft_grace_ends_at'] ?? null);
    }

    public function graceExpired(): bool
    {
        if (! empty($this->data['soft_grace_expired'])) {
            return true;
        }
        $ends = $this->graceEndsAt();

        return $ends !== null && $ends->isPast();
    }

    public function measuredAt(): ?Carbon
    {
        return $this->date($this->data['measured_at'] ?? $this->data['calculated_at'] ?? null);
    }

    public function isStale(): bool
    {
        $at = $this->measuredAt();

        return $at === null || $at->lt(now()->subHours(self::STALE_AFTER_HOURS));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
