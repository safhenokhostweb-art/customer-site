<?php

namespace App\Builder\Services;

use App\Helpers\SiteHelper;

/**
 * Resolves dynamic tokens in props before render.
 * Tokens: {{site.name}}, {{page.title}}, {{user.name}}, {{post.title}}, etc.
 */
class DynamicDataResolver
{
    protected array $context = [];

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Replace tokens in a string. Recursively processes arrays.
     */
    public function resolve($value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'resolve'], $value);
        }
        if (!is_string($value)) {
            return $value;
        }
        return preg_replace_callback('#\{\{(\w+(?:\.\w+)*)\}\}#', [$this, 'replaceToken'], $value);
    }

    protected function replaceToken(array $m): string
    {
        $path = $m[1];
        $parts = explode('.', $path);
        $root = $parts[0] ?? '';
        $key = $parts[1] ?? '';

        switch ($root) {
            case 'site':
                return $this->resolveSite($key);
            case 'page':
                return $this->resolvePage($key);
            case 'user':
                return $this->resolveUser($key);
            case 'post':
                return $this->resolvePost($key);
            default:
                return $this->context[$path] ?? $m[0];
        }
    }

    protected function resolveSite(string $key): string
    {
        if ($key === 'name') {
            return (string) (config('site.name') ?? SiteHelper::siteName() ?? '');
        }
        return '';
    }

    protected function resolvePage(string $key): string
    {
        $page = $this->context['page'] ?? null;
        if (is_object($page) && isset($page->{$key})) {
            return (string) $page->{$key};
        }
        return '';
    }

    protected function resolveUser(string $key): string
    {
        $user = auth()->user();
        if ($user && isset($user->{$key})) {
            return (string) $user->{$key};
        }
        return '';
    }

    protected function resolvePost(string $key): string
    {
        $post = $this->context['post'] ?? null;
        if (is_object($post) && isset($post->{$key})) {
            return (string) $post->{$key};
        }
        return '';
    }
}
