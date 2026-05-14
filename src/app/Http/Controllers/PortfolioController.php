<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Services\ChartService;
use App\Services\Risk\PortfolioReturnCalculator;
use App\Services\Risk\RiskCalculator;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __construct(
        private ChartService $chartService,
        private RiskCalculator $riskCalculator,
        private PortfolioReturnCalculator $returnCalculator,
    ) {}


    public function index(Request $request): View
    {
        $riskYears = (int) $request->query('risk_years', RiskCalculator::DEFAULT_YEARS);
        if (! in_array($riskYears, [1, 2, 3, 5, 10], true)) {
            $riskYears = RiskCalculator::DEFAULT_YEARS;
        }

        // Filter (personal/system/index/all) — persistents pār year-button kliķiem
        $scatterFilter = (string) $request->query('filter', 'personal');
        if (! in_array($scatterFilter, ['all', 'personal', 'system', 'index'], true)) {
            $scatterFilter = 'personal';
        }

        // Personīgais saraksts — tikai lietotāja portfeļi (bez sistēmas)
        $personalPortfolios = Portfolio::forUser(Auth::id())
            ->withCount('instruments')
            ->orderByDesc('updated_at')
            ->get();

        // Sistēmas portfeļi (modeļu backtest rezultāti) — redzami visiem uz scatter plot
        $systemPortfolios = Portfolio::system()
            ->withCount('instruments')
            ->orderBy('name')
            ->get();

        $buildPoint = function (Portfolio $p, string $category) use ($riskYears) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'category' => $category,    // 'personal' | 'system' | 'index'
                'risk' => $this->riskCalculator->portfolioRisk($p, $riskYears),
                // Peļņa par to pašu logu kā risks (windowed return matching risk_years)
                'return' => $this->returnCalculator->periodReturn($p, $riskYears),
            ];
        };

        // Indeksu portfeļi — sistēmas portfeļi, kuru apraksts sākas ar "INDEX:" prefiksu
        $indexPortfolios = $systemPortfolios->filter(fn ($p) => str_starts_with((string) $p->description, 'INDEX:'));
        $modelPortfolios = $systemPortfolios->reject(fn ($p) => str_starts_with((string) $p->description, 'INDEX:'));

        $scatterPoints = $personalPortfolios->map(fn ($p) => $buildPoint($p, 'personal'))
            ->concat($modelPortfolios->map(fn ($p) => $buildPoint($p, 'system')))
            ->concat($indexPortfolios->map(fn ($p) => $buildPoint($p, 'index')))
            ->filter(fn ($pt) => $pt['risk'] !== null && $pt['return'] !== null)
            ->values();

        $latestDataDate = DB::table('prices_daily')->max('time');

        return view('portfolios.index', [
            'portfolios' => $personalPortfolios,
            'systemPortfolios' => $systemPortfolios,
            'scatterPoints' => $scatterPoints,
            'riskYears' => $riskYears,
            'scatterFilter' => $scatterFilter,
            'latestDataDate' => $latestDataDate ? \Carbon\Carbon::parse($latestDataDate)->toDateString() : null,
        ]);
    }

    /**
     * Manuālā portfeļa veidošanas forma — per-instrument svari, summas, datumi.
     */
    public function createForm(): View
    {
        $dataRange = DB::table('prices_daily')
            ->selectRaw('MIN(time) as earliest, MAX(time) as latest')->first();
        $earliestDataDate = $dataRange?->earliest ? \Carbon\Carbon::parse($dataRange->earliest)->toDateString() : null;
        $latestDataDate = $dataRange?->latest ? \Carbon\Carbon::parse($dataRange->latest)->toDateString() : null;

        return view('portfolios.create', [
            'earliestDataDate' => $earliestDataDate,
            'latestDataDate' => $latestDataDate,
        ]);
    }

    /**
     * Manuālā portfeļa saglabāšana — saņem picks masīvu un izveido portfeli + transakcijas.
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'capital' => 'required|numeric|min:1',
            'picks' => 'required|array|min:1',
            'picks.*.ticker' => 'required|string|max:32',
            'picks.*.amount' => 'required|numeric|min:0.01',
            'picks.*.transaction_date' => 'required|date|before_or_equal:today',
        ]);

        // Resolve tickers + prices for each pick
        $resolved = [];
        foreach ($data['picks'] as $idx => $pick) {
            $ticker = strtoupper(trim($pick['ticker']));
            $instrument = \App\Models\Instrument::where('ticker', $ticker)->first();
            if (! $instrument) {
                return back()->withErrors(['picks' => "Nezināms ticker: {$ticker}"])->withInput();
            }

            $priceRow = DB::table('prices_daily')
                ->where('instrument_id', $instrument->id)
                ->whereNotNull('close')
                ->where('time', '<=', $pick['transaction_date'])
                ->orderByDesc('time')
                ->first(['time', 'close']);

            if (! $priceRow) {
                return back()->withErrors(['picks' => "Nav cenas datu {$ticker} pirms {$pick['transaction_date']}"])->withInput();
            }

            $priceDate = \Carbon\Carbon::parse($priceRow->time)->toDateString();
            $gapDays = \Carbon\Carbon::parse($pick['transaction_date'])->diffInDays($priceDate, true);
            if ($gapDays > 7) {
                return back()->withErrors(['picks' =>
                    "Pārāk veca cena {$ticker}: jaunākā {$priceDate} ({$gapDays}d pirms {$pick['transaction_date']})"
                ])->withInput();
            }

            $price = (float) $priceRow->close;
            $shares = floor(((float) $pick['amount'] / $price) * 1000) / 1000;
            if ($shares <= 0) {
                return back()->withErrors(['picks' => "Summa {$ticker} par mazu, lai nopirktu vienu akciju"])->withInput();
            }
            $cost = round($shares * $price, 2);

            $resolved[] = [
                'instrument_id' => $instrument->id,
                'ticker' => $ticker,
                'price' => $price,
                'shares' => $shares,
                'cost' => $cost,
                'date' => $pick['transaction_date'],
            ];
        }

        $totalCost = array_sum(array_column($resolved, 'cost'));
        $capital = (float) $data['capital'];
        if ($totalCost > $capital + 0.01) {
            return back()->withErrors(['picks' =>
                'Kopējās izmaksas ($' . number_format($totalCost, 2) . ') pārsniedz kapitālu ($' . number_format($capital, 2) . ')'
            ])->withInput();
        }

        $earliestDate = min(array_column($resolved, 'date'));

        $portfolio = DB::transaction(function () use ($data, $resolved, $totalCost, $capital, $earliestDate) {
            $portfolio = Portfolio::create([
                'user_id' => Auth::id(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'currency' => 'USD',
                'free_capital' => round($capital - $totalCost, 2),
            ]);

            // Initial deposit on earliest transaction date
            PortfolioTransaction::create([
                'portfolio_id' => $portfolio->id,
                'instrument_id' => null,
                'type' => 'deposit',
                'transaction_date' => $earliestDate,
                'amount' => $capital,
                'currency' => 'USD',
                'note' => 'Initial deposit',
            ]);

            foreach ($resolved as $p) {
                $portfolio->instruments()->attach($p['instrument_id'], [
                    'amount_invested' => $p['cost'],
                    'shares' => $p['shares'],
                ]);

                PortfolioTransaction::create([
                    'portfolio_id' => $portfolio->id,
                    'instrument_id' => $p['instrument_id'],
                    'type' => 'buy',
                    'transaction_date' => $p['date'],
                    'shares' => $p['shares'],
                    'price_per_share' => $p['price'],
                    'amount' => -$p['cost'],
                    'currency' => 'USD',
                ]);
            }

            return $portfolio;
        });

        return redirect()->route('portfolios.show', $portfolio)
            ->with('success', "Portfelis '{$portfolio->name}' izveidots ar " . count($resolved) . " instrumentiem.");
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'free_capital' => 'required|numeric|min:0',
        ]);

        $currency = 'USD';
        $freeCapital = (float) $request->input('free_capital');

        // Atomic: ja deposit transakcijas izveide neizdodas, atritina arī portfeļa izveidi.
        // Šis novērš orphan portfeļus (free_capital DB, bet bez deposit transakcijas ledger).
        $portfolio = DB::transaction(function () use ($request, $currency, $freeCapital) {
            $portfolio = Portfolio::create([
                'user_id' => Auth::id(),
                'name' => $request->input('name'),
                'currency' => $currency,
                'free_capital' => $freeCapital,
            ]);

            if ($freeCapital > 0) {
                PortfolioTransaction::create([
                    'portfolio_id' => $portfolio->id,
                    'instrument_id' => null,
                    'type' => 'deposit',
                    'transaction_date' => now()->toDateString(),
                    'amount' => $freeCapital,
                    'currency' => $currency,
                    'note' => 'Initial deposit',
                ]);
            }

            return $portfolio;
        });

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
        $dataRange = DB::table('prices_daily')
            ->selectRaw('MIN(time) as earliest, MAX(time) as latest')->first();
        $earliestDataDate = $dataRange?->earliest ? \Carbon\Carbon::parse($dataRange->earliest)->toDateString() : null;
        $latestDataDate = $dataRange?->latest ? \Carbon\Carbon::parse($dataRange->latest)->toDateString() : null;

        if ($instruments->isEmpty()) {
            return view('portfolios.show', [
                'portfolio' => $portfolio,
                'cards' => collect(),
                'summary' => $this->buildSummary($portfolio, collect()),
                'chart' => $chart,
                'transactions' => $transactions,
                'earliestDataDate' => $earliestDataDate,
                'latestDataDate' => $latestDataDate,
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
            'earliestDataDate' => $earliestDataDate,
            'latestDataDate' => $latestDataDate,
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

    public function exportTransactions(Portfolio $portfolio): StreamedResponse
    {
        Gate::authorize('view', $portfolio);

        $rows = DB::table('portfolio_transactions as pt')
            ->leftJoin('instruments as i', 'i.id', '=', 'pt.instrument_id')
            ->where('pt.portfolio_id', $portfolio->id)
            ->orderBy('pt.transaction_date')
            ->orderBy('pt.id')
            ->select([
                'pt.transaction_date',
                'pt.type',
                'i.ticker',
                'i.company_name',
                'pt.shares',
                'pt.price_per_share',
                'pt.amount',
                'pt.currency',
                'pt.note',
                'pt.created_at',
            ])
            ->get();

        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', $portfolio->name);
        $filename = "{$slug}_transactions_" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens Latvian characters correctly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'transaction_date', 'type', 'ticker', 'company_name',
                'shares', 'price_per_share', 'amount', 'currency',
                'note', 'created_at',
            ]);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->transaction_date,
                    $r->type,
                    $r->ticker ?? '',
                    $r->company_name ?? '',
                    $r->shares,
                    $r->price_per_share,
                    $r->amount,
                    $r->currency,
                    $r->note ?? '',
                    $r->created_at,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function addInstrument(Request $request, Portfolio $portfolio): JsonResponse
    {
        Gate::authorize('update', $portfolio);

        $request->validate([
            'instrument_id' => 'required|integer|exists:instruments,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'nullable|date|before_or_equal:today',
        ]);

        $requestedAmount = (string) $request->input('amount');
        $instrumentId = (int) $request->input('instrument_id');
        $transactionDate = $request->input('transaction_date') ?: now()->toDateString();

        // Find closest available trading-day price at or before the requested date
        $priceRow = DB::table('prices_daily')
            ->where('instrument_id', $instrumentId)
            ->whereNotNull('close')
            ->where('time', '<=', $transactionDate)
            ->orderByDesc('time')
            ->first(['time', 'close']);

        if (!$priceRow) {
            return response()->json([
                'error' => "Nav pieejama cena šim instrumentam datumā {$transactionDate} vai pirms tā.",
            ], 422);
        }

        // Atteicies, ja jaunākā pieejamā cena ir vairāk nekā 7 dienas pirms pieprasītā datuma.
        // Tas novērš situāciju, kad lietotājs "nopērk" akciju 2025.g., bet cena ir no 2024.g.
        $priceDate = \Carbon\Carbon::parse($priceRow->time)->toDateString();
        $gapDays = \Carbon\Carbon::parse($transactionDate)->diffInDays($priceDate, true);
        if ($gapDays > 7) {
            return response()->json([
                'error' => "Pārāk veca cena: jaunākā cena šim instrumentam ir {$priceDate} ({$gapDays} dienas pirms {$transactionDate}). "
                         . "Izvēlies datumu līdz " . \Carbon\Carbon::parse($priceRow->time)->addDays(7)->toDateString() . " vai citu instrumentu.",
            ], 422);
        }

        $price = $priceRow->close;

        $priceStr = (string) $price;
        $shares = bcdiv($requestedAmount, $priceStr, 3);
        if (Money::lte($shares, '0', 3)) {
            return response()->json(['error' => 'Summa par mazu — vismaz 0.001 akcijai jābūt.'], 422);
        }

        $actualCost = Money::mul($shares, $priceStr, Money::SCALE_CASH);

        try {
            DB::transaction(function () use ($portfolio, $instrumentId, $shares, $priceStr, $actualCost, $transactionDate) {
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
                    'transaction_date' => $transactionDate,
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
            'transaction_date' => 'nullable|date|before_or_equal:today',
        ]);

        $sharesRequested = bcadd((string) $request->input('shares'), '0', 3);
        if (Money::lte($sharesRequested, '0', 3)) {
            return response()->json(['error' => 'Pārdošanas apjoms ir 0.'], 422);
        }

        $transactionDate = $request->input('transaction_date') ?: now()->toDateString();

        $priceRow = DB::table('prices_daily')
            ->where('instrument_id', $instrumentId)
            ->whereNotNull('close')
            ->where('time', '<=', $transactionDate)
            ->orderByDesc('time')
            ->first(['time', 'close']);

        if ($priceRow) {
            $gapDays = \Carbon\Carbon::parse($transactionDate)->diffInDays($priceRow->time, true);
            if ($gapDays > 7) {
                return response()->json([
                    'error' => "Pārāk veca cena: jaunākā cena šim instrumentam ir " . \Carbon\Carbon::parse($priceRow->time)->toDateString()
                             . " ({$gapDays} dienas pirms {$transactionDate}).",
                ], 422);
            }
        }

        $currentPrice = $priceRow ? $priceRow->close : null;
        $currentPriceStr = $currentPrice ? (string) $currentPrice : null;

        try {
            DB::transaction(function () use ($portfolio, $instrumentId, $sharesRequested, $currentPriceStr, $transactionDate) {
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
                    'transaction_date' => $transactionDate,
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

    /**
     * Pilnībā izdzēš portfeli ar visām transakcijām un holdingiem.
     */
    public function destroy(Portfolio $portfolio): RedirectResponse
    {
        Gate::authorize('delete', $portfolio);

        DB::transaction(function () use ($portfolio) {
            $portfolio->instruments()->detach();
            DB::table('portfolio_transactions')->where('portfolio_id', $portfolio->id)->delete();
            $portfolio->delete();
        });

        return redirect()->route('portfolios.index')
            ->with('success', "Portfelis '{$portfolio->name}' izdzēsts.");
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

    /**
     * Ģenerē QuantStats HTML atskaiti portfeļa veiktspējai.
     * Izsauc Python skriptu, kas raksta HTML uz storage/app/quantstats/.
     */
    public function quantstats(Request $request, Portfolio $portfolio)
    {
        Gate::authorize('view', $portfolio);

        $download = $request->boolean('download');

        $outDir = storage_path('app/quantstats');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }
        $outPath = "{$outDir}/portfolio_{$portfolio->id}.html";

        $script = base_path('../scripts/generate_quantstats.py');
        if (! file_exists($script)) {
            // Container path mapping: project mounted at /var/www/html, scripts pie /var/www/scripts
            $script = '/var/www/scripts/generate_quantstats.py';
        }

        $mplCacheDir = storage_path('app/quantstats/.matplotlib');
        if (! is_dir($mplCacheDir)) {
            mkdir($mplCacheDir, 0755, true);
        }

        $env = [
            'DB_HOST' => config('database.connections.pgsql.host'),
            'DB_PORT' => (string) config('database.connections.pgsql.port'),
            'DB_DATABASE' => config('database.connections.pgsql.database'),
            'DB_USERNAME' => config('database.connections.pgsql.username'),
            'DB_PASSWORD' => config('database.connections.pgsql.password'),
            'MPLCONFIGDIR' => $mplCacheDir,
            'HOME' => storage_path('app/quantstats'),
        ];

        $envPrefix = '';
        foreach ($env as $k => $v) {
            $envPrefix .= $k . '=' . escapeshellarg((string) $v) . ' ';
        }

        $cmd = $envPrefix . 'python3 ' . escapeshellarg($script)
            . ' ' . escapeshellarg((string) $portfolio->id)
            . ' ' . escapeshellarg($outPath)
            . ' 2>&1';

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($outPath)) {
            return response()->json([
                'error' => 'QuantStats ģenerēšana neizdevās',
                'detail' => implode("\n", $output),
            ], 500);
        }

        $html = file_get_contents($outPath);

        if ($download) {
            $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', $portfolio->name);
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $slug . '_quantstats.html"',
            ]);
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function buildSummary(Portfolio $portfolio, $cards): object
    {
        $cash = (float) DB::table('portfolio_transactions')
            ->where('portfolio_id', $portfolio->id)
            ->sum('amount');

        $netDeposits = (float) DB::table('portfolio_transactions')
            ->where('portfolio_id', $portfolio->id)
            ->whereIn('type', ['deposit', 'withdrawal'])
            ->sum('amount');

        $costBasis = (float) $cards->sum('amount_invested');

        $marketValue = 0.0;
        foreach ($cards as $card) {
            if ($card->current_close !== null && $card->shares > 0) {
                $marketValue += $card->shares * $card->current_close;
            }
        }

        $portfolioValue = $cash + $marketValue;

        // Unrealized P&L on currently-open positions only
        $unrealizedPnl = $marketValue - $costBasis;
        $unrealizedPnlPct = $costBasis > 0 ? ($unrealizedPnl / $costBasis) * 100 : 0;

        // Total return = portfolio value vs net deposits (realized + unrealized + cash gains)
        $totalReturn = $portfolioValue - $netDeposits;
        $totalReturnPct = $netDeposits > 0 ? ($totalReturn / $netDeposits) * 100 : 0;

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
            'net_deposits' => $netDeposits,
            'unrealized_pnl' => $unrealizedPnl,
            'unrealized_pnl_pct' => $unrealizedPnlPct,
            'total_return' => $totalReturn,
            'total_return_pct' => $totalReturnPct,
            'free_capital' => $cash,
            'currency' => $portfolio->currency,
        ];
    }
}
