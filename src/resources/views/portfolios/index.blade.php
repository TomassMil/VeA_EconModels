@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Mani portfeļi</h1>
                <p class="text-gray-600 mt-1">Pārvaldi savus investīciju portfeļus, salīdzini risku pret peļņu</p>
            </div>
            <a href="{{ route('backtests.create') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-100 transition-colors whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Backtest wizard
            </a>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-5 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Risks vs Peļņa scatter plot + QuantStats panel --}}
        @if ($scatterPoints->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Risks vs. Peļņa</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Klikšķini uz portfeļa punkta, lai apskatītu QuantStats. Kvadranti:
                            <span class="text-emerald-700 font-medium">↖ zema risk + peļņa</span> ·
                            <span class="text-blue-700 font-medium">↗ augsta risk + peļņa</span> ·
                            <span class="text-gray-500 font-medium">↙ zema risk + zaudējumi</span> ·
                            <span class="text-red-700 font-medium">↘ augsta risk + zaudējumi</span>
                        </p>
                    </div>
                    <div class="inline-flex rounded border border-gray-300 overflow-hidden bg-white text-xs">
                        @foreach ([30, 60, 90, 180, 365] as $d)
                            <a href="?risk_days={{ $d }}"
                               class="px-3 py-1.5 font-medium {{ $riskDays === $d ? 'bg-slate-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} {{ ! $loop->last ? 'border-r border-gray-300' : '' }}">
                                {{ $d }}d
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Filter pogas --}}
                <div class="flex flex-wrap gap-2 mb-3">
                    <button type="button" data-filter="all" class="scatter-filter px-3 py-1.5 text-xs font-medium rounded-full border border-gray-300 bg-slate-700 text-white">Visi</button>
                    <button type="button" data-filter="personal" class="scatter-filter px-3 py-1.5 text-xs font-medium rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1.5"></span>Mani portfeļi
                    </button>
                    <button type="button" data-filter="system" class="scatter-filter px-3 py-1.5 text-xs font-medium rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>Modeļi
                    </button>
                    <button type="button" data-filter="index" class="scatter-filter px-3 py-1.5 text-xs font-medium rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1.5"></span>Indeksi
                    </button>
                </div>

                <div style="position: relative; height: 600px;">
                    <canvas id="risk-return-chart"></canvas>
                </div>
            </div>
        @endif

        {{-- QuantStats slide-out drawer --}}
        <div id="quantstats-overlay" class="fixed inset-0 bg-black/30 z-40 hidden transition-opacity"></div>
        <aside id="quantstats-drawer" class="fixed top-0 right-0 h-screen w-full sm:w-[90%] md:w-[1100px] max-w-[95vw] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-out flex flex-col">
            <header class="flex items-center justify-between px-5 py-4 border-b border-gray-200 shrink-0">
                <div>
                    <h2 class="text-base font-bold text-gray-900">QuantStats atskaite</h2>
                    <p id="quantstats-subtitle" class="text-xs text-gray-500 mt-0.5"></p>
                </div>
                <div class="flex items-center gap-2">
                    <a id="quantstats-download" href="#" download
                       class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors hidden"
                       title="Lejupielādēt HTML atskaiti">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        Lejupielādēt
                    </a>
                    <button type="button" id="close-quantstats-btn" class="text-gray-400 hover:text-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </header>
            <div id="quantstats-body" class="flex-1 overflow-hidden bg-gray-50">
                {{-- iframe / loader / error message tiks ievietots šeit --}}
            </div>
        </aside>
        {{-- /end QuantStats drawer --}}

        {{-- Create new portfolio --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Izveidot jaunu portfeli</h2>
            <form action="{{ route('portfolios.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input
                    type="text"
                    name="name"
                    placeholder="Portfeļa nosaukums"
                    required
                    maxlength="100"
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                >
                <div class="flex gap-2">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                        <input
                            type="number"
                            name="free_capital"
                            placeholder="Sākuma kapitāls"
                            required
                            min="0"
                            step="0.01"
                            value="10000"
                            class="w-40 rounded-lg border border-gray-300 pl-7 pr-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                        >
                    </div>
                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors whitespace-nowrap"
                    >
                        Izveidot
                    </button>
                </div>
            </form>
            @if ($errors->any())
                <p class="text-xs text-red-600 mt-2">{{ $errors->first() }}</p>
            @endif
        </div>

        @if ($portfolios->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                <p class="text-gray-500">Tev vēl nav neviena portfeļa.</p>
            </div>
        @else
            <div class="grid gap-4">
                @foreach ($portfolios as $portfolio)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-blue-300 hover:shadow-md transition-all flex items-center justify-between gap-4">
                        <a href="{{ route('portfolios.show', $portfolio) }}" class="flex-1 min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900">{{ $portfolio->name }}</h2>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $portfolio->instruments_count }} instrumenti
                                &middot;
                                Brīvais kapitāls: ${{ number_format((float)$portfolio->free_capital, 2) }}
                            </p>
                            @if ($portfolio->description)
                                <p class="text-xs text-gray-400 mt-1 truncate">{{ $portfolio->description }}</p>
                            @endif
                        </a>
                        <form method="POST" action="{{ route('portfolios.destroy', $portfolio) }}"
                              onsubmit="return confirm('Vai tiešām dzēst portfeli &quot;{{ $portfolio->name }}&quot;? Visas transakcijas tiks zaudētas.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 hover:border-red-300 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Dzēst
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

@if ($scatterPoints->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const points = @json($scatterPoints);
    const ctx = document.getElementById('risk-return-chart');
    if (!ctx) return;

    const buildPoint = (p) => ({
        x: p.risk,
        y: p.return * 100, // procenti
        portfolio_id: p.id,
        name: p.name,
        category: p.category,
    });

    const personalData = points.filter(p => p.category === 'personal').map(buildPoint);
    const systemData   = points.filter(p => p.category === 'system').map(buildPoint);
    const indexData    = points.filter(p => p.category === 'index').map(buildPoint);

    // Quadrant lines plugin: zaļas līnijas pie X=0.5 un Y=0
    const quadrantPlugin = {
        id: 'quadrantLines',
        beforeDatasetsDraw(chart) {
            const { ctx, chartArea: area, scales } = chart;
            if (!area) return;
            ctx.save();
            ctx.strokeStyle = 'rgba(34, 197, 94, 0.6)';
            ctx.lineWidth = 1.5;
            ctx.setLineDash([6, 4]);

            // Vertikāla līnija pie X = 0.5
            const x05 = scales.x.getPixelForValue(0.5);
            if (x05 >= area.left && x05 <= area.right) {
                ctx.beginPath();
                ctx.moveTo(x05, area.top);
                ctx.lineTo(x05, area.bottom);
                ctx.stroke();
            }

            // Horizontāla līnija pie Y = 0
            const y0 = scales.y.getPixelForValue(0);
            if (y0 >= area.top && y0 <= area.bottom) {
                ctx.beginPath();
                ctx.moveTo(area.left, y0);
                ctx.lineTo(area.right, y0);
                ctx.stroke();
            }

            ctx.restore();
        },
    };

    const chart = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [
                {
                    label: 'Mani portfeļi',
                    data: personalData,
                    _category: 'personal',
                    backgroundColor: 'rgba(59, 130, 246, 0.75)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 2,
                    pointRadius: 9,
                    pointHoverRadius: 13,
                },
                {
                    label: 'Modeļu portfeļi',
                    data: systemData,
                    _category: 'system',
                    backgroundColor: 'rgba(34, 197, 94, 0.75)',
                    borderColor: 'rgb(22, 163, 74)',
                    borderWidth: 2,
                    pointRadius: 9,
                    pointHoverRadius: 13,
                },
                {
                    label: 'Indeksi',
                    data: indexData,
                    _category: 'index',
                    backgroundColor: 'rgba(239, 68, 68, 0.75)',
                    borderColor: 'rgb(220, 38, 38)',
                    borderWidth: 2,
                    pointRadius: 9,
                    pointHoverRadius: 13,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: { display: true, text: 'Risks (0-1)' },
                    min: 0,
                    max: 1,
                },
                y: {
                    title: { display: true, text: 'Peļņa (%)' },
                },
            },
            plugins: {
                legend: { display: true, position: 'top', align: 'end' },
                tooltip: {
                    callbacks: {
                        label: (item) => {
                            const d = item.raw;
                            const prefix = d.category === 'system' ? '[Modelis] '
                                         : d.category === 'index' ? '[Indekss] '
                                         : '';
                            return `${prefix}${d.name}: risks ${d.x.toFixed(3)}, peļņa ${d.y.toFixed(2)}%`;
                        },
                    },
                },
            },
            onClick: (e, elements) => {
                if (!elements.length) return;
                const el = elements[0];
                const data = chart.data.datasets[el.datasetIndex].data[el.index];
                loadQuantStats(data.portfolio_id, data.name);
            },
        },
        plugins: [
            quadrantPlugin,
            {
                id: 'pointLabels',
                afterDatasetsDraw(chart) {
                    const { ctx } = chart;
                    chart.data.datasets.forEach((ds, dsIdx) => {
                        const meta = chart.getDatasetMeta(dsIdx);
                        if (meta.hidden) return;
                        ds.data.forEach((d, i) => {
                            const elem = meta.data[i];
                            if (!elem) return;
                            ctx.save();
                            ctx.fillStyle = '#374151';
                            ctx.font = '600 11px system-ui';
                            ctx.textAlign = 'center';
                            ctx.fillText(d.name, elem.x, elem.y - 16);
                            ctx.restore();
                        });
                    });
                },
            },
        ],
    });

    // ─── Filter buttons ───
    document.querySelectorAll('.scatter-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            // Update visual state of buttons
            document.querySelectorAll('.scatter-filter').forEach(b => {
                b.classList.remove('bg-slate-700', 'text-white');
                b.classList.add('bg-white', 'text-gray-700');
            });
            btn.classList.add('bg-slate-700', 'text-white');
            btn.classList.remove('bg-white', 'text-gray-700');

            // Show/hide datasets
            chart.data.datasets.forEach((ds, idx) => {
                const meta = chart.getDatasetMeta(idx);
                meta.hidden = filter !== 'all' && ds._category !== filter;
            });
            chart.update();
        });
    });

    // ─── QuantStats drawer ───
    const drawer = document.getElementById('quantstats-drawer');
    const overlay = document.getElementById('quantstats-overlay');
    const closeBtn = document.getElementById('close-quantstats-btn');
    const body = document.getElementById('quantstats-body');
    const subtitle = document.getElementById('quantstats-subtitle');
    const downloadLink = document.getElementById('quantstats-download');

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

    closeBtn?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

    function loadQuantStats(id, name) {
        subtitle.textContent = name;
        downloadLink.href = `/portfelis/${id}/quantstats?download=1`;
        downloadLink.classList.add('hidden');
        body.innerHTML = `
            <div class="h-full flex items-center justify-center">
                <div class="inline-flex items-center gap-3 text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Ģenerē QuantStats atskaiti...
                </div>
            </div>
        `;
        openDrawer();

        fetch(`/portfelis/${id}/quantstats`, {
            headers: { 'Accept': 'text/html', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(html => {
            const iframe = document.createElement('iframe');
            iframe.srcdoc = html;
            iframe.className = 'w-full h-full';
            iframe.style.border = '0';
            body.innerHTML = '';
            body.appendChild(iframe);
            downloadLink.classList.remove('hidden');
        })
        .catch(err => {
            body.innerHTML = `
                <div class="h-full flex items-center justify-center px-6">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-red-600 mb-2">QuantStats ģenerēšana neizdevās</p>
                        <p class="text-xs text-gray-500">${err.message}</p>
                        <p class="text-xs text-gray-400 mt-3">Pārbaudi, vai docker container pārbūvēts ar python3 + quantstats.</p>
                    </div>
                </div>
            `;
        });
    }
})();
</script>
@endif

@endsection
