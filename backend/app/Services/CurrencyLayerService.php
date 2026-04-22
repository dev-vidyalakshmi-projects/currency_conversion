<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CurrencyLayerService
{
    private string $apiKey;
    private string $baseUrl;
    private string $historyBaseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.currency_layer.api_key');
        $this->baseUrl = config('services.currency_layer.base_url');
        $this->historyBaseUrl = config('services.currency_layer.history_base_url');
    }

    public function getLiveRates(array $currencies = []): array
    {
        $key = 'live_rates_' . implode('_', $currencies);

        return Cache::remember($key, now()->addMinutes(30), function () use ($currencies) {
            $response = Http::get("{$this->baseUrl}/live", [
                'access_key' => $this->apiKey,
                'source'     => 'USD',
                'currencies' => implode(',', $currencies),
                'format'     => 1,
            ]);

            if (!$response->successful()) {
                throw new RuntimeException('Failed to fetch live rates.');
            }

            $data = $response->json();

            if (empty($data['success'])) {
                throw new RuntimeException($data['error']['info'] ?? 'API error.');
            }

            return $data['quotes'] ?? [];
        });
    }

    public function getSupportedCurrencies(): array
    {
        return Cache::remember('supported_currencies', now()->addDay(), function () {
            $response = Http::get("{$this->historyBaseUrl}/currencies");

            if (!$response->successful()) {
                throw new RuntimeException('Failed to fetch currency list.');
            }

            $currencies = $response->json();
            $currencies['USD'] = 'United States Dollar';
            ksort($currencies);


            if (empty($currencies)) {
                throw new RuntimeException('Failed to fetch supported currencies.');
            }

            return $currencies;
        });
    }



    public function getHistoricalRate(string $currency, string $date): float
    {

        if ($currency === 'USD') {
            return 1.0;
        }

        $cacheKey = "historical_{$currency}_{$date}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($currency, $date) {

            $response = Http::get("{$this->historyBaseUrl}/{$date}", [
                'from'    => 'USD',
                'to'      => $currency,
            ]);

            if (!$response->successful()) {
                Log::warning("Frankfurter API failed for {$currency} on {$date}", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new RuntimeException(
                    "Failed to fetch historical rate for {$currency} on {$date}."
                );
            }

            $data = $response->json();

            if (!isset($data['rates'][$currency])) {
                throw new RuntimeException(
                    "No rate found for {$currency} on {$date}."
                );
            }

            return (float) $data['rates'][$currency];
        });
    }
}
