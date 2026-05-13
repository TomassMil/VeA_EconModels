<?php

namespace App\Services\Risk;

use App\Models\Instrument;
use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;

/**
 * Riska aprēķins, balstīts uz Engela trijstūri.
 *
 * Engela trijstūris (kā tas tiek zīmēts uz instrumenta lapas):
 *   - Katra rinda `r` = sākotnējā diena (close[r])
 *   - Katra kolonna `c > r` salīdzina close[c] ar close[r]:
 *       zaļa, ja close[c] >= close[r] (peļņa)
 *       sarkana, ja close[c] < close[r] (zaudējums)
 *   - "Cell value" = close[c] - close[r]
 *   - Trijstūrim ir N(N-1)/2 šūnu (kur N = dienu skaits)
 *
 * Riska metrikas:
 *   s_risk = sarkano šūnu skaits / kopā šūnu skaits     (vienmēr 0-1)
 *   v_risk = |Σ sarkano šūnu vērtību|                   (negatīvo "baseinu" tilpums)
 *
 * Normalizācija:
 *   Trijstūra laukums skalējas kvadrātiski ar gadu skaitu (N² / 2 šūnas).
 *   Tāpēc bāzes dalītājs = gadi² × 1000.
 *
 *   Papildus dalām ar `avg_price` (perioda vidējā close cena), lai v_risk
 *   būtu salīdzināms starp akcijām ar dažādu cenu mērogu — citādi $400 akcija
 *   (TSLA) vienmēr būs ar v_risk daudz lielāku par $50 akciju (KO) tikai
 *   tās lielākās dolāru skalas dēļ. Cell vērtības paliek absolūtas (dolāri).
 *
 *   v_risk_norm = v_risk_raw / (gadi² × 1000 × avg_price)
 *
 * Risks:
 *   risk = 0.5 × s_risk + 0.5 × min(1.0, v_risk_norm)        (cap pie 1.0)
 *
 * Periods iedotas GADOS, nevis dienās.
 * 250 tirdzniecības dienu uz 1 gadu (vidēji, neskaitot brīvdienas).
 */
class RiskCalculator
{
    public const DEFAULT_YEARS = 1;
    public const TRADING_DAYS_PER_YEAR = 250;
    public const VOLUME_DIVISOR_BASE = 1000;

    public const S_WEIGHT = 0.5;
    public const V_WEIGHT = 0.5;

    /** Request-scoped cache: instrumentId|years => risk */
    private static array $cache = [];

    /**
     * Atgriež instrumenta riska rādītāju vai null, ja nav pietiekoši datu.
     *
     * Vērtība var pārsniegt 1.0 ļoti svārstīgām akcijām (pasniedzēja konstantes
     * 1000 reizinātājs ir empīrisks). UI var to cap pie 1, ja vēlams.
     */
    public function instrumentRisk(Instrument $instrument, int $years = self::DEFAULT_YEARS): ?float
    {
        $cacheKey = $instrument->id . '|' . $years;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $closes = $this->loadLatestCloses($instrument->id, $years * self::TRADING_DAYS_PER_YEAR);
        $risk = $this->computeRiskFromCloses($closes, $years);

        self::$cache[$cacheKey] = $risk;
        return $risk;
    }

    /**
     * Atgriež portfeļa riska rādītāju kā svērtu vidējo no holdingu riskiem.
     * Svari = holdingu pašreizējās tirgus vērtības attiecībā pret kopējo.
     */
    public function portfolioRisk(Portfolio $portfolio, int $years = self::DEFAULT_YEARS): ?float
    {
        $holdings = DB::table('portfolio_instrument')
            ->where('portfolio_id', $portfolio->id)
            ->where('shares', '>', 0)
            ->pluck('shares', 'instrument_id');

        if ($holdings->isEmpty()) {
            return null;
        }

        $instrumentIds = $holdings->keys()->all();
        $idsArray = '{' . implode(',', $instrumentIds) . '}';

        // Pēdējais pieejamais datu datums (jo dati var beigties pirms šodienas)
        $latestDataTime = DB::table('prices_daily')->max('time');
        if ($latestDataTime === null) {
            return null;
        }

        // Pēdējās gadi×250 dienas + buferis 30 dienas (lai būtu vietas market_value lookup)
        $needDays = $years * self::TRADING_DAYS_PER_YEAR + 30;
        $windowStart = \Carbon\Carbon::parse($latestDataTime)->subDays((int) ($needDays * 1.5))->toDateString();

        $rows = DB::select(
            'SELECT instrument_id, close FROM prices_daily
             WHERE instrument_id = ANY(?)
               AND close IS NOT NULL
               AND time >= ? AND time <= ?
             ORDER BY instrument_id, time',
            [$idsArray, $windowStart, $latestDataTime]
        );

        // Grupē closes pēc instrument_id, saglabā tikai pēdējās N dienas
        $closesByInstrument = [];
        foreach ($rows as $r) {
            $closesByInstrument[(int) $r->instrument_id][] = (float) $r->close;
        }

        $weightedRiskSum = 0.0;
        $totalValue = 0.0;
        $maxDays = $years * self::TRADING_DAYS_PER_YEAR;

        foreach ($holdings as $iid => $shares) {
            $closes = $closesByInstrument[$iid] ?? [];
            if (count($closes) < 2) {
                continue;
            }

            // Pēdējās gadi×250 dienas
            if (count($closes) > $maxDays) {
                $closes = array_slice($closes, -$maxDays);
            }

            $instrRisk = $this->computeRiskFromCloses($closes, $years);
            if ($instrRisk === null) {
                continue;
            }

            $latestPrice = end($closes);
            if ($latestPrice <= 0) {
                continue;
            }

            $marketValue = (float) $shares * $latestPrice;
            $weightedRiskSum += $instrRisk * $marketValue;
            $totalValue += $marketValue;
        }

        if ($totalValue <= 0) {
            return null;
        }

        return round($weightedRiskSum / $totalValue, 4);
    }

    /**
     * Kodols: aprēķina risku no closing cenu masīva (sakārtots augšuplaiku).
     *
     * Engel trijstūris: N(N-1)/2 šūnas, katra ir close[c] - close[r] kur c > r.
     * Sarkanas (negative) šūnas: close[c] < close[r].
     */
    private function computeRiskFromCloses(array $closes, int $years): ?float
    {
        $n = count($closes);
        if ($n < 2) {
            return null;
        }

        $totalCells = 0;
        $redCount = 0;
        $negDepthSum = 0.0;
        $priceSum = 0.0;

        // Naivs O(N²) — N≤2500 (10 gadi) → ~3.1M operāciju ≤1s PHP
        for ($r = 0; $r < $n - 1; $r++) {
            $rowClose = $closes[$r];
            $priceSum += $rowClose;
            for ($c = $r + 1; $c < $n; $c++) {
                $diff = $closes[$c] - $rowClose;
                $totalCells++;
                if ($diff < 0) {
                    $redCount++;
                    $negDepthSum += -$diff;     // abs value
                }
            }
        }
        $priceSum += $closes[$n - 1];  // include last close in avg
        $avgPrice = $priceSum / $n;

        if ($totalCells === 0 || $avgPrice <= 0) {
            return null;
        }

        $sRisk = $redCount / $totalCells;
        $vRiskNorm = $negDepthSum / ($years * $years * self::VOLUME_DIVISOR_BASE * $avgPrice);
        $vRiskNorm = min(1.0, $vRiskNorm);      // cap at 1.0 for highly volatile stocks

        $risk = self::S_WEIGHT * $sRisk + self::V_WEIGHT * $vRiskNorm;
        return round($risk, 4);
    }

    /** Latest N closes for an instrument, sorted oldest → newest. */
    private function loadLatestCloses(int $instrumentId, int $maxDays): array
    {
        return DB::table('prices_daily')
            ->where('instrument_id', $instrumentId)
            ->whereNotNull('close')
            ->orderByDesc('time')
            ->limit($maxDays)
            ->pluck('close')
            ->reverse()
            ->map(fn ($v) => (float) $v)
            ->values()
            ->all();
    }
}
