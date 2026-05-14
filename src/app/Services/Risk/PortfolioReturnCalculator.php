<?php

namespace App\Services\Risk;

use App\Models\Portfolio;
use App\Services\ChartService;
use Illuminate\Support\Facades\DB;

/**
 * Aprēķina portfeļa atdevi (peļņu) procentos.
 *
 * Pasniedzēja formula (vienota gan akcijai, gan portfelim):
 *   peļņa = (pēdējā_diena - pirmā_diena) / pirmā_diena
 *
 * Portfeļa kontekstā:
 *   pirmā_diena = sākotnējais kapitāls (= net_deposits, t.i. visu deposit/withdrawal summa)
 *   pēdējā_diena = pašreizējā kopējā vērtība (= cash + market_value)
 *
 * Tas ekvivalents:
 *   peļņa = (pašreizējā_vērtība + brīvais_kapitāls − neto_iemaksas) / neto_iemaksas
 *
 * Backtest portfeļiem ar vienreizēju iemaksu (visi mūsu sistēmas/wizard portfeļi)
 * formula precīzi atbilst akcijas formulai.
 */
class PortfolioReturnCalculator
{
    public function __construct(private ChartService $chartService) {}

    /**
     * Atdeve par konkrētu logu (gados) — atbilstoši riska periodu pogai.
     *
     * Formula: (current_value - value_X_years_ago) / value_X_years_ago
     *
     * Ja portfeļa izveidošana ir VĒLĀK nekā $years atpakaļ, atgriež atdevi no portfeļa izveidošanas.
     * Atgriež null, ja portfelim nav vēstures vai sākotnējā vērtība ir 0.
     */
    public function periodReturn(Portfolio $portfolio, int $years): ?float
    {
        $series = $this->chartService->buildPortfolioSeries($portfolio);
        $points = $series['points'] ?? [];
        if (count($points) < 2) {
            return null;
        }

        $lastPoint = end($points);
        $lastValue = (float) ($lastPoint['value'] ?? 0);
        if ($lastValue <= 0) {
            return null;
        }

        // Atrod target datumu = pēdējais datums - $years
        $lastDate = \Carbon\Carbon::parse($lastPoint['date']);
        $targetDate = $lastDate->copy()->subYears($years)->toDateString();

        // Atrod pirmo punktu >= targetDate (vai izmanto pirmo, ja portfelis jaunāks)
        $startValue = null;
        foreach ($points as $p) {
            if ($p['date'] >= $targetDate) {
                $startValue = (float) ($p['value'] ?? 0);
                break;
            }
        }
        if ($startValue === null) {
            $startValue = (float) ($points[0]['value'] ?? 0);
        }

        if ($startValue <= 0) {
            return null;
        }

        return round(($lastValue - $startValue) / $startValue, 4);
    }


    /**
     * Atgriež portfeļa kopējo atdevi procentos vai null, ja nav neto iemaksu.
     * Piemēram: 0.15 = 15% peļņa, -0.08 = 8% zaudējumi.
     */
    public function totalReturn(Portfolio $portfolio): ?float
    {
        $cash = (float) DB::table('portfolio_transactions')
            ->where('portfolio_id', $portfolio->id)
            ->sum('amount');

        $netDeposits = (float) DB::table('portfolio_transactions')
            ->where('portfolio_id', $portfolio->id)
            ->whereIn('type', ['deposit', 'withdrawal'])
            ->sum('amount');

        if ($netDeposits <= 0) {
            return null;
        }

        $marketValue = $this->currentMarketValue($portfolio);
        $portfolioValue = $cash + $marketValue;

        return round(($portfolioValue - $netDeposits) / $netDeposits, 4);
    }

    /**
     * Pašreizējās holdingu tirgus vērtības summa.
     *
     * Optimizēts: viens vaicājums ar DISTINCT ON + time-bound logs TimescaleDB chunk pruning.
     */
    public function currentMarketValue(Portfolio $portfolio): float
    {
        $holdings = DB::table('portfolio_instrument')
            ->where('portfolio_id', $portfolio->id)
            ->where('shares', '>', 0)
            ->pluck('shares', 'instrument_id');

        if ($holdings->isEmpty()) {
            return 0.0;
        }

        $ids = $holdings->keys()->all();
        $idsArray = '{' . implode(',', $ids) . '}';

        // Bez time bound — paņem JEBKURU pēdējo cenu katram holdingam.
        // Tas iekļauj arī delisted/inactive akcijas, lai būtu konsekvents ar
        // PortfolioController::buildSummary() kas arī to dara.
        // Performance: portfelī parasti < 30 holdingu, DISTINCT ON ar indeksu = ~10ms.
        $priceRows = DB::select(
            'SELECT DISTINCT ON (instrument_id) instrument_id, close
             FROM prices_daily
             WHERE instrument_id = ANY(?)
               AND close IS NOT NULL
             ORDER BY instrument_id, time DESC',
            [$idsArray]
        );

        $total = 0.0;
        foreach ($priceRows as $r) {
            $iid = (int) $r->instrument_id;
            if (isset($holdings[$iid])) {
                $total += (float) $holdings[$iid] * (float) $r->close;
            }
        }

        return $total;
    }
}
