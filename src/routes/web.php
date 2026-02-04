<?php

use Illuminate\Support\Facades\Route;

$topics = [
    '1-1-regularas' => '1.1. Regulāras',
    '1-2-stohastiskas' => '1.2. Stohastiskas',
    '1-3-haotiskas' => '1.3. Haotiskas',
    '1-4-naudas-piedavajums' => '1.4. P = d*R + y*H + B*s (d + B + y = 1)',
    '2-1-trend-linija' => '2.1. Trend līnija',
    '2-2-hp-modelis' => '2.2. HP modelis',
    '2-3-ssa' => '2.3. SSA',
    '3-1-keynes' => '3.1. Keynes',
    '3-2-is-lm' => '3.2. IS-LM',
    '3-3-ret' => '3.3. RET',
    '3-4-dsge-is' => '3.4. DSGE{IS}',
    '4-1-placeholder' => '4.1. (Placeholder)',
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

Route::get('/temas/{slug}', function (string $slug) use ($topics) {
    if (!array_key_exists($slug, $topics)) {
        abort(404);
    }

    return view('topic', [
        'title' => $topics[$slug],
        'slug' => $slug,
    ]);
})->name('topic.show');
