<?php

namespace App\Services;

use SaaSPlatform\CoreUtils\Contracts\Platform\UserModelInterface;
use SaaSPlatform\CoreUtils\Contracts\Services\AuthUserResolverInterface;

/**
 * Bridges AuthUserResolverInterface to Laravel's auth() facade.
 * The customer-site User model doesn't implement UserModelInterface,
 * so user() returns null — callers must handle null safely.
 */
class AuthUserResolverAdapter implements AuthUserResolverInterface
{
    public function id(): ?int
    {
        return auth()->id();
    }

    public function check(): bool
    {
        return auth()->check();
    }

    public function user(): ?UserModelInterface
    {
        return null;
    }

    public function isAdmin(): bool
    {
        $user = auth()->user();
        return $user !== null && (bool) ($user->is_admin ?? false);
    }
}
