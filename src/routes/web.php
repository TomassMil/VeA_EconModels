<?php

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

Route::get('/', function () {
    return view('home');
});

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
