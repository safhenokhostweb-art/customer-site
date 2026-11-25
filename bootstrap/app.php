<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        // Commercial Productization fase-4 — real, previously undiscovered
        // bug found via an actual local end-to-end install (not
        // Http::fake()): /builder/api/theme/install and /theme/unprotect
        // are genuinely server-to-server endpoints (routes/web.php's own
        // comment: "uses X-Provision-Secret header, no auth middleware"),
        // authenticated by ProvisionSecretMiddleware's timing-safe shared-
        // secret check — never a browser session, so a CSRF token was
        // never obtainable for a real caller. Every test exercising this
        // path used Http::fake(), which never runs real middleware, so
        // this was invisible until a genuine local install was attempted.
        // Excluding CSRF here does not weaken the endpoint's real security
        // boundary (the secret-header check), it only removes a check that
        // could never legitimately pass for this endpoint's real callers.
        $middleware->validateCsrfTokens(except: [
            'builder/api/theme/*',
        ]);

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
            'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
            // Theme provisioning from saas-platform
            'provision.secret' => \App\Http\Middleware\ProvisionSecretMiddleware::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetTenantContext::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrackPageView::class,
            \App\Http\Middleware\CheckSiteLifecycle::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            // Canvas Slot Binding — annotates canvas-theme chrome <header>/<footer>
            // with data-layout-slot markers (config('canvas.slot_binding_enabled')).
            \App\Http\Middleware\DecorateCanvasLayoutSlots::class,
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

        // Auto-renew Engine (P2) ───────────────────────────────────────────
        // تمدید خودکار: رایگان→مستقیم | پولی→فاکتور+SMS | ساعت ۸ صبح
        $schedule->command('subscriptions:process-renewals')->dailyAt('08:00');

        // DB Backup (8.3b) ────────────────────────────────────────────────
        // پشتیبان‌گیری روزانه ساعت ۲ بامداد — ۷ نسخه نگه‌داری
        $schedule->command('db:backup', ['--retention' => env('DB_BACKUP_RETENTION', 7)])
                 ->dailyAt('02:00')
                 ->appendOutputTo(storage_path('logs/db-backup.log'))
                 ->onFailure(function () {
                     Log::error('db:backup scheduled run failed — check storage/logs/db-backup.log');
                 });

        // LANG — پردازش صف ترجمه هر ۱۵ دقیقه
        $schedule->command('translations:process', ['--batch' => 30])->everyFifteenMinutes();

        // C03 — پردازش کمپین‌های SMS زمان‌بندی‌شده هر ۵ دقیقه
        $schedule->command('sms:process-scheduled')->everyFiveMinutes()->withoutOverlapping(5);

        // GAP-H05 — Dunning: یادآوری فاکتورهای تمدید پرداخت‌نشده — روزانه ساعت ۱۰:۳۰
        $schedule->command('subscriptions:process-dunning')->dailyAt('10:30');
    })
    ->create();
