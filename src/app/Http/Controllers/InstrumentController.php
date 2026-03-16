<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): View
    {
        $rawSearch = trim((string) $request->query('q', ''));
        $search = mb_substr($rawSearch, 0, 100);

        $query = Instrument::query()
            ->select(['id', 'ticker', 'company_name', 'exchange']);

        if ($search !== '') {
            $escapedSearch = $this->escapeLike(mb_strtolower($search));
            $contains = '%'.$escapedSearch.'%';
            $prefix = $escapedSearch.'%';

            $query->where(function ($builder) use ($contains) {
                $builder->whereRaw('lower(ticker) like ?', [$contains])
                    ->orWhereRaw('lower(company_name) like ?', [$contains]);
            })->orderByRaw(
                'case when lower(ticker) like ? then 0 when lower(company_name) like ? then 1 else 2 end',
                [$prefix, $prefix]
            );
        }

        $instruments = $query
            ->orderBy('ticker')
            ->orderBy('exchange')
            ->paginate(25)
            ->withQueryString();

        return view('instruments.index', [
            'instruments' => $instruments,
            'search' => $search,
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

        return view('instruments.show', [
            'instrument' => $instrument,
            'priceSeries' => $priceSeries,
            'availableFundamentalYears' => $availableFundamentalYears,
            'fundamentalData' => $fundamentalData,
        ]);
    }

    private function getFundamentalData(Instrument $instrument): array
    {
        return Cache::remember(
            "fundamentals:{$instrument->id}",
            3600,
            fn () => $this->buildFundamentalData($instrument)
        );
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

            // Keep only data that matches the filing's own period
            $ownPeriod = $rows->filter(function ($row) use ($filing) {
                if ($row->context_date !== null) {
                    return $row->context_date === $filing->period_end;
                }
                return $row->period_end === $filing->period_end;
            });

            // Deduplicate: same tag may appear for different sub-periods
            // Annual → keep longest span (full year); Quarterly → keep shortest span (quarter only)
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

            // Classify each tag into a financial statement type
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
