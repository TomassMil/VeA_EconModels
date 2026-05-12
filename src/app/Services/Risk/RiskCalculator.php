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
            ->where('pi.portfolio_id', $portfolio->id)
            ->where('pi.shares', '>', 0)
            ->select(['pi.instrument_id', 'pi.shares'])
            ->get();

        if ($holdings->isEmpty()) {
            return null;
        }

        $instrumentIds = $holdings->pluck('instrument_id')->all();
        $idsArray = '{' . implode(',', $instrumentIds) . '}';

        // TimescaleDB chunk pruning: izmantojam datu PĒDĒJO datumu kā atskaiti (nevis now()),
        // jo dati var beigties agrāk par šodienu. Logs = pēdējās 60 dienas no max(time).
        $latestDataTime = DB::table('prices_daily')->max('time');
        if ($latestDataTime === null) {
            return null;
        }
        $windowStart = \Carbon\Carbon::parse($latestDataTime)->subDays(60)->toDateString();

        // 1. Visu holdingu pēdējās 30+1 dienu cenas vienā vaicājumā (nevis 20 vaicājumi)
        $allRows = DB::select(
            'SELECT instrument_id, time, close, volume
             FROM prices_daily
             WHERE instrument_id = ANY(?)
               AND close IS NOT NULL
               AND time >= ?
             ORDER BY instrument_id, time',
            [$idsArray, $windowStart]
        );

        // Grupē pēc instrument_id
        $byInstrument = [];
        foreach ($allRows as $r) {
            $byInstrument[(int) $r->instrument_id][] = $r;
        }

        $weightedRiskSum = 0.0;
        $totalValue = 0.0;

        foreach ($holdings as $h) {
            $iid = (int) $h->instrument_id;
            $rows = $byInstrument[$iid] ?? [];
            if (count($rows) < 2) {
                continue;
            }

            // Tikai pēdējās $days + 1 dienas riska aprēķinam
            $window = array_slice($rows, -($days + 1));
            $negCount = 0;
            $negVolume = 0.0;
            $totalVolume = 0.0;
            $totalDays = 0;

            for ($i = 1; $i < count($window); $i++) {
                $prev = (float) $window[$i - 1]->close;
                $curr = (float) $window[$i]->close;
                $vol = (float) ($window[$i]->volume ?? 0);
                $totalDays++;
                $totalVolume += $vol;
                if ($curr < $prev) {
                    $negCount++;
                    $negVolume += $vol;
                }
            }
            if ($totalDays === 0) {
                continue;
            }

            $sRisk = $negCount / $totalDays;
            $vRisk = $totalVolume > 0 ? ($negVolume / $totalVolume) : 0.0;
            $instrRisk = self::S_WEIGHT * $sRisk + self::V_WEIGHT * $vRisk;

            $latestPrice = (float) end($rows)->close;
            if ($latestPrice <= 0) {
                continue;
            }

            $marketValue = (float) $h->shares * $latestPrice;
            $weightedRiskSum += $instrRisk * $marketValue;
            $totalValue += $marketValue;
        }

        if ($totalValue <= 0) {
            return null;
        }

        return round($weightedRiskSum / $totalValue, 4);
    }
}
