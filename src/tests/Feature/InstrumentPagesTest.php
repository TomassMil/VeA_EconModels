<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstrumentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_instrument_index_lists_instruments_and_supports_search(): void
    {
        $appleId = DB::table('instruments')->insertGetId([
            'ticker' => 'AAPL',
            'company_name' => 'Apple Inc',
            'exchange' => 'NASDAQ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('instruments')->insert([
            'ticker' => 'MSFT',
            'company_name' => 'Microsoft Corporation',
            'exchange' => 'NASDAQ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('instruments.index', ['q' => 'AAP']));

        $response->assertOk();
        $response->assertSee('AAPL');
        $response->assertSee('Apple Inc');
        $response->assertDontSee('MSFT');
        $response->assertSee(route('instruments.show', ['instrument' => $appleId]), false);
    }

    public function test_instrument_show_page_renders_chart_section(): void
    {
        $instrumentId = DB::table('instruments')->insertGetId([
            'ticker' => 'NVDA',
            'company_name' => 'NVIDIA Corporation',
            'exchange' => 'NASDAQ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prices_daily')->insert([
            [
                'instrument_id' => $instrumentId,
                'time' => '2026-01-02',
                'open' => 100,
                'low' => 99,
                'high' => 101,
                'close' => 100.50,
                'adj_close' => 100.50,
                'volume' => 1000000,
            ],
            [
                'instrument_id' => $instrumentId,
                'time' => '2026-01-03',
                'open' => 101,
                'low' => 100,
                'high' => 103,
                'close' => 102.25,
                'adj_close' => 102.25,
                'volume' => 1200000,
            ],
        ]);

        $response = $this->get(route('instruments.show', ['instrument' => $instrumentId]));

        $response->assertOk();
        $response->assertSee('fundamentals-year-list', false);
        $response->assertSee('price-chart');
        $response->assertSee('chart-mode-close');
        $response->assertSee('chart-mode-ohlc');
    }

    public function test_instrument_search_endpoint_returns_substring_matches(): void
    {
        DB::table('instruments')->insert([
            [
                'ticker' => 'AAPL',
                'company_name' => 'Apple Inc',
                'exchange' => 'NASDAQ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ticker' => 'SNAP',
                'company_name' => 'Snap Inc',
                'exchange' => 'NYSE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ticker' => 'MSFT',
                'company_name' => 'Microsoft Corporation',
                'exchange' => 'NASDAQ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson(route('instruments.search', ['q' => 'Ap']));

        $response->assertOk();
        $response->assertJsonPath('data.0.ticker', 'AAPL');
        $response->assertJsonFragment(['ticker' => 'SNAP']);
        $response->assertJsonMissing(['ticker' => 'MSFT']);
    }

    public function test_instrument_index_hides_missing_company_name_text(): void
    {
        DB::table('instruments')->insert([
            'ticker' => 'QQQ',
            'company_name' => null,
            'exchange' => 'NASDAQ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('instruments.index'));

        $response->assertOk();
        $response->assertDontSee('Uzņēmuma nosaukums nav pieejams');
    }
}
