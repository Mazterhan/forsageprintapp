<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PrivatBankExchangeRateService
{
    private const CACHE_KEY = 'payments.privatbank.sale-rates.v1';

    /**
     * @return array{rates: array<string, float>, fetched_at: string, source: string, type: string}
     */
    public function currentSaleRates(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(20), function (): array {
            $response = Http::acceptJson()
                ->timeout(5)
                ->retry(2, 200)
                ->get((string) config('services.privatbank.exchange_rates_url'));

            if (! $response->successful() || ! is_array($response->json())) {
                throw new RuntimeException('ПриватБанк тимчасово не надав актуальний курс валют.');
            }

            $rates = collect($response->json())
                ->filter(fn ($row): bool => is_array($row)
                    && in_array($row['ccy'] ?? null, ['USD', 'EUR'], true)
                    && ($row['base_ccy'] ?? null) === 'UAH'
                    && is_numeric($row['sale'] ?? null)
                    && (float) $row['sale'] > 0)
                ->mapWithKeys(fn (array $row): array => [$row['ccy'] => (float) $row['sale']])
                ->all();

            if (! isset($rates['USD'], $rates['EUR'])) {
                throw new RuntimeException('ПриватБанк не повернув курс SALE для USD та EUR.');
            }

            return [
                'rates' => $rates,
                'fetched_at' => now()->utc()->toIso8601String(),
                'source' => 'PrivatBank',
                'type' => 'SALE',
            ];
        });
    }

    /** @return array{rate: float, fetched_at: Carbon, source: string, type: string} */
    public function quote(string $currency): array
    {
        $payload = $this->currentSaleRates();
        $rate = $payload['rates'][$currency] ?? null;

        if (! is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException("Курс SALE для {$currency} наразі недоступний.");
        }

        return [
            'rate' => (float) $rate,
            'fetched_at' => Carbon::parse($payload['fetched_at'])->utc(),
            'source' => $payload['source'],
            'type' => $payload['type'],
        ];
    }
}
