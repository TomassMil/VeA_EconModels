<?php

namespace Database\Seeders;

use App\Models\Instrument;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@econmodels.lv'],
            [
                'name' => 'TestUser',
                'password' => Hash::make('TestPass'),
            ]
        );

        // Create a default portfolio for the test user
        $aapl = Instrument::where('ticker', 'AAPL')->first();
        $msft = Instrument::where('ticker', 'MSFT')->first();

        if ($aapl && $msft) {
            $portfolio = Portfolio::updateOrCreate(
                ['user_id' => $user->id, 'name' => 'Mans portfelis'],
                ['currency' => 'USD', 'free_capital' => 6000.00]
            );

            // Get latest prices to calculate shares
            $aaplPrice = \Illuminate\Support\Facades\DB::table('prices_daily')
                ->where('instrument_id', $aapl->id)
                ->whereNotNull('close')
                ->orderByDesc('time')
                ->value('close');

            $msftPrice = \Illuminate\Support\Facades\DB::table('prices_daily')
                ->where('instrument_id', $msft->id)
                ->whereNotNull('close')
                ->orderByDesc('time')
                ->value('close');

            $aaplShares = $aaplPrice ? round(2000 / (float) $aaplPrice, 6) : 0;
            $msftShares = $msftPrice ? round(2000 / (float) $msftPrice, 6) : 0;

            $portfolio->instruments()->syncWithoutDetaching([
                $aapl->id => ['amount_invested' => 2000.00, 'shares' => $aaplShares],
                $msft->id => ['amount_invested' => 2000.00, 'shares' => $msftShares],
            ]);
        }
    }
}
