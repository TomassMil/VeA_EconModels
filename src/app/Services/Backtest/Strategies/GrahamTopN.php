<?php

namespace App\Services\Backtest\Strategies;

use App\Services\Backtest\BacktestStrategyInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Graham Intrinsic Value formula:
 *   V = EPS × (8.5 + 2g)
 *
 * Kur:
 *   EPS = net_income / shares_diluted (pēdējais pilnais finanšu gads pirms bāzes datuma)
 *   g   = EPS pieaugums (CAGR) pēdējos 3 gados, iekapsulēts pie 15%
 *
 * Score = V / current_price
 *   - Score > 1.0 nozīmē, ka akcija ir lētāka par Graham aplēsto vērtību
 *   - Lielāks score = labāka vērtība
 *
 * Top-N: paņem N akcijas ar augstāko score.
 *
 * Params:
 *   top_n: int  — cik akcijas iekļaut (default 20)
 */
class GrahamTopN implements BacktestStrategyInterface
{
    public function key(): string
    {
        return 'graham_top_n';
    }

    public function name(): string
    {
        return 'Graham Intrinsic Value (Top-N)';
    }

    public function description(): string
    {
        return 'Score = V/cena, kur V = EPS × (8.5 + 2g). Paņem N akcijas ar augstāko vērtības attiecību.';
    }

    public function selectInstruments(string $baseDate, array $params): Collection
    {
        $topN = (int) ($params['top_n'] ?? 20);
        $cagrYears = (int) ($params['cagr_years'] ?? 2);    // SimFin sākas 2018, tāpēc 2 gadi praktiskāk
        $baseYear = (int) substr($baseDate, 0, 4);
        $fundamentalYear = $baseYear - 1;       // pēdējais pilnais FY pirms bāzes datuma
        $oldYear = $fundamentalYear - $cagrYears;

        // EPS pēdējais gads + 3 gadus atpakaļ — annual data only
        // SimFin glabā ceturkšņu datus — agregējam Q1-Q4 uz pilnu gadu (sum income, max shares).
        $rows = DB::select(
            "WITH yr_curr AS (
                SELECT instrument_id,
                       SUM(net_income) AS net_income,
                       MAX(shares_diluted) AS shares_diluted,
                       COUNT(*) AS q_count
                FROM simfin_income_statement
                WHERE fiscal_year = ? AND fiscal_period IN ('Q1','Q2','Q3','Q4')
                  AND net_income IS NOT NULL AND shares_diluted > 0
                GROUP BY instrument_id
                HAVING COUNT(*) = 4
             ), yr_old AS (
                SELECT instrument_id,
                       SUM(net_income) AS net_income,
                       MAX(shares_diluted) AS shares_diluted
                FROM simfin_income_statement
                WHERE fiscal_year = ? AND fiscal_period IN ('Q1','Q2','Q3','Q4')
                  AND net_income IS NOT NULL AND shares_diluted > 0
                GROUP BY instrument_id
                HAVING COUNT(*) = 4
             )
             SELECT c.instrument_id,
                    c.net_income / NULLIF(c.shares_diluted, 0) AS eps_now,
                    o.net_income / NULLIF(o.shares_diluted, 0) AS eps_old
             FROM yr_curr c
             JOIN yr_old o ON o.instrument_id = c.instrument_id",
            [$fundamentalYear, $oldYear]
        );

        if (empty($rows)) {
            return collect();
        }

        $instrumentIds = collect($rows)->pluck('instrument_id')->all();
        $prices = $this->fetchPricesAt($instrumentIds, $baseDate);

        $scored = [];
        foreach ($rows as $r) {
            $iid = (int) $r->instrument_id;
            $epsNow = (float) $r->eps_now;
            $epsOld = (float) $r->eps_old;
            $price = $prices[$iid] ?? null;

            if ($epsNow <= 0 || $epsOld <= 0 || $price === null || $price <= 0) {
                continue;       // negatīva peļņa vai nav cenas — izlaižam
            }

            // CAGR (gads-pēc-gada), capped at 15%
            $g = pow($epsNow / $epsOld, 1.0 / max($cagrYears, 1)) - 1.0;
            $g = min(0.15, max(-0.15, $g));     // -15% līdz +15%
            $g_pct = $g * 100;

            $intrinsicValue = $epsNow * (8.5 + 2 * $g_pct);
            if ($intrinsicValue <= 0) {
                continue;
            }

            $score = $intrinsicValue / $price;

            // SimFin dažkārt satur acīmredzami kļūdainus datus (piem. quarter net_income
            // tūkstoš reizes par lielu). Score > 50 nozīmē P/E < 0.02 — nereāli pat
            // visdziļākajām value akcijām. Izlaižam šādus outlierus.
            if ($score > 50) {
                continue;
            }

            $scored[] = ['instrument_id' => $iid, 'score' => $score];
        }

        // Sort by score desc, take top N
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scored, 0, $topN);

        if (empty($top)) {
            return collect();
        }

        $weight = 1.0 / count($top);
        return collect($top)->map(fn ($s) => [
            'instrument_id' => $s['instrument_id'],
            'weight' => $weight,
            'score' => round($s['score'], 4),
        ]);
    }

    /**
     * @param  int[]  $instrumentIds
     * @return array<int, float>
     *
     * Time-bound window (14 dienas pirms baseDate) lai TimescaleDB izmanto
     * chunk pruning — bez tā 3000 instrumenti × 5 gadi vēstures = 23s; ar to ~500ms.
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
