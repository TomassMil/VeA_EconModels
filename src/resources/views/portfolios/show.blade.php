@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <a href="{{ route('portfolios.index') }}" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Atpakaļ uz portfeļiem
        </a>

        <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ $portfolio->name }}</h1>

        {{-- Portfolio Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Portfeļa vērtība</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($summary->portfolio_value, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Investēts</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($summary->total_invested, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Izmaiņas</p>
                    <p class="text-2xl font-bold mt-1 {{ $summary->total_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $summary->total_change >= 0 ? '+' : '' }}${{ number_format($summary->total_change, 2) }}
                        <span class="text-sm font-medium">
                            ({{ $summary->total_change_pct >= 0 ? '+' : '' }}{{ number_format($summary->total_change_pct, 2) }}%)
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Brīvais kapitāls</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($summary->free_capital, 2) }}</p>
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
                                            class="remove-instrument-btn text-gray-300 hover:text-red-500 transition-colors"
                                            data-instrument-id="{{ $card->instrument->id }}"
                                            data-ticker="{{ $card->instrument->ticker }}"
                                            title="Noņemt no portfeļa"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
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
                            <div class="w-[55%] p-4 text-sm">
                                <div class="grid grid-cols-2 gap-x-3 gap-y-2">
                                    {{-- Current price --}}
                                    <div>
                                        <span class="text-xs text-gray-500">Cena</span>
                                        <p class="font-semibold text-gray-900">
                                            {{ $card->current_close !== null ? '$' . number_format($card->current_close, 2) : '-' }}
                                        </p>
                                    </div>
                                    {{-- Volume --}}
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
                                    {{-- Day change --}}
                                    <div>
                                        <span class="text-xs text-gray-500">Diena</span>
                                        @if ($card->day_change_abs !== null)
                                            <p class="font-semibold {{ $card->day_change_abs >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $card->day_change_abs >= 0 ? '+' : '' }}{{ number_format($card->day_change_abs, 2) }}
                                                <span class="text-xs">({{ $card->day_change_pct >= 0 ? '+' : '' }}{{ number_format($card->day_change_pct, 2) }}%)</span>
                                            </p>
                                        @else
                                            <p class="font-semibold text-gray-400">-</p>
                                        @endif
                                    </div>
                                    {{-- Week change --}}
                                    <div>
                                        <span class="text-xs text-gray-500">Nedēļa</span>
                                        @if ($card->week_change_abs !== null)
                                            <p class="font-semibold {{ $card->week_change_abs >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $card->week_change_abs >= 0 ? '+' : '' }}{{ number_format($card->week_change_abs, 2) }}
                                                <span class="text-xs">({{ $card->week_change_pct >= 0 ? '+' : '' }}{{ number_format($card->week_change_pct, 2) }}%)</span>
                                            </p>
                                        @else
                                            <p class="font-semibold text-gray-400">-</p>
                                        @endif
                                    </div>
                                    {{-- Amount invested --}}
                                    <div>
                                        <span class="text-xs text-gray-500">Investēts</span>
                                        <p class="font-semibold text-gray-900">${{ number_format($card->amount_invested, 2) }}</p>
                                    </div>
                                    {{-- Weight --}}
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

<script>
(function() {
    const portfolioId = {{ $portfolio->id }};
    const csrfToken = '{{ csrf_token() }}';

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

    // ─── Remove instrument ───
    document.querySelectorAll('.remove-instrument-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const instrumentId = this.dataset.instrumentId;
            const ticker = this.dataset.ticker;
            if (!confirm(`Vai tiešām vēlies noņemt ${ticker} no portfeļa?`)) return;

            fetch(`/portfelis/${portfolioId}/remove-instrument/${instrumentId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            })
            .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
            .then(({ ok, data }) => {
                if (!ok) { alert(data.error || 'Kļūda.'); return; }
                window.location.reload();
            });
        });
    });

})();
</script>
@endsection
