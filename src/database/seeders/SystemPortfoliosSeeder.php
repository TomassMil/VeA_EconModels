<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\Portfolio;
use App\Services\Backtest\BacktestRunner;
use App\Services\Backtest\StrategyRegistry;
use App\Services\Backtest\Strategies\EqualWeightBuyHold;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Veido sistēmas portfeļus, kas ir redzami visiem lietotājiem uz risk-vs-return scatter plot:
 *   - Modeļu portfeļi (zaļi punkti): Graham Top-20, Altman Z Top-20 — bāzes datums 2021-04-01
 *   - Indeksu portfeļi (sarkani punkti): S&P 500 (SPY), Nasdaq-100 (QQQ), Dow Jones (DIA) — buy-and-hold no 2018-04-01
 *
 * Indeksu portfeļi ir atzīmēti ar "INDEX:" prefiksu aprakstā, lai tos atšķirtu no modeļiem.
 *
 * Palaišana:
 *   docker compose exec app php artisan db:seed --class=SystemPortfoliosSeeder
 */
class SystemPortfoliosSeeder extends Seeder
{
    public function run(): void
    {
        // Notīra esošos sistēmas portfeļus, lai būtu idempotents
        $existing = Portfolio::system()->get();
        foreach ($existing as $p) {
            $this->command->info("Dzēšu esošo: {$p->name}");
            $p->instruments()->detach();
            DB::table('portfolio_transactions')->where('portfolio_id', $p->id)->delete();
            $p->delete();
        }

        $runner = app(BacktestRunner::class);
        $registry = app(StrategyRegistry::class);
        $capital = 10000;
        $baseDate = '2021-04-01';

        // 1. Graham Top-20
        $this->command->info("Veidoju Graham Top-20...");
        $runner->run($registry->get('graham_top_n'), [
            'name' => 'Graham Intrinsic Value Top-20',
            'description' => 'Modelis: V = EPS × (8.5 + 2g). Top 20 akcijas ar augstāko V/cena 2020.gada beigās.',
            'base_date' => $baseDate,
            'capital' => $capital,
            'params' => ['top_n' => 20],
            'is_system' => true,
            'user_id' => null,
        ]);

        // 2. Altman Z-Score Top-20
        $this->command->info("Veidoju Altman Z Top-20...");
        $runner->run($registry->get('altman_z_top_n'), [
            'name' => 'Altman Z-Score Top-20',
            'description' => 'Modelis: 5-faktoru bankrota formula. Top 20 akcijas ar augstāko Z 2020.gada beigās (izslēdzot bankas/apdrošināšanu).',
            'base_date' => $baseDate,
            'capital' => $capital,
            'params' => ['top_n' => 20],
            'is_system' => true,
            'user_id' => null,
        ]);

        // 3. Index ETFs — buy-and-hold no 2018-04-01 (vecākais datums, ko atļauj wizard)
        $indexBaseDate = '2018-04-02';      // 2018-04-01 bija svētdiena
        $indexes = [
            ['ticker' => 'SPY', 'name' => 'S&P 500 indekss (SPY ETF)', 'description' => 'INDEX: S&P 500 buy-and-hold no 2018-04-02 (SPY ETF tracker).'],
            ['ticker' => 'QQQ', 'name' => 'Nasdaq-100 indekss (QQQ ETF)', 'description' => 'INDEX: Nasdaq-100 buy-and-hold no 2018-04-02 (QQQ ETF tracker).'],
            ['ticker' => 'DIA', 'name' => 'Dow Jones Industrial (DIA ETF)', 'description' => 'INDEX: Dow Jones Industrial Average buy-and-hold no 2018-04-02 (DIA ETF tracker).'],
        ];

        $equalWeight = $registry->get('equal_weight_buy_hold');
        foreach ($indexes as $idx) {
            $inst = Instrument::where('ticker', $idx['ticker'])->first();
            if (! $inst) {
                $this->command->warn("Izlaižu {$idx['ticker']}: instruments nav DB");
                continue;
            }
            $this->command->info("Veidoju {$idx['name']}...");
            $runner->run($equalWeight, [
                'name' => $idx['name'],
                'description' => $idx['description'],
                'base_date' => $indexBaseDate,
                'capital' => $capital,
                'params' => ['instrument_ids' => [$inst->id]],
                'is_system' => true,
                'user_id' => null,
            ]);
        }

        $count = Portfolio::system()->count();
        $this->command->info("Pabeigts. Izveidoti {$count} sistēmas portfeļi.");
    }
}
