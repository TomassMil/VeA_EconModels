<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Services\ChartService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __construct(private ChartService $chartService) {}


    public function index(): View
    {
        $portfolios = Portfolio::where('user_id', Auth::id())
            ->withCount('instruments')
            ->orderByDesc('updated_at')
            ->get();

        return view('portfolios.index', [
            'portfolios' => $portfolios,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'free_capital' => 'required|numeric|min:0',
        ]);

        $portfolio = Portfolio::create([
            'user_id' => Auth::id(),
            'name' => $request->input('name'),
            'free_capital' => $request->input('free_capital'),
        ]);

        if ((float) $request->input('free_capital') > 0) {
            PortfolioTransaction::create([
                'portfolio_id' => $portfolio->id,
                'instrument_id' => null,
                'type' => 'deposit',
                'transaction_date' => now()->toDateString(),
                'amount' => (float) $request->input('free_capital'),
                'currency' => $portfolio->currency,
                'note' => 'Initial deposit',
            ]);
        }

        return redirect()->route('portfolios.show', $portfolio);
    }

    public function show(Portfolio $portfolio): View
    {
        Gate::authorize('view', $portfolio);

        $instruments = $portfolio->instruments()
            ->select(['instruments.id', 'ticker', 'company_name', 'exchange'])
            ->orderBy('ticker')
            ->get();

        $chart = $this->chartService->buildPortfolioSeries($portfolio);
        $transactions = $this->loadTransactions($portfolio);

        if ($instruments->isEmpty()) {
            return view('portfolios.show', [
                'portfolio' => $portfolio,
                'cards' => collect(),
                'summary' => $this->buildSummary($portfolio, collect()),
                'chart' => $chart,
                'transactions' => $transactions,
            ]);
        }

        $instrumentIds = $instruments->pluck('id')->all();

        // Get last 180 days of close prices for mini charts
        $priceSeries = DB::table('prices_daily')
            ->select(['instrument_id', 'time', 'close'])
            ->whereIn('instrument_id', $instrumentIds)
            ->whereNotNull('close')
            ->where('time', '>=', DB::raw("(SELECT MAX(time) FROM prices_daily) - INTERVAL '180 days'"))
            ->orderBy('instrument_id')
            ->orderBy('time')
            ->get()
            ->groupBy('instrument_id');

        // Get latest 2 trading days + 1 week ago price per instrument for change calculations
        $latestPrices = DB::table('prices_daily as p1')
            ->select([
                'p1.instrument_id',
                DB::raw('(array_agg(p1.close ORDER BY p1.time DESC))[1] as current_close'),
                DB::raw('(array_agg(p1.close ORDER BY p1.time DESC))[2] as prev_close'),
                DB::raw('(array_agg(p1.volume ORDER BY p1.time DESC))[1] as current_volume'),
            ])
            ->whereIn('p1.instrument_id', $instrumentIds)
            ->whereNotNull('p1.close')
            ->groupBy('p1.instrument_id')
            ->get()
            ->keyBy('instrument_id');

        // Week-ago close: find close from ~7 calendar days ago
        $weekAgoPrices = DB::table('prices_daily')
            ->select([
                'instrument_id',
                DB::raw('(array_agg(close ORDER BY time DESC))[1] as week_ago_close'),
            ])
            ->whereIn('instrument_id', $instrumentIds)
            ->whereNotNull('close')
            ->where('time', '<=', DB::raw("(SELECT MAX(time) FROM prices_daily) - INTERVAL '7 days'"))
            ->groupBy('instrument_id')
            ->get()
            ->keyBy('instrument_id');

        // Build card data
        $cards = $instruments->map(function ($inst) use ($priceSeries, $latestPrices, $weekAgoPrices) {
            $series = $priceSeries->get($inst->id, collect())->map(fn ($r) => [
                'time' => $r->time,
                'close' => (float) $r->close,
            ])->values();

            $latest = $latestPrices->get($inst->id);
            $weekAgo = $weekAgoPrices->get($inst->id);

            $currentClose = $latest ? (float) $latest->current_close : null;
            $prevClose = $latest ? (float) $latest->prev_close : null;
            $volume = $latest ? (int) $latest->current_volume : null;
            $weekAgoClose = $weekAgo ? (float) $weekAgo->week_ago_close : null;

            $dayChangeAbs = ($currentClose !== null && $prevClose !== null) ? $currentClose - $prevClose : null;
            $dayChangePct = ($dayChangeAbs !== null && $prevClose != 0) ? ($dayChangeAbs / $prevClose) * 100 : null;

            $weekChangeAbs = ($currentClose !== null && $weekAgoClose !== null) ? $currentClose - $weekAgoClose : null;
            $weekChangePct = ($weekChangeAbs !== null && $weekAgoClose != 0) ? ($weekChangeAbs / $weekAgoClose) * 100 : null;

            $amountInvested = (float) $inst->pivot->amount_invested;
            $shares = (float) $inst->pivot->shares;
            $currentValue = ($currentClose !== null && $shares > 0) ? $currentClose * $shares : $amountInvested;

            $totalChangeAbs = ($currentClose !== null && $shares > 0) ? $currentValue - $amountInvested : null;
            $totalChangePct = ($totalChangeAbs !== null && $amountInvested > 0) ? ($totalChangeAbs / $amountInvested) * 100 : null;

            return (object) [
                'instrument' => $inst,
                'series' => $series,
                'current_close' => $currentClose,
                'volume' => $volume,
                'day_change_abs' => $dayChangeAbs,
                'day_change_pct' => $dayChangePct,
                'week_change_abs' => $weekChangeAbs,
                'week_change_pct' => $weekChangePct,
                'total_change_abs' => $totalChangeAbs,
                'total_change_pct' => $totalChangePct,
                'amount_invested' => $amountInvested,
                'shares' => $shares,
                'current_value' => $currentValue,
            ];
        });

        $summary = $this->buildSummary($portfolio, $cards);

        return view('portfolios.show', [
            'portfolio' => $portfolio,
            'cards' => $cards,
            'summary' => $summary,
            'chart' => $chart,
            'transactions' => $transactions,
        ]);
    }

    private function loadTransactions(Portfolio $portfolio)
    {
        return DB::table('portfolio_transactions as pt')
            ->leftJoin('instruments as i', 'i.id', '=', 'pt.instrument_id')
            ->where('pt.portfolio_id', $portfolio->id)
            ->orderByDesc('pt.transaction_date')
            ->orderByDesc('pt.id')
            ->select([
                'pt.id',
                'pt.type',
                'pt.transaction_date',
                'pt.shares',
                'pt.price_per_share',
                'pt.amount',
                'pt.currency',
                'pt.note',
                'pt.instrument_id',
                'i.ticker',
            ])
            ->get();
    }

    public function addInstrument(Request $request, Portfolio $portfolio): JsonResponse
    {
        Gate::authorize('update', $portfolio);

        $request->validate([
            'instrument_id' => 'required|integer|exists:instruments,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $requestedAmount = (string) $request->input('amount');
        $instrumentId = (int) $request->input('instrument_id');

        $price = DB::table('prices_daily')
            ->where('instrument_id', $instrumentId)
            ->whereNotNull('close')
            ->orderByDesc('time')
            ->value('close');

        if (!$price) {
            return response()->json(['error' => 'Nav pieejama cena šim instrumentam.'], 422);
        }

        $priceStr = (string) $price;
        $shares = bcdiv($requestedAmount, $priceStr, 3);
        if (Money::lte($shares, '0', 3)) {
            return response()->json(['error' => 'Summa par mazu — vismaz 0.001 akcijai jābūt.'], 422);
        }

        $actualCost = Money::mul($shares, $priceStr, Money::SCALE_CASH);

        try {
            DB::transaction(function () use ($portfolio, $instrumentId, $shares, $priceStr, $actualCost) {
                $locked = Portfolio::where('id', $portfolio->id)->lockForUpdate()->first();

                if (Money::gt($actualCost, $locked->free_capital)) {
                    abort(response()->json(['error' => 'Nepietiek brīvā kapitāla.'], 422));
                }

                $existing = $locked->instruments()->where('instrument_id', $instrumentId)->first();

                if ($existing) {
                    $locked->instruments()->updateExistingPivot($instrumentId, [
                        'amount_invested' => Money::add($existing->pivot->amount_invested, $actualCost),
                        'shares' => Money::add($existing->pivot->shares, $shares, 3),
                    ]);
                } else {
                    $locked->instruments()->attach($instrumentId, [
                        'amount_invested' => $actualCost,
                        'shares' => $shares,
                    ]);
                }

                PortfolioTransaction::create([
                    'portfolio_id' => $locked->id,
                    'instrument_id' => $instrumentId,
                    'type' => 'buy',
                    'transaction_date' => now()->toDateString(),
                    'shares' => $shares,
                    'price_per_share' => $priceStr,
                    'amount' => Money::neg($actualCost),
                    'currency' => $locked->currency,
                ]);

                $locked->update(['free_capital' => Money::sub($locked->free_capital, $actualCost)]);
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $e->getResponse();
        }

        return response()->json(['success' => true]);
    }

    public function sellInstrument(Request $request, Portfolio $portfolio, int $instrumentId): JsonResponse
    {
        Gate::authorize('update', $portfolio);

        $request->validate([
            'shares' => 'required|numeric|min:0.001',
        ]);

        $sharesRequested = bcadd((string) $request->input('shares'), '0', 3);
        if (Money::lte($sharesRequested, '0', 3)) {
            return response()->json(['error' => 'Pārdošanas apjoms ir 0.'], 422);
        }

        $currentPrice = DB::table('prices_daily')
            ->where('instrument_id', $instrumentId)
            ->whereNotNull('close')
            ->orderByDesc('time')
            ->value('close');

        $currentPriceStr = $currentPrice ? (string) $currentPrice : null;

        try {
            DB::transaction(function () use ($portfolio, $instrumentId, $sharesRequested, $currentPriceStr) {
                $locked = Portfolio::where('id', $portfolio->id)->lockForUpdate()->first();

                $existing = $locked->instruments()->where('instrument_id', $instrumentId)->first();
                if (!$existing) {
                    abort(response()->json(['error' => 'Instruments nav portfelī.'], 404));
                }

                $currentShares = (string) $existing->pivot->shares;
                $currentInvested = (string) $existing->pivot->amount_invested;

                if (Money::gt($sharesRequested, $currentShares, 3)) {
                    abort(response()->json(['error' => 'Nevar pārdot vairāk akciju nekā ir.'], 422));
                }

                $sharesToSell = Money::lte($sharesRequested, $currentShares, 3) ? $sharesRequested : $currentShares;

                $proceeds = $currentPriceStr
                    ? Money::mul($sharesToSell, $currentPriceStr)
                    : Money::mul(Money::div($sharesToSell, $currentShares), $currentInvested);

                PortfolioTransaction::create([
                    'portfolio_id' => $locked->id,
                    'instrument_id' => $instrumentId,
                    'type' => 'sell',
                    'transaction_date' => now()->toDateString(),
                    'shares' => Money::neg($sharesToSell, 3),
                    'price_per_share' => $currentPriceStr,
                    'amount' => $proceeds,
                    'currency' => $locked->currency,
                ]);

                $remainingShares = Money::sub($currentShares, $sharesToSell, 3);

                if (Money::lte($remainingShares, '0.000001', 6)) {
                    $locked->instruments()->detach($instrumentId);
                } else {
                    $costBasisSold = Money::mul(Money::div($sharesToSell, $currentShares), $currentInvested);
                    $remainingInvested = Money::sub($currentInvested, $costBasisSold);
                    if (Money::lte($remainingInvested, '0')) {
                        $remainingInvested = '0.00';
                    }
                    $locked->instruments()->updateExistingPivot($instrumentId, [
                        'amount_invested' => $remainingInvested,
                        'shares' => $remainingShares,
                    ]);
                }

                $locked->update([
                    'free_capital' => Money::add($locked->free_capital, $proceeds),
                ]);
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $e->getResponse();
        }

        return response()->json(['success' => true]);
    }

    public function removeInstrument(Portfolio $portfolio, int $instrumentId): JsonResponse
    {
        Gate::authorize('update', $portfolio);

        $currentPrice = DB::table('prices_daily')
            ->where('instrument_id', $instrumentId)
            ->whereNotNull('close')
            ->orderByDesc('time')
            ->value('close');

        $currentPriceStr = $currentPrice ? (string) $currentPrice : null;

        try {
            DB::transaction(function () use ($portfolio, $instrumentId, $currentPriceStr) {
                $locked = Portfolio::where('id', $portfolio->id)->lockForUpdate()->first();

                $existing = $locked->instruments()->where('instrument_id', $instrumentId)->first();
                if (!$existing) {
                    abort(response()->json(['error' => 'Instruments nav portfelī.'], 404));
                }

                $shares = (string) $existing->pivot->shares;
                $invested = (string) $existing->pivot->amount_invested;

                $proceeds = ($currentPriceStr && Money::gt($shares, '0', 3))
                    ? Money::mul($shares, $currentPriceStr)
                    : $invested;

                PortfolioTransaction::create([
                    'portfolio_id' => $locked->id,
                    'instrument_id' => $instrumentId,
                    'type' => 'sell',
                    'transaction_date' => now()->toDateString(),
                    'shares' => Money::neg($shares, 3),
                    'price_per_share' => $currentPriceStr,
                    'amount' => $proceeds,
                    'currency' => $locked->currency,
                ]);

                $locked->update([
                    'free_capital' => Money::add($locked->free_capital, $proceeds),
                ]);

                $locked->instruments()->detach($instrumentId);
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $e->getResponse();
        }

        return response()->json(['success' => true]);
    }

    private function buildSummary(Portfolio $portfolio, $cards): object
    {
        $cash = (float) DB::table('portfolio_transactions')
            ->where('portfolio_id', $portfolio->id)
            ->sum('amount');

        $costBasis = (float) $cards->sum('amount_invested');

        $marketValue = 0.0;
        foreach ($cards as $card) {
            if ($card->current_close !== null && $card->shares > 0) {
                $marketValue += $card->shares * $card->current_close;
            }
        }

        $portfolioValue = $cash + $marketValue;
        $totalChange = $marketValue - $costBasis;
        $totalChangePct = $costBasis > 0 ? ($totalChange / $costBasis) * 100 : 0;

        foreach ($cards as $card) {
            $marketWeight = ($card->current_close !== null && $card->shares > 0)
                ? $card->shares * $card->current_close
                : 0;
            $card->weight = $portfolioValue > 0
                ? ($marketWeight / $portfolioValue) * 100
                : 0;
        }

        return (object) [
            'portfolio_value' => $portfolioValue,
            'total_invested' => $costBasis,
            'total_current_value' => $marketValue,
            'total_change' => $totalChange,
            'total_change_pct' => $totalChangePct,
            'free_capital' => $cash,
            'currency' => $portfolio->currency,
        ];
    }
}
