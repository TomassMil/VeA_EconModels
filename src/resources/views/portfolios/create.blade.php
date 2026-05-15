@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <a href="{{ route('portfolios.index') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 mb-4">
            ← Atpakaļ uz Portfeļiem
        </a>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Izveidot jaunu portfeli</h1>
            <p class="text-gray-600 mt-1">Manuāli izvēlies instrumentus, summas un datumus. Svari un summas auto-aprēķinās.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <ul class="text-sm text-red-600 list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('portfolios.storeManual') }}" method="POST" id="portfolio-form" class="space-y-5">
            @csrf

            {{-- Pamatdati --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Pamatdati</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Nosaukums *</label>
                        <input type="text" name="name" required maxlength="100" value="{{ old('name') }}"
                               placeholder="Piem.: A_260514_01"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Sākuma kapitāls ($) *</label>
                        <input type="number" name="capital" id="capital-input" required min="100" step="any"
                               value="{{ old('capital', 10000) }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bāzes datums *</label>
                        <input type="date" id="base-date-input" required
                               min="{{ $earliestDataDate ?? '2014-01-01' }}" max="{{ $latestDataDate ?? now()->toDateString() }}"
                               value="{{ old('base_date', '2023-01-03') }}"
                               class="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
                        <p class="text-[10px] text-amber-700 mt-1">Default visiem pirkumiem. Var mainīt katram atsevišķi tabulā.</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Apraksts (neobligāti)</label>
                    <textarea name="description" maxlength="500" rows="2"
                              placeholder="Piem.: Annas atlasītie tehnoloģiju gigantu portfelis"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Instruments selection --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-700">Instrumenti</h2>
                    <button type="button" id="equal-weight-btn"
                            class="text-xs text-blue-600 hover:text-blue-700 font-medium hidden">
                        ⚖ Sadalīt vienlīdzīgi
                    </button>
                </div>

                <div class="relative mb-2">
                    <input type="text" id="ticker-search" placeholder="🔍 Meklēt vai ievadīt sarakstu: AAPL, MSFT, NVDA (Enter)"
                           autocomplete="off"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    <div id="ticker-search-results" class="absolute z-20 hidden bg-white border border-gray-200 rounded-lg shadow-lg w-full mt-1 max-h-60 overflow-y-auto"></div>
                </div>
                <p class="text-[11px] text-gray-500 mb-3">
                    💡 Tips: ieraksti vairākus atdalītus ar komatu un nospied <kbd class="px-1 bg-gray-100 border border-gray-300 rounded">Enter</kbd>.
                    Ar svariem: <code class="text-blue-700">AAPL 20%, MSFT 15%, NVDA</code> — atlikušais sadalīsies vienlīdzīgi
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-[10px] font-semibold text-gray-600 uppercase">Ticker</th>
                                <th class="px-2 py-2 text-left text-[10px] font-semibold text-gray-600 uppercase">Uzņēmums</th>
                                <th class="px-2 py-2 text-right text-[10px] font-semibold text-gray-600 uppercase w-24">Svars %</th>
                                <th class="px-2 py-2 text-right text-[10px] font-semibold text-gray-600 uppercase w-28">Summa $</th>
                                <th class="px-2 py-2 text-left text-[10px] font-semibold text-gray-600 uppercase w-36">Datums</th>
                                <th class="px-2 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="picks-tbody" class="divide-y divide-gray-100">
                            <tr id="picks-empty">
                                <td colspan="6" class="text-center text-xs text-gray-400 py-8">
                                    Sāc meklēt instrumentu, lai pievienotu pirmo pirkumu
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-2 py-2 text-xs font-semibold text-gray-700 text-right">Kopā:</td>
                                <td class="px-2 py-2 text-right text-xs font-bold tabular-nums">
                                    <span id="total-weight">0.0</span>%
                                </td>
                                <td class="px-2 py-2 text-right text-xs font-bold tabular-nums">
                                    $<span id="total-amount">0.00</span>
                                </td>
                                <td colspan="2" class="px-2 py-2 text-[10px] text-gray-500">
                                    Atlikušais kapitāls: $<span id="remaining-capital">10,000.00</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p id="picks-validation" class="text-xs mt-3 hidden"></p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('portfolios.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Atcelt
                </a>
                <button type="submit" id="submit-btn" disabled
                        class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Izveidot portfeli
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const csrfToken = '{{ csrf_token() }}';
    const dataMinDate = '{{ $earliestDataDate ?? "2014-01-01" }}';
    const dataMaxDate = '{{ $latestDataDate ?? now()->toDateString() }}';

    const capitalInput = document.getElementById('capital-input');
    const baseDateInput = document.getElementById('base-date-input');
    const tickerSearch = document.getElementById('ticker-search');
    const tickerResults = document.getElementById('ticker-search-results');
    const picksTbody = document.getElementById('picks-tbody');
    const picksEmpty = document.getElementById('picks-empty');
    const totalWeightEl = document.getElementById('total-weight');
    const totalAmountEl = document.getElementById('total-amount');
    const remainingCapitalEl = document.getElementById('remaining-capital');
    const submitBtn = document.getElementById('submit-btn');
    const equalWeightBtn = document.getElementById('equal-weight-btn');
    const validationEl = document.getElementById('picks-validation');

    let picks = [];     // [{ticker, company_name, weight, amount, date}]

    function getCapital() {
        return Math.max(0, parseFloat(capitalInput.value) || 0);
    }

    function fmt(n) {
        return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function render() {
        picksEmpty.classList.toggle('hidden', picks.length > 0);

        // Remove all rows except empty placeholder
        Array.from(picksTbody.querySelectorAll('tr:not(#picks-empty)')).forEach(r => r.remove());

        picks.forEach((p, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-2 py-2 font-semibold text-gray-900">${p.ticker}</td>
                <td class="px-2 py-2 text-xs text-gray-600 truncate max-w-[200px]">${p.company_name || ''}</td>
                <td class="px-2 py-2">
                    <input type="number" data-idx="${idx}" data-field="weight" step="any" min="0" max="100" value="${p.weight.toFixed(2)}"
                           class="w-full rounded border border-gray-300 px-2 py-1 text-xs text-right tabular-nums focus:border-blue-500 outline-none">
                </td>
                <td class="px-2 py-2">
                    <input type="number" data-idx="${idx}" data-field="amount" step="any" min="0" value="${p.amount.toFixed(2)}"
                           class="w-full rounded border border-gray-300 px-2 py-1 text-xs text-right tabular-nums focus:border-blue-500 outline-none">
                </td>
                <td class="px-2 py-2">
                    <input type="date" data-idx="${idx}" data-field="date" min="${dataMinDate}" max="${dataMaxDate}" value="${p.date}"
                           class="w-full rounded border border-gray-300 px-2 py-1 text-xs focus:border-blue-500 outline-none">
                </td>
                <td class="px-2 py-2 text-center">
                    <button type="button" data-idx="${idx}" data-action="remove" class="text-red-500 hover:text-red-700 text-lg leading-none" title="Noņemt">×</button>
                </td>
            `;
            picksTbody.appendChild(tr);
        });

        renderTotals();
        renderHiddenInputs();
        equalWeightBtn.classList.toggle('hidden', picks.length === 0);
    }

    function renderTotals() {
        const totalAmount = picks.reduce((s, p) => s + p.amount, 0);
        const capital = getCapital();
        const totalWeight = picks.reduce((s, p) => s + p.weight, 0);

        totalWeightEl.textContent = totalWeight.toFixed(1);
        totalAmountEl.textContent = fmt(totalAmount);
        remainingCapitalEl.textContent = fmt(capital - totalAmount);

        const overspent = totalAmount > capital + 0.01;
        const overweight = totalWeight > 100.5;
        totalAmountEl.classList.toggle('text-red-600', overspent);
        totalWeightEl.classList.toggle('text-red-600', overweight);
        totalAmountEl.classList.toggle('text-emerald-600', !overspent && totalAmount > 0);
        totalWeightEl.classList.toggle('text-emerald-600', !overweight && totalWeight > 0);

        let valid = picks.length > 0 && !overspent && totalAmount > 0;
        // All picks need date + non-zero amount
        for (const p of picks) {
            if (!p.date || p.amount <= 0) { valid = false; break; }
        }
        submitBtn.disabled = !valid;

        if (picks.length === 0) {
            validationEl.classList.add('hidden');
        } else if (overspent) {
            validationEl.textContent = '⚠ Pārsniedz kapitālu';
            validationEl.className = 'text-xs mt-3 text-red-600';
            validationEl.classList.remove('hidden');
        } else {
            validationEl.classList.add('hidden');
        }
    }

    function renderHiddenInputs() {
        // Remove old hidden inputs
        Array.from(document.querySelectorAll('input[data-hidden-pick]')).forEach(i => i.remove());

        // Append fresh hidden inputs for form submit
        const form = document.getElementById('portfolio-form');
        picks.forEach((p, idx) => {
            ['ticker', 'amount', 'transaction_date'].forEach(field => {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = `picks[${idx}][${field}]`;
                h.value = field === 'transaction_date' ? p.date : p[field === 'amount' ? 'amount' : 'ticker'];
                h.setAttribute('data-hidden-pick', '1');
                form.appendChild(h);
            });
        });
    }

    // Two-way binding: change weight → recalc amount, change amount → recalc weight
    picksTbody.addEventListener('input', e => {
        const idx = parseInt(e.target.dataset.idx);
        const field = e.target.dataset.field;
        if (isNaN(idx) || !field) return;

        const val = parseFloat(e.target.value) || 0;
        const capital = getCapital();

        if (field === 'weight') {
            picks[idx].weight = val;
            picks[idx].amount = (val / 100) * capital;
            // Re-render only totals + the amount input (don't lose focus)
            const amountInput = picksTbody.querySelector(`input[data-idx="${idx}"][data-field="amount"]`);
            if (amountInput) amountInput.value = picks[idx].amount.toFixed(2);
        } else if (field === 'amount') {
            picks[idx].amount = val;
            picks[idx].weight = capital > 0 ? (val / capital) * 100 : 0;
            const weightInput = picksTbody.querySelector(`input[data-idx="${idx}"][data-field="weight"]`);
            if (weightInput) weightInput.value = picks[idx].weight.toFixed(2);
        } else if (field === 'date') {
            picks[idx].date = e.target.value;
        }
        renderTotals();
        renderHiddenInputs();
    });

    picksTbody.addEventListener('click', e => {
        if (e.target.dataset.action === 'remove') {
            const idx = parseInt(e.target.dataset.idx);
            picks.splice(idx, 1);
            render();
        }
    });

    capitalInput.addEventListener('input', () => {
        const capital = getCapital();
        // Recalc amounts based on existing weights
        picks.forEach(p => p.amount = (p.weight / 100) * capital);
        render();
    });

    // Base date change → propagate to all picks (overrides their individual dates)
    baseDateInput.addEventListener('change', () => {
        const newDate = baseDateInput.value;
        if (!newDate) return;
        picks.forEach(p => p.date = newDate);
        render();
    });

    // Submit safety net — regenerate hidden inputs at submit time
    document.getElementById('portfolio-form').addEventListener('submit', (e) => {
        if (picks.length === 0) {
            e.preventDefault();
            alert('Pievieno vismaz vienu instrumentu pirms portfeļa izveides');
            return;
        }
        renderHiddenInputs();      // last-chance to ensure hidden inputs reflect current picks
        console.log('Submitting portfolio with', picks.length, 'picks:', picks);
    });

    equalWeightBtn.addEventListener('click', () => {
        if (picks.length === 0) return;
        const w = 100 / picks.length;
        picks.forEach(p => p.weight = w);
        const capital = getCapital();
        picks.forEach(p => p.amount = (w / 100) * capital);
        render();
    });

    /* ─── Ticker search ─── */
    let searchTimer;
    tickerSearch.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = tickerSearch.value.trim();
        if (q.length < 1) {
            tickerResults.classList.add('hidden');
            return;
        }
        searchTimer = setTimeout(() => {
            fetch(`{{ route('instruments.search') }}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(resp => {
                    const items = resp.data || [];
                    tickerResults.innerHTML = '';
                    if (!items.length) {
                        tickerResults.classList.add('hidden');
                        return;
                    }
                    items.slice(0, 10).forEach(i => {
                        const t = (i.ticker || '').toUpperCase();
                        const alreadyPicked = picks.some(p => p.ticker === t);
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = `block w-full text-left px-3 py-2 text-sm hover:bg-blue-50 ${alreadyPicked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`;
                        row.innerHTML = `<span class="font-semibold text-gray-900">${t}</span> <span class="text-gray-500 text-xs">${i.company_name || ''}</span>${alreadyPicked ? ' <span class="text-[10px] text-amber-600">jau pievienots</span>' : ''}`;
                        if (!alreadyPicked) {
                            row.addEventListener('click', () => addPick(t, i.company_name || ''));
                        }
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

    function addPick(ticker, companyName) {
        const capital = getCapital();
        // Auto-rebalance: equal weight on add
        const newCount = picks.length + 1;
        const w = 100 / newCount;
        picks.forEach(p => {
            p.weight = w;
            p.amount = (w / 100) * capital;
        });
        picks.push({
            ticker,
            company_name: companyName,
            weight: w,
            amount: (w / 100) * capital,
            date: baseDateInput.value || dataMaxDate,    // default to base date (or latest)
        });
        tickerSearch.value = '';
        tickerResults.classList.add('hidden');
        render();
    }

    /* ─── Bulk input on Enter: "AAPL, MSFT 20%, NVDA" ─── */
    function parseBulkInput(text) {
        return text.split(',').map(s => s.trim()).filter(Boolean).map(item => {
            // Match: TICKER + optional " number(.number)? %?"
            const m = item.match(/^([A-Za-z][A-Za-z0-9.\-]*)\s*(\d+(?:\.\d+)?)?\s*%?$/);
            if (!m) return { ticker: item.toUpperCase(), weight: null, invalid: true };
            return {
                ticker: m[1].toUpperCase(),
                weight: m[2] !== undefined ? parseFloat(m[2]) : null,
            };
        });
    }

    async function addBulkPicks(text) {
        const parsed = parseBulkInput(text);
        if (!parsed.length) return;

        // Filter out duplicates already in picks
        const existingTickers = new Set(picks.map(p => p.ticker));
        const newPicks = parsed.filter(p => !existingTickers.has(p.ticker) && !p.invalid);
        const alreadyAdded = parsed.filter(p => existingTickers.has(p.ticker));

        if (newPicks.length === 0) {
            alert('Visi šie tickeri jau ir pievienoti');
            return;
        }

        const tickersParam = newPicks.map(p => p.ticker).join(',');
        try {
            const r = await fetch(`{{ route('instruments.batch') }}?tickers=${encodeURIComponent(tickersParam)}`);
            const json = await r.json();
            const lookup = new Map(json.data.map(d => [d.ticker, d]));

            const missing = [];
            const valid = [];
            newPicks.forEach(p => {
                const info = lookup.get(p.ticker);
                if (!info || !info.found) {
                    missing.push(p.ticker);
                    return;
                }
                valid.push({ ticker: p.ticker, company_name: info.company_name, explicit_weight: p.weight });
            });

            if (missing.length) {
                alert('Nezināmi tickeri (izlaisti): ' + missing.join(', '));
            }
            if (valid.length === 0) return;

            // Distribute weights
            const explicitSum = valid.filter(p => p.explicit_weight !== null).reduce((s, p) => s + p.explicit_weight, 0);
            const implicitCount = valid.filter(p => p.explicit_weight === null).length;
            const remaining = Math.max(0, 100 - explicitSum);
            const implicitWeight = implicitCount > 0 ? remaining / implicitCount : 0;

            const capital = getCapital();
            valid.forEach(p => {
                const w = p.explicit_weight !== null ? p.explicit_weight : implicitWeight;
                picks.push({
                    ticker: p.ticker,
                    company_name: p.company_name,
                    weight: w,
                    amount: (w / 100) * capital,
                    date: baseDateInput.value || dataMaxDate,
                });
            });

            tickerSearch.value = '';
            tickerResults.classList.add('hidden');
            render();
        } catch (err) {
            alert('Kļūda meklējot instrumentus: ' + err.message);
        }
    }

    tickerSearch.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const text = tickerSearch.value.trim();
            if (text) addBulkPicks(text);
        }
    });
})();
</script>
@endsection
