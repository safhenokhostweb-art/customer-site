<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessagingChannel extends Model
{
    protected $fillable = ['platform', 'url', 'phone', 'message', 'custom_image', 'custom_label', 'is_active', 'order'];

    protected $casts = ['is_active' => 'boolean'];

    public static function platforms(): array
    {
        return [
            'whatsapp' => ['name' => 'واتساپ', 'icon' => 'whatsapp', 'needs_phone' => true],
            'telegram' => ['name' => 'تلگرام', 'icon' => 'telegram', 'needs_phone' => false],
            'eitaa' => ['name' => 'ایتا', 'icon' => 'eitaa', 'needs_phone' => false],
            'bale' => ['name' => 'بله', 'icon' => 'bale', 'needs_phone' => false],
            'soroush' => ['name' => 'سروش', 'icon' => 'soroush', 'needs_phone' => false],
            'gap' => ['name' => 'گپ', 'icon' => 'gap', 'needs_phone' => false],
            'igap' => ['name' => 'آی‌گپ', 'icon' => 'igap', 'needs_phone' => false],
            'rubika' => ['name' => 'روبیکا', 'icon' => 'rubika', 'needs_phone' => false],
            'custom' => ['name' => 'شبکهٔ سفارشی', 'icon' => 'custom', 'needs_phone' => false],
        ];
    }

    public function getLinkAttribute(): ?string
    {
        if (!$this->is_active) {
            return null;
        }
        return match ($this->platform) {
            'whatsapp' => $this->phone ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $this->phone) . ($this->message ? '?text=' . urlencode($this->message) : '') : null,
            'telegram' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://t.me/' . ltrim($this->url, '/@')) : null,
            'eitaa' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://eitaa.com/' . ltrim($this->url, '/')) : null,
            'bale' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://ble.ir/' . ltrim($this->url, '/')) : null,
            'soroush' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://splus.ir/' . ltrim($this->url, '/')) : null,
            'gap' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://gap.im/' . ltrim($this->url, '/')) : null,
            'igap' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://igap.net/' . ltrim($this->url, '/')) : null,
            'rubika' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://rubika.ir/' . ltrim($this->url, '/')) : null,
            'custom' => $this->url ? (str_starts_with($this->url, 'http') ? $this->url : 'https://' . $this->url) : null,
            default => $this->url,
        };
    }
}
