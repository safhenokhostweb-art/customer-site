<?php

namespace App\Services;

use SaaSPlatform\CoreUtils\Contracts\Services\ActivityLoggerInterface;

/**
 * Bridges the package ActivityLoggerInterface to the local static AdminActivityLogger.
 */
class ActivityLoggerAdapter implements ActivityLoggerInterface
{
    public function log(
        string $action,
        ?string $description = null,
        ?object $subject = null,
        array $properties = []
    ): void {
        AdminActivityLogger::log($action, $description, $subject instanceof \Illuminate\Database\Eloquent\Model ? $subject : null, $properties);
    }
}
