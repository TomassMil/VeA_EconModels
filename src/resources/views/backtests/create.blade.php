@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <a href="{{ route('portfolios.index') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 mb-4">
            ← Atpakaļ uz Portfeļiem
        </a>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Backtest wizard</h1>
            <p class="text-gray-600 mt-1">
                1. Konfigurē stratēģiju → 2. Atlasi un priekšskati akcijas → 3. Izveido portfeli.
            </p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <p class="text-sm font-semibold text-red-700 mb-2">Kļūdas:</p>
                <ul class="text-sm text-red-600 list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('backtests.store') }}" method="POST" id="backtest-form" class="space-y-5">
            @csrf

            {{-- Strategy selector --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Stratēģija</label>
                <select name="strategy" id="strategy-select" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    @foreach ($strategies as $s)
                        <option value="{{ $s->key() }}"
                                data-description="{{ $s->description() }}"
                                data-needs-tickers="{{ $s->key() === 'equal_weight_buy_hold' ? '1' : '0' }}"
                                data-needs-topn="{{ str_ends_with($s->key(), '_top_n') || $s->key() === 'custom_formula' ? '1' : '0' }}"
                                data-needs-formula="{{ $s->key() === 'custom_formula' ? '1' : '0' }}"
                                {{ old('strategy') === $s->key() ? 'selected' : '' }}>
                            {{ $s->name() }}
                        </option>
                    @endforeach
                </select>
                <p id="strategy-description" class="text-xs text-gray-500 mt-2"></p>
            </div>

            {{-- Portfolio name + description --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Portfeļa nosaukums</label>
                    <input type="text" name="name" required maxlength="100" value="{{ old('name') }}"
                           placeholder="Piem.: Graham Top-20 (2020 bāze)"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Apraksts (neobligāti)</label>
                    <textarea name="description" maxlength="500" rows="2"
                              placeholder="Īss apraksts par šo backtestu"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Walk-forward explanation --}}
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-gray-700">
                <p class="font-semibold text-gray-800 mb-1.5 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    Walk-forward backtest princips
                </p>
                <p class="leading-relaxed">
                    Stratēģija izvēlas akcijas <strong>pirms</strong> bāzes datuma (optimizācijas periods, izmantojot fundamentālos datus no FY pirms bāzes gada), un tad <strong>tur tās pēc bāzes datuma</strong> līdz šim brīdim (testēšanas periods). Bāzes datums = robeža starp diviem periodiem.
                </p>
            </div>

            {{-- Base date + capital --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <span class="inline-block w-2 h-2 bg-purple-500 rounded-full mr-1.5 align-middle"></span>
                        Bāzes datums (testēšanas sākums)
                    </label>
                    <input type="date" name="base_date" id="base-date" required
                           min="2018-04-01" max="{{ now()->toDateString() }}"
                           value="{{ old('base_date', '2021-04-01') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    <p class="text-[11px] text-gray-500 mt-1">
                        Optimizācijas dati no FY pirms šī datuma. Testēšana no šī datuma līdz šim brīdim.
                        <strong>Min 2018-04-01</strong> (SimFin sākas 2018).
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sākuma kapitāls ($)</label>
                    <input type="number" name="capital" required min="100" step="100"
                           value="{{ old('capital', 10000) }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
            </div>

            {{-- Strategy-specific params --}}
            <div id="param-top-n" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Top N akcijas</label>
                <input type="number" name="top_n" id="top-n-input" min="1" max="100" value="{{ old('top_n', 20) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                <p class="text-[11px] text-gray-500 mt-1">Cik akcijas ar augstāko score iekļaut portfelī (vienlīdzīgi sadalīts)</p>
            </div>

            <div id="param-tickers" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Instrumenti</label>
                <div class="relative mb-2">
                    <input type="text" id="ticker-search" placeholder="Meklēt ticker vai uzņēmuma nosaukumu..."
                           autocomplete="off"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    <div id="ticker-search-results" class="absolute z-20 hidden bg-white border border-gray-200 rounded-lg shadow-lg w-full mt-1 max-h-60 overflow-y-auto"></div>
                </div>
                <div id="ticker-chips" class="flex flex-wrap gap-2 min-h-[2rem] mb-1"></div>
                <input type="hidden" name="instrument_tickers" id="instrument-tickers" value="{{ old('instrument_tickers') }}">
                <p class="text-[11px] text-gray-500 mt-1">Meklē un klikšķini, lai pievienotu. Kapitāls tiks sadalīts vienādās daļās.</p>
            </div>

            {{-- Custom formula param --}}
            <div id="param-formula" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hidden space-y-3">
                {{-- Saglabāto formulu dropdown --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Saglabātās formulas</label>
                    <div class="flex gap-2">
                        <select id="saved-formula-select" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="">— jauna formula —</option>
                            @foreach ($savedFormulas as $f)
                                <option value="{{ $f->id }}"
                                        data-formula="{{ $f->formula }}"
                                        data-top-n="{{ $f->top_n }}"
                                        data-description="{{ $f->description }}">
                                    {{ $f->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" id="delete-formula-btn" class="hidden rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 transition-colors" title="Dzēst izvēlēto formulu">
                            🗑
                        </button>
                    </div>
                </div>

                {{-- Cheatsheet --}}
                <details class="bg-gradient-to-r from-purple-50 to-blue-50 border border-blue-200 rounded-lg">
                    <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-blue-50">
                        📖 Pieejamie mainīgie ({{ count($formulaVariables) }}) un funkcijas ({{ count($formulaFunctions) }})
                    </summary>
                    <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1.5">Mainīgie</p>
                            <ul class="space-y-0.5">
                                @foreach ($formulaVariables as $v => $desc)
                                    <li>
                                        <code class="text-blue-700 font-mono">{{ $v }}</code>
                                        <span class="text-gray-600"> — {{ $desc }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700 mb-1.5">Funkcijas + operatori</p>
                            <ul class="space-y-0.5">
                                @foreach ($formulaFunctions as $fn => $desc)
                                    <li>
                                        <code class="text-blue-700 font-mono">{{ $fn }}</code>
                                        <span class="text-gray-600"> — {{ $desc }}</span>
                                    </li>
                                @endforeach
                                <li class="pt-1 text-gray-500">
                                    Operatori: <code class="text-blue-700">+ − * / %</code> un iekavas <code class="text-blue-700">( )</code>
                                </li>
                            </ul>
                        </div>
                    </div>
                </details>

                {{-- Formula textarea --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Formula</label>
                    <textarea name="formula" id="formula-input" rows="3"
                              placeholder="Piem.: roe + gross_margin * 0.5 - debt_to_equity"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ old('formula') }}</textarea>
                    <div class="flex items-center justify-between mt-1.5">
                        <p id="formula-validation" class="text-[11px] text-gray-500">Formula tiks validēta, kad spied "Atlasīt akcijas"</p>
                        <button type="button" id="validate-formula-btn" class="text-[11px] text-blue-600 hover:text-blue-700 font-medium">✓ Validēt</button>
                    </div>
                </div>

                {{-- Save formula --}}
                <div class="flex gap-2 items-end pt-2 border-t border-gray-100">
                    <div class="flex-1">
                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Saglabāt kā:</label>
                        <input type="text" id="formula-save-name" placeholder="Nosaukums..." maxlength="100"
                               class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    </div>
                    <button type="button" id="save-formula-btn" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition-colors">
                        💾 Saglabāt
                    </button>
                </div>
                <p id="formula-save-msg" class="text-[11px] hidden"></p>
            </div>

            {{-- Preview button + result area --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <button type="button" id="preview-btn"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2 text-sm font-medium text-white hover:bg-amber-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    1. Atlasīt akcijas
                </button>

                <div id="preview-result" class="mt-4 hidden">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-800">Atlasīto akciju saraksts</h3>
                        <button type="button" id="preview-equal-weight-btn" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                            ⚖ Sadalīt vienlīdzīgi
                        </button>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 uppercase">#</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 uppercase">Ticker</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 uppercase">Uzņēmums</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 uppercase">Score</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 uppercase w-24">Svars %</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 uppercase w-28">Summa $</th>
                                </tr>
                            </thead>
                            <tbody id="preview-tbody" class="divide-y divide-gray-100"></tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-3 py-2 text-xs text-right font-semibold text-gray-700">Kopā:</td>
                                    <td class="px-3 py-2 text-right text-xs font-bold tabular-nums"><span id="preview-total-weight">100.0</span>%</td>
                                    <td class="px-3 py-2 text-right text-xs font-bold tabular-nums">$<span id="preview-total-amount">0.00</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <input type="hidden" name="custom_weights" id="custom-weights-input" value="">
                </div>

                <div id="preview-loader" class="mt-4 hidden">
                    <div class="inline-flex items-center gap-2 text-sm text-gray-500">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Aprēķinu score un atlasu akcijas...
                    </div>
                </div>

                <p id="preview-error" class="text-sm text-red-600 mt-3 hidden"></p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('portfolios.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Atcelt
                </a>
                <button type="submit" id="submit-btn" disabled
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg id="submit-spinner" class="animate-spin h-4 w-4 hidden" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span id="submit-text">2. Izveidot portfeli</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const csrfToken = '{{ csrf_token() }}';
    const select = document.getElementById('strategy-select');
    const descEl = document.getElementById('strategy-description');
    const paramTopN = document.getElementById('param-top-n');
    const paramTickers = document.getElementById('param-tickers');
    const paramFormula = document.getElementById('param-formula');
    const formulaInput = document.getElementById('formula-input');
    const previewBtn = document.getElementById('preview-btn');
    const previewResult = document.getElementById('preview-result');
    const previewTbody = document.getElementById('preview-tbody');
    const previewLoader = document.getElementById('preview-loader');
    const previewError = document.getElementById('preview-error');
    const submitBtn = document.getElementById('submit-btn');
    const baseDate = document.getElementById('base-date');
    const topNInput = document.getElementById('top-n-input');
    const tickersHidden = document.getElementById('instrument-tickers');

    // ─── Strategy switch ───
    function updateStrategy() {
        const opt = select.options[select.selectedIndex];
        descEl.textContent = opt.dataset.description || '';
        paramTopN.classList.toggle('hidden', opt.dataset.needsTopn !== '1');
        paramTickers.classList.toggle('hidden', opt.dataset.needsTickers !== '1');
        paramFormula.classList.toggle('hidden', opt.dataset.needsFormula !== '1');
        invalidatePreview();
    }
    select.addEventListener('change', updateStrategy);
    updateStrategy();

    /* ─── Custom formula UI ─── */
    const savedFormulaSelect = document.getElementById('saved-formula-select');
    const deleteFormulaBtn = document.getElementById('delete-formula-btn');
    const formulaValidation = document.getElementById('formula-validation');
    const validateFormulaBtn = document.getElementById('validate-formula-btn');
    const saveFormulaBtn = document.getElementById('save-formula-btn');
    const formulaSaveName = document.getElementById('formula-save-name');
    const formulaSaveMsg = document.getElementById('formula-save-msg');

    // Load saved formula into textarea + top_n
    savedFormulaSelect.addEventListener('change', () => {
        const opt = savedFormulaSelect.options[savedFormulaSelect.selectedIndex];
        if (opt.value) {
            formulaInput.value = opt.dataset.formula || '';
            if (opt.dataset.topN) topNInput.value = opt.dataset.topN;
            formulaSaveName.value = opt.textContent;
            deleteFormulaBtn.classList.remove('hidden');
        } else {
            deleteFormulaBtn.classList.add('hidden');
        }
        invalidatePreview();
    });

    // Validate formula (AJAX)
    function validateFormulaInput() {
        const formula = formulaInput.value.trim();
        if (!formula) {
            formulaValidation.textContent = 'Formula tukša';
            formulaValidation.className = 'text-[11px] text-gray-500';
            return;
        }
        const fd = new FormData();
        fd.append('formula', formula);
        fetch('{{ route("formulas.validate") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: fd,
        })
        .then(r => r.json())
        .then(json => {
            if (json.valid) {
                formulaValidation.textContent = '✓ Formula derīga (testa rezultāts ar vērtībām=1: ' + Number(json.sample_result).toFixed(4) + ')';
                formulaValidation.className = 'text-[11px] text-emerald-600';
            } else {
                formulaValidation.textContent = '✗ ' + (json.error || 'Nederīga');
                formulaValidation.className = 'text-[11px] text-red-600';
            }
        });
    }
    validateFormulaBtn.addEventListener('click', validateFormulaInput);
    formulaInput.addEventListener('input', () => {
        invalidatePreview();
        formulaValidation.textContent = 'Formula nav validēta';
        formulaValidation.className = 'text-[11px] text-gray-500';
    });

    // Save formula
    saveFormulaBtn.addEventListener('click', () => {
        const name = formulaSaveName.value.trim();
        const formula = formulaInput.value.trim();
        if (!name) {
            formulaSaveMsg.textContent = 'Ievadi nosaukumu';
            formulaSaveMsg.className = 'text-[11px] text-red-600';
            formulaSaveMsg.classList.remove('hidden');
            return;
        }
        if (!formula) {
            formulaSaveMsg.textContent = 'Formula tukša';
            formulaSaveMsg.className = 'text-[11px] text-red-600';
            formulaSaveMsg.classList.remove('hidden');
            return;
        }
        const fd = new FormData();
        fd.append('name', name);
        fd.append('formula', formula);
        fd.append('top_n', topNInput.value || '20');
        fetch('{{ route("formulas.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: fd,
        })
        .then(async r => ({ ok: r.ok, json: await r.json() }))
        .then(({ ok, json }) => {
            if (!ok) {
                formulaSaveMsg.textContent = json.error || 'Saglabāšana neizdevās';
                formulaSaveMsg.className = 'text-[11px] text-red-600';
                formulaSaveMsg.classList.remove('hidden');
                return;
            }
            // Update dropdown — add option if new, or just notify
            const existing = Array.from(savedFormulaSelect.options).find(o => o.textContent === name);
            if (!existing) {
                const opt = document.createElement('option');
                opt.value = json.data.id;
                opt.textContent = json.data.name;
                opt.dataset.formula = json.data.formula;
                opt.dataset.topN = json.data.top_n;
                savedFormulaSelect.appendChild(opt);
            }
            formulaSaveMsg.textContent = '✓ Saglabāts: ' + name;
            formulaSaveMsg.className = 'text-[11px] text-emerald-600';
            formulaSaveMsg.classList.remove('hidden');
        });
    });

    // Delete saved formula
    deleteFormulaBtn.addEventListener('click', () => {
        const id = savedFormulaSelect.value;
        if (!id) return;
        if (!confirm('Dzēst formulu "' + savedFormulaSelect.options[savedFormulaSelect.selectedIndex].textContent + '"?')) return;

        fetch(`/formulas/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                savedFormulaSelect.options[savedFormulaSelect.selectedIndex].remove();
                savedFormulaSelect.value = '';
                deleteFormulaBtn.classList.add('hidden');
                formulaSaveMsg.textContent = '✓ Izdzēsts';
                formulaSaveMsg.className = 'text-[11px] text-emerald-600';
                formulaSaveMsg.classList.remove('hidden');
            }
        });
    });

    // Invalidate the preview when any input changes
    function invalidatePreview() {
        previewResult.classList.add('hidden');
        submitBtn.disabled = true;
        previewError.classList.add('hidden');
    }
    baseDate.addEventListener('change', invalidatePreview);
    topNInput.addEventListener('change', invalidatePreview);

    // ─── Ticker autocomplete (chips) ───
    const tickerSearch = document.getElementById('ticker-search');
    const tickerResults = document.getElementById('ticker-search-results');
    const tickerChips = document.getElementById('ticker-chips');
    let selectedTickers = new Set();

    // Initialize from old() value
    if (tickersHidden.value) {
        tickersHidden.value.split(',').map(t => t.trim().toUpperCase()).filter(Boolean).forEach(t => selectedTickers.add(t));
        renderChips();
    }

    function renderChips() {
        tickerChips.innerHTML = '';
        selectedTickers.forEach(ticker => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 px-2.5 py-1 text-xs font-medium text-blue-700';
            chip.innerHTML = `${ticker}<button type="button" class="text-blue-400 hover:text-blue-700" data-remove="${ticker}">×</button>`;
            tickerChips.appendChild(chip);
        });
        tickersHidden.value = Array.from(selectedTickers).join(',');
        invalidatePreview();
    }

    tickerChips.addEventListener('click', e => {
        const t = e.target.dataset?.remove;
        if (t) {
            selectedTickers.delete(t);
            renderChips();
        }
    });

    let searchTimer = null;
    tickerSearch.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = tickerSearch.value.trim();
        if (q.length < 1) {
            tickerResults.classList.add('hidden');
            return;
        }
        searchTimer = setTimeout(() => {
            fetch(`/fundamentali/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(resp => {
                    const items = resp.data || resp || [];
                    tickerResults.innerHTML = '';
                    if (!items.length) {
                        tickerResults.classList.add('hidden');
                        return;
                    }
                    items.slice(0, 8).forEach(item => {
                        const ticker = (item.ticker || '').toUpperCase();
                        if (!ticker) return;
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = 'block w-full text-left px-3 py-2 hover:bg-blue-50 text-sm';
                        row.innerHTML = `<span class="font-semibold text-gray-900">${ticker}</span> <span class="text-gray-500 text-xs">${item.company_name || ''}</span>`;
                        row.addEventListener('click', () => {
                            selectedTickers.add(ticker);
                            tickerSearch.value = '';
                            tickerResults.classList.add('hidden');
                            renderChips();
                        });
                        tickerResults.appendChild(row);
                    });
                    tickerResults.classList.remove('hidden');
                });
        }, 200);
    });

    document.addEventListener('click', e => {
        if (!tickerSearch.contains(e.target) && !tickerResults.contains(e.target)) {
            tickerResults.classList.add('hidden');
        }
    });

    // ─── Submit (form action POST /backtests) ───
    const form = document.getElementById('backtest-form');
    const submitSpinner = document.getElementById('submit-spinner');
    const submitText = document.getElementById('submit-text');
    form.addEventListener('submit', () => {
        // Show spinner immediately so user knows it's working (server-side can take 2-5s)
        submitBtn.disabled = true;
        submitSpinner.classList.remove('hidden');
        submitText.textContent = 'Izveidoju portfeli...';
    });

    // ─── Preview step ───
    previewBtn.addEventListener('click', () => {
        previewError.classList.add('hidden');
        previewResult.classList.add('hidden');
        previewLoader.classList.remove('hidden');
        previewBtn.disabled = true;

        const formData = new FormData();
        formData.append('strategy', select.value);
        formData.append('base_date', baseDate.value);
        formData.append('top_n', topNInput.value || '');
        formData.append('instrument_tickers', tickersHidden.value || '');
        formData.append('formula', formulaInput.value || '');

        fetch('{{ route("backtests.preview") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData,
        })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(({ ok, data }) => {
            previewLoader.classList.add('hidden');
            previewBtn.disabled = false;
            if (!ok) {
                previewError.textContent = data.error || 'Atlase neizdevās.';
                previewError.classList.remove('hidden');
                return;
            }
            renderPreviewPicks(data.picks);
            previewResult.classList.remove('hidden');
            submitBtn.disabled = false;
        })
        .catch(err => {
            previewLoader.classList.add('hidden');
            previewBtn.disabled = false;
            previewError.textContent = 'Tīkla kļūda: ' + err.message;
            previewError.classList.remove('hidden');
        });
    });

    /* ─── Preview picks editing: weight ↔ amount two-way binding + custom_weights submit ─── */
    const previewTotalWeight = document.getElementById('preview-total-weight');
    const previewTotalAmount = document.getElementById('preview-total-amount');
    const previewEqualWeightBtn = document.getElementById('preview-equal-weight-btn');
    const customWeightsInput = document.getElementById('custom-weights-input');
    const capitalInput = document.querySelector('input[name="capital"]');

    let previewPicks = [];    // [{instrument_id, ticker, company_name, score, weight (0-1), amount}]

    function getPreviewCapital() {
        return Math.max(0, parseFloat(capitalInput.value) || 0);
    }

    function renderPreviewPicks(picks) {
        previewPicks = picks.map(p => ({
            instrument_id: p.instrument_id || null,        // backend may need to provide this
            ticker: p.ticker,
            company_name: p.company_name,
            score: p.score,
            weight: p.weight,
            amount: p.weight * getPreviewCapital(),
        }));
        previewTbody.innerHTML = '';
        previewPicks.forEach((p, i) => {
            const tr = document.createElement('tr');
            const scoreText = p.score !== null ? Number(p.score).toFixed(4) : '—';
            tr.innerHTML = `
                <td class="px-3 py-1.5 text-gray-500">${i + 1}</td>
                <td class="px-3 py-1.5 font-semibold text-gray-900">${p.ticker}</td>
                <td class="px-3 py-1.5 text-gray-600 truncate max-w-xs">${p.company_name || ''}</td>
                <td class="px-3 py-1.5 text-right tabular-nums text-gray-800">${scoreText}</td>
                <td class="px-3 py-1.5">
                    <input type="number" data-idx="${i}" data-field="weight" step="any" min="0" max="100" value="${(p.weight * 100).toFixed(2)}"
                           class="w-full rounded border border-gray-300 px-2 py-1 text-xs text-right tabular-nums focus:border-blue-500 outline-none">
                </td>
                <td class="px-3 py-1.5">
                    <input type="number" data-idx="${i}" data-field="amount" step="any" min="0" value="${p.amount.toFixed(2)}"
                           class="w-full rounded border border-gray-300 px-2 py-1 text-xs text-right tabular-nums focus:border-blue-500 outline-none">
                </td>
            `;
            previewTbody.appendChild(tr);
        });
        updatePreviewTotals();
    }

    function updatePreviewTotals() {
        const capital = getPreviewCapital();
        const totalAmount = previewPicks.reduce((s, p) => s + p.amount, 0);
        const totalWeight = previewPicks.reduce((s, p) => s + p.weight, 0) * 100;

        previewTotalWeight.textContent = totalWeight.toFixed(1);
        previewTotalAmount.textContent = totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const overspent = totalAmount > capital + 0.01;
        previewTotalWeight.classList.toggle('text-red-600', totalWeight > 100.5);
        previewTotalAmount.classList.toggle('text-red-600', overspent);

        // Update hidden input with custom weights array
        const customWeights = previewPicks
            .filter(p => p.weight > 0 && p.instrument_id)
            .map(p => ({ instrument_id: p.instrument_id, weight: p.weight }));
        customWeightsInput.value = JSON.stringify(customWeights);
    }

    previewTbody.addEventListener('input', e => {
        const idx = parseInt(e.target.dataset.idx);
        const field = e.target.dataset.field;
        if (isNaN(idx) || !field) return;

        const val = parseFloat(e.target.value) || 0;
        const capital = getPreviewCapital();

        if (field === 'weight') {
            previewPicks[idx].weight = val / 100;
            previewPicks[idx].amount = (val / 100) * capital;
            const amountInput = previewTbody.querySelector(`input[data-idx="${idx}"][data-field="amount"]`);
            if (amountInput) amountInput.value = previewPicks[idx].amount.toFixed(2);
        } else if (field === 'amount') {
            previewPicks[idx].amount = val;
            previewPicks[idx].weight = capital > 0 ? val / capital : 0;
            const weightInput = previewTbody.querySelector(`input[data-idx="${idx}"][data-field="weight"]`);
            if (weightInput) weightInput.value = (previewPicks[idx].weight * 100).toFixed(2);
        }
        updatePreviewTotals();
    });

    previewEqualWeightBtn.addEventListener('click', () => {
        if (previewPicks.length === 0) return;
        const w = 1 / previewPicks.length;
        const capital = getPreviewCapital();
        previewPicks.forEach(p => {
            p.weight = w;
            p.amount = w * capital;
        });
        renderPreviewPicks(previewPicks.map(p => ({ ...p, weight: w })));
    });

    // Recalc amounts when capital changes
    capitalInput.addEventListener('input', () => {
        const capital = getPreviewCapital();
        previewPicks.forEach(p => p.amount = p.weight * capital);
        // Update amount inputs in DOM
        previewPicks.forEach((p, idx) => {
            const ai = previewTbody.querySelector(`input[data-idx="${idx}"][data-field="amount"]`);
            if (ai) ai.value = p.amount.toFixed(2);
        });
        updatePreviewTotals();
    });
})();
</script>
@endsection
