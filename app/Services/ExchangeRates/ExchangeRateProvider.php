<?php

namespace App\Services\ExchangeRates;

/**
 * An online source of exchange rates. A future platform-wide rate feed
 * becomes one more implementation; consumers only see ExchangeRateService.
 */
interface ExchangeRateProvider
{
    /**
     * @param list<string> $codes ISO-4217 codes to fetch
     * @return array<string, float> code => Toman per unit (only valid, positive rates)
     *
     * @throws ExchangeRateException when the source cannot be read at all
     */
    public function fetch(array $codes): array;
}
