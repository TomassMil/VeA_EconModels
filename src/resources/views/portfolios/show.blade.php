@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <a href="{{ route('portfolios.index') }}" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Atpakaļ uz portfeļiem
        </a>

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">{{ $portfolio->name }}</h1>
            <button
                type="button"
                id="toggle-ledger-btn"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Darījumu vēsture
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ count($transactions) }}</span>
            </button>
        </div>

        {{-- Portfolio Chart + Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                {{-- Chart (left, ~60%) --}}
                <div class="lg:col-span-3">
                    <div class="flex items-baseline justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-700">Portfeļa vērtība laikā</h3>
                        <span class="text-xs text-gray-400">{{ $chart['resolution'] === 'weekly' ? 'Nedēļas' : 'Dienas' }}</span>
                    </div>
                    @if (count($chart['points']) > 1)
                        <div class="relative">
                            <svg id="portfolio-chart" viewBox="0 0 600 220" preserveAspectRatio="none" class="w-full h-48"></svg>
                            <div id="portfolio-chart-tooltip" class="absolute hidden pointer-events-none bg-gray-900 text-white text-xs rounded px-2 py-1 shadow-lg"></div>
                        </div>
                        <div id="portfolio-chart-axis" class="flex justify-between text-[10px] text-gray-400 mt-1 px-1"></div>
                    @else
                        <div class="h-48 flex items-center justify-center text-sm text-gray-400 border border-dashed border-gray-200 rounded">
                            Nav pietiekami datu, lai zīmētu grafiku
                        </div>
                    @endif
                </div>

                {{-- Stats (right, ~40%) --}}
                <div class="lg:col-span-2 grid grid-cols-2 lg:grid-cols-3 gap-4 content-center">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Portfeļa vērtība</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($summary->portfolio_value, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Brīvais kapitāls</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($summary->free_capital, 2) }}</p>
                    </div>
                    <div title="Kopā iemaksāts mīnus izmaksāts">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Iemaksāts</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($summary->net_deposits, 2) }}</p>
                    </div>
                    <div title="Atvērto pozīciju iegādes vērtība">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Investēts</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($summary->total_invested, 2) }}</p>
                    </div>
                    <div title="Atvērto pozīciju peļņa/zaudējumi pret iegādes cenu">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nerealizētā P&L</p>
                        <p class="text-xl font-bold mt-1 {{ $summary->unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $summary->unrealized_pnl >= 0 ? '+' : '' }}${{ number_format($summary->unrealized_pnl, 2) }}
                            <span class="text-xs font-medium">
                                ({{ $summary->unrealized_pnl_pct >= 0 ? '+' : '' }}{{ number_format($summary->unrealized_pnl_pct, 2) }}%)
                            </span>
                        </p>
                    </div>
                    <div title="Portfeļa vērtība mīnus iemaksāts (realizētā + nerealizētā peļņa)">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kopējā atdeve</p>
                        <p class="text-xl font-bold mt-1 {{ $summary->total_return >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $summary->total_return >= 0 ? '+' : '' }}${{ number_format($summary->total_return, 2) }}
                            <span class="text-xs font-medium">
                                ({{ $summary->total_return_pct >= 0 ? '+' : '' }}{{ number_format($summary->total_return_pct, 2) }}%)
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Instrument --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Pievienot instrumentu</h3>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                id="add-instrument-search"
                                placeholder="Meklēt pēc ticker vai nosaukuma..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                                autocomplete="off"
                            >
                            <div id="add-instrument-results" class="absolute z-20 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
                        </div>
                        <input
                            type="number"
                            id="add-instrument-amount"
                            placeholder="Summa ($)"
                            min="0.01"
                            step="0.01"
                            class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                        >
                        <button
                            type="button"
                            id="add-instrument-btn"
                            disabled
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors whitespace-nowrap"
                        >
                            Pievienot
                        </button>
                    </div>
                    <p id="add-instrument-selected" class="text-xs text-gray-500 mt-1 hidden">
                        Izvēlēts: <span class="font-medium text-gray-700"></span>
                    </p>
                    <p id="add-instrument-error" class="text-xs text-red-600 mt-1 hidden"></p>
            </div>
        </div>

        {{-- Instrument Cards --}}
        @if ($cards->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                <p class="text-gray-500">Portfelī nav instrumentu. Pievieno instrumentus izmantojot meklēšanu augstāk.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach ($cards as $i => $card)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow" id="card-{{ $card->instrument->id }}">
                        <div class="flex">
                            {{-- Left: Mini Chart --}}
                            <div class="w-[45%] p-4 flex flex-col justify-between border-r border-gray-100">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <a href="{{ route('instruments.show', $card->instrument) }}" class="text-sm font-bold text-blue-600 hover:text-blue-800">
                                            {{ $card->instrument->ticker }}
                                        </a>
                                        <button
                                            type="button"
                                            class="sell-instrument-btn text-xs font-medium text-gray-500 hover:text-orange-600 transition-colors flex items-center gap-1"
                                            data-instrument-id="{{ $card->instrument->id }}"
                                            data-ticker="{{ $card->instrument->ticker }}"
                                            data-shares="{{ $card->shares }}"
                                            data-price="{{ $card->current_close ?? '' }}"
                                            data-invested="{{ $card->amount_invested }}"
                                            title="Pārdot"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            Pārdot
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate mt-0.5">{{ $card->instrument->company_name }}</p>
                                </div>
                                <div class="mt-3">
                                    @if ($card->series->count() > 1)
                                        <svg id="minichart-{{ $i }}" viewBox="0 0 200 80" preserveAspectRatio="none" class="w-full h-16"></svg>
                                    @else
                                        <div class="h-16 flex items-center justify-center text-xs text-gray-400">Nav datu</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Right: Data --}}
                            <div class="w-[55%] p-4 text-sm space-y-2.5">
                                {{-- Top: price + volume --}}
                                <div class="grid grid-cols-2 gap-x-3">
                                    <div>
                                        <span class="text-xs text-gray-500">Cena</span>
                                        <p class="font-semibold text-gray-900">
                                            {{ $card->current_close !== null ? '$' . number_format($card->current_close, 2) : '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500">Apjoms</span>
                                        <p class="font-semibold text-gray-900">
                                            @if ($card->volume !== null)
                                                @if ($card->volume >= 1000000)
                                                    {{ number_format($card->volume / 1000000, 1) }}M
                                                @elseif ($card->volume >= 1000)
                                                    {{ number_format($card->volume / 1000, 1) }}K
                                                @else
                                                    {{ number_format($card->volume) }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                {{-- Middle: change strip (day | week | total since purchase) --}}
                                <div class="grid grid-cols-3 gap-x-2 border-t border-gray-100 pt-2">
                                    <div>
                                        <span class="text-[11px] text-gray-500">Diena</span>
                                        @if ($card->day_change_abs !== null)
                                            <p class="text-xs font-semibold {{ $card->day_change_abs >= 0 ? 'text-green-600' : 'text-red-600' }} leading-tight">
                                                {{ $card->day_change_abs >= 0 ? '+' : '' }}{{ number_format($card->day_change_abs, 2) }}
                                                <span class="text-[10px] font-normal">({{ $card->day_change_pct >= 0 ? '+' : '' }}{{ number_format($card->day_change_pct, 2) }}%)</span>
                                            </p>
                                        @else
                                            <p class="text-xs font-semibold text-gray-400">-</p>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-gray-500">Nedēļa</span>
                                        @if ($card->week_change_abs !== null)
                                            <p class="text-xs font-semibold {{ $card->week_change_abs >= 0 ? 'text-green-600' : 'text-red-600' }} leading-tight">
                                                {{ $card->week_change_abs >= 0 ? '+' : '' }}{{ number_format($card->week_change_abs, 2) }}
                                                <span class="text-[10px] font-normal">({{ $card->week_change_pct >= 0 ? '+' : '' }}{{ number_format($card->week_change_pct, 2) }}%)</span>
                                            </p>
                                        @else
                                            <p class="text-xs font-semibold text-gray-400">-</p>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-gray-500">Kopā</span>
                                        @if ($card->total_change_abs !== null)
                                            <p class="text-xs font-semibold {{ $card->total_change_abs >= 0 ? 'text-green-600' : 'text-red-600' }} leading-tight">
                                                {{ $card->total_change_abs >= 0 ? '+' : '' }}${{ number_format($card->total_change_abs, 2) }}
                                                <span class="text-[10px] font-normal">({{ $card->total_change_pct >= 0 ? '+' : '' }}{{ number_format($card->total_change_pct, 2) }}%)</span>
                                            </p>
                                        @else
                                            <p class="text-xs font-semibold text-gray-400">-</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Bottom: invested + weight --}}
                                <div class="grid grid-cols-2 gap-x-3 border-t border-gray-100 pt-2">
                                    <div>
                                        <span class="text-xs text-gray-500">Investēts</span>
                                        <p class="font-semibold text-gray-900">${{ number_format($card->amount_invested, 2) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500">Svars</span>
                                        <p class="font-semibold text-gray-900">{{ number_format($card->weight, 1) }}%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

{{-- Transaction ledger drawer (right side) --}}
<div id="ledger-overlay" class="fixed inset-0 bg-black/30 z-40 hidden transition-opacity"></div>
<aside id="ledger-drawer" class="fixed top-0 right-0 h-screen w-full sm:w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-out flex flex-col">
    <header class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <div>
            <h2 class="text-base font-bold text-gray-900">Darījumu vēsture</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ $portfolio->name }} · {{ count($transactions) }} darījumi</p>
        </div>
        <div class="flex items-center gap-2">
            @if (!$transactions->isEmpty())
                <a href="{{ route('portfolios.exportTransactions', $portfolio) }}"
                   class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors"
                   title="Lejupielādēt CSV">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    Eksportēt
                </a>
            @endif
            <button type="button" id="close-ledger-btn" class="text-gray-400 hover:text-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </header>
    <div class="flex-1 overflow-y-auto">
        @if ($transactions->isEmpty())
            <div class="flex items-center justify-center h-full text-sm text-gray-400 px-5 text-center">
                Šim portfelim vēl nav darījumu.
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($transactions as $tx)
                    @php
                        $typeMeta = match ($tx->type) {
                            'deposit' => ['label' => 'Iemaksa', 'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            'withdrawal' => ['label' => 'Izmaksa', 'classes' => 'bg-gray-100 text-gray-700 border-gray-200'],
                            'buy' => ['label' => 'Pirkums', 'classes' => 'bg-blue-50 text-blue-700 border-blue-200'],
                            'sell' => ['label' => 'Pārdošana', 'classes' => 'bg-orange-50 text-orange-700 border-orange-200'],
                            default => ['label' => $tx->type, 'classes' => 'bg-gray-100 text-gray-700 border-gray-200'],
                        };
                        $amountClass = (float) $tx->amount >= 0 ? 'text-emerald-600' : 'text-red-600';
                        $amountSign = (float) $tx->amount >= 0 ? '+' : '';
                        $dateStr = \Illuminate\Support\Carbon::parse($tx->transaction_date)->format('Y-m-d');
                    @endphp
                    <li class="px-5 py-3 hover:bg-gray-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $typeMeta['classes'] }}">
                                        {{ $typeMeta['label'] }}
                                    </span>
                                    @if ($tx->ticker)
                                        <span class="text-sm font-bold text-gray-900">{{ $tx->ticker }}</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">{{ $dateStr }}</p>
                                @if ($tx->shares !== null && (float) $tx->shares !== 0.0)
                                    <p class="text-xs text-gray-600 mt-1">
                                        {{ number_format(abs((float) $tx->shares), 4) }} akc.
                                        @if ($tx->price_per_share !== null)
                                            @ ${{ number_format((float) $tx->price_per_share, 2) }}
                                        @endif
                                    </p>
                                @endif
                                @if ($tx->note)
                                    <p class="text-[11px] text-gray-400 mt-1 italic truncate">{{ $tx->note }}</p>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold {{ $amountClass }}">
                                    {{ $amountSign }}${{ number_format((float) $tx->amount, 2) }}
                                </p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $tx->currency }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</aside>

{{-- Sell dialog --}}
<div id="sell-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-black/40">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Pārdot <span id="sell-ticker"></span></h3>
                <p class="text-xs text-gray-500 mt-0.5">Atbrīvot daļu vai visas akcijas</p>
            </div>
            <button type="button" id="sell-cancel-x" class="text-gray-400 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-[11px] text-gray-500 uppercase tracking-wide">Pieder</p>
                <p class="font-semibold text-gray-900 mt-0.5"><span id="sell-current-shares"></span> akc.</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-[11px] text-gray-500 uppercase tracking-wide">Cena</p>
                <p class="font-semibold text-gray-900 mt-0.5">$<span id="sell-current-price"></span></p>
            </div>
        </div>

        <label class="block text-xs font-medium text-gray-600 mb-1">Akciju skaits, ko pārdot</label>
        <input
            type="number"
            id="sell-shares-input"
            step="0.001"
            min="0"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
        >
        <div class="flex gap-2 mt-2">
            <button type="button" data-pct="25" class="sell-pct-btn flex-1 text-xs py-1.5 rounded border border-gray-300 hover:bg-gray-50">25%</button>
            <button type="button" data-pct="50" class="sell-pct-btn flex-1 text-xs py-1.5 rounded border border-gray-300 hover:bg-gray-50">50%</button>
            <button type="button" data-pct="75" class="sell-pct-btn flex-1 text-xs py-1.5 rounded border border-gray-300 hover:bg-gray-50">75%</button>
            <button type="button" data-pct="100" class="sell-pct-btn flex-1 text-xs py-1.5 rounded border border-gray-300 bg-gray-50 hover:bg-gray-100 font-semibold">Visu</button>
        </div>

        <div class="mt-4 bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-sm flex items-baseline justify-between">
            <span class="text-emerald-700 font-medium">Saņemtā summa</span>
            <span class="text-emerald-700 font-bold">$<span id="sell-proceeds">0.00</span></span>
        </div>

        <p id="sell-error" class="text-xs text-red-600 mt-2 hidden"></p>

        <div class="flex justify-end gap-2 mt-5">
            <button type="button" id="sell-cancel-btn" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Atcelt</button>
            <button type="button" id="sell-confirm-btn" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed">Pārdot</button>
        </div>
    </div>
</div>

<script>
(function() {
    const portfolioId = {{ $portfolio->id }};
    const csrfToken = '{{ csrf_token() }}';

    // ─── Portfolio value chart ───
    const portfolioPoints = @json($chart['points'] ?? []);
    (function renderPortfolioChart() {
        const svg = document.getElementById('portfolio-chart');
        if (!svg || portfolioPoints.length < 2) return;

        const tooltip = document.getElementById('portfolio-chart-tooltip');
        const axisRow = document.getElementById('portfolio-chart-axis');
        const w = 600, h = 220, padL = 50, padR = 8, padT = 10, padB = 18;

        const values = portfolioPoints.map(p => p.value);
        let minV = Math.min(...values);
        let maxV = Math.max(...values);
        if (minV === maxV) { minV -= 1; maxV += 1; }
        const pad = (maxV - minV) * 0.05;
        minV -= pad; maxV += pad;
        const range = maxV - minV;

        const n = portfolioPoints.length;
        const xFor = i => padL + (i / (n - 1)) * (w - padL - padR);
        const yFor = v => padT + (1 - (v - minV) / range) * (h - padT - padB);

        const first = values[0], last = values[values.length - 1];
        const color = last >= first ? '#16a34a' : '#dc2626';
        const fillColor = last >= first ? 'rgba(22,163,74,0.10)' : 'rgba(220,38,38,0.10)';

        // Y axis grid + labels (4 lines)
        const ticks = 4;
        let gridSvg = '';
        for (let i = 0; i <= ticks; i++) {
            const v = minV + (range * i / ticks);
            const y = yFor(v);
            gridSvg += `<line x1="${padL}" y1="${y}" x2="${w - padR}" y2="${y}" stroke="#f3f4f6" stroke-width="1"/>`;
            gridSvg += `<text x="${padL - 4}" y="${y + 3}" text-anchor="end" font-size="10" fill="#9ca3af">$${v >= 1000 ? (v/1000).toFixed(1)+'k' : v.toFixed(0)}</text>`;
        }

        const points = portfolioPoints.map((p, i) => `${xFor(i).toFixed(1)},${yFor(p.value).toFixed(1)}`);
        const areaPoints = `${padL},${h - padB} ` + points.join(' ') + ` ${(w - padR).toFixed(1)},${h - padB}`;

        svg.innerHTML = gridSvg
            + `<polygon points="${areaPoints}" fill="${fillColor}"/>`
            + `<polyline points="${points.join(' ')}" fill="none" stroke="${color}" stroke-width="1.8" stroke-linejoin="round"/>`
            + `<line id="portfolio-chart-cursor" x1="0" y1="${padT}" x2="0" y2="${h - padB}" stroke="#9ca3af" stroke-width="1" stroke-dasharray="3 3" style="display:none"/>`;

        // X-axis labels: start, middle, end
        if (axisRow) {
            const idxs = [0, Math.floor(n / 2), n - 1];
            axisRow.innerHTML = idxs.map(i => `<span>${portfolioPoints[i].date}</span>`).join('');
        }

        // Hover tooltip
        const cursor = document.getElementById('portfolio-chart-cursor');
        const rect = svg.getBoundingClientRect.bind(svg);
        svg.addEventListener('mousemove', function(e) {
            const r = rect();
            const xPx = e.clientX - r.left;
            const xSvg = (xPx / r.width) * w;
            const i = Math.max(0, Math.min(n - 1, Math.round(((xSvg - padL) / (w - padL - padR)) * (n - 1))));
            const p = portfolioPoints[i];
            cursor.setAttribute('x1', xFor(i));
            cursor.setAttribute('x2', xFor(i));
            cursor.style.display = '';
            tooltip.classList.remove('hidden');
            tooltip.style.left = (xPx + 8) + 'px';
            tooltip.style.top = '4px';
            tooltip.innerHTML = `<div class="font-semibold">${p.date}</div>`
                + `<div>Vērtība: $${Number(p.value).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>`
                + `<div class="text-gray-300">Skaidra: $${Number(p.cash).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>`
                + `<div class="text-gray-300">Tirgus: $${Number(p.market_value).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>`;
        });
        svg.addEventListener('mouseleave', function() {
            cursor.style.display = 'none';
            tooltip.classList.add('hidden');
        });
    })();

    // ─── Mini chart rendering ───
    const allSeries = @json($cards->map(fn ($c, $i) => ['idx' => $i, 'data' => $c->series])->values());

    allSeries.forEach(({ idx, data }) => {
        if (data.length < 2) return;
        const svg = document.getElementById('minichart-' + idx);
        if (!svg) return;

        const closes = data.map(d => d.close);
        const minVal = Math.min(...closes);
        const maxVal = Math.max(...closes);
        const range = maxVal - minVal || 1;
        const w = 200, h = 80, pad = 2;

        const points = data.map((d, i) => {
            const x = pad + (i / (data.length - 1)) * (w - 2 * pad);
            const y = pad + (1 - (d.close - minVal) / range) * (h - 2 * pad);
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        });

        const first = closes[0], last = closes[closes.length - 1];
        const color = last >= first ? '#16a34a' : '#dc2626';
        const fillColor = last >= first ? 'rgba(22,163,74,0.08)' : 'rgba(220,38,38,0.08)';

        const areaPoints = `${pad},${h - pad} ` + points.join(' ') + ` ${w - pad},${h - pad}`;
        const area = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        area.setAttribute('points', areaPoints);
        area.setAttribute('fill', fillColor);
        svg.appendChild(area);

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        line.setAttribute('points', points.join(' '));
        line.setAttribute('fill', 'none');
        line.setAttribute('stroke', color);
        line.setAttribute('stroke-width', '1.5');
        line.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(line);
    });

    // ─── Add instrument search ───
    const searchInput = document.getElementById('add-instrument-search');
    const searchResults = document.getElementById('add-instrument-results');
    const amountInput = document.getElementById('add-instrument-amount');
    const addBtn = document.getElementById('add-instrument-btn');
    const selectedLabel = document.getElementById('add-instrument-selected');
    const errorLabel = document.getElementById('add-instrument-error');
    let selectedInstrument = null;
    let searchTimeout = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        selectedInstrument = null;
        addBtn.disabled = true;
        selectedLabel.classList.add('hidden');

        const q = this.value.trim();
        if (q.length < 1) {
            searchResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`/instrumenti/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(json => {
                    const items = json.data || [];
                    if (!items.length) {
                        searchResults.innerHTML = '<p class="px-3 py-2 text-sm text-gray-500">Nav rezultātu</p>';
                    } else {
                        searchResults.innerHTML = items.slice(0, 8).map(i =>
                            `<div class="search-result-item px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer flex justify-between" data-id="${i.id}" data-ticker="${i.ticker}">
                                <span><span class="font-semibold">${i.ticker}</span> <span class="text-gray-500">${i.company_name || ''}</span></span>
                                <span class="text-gray-400 text-xs">${i.exchange || ''}</span>
                            </div>`
                        ).join('');
                    }
                    searchResults.classList.remove('hidden');
                });
        }, 250);
    });

    searchResults.addEventListener('click', function(e) {
        const item = e.target.closest('.search-result-item');
        if (!item) return;

        selectedInstrument = { id: parseInt(item.dataset.id), ticker: item.dataset.ticker };
        searchInput.value = item.dataset.ticker;
        selectedLabel.querySelector('span').textContent = item.dataset.ticker;
        selectedLabel.classList.remove('hidden');
        searchResults.classList.add('hidden');
        addBtn.disabled = false;
    });

    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.classList.add('hidden');
        }
    });

    addBtn.addEventListener('click', function() {
        if (!selectedInstrument) return;

        const amount = parseFloat(amountInput.value);
        if (!amount || amount <= 0) {
            errorLabel.textContent = 'Ievadi derīgu summu.';
            errorLabel.classList.remove('hidden');
            return;
        }

        errorLabel.classList.add('hidden');
        addBtn.disabled = true;
        addBtn.textContent = '...';

        fetch(`/portfelis/${portfolioId}/add-instrument`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ instrument_id: selectedInstrument.id, amount: amount }),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(({ ok, data }) => {
            if (!ok) {
                errorLabel.textContent = data.error || 'Kļūda pievienojot.';
                errorLabel.classList.remove('hidden');
                addBtn.disabled = false;
                addBtn.textContent = 'Pievienot';
                return;
            }
            window.location.reload();
        })
        .catch(() => {
            errorLabel.textContent = 'Savienojuma kļūda.';
            errorLabel.classList.remove('hidden');
            addBtn.disabled = false;
            addBtn.textContent = 'Pievienot';
        });
    });

    // ─── Ledger drawer ───
    const drawer = document.getElementById('ledger-drawer');
    const overlay = document.getElementById('ledger-overlay');
    const openBtn = document.getElementById('toggle-ledger-btn');
    const closeBtn = document.getElementById('close-ledger-btn');

    function openDrawer() {
        drawer.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer.classList.add('translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    openBtn?.addEventListener('click', openDrawer);
    closeBtn?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

    // ─── Sell dialog ───
    const sellModal = document.getElementById('sell-modal');
    const sellTickerEl = document.getElementById('sell-ticker');
    const sellSharesEl = document.getElementById('sell-current-shares');
    const sellPriceEl = document.getElementById('sell-current-price');
    const sellInput = document.getElementById('sell-shares-input');
    const sellProceeds = document.getElementById('sell-proceeds');
    const sellError = document.getElementById('sell-error');
    const sellConfirmBtn = document.getElementById('sell-confirm-btn');
    const sellCancelBtn = document.getElementById('sell-cancel-btn');
    const sellCancelX = document.getElementById('sell-cancel-x');

    let sellState = { instrumentId: null, ticker: null, shares: 0, price: 0, invested: 0 };

    function openSellModal(state) {
        sellState = state;
        sellTickerEl.textContent = state.ticker;
        sellSharesEl.textContent = Number(state.shares).toFixed(3);
        sellPriceEl.textContent = state.price ? Number(state.price).toFixed(2) : '—';
        sellInput.value = state.shares.toFixed(3);
        sellInput.max = state.shares;
        sellError.classList.add('hidden');
        sellConfirmBtn.disabled = false;
        sellConfirmBtn.textContent = 'Pārdot';
        updateProceeds();
        sellModal.classList.remove('hidden');
        sellModal.classList.add('flex');
    }

    function closeSellModal() {
        sellModal.classList.add('hidden');
        sellModal.classList.remove('flex');
    }

    function updateProceeds() {
        const sh = parseFloat(sellInput.value) || 0;
        const proceeds = sellState.price
            ? sh * sellState.price
            : (sellState.shares > 0 ? (sh / sellState.shares) * sellState.invested : 0);
        sellProceeds.textContent = proceeds.toFixed(2);
    }

    sellInput.addEventListener('input', updateProceeds);

    document.querySelectorAll('.sell-pct-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pct = parseFloat(this.dataset.pct) / 100;
            const target = pct === 1 ? sellState.shares : Math.round(sellState.shares * pct * 1000) / 1000;
            sellInput.value = target.toFixed(3);
            updateProceeds();
        });
    });

    document.querySelectorAll('.sell-instrument-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openSellModal({
                instrumentId: parseInt(this.dataset.instrumentId),
                ticker: this.dataset.ticker,
                shares: parseFloat(this.dataset.shares) || 0,
                price: parseFloat(this.dataset.price) || 0,
                invested: parseFloat(this.dataset.invested) || 0,
            });
        });
    });

    sellCancelBtn.addEventListener('click', closeSellModal);
    sellCancelX.addEventListener('click', closeSellModal);
    sellModal.addEventListener('click', e => { if (e.target === sellModal) closeSellModal(); });

    sellConfirmBtn.addEventListener('click', function() {
        const sh = parseFloat(sellInput.value);
        if (!sh || sh <= 0) {
            sellError.textContent = 'Ievadi derīgu akciju skaitu.';
            sellError.classList.remove('hidden');
            return;
        }
        if (sh > sellState.shares + 1e-3) {
            sellError.textContent = 'Pārsniedz pieejamo akciju skaitu.';
            sellError.classList.remove('hidden');
            return;
        }

        sellError.classList.add('hidden');
        sellConfirmBtn.disabled = true;
        sellConfirmBtn.textContent = '...';

        fetch(`/portfelis/${portfolioId}/sell-instrument/${sellState.instrumentId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ shares: sh }),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(({ ok, data }) => {
            if (!ok) {
                sellError.textContent = data.error || 'Kļūda pārdodot.';
                sellError.classList.remove('hidden');
                sellConfirmBtn.disabled = false;
                sellConfirmBtn.textContent = 'Pārdot';
                return;
            }
            window.location.reload();
        })
        .catch(() => {
            sellError.textContent = 'Savienojuma kļūda.';
            sellError.classList.remove('hidden');
            sellConfirmBtn.disabled = false;
            sellConfirmBtn.textContent = 'Pārdot';
        });
    });

})();
</script>
@endsection
