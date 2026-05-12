<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Services\Backtest\BacktestRunner;
use App\Services\Backtest\StrategyRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BacktestController extends Controller
{
    public function __construct(
        private StrategyRegistry $registry,
        private BacktestRunner $runner,
    ) {}

    /**
     * Wizard form: izvēlies stratēģiju, datumu, kapitālu, parametrus.
     */
    public function create(): View
    {
        return view('backtests.create', [
            'strategies' => $this->registry->all(),
        ]);
    }

    /**
     * AJAX preview: aprēķina, kuras akcijas stratēģija izvēlētos ar dotajiem parametriem.
     * Atgriež JSON ar tickerā un nosaukumiem (un score, ja stratēģija to dod).
     */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'strategy' => 'required|string',
            'base_date' => 'required|date|after_or_equal:2018-04-01|before_or_equal:today',
            'top_n' => 'nullable|integer|min:1|max:100',
            'instrument_tickers' => 'nullable|string',
        ]);

        $strategy = $this->registry->get($request->input('strategy'));
        if (! $strategy) {
            return response()->json(['error' => 'Nezināma stratēģija.'], 422);
        }

        $params = [];
        if ($request->filled('top_n')) {
            $params['top_n'] = (int) $request->input('top_n');
        }
        if ($request->filled('instrument_tickers')) {
            $tickers = collect(explode(',', $request->input('instrument_tickers')))
                ->map(fn ($t) => trim(strtoupper($t)))
                ->filter()->unique()->values()->all();

            $ids = Instrument::whereIn('ticker', $tickers)->pluck('id', 'ticker');
            $missingTickers = array_diff($tickers, $ids->keys()->all());
            if (! empty($missingTickers)) {
                return response()->json(['error' => 'Nezināmi tickeri: ' . implode(', ', $missingTickers)], 422);
            }
            $params['instrument_ids'] = $ids->values()->all();
        }

        try {
            $selections = $strategy->selectInstruments($request->input('base_date'), $params);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Kļūda atlasē: ' . $e->getMessage()], 500);
        }

        if ($selections->isEmpty()) {
            return response()->json(['error' => 'Nav neviena instrumenta, kas atbilstu kritērijiem.'], 422);
        }

        // Pielīmē tickerus un nosaukumus
        $ids = $selections->pluck('instrument_id')->all();
        $instruments = Instrument::whereIn('id', $ids)->get(['id', 'ticker', 'company_name'])->keyBy('id');

        $result = $selections->map(function ($s) use ($instruments) {
            $inst = $instruments->get($s['instrument_id']);
            return [
                'ticker' => $inst?->ticker ?? '?',
                'company_name' => $inst?->company_name ?? '',
                'weight' => $s['weight'],
                'score' => $s['score'] ?? null,
            ];
        })->values();

        return response()->json([
            'strategy' => $strategy->name(),
            'base_date' => $request->input('base_date'),
            'count' => $result->count(),
            'picks' => $result,
        ]);
    }

    /**
     * Palaiž backtestu, izveido jaunu portfeli.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'strategy' => 'required|string',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'base_date' => 'required|date|after_or_equal:2018-04-01|before_or_equal:today',
            'capital' => 'required|numeric|min:100',
            'top_n' => 'nullable|integer|min:1|max:100',
            'instrument_tickers' => 'nullable|string',  // comma-separated for equal_weight
        ], [
            'base_date.after_or_equal' => 'Bāzes datumam jābūt 2018-04-01 vai vēlāk (SimFin fundamentālie dati sākas no 2018).',
        ]);

        $strategy = $this->registry->get($request->input('strategy'));
        if (! $strategy) {
            return back()->withErrors(['strategy' => 'Nezināma stratēģija.'])->withInput();
        }

        // Build params based on strategy type
        $params = [];
        if ($request->filled('top_n')) {
            $params['top_n'] = (int) $request->input('top_n');
        }
        if ($request->filled('instrument_tickers')) {
            $tickers = collect(explode(',', $request->input('instrument_tickers')))
                ->map(fn ($t) => trim(strtoupper($t)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $ids = Instrument::whereIn('ticker', $tickers)->pluck('id', 'ticker');
            $missingTickers = array_diff($tickers, $ids->keys()->all());
            if (! empty($missingTickers)) {
                return back()
                    ->withErrors(['instrument_tickers' => 'Nezināmi tickeri: ' . implode(', ', $missingTickers)])
                    ->withInput();
            }
            $params['instrument_ids'] = $ids->values()->all();
        }

        // Lietotāju izveidotie backtesti vienmēr ir personīgi.
        // Sistēmas portfeļus (modeļu paraugus) admin manuāli izveido caur tinker / seed.
        try {
            $portfolio = $this->runner->run($strategy, [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'base_date' => $request->input('base_date'),
                'capital' => (float) $request->input('capital'),
                'params' => $params,
                'is_system' => false,
                'user_id' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['strategy' => 'Kļūda: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('portfolios.show', $portfolio)
            ->with('success', "Backtest portfelis '{$portfolio->name}' izveidots ({$portfolio->instruments()->count()} instrumenti).");
    }
}
