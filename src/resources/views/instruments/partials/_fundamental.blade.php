        <section class="mt-8 rounded-xl border border-gray-200 bg-white p-5 sm:p-6 shadow-sm">
            <div class="flex gap-4 lg:gap-6">
                <aside class="shrink-0 w-[160px] rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <div id="fundamentals-year-list" class="space-y-2"></div>
                </aside>

                <div class="min-w-0 flex-1 flex flex-col">
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <div id="fundamentals-statement-tabs" class="inline-flex flex-wrap rounded-lg border border-gray-300 overflow-hidden">
                            <button
                                type="button"
                                data-statement="balance_sheet"
                                class="fundamentals-statement-btn px-4 py-2 text-sm font-medium text-gray-700 bg-white border-r border-gray-300"
                            >
                                Balance Sheet
                            </button>
                            <button
                                type="button"
                                data-statement="cash_flow_statement"
                                class="fundamentals-statement-btn px-4 py-2 text-sm font-medium text-gray-700 bg-white border-r border-gray-300"
                            >
                                Cash Flow Statement
                            </button>
                            <button
                                type="button"
                                data-statement="income_statement"
                                class="fundamentals-statement-btn px-4 py-2 text-sm font-medium text-gray-700 bg-white"
                            >
                                Income Statement (P&amp;L)
                            </button>
                        </div>
                        <p id="fundamentals-period-title" class="text-sm font-medium text-gray-500"></p>
                    </div>
                    <div id="fundamentals-content" class="grow overflow-auto"></div>
                </div>
            </div>
        </section>
    </div>
</div>


<script>
    (function () {
        const yearList = document.getElementById('fundamentals-year-list');
        const statementTabs = document.getElementById('fundamentals-statement-tabs');
        const periodTitle = document.getElementById('fundamentals-period-title');
        const content = document.getElementById('fundamentals-content');

        if (!yearList || !statementTabs || !periodTitle || !content) {
            return;
        }

        const fallbackYearsRaw = @json($availableFundamentalYears ?? []);
        const fallbackYears = Array.isArray(fallbackYearsRaw) ? fallbackYearsRaw : [];
        const rawFundamentals = @json($fundamentalData ?? []);
        const statementOrder = ['balance_sheet', 'cash_flow_statement', 'income_statement'];
        const statementLabels = {
            balance_sheet: 'Balance Sheet',
            cash_flow_statement: 'Cash Flow Statement',
            income_statement: 'Income Statement (P&L)',
        };
        let selectedStatement = 'balance_sheet';
        let selectedPeriod = 'annual';
        let fundamentalsByYear = normalizeFundamentals(rawFundamentals, fallbackYears);
        let years = sortYearsDesc(Object.keys(fundamentalsByYear));
        let selectedYear = years[0] || null;

        /* ── Abbreviation map for long labels (tooltip shows full) ── */
        const abbreviations = {
            'Accumulated Depreciation Depletion And Amortization Property Plant And Equipment': 'Accum. Depr. PP&E',
            'Accumulated Depreciation Depletion And Amortization Property Plant And Equipment And Capitalized Software': 'Accum. Depr. PP&E + SW',
            'Accumulated Other Comprehensive Income Loss Net Of Tax': 'Accum. OCI (Net)',
            'Available For Sale Securities Accumulated Gross Unrealized Gain Before Tax': 'AFS Unrealized Gain',
            'Available For Sale Securities Accumulated Gross Unrealized Loss Before Tax': 'AFS Unrealized Loss',
            'Available For Sale Securities Amortized Cost': 'AFS Amortized Cost',
            'Cash Cash Equivalents And Marketable Securities Held By Foreign Subsidiaries': 'Foreign Cash & Securities',
            'Deferred Revenue Current': 'Deferred Revenue (Curr.)',
            'Deferred Revenue Non Current': 'Deferred Revenue (Non-Curr.)',
            'Deferred Tax Assets Deferred Cost Sharing': 'DTA Cost Sharing',
            'Deferred Tax Assets Unrealized Losses': 'DTA Unrealized Losses',
            'Effective Income Tax Rate Continuing Operations': 'Eff. Tax Rate',
            'Effective Income Tax Rate Reconciliation At Federal Statutory Income Tax Rate': 'Fed. Statutory Tax Rate',
            'Weighted Average Number Of Diluted Shares Outstanding': 'Wtd. Avg. Diluted Shares',
            'Weighted Average Number Of Shares Outstanding Basic': 'Wtd. Avg. Basic Shares',
            'Other Comprehensive Income Loss Foreign Currency Transaction And Translation Adjustment Net Of Tax': 'FX Translation Adj.',
            'Other Comprehensive Income Unrealized Holding Gain Loss On Securities Arising During Period Net Of Tax': 'OCI Securities Gain/Loss',
        };
        const MAX_LABEL_LEN = 38;

        function abbrev(label) {
            if (abbreviations[label]) return { short: abbreviations[label], full: label };
            if (label.length <= MAX_LABEL_LEN) return { short: label, full: null };
            return { short: label.slice(0, MAX_LABEL_LEN - 1) + '\u2026', full: label };
        }

        /* ── Group definitions ── */
        const balanceSheetGroups = {
            left: [
                { title: 'Assets', labels: ['Total Assets', 'Current Assets', 'Cash & Equivalents', 'Short-term Investments', 'Accounts Receivable', 'Inventory', 'PP&E (Net)', 'Goodwill', 'Intangible Assets'] },
            ],
            right: [
                { title: 'Liabilities', labels: ['Total Liabilities', 'Current Liabilities', 'Accounts Payable', 'Long-term Debt', 'Total Long-term Debt'] },
                { title: 'Equity', labels: ["Stockholders' Equity", 'Retained Earnings', 'Shares Outstanding', 'Total Liabilities & Equity'] },
            ],
        };
        const incomeGroups = [
            { title: 'Revenue & Costs', labels: ['Revenue', 'Cost of Revenue', 'Gross Profit'] },
            { title: 'Operating', labels: ['R&D Expense', 'SG&A Expense', 'Operating Expenses', 'Operating Income'] },
            { title: 'Other & Taxes', labels: ['Non-operating Income', 'Interest Expense', 'Income Tax'] },
            { title: 'Bottom Line', labels: ['Net Income', 'Comprehensive Income', 'EPS (Basic)', 'EPS (Diluted)'] },
        ];
        const cashFlowGroups = [
            { title: 'Operating', labels: ['Operating Cash Flow', 'Depreciation & Amortization', 'Stock-based Compensation'] },
            { title: 'Investing', labels: ['Investing Cash Flow', 'Capital Expenditures'] },
            { title: 'Financing', labels: ['Financing Cash Flow', 'Dividends Paid', 'Share Buybacks'] },
            { title: 'Net', labels: ['Net Change in Cash'] },
        ];

        /* ── Normalization helpers ── */
        function createEmptyStatementSet() {
            return { balance_sheet: {}, cash_flow_statement: {}, income_statement: {} };
        }
        function ensureYear(store, year) {
            if (!store[year]) store[year] = { annual: createEmptyStatementSet(), quarters: {} };
            return store[year];
        }
        function normalizeStatementKey(key) {
            const v = String(key || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
            if (v === 'cash_flow' || v === 'cashflow' || v === 'cash_flow_statement') return 'cash_flow_statement';
            if (v === 'income_statement' || v === 'income' || v === 'income_statement_(p&l)' || v === 'p&l' || v === 'pl') return 'income_statement';
            return v === 'balance_sheet' ? 'balance_sheet' : null;
        }
        function normalizeQuarterKey(value) {
            const raw = String(value || '').trim().toUpperCase().replace(/\s+/g, '');
            if (raw === '' || raw === 'ANNUAL' || raw === 'FY' || raw === 'YEAR') return 'annual';
            const m = raw.match(/Q?([1-4])/);
            return m ? `Q${m[1]}` : 'annual';
        }
        function normalizeStatementPayload(p) {
            return p && typeof p === 'object' && !Array.isArray(p) ? p : {};
        }
        function normalizeFundamentals(raw, yearsFallback) {
            const n = {};
            yearsFallback.forEach((y) => { const ny = String(y||'').trim(); if (/^\d{4}$/.test(ny)) ensureYear(n, ny); });
            if (!raw || typeof raw !== 'object') return n;
            if (Array.isArray(raw)) {
                raw.forEach((row) => {
                    if (!row || typeof row !== 'object') return;
                    const year = String(row.year||'').trim();
                    if (!/^\d{4}$/.test(year)) return;
                    const sk = normalizeStatementKey(row.statement_type || row.statement);
                    if (!sk) return;
                    const pk = normalizeQuarterKey(row.quarter || row.period);
                    const payload = normalizeStatementPayload(row.data || row.values || row.metrics || row.payload);
                    const yn = ensureYear(n, year);
                    if (pk === 'annual') { yn.annual[sk] = payload; return; }
                    if (!yn.quarters[pk]) yn.quarters[pk] = createEmptyStatementSet();
                    yn.quarters[pk][sk] = payload;
                });
                return n;
            }
            Object.entries(raw).forEach(([year, yv]) => {
                const ny = String(year||'').trim();
                if (!/^\d{4}$/.test(ny)) return;
                const yn = ensureYear(n, ny);
                if (!yv || typeof yv !== 'object') return;
                const an = (yv.annual && typeof yv.annual === 'object') ? yv.annual : yv;
                statementOrder.forEach((sk) => { yn.annual[sk] = normalizeStatementPayload(an[sk]); });
                const qn = (yv.quarters && typeof yv.quarters === 'object') ? yv.quarters : {};
                Object.entries(qn).forEach(([q, qv]) => {
                    const nq = normalizeQuarterKey(q);
                    if (nq === 'annual') return;
                    if (!yn.quarters[nq]) yn.quarters[nq] = createEmptyStatementSet();
                    if (!qv || typeof qv !== 'object') return;
                    statementOrder.forEach((sk) => { yn.quarters[nq][sk] = normalizeStatementPayload(qv[sk]); });
                });
            });
            return n;
        }
        function sortYearsDesc(yk) { return yk.filter((y) => /^\d{4}$/.test(y)).sort((a,b) => Number(b)-Number(a)); }
        function getQuarterKeys(year) {
            const qm = fundamentalsByYear[year]?.quarters || {};
            return Object.keys(qm).sort((a,b) => Number(a.slice(1))-Number(b.slice(1)));
        }

        /* ── Formatting helpers ── */
        function escapeHtml(v) { return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
        function parseMaybeNumber(v) {
            if (typeof v === 'number' && Number.isFinite(v)) return v;
            if (typeof v === 'string' && v.trim() !== '') { const n = Number(v.replace(/,/g,'')); if (Number.isFinite(n)) return n; }
            return null;
        }
        function formatValue(v) {
            if (v === null || typeof v === 'undefined') return '\u2014';
            const n = parseMaybeNumber(v);
            if (n !== null) {
                const abs = Math.abs(n);
                if (abs >= 1e9) return (n < 0 ? '-' : '') + '$' + (abs / 1e9).toFixed(2) + 'B';
                if (abs >= 1e6) return (n < 0 ? '-' : '') + '$' + (abs / 1e6).toFixed(2) + 'M';
                if (abs >= 1e3) return (n < 0 ? '-' : '') + '$' + (abs / 1e3).toFixed(2) + 'K';
                return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: abs >= 1 ? 2 : 4 }).format(n);
            }
            if (typeof v === 'boolean') return v ? 'true' : 'false';
            return String(v);
        }
        function formatValueRaw(v) {
            const n = parseMaybeNumber(v);
            if (n !== null) return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(n);
            return '';
        }
        function flattenRows(source) {
            if (!source || typeof source !== 'object' || Array.isArray(source)) return [];
            let rows = [];
            Object.entries(source).forEach(([k, v]) => {
                if (v && typeof v === 'object' && !Array.isArray(v)) { rows = rows.concat(flattenRows(v)); return; }
                rows.push([k, v]);
            });
            return rows;
        }

        /* ── Render helpers ── */
        function renderRow(label, value) {
            const a = abbrev(label);
            const titleAttr = a.full ? ` title="${escapeHtml(a.full)}"` : '';
            const rawTitle = formatValueRaw(value);
            const valTitle = rawTitle ? ` title="${escapeHtml(rawTitle)}"` : '';
            return `<tr class="hover:bg-gray-50/70">
                <td class="pl-3 pr-2 py-1.5 text-gray-700 text-[13px] whitespace-nowrap"${titleAttr}>${escapeHtml(a.short)}</td>
                <td class="pr-3 pl-2 py-1.5 text-right tabular-nums text-gray-900 font-medium text-[13px] whitespace-nowrap"${valTitle}>${escapeHtml(formatValue(value))}</td>
            </tr>`;
        }
        function renderGroupedMiniTable(groups, rowMap, used) {
            let html = '';
            groups.forEach((g) => {
                const matched = g.labels.filter((l) => l in rowMap);
                if (matched.length === 0) return;
                matched.forEach((l) => used.add(l));
                html += `<tr><td colspan="2" class="bg-gray-50 px-3 py-1 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-t border-gray-200">${escapeHtml(g.title)}</td></tr>`;
                html += matched.map((l) => renderRow(l, rowMap[l])).join('');
            });
            return html;
        }
        function wrapTable(bodyHtml) {
            return `<div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm"><tbody class="divide-y divide-gray-100 bg-white">${bodyHtml}</tbody></table></div>`;
        }
        function renderOtherGrid(otherRows) {
            if (otherRows.length === 0) return '';
            const cols = 3;
            let rowsHtml = '';
            for (let i = 0; i < otherRows.length; i += cols) {
                const chunk = otherRows.slice(i, i + cols);
                let cellsHtml = '';
                chunk.forEach(([l, v]) => {
                    const a = abbrev(l);
                    const titleAttr = a.full ? ` title="${escapeHtml(a.full)}"` : '';
                    const rawTitle = formatValueRaw(v);
                    const valTitle = rawTitle ? ` title="${escapeHtml(rawTitle)}"` : '';
                    cellsHtml += `<td class="px-3 py-1.5 text-[12px] text-gray-600 whitespace-nowrap"${titleAttr}>${escapeHtml(a.short)}</td>
                        <td class="px-2 py-1.5 text-[12px] text-right tabular-nums text-gray-800 font-medium whitespace-nowrap"${valTitle}>${escapeHtml(formatValue(v))}</td>`;
                });
                const empty = cols - chunk.length;
                for (let j = 0; j < empty; j++) cellsHtml += '<td></td><td></td>';
                rowsHtml += `<tr class="hover:bg-gray-50/70">${cellsHtml}</tr>`;
            }
            // Other header inside table (so it scrolls with the table when overflowing)
            return `<div class="mt-3 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr><td colspan="6" class="bg-gray-50 px-3 py-1 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-200">Other</td></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">${rowsHtml}</tbody>
                </table></div>`;
        }

        /* ── Year list ── */
        function renderYearList() {
            if (years.length === 0) {
                yearList.innerHTML = '<p class="text-xs text-gray-500 px-1 py-1">No years</p>';
                return;
            }
            yearList.innerHTML = years.map((year) => {
                const isActive = year === selectedYear;
                const quarters = getQuarterKeys(year);
                const yCls = isActive ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:border-blue-300 hover:text-blue-700';
                let periodHtml = '';
                if (isActive) {
                    const periods = quarters.map((q) => ({ key: q, label: q }));
                    periods.push({ key: 'annual', label: 'FY' });
                    periodHtml = `<div class="mt-1 grid gap-1" style="grid-template-columns:repeat(${periods.length},1fr)">${periods.map((p) => {
                        const pCls = selectedPeriod === p.key
                            ? 'bg-slate-700 text-white border-slate-700'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-blue-300 hover:text-blue-700';
                        return `<button type="button" data-role="period" data-year="${escapeHtml(year)}" data-period="${escapeHtml(p.key)}" class="rounded border py-0.5 text-[11px] font-semibold text-center transition-colors ${pCls}">${escapeHtml(p.label)}</button>`;
                    }).join('')}</div>`;
                }
                return `<div>
                    <button type="button" data-role="year" data-year="${escapeHtml(year)}" class="block w-full rounded-md border px-2.5 py-1.5 text-left text-sm font-semibold transition-colors ${yCls}">${escapeHtml(year)}</button>
                    ${periodHtml}
                </div>`;
            }).join('');
        }

        /* ── Statement tabs ── */
        function renderStatementTabs() {
            const buttons = Array.from(statementTabs.querySelectorAll('.fundamentals-statement-btn'));
            buttons.forEach((btn, i) => {
                const isActive = btn.dataset.statement === selectedStatement;
                btn.classList.toggle('bg-slate-700', isActive);
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('bg-white', !isActive);
                btn.classList.toggle('text-gray-700', !isActive);
                btn.classList.toggle('border-r', i < buttons.length - 1);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        /* ── Main content ── */
        function renderContent() {
            if (!selectedYear || !fundamentalsByYear[selectedYear]) {
                periodTitle.textContent = '';
                content.innerHTML = '<div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">No fundamental data available for this instrument.</div>';
                return;
            }

            const yearNode = fundamentalsByYear[selectedYear];
            const periodLabel = selectedPeriod === 'annual'
                ? `${selectedYear} \u2014 Annual (10-K)`
                : `${selectedYear} \u2014 ${selectedPeriod} (10-Q)`;
            periodTitle.textContent = periodLabel;

            const source = selectedPeriod === 'annual' ? yearNode.annual : (yearNode.quarters[selectedPeriod] || createEmptyStatementSet());
            const statementData = source[selectedStatement] || {};
            const allRows = flattenRows(statementData);

            if (allRows.length === 0) {
                content.innerHTML = `<div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-600">No ${escapeHtml(statementLabels[selectedStatement] || selectedStatement)} data for the selected period.</div>`;
                return;
            }

            const rowMap = {};
            allRows.forEach(([l, v]) => { rowMap[l] = v; });
            const used = new Set();

            if (selectedStatement === 'balance_sheet') {
                /* Balance sheet: two-column layout — Assets left, Liabilities+Equity right */
                let leftHtml = renderGroupedMiniTable(balanceSheetGroups.left, rowMap, used);
                let rightHtml = renderGroupedMiniTable(balanceSheetGroups.right, rowMap, used);

                const other = allRows.filter(([l]) => !used.has(l));

                content.innerHTML = `
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                        <div>${wrapTable(leftHtml)}</div>
                        <div>${wrapTable(rightHtml)}</div>
                    </div>
                    ${renderOtherGrid(other)}
                `;
            } else {
                /* Income / Cash Flow: multi-column card grid */
                const groups = selectedStatement === 'income_statement' ? incomeGroups : cashFlowGroups;

                let cardsHtml = '';
                groups.forEach((g) => {
                    const matched = g.labels.filter((l) => l in rowMap);
                    if (matched.length === 0) return;
                    matched.forEach((l) => used.add(l));
                    cardsHtml += `<div class="overflow-x-auto rounded-lg border border-gray-200">
                        <div class="bg-gray-50 px-3 py-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-200">${escapeHtml(g.title)}</div>
                        <table class="min-w-full text-sm"><tbody class="divide-y divide-gray-100 bg-white">
                            ${matched.map((l) => renderRow(l, rowMap[l])).join('')}
                        </tbody></table>
                    </div>`;
                });

                const other = allRows.filter(([l]) => !used.has(l));

                content.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">${cardsHtml}</div>
                    ${renderOtherGrid(other)}
                `;
            }
        }

        /* ── Events ── */
        yearList.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-role]');
            if (!btn) return;
            if (btn.dataset.role === 'year') {
                const wasSelected = selectedYear === btn.dataset.year;
                selectedYear = btn.dataset.year || null;
                selectedPeriod = wasSelected ? selectedPeriod : 'annual';
                renderYearList();
                renderContent();
            } else if (btn.dataset.role === 'period') {
                selectedYear = btn.dataset.year || null;
                selectedPeriod = btn.dataset.period || 'annual';
                renderYearList();
                renderContent();
            }
        });
        statementTabs.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-statement]');
            if (!btn) return;
            const next = normalizeStatementKey(btn.dataset.statement);
            if (!next) return;
            selectedStatement = next;
            renderStatementTabs();
            renderContent();
        });

        if (selectedYear && !fundamentalsByYear[selectedYear]) selectedYear = years[0] || null;
        renderYearList();
        renderStatementTabs();
        renderContent();
    })();
</script>
