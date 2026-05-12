<?php

namespace App\Services\Backtest\Strategies;

use App\Services\Backtest\BacktestStrategyInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Altman Z-Score (for manufacturing/publicly traded firms):
 *   Z = 1.2·X1 + 1.4·X2 + 3.3·X3 + 0.6·X4 + 1.0·X5
 *
 * Kur:
 *   X1 = (current_assets - current_liabilities) / total_assets   — likviditāte
 *   X2 = retained_earnings / total_assets                        — kumulatīvā peļņa
 *   X3 = operating_income / total_assets                         — operatīvā efektivitāte
 *   X4 = (shares_diluted × price) / total_liabilities            — tirgus vērtība pret parādiem
 *   X5 = revenue / total_assets                                  — aktīvu izmantošana
 *
 * Interpretācija:
 *   Z > 2.99 = drošā zonā
 *   1.81 < Z < 2.99 = pelēkā zonā
 *   Z < 1.81 = bankrota risks
 *
 * Stratēģija: paņem N akcijas ar augstāko Z-Score (visstabilākās).
 *
 * IZSLĒDZ bankas un apdrošināšanas (cita biznesa modelis, formulai jābūt cita).
 *
 * Params:
 *   top_n: int  — cik akcijas iekļaut (default 20)
 */
class AltmanZTopN implements BacktestStrategyInterface
{
    public function key(): string
    {
        return 'altman_z_top_n';
    }

    public function name(): string
    {
        return 'Altman Z-Score (Top-N)';
    }

    public function description(): string
    {
        return 'Paņem N akcijas ar augstāko Z-Score (zema bankrota varbūtība). Izslēdz bankas/apdrošināšanu.';
    }

    public function selectInstruments(string $baseDate, array $params): Collection
    {
        $topN = (int) ($params['top_n'] ?? 20);
        $baseYear = (int) substr($baseDate, 0, 4);
        $fundamentalYear = $baseYear - 1;       // pēdējais pilnais FY pirms bāzes datuma

        // SimFin glabā ceturkšņu datus:
        //   - Balance sheet: paņem Q4 (snapshot uz gada beigām)
        //   - Income statement: summējam Q1+Q2+Q3+Q4 (gada plūsma)
        // Izslēdzam bankas/apdrošināšanu.
        $rows = DB::select(
            "WITH bs_q4 AS (
                SELECT instrument_id, total_assets, total_liabilities,
                       total_current_assets, total_current_liabilities,
                       retained_earnings, shares_diluted, source_type
                FROM simfin_balance_sheet
                WHERE fiscal_year = ? AND fiscal_period = 'Q4'
                  AND total_assets > 0 AND total_liabilities > 0 AND shares_diluted > 0
                  AND source_type NOT IN ('banks', 'insurance')
             ), inc_yr AS (
                SELECT instrument_id,
                       SUM(operating_income) AS operating_income,
                       SUM(revenue) AS revenue,
                       COUNT(*) AS q_count
                FROM simfin_income_statement
                WHERE fiscal_year = ? AND fiscal_period IN ('Q1','Q2','Q3','Q4')
                  AND source_type NOT IN ('banks', 'insurance')
                GROUP BY instrument_id
                HAVING COUNT(*) = 4
             )
             SELECT bs.instrument_id, bs.total_assets, bs.total_liabilities,
                    bs.total_current_assets, bs.total_current_liabilities,
                    bs.retained_earnings, bs.shares_diluted,
                    inc.operating_income, inc.revenue
             FROM bs_q4 bs
             JOIN inc_yr inc ON inc.instrument_id = bs.instrument_id",
            [$fundamentalYear, $fundamentalYear]
        );

        if (empty($rows)) {
            return collect();
        }

        $instrumentIds = collect($rows)->pluck('instrument_id')->all();
        $prices = $this->fetchPricesAt($instrumentIds, $baseDate);

        $scored = [];
        foreach ($rows as $r) {
            $iid = (int) $r->instrument_id;
            $price = $prices[$iid] ?? null;
            if ($price === null || $price <= 0) {
                continue;
            }

            $ta = (float) $r->total_assets;
            $tl = (float) $r->total_liabilities;
            $ca = (float) ($r->total_current_assets ?? 0);
            $cl = (float) ($r->total_current_liabilities ?? 0);
            $re = (float) ($r->retained_earnings ?? 0);
            $op = (float) ($r->operating_income ?? 0);
            $rev = (float) ($r->revenue ?? 0);
            $sh = (float) $r->shares_diluted;

            $x1 = ($ca - $cl) / $ta;
            $x2 = $re / $ta;
            $x3 = $op / $ta;
            $x4 = ($sh * $price) / $tl;
            $x5 = $rev / $ta;

            $z = 1.2 * $x1 + 1.4 * $x2 + 3.3 * $x3 + 0.6 * $x4 + 1.0 * $x5;

            $scored[] = ['instrument_id' => $iid, 'score' => $z];
        }

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
     * Time-bound window 14 dienas — TimescaleDB chunk pruning.
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
