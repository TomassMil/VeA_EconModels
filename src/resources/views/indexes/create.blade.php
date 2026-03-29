@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('indexes.index') }}" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Atpakaļ uz indeksiem
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Izveidot jaunu indeksu</h1>
            <p class="text-gray-600 mt-2">Definē filtrus vai manuāli pievieno instrumentus savam indeksam.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('indexes.store') }}" id="index-form">
            @csrf

            {{-- Name & Description --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Pamatinformācija</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nosaukums *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Apraksts</label>
                        <textarea name="description" id="description" rows="2" maxlength="500"
                                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Filter Presets --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Ieteiktie filtri</h2>
                <p class="text-sm text-gray-500 mb-4">Klikšķini, lai ātri aizpildītu filtrus. Vari arī norādīt pats.</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($presets as $key => $preset)
                        <button type="button"
                                class="preset-btn rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                                data-filters="{{ json_encode($preset['filters']) }}"
                                title="{{ $preset['description'] }}">
                            {{ $preset['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Tirgus filtri</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Cena ($)</span>
                        <input type="number" name="filters[price_min]" id="f-price-min" step="0.01" min="0" value="{{ old('filters.price_min') }}"
                               class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                        <span class="text-gray-400 text-xs">—</span>
                        <input type="number" name="filters[price_max]" id="f-price-max" step="0.01" min="0" value="{{ old('filters.price_max') }}"
                               class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Vid. apjoms</span>
                        <input type="number" name="filters[avg_volume_min]" id="f-vol-min" min="0" value="{{ old('filters.avg_volume_min') }}"
                               class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                        <span class="text-gray-400 text-xs">—</span>
                        <input type="number" name="filters[avg_volume_max]" id="f-vol-max" min="0" value="{{ old('filters.avg_volume_max') }}"
                               class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Izslēgt zem ($)</span>
                        <input type="number" name="filters[exclude_below_price]" id="f-exclude-below" step="0.01" min="0" value="{{ old('filters.exclude_below_price') }}"
                               class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="piem., 1.00">
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="filters[has_fundamentals]" id="f-has-fundamentals" value="1"
                                   {{ old('filters.has_fundamentals') ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-medium text-gray-600">Tikai ar finanšu datiem</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fundamentālie filtri</h3>
                    <p class="text-xs text-gray-400 mb-3">Pēc pēdējā gada pārskata datiem (USD)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Ieņēmumi</span>
                            <input type="number" name="filters[revenue_min]" id="f-revenue-min" min="0" value="{{ old('filters.revenue_min') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="filters[revenue_max]" id="f-revenue-max" min="0" value="{{ old('filters.revenue_max') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Tīrā peļņa</span>
                            <input type="number" name="filters[net_income_min]" id="f-net-income-min" value="{{ old('filters.net_income_min') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="filters[net_income_max]" id="f-net-income-max" value="{{ old('filters.net_income_max') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Kopā aktīvi</span>
                            <input type="number" name="filters[total_assets_min]" id="f-assets-min" min="0" value="{{ old('filters.total_assets_min') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="filters[total_assets_max]" id="f-assets-max" min="0" value="{{ old('filters.total_assets_max') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Saistības</span>
                            <input type="number" name="filters[total_liabilities_min]" id="f-liabilities-min" min="0" value="{{ old('filters.total_liabilities_min') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="filters[total_liabilities_max]" id="f-liabilities-max" min="0" value="{{ old('filters.total_liabilities_max') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-600 w-24 shrink-0">EPS</span>
                            <input type="number" name="filters[eps_min]" id="f-eps-min" step="0.01" value="{{ old('filters.eps_min') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="filters[eps_max]" id="f-eps-max" step="0.01" value="{{ old('filters.eps_max') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-medium text-gray-600 w-24 shrink-0">Op. naudas pl.</span>
                            <input type="number" name="filters[operating_cf_min]" id="f-opcf-min" value="{{ old('filters.operating_cf_min') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="min">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="filters[operating_cf_max]" id="f-opcf-max" value="{{ old('filters.operating_cf_max') }}"
                                   class="w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none" placeholder="max">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Manual Instrument Selection --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Manuāli pievienot instrumentus</h2>
                <p class="text-sm text-gray-500 mb-4">Meklē un pievieno konkrētus instrumentus savam indeksam.</p>

                <div class="relative mb-4">
                    <input type="text" id="instrument-search"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                           placeholder="Meklēt pēc ticker vai nosaukuma...">
                    <div id="search-results" class="absolute z-20 w-full mt-1 bg-white rounded-lg border border-gray-200 shadow-lg hidden max-h-48 overflow-y-auto"></div>
                </div>

                <div id="selected-instruments" class="space-y-2"></div>
            </div>

            {{-- Preview --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Priekšskatījums</h2>
                    <button type="button" id="preview-btn"
                            class="rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 transition-colors">
                        Atjaunināt priekšskatījumu
                    </button>
                </div>
                <div id="preview-area">
                    <p class="text-sm text-gray-500">Nospied "Atjaunināt priekšskatījumu", lai redzētu atbilstošos instrumentus.</p>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('indexes.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Atcelt
                </a>
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                    Izveidot indeksu
                </button>
            </div>
        </form>

    </div>
</div>

<script>
(function() {
    const manualIds = new Set();
    const manualInstruments = {};
    const excludedIds = new Set();

    // ── Preset buttons ──
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const filters = JSON.parse(btn.dataset.filters);
            document.getElementById('f-price-min').value = filters.price_min ?? '';
            document.getElementById('f-price-max').value = filters.price_max ?? '';
            document.getElementById('f-vol-min').value = filters.avg_volume_min ?? '';
            document.getElementById('f-vol-max').value = filters.avg_volume_max ?? '';
            document.getElementById('f-exclude-below').value = filters.exclude_below_price ?? '';
            document.getElementById('f-has-fundamentals').checked = !!filters.has_fundamentals;
            document.getElementById('f-revenue-min').value = filters.revenue_min ?? '';
            document.getElementById('f-revenue-max').value = filters.revenue_max ?? '';
            document.getElementById('f-net-income-min').value = filters.net_income_min ?? '';
            document.getElementById('f-net-income-max').value = filters.net_income_max ?? '';
            document.getElementById('f-assets-min').value = filters.total_assets_min ?? '';
            document.getElementById('f-assets-max').value = filters.total_assets_max ?? '';
            document.getElementById('f-liabilities-min').value = filters.total_liabilities_min ?? '';
            document.getElementById('f-liabilities-max').value = filters.total_liabilities_max ?? '';
            document.getElementById('f-eps-min').value = filters.eps_min ?? '';
            document.getElementById('f-eps-max').value = filters.eps_max ?? '';
            document.getElementById('f-opcf-min').value = filters.operating_cf_min ?? '';
            document.getElementById('f-opcf-max').value = filters.operating_cf_max ?? '';

            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-700'));
            btn.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-700');
        });
    });

    // ── Instrument search ──
    const searchInput = document.getElementById('instrument-search');
    const searchResults = document.getElementById('search-results');
    let searchTimeout = null;

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        const q = searchInput.value.trim();
        if (q.length < 1) { searchResults.classList.add('hidden'); return; }

        searchTimeout = setTimeout(() => {
            fetch(`{{ route('instruments.search') }}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.data || data.data.length === 0) {
                        searchResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Nav rezultātu</div>';
                    } else {
                        searchResults.innerHTML = data.data.map(inst => `
                            <div class="search-result-item px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer flex justify-between items-center ${manualIds.has(inst.id) ? 'opacity-50' : ''}"
                                 data-id="${inst.id}" data-ticker="${inst.ticker}" data-name="${inst.company_name}" data-exchange="${inst.exchange}">
                                <span><strong>${inst.ticker}</strong> — ${inst.company_name}</span>
                                <span class="text-xs text-gray-400">${inst.exchange || ''}</span>
                            </div>
                        `).join('');

                        searchResults.querySelectorAll('.search-result-item').forEach(item => {
                            item.addEventListener('click', () => {
                                const id = parseInt(item.dataset.id);
                                if (manualIds.has(id)) return;
                                addManualInstrument(id, item.dataset.ticker, item.dataset.name, item.dataset.exchange);
                                searchInput.value = '';
                                searchResults.classList.add('hidden');
                            });
                        });
                    }
                    searchResults.classList.remove('hidden');
                });
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });

    function addManualInstrument(id, ticker, name, exchange) {
        manualIds.add(id);
        manualInstruments[id] = { ticker, name, exchange };
        renderSelectedInstruments();
    }

    function removeManualInstrument(id) {
        manualIds.delete(id);
        delete manualInstruments[id];
        renderSelectedInstruments();
    }

    function renderSelectedInstruments() {
        const container = document.getElementById('selected-instruments');
        if (manualIds.size === 0) {
            container.innerHTML = '';
            return;
        }
        container.innerHTML = Array.from(manualIds).map(id => {
            const inst = manualInstruments[id];
            return `
                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-semibold text-gray-900">${inst.ticker}</span>
                        <span class="text-gray-600">${inst.name}</span>
                        <span class="text-xs text-gray-400">${inst.exchange || ''}</span>
                    </div>
                    <button type="button" class="remove-instrument text-gray-400 hover:text-red-600" data-id="${id}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <input type="hidden" name="manual_instruments[]" value="${id}">
                </div>
            `;
        }).join('');

        container.querySelectorAll('.remove-instrument').forEach(btn => {
            btn.addEventListener('click', () => removeManualInstrument(parseInt(btn.dataset.id)));
        });
    }

    // ── Helpers ──
    function formatVolume(vol) {
        vol = parseFloat(vol);
        if (vol >= 1000000) return (vol / 1000000).toFixed(1) + 'M';
        if (vol >= 1000) return Math.round(vol / 1000) + 'K';
        return Math.round(vol).toString();
    }

    function formatMoney(val) {
        val = parseFloat(val);
        const abs = Math.abs(val);
        const sign = val < 0 ? '-' : '';
        if (abs >= 1e12) return sign + '$' + (abs / 1e12).toFixed(1) + 'T';
        if (abs >= 1e9) return sign + '$' + (abs / 1e9).toFixed(1) + 'B';
        if (abs >= 1e6) return sign + '$' + (abs / 1e6).toFixed(1) + 'M';
        if (abs >= 1e3) return sign + '$' + (abs / 1e3).toFixed(1) + 'K';
        return sign + '$' + abs.toFixed(2);
    }

    function getActiveFilterColumns(filters) {
        const cols = [];
        if (filters.price_min !== undefined || filters.price_max !== undefined || filters.exclude_below_price !== undefined) {
            cols.push({ key: 'latest_close', label: 'Cena', src: 'root', fmt: v => v ? '$' + parseFloat(v).toFixed(2) : '-' });
        }
        if (filters.avg_volume_min !== undefined || filters.avg_volume_max !== undefined) {
            cols.push({ key: 'avg_volume', label: 'Vid. apjoms', src: 'root', fmt: v => v ? formatVolume(v) : '-' });
        }
        const fundCols = [
            { fMin: 'revenue_min', fMax: 'revenue_max', key: 'revenue', label: 'Ieņēmumi' },
            { fMin: 'net_income_min', fMax: 'net_income_max', key: 'net_income', label: 'Tīrā peļņa' },
            { fMin: 'total_assets_min', fMax: 'total_assets_max', key: 'total_assets', label: 'Aktīvi' },
            { fMin: 'total_liabilities_min', fMax: 'total_liabilities_max', key: 'total_liabilities', label: 'Saistības' },
            { fMin: 'eps_min', fMax: 'eps_max', key: 'eps', label: 'EPS' },
            { fMin: 'operating_cf_min', fMax: 'operating_cf_max', key: 'operating_cf', label: 'Op. naudas pl.' },
        ];
        fundCols.forEach(c => {
            if (filters[c.fMin] !== undefined || filters[c.fMax] !== undefined) {
                const isEps = c.key === 'eps';
                cols.push({
                    key: c.key, label: c.label, src: 'fundamentals',
                    fmt: v => v ? (isEps ? '$' + parseFloat(v).toFixed(2) : formatMoney(v)) : '-'
                });
            }
        });
        return cols;
    }

    function updateExcludedInputs() {
        document.querySelectorAll('.excluded-input').forEach(el => el.remove());
        const form = document.getElementById('index-form');
        excludedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'excluded_instruments[]';
            input.value = id;
            input.className = 'excluded-input';
            form.appendChild(input);
        });
    }

    function removeFromPreview(id) {
        excludedIds.add(id);
        const row = document.querySelector(`tr[data-instrument-id="${id}"]`);
        if (row) row.remove();
        const totalEl = document.querySelector('.preview-total');
        if (totalEl) {
            const newTotal = parseInt(totalEl.textContent) - 1;
            totalEl.textContent = newTotal;
        }
        // Also remove from manual selection if present
        if (manualIds.has(id)) {
            manualIds.delete(id);
            delete manualInstruments[id];
            renderSelectedInstruments();
        }
        updateExcludedInputs();
    }

    // ── Preview ──
    document.getElementById('preview-btn').addEventListener('click', () => {
        const area = document.getElementById('preview-area');
        area.innerHTML = '<p class="text-sm text-gray-500">Ielādē...</p>';

        const filters = {};
        const priceMin = document.getElementById('f-price-min').value;
        const priceMax = document.getElementById('f-price-max').value;
        const volMin = document.getElementById('f-vol-min').value;
        const volMax = document.getElementById('f-vol-max').value;
        const excludeBelow = document.getElementById('f-exclude-below').value;
        const hasFundamentals = document.getElementById('f-has-fundamentals').checked;

        if (priceMin) filters.price_min = parseFloat(priceMin);
        if (priceMax) filters.price_max = parseFloat(priceMax);
        if (volMin) filters.avg_volume_min = parseInt(volMin);
        if (volMax) filters.avg_volume_max = parseInt(volMax);
        if (excludeBelow) filters.exclude_below_price = parseFloat(excludeBelow);
        if (hasFundamentals) filters.has_fundamentals = true;

        // Fundamental filters
        const fundFields = [
            ['f-revenue-min', 'revenue_min'], ['f-revenue-max', 'revenue_max'],
            ['f-net-income-min', 'net_income_min'], ['f-net-income-max', 'net_income_max'],
            ['f-assets-min', 'total_assets_min'], ['f-assets-max', 'total_assets_max'],
            ['f-liabilities-min', 'total_liabilities_min'], ['f-liabilities-max', 'total_liabilities_max'],
            ['f-eps-min', 'eps_min'], ['f-eps-max', 'eps_max'],
            ['f-opcf-min', 'operating_cf_min'], ['f-opcf-max', 'operating_cf_max'],
        ];
        fundFields.forEach(([elId, key]) => {
            const v = document.getElementById(elId).value;
            if (v) filters[key] = parseFloat(v);
        });

        fetch('{{ route("indexes.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                filters: filters,
                manual_instruments: Array.from(manualIds),
                excluded_instruments: Array.from(excludedIds),
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.total === 0) {
                area.innerHTML = '<p class="text-sm text-gray-500">Nav atbilstošu instrumentu ar šiem filtriem.</p>';
                return;
            }

            const dynCols = getActiveFilterColumns(filters);

            let html = `<p class="text-sm font-medium text-gray-700 mb-3">Kopā atbilst: <span class="preview-total text-blue-600 font-bold">${data.total}</span> instrumenti</p>`;
            html += '<div class="overflow-x-auto rounded-lg border border-gray-200"><table class="min-w-full text-sm">';
            html += '<thead class="bg-gray-50"><tr>';
            html += '<th class="px-3 py-2 text-left font-medium text-gray-600">Ticker</th>';
            html += '<th class="px-3 py-2 text-left font-medium text-gray-600">Nosaukums</th>';
            html += '<th class="px-3 py-2 text-left font-medium text-gray-600">Birža</th>';
            dynCols.forEach(c => {
                html += `<th class="px-3 py-2 text-right font-medium text-gray-600">${c.label}</th>`;
            });
            html += '<th class="px-3 py-2 w-10"></th>';
            html += '</tr></thead>';
            html += '<tbody class="divide-y divide-gray-100">';
            data.preview.forEach(inst => {
                html += `<tr class="hover:bg-gray-50" data-instrument-id="${inst.id}">`;
                html += `<td class="px-3 py-2 font-semibold text-gray-900">${inst.ticker}</td>`;
                html += `<td class="px-3 py-2 text-gray-700">${inst.company_name}</td>`;
                html += `<td class="px-3 py-2 text-gray-500">${inst.exchange || '-'}</td>`;
                dynCols.forEach(c => {
                    const val = c.src === 'fundamentals' ? (inst.fundamentals?.[c.key] ?? null) : inst[c.key];
                    html += `<td class="px-3 py-2 text-right text-gray-700">${c.fmt(val)}</td>`;
                });
                html += `<td class="px-3 py-2 text-center">
                    <button type="button" class="preview-remove text-gray-400 hover:text-red-600 transition-colors" data-id="${inst.id}" title="Noņemt no indeksa">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </td>`;
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            if (data.total > 15) {
                html += `<p class="text-xs text-gray-400 mt-2">Rāda pirmos 15 no ${data.total}.</p>`;
            }
            area.innerHTML = html;

            area.querySelectorAll('.preview-remove').forEach(btn => {
                btn.addEventListener('click', () => removeFromPreview(parseInt(btn.dataset.id)));
            });
        })
        .catch(() => {
            area.innerHTML = '<p class="text-sm text-red-600">Kļūda ielādējot priekšskatījumu.</p>';
        });
    });
})();
</script>
@endsection
