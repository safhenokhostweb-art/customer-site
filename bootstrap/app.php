<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            \App\Http\Middleware\UseFileSessionWhenNotInstalled::class,
        ]);

        $middleware->alias([
            'install.check' => \App\Http\Middleware\CheckInstalled::class,
            'redirect.if.not.installed' => \App\Http\Middleware\RedirectIfNotInstalled::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'license.valid' => \App\Http\Middleware\EnsureLicenseValid::class,
            // PHASE M3.2 — DOMAIN A: Support Module License Gate
            'support.license' => \App\Http\Middleware\SupportModuleLicenseCheck::class,
            // Mobile end-user auth
            'mobile.auth' => \App\Http\Middleware\AuthenticateMobileUser::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetTenantContext::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Wave 8.0b — prune tenant admin_activity_log (respect AUDIT_PRUNE_DRY_RUN)
        $schedule->command('admin-activity-log:prune')->dailyAt('03:05');

        // Subscription — expire overdue subscriptions every hour
        $schedule->command('subscriptions:expire')->hourly();

        // Retention Engine (Faz 3) ─────────────────────────────────────────
        // ارسال SMS یادآوری ۷روزه و ۱روزه قبل از انقضا + کوپن تمدید
        $schedule->command('subscriptions:send-reminders')->dailyAt('10:00');
        // هدیه امتیاز تولد
        $schedule->command('membership:birthday-gifts')->dailyAt('09:00');
    })
    ->create();
