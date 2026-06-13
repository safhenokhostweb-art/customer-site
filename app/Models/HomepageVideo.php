<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageVideo extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'video_type', 'video_url',
        'thumbnail_url', 'autoplay', 'loop', 'muted',
        'show_controls', 'overlay_text', 'overlay_opacity',
        'cta_text', 'cta_url', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'autoplay'      => 'boolean',
        'loop'          => 'boolean',
        'muted'         => 'boolean',
        'show_controls' => 'boolean',
        'is_active'     => 'boolean',
        'overlay_opacity' => 'integer',
        'sort_order'    => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function embedUrl(): string
    {
        return match ($this->video_type) {
            'youtube'    => $this->youtubeEmbedUrl(),
            'cloudflare' => $this->cloudflarEmbedUrl(),
            default      => $this->video_url,
        };
    }

    public function youtubeId(): ?string
    {
        preg_match(
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i',
            $this->video_url,
            $m
        );
        return $m[1] ?? null;
    }

    private function youtubeEmbedUrl(): string
    {
        $id     = $this->youtubeId();
        if (!$id) return $this->video_url;

        $params = http_build_query(array_filter([
            'autoplay'       => $this->autoplay ? 1 : 0,
            'loop'           => $this->loop     ? 1 : 0,
            'playlist'       => $this->loop     ? $id : null,
            'mute'           => $this->muted    ? 1 : 0,
            'controls'       => $this->show_controls ? 1 : 0,
            'modestbranding' => 1,
            'rel'            => 0,
            'playsinline'    => 1,
        ]));

        return "https://www.youtube.com/embed/{$id}?{$params}";
    }

    private function cloudflarEmbedUrl(): string
    {
        // Cloudflare Stream URL format: https://customer-*.cloudflarestream.com/{id}/iframe
        if (str_contains($this->video_url, 'cloudflarestream.com')) {
            return $this->video_url;
        }
        return $this->video_url;
    }
}
