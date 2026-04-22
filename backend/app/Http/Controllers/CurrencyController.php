<?php

namespace App\Http\Controllers;

use App\Models\UserCurrency;
use App\Services\CurrencyLayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        private CurrencyLayerService $currencyService
    ) {}

    public function index(): JsonResponse
    {
        $currencies = $this->currencyService->getSupportedCurrencies();

        return response()->json(['currencies' => $currencies]);
    }

    public function selected(Request $request): JsonResponse
    {
        $selected = $request->user()
            ->selectedCurrencies()
            ->pluck('currency_code')
            ->toArray();

        $rates = [];
        if (!empty($selected)) {
            $liveRates = $this->currencyService->getLiveRates($selected);
            foreach ($selected as $code) {
                $rates[$code] = $liveRates["USD{$code}"] ?? null;
            }
        }

        return response()->json([
            'selected_currencies' => $selected,
            'rates'               => $rates,
        ]);
    }

    public function updateSelected(Request $request): JsonResponse
    {
        $request->validate([
            'currencies'   => ['required', 'array', 'min:1', 'max:5'],
            'currencies.*' => ['required', 'string', 'size:3'],
        ]);

        $user = $request->user();
        $user->selectedCurrencies()->delete();

        foreach ($request->currencies as $code) {
            UserCurrency::create([
                'user_id'       => $user->id,
                'currency_code' => strtoupper($code),
            ]);
        }

        return response()->json([
            'message'    => 'Currencies updated successfully.',
            'currencies' => $request->currencies,
        ]);
    }

    public function liveRates(Request $request): JsonResponse
    {
        $selected = $request->user()
            ->selectedCurrencies()
            ->pluck('currency_code')
            ->toArray();

        if (empty($selected)) {
            return response()->json([
                'message' => 'No currencies selected.',
                'rates'   => [],
            ]);
        }

        $liveRates = $this->currencyService->getLiveRates($selected);

        $rates = [];
        foreach ($selected as $code) {
            $rates[] = [
                'currency' => $code,
                'rate'     => $liveRates["USD{$code}"] ?? null,
            ];
        }

        return response()->json([
            'source'    => 'USD',
            'rates'     => $rates,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
