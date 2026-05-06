<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Valuation model categories (used on /modeli + home page tree)
|--------------------------------------------------------------------------
*/
$valuationCategories = [
    'i-dividenzu-izaugsmes' => [
        'title' => 'I. Dividenžu un izaugsmes modeļi',
        'description' => 'Modeļi, kas vērtē akcijas pamatojoties uz nākotnes dividendēm un to izaugsmes tempu.',
        'models' => [
            ['name' => 'Williams dividend discount model', 'year' => 1938, 'author' => 'John Burr Williams', 'note' => 'Akcijas vērtība = diskontētā nākotnes dividenžu summa.'],
            ['name' => 'Walter modelis', 'year' => 1963, 'author' => 'James E. Walter', 'note' => 'Saista dividenžu politiku ar akcijas cenu caur ROE/r attiecību.'],
            ['name' => 'Gordon–Shapiro modelis', 'year' => 1956, 'author' => 'Myron Gordon, Eli Shapiro', 'note' => 'Pastāvīgas izaugsmes DDM: P = D₁ / (r − g).'],
            ['name' => 'Fuller–Hsia H-modelis', 'year' => 1984, 'author' => 'Russell Fuller, Chi-Cheng Hsia', 'note' => 'Pārejas (transition) izaugsme starp augstu un stabilu g.'],
            ['name' => 'Graham iekšējās vērtības formula', 'year' => 1962, 'author' => 'Benjamin Graham', 'note' => 'V = EPS × (8.5 + 2g) — ātrs vērtības skrīninga rīks.'],
        ],
    ],
    'ii-cash-flow-vc' => [
        'title' => 'II. Cash-flow, capital structure un VC pieejas',
        'description' => 'DCF saimes modeļi (FCFF, FCFE, APV, CCF) un riska kapitāla (VC) novērtēšanas metodes.',
        'models' => [
            ['name' => 'Modigliani–Miller firm valuation', 'year' => '1958/1963', 'author' => 'Modigliani, Miller', 'note' => 'Kapitāla struktūras teorija ar/bez nodokļiem.'],
            ['name' => 'Myers APV modelis', 'year' => 1974, 'author' => 'Stewart Myers', 'note' => 'Adjusted Present Value — atsevišķi vērtē operāciju un finansēšanas blakusefektus.'],
            ['name' => 'Ruback capital cash flow', 'year' => 2002, 'author' => 'Richard Ruback', 'note' => 'CCF iekļauj nodokļu vairogu pašā cash flow.'],
            ['name' => 'Kaplan–Ruback cash-flow valuation', 'year' => 1995, 'author' => 'Kaplan, Ruback', 'note' => 'DCF empīriska validācija ar HLT darījumiem.'],
            ['name' => 'Damodaran FCFF framework', 'year' => '1990s', 'author' => 'Aswath Damodaran', 'note' => 'Free Cash Flow to Firm → uzņēmuma kopējā vērtība.'],
            ['name' => 'Damodaran FCFE framework', 'year' => '1990s', 'author' => 'Aswath Damodaran', 'note' => 'Free Cash Flow to Equity → tieša pašu kapitāla vērtība.'],
            ['name' => 'Miles–Ezzell modelis', 'year' => 1980, 'author' => 'Miles, Ezzell', 'note' => 'Tax shield pie target D/V attiecības.'],
            ['name' => 'Harris–Pringle modelis', 'year' => 1985, 'author' => 'Harris, Pringle', 'note' => 'Vienkāršāka tax shield pieeja — diskontē ar unlevered cost.'],
            ['name' => 'First Chicago method', 'year' => '1980s', 'author' => 'First Chicago Bank', 'note' => 'VC scenāriju metode: success/sideways/failure svēršana.'],
            ['name' => 'Sahlman venture capital method', 'year' => 1987, 'author' => 'William Sahlman', 'note' => 'Pre/post-money vērtēšana caur exit multiple.'],
            ['name' => 'Berkus method', 'year' => '1990s', 'author' => 'Dave Berkus', 'note' => 'Pre-revenue startupu kvalitatīvs vērtējums (5 faktori).'],
            ['name' => 'Payne scorecard method', 'year' => '2000s', 'author' => 'Bill Payne', 'note' => 'Angel investor scoring pret vidējo tirgus vērtējumu.'],
            ['name' => 'Risk factor summation method', 'year' => '2000s', 'author' => '—', 'note' => 'Summē 12 riska kategoriju korekcijas pret bāzes vērtību.'],
        ],
    ],
    'iii-accounting-based' => [
        'title' => 'III. Accounting-based valuation',
        'description' => 'Modeļi, kas balstās uz grāmatvedības rādītājiem (book value, residual income, abnormal earnings).',
        'models' => [
            ['name' => 'Edwards–Bell modelis', 'year' => 1961, 'author' => 'Edgar Edwards, Philip Bell', 'note' => 'Residual income teorētiskā bāze.'],
            ['name' => 'Peasnell residual income model', 'year' => 1982, 'author' => 'Ken Peasnell', 'note' => 'RI valuation kā BV plus diskontētie pārpalikuma ienākumi.'],
            ['name' => 'Ohlson modelis', 'year' => 1995, 'author' => 'James Ohlson', 'note' => 'Equity vērtība no book value un residual income.'],
            ['name' => 'Feltham–Ohlson modelis', 'year' => 1995, 'author' => 'Feltham, Ohlson', 'note' => 'Paplašinātais RI ar operatīvajām vs finanšu darbībām.'],
            ['name' => 'Edwards–Bell–Ohlson (EBO)', 'year' => '1961/1995', 'author' => '—', 'note' => 'Konsolidētā accounting-based modeļu saime.'],
            ['name' => 'Ohlson–Juettner–Nauroth modelis', 'year' => 2005, 'author' => 'Ohlson, Juettner-Nauroth', 'note' => 'Implied cost of capital no analītiķu prognozēm.'],
            ['name' => 'Penman abnormal earnings growth', 'year' => 2001, 'author' => 'Stephen Penman', 'note' => 'AEG valuation — fokus uz peļņas izaugsmi.'],
            ['name' => 'Frankel–Lee valuation model', 'year' => 1998, 'author' => 'Frankel, Lee', 'note' => 'Mispricing signāls no analītiķu konsensa.'],
        ],
    ],
    'iv-asset-pricing' => [
        'title' => 'IV. Diskonta likme un asset-pricing modeļi',
        'description' => 'Modeļi, kas nosaka required return / cost of equity caur tirgus risku un faktoriem.',
        'models' => [
            ['name' => 'Sharpe CAPM', 'year' => 1964, 'author' => 'William Sharpe', 'note' => 'Klasiskais CAPM: r = rf + β(rm − rf).'],
            ['name' => 'Lintner CAPM', 'year' => 1965, 'author' => 'John Lintner', 'note' => 'Paralēla CAPM atvasinājums ar nedaudz citu pieņēmumu kopu.'],
            ['name' => 'Black zero-beta CAPM', 'year' => 1972, 'author' => 'Fischer Black', 'note' => 'CAPM bez bezriska likmes pieņēmuma.'],
            ['name' => 'Merton ICAPM', 'year' => 1973, 'author' => 'Robert Merton', 'note' => 'Intertemporal CAPM ar state variables.'],
            ['name' => 'Breeden CCAPM', 'year' => 1979, 'author' => 'Douglas Breeden', 'note' => 'Consumption-based CAPM.'],
            ['name' => 'Ross APT', 'year' => 1976, 'author' => 'Stephen Ross', 'note' => 'Arbitrage Pricing Theory — daudzfaktoru pieeja.'],
            ['name' => 'Fama–French 3-factor model', 'year' => 1993, 'author' => 'Fama, French', 'note' => 'Market + SMB (size) + HML (value) faktori.'],
            ['name' => 'Carhart 4-factor model', 'year' => 1997, 'author' => 'Mark Carhart', 'note' => 'FF3 + Momentum (UMD) faktors.'],
            ['name' => 'Fama–French 5-factor model', 'year' => 2015, 'author' => 'Fama, French', 'note' => 'FF3 + Profitability (RMW) + Investment (CMA).'],
            ['name' => 'Pastor–Stambaugh liquidity model', 'year' => 2003, 'author' => 'Pastor, Stambaugh', 'note' => 'Liquidity premium kā 5. faktors.'],
            ['name' => 'Campbell–Cochrane habit model', 'year' => 1999, 'author' => 'Campbell, Cochrane', 'note' => 'Habit-formation patēriņš → laikā mainīgas riska prēmijas.'],
            ['name' => 'Lucas exchange economy model', 'year' => 1978, 'author' => 'Robert Lucas', 'note' => 'Asset-pricing teorētiskais pamats.'],
            ['name' => "Tobin's q model", 'year' => 1969, 'author' => 'James Tobin', 'note' => 'Tirgus vērtības / aizvietošanas izmaksu attiecība.'],
        ],
    ],
    'v-distress-credit' => [
        'title' => 'V. Distress, credit un default modeļi',
        'description' => 'Bankrota varbūtības un kredītriska modeļi.',
        'models' => [
            ['name' => 'Altman Z-score', 'year' => 1968, 'author' => 'Edward Altman', 'note' => 'Multivariate diskriminanta analīze bankrota varbūtībai.'],
            ['name' => 'Ohlson O-score', 'year' => 1980, 'author' => 'James Ohlson', 'note' => 'Logit-based default modelis no 9 finanšu rādītājiem.'],
            ['name' => 'Merton structural credit model', 'year' => 1974, 'author' => 'Robert Merton', 'note' => 'Pašu kapitāls kā call option uz uzņēmuma aktīviem.'],
            ['name' => 'Black–Cox modelis', 'year' => 1976, 'author' => 'Black, Cox', 'note' => 'Strukturāls modelis ar barriers (early default).'],
            ['name' => 'Jarrow–Turnbull modelis', 'year' => 1995, 'author' => 'Jarrow, Turnbull', 'note' => 'Reduced-form ar hazard rate intensitāti.'],
            ['name' => 'Duffie–Singleton modelis', 'year' => 1999, 'author' => 'Duffie, Singleton', 'note' => 'Intensity-based credit pricing ar atgūšanas likmi.'],
        ],
    ],
    'vi-options-derivatives' => [
        'title' => 'VI. Opciju un atvasināto instrumentu modeļi',
        'description' => 'Atvasināto instrumentu cenošanas modeļi.',
        'models' => [
            ['name' => 'Black–Scholes modelis', 'year' => 1973, 'author' => 'Black, Scholes, Merton', 'note' => 'Klasiskā Eiropas opciju cenošanas formula.'],
            ['name' => 'Cox–Ross–Rubinstein binomial model', 'year' => 1979, 'author' => 'Cox, Ross, Rubinstein', 'note' => 'Diskrētā laika binomiālais koks — der amerikāņu opcijām.'],
            ['name' => 'Bachelier modelis', 'year' => 1900, 'author' => 'Louis Bachelier', 'note' => 'Pirmais matemātiskais finanšu modelis ar normālu cenu sadalījumu.'],
            ['name' => 'Heston modelis', 'year' => 1993, 'author' => 'Steven Heston', 'note' => 'Stohastiskā volatilitāte — atrisina volatility smile problēmu.'],
            ['name' => 'Garman–Kohlhagen modelis', 'year' => 1983, 'author' => 'Garman, Kohlhagen', 'note' => 'Black-Scholes pielāgojums valūtas opcijām.'],
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Featured tickers — sub-branches under "Laika rindu prognozēšana"
|--------------------------------------------------------------------------
*/
$featuredTickers = ['AAPL', 'MSFT', 'NVDA', 'AMZN'];

/*
|--------------------------------------------------------------------------
| Authenticated routes — require login
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () use ($valuationCategories, $featuredTickers) {

    Route::get('/', function () use ($valuationCategories, $featuredTickers) {
        // Look up instrument records for featured tickers (IDs differ across environments)
        $tickerInstruments = \App\Models\Instrument::whereIn('ticker', $featuredTickers)
            ->get(['id', 'ticker', 'company_name'])
            ->keyBy('ticker');

        return view('home', [
            'valuationCategories' => $valuationCategories,
            'featuredTickers' => $featuredTickers,
            'tickerInstruments' => $tickerInstruments,
        ]);
    })->name('home');

    Route::get('/instrumenti', [InstrumentController::class, 'index'])->name('instruments.index');
    Route::get('/instrumenti/search', [InstrumentController::class, 'search'])->name('instruments.search');
    Route::get('/instrumenti/filter', [InstrumentController::class, 'filter'])->name('instruments.filter');
    Route::get('/instrument/{instrument}', [InstrumentController::class, 'show'])->name('instruments.show');

    // Indeksi — lapa atstāta kodā, bet šobrīd nepieejama lietotājiem
    // Lai atjaunotu, atkomentē:
    // Route::get('/indeksi', [IndexController::class, 'index'])->name('indexes.index');
    // Route::get('/indeksi/izveidot', [IndexController::class, 'create'])->name('indexes.create');
    // Route::post('/indeksi', [IndexController::class, 'store'])->name('indexes.store');
    // Route::get('/indeksi/{index}', [IndexController::class, 'show'])->name('indexes.show');
    // Route::post('/indeksi/preview', [IndexController::class, 'preview'])->name('indexes.preview');
    // Route::delete('/indeksi/{index}', [IndexController::class, 'destroy'])->name('indexes.destroy');

    Route::get('/portfelis', [PortfolioController::class, 'index'])->name('portfolios.index');
    Route::post('/portfelis', [PortfolioController::class, 'store'])->name('portfolios.store');
    Route::get('/portfelis/{portfolio}', [PortfolioController::class, 'show'])->name('portfolios.show');
    Route::post('/portfelis/{portfolio}/add-instrument', [PortfolioController::class, 'addInstrument'])->name('portfolios.addInstrument');
    Route::post('/portfelis/{portfolio}/sell-instrument/{instrumentId}', [PortfolioController::class, 'sellInstrument'])->name('portfolios.sellInstrument');
    Route::get('/portfelis/{portfolio}/transactions/export', [PortfolioController::class, 'exportTransactions'])->name('portfolios.exportTransactions');
    Route::delete('/portfelis/{portfolio}/remove-instrument/{instrumentId}', [PortfolioController::class, 'removeInstrument'])->name('portfolios.removeInstrument');

    Route::get('/modeli', function () use ($valuationCategories) {
        return view('models', [
            'valuationCategories' => $valuationCategories,
        ]);
    })->name('models.index');

    Route::get('/par-projektu', function () {
        return view('about');
    })->name('about');
});

require __DIR__.'/auth.php';
