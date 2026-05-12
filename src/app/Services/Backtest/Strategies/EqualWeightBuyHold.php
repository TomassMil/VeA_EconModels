<?php

namespace App\Services\Backtest\Strategies;

use App\Services\Backtest\BacktestStrategyInterface;
use Illuminate\Support\Collection;

/**
 * Vienlīdzīgo svaru pirkt-un-turēt stratēģija.
 *
 * Lietotājs izvēlas N instrumentus → katram pieskirsts 1/N kapitāla daļa.
 * Transakcijas tiek izveidotas bāzes datumā, instrumenti tiek turēti līdz šim brīdim.
 *
 * Params:
 *   instrument_ids: int[]  — masīvs ar instrumentu ID
 */
class EqualWeightBuyHold implements BacktestStrategyInterface
{
    public function key(): string
    {
        return 'equal_weight_buy_hold';
    }

    public function name(): string
    {
        return 'Vienlīdzīgi svari (Buy & Hold)';
    }

    public function description(): string
    {
        return 'Sadala kapitālu vienādās daļās starp izvēlētajiem instrumentiem un tur tos līdz beigām.';
    }

    public function selectInstruments(string $baseDate, array $params): Collection
    {
        $ids = $params['instrument_ids'] ?? [];
        if (empty($ids)) {
            return collect();
        }

        $n = count($ids);
        $weight = 1.0 / $n;

        return collect($ids)->map(fn ($id) => [
            'instrument_id' => (int) $id,
            'weight' => $weight,
        ]);
    }
}
