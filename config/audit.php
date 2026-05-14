<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit log retention (days) — admin_activity_log
    |--------------------------------------------------------------------------
    |
    | Rows older than this window are removed by `php artisan admin-activity-log:prune`.
    | Override with AUDIT_RETENTION_DAYS (shared name with platform where applicable).
    |
    */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
