<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerMobileAuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'mobile'      => 'required|string|regex:/^09[0-9]{9}$/',
            'password'    => 'required|string',
            'device_id'   => 'nullable|string',
            'platform'    => 'nullable|string',
            'app_version' => 'nullable|string',
        ]);

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'شماره موبایل یا رمز عبور اشتباه است.'], 401);
        }

        // تولید توکن ساده
        $plainToken = Str::random(60);
        $user->update(['api_token' => hash('sha256', $plainToken)]);

        $sessionId  = 'cs-' . $user->id . '-' . now()->timestamp;
        $expiresAt  = now()->addDays(30)->toIso8601String();

        return response()->json([
            'data' => [
                'token'      => $plainToken,
                'expires_at' => $expiresAt,
                'user' => [
                    'id'            => (string) $user->id,
                    'name'          => $user->name ?? $user->mobile,
                    'mobile'        => $user->mobile,
                    'roles'         => ['end_user'],
                    'is_admin'      => false,
                    'is_site_admin' => false,
                    'site_id'       => null,
                ],
                'session' => [
                    'session_id' => $sessionId,
                    'expires_at' => $expiresAt,
                ],
            ],
            'meta' => ['tenant_id' => 'customer-site'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->update(['api_token' => null]);
        }
        return response()->json(['message' => 'خروج موفق.']);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        // توکن فعلی را از header می‌خوانیم
        $header = $request->header('Authorization', '');
        $currentToken = str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;

        if (!$currentToken) {
            return response()->json(['message' => 'توکن یافت نشد.'], 401);
        }

        $user = User::where('api_token', hash('sha256', $currentToken))->first();
        if (!$user) {
            return response()->json(['message' => 'توکن نامعتبر است.'], 401);
        }

        // توکن جدید صادر کن
        $newToken = Str::random(60);
        $user->update(['api_token' => hash('sha256', $newToken)]);

        return response()->json([
            'data' => [
                'token'      => $newToken,
                'expires_at' => now()->addDays(30)->toIso8601String(),
            ],
        ]);
    }
}
