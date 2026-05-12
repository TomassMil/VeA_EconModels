@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mani portfeļi</h1>
            <p class="text-gray-600 mt-1">Pārvaldi savus investīciju portfeļus, salīdzini risku pret peļņu</p>
        </div>

        {{-- Risks vs Peļņa scatter plot + QuantStats panel --}}
        @if ($scatterPoints->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Risks vs. Peļņa</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Klikšķini uz portfeļa punkta, lai apskatītu QuantStats atskaiti</p>
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

                <div>
                    <canvas id="risk-return-chart" style="max-height: 480px;"></canvas>
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
                    <a href="{{ route('portfolios.show', $portfolio) }}"
                       class="block bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:border-blue-300 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $portfolio->name }}</h2>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $portfolio->instruments_count }} instrumenti
                                    &middot;
                                    Brīvais kapitāls: ${{ number_format((float)$portfolio->free_capital, 2) }}
                                </p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
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

    const dataset = points.map(p => ({
        x: p.risk,
        y: p.return * 100, // procenti
        portfolio_id: p.id,
        name: p.name,
    }));

    const chart = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Portfeļi',
                data: dataset,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgb(37, 99, 235)',
                borderWidth: 2,
                pointRadius: 10,
                pointHoverRadius: 13,
            }],
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
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => {
                            const d = item.raw;
                            return `${d.name}: risks ${d.x.toFixed(3)}, peļņa ${d.y.toFixed(2)}%`;
                        },
                    },
                },
            },
            onClick: (e, elements) => {
                if (!elements.length) return;
                const point = dataset[elements[0].index];
                loadQuantStats(point.portfolio_id, point.name);
            },
        },
        plugins: [{
            id: 'pointLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                chart.data.datasets[0].data.forEach((d, i) => {
                    const meta = chart.getDatasetMeta(0).data[i];
                    ctx.save();
                    ctx.fillStyle = '#374151';
                    ctx.font = '600 11px system-ui';
                    ctx.textAlign = 'center';
                    ctx.fillText(d.name, meta.x, meta.y - 16);
                    ctx.restore();
                });
            },
        }],
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
