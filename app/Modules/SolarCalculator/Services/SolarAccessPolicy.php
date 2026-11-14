<?php

namespace App\Modules\SolarCalculator\Services;

use App\Models\User;
use App\Modules\SolarCalculator\Models\SolarCalculatorLead;
use Illuminate\Support\Facades\Route;

/**
 * The lead gatekeeper (setting `access.mode`). Contact details are always
 * captured as a lead before a quote is released; the mode only decides
 * where a login is also required:
 *
 *  - open:          calculate and see results anonymously; the quote is
 *                   released after the contact form (registration as lead);
 *  - login_results: a login is needed before results are shown;
 *  - login_quote:   results are anonymous; viewing or downloading the
 *                   quote (PDF) needs a login.
 *
 * The quote URL carries a 48-character random token; in the login modes
 * the quote is also bound to the account that first opens it.
 */
class SolarAccessPolicy
{
    public function __construct(private readonly SolarCalculatorSettings $settings)
    {
    }

    public function mode(): string
    {
        return $this->settings->accessMode();
    }

    public function canCalculate(?User $user): bool
    {
        return $this->mode() !== 'login_results' || $user !== null;
    }

    public function quoteNeedsLogin(): bool
    {
        return in_array($this->mode(), ['login_results', 'login_quote'], true);
    }

    public function canViewQuote(SolarCalculatorLead $lead, ?User $user): bool
    {
        if (!$this->quoteNeedsLogin()) {
            return true;
        }

        return $user !== null && ($lead->user_id === null || (int) $lead->user_id === (int) $user->id);
    }

    public function loginUrl(?string $returnTo = null): ?string
    {
        if (!Route::has('login')) {
            return null;
        }

        return route('login') . ($returnTo ? '?redirect=' . rawurlencode($returnTo) : '');
    }
}
