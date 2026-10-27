<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 0.7 (saas-platform ADR-066): the theme newsletter block's endpoint.
 * POST only, inside the web group (CSRF) and throttled per IP. Answers JSON
 * to the block's script (site-forms.js) and redirects back for a plain,
 * script-less form post. A repeat sign-up is reported, never duplicated.
 */
class NewsletterController extends Controller
{
    public const MSG_SUBSCRIBED = 'عضویت شما در خبرنامه ثبت شد. سپاس!';
    public const MSG_ALREADY = 'این ایمیل یا شماره قبلاً در خبرنامه ثبت شده است.';
    public const MSG_INVALID = 'ایمیل یا شماره موبایل معتبر نیست. نمونه: name@example.com یا ۰۹۱۲۳۴۵۶۷۸۹';
    public const MSG_EMPTY = 'لطفاً ایمیل یا شماره موبایل خود را وارد کنید.';

    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        // Honeypot: a bot that fills the hidden field gets a normal-looking
        // answer and nothing is stored.
        if (trim((string) $request->input('website', '')) !== '') {
            return $this->answer($request, 200, 'subscribed', self::MSG_SUBSCRIBED);
        }

        $raw = $request->input('contact');
        if (!is_string($raw) || trim($raw) === '' || mb_strlen($raw) > 190) {
            return $this->invalid($request, is_string($raw) && trim($raw) !== '' ? self::MSG_INVALID : self::MSG_EMPTY);
        }
        $contact = NewsletterSubscriber::normalizeContact($raw);
        if ($contact === null) {
            return $this->invalid($request, self::MSG_INVALID);
        }

        $source = preg_match('/^[A-Za-z0-9_-]{1,64}$/', (string) $request->input('source', '')) ? (string) $request->input('source') : null;

        $existing = NewsletterSubscriber::where('contact', $contact['contact'])->first();
        if ($existing !== null) {
            if ($existing->unsubscribed_at === null) {
                return $this->answer($request, 200, 'already', self::MSG_ALREADY);
            }
            $existing->forceFill(['unsubscribed_at' => null, 'source' => $source ?? $existing->source])->save();
            return $this->answer($request, 200, 'subscribed', self::MSG_SUBSCRIBED);
        }

        try {
            NewsletterSubscriber::create([
                'contact' => $contact['contact'],
                'channel' => $contact['channel'],
                'source' => $source,
                'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
            ]);
        } catch (QueryException $e) {
            // Two simultaneous sign-ups for the same contact: the unique
            // index kept one row; the other is a repeat, not an error.
            if (NewsletterSubscriber::where('contact', $contact['contact'])->exists()) {
                return $this->answer($request, 200, 'already', self::MSG_ALREADY);
            }
            throw $e;
        }

        return $this->answer($request, 201, 'subscribed', self::MSG_SUBSCRIBED);
    }

    private function invalid(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message, 'errors' => ['contact' => [$message]]], 422);
        }
        return back()->withErrors(['contact' => $message], 'newsletter')->withInput($request->only('contact'));
    }

    private function answer(Request $request, int $status, string $result, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $result, 'message' => $message], $status);
        }
        return back()->with('newsletter_status', $message);
    }
}
