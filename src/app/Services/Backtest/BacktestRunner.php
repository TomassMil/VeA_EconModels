<?php

namespace App\Services\Backtest;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Palaiž backtest stratēģiju un izveido portfeli + transakcijas.
 *
 * Plūsma:
 *   1. Stratēģija atgriež instrumentu sarakstu ar svariem
 *   2. Aprēķina cenas bāzes datumā
 *   3. Aprēķina akciju skaitu katram instrumentam
 *   4. Vienā transakcijā: izveido portfeli, ieraksta deposit + buy transakcijas
 */
class BacktestRunner
{
    /**
     * @param  array  $config  [name, description, base_date, capital, params, is_system, user_id]
     * @return Portfolio  jaunizveidotais portfelis
     */
    public function run(BacktestStrategyInterface $strategy, array $config): Portfolio
    {
        $baseDate = $config['base_date'];
        $capital = (float) $config['capital'];
        $params = $config['params'] ?? [];

        $selections = $strategy->selectInstruments($baseDate, $params);

        if ($selections->isEmpty()) {
            throw new \RuntimeException("Stratēģija '{$strategy->key()}' neatgrieza nevienu instrumentu.");
        }

        // Override weights if custom_weights provided (wizard preview manual edit)
        if (! empty($config['custom_weights'])) {
            $weightMap = collect($config['custom_weights'])
                ->keyBy('instrument_id')
                ->map(fn ($w) => (float) ($w['weight'] ?? 0));

            $selections = $selections->filter(fn ($s) => isset($weightMap[$s['instrument_id']]) && $weightMap[$s['instrument_id']] > 0)
                ->map(function ($s) use ($weightMap) {
                    $s['weight'] = $weightMap[$s['instrument_id']];
                    return $s;
                })->values();

            if ($selections->isEmpty()) {
                throw new \RuntimeException('Custom weights neatstāja nevienu instrumentu.');
            }

            // Respektējam lietotāja patieso svaru summu — ja < 1.0, atliek brīvais kapitāls;
            // ja > 1.0 (+ neliels epsilons floating-point dēļ), noraidām, lai nepārsniegtu kapitālu.
            $weightSum = $selections->sum('weight');
            if ($weightSum > 1.0001) {
                throw new \RuntimeException(sprintf(
                    'Svaru summa pārsniedz 100%% (%.2f%%). Samazini svarus tabulā.',
                    $weightSum * 100
                ));
            }
        }

        // Cenas bāzes datumā: tuvākā agrākā tirgus diena katram instrumentam
        $instrumentIds = $selections->pluck('instrument_id')->all();
        $prices = $this->fetchPricesAt($instrumentIds, $baseDate);

        $missingPrices = $selections->filter(fn ($s) => ! isset($prices[$s['instrument_id']]));
        if ($missingPrices->isNotEmpty()) {
            throw new \RuntimeException(
                "Nav cenu datu instrumentiem (ID): " . $missingPrices->pluck('instrument_id')->implode(', ') . " uz {$baseDate}"
            );
        }

        return DB::transaction(function () use ($strategy, $config, $selections, $prices, $baseDate, $capital) {
            $portfolio = Portfolio::create([
                'user_id' => $config['user_id'] ?? null,
                'is_system' => $config['is_system'] ?? false,
                'name' => $config['name'],
                'description' => $config['description'] ?? null,
                'currency' => 'USD',
                'free_capital' => 0,
            ]);

            // 1. Sākotnējā iemaksa
            PortfolioTransaction::create([
                'portfolio_id' => $portfolio->id,
                'instrument_id' => null,
                'type' => 'deposit',
                'transaction_date' => $baseDate,
                'shares' => null,
                'price_per_share' => null,
                'amount' => $capital,
                'currency' => 'USD',
                'note' => "Backtest sākums: {$strategy->name()}",
            ]);

            $totalSpent = 0.0;

            // 2. Pirkumi
            foreach ($selections as $sel) {
                $instrumentId = $sel['instrument_id'];
                $weight = $sel['weight'];
                $price = $prices[$instrumentId];
                $allocation = $capital * $weight;
                // Floor (round down) shares — garantē, ka totālais izlietojums NEPĀRSNIEDZ kapitālu.
                // Citādi noapaļošana var dot free_capital negatīvu (piem. -$0.11) pēc 20 picks.
                $shares = floor(($allocation / $price) * 1000) / 1000;

                if ($shares <= 0) {
                    continue;
                }

                $actualCost = round($shares * $price, 2);
                $totalSpent += $actualCost;

                $portfolio->instruments()->attach($instrumentId, [
                    'amount_invested' => $actualCost,
                    'shares' => $shares,
                ]);

                PortfolioTransaction::create([
                    'portfolio_id' => $portfolio->id,
                    'instrument_id' => $instrumentId,
                    'type' => 'buy',
                    'transaction_date' => $baseDate,
                    'shares' => $shares,
                    'price_per_share' => $price,
                    'amount' => -$actualCost,
                    'currency' => 'USD',
                ]);
            }

            // 3. Atjaunina brīvo kapitālu (atlikums pēc pirkumiem)
            $portfolio->update([
                'free_capital' => round($capital - $totalSpent, 2),
            ]);

            return $portfolio->fresh();
        });
    }

    /**
     * @param  int[]  $instrumentIds
     * @return array<int, float>  instrument_id => close price
     *
     * Time-bound window (14 dienas pirms baseDate) — TimescaleDB chunk pruning.
     */
    private function fetchPricesAt(array $instrumentIds, string $baseDate): array
    {
        $start = date('Y-m-d', strtotime($baseDate . ' -14 days'));
        $rows = DB::select(
            'SELECT DISTINCT ON (instrument_id) instrument_id, close
             FROM prices_daily
             WHERE instrument_id = ANY(?)
               AND close IS NOT NULL
               AND time BETWEEN ? AND ?
             ORDER BY instrument_id, time DESC',
            ['{' . implode(',', $instrumentIds) . '}', $start, $baseDate]
        );

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->instrument_id] = (float) $r->close;
        }
        return $map;
    }
}
