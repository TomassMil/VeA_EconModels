<?php

namespace App\Services\Risk;

use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;

/**
 * Aprēķina portfeļa atdevi (peļņu) procentos.
 *
 * Formula:
 *   peļņa = (pašreizējā_vērtība + brīvais_kapitāls - neto_iemaksas) / neto_iemaksas
 *
 * Kur:
 *   pašreizējā_vērtība = Σ (shares × current_close) visiem holdingiem
 *   brīvais_kapitāls   = visu portfolio_transactions amount summa
 *   neto_iemaksas      = deposit/withdrawal tipa transakciju summa
 *
 * NB! Šī formula vēl tiek precizēta. Pagaidām atbilst PortfolioController::buildSummary().
 */
class PortfolioReturnCalculator
{
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

        // Izmantojam datu pēdējo datumu, nevis now() — dati var beigties agrāk par šodienu.
        $latestDataTime = DB::table('prices_daily')->max('time');
        if ($latestDataTime === null) {
            return 0.0;
        }
        $windowStart = \Carbon\Carbon::parse($latestDataTime)->subDays(60)->toDateString();

        $priceRows = DB::select(
            'SELECT DISTINCT ON (instrument_id) instrument_id, close
             FROM prices_daily
             WHERE instrument_id = ANY(?)
               AND close IS NOT NULL
               AND time >= ?
             ORDER BY instrument_id, time DESC',
            [$idsArray, $windowStart]
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
