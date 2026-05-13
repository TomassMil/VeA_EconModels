{{--
    Middle column: search box + instrument list.
    Klikšķis uz instrumenta navigē uz `$routeName` ar attiecīgo instrumenta ID.

    Vajadzīgie mainīgie:
    - $instruments     (Collection of initial 50 instruments)
    - $routeName       ('fundamentals.show' vai 'technical.show')
    - $instrument      (selected instrument, var būt null)
--}}
<div class="h-full flex flex-col">
    {{-- Search box + filter toggle --}}
    <div class="p-3 border-b border-gray-200 bg-gray-50">
        <div class="relative">
            <input
                type="text"
                id="screener-search"
                placeholder="Meklēt: ticker vai uzņēmums..."
                autocomplete="off"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
            >
            <div id="screener-search-spinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>
        <div class="flex items-center justify-between mt-2 gap-2">
            <p class="text-[10px] text-gray-500"><span id="screener-count">{{ number_format($total ?? 0) }}</span> instrumenti</p>
            <button type="button" id="filter-toggle"
                    class="text-[11px] font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtri <span id="filter-active-count" class="hidden ml-1 bg-blue-100 text-blue-700 px-1.5 rounded-full text-[10px]">0</span>
            </button>
        </div>
    </div>

    {{-- Collapsible filter panel --}}
    <div id="filter-panel" class="hidden border-b border-gray-200 bg-white">
        {{-- Tabs --}}
        <div class="flex border-b border-gray-200 text-xs">
            <button type="button" data-tab="descriptive" class="filter-tab flex-1 px-2 py-2 font-medium border-b-2 border-blue-500 text-blue-700">Apraksts</button>
            <button type="button" data-tab="fundamental" class="filter-tab flex-1 px-2 py-2 font-medium border-b-2 border-transparent text-gray-600">Fundament.</button>
            <button type="button" data-tab="technical"   class="filter-tab flex-1 px-2 py-2 font-medium border-b-2 border-transparent text-gray-600">Tehnisk.</button>
        </div>

        {{-- Descriptive tab --}}
        <div class="filter-tab-content p-3 space-y-2" data-tab="descriptive">
            <div>
                <label class="text-[10px] uppercase text-gray-500 font-semibold">Sektors</label>
                <select id="filter-sector" class="w-full mt-0.5 rounded border border-gray-300 px-2 py-1 text-xs">
                    <option value="">— visi —</option>
                    @foreach ($sectors ?? [] as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] uppercase text-gray-500 font-semibold">Industrija</label>
                <select id="filter-industry" class="w-full mt-0.5 rounded border border-gray-300 px-2 py-1 text-xs">
                    <option value="">— visas —</option>
                </select>
            </div>
        </div>

        {{-- Fundamental tab --}}
        <div class="filter-tab-content hidden p-3 space-y-2" data-tab="fundamental">
            @foreach (['revenue' => 'Ieņēmumi', 'net_income' => 'Tīrā peļņa', 'total_assets' => 'Aktīvi', 'total_equity' => 'Pašu kapitāls'] as $key => $label)
                <div>
                    <label class="text-[10px] uppercase text-gray-500 font-semibold">{{ $label }} ($)</label>
                    <div class="flex gap-1 mt-0.5">
                        <input type="number" data-filter="{{ $key }}_min" placeholder="min" class="w-1/2 rounded border border-gray-300 px-2 py-1 text-xs">
                        <input type="number" data-filter="{{ $key }}_max" placeholder="max" class="w-1/2 rounded border border-gray-300 px-2 py-1 text-xs">
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Technical tab --}}
        <div class="filter-tab-content hidden p-3 space-y-2" data-tab="technical">
            <div>
                <label class="text-[10px] uppercase text-gray-500 font-semibold">Cena ($)</label>
                <div class="flex gap-1 mt-0.5">
                    <input type="number" data-filter="price_min" placeholder="min" class="w-1/2 rounded border border-gray-300 px-2 py-1 text-xs">
                    <input type="number" data-filter="price_max" placeholder="max" class="w-1/2 rounded border border-gray-300 px-2 py-1 text-xs">
                </div>
            </div>
            <div>
                <label class="text-[10px] uppercase text-gray-500 font-semibold">Vid. apjoms (shares/diena)</label>
                <div class="flex gap-1 mt-0.5">
                    <input type="number" data-filter="volume_min" placeholder="min" class="w-1/2 rounded border border-gray-300 px-2 py-1 text-xs">
                    <input type="number" data-filter="volume_max" placeholder="max" class="w-1/2 rounded border border-gray-300 px-2 py-1 text-xs">
                </div>
            </div>
        </div>

        {{-- "Clear all" appears only when filters are active --}}
        <div id="filter-clear-bar" class="hidden flex items-center justify-end p-2 border-t border-gray-100 bg-gray-50">
            <button type="button" id="filter-clear"
                    class="text-[11px] text-gray-500 hover:text-red-600 flex items-center gap-1 transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Notīrīt visus filtrus
            </button>
        </div>
    </div>

    {{-- Instruments list --}}
    <div id="screener-list" class="flex-1 overflow-y-auto">
        @foreach ($instruments as $inst)
            <a href="{{ route($routeName, $inst->id) }}"
               class="screener-row block px-3 py-2 border-b border-gray-100 hover:bg-blue-50 transition-colors {{ ($instrument ?? null) && $instrument->id === $inst->id ? 'bg-blue-100 border-l-4 border-l-blue-500' : '' }}">
                <div class="flex items-baseline justify-between gap-2">
                    <span class="font-semibold text-gray-900 text-sm">{{ $inst->ticker }}</span>
                    @if (!empty($inst->sector))
                        <span class="text-[10px] text-gray-500 truncate">{{ $inst->sector }}</span>
                    @endif
                </div>
                @if ($inst->company_name)
                    <p class="text-xs text-gray-600 truncate">{{ $inst->company_name }}</p>
                @endif
            </a>
        @endforeach
        <div id="screener-empty" class="hidden p-4 text-center text-sm text-gray-400">Nav rezultātu</div>
    </div>
</div>

<script>
(function () {
    const searchInput = document.getElementById('screener-search');
    const spinnerEl = document.getElementById('screener-search-spinner');
    const listEl = document.getElementById('screener-list');
    const emptyEl = document.getElementById('screener-empty');
    const initialHtml = listEl.innerHTML;       // preserve initial server-rendered list
    let searchTimer = null;

    const routeTemplate = '{{ route($routeName, ["instrument" => "__ID__"]) }}';

    function buildRowHtml(inst, selectedId) {
        const isActive = selectedId && parseInt(selectedId) === inst.id;
        const href = routeTemplate.replace('__ID__', inst.id);
        const sectorBadge = inst.sector ? `<span class="text-[10px] text-gray-500 truncate">${inst.sector}</span>` : '';
        const companyLine = inst.company_name ? `<p class="text-xs text-gray-600 truncate">${inst.company_name}</p>` : '';
        return `
            <a href="${href}" class="screener-row block px-3 py-2 border-b border-gray-100 hover:bg-blue-50 transition-colors ${isActive ? 'bg-blue-100 border-l-4 border-l-blue-500' : ''}">
                <div class="flex items-baseline justify-between gap-2">
                    <span class="font-semibold text-gray-900 text-sm">${inst.ticker}</span>
                    ${sectorBadge}
                </div>
                ${companyLine}
            </a>
        `;
    }

    /* ─── Search (debounced AJAX, replaces list) ─── */
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();

        if (q === '') {
            listEl.innerHTML = initialHtml;
            emptyEl.classList.add('hidden');
            return;
        }

        spinnerEl.classList.remove('hidden');
        searchTimer = setTimeout(() => {
            fetch(`{{ route('instruments.search') }}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(json => {
                    spinnerEl.classList.add('hidden');
                    const items = json.data || [];
                    if (!items.length) {
                        listEl.innerHTML = '';
                        emptyEl.classList.remove('hidden');
                        listEl.appendChild(emptyEl);
                        return;
                    }
                    listEl.innerHTML = items.map(i => buildRowHtml(i, null)).join('');
                })
                .catch(() => spinnerEl.classList.add('hidden'));
        }, 200);
    });

    /* ─── AJAX navigation: click on .screener-row or [data-ajax-link] ─── */
    // Lazy lookup — right column is parsed AFTER the middle column, so it may not exist
    // when this IIFE first runs. We look it up at click time instead.
    function getRightCol() {
        return document.getElementById('right-column-content');
    }

    function loadRightColumn(url, opts = {}) {
        const pushHistory = opts.pushHistory !== false;
        const rightCol = getRightCol();
        if (!rightCol) {
            console.error('right-column-content not found, falling back to full nav');
            window.location.href = url;
            return;
        }

        // Preserve current URL query (filter state) on the target URL
        const targetUrl = new URL(url, window.location.origin);
        new URLSearchParams(window.location.search).forEach((v, k) => {
            if (!targetUrl.searchParams.has(k)) targetUrl.searchParams.set(k, v);
        });
        const finalUrl = targetUrl.toString();

        // Show loading state
        rightCol.style.opacity = '0.5';
        rightCol.style.pointerEvents = 'none';

        fetch(finalUrl, {
            headers: { 'X-Right-Only': '1', 'Accept': 'text/html' },
        })
        .then(async r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(html => {
            // Replace right column DOM
            rightCol.innerHTML = html;

            // Re-execute scripts (innerHTML doesn't auto-run them)
            rightCol.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Update history (with preserved query)
            if (pushHistory) {
                history.pushState({ url: finalUrl }, '', finalUrl);
            }

            // Update active row in middle column
            updateActiveRow(finalUrl);

            // Restore right col interaction
            rightCol.style.opacity = '';
            rightCol.style.pointerEvents = '';

            // Scroll right column to top
            rightCol.parentElement.scrollTop = 0;
        })
        .catch(err => {
            console.error('AJAX load failed:', err);
            rightCol.style.opacity = '';
            rightCol.style.pointerEvents = '';
            // Fallback: full page navigation
            window.location.href = url;
        });
    }

    function updateActiveRow(url) {
        // Extract instrument id from URL (last segment)
        const match = url.match(/\/(\d+)$/);
        const id = match ? parseInt(match[1]) : null;

        listEl.querySelectorAll('a.screener-row').forEach(row => {
            const rowId = row.getAttribute('href').match(/\/(\d+)$/);
            const isActive = rowId && id && parseInt(rowId[1]) === id;
            row.classList.toggle('bg-blue-100', isActive);
            row.classList.toggle('border-l-4', isActive);
            row.classList.toggle('border-l-blue-500', isActive);
        });
    }

    // Intercept clicks on screener rows AND on data-ajax-link buttons in right column.
    // Attached to both listEl (middle column) and document (for data-ajax-link buttons elsewhere).
    function handleAjaxClick(e) {
        const link = e.target.closest('a.screener-row, a[data-ajax-link]');
        if (!link) return;
        // Allow modifier-clicks (cmd/ctrl/middle-click) to open in new tab
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;

        e.preventDefault();
        e.stopPropagation();
        loadRightColumn(link.href);
    }
    listEl.addEventListener('click', handleAjaxClick);
    document.addEventListener('click', handleAjaxClick);

    // Browser back/forward
    window.addEventListener('popstate', function (e) {
        const url = (e.state && e.state.url) || window.location.href;
        loadRightColumn(url, { pushHistory: false });
    });

    // Save initial state so back works from first AJAX nav
    if (!history.state) {
        history.replaceState({ url: window.location.href }, '', window.location.href);
    }

    /* ─── Filter panel toggle + tab switching ─── */
    const filterPanel = document.getElementById('filter-panel');
    const filterToggle = document.getElementById('filter-toggle');
    filterToggle.addEventListener('click', () => filterPanel.classList.toggle('hidden'));

    document.querySelectorAll('.filter-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            document.querySelectorAll('.filter-tab').forEach(b => {
                const active = b.dataset.tab === tab;
                b.classList.toggle('border-blue-500', active);
                b.classList.toggle('text-blue-700', active);
                b.classList.toggle('border-transparent', !active);
                b.classList.toggle('text-gray-600', !active);
            });
            document.querySelectorAll('.filter-tab-content').forEach(panel => {
                panel.classList.toggle('hidden', panel.dataset.tab !== tab);
            });
        });
    });

    /* ─── Industry depends on sector ─── */
    const industryBySector = @json($industryBySector ?? new \stdClass);
    const sectorEl = document.getElementById('filter-sector');
    const industryEl = document.getElementById('filter-industry');
    sectorEl.addEventListener('change', () => {
        const sector = sectorEl.value;
        industryEl.innerHTML = '<option value="">— visas —</option>';
        if (sector && industryBySector[sector]) {
            industryBySector[sector].forEach(ind => {
                const opt = document.createElement('option');
                opt.value = ind;
                opt.textContent = ind;
                industryEl.appendChild(opt);
            });
        }
    });

    /* ─── Apply filters ─── */
    const filterClear = document.getElementById('filter-clear');
    const filterClearBar = document.getElementById('filter-clear-bar');
    const filterActiveCount = document.getElementById('filter-active-count');
    const screenerCount = document.getElementById('screener-count');

    function collectFilters() {
        const params = new URLSearchParams();
        const sector = sectorEl.value;
        const industry = industryEl.value;
        if (sector) params.set('sector', sector);
        if (industry) params.set('industry', industry);

        document.querySelectorAll('[data-filter]').forEach(inp => {
            if (inp.value) params.set(inp.dataset.filter, inp.value);
        });
        return params;
    }

    function updateActiveCount(params) {
        const count = [...params.keys()].length;
        if (count > 0) {
            filterActiveCount.textContent = count;
            filterActiveCount.classList.remove('hidden');
            filterClearBar.classList.remove('hidden');
            filterClearBar.classList.add('flex');
        } else {
            filterActiveCount.classList.add('hidden');
            filterClearBar.classList.add('hidden');
            filterClearBar.classList.remove('flex');
        }
    }

    function applyFilters(updateUrl = true) {
        const params = collectFilters();
        updateActiveCount(params);

        // Save filter state to URL (preserves across mode switches)
        if (updateUrl) {
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.replaceState(history.state, '', newUrl);
        }

        // If no filters → restore initial list
        if ([...params.keys()].length === 0) {
            listEl.innerHTML = initialHtml;
            emptyEl.classList.add('hidden');
            screenerCount.textContent = '{{ number_format($total ?? 0) }}';
            return;
        }

        spinnerEl.classList.remove('hidden');
        fetch(`{{ route('instruments.filter') }}?${params.toString()}`)
            .then(r => r.json())
            .then(json => {
                spinnerEl.classList.add('hidden');
                const items = json.data || [];
                screenerCount.textContent = json.total?.toLocaleString() ?? items.length;
                if (!items.length) {
                    listEl.innerHTML = '';
                    emptyEl.classList.remove('hidden');
                    listEl.appendChild(emptyEl);
                    return;
                }
                listEl.innerHTML = items.map(i => buildRowHtml(i, null)).join('');
            })
            .catch(() => spinnerEl.classList.add('hidden'));
    }

    filterClear.addEventListener('click', () => {
        sectorEl.value = '';
        industryEl.innerHTML = '<option value="">— visas —</option>';
        document.querySelectorAll('[data-filter]').forEach(inp => inp.value = '');
        applyFilters();         // refreshes list + clears URL
    });

    /* ─── Auto-apply: triggers filter when any input changes (debounced) ─── */
    let autoApplyTimer = null;
    function scheduleAutoApply() {
        clearTimeout(autoApplyTimer);
        autoApplyTimer = setTimeout(() => applyFilters(), 400);
    }
    // Selects: change event (industry needs to populate after sector → small delay)
    sectorEl.addEventListener('change', scheduleAutoApply);
    industryEl.addEventListener('change', scheduleAutoApply);
    document.querySelectorAll('[data-filter]').forEach(inp => {
        inp.addEventListener('input', scheduleAutoApply);
    });

    /* ─── Restore filter state from URL on page load ─── */
    const urlParams = new URLSearchParams(window.location.search);
    if ([...urlParams.keys()].length > 0) {
        // Restore sector + manually populate industry dropdown (no dispatchEvent, to avoid double auto-apply)
        if (urlParams.has('sector')) {
            const sector = urlParams.get('sector');
            sectorEl.value = sector;
            industryEl.innerHTML = '<option value="">— visas —</option>';
            if (industryBySector[sector]) {
                industryBySector[sector].forEach(ind => {
                    const opt = document.createElement('option');
                    opt.value = ind;
                    opt.textContent = ind;
                    industryEl.appendChild(opt);
                });
            }
        }
        if (urlParams.has('industry')) {
            industryEl.value = urlParams.get('industry');
        }
        document.querySelectorAll('[data-filter]').forEach(inp => {
            if (urlParams.has(inp.dataset.filter)) {
                inp.value = urlParams.get(inp.dataset.filter);
            }
        });
        filterPanel.classList.remove('hidden');   // show panel so user sees active filters
        applyFilters(false);                       // false = URL is already correct
    }

    /* ─── Preserve filter state (URL query) on:
       (1) Cross-link mode switch buttons in right column (data-mode-switch)
       (2) Sidebar Fundamentālie/Tehniskie links (data-preserve-query)
       Event delegation on document — works for AJAX-loaded buttons too. */
    document.addEventListener('click', e => {
        const link = e.target.closest('a[data-mode-switch], a[data-preserve-query]');
        if (!link) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;     // allow new tab

        e.preventDefault();
        const target = new URL(link.href);
        // Append current URL query (filter state) to target URL
        new URLSearchParams(window.location.search).forEach((v, k) => {
            target.searchParams.set(k, v);
        });
        window.location.href = target.toString();
    });
})();
</script>
