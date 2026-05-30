<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\Installation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InstallController extends Controller
{
    public function welcome()
    {
        $checks = [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'tokenizer' => extension_loaded('tokenizer'),
                'json' => extension_loaded('json'),
                'ctype' => extension_loaded('ctype'),
                'fileinfo' => extension_loaded('fileinfo'),
            ],
            'writable' => [
                'storage' => is_writable(storage_path()),
                'bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
                'env' => ! file_exists(base_path('.env')) || is_writable(base_path('.env')),
            ],
        ];
        $checks['extensions_ok'] = ! in_array(false, $checks['extensions'], true);
        $checks['writable_ok'] = ! in_array(false, $checks['writable'], true);
        $checks['passed'] = $checks['php'] && $checks['extensions_ok'] && $checks['writable_ok'];

        return view('install.welcome', compact('checks'));
    }

    public function database()
    {
        return view('install.database');
    }

    public function storeDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_name' => 'required|string',
            'db_user' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $envPath = base_path('.env');
        $env = file_exists($envPath) ? file_get_contents($envPath) : file_get_contents(base_path('.env.example'));

        $env = $this->patchEnvForMysql(
            $env,
            $request->db_host,
            $request->db_name,
            $request->db_user,
            $request->db_password ?? ''
        );
        $env = $this->patchEnvValue($env, 'APP_URL', rtrim($request->root(), '/'));

        if (! file_put_contents($envPath, $env)) {
            return back()->withErrors(['db' => 'امکان نوشتن فایل .env نیست.']);
        }

        Config::set('database.default', 'mysql');
        config()->set('database.connections.mysql.host', $request->db_host);
        config()->set('database.connections.mysql.database', $request->db_name);
        config()->set('database.connections.mysql.username', $request->db_user);
        config()->set('database.connections.mysql.password', $request->db_password ?? '');
        DB::purge();

        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            return back()->withErrors(['db' => 'اتصال به دیتابیس برقرار نشد: ' . $e->getMessage()]);
        }

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => 'mysql',
                '--force' => true,
            ]);
        } catch (QueryException $e) {
            return back()
                ->withErrors(['db' => $this->migrationFailureMessage($e)])
                ->withInput();
        } catch (\Throwable $e) {
            return back()
                ->withErrors([
                    'db' => 'اجرای مایگریشن ناموفق بود. ' . $e->getMessage(),
                ])
                ->withInput();
        }

        $output = trim(Artisan::output());
        if ($exitCode !== 0) {
            return back()
                ->withErrors([
                    'db' => 'مایگریشن کامل نشد (کد خروج: ' . $exitCode . '). '
                        . ($output !== '' ? $output : 'لاگ برنامه را برای جزئیات بیشتر بررسی کنید.'),
                ])
                ->withInput();
        }

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        return view('install.admin');
    }

    public function storeAdmin(Request $request)
    {
        $existingByEmail = User::query()->where('email', $request->input('email'))->first();

        if ($existingByEmail && ! $existingByEmail->is_admin) {
            return back()->withErrors([
                'email' => 'این ایمیل برای یک کاربر عادی ثبت شده و نمی‌توان به‌عنوان مدیر اولیهٔ نصب استفاده کرد.',
            ])->withInput();
        }

        $emailRules = ['required', 'email'];
        if (! $existingByEmail) {
            $emailRules[] = Rule::unique('users', 'email');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                Rule::unique('users', 'mobile')->ignore($existingByEmail?->id),
            ],
            'email' => $emailRules,
            'password' => 'required|min:6|confirmed',
        ]);

        if ($existingByEmail) {
            $existingByEmail->update([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'password' => Hash::make($request->password),
                'is_admin' => true,
            ]);

            return redirect()->route('install.settings')->with(
                'success',
                'حساب مدیر از قبل وجود داشت؛ اطلاعات و رمز عبور به‌روزرسانی شد. ادامه دهید.'
            );
        }

        User::create([
            'name'     => $request->name,
            'mobile'   => $request->mobile,
            'password' => Hash::make($request->password),
            'is_admin' => true,
        ]);

        return redirect()->route('install.settings');
    }

    public function settings()
    {
        return view('install.settings');
    }

    public function storeSettings(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'theme' => 'required|string|in:default',
        ]);

        Setting::set('site_name', $request->site_name);
        Setting::set('theme', $request->theme ?? 'default');

        $envPath = base_path('.env');
        if (file_exists($envPath) && is_writable($envPath)) {
            $env = file_get_contents($envPath);
            $env = $this->patchEnvValue($env, 'APP_URL', rtrim($request->root(), '/'));
            file_put_contents($envPath, $env);
        }

        if (! Installation::writeLockFile()) {
            return back()->withErrors([
                'site_name' => 'فایل installed.lock در storage نوشته نشد. دسترسی نوشتن پوشهٔ storage را بررسی کنید.',
            ])->withInput();
        }

        return redirect()->route('install.done');
    }

    public function done()
    {
        if (! Installation::isLockPresent()) {
            return redirect()
                ->route('install.settings')
                ->with('install_notice', 'ابتدا در مرحلهٔ «تنظیمات سایت» دکمهٔ «اتمام نصب» را بزنید تا نصب ذخیره شود.');
        }

        return view('install.done');
    }

    /**
     * پیام خطای مایگریشن: تفکیک تداخل جدول با خطاهای دیگر (بدون فرض اشتباه «دیتابیس خالی»).
     */
    private function migrationFailureMessage(QueryException $e): string
    {
        $technical = $e->getMessage();
        $lower = strtolower($technical);
        $mysqlDuplicateCode = ($e->errorInfo[1] ?? null) === 1050;
        $isDuplicate = $mysqlDuplicateCode
            || str_contains($lower, 'already exists')
            || str_contains($lower, 'base table or view already exists');

        if ($isDuplicate) {
            return 'مایگریشن نتوانست جداول را بسازد چون برخی جداول از قبل در این دیتابیس وجود دارند '
                . '(مثلاً نصب قبلی ناقص، یا حذف جدول migrations در حالی که بقیهٔ جداول مانده‌اند). '
                . 'برای نصب کاملاً تازه از یک دیتابیس خالی استفاده کنید. '
                . 'اگر در حال نصب مجدد همان سایت هستید و دیتابیس سالم است، معمولاً بدون این خطا پیش می‌رود؛ '
                . 'در صورت تردید از پشتیبان یا دیتابیس تمیز کمک بگیرید. '
                . 'جزئیات فنی: ' . $technical;
        }

        return 'خطای دیتابیس هنگام اجرای مایگریشن: ' . $technical;
    }

    private function patchEnvForMysql(string $env, string $host, string $database, string $user, string $password): string
    {
        $pairs = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => '3306',
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $password,
        ];
        foreach ($pairs as $key => $value) {
            $pattern = '/^#?\s*' . preg_quote($key, '/') . '=.*/m';
            $line = $key . '=' . $value;
            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, $line, $env, 1);
            } else {
                $env = rtrim($env) . "\n" . $line;
            }
        }

        return $env;
    }

    /**
     * یک کلید ساده در متن .env را set/replace می‌کند (مثل APP_URL).
     */
    private function patchEnvValue(string $env, string $key, string $value): string
    {
        $pattern = '/^#?\s*' . preg_quote($key, '/') . '=.*/m';
        $line = $key . '=' . $value;
        if (preg_match($pattern, $env)) {
            return (string) preg_replace($pattern, $line, $env, 1);
        }

        return rtrim($env) . "\n" . $line;
    }
}
