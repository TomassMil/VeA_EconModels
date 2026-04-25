@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('indexes.index') }}" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Atpakaļ uz indeksiem
            </a>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $index->name }}</h1>
                    @if ($index->description)
                        <p class="text-gray-600 mt-1">{{ $index->description }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @if ($index->is_public)
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                            Publisks
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600 border border-gray-200">
                            Privāts
                        </span>
                    @endif
                    @if ($index->user_id === Auth::id())
                        <form method="POST" action="{{ route('indexes.destroy', $index) }}"
                              onsubmit="return confirm('Vai tiešām vēlies dzēst šo indeksu?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                Dzēst
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Index Performance Chart --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Indeksa veikums (bāze 100)</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $chart['resolution'] === 'weekly' ? 'Nedēļas izlase' : 'Dienas izlase' }} · {{ count($chart['points']) }} punkti</p>
                </div>
                <div class="inline-flex rounded border border-gray-300 overflow-hidden bg-white text-xs">
                    @foreach ([
                        'market_cap' => 'Tirgus kap.',
                        'equal' => 'Vienādi',
                        'price' => 'Cena',
                    ] as $w => $label)
                        <a href="{{ route('indexes.show', $index) }}?weighting={{ $w }}"
                           class="px-3 py-1.5 font-medium border-r border-gray-300 last:border-r-0 {{ $weighting === $w ? 'bg-slate-700 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
            @if (count($chart['points']) > 1)
                <div class="relative">
                    <svg id="index-chart" viewBox="0 0 800 240" preserveAspectRatio="none" class="w-full h-56"></svg>
                    <div id="index-chart-tooltip" class="absolute hidden pointer-events-none bg-gray-900 text-white text-xs rounded px-2 py-1 shadow-lg"></div>
                </div>
                <div id="index-chart-axis" class="flex justify-between text-[10px] text-gray-400 mt-1 px-1"></div>
            @else
                <div class="h-56 flex items-center justify-center text-sm text-gray-400 border border-dashed border-gray-200 rounded">
                    Nav pietiekami datu, lai zīmētu grafiku
                </div>
            @endif
        </div>

        {{-- Active Filters --}}
        @if ($index->filters && count($index->filters) > 0)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Aktīvie filtri</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($index->filters as $key => $value)
                        @php
                            $labels = [
                                'price_min' => 'Min. cena',
                                'price_max' => 'Maks. cena',
                                'avg_volume_min' => 'Min. apjoms',
                                'avg_volume_max' => 'Maks. apjoms',
                                'exclude_below_price' => 'Izslēgt zem',
                                'has_fundamentals' => 'Ar finanšu datiem',
                                'revenue_min' => 'Min. ieņēmumi',
                                'revenue_max' => 'Maks. ieņēmumi',
                                'net_income_min' => 'Min. tīrā peļņa',
                                'net_income_max' => 'Maks. tīrā peļņa',
                                'total_assets_min' => 'Min. aktīvi',
                                'total_assets_max' => 'Maks. aktīvi',
                                'total_liabilities_min' => 'Min. saistības',
                                'total_liabilities_max' => 'Maks. saistības',
                                'eps_min' => 'Min. EPS',
                                'eps_max' => 'Maks. EPS',
                                'operating_cf_min' => 'Min. op. naudas pl.',
                                'operating_cf_max' => 'Maks. op. naudas pl.',
                            ];
                            $label = $labels[$key] ?? $key;
                            $display = is_bool($value) || $value === true || $value === 1 ? 'Jā' : number_format((float)$value, is_float($value + 0) ? 2 : 0);
                        @endphp
                        <span class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 border border-blue-200">
                            {{ $label }}: {{ $display }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Instruments Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    Instrumenti
                    <span class="text-sm font-normal text-gray-500">({{ $instruments->total() }})</span>
                </h2>
            </div>

            @if ($instruments->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-500">
                    Šajā indeksā nav instrumentu.
                </div>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Ticker</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Nosaukums</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Birža</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Tips</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($instruments as $inst)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('instruments.show', $inst) }}" class="font-semibold text-blue-600 hover:text-blue-800">
                                        {{ $inst->ticker }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ $inst->company_name }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $inst->exchange ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    @if ($inst->pivot->added_manually)
                                        <span class="text-xs text-indigo-600 font-medium">Manuāli</span>
                                    @else
                                        <span class="text-xs text-gray-400">Filtrs</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($instruments->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">
                        {{ $instruments->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>

<script>
(function() {
    const points = @json($chart['points'] ?? []);
    const svg = document.getElementById('index-chart');
    if (!svg || points.length < 2) return;

    const tooltip = document.getElementById('index-chart-tooltip');
    const axisRow = document.getElementById('index-chart-axis');
    const w = 800, h = 240, padL = 50, padR = 8, padT = 10, padB = 18;

    const values = points.map(p => p.value);
    let minV = Math.min(...values);
    let maxV = Math.max(...values);
    if (minV === maxV) { minV -= 1; maxV += 1; }
    const padV = (maxV - minV) * 0.05;
    minV -= padV; maxV += padV;
    const range = maxV - minV;

    const n = points.length;
    const xFor = i => padL + (i / (n - 1)) * (w - padL - padR);
    const yFor = v => padT + (1 - (v - minV) / range) * (h - padT - padB);

    const first = values[0], last = values[values.length - 1];
    const color = last >= first ? '#16a34a' : '#dc2626';
    const fillColor = last >= first ? 'rgba(22,163,74,0.10)' : 'rgba(220,38,38,0.10)';

    const ticks = 4;
    let gridSvg = '';
    for (let i = 0; i <= ticks; i++) {
        const v = minV + (range * i / ticks);
        const y = yFor(v);
        gridSvg += `<line x1="${padL}" y1="${y}" x2="${w - padR}" y2="${y}" stroke="#f3f4f6" stroke-width="1"/>`;
        gridSvg += `<text x="${padL - 4}" y="${y + 3}" text-anchor="end" font-size="10" fill="#9ca3af">${v.toFixed(1)}</text>`;
    }
    // Baseline 100
    if (minV <= 100 && maxV >= 100) {
        const y100 = yFor(100);
        gridSvg += `<line x1="${padL}" y1="${y100}" x2="${w - padR}" y2="${y100}" stroke="#9ca3af" stroke-width="1" stroke-dasharray="2 2"/>`;
    }

    const polyPoints = points.map((p, i) => `${xFor(i).toFixed(1)},${yFor(p.value).toFixed(1)}`);
    const areaPoints = `${padL},${h - padB} ` + polyPoints.join(' ') + ` ${(w - padR).toFixed(1)},${h - padB}`;

    svg.innerHTML = gridSvg
        + `<polygon points="${areaPoints}" fill="${fillColor}"/>`
        + `<polyline points="${polyPoints.join(' ')}" fill="none" stroke="${color}" stroke-width="1.8" stroke-linejoin="round"/>`
        + `<line id="index-chart-cursor" x1="0" y1="${padT}" x2="0" y2="${h - padB}" stroke="#9ca3af" stroke-width="1" stroke-dasharray="3 3" style="display:none"/>`;

    if (axisRow) {
        const idxs = [0, Math.floor(n / 4), Math.floor(n / 2), Math.floor(3 * n / 4), n - 1];
        axisRow.innerHTML = idxs.map(i => `<span>${points[i].date}</span>`).join('');
    }

    const cursor = document.getElementById('index-chart-cursor');
    svg.addEventListener('mousemove', function(e) {
        const r = svg.getBoundingClientRect();
        const xPx = e.clientX - r.left;
        const xSvg = (xPx / r.width) * w;
        const i = Math.max(0, Math.min(n - 1, Math.round(((xSvg - padL) / (w - padL - padR)) * (n - 1))));
        const p = points[i];
        cursor.setAttribute('x1', xFor(i));
        cursor.setAttribute('x2', xFor(i));
        cursor.style.display = '';
        tooltip.classList.remove('hidden');
        tooltip.style.left = (xPx + 8) + 'px';
        tooltip.style.top = '4px';
        tooltip.innerHTML = `<div class="font-semibold">${p.date}</div>`
            + `<div>${Number(p.value).toFixed(2)}</div>`
            + `<div class="text-gray-300">Komp.: ${p.constituents}</div>`;
    });
    svg.addEventListener('mouseleave', function() {
        cursor.style.display = 'none';
        tooltip.classList.add('hidden');
    });
})();
</script>
@endsection
