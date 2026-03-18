<?php

namespace App\Services;

class CurrencyService
{
    public function siteCurrency(): string
    {
        return strtoupper((string) config('zangi_catalog.currency.site', 'ZMW'));
    }

    public function usdToZmwRate(): float
    {
        return (float) config('zangi_catalog.currency.usd_to_zmw', 28);
    }

    public function convertUsdToCurrency(float $amountUsd, string $currency): float
    {
        $currency = strtoupper($currency);

        if ($currency === $this->siteCurrency()) {
            return round($amountUsd * $this->usdToZmwRate(), 2);
        }

        return round($amountUsd, 2);
    }
}
