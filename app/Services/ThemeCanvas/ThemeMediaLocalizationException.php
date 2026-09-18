<?php

namespace App\Services\ThemeCanvas;

use RuntimeException;

/**
 * TASK 3/3 Phase 6.5D (BLD-31) — thrown when a URL that matched the
 * trusted Platform origin (see ThemeMediaLocalizationService::trustedOrigin())
 * could not be fetched/validated/stored. Such a URL is, by definition,
 * required Platform-owned Theme media — this is always a hard failure,
 * never a silently-ignored one, so a Theme install/update can never claim
 * to be fully self-contained while still actually depending on the
 * Platform for its own stock media.
 */
class ThemeMediaLocalizationException extends RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
