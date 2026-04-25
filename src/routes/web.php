<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

$topics = [
    '1-1-regularas' => '1.1. Regulāras',
    '1-2-stohastiskas' => '1.2. Stohastiskas',
    '1-3-haotiskas' => '1.3. Haotiskas',
    '1-4-naudas-piedavajums' => '1.4. P = α*R + γ*H + β*S (α + β + γ = 1)',
    '2-1-trend-linija' => '2.1. Trend līnija',
    '2-2-hp-modelis' => '2.2. HP modelis',
    '2-3-ssa' => '2.3. SSA',
    '3-1-keynes' => '3.1. Keynes',
    '3-2-is-lm' => '3.2. IS-LM',
    '3-3-ret' => '3.3. RET',
    '3-4-dsge-is' => '3.4. DSGE{IS}',
    '4-1-placeholder' => '4.1. (Placeholder)',
];

$categories = [
    'laika-rindas' => [
        'title' => '1. Laika rindas',
        'subtitle' => 'Time Series Analysis',
        'description' => 'Laika rindu modeļu un pieeju apkopojums ar vietu īsam aprakstam, piemēriem un atsaucēm.',
        'topics' => [
            '1-1-regularas',
            '1-2-stohastiskas',
            '1-3-haotiskas',
            '1-4-naudas-piedavajums',
        ],
    ],
    'laika-rindu-prognozesana' => [
        'title' => '2. Laika rindu prognozēšana',
        'subtitle' => 'Time Series Forecasting',
        'description' => 'Prognozēšanas metodes un rīki, ar vietu piemēriem, definīcijām un pielietojumiem.',
        'topics' => [
            '2-1-trend-linija',
            '2-2-hp-modelis',
            '2-3-ssa',
        ],
    ],
    'makroekonomika' => [
        'title' => '3. Makroekonomika',
        'subtitle' => 'Macroeconomics',
        'description' => 'Makroekonomisko modeļu grupējums ar vietu vēsturiskam kontekstam un galvenajām idejām.',
        'topics' => [
            '3-1-keynes',
            '3-2-is-lm',
            '3-3-ret',
            '3-4-dsge-is',
        ],
    ],
    'ekonomikas-izaugsme' => [
        'title' => '4. Ekonomikas izaugsme',
        'subtitle' => 'Economic Growth',
        'description' => 'Izaugsmes modeļu un teoriju kopsavilkums ar vietu galveno jēdzienu skaidrojumiem.',
        'topics' => [
            '4-1-placeholder',
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Authenticated routes — require login
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () use ($topics, $categories) {

    Route::get('/', function () {
        return view('home');
    })->name('home');

    Route::get('/instrumenti', [InstrumentController::class, 'index'])->name('instruments.index');
    Route::get('/instrumenti/search', [InstrumentController::class, 'search'])->name('instruments.search');
    Route::get('/instrumenti/filter', [InstrumentController::class, 'filter'])->name('instruments.filter');
    Route::get('/instrument/{instrument}', [InstrumentController::class, 'show'])->name('instruments.show');

    Route::get('/indeksi', [IndexController::class, 'index'])->name('indexes.index');
    Route::get('/indeksi/izveidot', [IndexController::class, 'create'])->name('indexes.create');
    Route::post('/indeksi', [IndexController::class, 'store'])->name('indexes.store');
    Route::get('/indeksi/{index}', [IndexController::class, 'show'])->name('indexes.show');
    Route::post('/indeksi/preview', [IndexController::class, 'preview'])->name('indexes.preview');
    Route::delete('/indeksi/{index}', [IndexController::class, 'destroy'])->name('indexes.destroy');

    Route::get('/portfelis', [PortfolioController::class, 'index'])->name('portfolios.index');
    Route::post('/portfelis', [PortfolioController::class, 'store'])->name('portfolios.store');
    Route::get('/portfelis/{portfolio}', [PortfolioController::class, 'show'])->name('portfolios.show');
    Route::post('/portfelis/{portfolio}/add-instrument', [PortfolioController::class, 'addInstrument'])->name('portfolios.addInstrument');
    Route::post('/portfelis/{portfolio}/sell-instrument/{instrumentId}', [PortfolioController::class, 'sellInstrument'])->name('portfolios.sellInstrument');
    Route::delete('/portfelis/{portfolio}/remove-instrument/{instrumentId}', [PortfolioController::class, 'removeInstrument'])->name('portfolios.removeInstrument');

    Route::get('/models', function () use ($topics) {
        return view('models', [
            'topics' => $topics,
        ]);
    })->name('models.index');

    Route::get('/par-projektu', function () {
        return view('about');
    })->name('about');

    Route::get('/kategorijas/{slug}', function (string $slug) use ($categories, $topics) {
        if (!array_key_exists($slug, $categories)) {
            abort(404);
        }

        $category = $categories[$slug];
        $subtopics = array_intersect_key($topics, array_flip($category['topics']));

        return view('category', [
            'category' => $category,
            'subtopics' => $subtopics,
        ]);
    })->name('category.show');

    Route::get('/temas/{slug}', function (string $slug) use ($topics) {
        if (!array_key_exists($slug, $topics)) {
            abort(404);
        }

        return view('topic', [
            'title' => $topics[$slug],
            'slug' => $slug,
        ]);
    })->name('topic.show');
});

require __DIR__.'/auth.php';
