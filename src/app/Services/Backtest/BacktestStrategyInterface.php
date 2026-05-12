<?php

namespace App\Services\Backtest;

use Illuminate\Support\Collection;

/**
 * Visi backtest stratēģijas (gan vienkāršās — Equal Weight Buy & Hold,
 * gan modeļu balstītās — Graham, Altman Z) implementē šo interfeisu.
 *
 * Stratēģijas uzdevums: dotā bāzes datumā atgriezt instrumentu sarakstu ar svariem.
 * BacktestRunner pēc tam aprēķina akciju skaitu un izveido transakcijas.
 */
interface BacktestStrategyInterface
{
    /**
     * Stabils atslēgas nosaukums (snake_case), izmanto DB un URL.
     */
    public function key(): string;

    /**
     * Cilvēkam lasāms nosaukums UI (latviski).
     */
    public function name(): string;

    /**
     * Īss apraksts par to, ko stratēģija dara.
     */
    public function description(): string;

    /**
     * Atgriež instrumentu sarakstu ar svariem dotā bāzes datumā.
     *
     * @param  string  $baseDate  ISO datums (YYYY-MM-DD)
     * @param  array  $params  Stratēģijas parametri (piem. top_n=20, instrument_ids=[1,2,3])
     * @return Collection<array{instrument_id: int, weight: float}>  weight summējas uz 1.0
     */
    public function selectInstruments(string $baseDate, array $params): Collection;
}
