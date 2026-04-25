<?php

namespace App\Services;

use App\Models\Portfolio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChartService
{
    private const DAILY_THRESHOLD_DAYS = 1825;

    public function resolutionFor(Carbon $start, Carbon $end): string
    {
        return $start->diffInDays($end) > self::DAILY_THRESHOLD_DAYS ? 'weekly' : 'daily';
    }

    public function buildPortfolioSeries(Portfolio $portfolio): array
    {
        $txns = DB::table('portfolio_transactions')
            ->where('portfolio_id', $portfolio->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        if ($txns->isEmpty()) {
            return ['resolution' => 'daily', 'points' => []];
        }

        $startDate = Carbon::parse($txns->first()->transaction_date)->startOfDay();
        $endDate = Carbon::today();
        if ($startDate->gte($endDate)) {
            $endDate = $startDate->copy();
        }

        $resolution = $this->resolutionFor($startDate, $endDate);

        $instrumentIds = $txns->whereNotNull('instrument_id')->pluck('instrument_id')->unique()->values()->all();

        $priceMap = $this->loadDailyClosesMap($instrumentIds, $startDate, $endDate);

        $tradingDates = $this->loadTradingDates($startDate, $endDate);
        if (empty($tradingDates)) {
            return ['resolution' => $resolution, 'points' => []];
        }

        $sampleDates = $resolution === 'weekly'
            ? $this->sampleWeekly($tradingDates)
            : $tradingDates;

        $orderedTxns = $txns->sortBy(fn ($t) => substr((string) $t->transaction_date, 0, 10))->values();
        $txnIdx = 0;
        $txnCount = $orderedTxns->count();

        $cash = 0.0;
        $positions = [];
        $lastPrice = [];
        $points = [];

        $sampleSet = array_flip($sampleDates);

        foreach ($tradingDates as $date) {
            while ($txnIdx < $txnCount && substr((string) $orderedTxns[$txnIdx]->transaction_date, 0, 10) <= $date) {
                $t = $orderedTxns[$txnIdx];
                $cash += (float) $t->amount;
                if ($t->instrument_id !== null && $t->shares !== null) {
                    $iid = (int) $t->instrument_id;
                    $positions[$iid] = ($positions[$iid] ?? 0.0) + (float) $t->shares;
                    if (abs($positions[$iid]) < 1e-9) {
                        unset($positions[$iid]);
                    }
                }
                $txnIdx++;
            }

            foreach ($positions as $iid => $shares) {
                if (isset($priceMap[$iid][$date])) {
                    $lastPrice[$iid] = $priceMap[$iid][$date];
                }
            }

            if (!isset($sampleSet[$date])) {
                continue;
            }

            $marketValue = 0.0;
            foreach ($positions as $iid => $shares) {
                $px = $lastPrice[$iid] ?? null;
                if ($px !== null) {
                    $marketValue += $shares * $px;
                }
            }

            $points[] = [
                'date' => $date,
                'value' => round($cash + $marketValue, 2),
                'cash' => round($cash, 2),
                'market_value' => round($marketValue, 2),
            ];
        }

        return ['resolution' => $resolution, 'points' => $points];
    }

    public function buildIndexSeries(array $instrumentIds, string $weighting = 'market_cap'): array
    {
        if (empty($instrumentIds)) {
            return ['resolution' => 'daily', 'weighting' => $weighting, 'points' => []];
        }

        $rows = match ($weighting) {
            'equal' => $this->queryEqualWeightedSeries($instrumentIds),
            'price' => $this->queryPriceWeightedSeries($instrumentIds),
            default => $this->queryMarketCapSeries($instrumentIds),
        };

        if (empty($rows)) {
            return ['resolution' => 'daily', 'weighting' => $weighting, 'points' => []];
        }

        $startDate = Carbon::parse($rows[0]['date'])->startOfDay();
        $endDate = Carbon::parse(end($rows)['date'])->startOfDay();
        $resolution = $this->resolutionFor($startDate, $endDate);

        if ($resolution === 'weekly') {
            $byWeek = [];
            foreach ($rows as $r) {
                $week = Carbon::parse($r['date'])->format('o-W');
                $byWeek[$week] = $r;
            }
            $rows = array_values($byWeek);
        }

        $base = (float) $rows[0]['raw'];
        if ($base == 0.0) {
            return ['resolution' => $resolution, 'weighting' => $weighting, 'points' => []];
        }

        $points = [];
        foreach ($rows as $r) {
            $points[] = [
                'date' => $r['date'],
                'value' => round(((float) $r['raw'] / $base) * 100, 4),
                'constituents' => (int) $r['constituents'],
            ];
        }

        return ['resolution' => $resolution, 'weighting' => $weighting, 'points' => $points];
    }

    private function queryMarketCapSeries(array $instrumentIds): array
    {
        $idList = implode(',', array_map('intval', $instrumentIds));

        $sql = "
            WITH constituent_prices AS (
                SELECT instrument_id, time::date AS d, close
                FROM prices_daily
                WHERE instrument_id IN ({$idList}) AND close IS NOT NULL
            ),
            shares_with_next AS (
                SELECT
                    instrument_id,
                    report_date,
                    LEAD(report_date) OVER (PARTITION BY instrument_id ORDER BY report_date) AS next_report_date,
                    shares_basic
                FROM simfin_balance_sheet
                WHERE instrument_id IN ({$idList}) AND shares_basic IS NOT NULL
            ),
            priced_with_shares AS (
                SELECT
                    cp.d,
                    cp.close * sw.shares_basic AS mcap
                FROM constituent_prices cp
                JOIN shares_with_next sw
                  ON sw.instrument_id = cp.instrument_id
                 AND sw.report_date <= cp.d
                 AND (sw.next_report_date IS NULL OR sw.next_report_date > cp.d)
            )
            SELECT d::text AS date, SUM(mcap) AS raw, COUNT(*) AS constituents
            FROM priced_with_shares
            GROUP BY d
            ORDER BY d
        ";

        return array_map(
            fn ($r) => ['date' => $r->date, 'raw' => $r->raw, 'constituents' => $r->constituents],
            DB::select($sql)
        );
    }

    private function queryEqualWeightedSeries(array $instrumentIds): array
    {
        $idList = implode(',', array_map('intval', $instrumentIds));

        $sql = "
            WITH constituent_prices AS (
                SELECT instrument_id, time::date AS d, close,
                       FIRST_VALUE(close) OVER (PARTITION BY instrument_id ORDER BY time) AS first_close
                FROM prices_daily
                WHERE instrument_id IN ({$idList}) AND close IS NOT NULL
            )
            SELECT d::text AS date, AVG(close / NULLIF(first_close, 0)) AS raw, COUNT(*) AS constituents
            FROM constituent_prices
            WHERE first_close > 0
            GROUP BY d
            ORDER BY d
        ";

        return array_map(
            fn ($r) => ['date' => $r->date, 'raw' => $r->raw, 'constituents' => $r->constituents],
            DB::select($sql)
        );
    }

    private function queryPriceWeightedSeries(array $instrumentIds): array
    {
        $idList = implode(',', array_map('intval', $instrumentIds));

        $sql = "
            SELECT time::date::text AS date, AVG(close) AS raw, COUNT(*) AS constituents
            FROM prices_daily
            WHERE instrument_id IN ({$idList}) AND close IS NOT NULL
            GROUP BY time::date
            ORDER BY time::date
        ";

        return array_map(
            fn ($r) => ['date' => $r->date, 'raw' => $r->raw, 'constituents' => $r->constituents],
            DB::select($sql)
        );
    }

    private function loadDailyClosesMap(array $instrumentIds, Carbon $start, Carbon $end): array
    {
        if (empty($instrumentIds)) {
            return [];
        }

        $rows = DB::table('prices_daily')
            ->select(['instrument_id', 'time', 'close'])
            ->whereIn('instrument_id', $instrumentIds)
            ->whereNotNull('close')
            ->whereBetween('time', [$start->toDateString(), $end->toDateString()])
            ->orderBy('instrument_id')
            ->orderBy('time')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->instrument_id][substr((string) $r->time, 0, 10)] = (float) $r->close;
        }
        return $map;
    }

    private function loadSharesBasicMap(array $instrumentIds): array
    {
        if (empty($instrumentIds)) {
            return [];
        }

        $rows = DB::table('simfin_balance_sheet')
            ->select(['instrument_id', 'report_date', 'shares_basic'])
            ->whereIn('instrument_id', $instrumentIds)
            ->whereNotNull('shares_basic')
            ->orderBy('instrument_id')
            ->orderBy('report_date')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->instrument_id][substr((string) $r->report_date, 0, 10)] = (float) $r->shares_basic;
        }
        return $map;
    }

    private function loadTradingDates(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('prices_daily')
            ->selectRaw('DISTINCT time::date AS d')
            ->whereBetween('time', [$start->toDateString(), $end->toDateString()])
            ->orderBy('d')
            ->get();

        return $rows->map(fn ($r) => substr((string) $r->d, 0, 10))->all();
    }

    private function sampleWeekly(array $tradingDates): array
    {
        if (empty($tradingDates)) {
            return [];
        }

        $byWeek = [];
        foreach ($tradingDates as $date) {
            $week = Carbon::parse($date)->format('o-W');
            $byWeek[$week] = $date;
        }
        return array_values($byWeek);
    }
}
