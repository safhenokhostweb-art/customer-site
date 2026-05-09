<?php

namespace App\Http\Middleware;

use App\Support\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Installation::isLockPresent()) {
            return $next($request);
        }

        // مهمان باید ویزارد را ببیند؛ کاربر واردشده را به نصب برنگردان مگر اینکه واقعاً session از دست رفته باشد.
        // این از حلقهٔ «ورود → داشبورد → دوباره نصب» جلوگیری می‌کند اگر قفل موقتاً دیده نشود یا نوشتن ناقص بوده باشد.
        if ($request->user()) {
            return $next($request);
        }

        return redirect()->route('install.welcome');
    }
}
