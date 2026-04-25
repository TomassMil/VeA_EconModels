@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Instrumenti</h1>
            <p class="text-xs text-gray-500">Atlasi pēc apraksta, fundamentālajiem un tehniskajiem rādītājiem</p>
        </div>

        {{-- Filter card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-3">
            <div class="flex border-b border-gray-200">
                <button type="button" data-tab="descriptive" class="filter-tab px-4 py-2.5 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                    Aprakstošie
                </button>
                <button type="button" data-tab="fundamental" class="filter-tab px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    Fundamentālie
                </button>
                <button type="button" data-tab="technical" class="filter-tab px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    Tehniskie
                </button>
            </div>

            {{-- Descriptive --}}
            <div id="tab-descriptive" class="filter-pane p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sektors</label>
                        <select name="sector" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="">Visi sektori</option>
                            @foreach ($sectors as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Industrija</label>
                        <select name="industry" id="filter-industry" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="">Visas industrijas</option>
                            @foreach ($allIndustries as $ind)
                                <option value="{{ $ind }}">{{ $ind }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cena ($)</label>
                        <div class="flex items-center gap-1">
                            <input type="number" name="price_min" step="0.01" min="0" placeholder="min" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="price_max" step="0.01" min="0" placeholder="max" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Vid. dienas apjoms</label>
                        <div class="flex items-center gap-1">
                            <input type="number" name="volume_min" min="0" placeholder="min" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" name="volume_max" min="0" placeholder="max" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Fundamental --}}
            <div id="tab-fundamental" class="filter-pane p-4 hidden">
                <p class="text-xs text-gray-400 mb-3">Pēc pēdējā gada (FY) pārskata datiem (USD). Pamatā SimFin dati 2018–2023.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @php
                        $fundFilters = [
                            ['name' => 'revenue',          'label' => 'Ieņēmumi'],
                            ['name' => 'net_income',       'label' => 'Tīrā peļņa'],
                            ['name' => 'gross_profit',     'label' => 'Bruto peļņa'],
                            ['name' => 'operating_income', 'label' => 'Op. ienākumi'],
                            ['name' => 'total_assets',     'label' => 'Kopā aktīvi'],
                            ['name' => 'total_liabilities','label' => 'Kopā saistības'],
                            ['name' => 'total_equity',     'label' => 'Pašu kapitāls'],
                            ['name' => 'operating_cf',     'label' => 'Op. naudas pl.'],
                        ];
                    @endphp
                    @foreach ($fundFilters as $f)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $f['label'] }}</label>
                            <div class="flex items-center gap-1">
                                <input type="number" name="{{ $f['name'] }}_min" placeholder="min" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                                <span class="text-gray-400 text-xs">—</span>
                                <input type="number" name="{{ $f['name'] }}_max" placeholder="max" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Technical --}}
            <div id="tab-technical" class="filter-pane p-4 hidden">
                <p class="text-xs text-gray-400 mb-3">Cenas izmaiņas balstoties uz pieejamo cenu vēsturi (līdz 2024-01).</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Veiktspējas periods</label>
                        <select name="perf_period" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="">—</option>
                            <option value="1m">1 mēnesis</option>
                            <option value="3m">3 mēneši</option>
                            <option value="6m">6 mēneši</option>
                            <option value="1y">1 gads</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Virziens</label>
                        <select name="perf_direction" class="filter-input w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="">Visi</option>
                            <option value="up">Pozitīva (↑)</option>
                            <option value="down">Negatīva (↓)</option>
                            <option value="up5">↑ &gt; 5%</option>
                            <option value="up10">↑ &gt; 10%</option>
                            <option value="up20">↑ &gt; 20%</option>
                            <option value="down5">↓ &lt; -5%</option>
                            <option value="down10">↓ &lt; -10%</option>
                            <option value="down20">↓ &lt; -20%</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active filter badges + clear button --}}
        <div class="flex items-center justify-between gap-2 mb-3 min-h-[28px]">
            <div id="active-filters" class="flex flex-wrap gap-1.5"></div>
            <button type="button" id="clear-all-btn" class="hidden shrink-0 text-xs text-gray-500 hover:text-red-600 underline">
                Notīrīt visus
            </button>
        </div>

        {{-- Search --}}
        <div class="relative mb-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                </svg>
                <input id="search-input" type="text" name="q" placeholder="Meklēt pēc ticker vai nosaukuma..." autocomplete="off"
                       class="filter-input w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            </div>
            <div id="search-popup" class="hidden absolute z-30 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden">
                <ul id="search-popup-list" class="max-h-72 overflow-y-auto"></ul>
            </div>
        </div>

        {{-- Result count + loading --}}
        <div class="flex items-center justify-between mb-2 px-1">
            <p class="text-sm text-gray-500">
                Atrasti: <span id="result-count" class="font-semibold text-gray-700">{{ $total }}</span> instrumenti
            </p>
            <div id="loading-indicator" class="hidden text-xs text-blue-600 flex items-center gap-1.5">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                Ielādē...
            </div>
        </div>

        {{-- Results list --}}
        <div id="results" class="bg-white rounded-lg border border-gray-200 overflow-hidden divide-y divide-gray-100">
            @foreach ($instruments as $inst)
                <a href="{{ route('instruments.show', $inst->id) }}" class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-blue-50 transition-colors">
                    <span class="font-bold text-gray-900 w-16 shrink-0">{{ $inst->ticker }}</span>
                    <span class="text-gray-600 truncate flex-1">{{ $inst->company_name }}</span>
                    @if ($inst->sector)
                        <span class="hidden sm:inline text-xs text-gray-400 truncate max-w-[140px]">{{ $inst->sector }}</span>
                    @endif
                    @if (isset($inst->latest_close) && $inst->latest_close !== null)
                        <span class="text-xs font-medium text-gray-700 w-16 text-right">${{ number_format((float) $inst->latest_close, 2) }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div id="pagination" class="mt-4 flex justify-center"></div>
    </div>
</div>

<script>
(function () {
    const filterUrl = @json(route('instruments.filter', [], false));
    const searchUrl = @json(route('instruments.search', [], false));
    const detailTpl = @json(route('instruments.show', ['instrument' => '__ID__'], false));
    const industryBySector = @json($industryBySector);
    const allIndustries = @json($allIndustries);

    const labels = {
        sector: 'Sektors', industry: 'Industrija',
        price_min: 'Cena ≥', price_max: 'Cena ≤',
        volume_min: 'Apjoms ≥', volume_max: 'Apjoms ≤',
        revenue_min: 'Ieņēm. ≥', revenue_max: 'Ieņēm. ≤',
        net_income_min: 'Tīrā peļņa ≥', net_income_max: 'Tīrā peļņa ≤',
        gross_profit_min: 'Bruto peļņa ≥', gross_profit_max: 'Bruto peļņa ≤',
        operating_income_min: 'Op. ien. ≥', operating_income_max: 'Op. ien. ≤',
        total_assets_min: 'Aktīvi ≥', total_assets_max: 'Aktīvi ≤',
        total_liabilities_min: 'Saistības ≥', total_liabilities_max: 'Saistības ≤',
        total_equity_min: 'Kapitāls ≥', total_equity_max: 'Kapitāls ≤',
        operating_cf_min: 'Op. CF ≥', operating_cf_max: 'Op. CF ≤',
        perf_period: 'Periods', perf_direction: 'Veiktspēja',
        q: 'Meklēšana',
    };

    const metricFormat = {
        latest_close:    { label: 'Cena',         fmt: v => '$' + parseFloat(v).toFixed(2) },
        avg_volume:      { label: 'Apj.',         fmt: v => formatVolume(v) },
        revenue:         { label: 'Ieņ.',         fmt: v => formatMoney(v) },
        net_income:      { label: 'NI',           fmt: v => formatMoney(v) },
        gross_profit:    { label: 'GP',           fmt: v => formatMoney(v) },
        operating_income:{ label: 'OI',           fmt: v => formatMoney(v) },
        total_assets:    { label: 'TA',           fmt: v => formatMoney(v) },
        total_liabilities:{label: 'TL',           fmt: v => formatMoney(v) },
        total_equity:    { label: 'TE',           fmt: v => formatMoney(v) },
        operating_cf:    { label: 'OCF',          fmt: v => formatMoney(v), key: 'net_cash_operating' },
        pct_change:      { label: 'Δ',            fmt: v => (v >= 0 ? '+' : '') + parseFloat(v).toFixed(1) + '%' },
    };

    let currentPage = 1;
    let debounceTimer = null;
    let requestCounter = 0;
    let activeMetrics = ['latest_close'];

    // ----- Tabs -----
    document.querySelectorAll('.filter-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-tab').forEach(b => {
                b.classList.remove('border-blue-500', 'text-blue-600');
                b.classList.add('border-transparent', 'text-gray-500');
            });
            btn.classList.add('border-blue-500', 'text-blue-600');
            btn.classList.remove('border-transparent', 'text-gray-500');

            document.querySelectorAll('.filter-pane').forEach(p => p.classList.add('hidden'));
            document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
        });
    });

    // ----- Industry dropdown filtering by sector -----
    const sectorSel = document.querySelector('select[name="sector"]');
    const industrySel = document.getElementById('filter-industry');
    sectorSel.addEventListener('change', () => {
        const s = sectorSel.value;
        const list = s && industryBySector[s] ? industryBySector[s] : allIndustries;
        const current = industrySel.value;
        industrySel.innerHTML = '<option value="">Visas industrijas</option>' +
            list.map(i => `<option value="${escapeHtml(i)}"${i === current ? ' selected' : ''}>${escapeHtml(i)}</option>`).join('');
        // If selected industry no longer in the new list, clear it and trigger fetch
        if (current && !list.includes(current)) {
            industrySel.value = '';
        }
    });

    // ----- Filter change handling -----
    document.querySelectorAll('.filter-input').forEach(el => {
        const evt = (el.tagName === 'SELECT') ? 'change' : 'input';
        el.addEventListener(evt, onFilterChange);
    });

    function onFilterChange(e) {
        // Search has its own popup behaviour — skip popup for the AJAX filter
        if (e && e.target && e.target.id === 'search-input') {
            handleSearchInput();
        }
        clearTimeout(debounceTimer);
        currentPage = 1;
        const delay = (e && e.target && e.target.tagName === 'SELECT') ? 0 : 220;
        debounceTimer = setTimeout(fetchResults, delay);
    }

    function collectFilters() {
        const f = {};
        document.querySelectorAll('.filter-input').forEach(el => {
            const v = el.value.trim();
            if (v !== '') f[el.name] = v;
        });
        return f;
    }

    function fetchResults() {
        const filters = collectFilters();
        renderActiveFilters(filters);

        const params = new URLSearchParams(filters);
        params.set('page', currentPage);

        const reqId = ++requestCounter;
        document.getElementById('loading-indicator').classList.remove('hidden');

        fetch(`${filterUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (reqId !== requestCounter) return;
                activeMetrics = (data.active_metrics && data.active_metrics.length) ? data.active_metrics : ['latest_close'];
                renderResults(data.data);
                renderCount(data.total);
                renderPagination(data);
            })
            .catch(() => {
                if (reqId !== requestCounter) return;
                document.getElementById('results').innerHTML = '<div class="px-4 py-6 text-center text-sm text-red-600">Kļūda ielādējot rezultātus.</div>';
            })
            .finally(() => {
                if (reqId === requestCounter) {
                    document.getElementById('loading-indicator').classList.add('hidden');
                }
            });
    }

    function renderResults(items) {
        const container = document.getElementById('results');
        if (!items || items.length === 0) {
            container.innerHTML = '<div class="px-4 py-6 text-center text-sm text-gray-500">Instrumenti netika atrasti.</div>';
            return;
        }
        container.innerHTML = items.map(inst => {
            const url = detailTpl.replace('__ID__', encodeURIComponent(inst.id));
            const sectorBadge = inst.sector
                ? `<span class="hidden sm:inline text-xs text-gray-400 truncate max-w-[140px]">${escapeHtml(inst.sector)}</span>`
                : '';

            const metrics = activeMetrics.map(m => {
                const def = metricFormat[m];
                if (!def) return '';
                const key = def.key || m;
                const val = inst[key];
                if (val === null || val === undefined) return '';
                let cls = 'text-gray-700';
                if (m === 'pct_change') cls = (parseFloat(val) >= 0 ? 'text-green-700' : 'text-red-700');
                return `<span class="text-xs font-medium ${cls} whitespace-nowrap">${def.label}: ${def.fmt(val)}</span>`;
            }).filter(Boolean).join('<span class="text-gray-300 text-xs">·</span>');

            return `<a href="${url}" class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-blue-50 transition-colors">
                <span class="font-bold text-gray-900 w-16 shrink-0">${escapeHtml(inst.ticker)}</span>
                <span class="text-gray-600 truncate flex-1">${escapeHtml(inst.company_name || '')}</span>
                ${sectorBadge}
                <span class="flex items-center gap-1.5 shrink-0">${metrics}</span>
            </a>`;
        }).join('');
    }

    function renderCount(total) {
        document.getElementById('result-count').textContent = total;
    }

    function renderActiveFilters(filters) {
        const container = document.getElementById('active-filters');
        const clearBtn = document.getElementById('clear-all-btn');
        const entries = Object.entries(filters);

        if (entries.length === 0) {
            container.innerHTML = '';
            clearBtn.classList.add('hidden');
            return;
        }

        // Special-case: perf_period + perf_direction render as a single badge
        const collapsed = [];
        const seen = new Set();
        for (const [k, v] of entries) {
            if (k === 'perf_period' || k === 'perf_direction') continue;
            collapsed.push([k, v]);
            seen.add(k);
        }
        if (filters.perf_period && filters.perf_direction) {
            collapsed.push(['__perf', `${filters.perf_period.toUpperCase()} ${filters.perf_direction}`]);
        }

        container.innerHTML = collapsed.map(([k, v]) => {
            const lbl = (k === '__perf') ? 'Veiktspēja' : (labels[k] || k);
            return `<span class="inline-flex items-center gap-1 rounded-full bg-blue-50 border border-blue-200 px-2 py-0.5 text-xs text-blue-800">
                <span class="font-medium">${escapeHtml(lbl)}:</span>
                <span>${escapeHtml(v)}</span>
                <button type="button" class="ml-0.5 hover:text-red-600" data-clear="${k}" title="Noņemt">×</button>
            </span>`;
        }).join('');

        container.querySelectorAll('button[data-clear]').forEach(btn => {
            btn.addEventListener('click', () => clearFilter(btn.dataset.clear));
        });

        clearBtn.classList.remove('hidden');
    }

    function clearFilter(key) {
        if (key === '__perf') {
            document.querySelector('[name="perf_period"]').value = '';
            document.querySelector('[name="perf_direction"]').value = '';
        } else {
            const el = document.querySelector(`[name="${key}"]`);
            if (el) el.value = '';
        }
        currentPage = 1;
        fetchResults();
    }

    document.getElementById('clear-all-btn').addEventListener('click', () => {
        document.querySelectorAll('.filter-input').forEach(el => { el.value = ''; });
        // Reset industry dropdown to full list
        industrySel.innerHTML = '<option value="">Visas industrijas</option>' +
            allIndustries.map(i => `<option value="${escapeHtml(i)}">${escapeHtml(i)}</option>`).join('');
        currentPage = 1;
        fetchResults();
    });

    function renderPagination(data) {
        const container = document.getElementById('pagination');
        if (!data.total || data.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        const cur = data.page, last = data.last_page;
        const pages = [];
        const push = p => pages.push(p);

        // Compact page list with ellipses
        const window = 1;
        push(1);
        if (cur - window > 2) push('…');
        for (let p = Math.max(2, cur - window); p <= Math.min(last - 1, cur + window); p++) push(p);
        if (cur + window < last - 1) push('…');
        if (last > 1) push(last);

        const btn = (label, page, active = false, disabled = false) => {
            const base = 'px-2.5 py-1 text-xs rounded border';
            if (disabled) return `<span class="${base} text-gray-300 border-gray-200 cursor-not-allowed">${label}</span>`;
            if (active) return `<span class="${base} bg-blue-600 text-white border-blue-600">${label}</span>`;
            return `<button type="button" data-page="${page}" class="${base} text-gray-700 border-gray-300 hover:border-blue-400 hover:text-blue-700">${label}</button>`;
        };

        let html = '<div class="flex items-center gap-1">';
        html += btn('«', cur - 1, false, cur === 1);
        for (const p of pages) {
            if (p === '…') html += `<span class="px-1 text-xs text-gray-400">…</span>`;
            else html += btn(p, p, p === cur);
        }
        html += btn('»', cur + 1, false, cur === last);
        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('button[data-page]').forEach(b => {
            b.addEventListener('click', () => {
                currentPage = parseInt(b.dataset.page);
                fetchResults();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    // ----- Search autocomplete popup -----
    const searchInput = document.getElementById('search-input');
    const popup = document.getElementById('search-popup');
    const popupList = document.getElementById('search-popup-list');
    let searchTimer = null;
    let searchReqCounter = 0;

    function handleSearchInput() {
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (!q) { hidePopup(); return; }
        searchTimer = setTimeout(async () => {
            const reqId = ++searchReqCounter;
            try {
                const r = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                if (!r.ok || reqId !== searchReqCounter) return;
                const data = await r.json();
                const rows = Array.isArray(data.data) ? data.data : [];
                if (rows.length === 0) {
                    popupList.innerHTML = '<li class="px-3 py-2 text-xs text-gray-500">Nav atrastu instrumentu.</li>';
                } else {
                    popupList.innerHTML = rows.map(item => {
                        const url = detailTpl.replace('__ID__', encodeURIComponent(item.id));
                        const exch = item.exchange ? `<span class="text-xs font-medium text-blue-700 bg-blue-50 rounded px-1.5">${escapeHtml(item.exchange)}</span>` : '';
                        return `<li><a href="${url}" class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-blue-50 border-b border-gray-100 last:border-b-0">
                            <span class="font-bold text-gray-900 w-14 shrink-0">${escapeHtml(item.ticker)}</span>
                            <span class="text-gray-600 truncate flex-1">${escapeHtml(item.company_name || '')}</span>
                            ${exch}
                        </a></li>`;
                    }).join('');
                }
                popup.classList.remove('hidden');
            } catch (_) { hidePopup(); }
        }, 180);
    }

    function hidePopup() {
        popup.classList.add('hidden');
        popupList.innerHTML = '';
    }

    searchInput.addEventListener('focus', () => { if (searchInput.value.trim()) handleSearchInput(); });
    document.addEventListener('click', (e) => {
        if (!popup.contains(e.target) && e.target !== searchInput) hidePopup();
    });
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { hidePopup(); searchInput.blur(); }
    });

    // ----- Helpers -----
    function escapeHtml(v) {
        return String(v ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function formatMoney(v) {
        v = parseFloat(v);
        if (!isFinite(v)) return '-';
        const abs = Math.abs(v), sign = v < 0 ? '-' : '';
        if (abs >= 1e12) return sign + '$' + (abs / 1e12).toFixed(1) + 'T';
        if (abs >= 1e9)  return sign + '$' + (abs / 1e9).toFixed(1) + 'B';
        if (abs >= 1e6)  return sign + '$' + (abs / 1e6).toFixed(1) + 'M';
        if (abs >= 1e3)  return sign + '$' + (abs / 1e3).toFixed(1) + 'K';
        return sign + '$' + abs.toFixed(2);
    }
    function formatVolume(v) {
        v = parseFloat(v);
        if (!isFinite(v)) return '-';
        if (v >= 1e9) return (v / 1e9).toFixed(1) + 'B';
        if (v >= 1e6) return (v / 1e6).toFixed(1) + 'M';
        if (v >= 1e3) return Math.round(v / 1e3) + 'K';
        return Math.round(v).toString();
    }
})();
</script>
@endsection
