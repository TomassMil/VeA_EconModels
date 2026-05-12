<?php

namespace App\Services\Risk;

use App\Models\Instrument;
use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;

/**
 * Aprēķina riska rādītāju instrumentam un portfelim, balstoties uz Engela trijstūra
 * negatīvo dienu (sarkano rūtiņu) analīzi noteiktā periodā.
 *
 * NB! Formula vēl tiek precizēta ar pasniedzēju. Pašreizējā implementācija:
 *   s_risk = sarkano_dienu_skaits / kopā_dienas              (0-1)
 *   v_risk = sarkano_dienu_volume / kopā_volume              (0-1)
 *   risk   = 0.5 * s_risk + 0.5 * v_risk                     (0-1)
 *
 * Sarkana diena = close[t] < close[t-1].
 * Periods (default 30 dienas) būs konfigurējams ar pogu uz /portfelis lapas.
 */
class RiskCalculator
{
    public const DEFAULT_DAYS = 30;
    public const S_WEIGHT = 0.5;
    public const V_WEIGHT = 0.5;

    /**
     * Atgriež instrumenta riska rādītāju [0, 1] vai null, ja nav pietiekoši daudz datu.
     */
    public function instrumentRisk(Instrument $instrument, int $days = self::DEFAULT_DAYS): ?float
    {
        $rows = DB::table('prices_daily')
            ->select(['time', 'close', 'volume'])
            ->where('instrument_id', $instrument->id)
            ->whereNotNull('close')
            ->orderByDesc('time')
            ->limit($days + 1)
            ->get()
            ->reverse()
            ->values();

        if ($rows->count() < 2) {
            return null;
        }

        $negativeCount = 0;
        $negativeVolume = 0.0;
        $totalVolume = 0.0;
        $totalDays = 0;

        for ($i = 1; $i < $rows->count(); $i++) {
            $prevClose = (float) $rows[$i - 1]->close;
            $currClose = (float) $rows[$i]->close;
            $vol = (float) ($rows[$i]->volume ?? 0);

            $totalDays++;
            $totalVolume += $vol;

            if ($currClose < $prevClose) {
                $negativeCount++;
                $negativeVolume += $vol;
            }
        }

        if ($totalDays === 0) {
            return null;
        }

        $sRisk = $negativeCount / $totalDays;
        $vRisk = $totalVolume > 0 ? ($negativeVolume / $totalVolume) : 0.0;

        return round(self::S_WEIGHT * $sRisk + self::V_WEIGHT * $vRisk, 4);
    }

    /**
     * Atgriež portfeļa riska rādītāju [0, 1] kā svērtu vidējo no holdingu riskiem,
     * kur svari ir holdinga tirgus vērtība / kopējā holdingu vērtība.
     *
     * Brīvais kapitāls (cash) tiek izslēgts no svariem (pieņemam, ka cash risk = 0
     * nedod ieguldījumu).
     */
    public function portfolioRisk(Portfolio $portfolio, int $days = self::DEFAULT_DAYS): ?float
    {
        $holdings = DB::table('portfolio_instrument as pi')
            ->join('instruments as i', 'i.id', '=', 'pi.instrument_id')
            ->where('pi.portfolio_id', $portfolio->id)
            ->where('pi.shares', '>', 0)
            ->select(['pi.instrument_id', 'pi.shares'])
            ->get();

        if ($holdings->isEmpty()) {
            return null;
        }

        $instrumentIds = $holdings->pluck('instrument_id')->all();

        $latestPrices = DB::table('prices_daily as p1')
            ->whereIn('p1.instrument_id', $instrumentIds)
            ->whereNotNull('p1.close')
            ->whereRaw('p1.time = (SELECT MAX(p2.time) FROM prices_daily p2 WHERE p2.instrument_id = p1.instrument_id AND p2.close IS NOT NULL)')
            ->pluck('p1.close', 'p1.instrument_id');

        $weightedRiskSum = 0.0;
        $totalValue = 0.0;

        foreach ($holdings as $h) {
            $price = (float) ($latestPrices[$h->instrument_id] ?? 0);
            if ($price <= 0) {
                continue;
            }

            $marketValue = (float) $h->shares * $price;
            $instrument = Instrument::find($h->instrument_id);
            if (! $instrument) {
                continue;
            }

            $instrRisk = $this->instrumentRisk($instrument, $days);
            if ($instrRisk === null) {
                continue;
            }

            $weightedRiskSum += $instrRisk * $marketValue;
            $totalValue += $marketValue;
        }

        if ($totalValue <= 0) {
            return null;
        }

        return round($weightedRiskSum / $totalValue, 4);
    }
}
