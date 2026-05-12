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
                                data-needs-topn="{{ str_ends_with($s->key(), '_top_n') ? '1' : '0' }}"
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

            {{-- Base date + capital --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Bāzes datums</label>
                    <input type="date" name="base_date" id="base-date" required
                           min="2018-04-01" max="{{ now()->toDateString() }}"
                           value="{{ old('base_date', '2021-04-01') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    <p class="text-[11px] text-gray-500 mt-1">Datums, kad simulētie pirkumi tiek veikti. <strong>Tikai 2018-04-01 vai vēlāk</strong> (SimFin sākas no 2018).</p>
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
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Atlasīto akciju saraksts</h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 uppercase">#</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 uppercase">Ticker</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 uppercase">Uzņēmums</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 uppercase">Score</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 uppercase">Svars</th>
                                </tr>
                            </thead>
                            <tbody id="preview-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
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
        invalidatePreview();
    }
    select.addEventListener('change', updateStrategy);
    updateStrategy();

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
            // Render table
            previewTbody.innerHTML = '';
            data.picks.forEach((p, i) => {
                const tr = document.createElement('tr');
                const scoreText = p.score !== null ? Number(p.score).toFixed(4) : '—';
                tr.innerHTML = `
                    <td class="px-3 py-1.5 text-gray-500">${i + 1}</td>
                    <td class="px-3 py-1.5 font-semibold text-gray-900">${p.ticker}</td>
                    <td class="px-3 py-1.5 text-gray-600 truncate max-w-xs">${p.company_name || ''}</td>
                    <td class="px-3 py-1.5 text-right tabular-nums text-gray-800">${scoreText}</td>
                    <td class="px-3 py-1.5 text-right tabular-nums text-gray-600">${(p.weight * 100).toFixed(2)}%</td>
                `;
                previewTbody.appendChild(tr);
            });
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
})();
</script>
@endsection
