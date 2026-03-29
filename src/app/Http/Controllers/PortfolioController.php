<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortfolioController extends Controller
{
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

        return redirect()->route('portfolios.show', $portfolio);
    }

    public function show(Portfolio $portfolio): View
    {
        if ($portfolio->user_id !== Auth::id()) {
            abort(403);
        }

        $instruments = $portfolio->instruments()
            ->select(['instruments.id', 'ticker', 'company_name', 'exchange'])
            ->orderBy('ticker')
            ->get();

        if ($instruments->isEmpty()) {
            return view('portfolios.show', [
                'portfolio' => $portfolio,
                'cards' => collect(),
                'summary' => $this->buildSummary($portfolio, collect()),
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

            return (object) [
                'instrument' => $inst,
                'series' => $series,
                'current_close' => $currentClose,
                'volume' => $volume,
                'day_change_abs' => $dayChangeAbs,
                'day_change_pct' => $dayChangePct,
                'week_change_abs' => $weekChangeAbs,
                'week_change_pct' => $weekChangePct,
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
        ]);
    }

    public function addInstrument(Request $request, Portfolio $portfolio): JsonResponse
    {
        if ($portfolio->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'instrument_id' => 'required|integer|exists:instruments,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = (float) $request->input('amount');

        if ($amount > (float) $portfolio->free_capital) {
            return response()->json(['error' => 'Nepietiek brīvā kapitāla.'], 422);
        }

        // Get current price to calculate shares
        $price = DB::table('prices_daily')
            ->where('instrument_id', $request->input('instrument_id'))
            ->whereNotNull('close')
            ->orderByDesc('time')
            ->value('close');

        $shares = $price ? round($amount / (float) $price, 6) : 0;

        // Check if instrument already in portfolio
        $existing = $portfolio->instruments()->where('instrument_id', $request->input('instrument_id'))->first();

        if ($existing) {
            $portfolio->instruments()->updateExistingPivot($request->input('instrument_id'), [
                'amount_invested' => (float) $existing->pivot->amount_invested + $amount,
                'shares' => (float) $existing->pivot->shares + $shares,
            ]);
        } else {
            $portfolio->instruments()->attach($request->input('instrument_id'), [
                'amount_invested' => $amount,
                'shares' => $shares,
            ]);
        }

        $portfolio->update(['free_capital' => (float) $portfolio->free_capital - $amount]);

        return response()->json(['success' => true]);
    }

    public function removeInstrument(Portfolio $portfolio, int $instrumentId): JsonResponse
    {
        if ($portfolio->user_id !== Auth::id()) {
            abort(403);
        }

        $existing = $portfolio->instruments()->where('instrument_id', $instrumentId)->first();

        if (!$existing) {
            return response()->json(['error' => 'Instruments nav portfelī.'], 404);
        }

        // Return invested amount to free capital
        $portfolio->update([
            'free_capital' => (float) $portfolio->free_capital + (float) $existing->pivot->amount_invested,
        ]);

        $portfolio->instruments()->detach($instrumentId);

        return response()->json(['success' => true]);
    }

    private function buildSummary(Portfolio $portfolio, $cards): object
    {
        $totalInvested = $cards->sum('amount_invested');
        $totalCurrentValue = $cards->sum('current_value');
        $freeCapital = (float) $portfolio->free_capital;
        $portfolioValue = $totalCurrentValue + $freeCapital;
        $totalChange = $totalCurrentValue - $totalInvested;
        $totalChangePct = $totalInvested > 0 ? ($totalChange / $totalInvested) * 100 : 0;

        // Add weight to each card
        foreach ($cards as $card) {
            $card->weight = $portfolioValue > 0
                ? ($card->current_value / $portfolioValue) * 100
                : 0;
        }

        return (object) [
            'portfolio_value' => $portfolioValue,
            'total_invested' => $totalInvested,
            'total_current_value' => $totalCurrentValue,
            'total_change' => $totalChange,
            'total_change_pct' => $totalChangePct,
            'free_capital' => $freeCapital,
            'currency' => $portfolio->currency,
        ];
    }
}
