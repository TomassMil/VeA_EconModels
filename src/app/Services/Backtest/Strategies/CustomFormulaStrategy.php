<?php

namespace App\Services\Backtest\Strategies;

use App\Services\Backtest\BacktestStrategyInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NXP\MathExecutor;

/**
 * Pielāgota formula — lietotājs ievada matemātisku izteiksmi, kas tiek izpildīta
 * katram instrumentam ar pieejamiem fundamentāliem + tehniskiem mainīgajiem.
 *
 * Params:
 *   formula: string    — math izteiksme (piem. "revenue / market_cap + 2 * eps")
 *   top_n:   int       — cik akcijas iekļaut portfelī (default 20)
 *
 * Drošība: izmanto NXP\MathExecutor — bez eval(), tikai atļauti math operatori
 * + funkcijas + iepriekš whitelistēti mainīgie.
 */
class CustomFormulaStrategy implements BacktestStrategyInterface
{
    public const VARIABLES = [
        // Tehniskie
        'price' => 'Pēdējā close cena bāzes datumā ($)',
        'volume_avg' => 'Vidējais dienas apjoms (shares) pēdējās 90d',

        // Fundamentāli — ienākumi
        'revenue' => 'Gada ieņēmumi ($)',
        'gross_profit' => 'Gada bruto peļņa ($)',
        'operating_income' => 'Gada operatīvā peļņa ($)',
        'net_income' => 'Gada tīrā peļņa ($)',

        // Fundamentāli — bilance
        'total_assets' => 'Kopējie aktīvi ($)',
        'total_liabilities' => 'Kopējās saistības ($)',
        'total_equity' => 'Pašu kapitāls ($)',
        'total_current_assets' => 'Apgrozāmie aktīvi ($)',
        'total_current_liabilities' => 'Īstermiņa saistības ($)',
        'retained_earnings' => 'Nesadalītā peļņa ($)',
        'shares' => 'Akciju skaits (shares_diluted)',

        // Atvasinātie
        'eps' => 'Peļņa uz akciju ($) = net_income / shares',
        'market_cap' => 'Tirgus kapitalizācija ($) = price × shares',
        'pe_ratio' => 'P/E koeficients = price / eps',
        'pb_ratio' => 'P/B koeficients = market_cap / total_equity',
        'ps_ratio' => 'P/S koeficients = market_cap / revenue',
        'roe' => 'Return on Equity = net_income / total_equity',
        'roa' => 'Return on Assets = net_income / total_assets',
        'gross_margin' => 'Bruto peļņas marža = gross_profit / revenue',
        'operating_margin' => 'Operatīvā marža = operating_income / revenue',
        'net_margin' => 'Tīrā peļņas marža = net_income / revenue',
        'debt_to_equity' => 'Parāda/kapitāla koeficients = total_liabilities / total_equity',
        'current_ratio' => 'Likviditātes koef. = current_assets / current_liabilities',
    ];

    public const FUNCTIONS = [
        'abs(x)' => 'Absolūtā vērtība',
        'sqrt(x)' => 'Kvadrātsakne',
        'log(x)' => 'Naturālais logaritms',
        'exp(x)' => 'Eksponente',
        'pow(x, y)' => 'x pakāpē y',
        'min(a, b, ...)' => 'Minimālā vērtība',
        'max(a, b, ...)' => 'Maksimālā vērtība',
        'round(x)' => 'Apaļošana',
        'ceil(x)' => 'Apaļošana uz augšu',
        'floor(x)' => 'Apaļošana uz leju',
    ];

    public function key(): string
    {
        return 'custom_formula';
    }

    public function name(): string
    {
        return 'Pielāgota formula';
    }

    public function description(): string
    {
        return 'Lietotāja definēta matemātiska izteiksme ar fundamentāliem + tehniskiem mainīgajiem.';
    }

    public function selectInstruments(string $baseDate, array $params): Collection
    {
        $formula = trim((string) ($params['formula'] ?? ''));
        $topN = (int) ($params['top_n'] ?? 20);

        if ($formula === '') {
            throw new \RuntimeException('Formula nav norādīta.');
        }

        $rows = $this->loadVariablesForAllInstruments($baseDate);
        if (empty($rows)) {
            return collect();
        }

        $executor = new MathExecutor();

        // Validate formula syntax once
        try {
            $executor->setVars(array_fill_keys(array_keys(self::VARIABLES), 1.0));
            $executor->execute($formula);   // dry run with dummy vars
        } catch (\Throwable $e) {
            throw new \RuntimeException('Formulas sintakses kļūda: ' . $e->getMessage());
        }

        $scored = [];
        foreach ($rows as $r) {
            $vars = $this->buildVariables($r);
            if ($vars === null) {
                continue;       // skip if essential data missing
            }

            try {
                $executor->setVars($vars);
                $score = $executor->execute($formula);
            } catch (\Throwable $e) {
                continue;       // skip on per-instrument eval error (e.g. div by zero)
            }

            if (! is_numeric($score) || ! is_finite($score)) {
                continue;
            }

            $scored[] = ['instrument_id' => (int) $r->instrument_id, 'score' => (float) $score];
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
     * Vienā SQL ielādē visus instrumentus ar to fundamentāliem + cenu + apjomu.
     * Izmanto LATERAL joins efektivitātei.
     */
    private function loadVariablesForAllInstruments(string $baseDate): array
    {
        $year = (int) substr($baseDate, 0, 4) - 1;       // FY pirms bāzes datuma
        $start = date('Y-m-d', strtotime($baseDate . ' -14 days'));
        $volumeStart = date('Y-m-d', strtotime($baseDate . ' -90 days'));

        return DB::select(
            "SELECT
                i.id AS instrument_id,
                bs.total_assets,
                bs.total_liabilities,
                bs.total_equity,
                bs.total_current_assets,
                bs.total_current_liabilities,
                bs.retained_earnings,
                bs.shares_diluted,
                inc.revenue,
                inc.gross_profit,
                inc.operating_income,
                inc.net_income,
                p.close AS price,
                pv.avg_volume
             FROM instruments i
             LEFT JOIN LATERAL (
                SELECT total_assets, total_liabilities, total_equity, total_current_assets,
                       total_current_liabilities, retained_earnings, shares_diluted
                FROM simfin_balance_sheet
                WHERE instrument_id = i.id AND fiscal_year = ? AND fiscal_period = 'Q4'
                LIMIT 1
             ) bs ON true
             LEFT JOIN LATERAL (
                SELECT SUM(revenue) AS revenue,
                       SUM(gross_profit) AS gross_profit,
                       SUM(operating_income) AS operating_income,
                       SUM(net_income) AS net_income,
                       COUNT(*) AS q_count
                FROM simfin_income_statement
                WHERE instrument_id = i.id AND fiscal_year = ?
                  AND fiscal_period IN ('Q1', 'Q2', 'Q3', 'Q4')
             ) inc ON inc.q_count = 4
             LEFT JOIN LATERAL (
                SELECT close FROM prices_daily
                WHERE instrument_id = i.id AND close IS NOT NULL
                  AND time BETWEEN ? AND ?
                ORDER BY time DESC LIMIT 1
             ) p ON true
             LEFT JOIN LATERAL (
                SELECT AVG(volume)::float AS avg_volume FROM prices_daily
                WHERE instrument_id = i.id AND volume IS NOT NULL
                  AND time BETWEEN ? AND ?
             ) pv ON true
             WHERE p.close IS NOT NULL",
            [$year, $year, $start, $baseDate, $volumeStart, $baseDate]
        );
    }

    /**
     * Konvertē DB rindu uz pilno mainīgo karti, t.sk. aprēķina atvasinātos.
     * Atgriež null, ja kritiski dati trūkst (price vai shares).
     */
    private function buildVariables(object $r): ?array
    {
        $price = (float) ($r->price ?? 0);
        $shares = (float) ($r->shares_diluted ?? 0);

        if ($price <= 0 || $shares <= 0) {
            return null;
        }

        $revenue = (float) ($r->revenue ?? 0);
        $netIncome = (float) ($r->net_income ?? 0);
        $totalAssets = (float) ($r->total_assets ?? 0);
        $totalLiabilities = (float) ($r->total_liabilities ?? 0);
        $totalEquity = (float) ($r->total_equity ?? 0);
        $totalCurrentAssets = (float) ($r->total_current_assets ?? 0);
        $totalCurrentLiabilities = (float) ($r->total_current_liabilities ?? 0);
        $grossProfit = (float) ($r->gross_profit ?? 0);
        $operatingIncome = (float) ($r->operating_income ?? 0);
        $retainedEarnings = (float) ($r->retained_earnings ?? 0);
        $volumeAvg = (float) ($r->avg_volume ?? 0);

        $eps = $netIncome / $shares;
        $marketCap = $price * $shares;

        return [
            // Technical
            'price' => $price,
            'volume_avg' => $volumeAvg,

            // Income
            'revenue' => $revenue,
            'gross_profit' => $grossProfit,
            'operating_income' => $operatingIncome,
            'net_income' => $netIncome,

            // Balance
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_current_assets' => $totalCurrentAssets,
            'total_current_liabilities' => $totalCurrentLiabilities,
            'retained_earnings' => $retainedEarnings,
            'shares' => $shares,

            // Derived (defaults to 0 to avoid div-by-zero in formulas)
            'eps' => $eps,
            'market_cap' => $marketCap,
            'pe_ratio' => $eps != 0 ? $price / $eps : 0,
            'pb_ratio' => $totalEquity != 0 ? $marketCap / $totalEquity : 0,
            'ps_ratio' => $revenue != 0 ? $marketCap / $revenue : 0,
            'roe' => $totalEquity != 0 ? $netIncome / $totalEquity : 0,
            'roa' => $totalAssets != 0 ? $netIncome / $totalAssets : 0,
            'gross_margin' => $revenue != 0 ? $grossProfit / $revenue : 0,
            'operating_margin' => $revenue != 0 ? $operatingIncome / $revenue : 0,
            'net_margin' => $revenue != 0 ? $netIncome / $revenue : 0,
            'debt_to_equity' => $totalEquity != 0 ? $totalLiabilities / $totalEquity : 0,
            'current_ratio' => $totalCurrentLiabilities != 0 ? $totalCurrentAssets / $totalCurrentLiabilities : 0,
        ];
    }
}
