<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Phase 5 / P0-4:
 * Cast سفارشی برای فیلدهای حساس متنی (مانند license_key) که باید encrypted ذخیره شوند
 * اما باید با دادهٔ legacy plaintext موجود در دیتابیس **سازگار** باقی بمانند.
 *
 * رفتار:
 * - `get()`:
 *      مقدار خام دیتابیس را با Laravel\Crypt تلاش به decrypt می‌کند.
 *      اگر DecryptException پرتاب شود (یعنی مقدار از قبل plaintext است؛ legacy)،
 *      همان مقدار plaintext را برمی‌گرداند تا کاربر بتواند به‌طور عادی به دیتای خود
 *      دسترسی داشته باشد.
 * - `set()`:
 *      مقدار را همیشه encrypt و ذخیره می‌کند.
 *
 * نتیجه: یک «lazy migration» بدون نیاز به مایگریشن دیتابیس یا backfill جداگانه:
 * هر رکورد legacy در اولین save بعدی به‌صورت خودکار encrypted می‌شود.
 *
 * هشدار:
 * - این Cast هیچ‌گاه رازی را که decrypt شود در log ثبت نمی‌کند.
 * - شکست در decrypt صرفاً به سکوت plaintext fallback انجام می‌دهد (با لاگ debug
 *   اختیاری برای رصد مهاجرت).
 */
class EncryptedOrPlaintext implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying database value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // legacy plaintext؛ همان مقدار برگردانده می‌شود تا در اولین save بعدی encrypted ذخیره شود.
            if (config('app.debug')) {
                Log::debug('cast.encrypted_or_plaintext.legacy_value_read', [
                    'model' => $model::class,
                    'key' => $key,
                ]);
            }
            return $value;
        }
    }

    /**
     * Transform the attribute to its underlying database value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Crypt::encryptString((string) $value);
    }
}
