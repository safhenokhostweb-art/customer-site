<?php

namespace App\Modules\SolarCalculator\Services;

use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\User;
use App\Modules\SolarCalculator\Formula\Expression;
use App\Modules\SolarCalculator\Models\SolarCalculatorLead;
use App\Services\ExchangeRates\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SaaSPlatform\ThemeRuntime\Support\PersianNumber;

/**
 * Captures a quotation request: the lead row (with frozen inputs, results,
 * quote and exchange-rate snapshot) and the CRM contact + activity, in one
 * transaction — a quote is never released for a request the CRM did not
 * record.
 */
class SolarLeadService
{
    /**
     * crm_contacts.source is an enum (manual/order/ticket/form/import): a
     * calculator request is a web form, identified by the
     * `solar_calculator` tag.
     */
    public const SOURCE = 'form';
    public const TAG = 'solar_calculator';

    public function __construct(
        private readonly SolarCalculatorSettings $settings,
        private readonly ExchangeRateService $rates,
    ) {
    }

    /**
     * @return array{name:string, mobile:string, email:?string, note:?string}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function normalizeContact(array $raw, ?User $user): array
    {
        $data = [
            'name' => trim((string) ($raw['name'] ?? ($user?->name ?? ''))),
            'mobile' => self::normalizeMobile((string) ($raw['mobile'] ?? ($user?->mobile ?? ''))),
            'email' => trim((string) ($raw['email'] ?? ($user?->email ?? ''))) ?: null,
            'note' => trim((string) ($raw['note'] ?? '')) ?: null,
        ];

        $requireEmail = (bool) ($this->settings->section('access')['require_email'] ?? false);
        Validator::make($data, [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'email' => [$requireEmail ? 'required' : 'nullable', 'email:rfc', 'max:190'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.*' => 'نام و نام خانوادگی را وارد کنید.',
            'mobile.*' => 'شمارهٔ موبایل معتبر (مانند ۰۹۱۲۱۲۳۴۵۶۷) وارد کنید.',
            'email.*' => 'ایمیل معتبر وارد کنید.',
            'note.*' => 'توضیحات حداکثر ۱۰۰۰ نویسه است.',
        ])->validate();

        return $data;
    }

    public static function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', Expression::normalizeDigits($mobile)) ?? '';
        if (str_starts_with($digits, '0098')) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /**
     * @param array<string, mixed> $input  normalized calculator input
     * @param array<string, mixed> $result SolarCalculationService::calculate() output
     * @param array{name:string, mobile:string, email:?string, note:?string} $contact
     */
    public function capture(array $input, array $result, array $contact, ?User $user, ?string $ip): SolarCalculatorLead
    {
        return DB::transaction(function () use ($input, $result, $contact, $user, $ip): SolarCalculatorLead {
            $crm = $this->upsertCrmContact($contact, $input, $user);
            $company = $this->settings->company();

            $lead = SolarCalculatorLead::query()->create([
                'public_token' => Str::random(48),
                'quote_number' => 'tmp-' . Str::random(24),
                'user_id' => $user?->id,
                'crm_contact_id' => $crm->id,
                'name' => $contact['name'],
                'mobile' => $contact['mobile'],
                'email' => $contact['email'],
                'region_key' => $input['region'],
                'system_type' => $input['system_type'],
                'inputs' => $input,
                'results' => [
                    'values' => $result['values'],
                    'cards' => $result['cards'],
                    'roof_fits' => $result['roof_fits'],
                    'price' => $result['price'],
                ],
                'quote' => [
                    'available' => $result['quote'] !== null,
                    'data' => $result['quote'],
                    'company' => $company,
                    'issued_at' => now()->toIso8601String(),
                    'valid_until' => now()->addDays((int) $company['validity_days'])->toIso8601String(),
                ],
                'exchange_rates' => $this->rates->snapshot(),
                'total_price' => $result['price']['total'],
                'status' => 'new',
                'customer_note' => $contact['note'],
                'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
            ]);

            $prefix = preg_replace('/[^A-Za-z0-9]/', '', (string) $company['quote_prefix']) ?: 'SQ';
            $lead->update(['quote_number' => $prefix . '-' . str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT)]);

            CrmActivity::query()->create([
                'contact_id' => $crm->id,
                'type' => 'note',
                'subject' => 'درخواست پیش‌فاکتور سیستم خورشیدی ' . $lead->quote_number,
                'body' => $this->summary($input, $result),
            ]);

            return $lead;
        });
    }

    private function upsertCrmContact(array $contact, array $input, ?User $user): CrmContact
    {
        $region = collect($this->settings->regions())->firstWhere('key', $input['region']);
        $crm = CrmContact::query()->where('mobile', $contact['mobile'])->first();

        if ($crm === null) {
            return CrmContact::query()->create([
                'user_id' => $user?->id,
                'name' => $contact['name'],
                'mobile' => $contact['mobile'],
                'email' => $contact['email'],
                'source' => self::SOURCE,
                'status' => 'lead',
                'city' => $region['label'] ?? null,
                'tags' => [self::TAG],
            ]);
        }

        $updates = [];
        foreach (['email' => $contact['email'], 'user_id' => $user?->id, 'city' => $region['label'] ?? null] as $field => $value) {
            if ($value !== null && empty($crm->{$field})) {
                $updates[$field] = $value;
            }
        }
        $tags = is_array($crm->tags) ? $crm->tags : [];
        if (!in_array(self::TAG, $tags, true)) {
            $updates['tags'] = array_values(array_merge($tags, [self::TAG]));
        }
        if ($updates !== []) {
            $crm->update($updates);
        }

        return $crm;
    }

    private function summary(array $input, array $result): string
    {
        $lines = [];
        foreach ($result['cards'] as $card) {
            $lines[] = $card['label'] . ': ' . $card['display'] . ' ' . $card['unit'];
        }
        $usage = $input['consumption_mode'] === 'bill'
            ? 'مبلغ قبض: ' . PersianNumber::format($input['monthly_bill']) . ' تومان'
            : 'مصرف ماهانه: ' . PersianNumber::format($input['monthly_kwh']) . ' کیلووات‌ساعت';
        array_unshift($lines, $usage);
        if ($result['price']['total'] !== null) {
            $lines[] = 'مبلغ برآوردی: ' . PersianNumber::format($result['price']['total']) . ' تومان';
        }

        return implode("\n", $lines);
    }
}
