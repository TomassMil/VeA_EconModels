<?php

namespace App\Http\Controllers;

use App\Models\Index;
use App\Models\Instrument;
use App\Services\ChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __construct(private ChartService $chartService) {}


    /**
     * Suggested filter presets for quick index creation.
     */
    public const FILTER_PRESETS = [
        'large_cap_liquid' => [
            'name' => 'Lielās kapitalizācijas, likvīdi',
            'description' => 'Akcijas ar augstu vidējo apjomu un cenu virs $10',
            'filters' => [
                'avg_volume_min' => 1000000,
                'exclude_below_price' => 10.00,
            ],
        ],
        'mid_cap' => [
            'name' => 'Vidējās kapitalizācijas',
            'description' => 'Akcijas ar cenu $5–$200 un vidējo apjomu virs 100K',
            'filters' => [
                'price_min' => 5.00,
                'price_max' => 200.00,
                'avg_volume_min' => 100000,
            ],
        ],
        'penny_stocks' => [
            'name' => 'Lētās akcijas (Penny stocks)',
            'description' => 'Akcijas ar cenu zem $5',
            'filters' => [
                'price_max' => 5.00,
            ],
        ],
        'high_volume' => [
            'name' => 'Augsta apjoma akcijas',
            'description' => 'Instrumenti ar vidējo dienas apjomu virs 5M',
            'filters' => [
                'avg_volume_min' => 5000000,
            ],
        ],
        'with_fundamentals' => [
            'name' => 'Ar finanšu datiem',
            'description' => 'Tikai instrumenti, kuriem ir fundamentālie dati',
            'filters' => [
                'has_fundamentals' => true,
            ],
        ],
        'profitable' => [
            'name' => 'Peļņu nesoši',
            'description' => 'Uzņēmumi ar pozitīvu tīro peļņu un EPS',
            'filters' => [
                'has_fundamentals' => true,
                'net_income_min' => 0,
                'eps_min' => 0,
            ],
        ],
        'high_revenue' => [
            'name' => 'Lielie uzņēmumi',
            'description' => 'Uzņēmumi ar gada ieņēmumiem virs $1 miljarda',
            'filters' => [
                'has_fundamentals' => true,
                'revenue_min' => 1000000000,
            ],
        ],
    ];

    private const FUNDAMENTAL_FILTER_TAGS = [
        'revenue' => [
            'us-gaap:Revenues',
            'us-gaap:RevenueFromContractWithCustomerExcludingAssessedTax',
            'us-gaap:SalesRevenueNet',
        ],
        'net_income' => ['us-gaap:NetIncomeLoss'],
        'total_assets' => ['us-gaap:Assets'],
        'total_liabilities' => ['us-gaap:Liabilities'],
        'eps' => ['us-gaap:EarningsPerShareBasic'],
        'operating_cf' => [
            'us-gaap:NetCashProvidedByUsedInOperatingActivities',
            'us-gaap:NetCashProvidedByUsedInOperatingActivitiesContinuingOperations',
        ],
    ];

    public function index(): View
    {
        $publicIndexes = Index::where('is_public', true)
            ->withCount('instruments')
            ->orderBy('name')
            ->get();

        $userIndexes = Index::where('user_id', Auth::id())
            ->withCount('instruments')
            ->orderByDesc('updated_at')
            ->get();

        return view('indexes.index', [
            'publicIndexes' => $publicIndexes,
            'userIndexes' => $userIndexes,
        ]);
    }

    public function create(): View
    {
        return view('indexes.create', [
            'presets' => self::FILTER_PRESETS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'filters' => 'nullable|array',
            'filters.price_min' => 'nullable|numeric|min:0',
            'filters.price_max' => 'nullable|numeric|min:0',
            'filters.avg_volume_min' => 'nullable|integer|min:0',
            'filters.avg_volume_max' => 'nullable|integer|min:0',
            'filters.exclude_below_price' => 'nullable|numeric|min:0',
            'filters.has_fundamentals' => 'nullable|boolean',
            'filters.revenue_min' => 'nullable|numeric',
            'filters.revenue_max' => 'nullable|numeric',
            'filters.net_income_min' => 'nullable|numeric',
            'filters.net_income_max' => 'nullable|numeric',
            'filters.total_assets_min' => 'nullable|numeric|min:0',
            'filters.total_assets_max' => 'nullable|numeric|min:0',
            'filters.total_liabilities_min' => 'nullable|numeric|min:0',
            'filters.total_liabilities_max' => 'nullable|numeric|min:0',
            'filters.eps_min' => 'nullable|numeric',
            'filters.eps_max' => 'nullable|numeric',
            'filters.operating_cf_min' => 'nullable|numeric',
            'filters.operating_cf_max' => 'nullable|numeric',
            'manual_instruments' => 'nullable|array',
            'manual_instruments.*' => 'integer|exists:instruments,id',
            'excluded_instruments' => 'nullable|array',
            'excluded_instruments.*' => 'integer',
        ]);

        $slug = Str::slug($validated['name']);
        $baseSlug = $slug;
        $counter = 1;
        while (Index::where('user_id', Auth::id())->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        // Clean empty filter values
        $filters = collect($validated['filters'] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();

        $index = Index::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'filters' => !empty($filters) ? $filters : null,
        ]);

        // Apply filters to find matching instruments
        $filteredIds = $this->applyFilters($filters);

        // Remove excluded instruments
        $excludedIds = array_map('intval', $validated['excluded_instruments'] ?? []);
        $filteredIds = array_values(array_diff($filteredIds, $excludedIds));

        // Attach filter-matched instruments
        $pivotData = [];
        foreach ($filteredIds as $id) {
            $pivotData[$id] = ['added_manually' => false];
        }

        // Attach manually selected instruments (also respecting exclusions)
        $manualIds = array_values(array_diff($validated['manual_instruments'] ?? [], $excludedIds));
        foreach ($manualIds as $id) {
            $pivotData[$id] = ['added_manually' => true];
        }

        if (!empty($pivotData)) {
            $index->instruments()->attach($pivotData);
        }

        return redirect()->route('indexes.show', $index)->with('status', 'Indekss izveidots!');
    }

    public function show(Request $request, Index $index): View
    {
        Gate::authorize('view', $index);

        $instruments = $index->instruments()
            ->select(['instruments.id', 'ticker', 'company_name', 'exchange'])
            ->orderBy('ticker')
            ->paginate(50);

        $weighting = $request->query('weighting', 'market_cap');
        if (!in_array($weighting, ['market_cap', 'equal', 'price'], true)) {
            $weighting = 'market_cap';
        }

        $allInstrumentIds = $index->instruments()->pluck('instruments.id')->all();

        $chart = Cache::remember(
            "index_chart:{$index->id}:{$weighting}",
            3600,
            fn () => $this->chartService->buildIndexSeries($allInstrumentIds, $weighting)
        );

        return view('indexes.show', [
            'index' => $index,
            'instruments' => $instruments,
            'chart' => $chart,
            'weighting' => $weighting,
        ]);
    }

    /**
     * Preview: apply filters and return matching instruments (JSON, for the create page).
     */
    public function preview(Request $request): JsonResponse
    {
        $filters = $request->input('filters', []);
        $manualIds = $request->input('manual_instruments', []);
        $excludedIds = array_map('intval', $request->input('excluded_instruments', []));

        $filteredIds = $this->applyFilters($filters);
        $allIds = array_values(array_diff(
            array_unique(array_merge($filteredIds, $manualIds)),
            $excludedIds
        ));

        $total = count($allIds);
        $previewIds = array_slice($allIds, 0, 15);

        $sub = DB::table('prices_daily')
            ->select([
                'instrument_id',
                DB::raw('(array_agg(close ORDER BY time DESC))[1] as latest_close'),
                DB::raw('avg(volume) as avg_volume'),
            ])
            ->whereNotNull('close')
            ->groupBy('instrument_id');

        $preview = DB::table('instruments')
            ->leftJoinSub($sub, 'stats', 'instruments.id', '=', 'stats.instrument_id')
            ->whereIn('instruments.id', $previewIds)
            ->select(['instruments.id', 'ticker', 'company_name', 'exchange', 'stats.latest_close', 'stats.avg_volume'])
            ->orderBy('ticker')
            ->get();

        // Determine which fundamental fields have active filters
        $activeFundFields = [];
        foreach (array_keys(self::FUNDAMENTAL_FILTER_TAGS) as $field) {
            $min = $filters["{$field}_min"] ?? null;
            $max = $filters["{$field}_max"] ?? null;
            if (($min !== null && $min !== '') || ($max !== null && $max !== '')) {
                $activeFundFields[] = $field;
            }
        }

        $fundamentalValues = !empty($activeFundFields) && !empty($previewIds)
            ? $this->getFundamentalValuesForPreview($previewIds, $activeFundFields)
            : [];

        foreach ($preview as $inst) {
            $inst->fundamentals = (object) ($fundamentalValues[$inst->id] ?? []);
        }

        return response()->json([
            'total' => $total,
            'preview' => $preview,
        ]);
    }

    public function destroy(Index $index)
    {
        Gate::authorize('delete', $index);

        $index->delete();

        return redirect()->route('indexes.index')->with('status', 'Indekss dzēsts.');
    }

    /**
     * Fetch fundamental data values for preview instruments (only for active filter fields).
     */
    private function getFundamentalValuesForPreview(array $instrumentIds, array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $tags = self::FUNDAMENTAL_FILTER_TAGS[$field] ?? [];
            if (empty($tags)) {
                continue;
            }

            $values = DB::table('financial_data as fd')
                ->join('filings as f', 'f.id', '=', 'fd.filing_id')
                ->whereIn('f.instrument_id', $instrumentIds)
                ->where('f.filing_type', 'Y')
                ->whereNotNull('f.fiscal_year')
                ->whereIn('fd.xbrl_tag', $tags)
                ->whereNull('fd.dimension')
                ->whereNotNull('fd.value_num')
                ->whereRaw("f.period_end = (SELECT MAX(f2.period_end) FROM filings f2 WHERE f2.instrument_id = f.instrument_id AND f2.filing_type = 'Y' AND f2.fiscal_year IS NOT NULL)")
                ->select(['f.instrument_id', 'fd.value_num'])
                ->get();

            foreach ($values as $row) {
                $result[$row->instrument_id][$field] = $row->value_num;
            }
        }

        return $result;
    }

    /**
     * Apply filter criteria and return matching instrument IDs.
     */
    private function applyFilters(array $filters): array
    {
        if (empty($filters)) {
            return [];
        }

        // Build a subquery that gets the latest close + avg volume per instrument
        $sub = DB::table('prices_daily')
            ->select([
                'instrument_id',
                DB::raw('(array_agg(close ORDER BY time DESC))[1] as latest_close'),
                DB::raw('avg(volume) as avg_volume'),
            ])
            ->whereNotNull('close')
            ->groupBy('instrument_id');

        $query = DB::table('instruments')
            ->joinSub($sub, 'stats', 'instruments.id', '=', 'stats.instrument_id')
            ->select('instruments.id');

        // Price filters
        $priceMin = $filters['price_min'] ?? null;
        $priceMax = $filters['price_max'] ?? null;
        $excludeBelow = $filters['exclude_below_price'] ?? null;

        if ($priceMin !== null && $priceMin !== '') {
            $query->where('stats.latest_close', '>=', (float) $priceMin);
        }
        if ($priceMax !== null && $priceMax !== '') {
            $query->where('stats.latest_close', '<=', (float) $priceMax);
        }
        if ($excludeBelow !== null && $excludeBelow !== '') {
            $query->where('stats.latest_close', '>=', (float) $excludeBelow);
        }

        // Volume filters
        $volMin = $filters['avg_volume_min'] ?? null;
        $volMax = $filters['avg_volume_max'] ?? null;

        if ($volMin !== null && $volMin !== '') {
            $query->where('stats.avg_volume', '>=', (int) $volMin);
        }
        if ($volMax !== null && $volMax !== '') {
            $query->where('stats.avg_volume', '<=', (int) $volMax);
        }

        // Fundamentals filter
        if (!empty($filters['has_fundamentals'])) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('filings')
                    ->whereColumn('filings.instrument_id', 'instruments.id');
            });
        }

        // Fundamental data filters (revenue, net income, assets, liabilities, eps, operating CF)
        $fundamentalFields = array_keys(self::FUNDAMENTAL_FILTER_TAGS);
        foreach ($fundamentalFields as $field) {
            $min = $filters["{$field}_min"] ?? null;
            $max = $filters["{$field}_max"] ?? null;
            if (($min === null || $min === '') && ($max === null || $max === '')) {
                continue;
            }

            $tags = self::FUNDAMENTAL_FILTER_TAGS[$field];
            $query->whereExists(function ($sub) use ($tags, $min, $max) {
                $sub->select(DB::raw(1))
                    ->from('financial_data as fd')
                    ->join('filings as f', 'f.id', '=', 'fd.filing_id')
                    ->whereColumn('f.instrument_id', 'instruments.id')
                    ->where('f.filing_type', 'Y')
                    ->whereNotNull('f.fiscal_year')
                    ->whereIn('fd.xbrl_tag', $tags)
                    ->whereNull('fd.dimension')
                    ->whereNotNull('fd.value_num')
                    ->whereRaw("f.period_end = (SELECT MAX(f2.period_end) FROM filings f2 WHERE f2.instrument_id = f.instrument_id AND f2.filing_type = 'Y' AND f2.fiscal_year IS NOT NULL)");

                if ($min !== null && $min !== '') {
                    $sub->where('fd.value_num', '>=', (float) $min);
                }
                if ($max !== null && $max !== '') {
                    $sub->where('fd.value_num', '<=', (float) $max);
                }
            });
        }

        return $query->pluck('instruments.id')->all();
    }
}
