<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $latestPriceDate = DB::table('prices_daily')
            ->whereNotNull('close')
            ->max('time');
        $latestPriceDate = $latestPriceDate ? substr((string) $latestPriceDate, 0, 10) : null;

        // For backfilled rows whose original date is after the price coverage,
        // synthesize a date one year before the latest price so the chart has history to plot.
        $syntheticDate = $latestPriceDate
            ? \Illuminate\Support\Carbon::parse($latestPriceDate)->subYear()->toDateString()
            : null;

        $portfolios = DB::table('portfolios')->get();

        foreach ($portfolios as $portfolio) {
            $holdings = DB::table('portfolio_instrument')
                ->where('portfolio_id', $portfolio->id)
                ->get();

            $totalInvested = (float) $holdings->sum('amount_invested');
            $totalDeposited = $totalInvested + (float) $portfolio->free_capital;

            $depositDate = substr($portfolio->created_at, 0, 10);
            if ($latestPriceDate !== null && $depositDate > $latestPriceDate) {
                $depositDate = $syntheticDate;
            }

            DB::table('portfolio_transactions')->insert([
                'portfolio_id' => $portfolio->id,
                'instrument_id' => null,
                'type' => 'deposit',
                'transaction_date' => $depositDate,
                'shares' => null,
                'price_per_share' => null,
                'amount' => $totalDeposited,
                'currency' => $portfolio->currency,
                'note' => 'Backfilled initial deposit',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($holdings as $h) {
                $shares = (float) $h->shares;
                $amount = (float) $h->amount_invested;
                $price = $shares > 0 ? round($amount / $shares, 6) : null;

                $buyDate = substr($h->created_at, 0, 10);
                if ($latestPriceDate !== null && $buyDate > $latestPriceDate) {
                    $buyDate = $syntheticDate;
                }

                DB::table('portfolio_transactions')->insert([
                    'portfolio_id' => $portfolio->id,
                    'instrument_id' => $h->instrument_id,
                    'type' => 'buy',
                    'transaction_date' => $buyDate,
                    'shares' => $shares,
                    'price_per_share' => $price,
                    'amount' => -$amount,
                    'currency' => $portfolio->currency,
                    'note' => 'Backfilled from portfolio_instrument',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('portfolio_transactions')->truncate();
    }
};
