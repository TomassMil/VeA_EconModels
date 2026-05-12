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
     */
    public function currentMarketValue(Portfolio $portfolio): float
    {
        $rows = DB::table('portfolio_instrument as pi')
            ->leftJoin('prices_daily as pd', function ($join) {
                $join->on('pd.instrument_id', '=', 'pi.instrument_id')
                    ->whereRaw('pd.time = (SELECT MAX(p.time) FROM prices_daily p WHERE p.instrument_id = pi.instrument_id AND p.close IS NOT NULL)');
            })
            ->where('pi.portfolio_id', $portfolio->id)
            ->where('pi.shares', '>', 0)
            ->select(['pi.shares', 'pd.close'])
            ->get();

        $total = 0.0;
        foreach ($rows as $r) {
            if ($r->close !== null) {
                $total += (float) $r->shares * (float) $r->close;
            }
        }

        return $total;
    }
}
