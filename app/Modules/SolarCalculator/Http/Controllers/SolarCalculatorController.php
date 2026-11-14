<?php

namespace App\Modules\SolarCalculator\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SolarCalculator\Formula\FormulaException;
use App\Modules\SolarCalculator\Models\SolarCalculatorLead;
use App\Modules\SolarCalculator\Services\SolarAccessPolicy;
use App\Modules\SolarCalculator\Services\SolarCalculationService;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use App\Modules\SolarCalculator\Services\SolarLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SaaSPlatform\ThemeRuntime\Support\IconLibrary;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public endpoints of the calculator widget (routes are gated by
 * `module:solar_calculator`, so an unlicensed site answers 403).
 */
class SolarCalculatorController extends Controller
{
    public function __construct(
        private readonly SolarCalculationService $calculator,
        private readonly SolarAccessPolicy $access,
        private readonly SolarLeadService $leads,
        private readonly SolarCalculatorSettings $settings,
    ) {
    }

    public function calculate(Request $request): JsonResponse
    {
        if (!$this->access->canCalculate($request->user())) {
            return $this->loginRequired($request);
        }

        $input = $this->calculator->normalizeInput($request->all());
        $result = $this->run($input);
        if ($result === null) {
            return $this->engineFailure();
        }

        return response()->json(['ok' => true, 'result' => $this->publicResult($result)]);
    }

    public function storeQuote(Request $request): JsonResponse
    {
        if (trim((string) $request->input('website', '')) !== '') {
            return response()->json(['ok' => false, 'message' => 'درخواست نامعتبر است.'], 422);
        }
        if (!$this->access->canCalculate($request->user())) {
            return $this->loginRequired($request);
        }

        $input = $this->calculator->normalizeInput($request->all());
        $contact = $this->leads->normalizeContact($request->only(['name', 'mobile', 'email', 'note']), $request->user());
        $result = $this->run($input);
        if ($result === null) {
            return $this->engineFailure();
        }

        try {
            $lead = $this->leads->capture($input, $result, $contact, $request->user(), $request->ip());
        } catch (\Throwable $e) {
            Log::error('solar_calculator.lead_capture_failed', ['message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'ثبت درخواست انجام نشد؛ لطفاً دوباره تلاش کنید.'], 500);
        }

        $url = route('solar-calculator.quote.show', ['token' => $lead->public_token]);

        return response()->json([
            'ok' => true,
            'quote_number' => $lead->quote_number,
            'quote_url' => $url,
            'print_url' => $url . '?print=1',
        ], 201);
    }

    public function showQuote(Request $request, string $token): Response
    {
        $lead = SolarCalculatorLead::query()->where('public_token', $token)->first();
        abort_if($lead === null, 404);

        $user = $request->user();
        if ($this->access->quoteNeedsLogin() && $user === null) {
            return redirect()->guest(route('login'));
        }
        abort_unless($this->access->canViewQuote($lead, $user), 403, 'این پیش‌فاکتور متعلق به حساب دیگری است.');

        $updates = [];
        if ($this->access->quoteNeedsLogin() && $lead->user_id === null) {
            $updates['user_id'] = $user->id;
        }
        if ($lead->quote_viewed_at === null) {
            $updates['quote_viewed_at'] = now();
        }
        if ($updates !== []) {
            $lead->forceFill($updates)->save();
        }

        return $this->quoteResponse($lead, $request->boolean('print'), false);
    }

    /** Shared with the admin lead screen. */
    public function quoteResponse(SolarCalculatorLead $lead, bool $autoPrint, bool $admin): Response
    {
        return response()
            ->view('modules.solar-calculator.quote', [
                'lead' => $lead,
                'autoPrint' => $autoPrint,
                'admin' => $admin,
                'regionLabel' => collect($this->settings->regions())->firstWhere('key', $lead->region_key)['label'] ?? $lead->region_key,
            ])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /** @return array<string, mixed>|null */
    private function run(array $input): ?array
    {
        try {
            return $this->calculator->calculate($input);
        } catch (FormulaException $e) {
            Log::error('solar_calculator.formula_failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /** Only what the widget renders — never the raw formula values. */
    private function publicResult(array $result): array
    {
        $cards = array_map(static function (array $c): array {
            $c['icon_svg'] = $c['icon'] !== '' && IconLibrary::has($c['icon']) ? IconLibrary::svg($c['icon']) : '';
            unset($c['icon']);

            return $c;
        }, $result['cards']);

        return [
            'cards' => $cards,
            'roof_fits' => $result['roof_fits'],
            'price' => $result['price'],
        ];
    }

    private function loginRequired(Request $request): JsonResponse
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            $request->session()->put('url.intended', $referer);
        }

        return response()->json([
            'ok' => false,
            'login_required' => true,
            'login_url' => $this->access->loginUrl(),
            'message' => $this->settings->ui()['login_required_text'],
        ], 401);
    }

    private function engineFailure(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => 'محاسبه در حال حاضر ممکن نیست؛ لطفاً با ما تماس بگیرید.',
        ], 503);
    }
}
