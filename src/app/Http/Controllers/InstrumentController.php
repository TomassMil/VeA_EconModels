<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstrumentController extends Controller
{
    private const TAG_LABELS = [
        // Balance Sheet
        'us-gaap:Assets' => 'Total Assets',
        'us-gaap:AssetsCurrent' => 'Current Assets',
        'us-gaap:CashAndCashEquivalentsAtCarryingValue' => 'Cash & Equivalents',
        'us-gaap:ShortTermInvestments' => 'Short-term Investments',
        'us-gaap:AccountsReceivableNetCurrent' => 'Accounts Receivable',
        'us-gaap:InventoryNet' => 'Inventory',
        'us-gaap:PropertyPlantAndEquipmentNet' => 'PP&E (Net)',
        'us-gaap:Goodwill' => 'Goodwill',
        'us-gaap:IntangibleAssetsNetExcludingGoodwill' => 'Intangible Assets',
        'us-gaap:Liabilities' => 'Total Liabilities',
        'us-gaap:LiabilitiesCurrent' => 'Current Liabilities',
        'us-gaap:AccountsPayableCurrent' => 'Accounts Payable',
        'us-gaap:LongTermDebtNoncurrent' => 'Long-term Debt',
        'us-gaap:LongTermDebt' => 'Total Long-term Debt',
        'us-gaap:StockholdersEquity' => "Stockholders' Equity",
        'us-gaap:RetainedEarningsAccumulatedDeficit' => 'Retained Earnings',
        'us-gaap:LiabilitiesAndStockholdersEquity' => 'Total Liabilities & Equity',
        'us-gaap:CommonStockSharesOutstanding' => 'Shares Outstanding',
        // Income Statement
        'us-gaap:Revenues' => 'Revenue',
        'us-gaap:SalesRevenueNet' => 'Revenue',
        'us-gaap:RevenueFromContractWithCustomerExcludingAssessedTax' => 'Revenue',
        'us-gaap:CostOfRevenue' => 'Cost of Revenue',
        'us-gaap:CostOfGoodsAndServicesSold' => 'Cost of Revenue',
        'us-gaap:GrossProfit' => 'Gross Profit',
        'us-gaap:ResearchAndDevelopmentExpense' => 'R&D Expense',
        'us-gaap:SellingGeneralAndAdministrativeExpense' => 'SG&A Expense',
        'us-gaap:OperatingExpenses' => 'Operating Expenses',
        'us-gaap:OperatingIncomeLoss' => 'Operating Income',
        'us-gaap:NonoperatingIncomeExpense' => 'Non-operating Income',
        'us-gaap:InterestExpense' => 'Interest Expense',
        'us-gaap:IncomeTaxExpenseBenefit' => 'Income Tax',
        'us-gaap:NetIncomeLoss' => 'Net Income',
        'us-gaap:EarningsPerShareBasic' => 'EPS (Basic)',
        'us-gaap:EarningsPerShareDiluted' => 'EPS (Diluted)',
        'us-gaap:ComprehensiveIncomeNetOfTax' => 'Comprehensive Income',
        // Cash Flow
        'us-gaap:NetCashProvidedByUsedInOperatingActivities' => 'Operating Cash Flow',
        'us-gaap:NetCashProvidedByUsedInOperatingActivitiesContinuingOperations' => 'Operating Cash Flow',
        'us-gaap:NetCashProvidedByUsedInInvestingActivities' => 'Investing Cash Flow',
        'us-gaap:NetCashProvidedByUsedInInvestingActivitiesContinuingOperations' => 'Investing Cash Flow',
        'us-gaap:NetCashProvidedByUsedInFinancingActivities' => 'Financing Cash Flow',
        'us-gaap:NetCashProvidedByUsedInFinancingActivitiesContinuingOperations' => 'Financing Cash Flow',
        'us-gaap:DepreciationDepletionAndAmortization' => 'Depreciation & Amortization',
        'us-gaap:DepreciationAndAmortization' => 'Depreciation & Amortization',
        'us-gaap:PaymentsToAcquirePropertyPlantAndEquipment' => 'Capital Expenditures',
        'us-gaap:PaymentsOfDividendsCommonStock' => 'Dividends Paid',
        'us-gaap:PaymentsOfDividends' => 'Dividends Paid',
        'us-gaap:PaymentsForRepurchaseOfCommonStock' => 'Share Buybacks',
        'us-gaap:CashAndCashEquivalentsPeriodIncreaseDecrease' => 'Net Change in Cash',
        'us-gaap:ShareBasedCompensation' => 'Stock-based Compensation',
        'us-gaap:AllocatedShareBasedCompensationExpense' => 'Stock-based Compensation',
    ];

    private const CASH_FLOW_PATTERNS = [
        'NetCashProvided',
        'NetCashUsed',
        'CashAndCashEquivalentsPeriodIncreaseDecrease',
        'EffectOfExchangeRate',
        'PaymentsToAcquire',
        'ProceedsFrom',
        'PaymentsOfDividends',
        'PaymentsForRepurchase',
        'DepreciationDepletionAndAmortization',
        'DepreciationAndAmortization',
        'ShareBasedCompensation',
        'AllocatedShareBasedCompensation',
        'IncreaseDecreaseIn',
        'PaymentsOfDebt',
        'RepaymentsOf',
        'CapitalExpenditures',
        'PaymentsOfFinancingCosts',
    ];

    private const INCOME_FIELDS = ['revenue', 'net_income', 'gross_profit', 'operating_income'];
    private const BALANCE_FIELDS = ['total_assets', 'total_liabilities', 'total_equity'];

    private const SIMFIN_INCOME_LABELS = [
        'revenue' => 'Revenue',
        'cost_of_revenue' => 'Cost of Revenue',
        'gross_profit' => 'Gross Profit',
        'rd' => 'R&D Expense',
        'sga' => 'SG&A Expense',
        'operating_expenses' => 'Operating Expenses',
        'operating_income' => 'Operating Income',
        'non_operating_income' => 'Non-operating Income',
        'interest_expense_net' => 'Interest Expense',
        'income_tax' => 'Income Tax',
        'net_income' => 'Net Income',
        'pretax_income' => 'Pretax Income',
        'abnormal_gains_losses' => 'Abnormal Gains/Losses',
        'depreciation_amortization' => 'Depreciation & Amortization',
    ];

    private const SIMFIN_BALANCE_LABELS = [
        'total_assets' => 'Total Assets',
        'total_current_assets' => 'Current Assets',
        'cash_and_equivalents' => 'Cash & Equivalents',
        'accounts_receivable' => 'Accounts Receivable',
        'inventories' => 'Inventory',
        'ppe_net' => 'PP&E (Net)',
        'lt_investments' => 'Long-term Investments',
        'total_liabilities' => 'Total Liabilities',
        'total_current_liabilities' => 'Current Liabilities',
        'payables_accruals' => 'Accounts Payable',
        'short_term_debt' => 'Short-term Debt',
        'long_term_debt' => 'Long-term Debt',
        'total_equity' => "Stockholders' Equity",
        'retained_earnings' => 'Retained Earnings',
        'total_liabilities_equity' => 'Total Liabilities & Equity',
        'shares_basic' => 'Shares Outstanding',
    ];

    private const SIMFIN_CASHFLOW_LABELS = [
        'net_cash_operating' => 'Operating Cash Flow',
        'depreciation_amortization' => 'Depreciation & Amortization',
        'change_working_capital' => 'Change in Working Capital',
        'change_fixed_assets' => 'Capital Expenditures',
        'net_cash_investing' => 'Investing Cash Flow',
        'net_cash_acquisitions' => 'Net Cash Acquisitions',
        'cash_from_debt' => 'Cash from Debt',
        'cash_from_equity' => 'Cash from Equity',
        'dividends_paid' => 'Dividends Paid',
        'net_cash_financing' => 'Financing Cash Flow',
        'net_change_cash' => 'Net Change in Cash',
    ];

    private const PERF_INTERVALS = [
        '1m' => '30 days',
        '3m' => '90 days',
        '6m' => '180 days',
        '1y' => '365 days',
    ];

    private const PERF_DIRECTIONS = [
        'up'     => [0,   null],
        'down'   => [null, 0],
        'up5'    => [5,   null],
        'up10'   => [10,  null],
        'up20'   => [20,  null],
        'down5'  => [null, -5],
        'down10' => [null, -10],
        'down20' => [null, -20],
    ];

    public function index(Request $request): View
    {
        $sectors = DB::table('instruments')
            ->whereNotNull('sector')
            ->distinct()
            ->orderBy('sector')
            ->pluck('sector');

        $industriesGrouped = DB::table('instruments')
            ->select(['sector', 'industry'])
            ->whereNotNull('sector')
            ->whereNotNull('industry')
            ->distinct()
            ->orderBy('sector')
            ->orderBy('industry')
            ->get();

        $industryBySector = $industriesGrouped
            ->groupBy('sector')
            ->map(fn ($group) => $group->pluck('industry')->unique()->values());

        $allIndustries = $industriesGrouped->pluck('industry')->unique()->sort()->values();

        $total = (int) DB::table('instruments')->count();

        $instruments = $this->initialList();

        return view('instruments.index', [
            'instruments' => $instruments,
            'sectors' => $sectors,
            'industryBySector' => $industryBySector,
            'allIndustries' => $allIndustries,
            'total' => $total,
        ]);
    }

    public function filter(Request $request): JsonResponse
    {
        $perPage = 50;
        $page = max(1, (int) $request->input('page', 1));

        [$query, $activeMetrics] = $this->buildFilterQuery($request);

        $total = (int) (clone $query)->count('i.id');

        $instruments = $query
            ->orderBy('i.ticker')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return response()->json([
            'total' => $total,
            'data' => $instruments,
            'page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
            'active_metrics' => array_values(array_unique($activeMetrics)),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $rawSearch = trim((string) $request->query('q', ''));
        $search = mb_substr($rawSearch, 0, 100);

        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $escapedSearch = $this->escapeLike(mb_strtolower($search));
        $contains = '%'.$escapedSearch.'%';
        $prefix = $escapedSearch.'%';

        $instruments = Instrument::query()
            ->select(['id', 'ticker', 'company_name', 'exchange'])
            ->where(function ($builder) use ($contains) {
                $builder->whereRaw('lower(ticker) like ?', [$contains])
                    ->orWhereRaw('lower(company_name) like ?', [$contains]);
            })
            ->orderByRaw(
                'case when lower(ticker) like ? then 0 when lower(company_name) like ? then 1 else 2 end',
                [$prefix, $prefix]
            )
            ->orderBy('ticker')
            ->limit(12)
            ->get();

        return response()->json(['data' => $instruments]);
    }

    public function show(Instrument $instrument): View
    {
        $priceSeries = DB::table('prices_daily')
            ->select(['time', 'open', 'high', 'low', 'close', 'volume'])
            ->where('instrument_id', $instrument->id)
            ->where(function ($query) {
                $query->whereNotNull('close')
                    ->orWhereNotNull('open')
                    ->orWhereNotNull('high')
                    ->orWhereNotNull('low')
                    ->orWhereNotNull('volume');
            })
            ->orderBy('time')
            ->get()
            ->values();

        $fundamentalData = $this->getFundamentalData($instrument);

        $availableFundamentalYears = collect(array_keys($fundamentalData))
            ->filter(fn ($y) => preg_match('/^\d{4}$/', $y))
            ->sortDesc()
            ->values();

        $userPortfolios = Auth::check()
            ? \App\Models\Portfolio::where('user_id', Auth::id())->orderBy('name')->get(['id', 'name', 'free_capital'])
            : collect();

        return view('instruments.show', [
            'instrument' => $instrument,
            'priceSeries' => $priceSeries,
            'availableFundamentalYears' => $availableFundamentalYears,
            'fundamentalData' => $fundamentalData,
            'userPortfolios' => $userPortfolios,
        ]);
    }

    private function initialList()
    {
        return DB::table('instruments as i')
            ->leftJoinLateral(
                DB::table('prices_daily')
                    ->select(['close as latest_close'])
                    ->whereColumn('instrument_id', 'i.id')
                    ->whereNotNull('close')
                    ->orderByDesc('time')
                    ->limit(1),
                'ps'
            )
            ->select(['i.id', 'i.ticker', 'i.company_name', 'i.sector', 'i.industry', 'ps.latest_close'])
            ->orderBy('i.ticker')
            ->limit(50)
            ->get();
    }

    /**
     * Build the dynamic filter query. Returns [query, activeMetrics].
     */
    private function buildFilterQuery(Request $request): array
    {
        $query = DB::table('instruments as i')
            ->select(['i.id', 'i.ticker', 'i.company_name', 'i.sector', 'i.industry']);

        $activeMetrics = [];

        // Search
        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $escaped = $this->escapeLike(mb_strtolower(mb_substr($search, 0, 100)));
            $contains = '%'.$escaped.'%';
            $query->where(function ($q) use ($contains) {
                $q->whereRaw('lower(i.ticker) like ?', [$contains])
                    ->orWhereRaw('lower(i.company_name) like ?', [$contains]);
            });
        }

        // Descriptive
        if ($request->filled('sector')) {
            $query->where('i.sector', $request->input('sector'));
        }
        if ($request->filled('industry')) {
            $query->where('i.industry', $request->input('industry'));
        }

        // Always join latest price for display
        $query->leftJoinLateral(
            DB::table('prices_daily')
                ->select(['close as latest_close'])
                ->whereColumn('instrument_id', 'i.id')
                ->whereNotNull('close')
                ->orderByDesc('time')
                ->limit(1),
            'ps'
        );
        $query->addSelect('ps.latest_close');

        if ($request->filled('price_min')) {
            $query->where('ps.latest_close', '>=', (float) $request->input('price_min'));
            $activeMetrics[] = 'latest_close';
        }
        if ($request->filled('price_max')) {
            $query->where('ps.latest_close', '<=', (float) $request->input('price_max'));
            $activeMetrics[] = 'latest_close';
        }

        // Volume
        if ($request->filled('volume_min') || $request->filled('volume_max')) {
            $query->leftJoin(
                DB::raw("(SELECT instrument_id, AVG(volume)::bigint AS avg_volume FROM prices_daily GROUP BY instrument_id) AS vs"),
                'i.id', '=', 'vs.instrument_id'
            );
            $query->addSelect('vs.avg_volume');
            $activeMetrics[] = 'avg_volume';

            if ($request->filled('volume_min')) {
                $query->where('vs.avg_volume', '>=', (float) $request->input('volume_min'));
            }
            if ($request->filled('volume_max')) {
                $query->where('vs.avg_volume', '<=', (float) $request->input('volume_max'));
            }
        }

        // Income statement
        $incomeActive = [];
        foreach (self::INCOME_FIELDS as $f) {
            if ($request->filled("{$f}_min") || $request->filled("{$f}_max")) {
                $incomeActive[] = $f;
                $activeMetrics[] = $f;
            }
        }
        if (!empty($incomeActive)) {
            $sums = implode(', ', array_map(fn ($f) => "SUM({$f}) AS {$f}", self::INCOME_FIELDS));
            $query->leftJoin(
                DB::raw("(SELECT instrument_id, {$sums} FROM (
                    SELECT instrument_id, ".implode(', ', self::INCOME_FIELDS).",
                           ROW_NUMBER() OVER (PARTITION BY instrument_id ORDER BY fiscal_year DESC, fiscal_period DESC) AS rn
                    FROM simfin_income_statement
                ) s WHERE rn <= 4 GROUP BY instrument_id) AS inc"),
                'i.id', '=', 'inc.instrument_id'
            );
            foreach ($incomeActive as $f) {
                $query->addSelect("inc.{$f}");
                if ($request->filled("{$f}_min")) {
                    $query->where("inc.{$f}", '>=', (float) $request->input("{$f}_min"));
                }
                if ($request->filled("{$f}_max")) {
                    $query->where("inc.{$f}", '<=', (float) $request->input("{$f}_max"));
                }
            }
        }

        // Balance sheet
        $bsActive = [];
        foreach (self::BALANCE_FIELDS as $f) {
            if ($request->filled("{$f}_min") || $request->filled("{$f}_max")) {
                $bsActive[] = $f;
                $activeMetrics[] = $f;
            }
        }
        if (!empty($bsActive)) {
            $cols = implode(', ', array_merge(['instrument_id'], self::BALANCE_FIELDS));
            $query->leftJoin(
                DB::raw("(SELECT DISTINCT ON (instrument_id) {$cols} FROM simfin_balance_sheet ORDER BY instrument_id, fiscal_year DESC, fiscal_period DESC) AS bs"),
                'i.id', '=', 'bs.instrument_id'
            );
            foreach ($bsActive as $f) {
                $query->addSelect("bs.{$f}");
                if ($request->filled("{$f}_min")) {
                    $query->where("bs.{$f}", '>=', (float) $request->input("{$f}_min"));
                }
                if ($request->filled("{$f}_max")) {
                    $query->where("bs.{$f}", '<=', (float) $request->input("{$f}_max"));
                }
            }
        }

        // Cash flow (operating)
        if ($request->filled('operating_cf_min') || $request->filled('operating_cf_max')) {
            $query->leftJoin(
                DB::raw("(SELECT instrument_id, SUM(net_cash_operating) AS net_cash_operating FROM (
                    SELECT instrument_id, net_cash_operating,
                           ROW_NUMBER() OVER (PARTITION BY instrument_id ORDER BY fiscal_year DESC, fiscal_period DESC) AS rn
                    FROM simfin_cashflow
                ) s WHERE rn <= 4 GROUP BY instrument_id) AS cf"),
                'i.id', '=', 'cf.instrument_id'
            );
            $query->addSelect('cf.net_cash_operating');
            $activeMetrics[] = 'operating_cf';

            if ($request->filled('operating_cf_min')) {
                $query->where('cf.net_cash_operating', '>=', (float) $request->input('operating_cf_min'));
            }
            if ($request->filled('operating_cf_max')) {
                $query->where('cf.net_cash_operating', '<=', (float) $request->input('operating_cf_max'));
            }
        }

        // Technical: price change over period
        $perfPeriod = $request->input('perf_period');
        $perfDirection = $request->input('perf_direction');
        if ($perfPeriod && $perfDirection
            && isset(self::PERF_INTERVALS[$perfPeriod])
            && isset(self::PERF_DIRECTIONS[$perfDirection])
        ) {
            $interval = self::PERF_INTERVALS[$perfPeriod];
            $query->join(
                DB::raw($this->perfSubquery($interval)),
                'i.id', '=', 'perf.instrument_id'
            );
            $query->addSelect('perf.pct_change');
            $activeMetrics[] = 'pct_change';

            [$min, $max] = self::PERF_DIRECTIONS[$perfDirection];
            if ($min !== null) {
                $query->where('perf.pct_change', '>=', $min);
            }
            if ($max !== null) {
                $query->where('perf.pct_change', '<=', $max);
            }
        }

        return [$query, $activeMetrics];
    }

    private function perfSubquery(string $interval): string
    {
        return "(
            SELECT p1.instrument_id,
                   CASE WHEN p2.close > 0 THEN ((p1.close - p2.close) / p2.close * 100) END AS pct_change
            FROM (
                SELECT DISTINCT ON (instrument_id) instrument_id, close, time AS latest_date
                FROM prices_daily WHERE close IS NOT NULL
                ORDER BY instrument_id, time DESC
            ) p1
            LEFT JOIN LATERAL (
                SELECT close FROM prices_daily
                WHERE instrument_id = p1.instrument_id
                  AND close IS NOT NULL
                  AND time <= p1.latest_date - interval '{$interval}'
                ORDER BY time DESC LIMIT 1
            ) p2 ON true
            WHERE p2.close IS NOT NULL
        ) AS perf";
    }

    private function getFundamentalData(Instrument $instrument): array
    {
        return Cache::remember(
            "fundamentals:v2:{$instrument->id}",
            3600,
            fn () => $this->mergeFundamentals(
                $this->buildFundamentalData($instrument),
                $this->buildSimFinFundamentalData($instrument)
            )
        );
    }

    private function buildSimFinFundamentalData(Instrument $instrument): array
    {
        $result = [];

        $this->collectSimFinStatement(
            $result,
            'simfin_income_statement',
            'income_statement',
            self::SIMFIN_INCOME_LABELS,
            $instrument->id
        );
        $this->collectSimFinStatement(
            $result,
            'simfin_balance_sheet',
            'balance_sheet',
            self::SIMFIN_BALANCE_LABELS,
            $instrument->id
        );
        $this->collectSimFinStatement(
            $result,
            'simfin_cashflow',
            'cash_flow_statement',
            self::SIMFIN_CASHFLOW_LABELS,
            $instrument->id
        );

        foreach ($result as $year => $node) {
            $result[$year]['annual'] = $this->buildAnnualFromQuarters($node['quarters']);
        }

        return $result;
    }

    private function collectSimFinStatement(array &$result, string $table, string $statementKey, array $labelMap, int $instrumentId): void
    {
        $columns = array_merge(['fiscal_year', 'fiscal_period'], array_keys($labelMap));

        $rows = DB::table($table)
            ->where('instrument_id', $instrumentId)
            ->select($columns)
            ->get();

        foreach ($rows as $row) {
            $year = (string) $row->fiscal_year;
            $period = strtoupper((string) $row->fiscal_period);
            if (!preg_match('/^Q[1-4]$/', $period)) {
                continue;
            }

            if (!isset($result[$year])) {
                $result[$year] = [
                    'annual' => [
                        'balance_sheet' => [],
                        'income_statement' => [],
                        'cash_flow_statement' => [],
                    ],
                    'quarters' => [],
                ];
            }
            if (!isset($result[$year]['quarters'][$period])) {
                $result[$year]['quarters'][$period] = [
                    'balance_sheet' => [],
                    'income_statement' => [],
                    'cash_flow_statement' => [],
                ];
            }

            foreach ($labelMap as $col => $label) {
                $val = $row->{$col} ?? null;
                if ($val === null || $val === '') {
                    continue;
                }
                $result[$year]['quarters'][$period][$statementKey][$label] = (float) $val;
            }
        }
    }

    private function buildAnnualFromQuarters(array $quarters): array
    {
        $annual = [
            'balance_sheet' => [],
            'income_statement' => [],
            'cash_flow_statement' => [],
        ];

        $orderedKeys = ['Q4', 'Q3', 'Q2', 'Q1'];

        // Balance sheet: latest available quarter (Q4 if present, else Q3, ...)
        foreach ($orderedKeys as $q) {
            if (!empty($quarters[$q]['balance_sheet'])) {
                $annual['balance_sheet'] = $quarters[$q]['balance_sheet'];
                break;
            }
        }

        // Income + cash flow: sum all available quarters
        foreach (['income_statement', 'cash_flow_statement'] as $sk) {
            $sums = [];
            foreach ($quarters as $qData) {
                foreach ($qData[$sk] ?? [] as $label => $val) {
                    $sums[$label] = ($sums[$label] ?? 0) + (float) $val;
                }
            }
            $annual[$sk] = $sums;
        }

        return $annual;
    }

    private function mergeFundamentals(array $edgar, array $simfin): array
    {
        $merged = $edgar;

        foreach ($simfin as $year => $node) {
            if (!isset($merged[$year])) {
                $merged[$year] = $node;
                continue;
            }

            foreach (['balance_sheet', 'income_statement', 'cash_flow_statement'] as $sk) {
                if (empty($merged[$year]['annual'][$sk])) {
                    $merged[$year]['annual'][$sk] = $node['annual'][$sk];
                }
            }
            foreach ($node['quarters'] as $q => $qData) {
                if (!isset($merged[$year]['quarters'][$q])) {
                    $merged[$year]['quarters'][$q] = $qData;
                    continue;
                }
                foreach ($qData as $sk => $payload) {
                    if (empty($merged[$year]['quarters'][$q][$sk])) {
                        $merged[$year]['quarters'][$q][$sk] = $payload;
                    }
                }
            }
        }

        return $merged;
    }

    private function buildFundamentalData(Instrument $instrument): array
    {
        $filings = DB::table('filings')
            ->where('instrument_id', $instrument->id)
            ->whereNotNull('fiscal_year')
            ->get();

        if ($filings->isEmpty()) {
            return [];
        }

        $filingIds = $filings->pluck('id')->all();

        $financialData = DB::table('financial_data')
            ->whereIn('filing_id', $filingIds)
            ->whereNull('dimension')
            ->whereNotNull('value_num')
            ->where('xbrl_tag', 'not like', 'dei:%')
            ->select(['filing_id', 'xbrl_tag', 'context_date', 'period_start', 'period_end', 'value_num'])
            ->get();

        $byFiling = $financialData->groupBy('filing_id');
        $result = [];

        foreach ($filings as $filing) {
            $rows = $byFiling->get($filing->id, collect());
            if ($rows->isEmpty()) {
                continue;
            }

            $year = (string) $filing->fiscal_year;
            $isAnnual = $filing->filing_type === 'Y';

            $ownPeriod = $rows->filter(function ($row) use ($filing) {
                if ($row->context_date !== null) {
                    return $row->context_date === $filing->period_end;
                }
                return $row->period_end === $filing->period_end;
            });

            $deduped = [];
            foreach ($ownPeriod as $row) {
                $tag = $row->xbrl_tag;
                if (!isset($deduped[$tag])) {
                    $deduped[$tag] = $row;
                    continue;
                }
                if ($row->period_start !== null && $deduped[$tag]->period_start !== null) {
                    if ($isAnnual && $row->period_start < $deduped[$tag]->period_start) {
                        $deduped[$tag] = $row;
                    } elseif (!$isAnnual && $row->period_start > $deduped[$tag]->period_start) {
                        $deduped[$tag] = $row;
                    }
                }
            }

            $statements = [
                'balance_sheet' => [],
                'income_statement' => [],
                'cash_flow_statement' => [],
            ];

            foreach ($deduped as $tag => $row) {
                $label = $this->formatXbrlLabel($tag);
                $type = $this->classifyXbrlTag($tag, $row->context_date !== null);
                $statements[$type][$label] = $row->value_num;
            }

            if (!isset($result[$year])) {
                $result[$year] = [
                    'annual' => [
                        'balance_sheet' => [],
                        'income_statement' => [],
                        'cash_flow_statement' => [],
                    ],
                    'quarters' => [],
                ];
            }

            if ($isAnnual) {
                $result[$year]['annual'] = $statements;
            } else {
                $period = $filing->fiscal_period ?? 'Q?';
                $result[$year]['quarters'][$period] = $statements;
            }
        }

        return $result;
    }

    private function classifyXbrlTag(string $tag, bool $hasContextDate): string
    {
        if ($hasContextDate) {
            return 'balance_sheet';
        }

        $tagName = preg_replace('/^[^:]+:/', '', $tag);

        foreach (self::CASH_FLOW_PATTERNS as $pattern) {
            if (str_contains($tagName, $pattern)) {
                return 'cash_flow_statement';
            }
        }

        return 'income_statement';
    }

    private function formatXbrlLabel(string $tag): string
    {
        if (isset(self::TAG_LABELS[$tag])) {
            return self::TAG_LABELS[$tag];
        }

        $name = preg_replace('/^[^:]+:/', '', $tag);

        return preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $name);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
