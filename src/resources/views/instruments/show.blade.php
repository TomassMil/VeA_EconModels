@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('instruments.index') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 mb-4">
            ← Atpakaļ uz Instrumenti
        </a>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                {{ $instrument->ticker }}
            </h1>
            @if ($instrument->company_name)
                <p class="text-gray-600 mt-2">
                    {{ $instrument->company_name }}
                </p>
            @endif
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div
                        id="chart-mode-group"
                        class="inline-flex rounded-lg border border-gray-300 overflow-hidden bg-white"
                    >
                        <button
                            type="button"
                            id="chart-mode-close"
                            data-mode="close"
                            class="chart-mode-btn inline-flex items-center gap-2 px-3 py-2 text-sm font-medium bg-blue-600 text-white"
                            aria-pressed="true"
                        >
                            <svg viewBox="0 0 20 20" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M2 14 L7 10 L11 12 L18 5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Close
                        </button>
                        <button
                            type="button"
                            id="chart-mode-ohlc"
                            data-mode="ohlc"
                            class="chart-mode-btn inline-flex items-center gap-2 px-3 py-2 text-sm font-medium bg-white text-gray-700 border-l border-gray-300"
                            aria-pressed="false"
                        >
                            <svg viewBox="0 0 20 20" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6">
                                <line x1="5" y1="4" x2="5" y2="16" />
                                <rect x="3.3" y="7" width="3.4" height="5" fill="currentColor" stroke="none" />
                                <line x1="13" y1="3" x2="13" y2="17" />
                                <rect x="11.3" y="9" width="3.4" height="4.5" fill="currentColor" stroke="none" />
                            </svg>
                            Candles
                        </button>
                    </div>
                </div>
            </div>

            @if ($priceSeries->isNotEmpty())
                <div id="chart-container" class="relative overflow-hidden border border-gray-100 rounded-lg cursor-none select-none">
                    <div
                        id="chart-hover-info"
                        class="absolute left-3 top-2 z-10 text-xs text-gray-500 pointer-events-none font-medium tracking-wide"
                    ></div>
                    <div
                        id="chart-controls"
                        class="absolute right-3 top-2 z-10 flex items-center gap-2"
                    >
                        <div id="chart-range-group" class="inline-flex rounded border border-gray-300 overflow-hidden bg-white">
                            <button type="button" class="chart-range-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700 border-r border-gray-300" data-range="1m">1m</button>
                            <button type="button" class="chart-range-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700 border-r border-gray-300" data-range="1y">1y</button>
                            <button type="button" class="chart-range-btn px-2 py-1 text-[11px] font-medium bg-slate-700 text-white border-r border-gray-300" data-range="5y">5y</button>
                            <button type="button" class="chart-range-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700 border-r border-gray-300" data-range="10y">10y</button>
                            <button type="button" class="chart-range-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700" data-range="max">max</button>
                        </div>
                        <div id="chart-interval-group" class="inline-flex rounded border border-gray-300 overflow-hidden bg-white">
                            <button type="button" class="px-2 py-1 text-[11px] font-medium bg-slate-700 text-white border-r border-gray-300" data-interval="1d">1d</button>
                            <button type="button" class="px-2 py-1 text-[11px] font-medium bg-gray-100 text-gray-400 cursor-not-allowed" data-interval="1w" disabled title="1w coming soon">1w</button>
                        </div>
                    </div>
                    <svg id="price-chart" class="h-[520px]"></svg>
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-gray-600">
                    Šim instrumentam vēl nav cenas datu tabulā <code>prices_daily</code>.
                </div>
            @endif
        </section>

        <section class="mt-8 rounded-xl border border-gray-200 bg-white p-5 sm:p-6 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 lg:gap-6 min-h-[420px]">
                <aside class="md:col-span-1 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <div id="fundamentals-year-list" class="space-y-2"></div>
                </aside>

                <div class="md:col-span-4 rounded-lg border border-gray-200 bg-white p-4 sm:p-5 flex flex-col">
                    <div id="fundamentals-statement-tabs" class="inline-flex flex-wrap rounded-lg border border-gray-300 overflow-hidden self-start">
                        <button
                            type="button"
                            data-statement="balance_sheet"
                            class="fundamentals-statement-btn px-4 py-2 text-sm font-medium text-gray-700 bg-white border-r border-gray-300"
                        >
                            Balance sheet
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

                    <p id="fundamentals-period-title" class="mt-3 text-sm font-medium text-gray-600"></p>
                    <div id="fundamentals-content" class="mt-4 grow overflow-auto"></div>
                </div>
            </div>
        </section>
    </div>
</div>

@if ($priceSeries->isNotEmpty())
    <script>
        (function () {
            const points = @json($priceSeries);
            const svg = document.getElementById('price-chart');
            const modeButtons = Array.from(document.querySelectorAll('.chart-mode-btn'));
            const rangeButtons = Array.from(document.querySelectorAll('.chart-range-btn'));
            const chartContainer = document.getElementById('chart-container');
            const hoverInfo = document.getElementById('chart-hover-info');

            if (!svg || modeButtons.length === 0 || rangeButtons.length === 0 || !chartContainer || !hoverInfo || !Array.isArray(points) || points.length === 0) {
                return;
            }

            const height = 520;
            const padding = { top: 42, right: 20, bottom: 56, left: 74 };
            const volumePanelHeight = 92;
            const panelGap = 18;
            const approxTradingDaysPerYear = 252;
            const defaultRange = '5y';
            const parsedPoints = points.map((point) => ({
                date: point.date,
                open: toNumber(point.open),
                high: toNumber(point.high),
                low: toNumber(point.low),
                close: toNumber(point.close),
                volume: toNumber(point.volume),
            }));
            const rangeBarCounts = {
                '1m': 22,
                '1y': approxTradingDaysPerYear,
                '5y': approxTradingDaysPerYear * 5,
                '10y': approxTradingDaysPerYear * 10,
                max: parsedPoints.length,
            };
            const minBarsPerView = Math.min(30, parsedPoints.length);
            const maxBarsPerView = parsedPoints.length;
            const minPixelsPerCandle = 2;
            const minPixelsPerVolumeBar = 1.5;
            const defaultBarsPerView = Math.max(1, Math.min(rangeBarCounts[defaultRange], parsedPoints.length));
            let barsPerView = defaultBarsPerView;
            let startIndex = Math.max(0, parsedPoints.length - barsPerView);
            let isDragging = false;
            let lastDragX = 0;
            let currentMode = 'close';
            let currentRange = defaultRange;
            let hoverIndex = null;
            let hoverClientX = null;
            let drawQueued = false;

            function toNumber(value) {
                const number = Number(value);
                return Number.isFinite(number) ? number : null;
            }

            function clamp(value, min, max) {
                return Math.min(Math.max(value, min), max);
            }

            function maxStartIndex() {
                return Math.max(0, parsedPoints.length - barsPerView);
            }

            function scheduleDraw() {
                if (drawQueued) {
                    return;
                }

                drawQueued = true;
                window.requestAnimationFrame(function () {
                    drawQueued = false;
                    draw();
                });
            }

            function getContainerWidth() {
                return Math.max(chartContainer.clientWidth, 760);
            }

            function getPlotWidth() {
                return Math.max(getContainerWidth() - padding.left - padding.right, 260);
            }

            function getStepX(plotWidth) {
                return plotWidth / Math.max(barsPerView - 1, 1);
            }

            function getBarsForRange(range) {
                const fallbackBars = rangeBarCounts[defaultRange];
                const configuredBars = Object.prototype.hasOwnProperty.call(rangeBarCounts, range)
                    ? rangeBarCounts[range]
                    : fallbackBars;

                return Math.max(1, Math.min(configuredBars, parsedPoints.length));
            }

            function formatValue(value, mode) {
                if (!Number.isFinite(value)) {
                    return '';
                }

                if (mode === 'volume') {
                    return value >= 1_000_000_000
                        ? `${(value / 1_000_000_000).toFixed(2)}B`
                        : value >= 1_000_000
                            ? `${(value / 1_000_000).toFixed(2)}M`
                            : value.toFixed(0);
                }

                return value.toFixed(2);
            }

            function formatDate(dateString) {
                const date = new Date(`${dateString}T00:00:00Z`);
                if (Number.isNaN(date.getTime())) {
                    return dateString;
                }

                const day = String(date.getUTCDate()).padStart(2, '0');
                const month = String(date.getUTCMonth() + 1).padStart(2, '0');
                const year = String(date.getUTCFullYear()).slice(2);

                return `${day}.${month}.${year}`;
            }

            function formatMaybePrice(value) {
                return Number.isFinite(value) ? value.toFixed(2) : '—';
            }

            function formatMaybeVolume(value) {
                return Number.isFinite(value) ? formatValue(value, 'volume') : '—';
            }

            function formatHoverText(point) {
                const dateLabel = formatDate(point.date);
                return `${dateLabel} | O: ${formatMaybePrice(point.open)} | H: ${formatMaybePrice(point.high)} | L: ${formatMaybePrice(point.low)} | C: ${formatMaybePrice(point.close)} | V: ${formatMaybeVolume(point.volume)}`;
            }

            function xForIndex(globalIndex, stepX) {
                return padding.left + (globalIndex - startIndex) * stepX;
            }

            function buildVisibleIndexRange() {
                const first = Math.max(0, Math.floor(startIndex));
                const last = Math.min(parsedPoints.length - 1, Math.ceil(startIndex + barsPerView - 1));
                return { first, last };
            }

            function buildPriceRange(mode, firstIndex, lastIndex) {
                let values = [];

                if (mode === 'ohlc') {
                    for (let i = firstIndex; i <= lastIndex; i += 1) {
                        const point = parsedPoints[i];
                        [point.open, point.high, point.low, point.close].forEach((value) => {
                            if (Number.isFinite(value)) {
                                values.push(value);
                            }
                        });
                    }
                } else {
                    for (let i = firstIndex; i <= lastIndex; i += 1) {
                        const value = parsedPoints[i].close;
                        if (Number.isFinite(value)) {
                            values.push(value);
                        }
                    }
                }

                if (values.length === 0) {
                    return null;
                }

                const minValue = Math.min(...values);
                const maxValue = Math.max(...values);
                const pad = Math.max((maxValue - minValue) * 0.05, 0.0001);

                return {
                    min: minValue - pad,
                    max: maxValue + pad,
                };
            }

            function buildVolumeRange(firstIndex, lastIndex) {
                const values = [];
                for (let i = firstIndex; i <= lastIndex; i += 1) {
                    const value = parsedPoints[i].volume;
                    if (Number.isFinite(value)) {
                        values.push(value);
                    }
                }

                if (values.length === 0) {
                    return { min: 0, max: 1 };
                }

                const maxValue = Math.max(...values);
                return {
                    min: 0,
                    max: maxValue === 0 ? 1 : maxValue * 1.08,
                };
            }

            function yForPrice(value, minValue, maxValue, priceTop, priceBottom) {
                const range = maxValue - minValue || 1;
                return priceTop + ((maxValue - value) / range) * (priceBottom - priceTop);
            }

            function yForVolume(value, maxValue, volumeTop, volumeBottom) {
                const safeMax = maxValue || 1;
                return volumeBottom - (value / safeMax) * (volumeBottom - volumeTop);
            }

            function renderGrid(context) {
                const {
                    priceMin,
                    priceMax,
                    volumeMax,
                    mode,
                    stepX,
                    chartWidth,
                    priceTop,
                    priceBottom,
                    volumeTop,
                    volumeBottom,
                    firstIndex,
                    lastIndex,
                } = context;
                const yTicks = 6;
                let markup = '';

                for (let tick = 0; tick <= yTicks; tick++) {
                    const y = priceTop + (tick / yTicks) * (priceBottom - priceTop);
                    const value = priceMax - (tick / yTicks) * (priceMax - priceMin);

                    markup += `<line x1="${padding.left}" y1="${y}" x2="${chartWidth - padding.right}" y2="${y}" stroke="#e5e7eb" stroke-width="1" />`;
                    markup += `<text x="${padding.left - 10}" y="${y + 4}" text-anchor="end" fill="#6b7280" font-size="12">${formatValue(value, mode)}</text>`;
                }

                const volumeTicks = 2;
                for (let tick = 0; tick <= volumeTicks; tick++) {
                    const y = volumeTop + (tick / volumeTicks) * (volumeBottom - volumeTop);
                    const value = volumeMax - (tick / volumeTicks) * volumeMax;
                    markup += `<line x1="${padding.left}" y1="${y}" x2="${chartWidth - padding.right}" y2="${y}" stroke="#f1f5f9" stroke-width="1" />`;
                    markup += `<text x="${padding.left - 10}" y="${y + 4}" text-anchor="end" fill="#94a3b8" font-size="11">${formatValue(value, 'volume')}</text>`;
                }

                const tickCount = Math.min(10, Math.max(2, lastIndex - firstIndex + 1));
                for (let tick = 0; tick < tickCount; tick += 1) {
                    const ratio = tickCount === 1 ? 0 : tick / (tickCount - 1);
                    const indexFloat = firstIndex + ratio * Math.max(lastIndex - firstIndex, 1);
                    const index = clamp(Math.round(indexFloat), firstIndex, lastIndex);
                    const x = xForIndex(index, stepX);
                    const label = formatDate(parsedPoints[index].date);

                    markup += `<line x1="${x}" y1="${priceTop}" x2="${x}" y2="${volumeBottom}" stroke="#d1d5db" stroke-width="1" />`;
                    markup += `<text x="${x}" y="${height - padding.bottom + 24}" text-anchor="middle" fill="#6b7280" font-size="12">${label}</text>`;
                }

                markup += `<line x1="${padding.left}" y1="${priceBottom}" x2="${chartWidth - padding.right}" y2="${priceBottom}" stroke="#cbd5e1" stroke-width="1" />`;
                markup += `<line x1="${padding.left}" y1="${volumeBottom}" x2="${chartWidth - padding.right}" y2="${volumeBottom}" stroke="#9ca3af" stroke-width="1" />`;
                markup += `<line x1="${padding.left}" y1="${priceTop}" x2="${padding.left}" y2="${volumeBottom}" stroke="#9ca3af" stroke-width="1" />`;

                return markup;
            }

            function renderClose(priceMin, priceMax, stepX, priceTop, priceBottom, firstIndex, lastIndex) {
                let path = '';
                let started = false;

                for (let index = firstIndex; index <= lastIndex; index += 1) {
                    const point = parsedPoints[index];
                    if (!Number.isFinite(point.close)) {
                        started = false;
                        continue;
                    }

                    const x = xForIndex(index, stepX);
                    const y = yForPrice(point.close, priceMin, priceMax, priceTop, priceBottom);

                    path += `${started ? ' L' : 'M'} ${x} ${y}`;
                    started = true;
                }

                return path === '' ? '' : `<path d="${path}" fill="none" stroke="#2563eb" stroke-width="2.1" />`;
            }

            function buildAggregatedVolumeBars(firstIndex, lastIndex, maxBars) {
                const visibleCount = (lastIndex - firstIndex) + 1;
                if (visibleCount <= maxBars || maxBars < 2) {
                    return null;
                }

                const bucketSize = Math.ceil(visibleCount / maxBars);
                const bars = [];

                for (let bucketStart = firstIndex; bucketStart <= lastIndex; bucketStart += bucketSize) {
                    const bucketEnd = Math.min(lastIndex, bucketStart + bucketSize - 1);
                    let maxVolume = null;

                    for (let index = bucketStart; index <= bucketEnd; index += 1) {
                        const volume = parsedPoints[index].volume;
                        if (!Number.isFinite(volume)) {
                            continue;
                        }
                        maxVolume = maxVolume === null ? volume : Math.max(maxVolume, volume);
                    }

                    if (!Number.isFinite(maxVolume)) {
                        continue;
                    }

                    bars.push({
                        index: bucketStart + ((bucketEnd - bucketStart) / 2),
                        volume: maxVolume,
                    });
                }

                return bars;
            }

            function renderVolumeBars(stepX, plotWidth, volumeMax, volumeTop, volumeBottom, firstIndex, lastIndex) {
                const targetBars = Math.max(60, Math.floor(plotWidth / minPixelsPerVolumeBar));
                const aggregatedBars = buildAggregatedVolumeBars(firstIndex, lastIndex, targetBars);
                const renderedCount = aggregatedBars ? aggregatedBars.length : Math.max((lastIndex - firstIndex) + 1, 1);
                const bodyWidth = Math.max(Math.min((plotWidth / renderedCount) * 0.72, 12), 1);
                let bars = '';

                if (aggregatedBars) {
                    for (const bar of aggregatedBars) {
                        const xCenter = xForIndex(bar.index, stepX);
                        const x = xCenter - (bodyWidth / 2);
                        const y = yForVolume(bar.volume, volumeMax, volumeTop, volumeBottom);
                        const h = volumeBottom - y;
                        bars += `<rect x="${x}" y="${y}" width="${bodyWidth}" height="${Math.max(h, 1)}" fill="#2563eb" opacity="0.35" />`;
                    }
                    return bars;
                }

                for (let index = firstIndex; index <= lastIndex; index += 1) {
                    const point = parsedPoints[index];
                    if (!Number.isFinite(point.volume)) {
                        continue;
                    }

                    const xCenter = xForIndex(index, stepX);
                    const x = xCenter - (bodyWidth / 2);
                    const y = yForVolume(point.volume, volumeMax, volumeTop, volumeBottom);
                    const h = volumeBottom - y;

                    bars += `<rect x="${x}" y="${y}" width="${bodyWidth}" height="${Math.max(h, 1)}" fill="#2563eb" opacity="0.35" />`;
                }

                return bars;
            }

            function buildAggregatedOhlcCandles(firstIndex, lastIndex, maxCandles) {
                const visibleCount = (lastIndex - firstIndex) + 1;
                if (visibleCount <= maxCandles || maxCandles < 2) {
                    return null;
                }

                const bucketSize = Math.ceil(visibleCount / maxCandles);
                const candles = [];

                for (let bucketStart = firstIndex; bucketStart <= lastIndex; bucketStart += bucketSize) {
                    const bucketEnd = Math.min(lastIndex, bucketStart + bucketSize - 1);
                    let firstPoint = null;
                    let lastPoint = null;
                    let high = -Infinity;
                    let low = Infinity;

                    for (let index = bucketStart; index <= bucketEnd; index += 1) {
                        const point = parsedPoints[index];
                        if (![point.open, point.high, point.low, point.close].every(Number.isFinite)) {
                            continue;
                        }

                        if (!firstPoint) {
                            firstPoint = point;
                        }

                        lastPoint = point;
                        high = Math.max(high, point.high);
                        low = Math.min(low, point.low);
                    }

                    if (!firstPoint || !lastPoint || !Number.isFinite(high) || !Number.isFinite(low)) {
                        continue;
                    }

                    candles.push({
                        index: bucketStart + ((bucketEnd - bucketStart) / 2),
                        open: firstPoint.open,
                        high,
                        low,
                        close: lastPoint.close,
                    });
                }

                return candles;
            }

            function renderOhlc(priceMin, priceMax, stepX, plotWidth, priceTop, priceBottom, firstIndex, lastIndex) {
                const targetCandles = Math.max(60, Math.floor(plotWidth / minPixelsPerCandle));
                const aggregatedCandles = buildAggregatedOhlcCandles(firstIndex, lastIndex, targetCandles);
                const candlesToRender = [];

                if (aggregatedCandles) {
                    candlesToRender.push(...aggregatedCandles);
                } else {
                    for (let index = firstIndex; index <= lastIndex; index += 1) {
                        const point = parsedPoints[index];
                        if (![point.open, point.high, point.low, point.close].every(Number.isFinite)) {
                            continue;
                        }

                        candlesToRender.push({
                            index,
                            open: point.open,
                            high: point.high,
                            low: point.low,
                            close: point.close,
                        });
                    }
                }

                const renderedCount = Math.max(candlesToRender.length, 1);
                const bodyWidth = Math.max(Math.min((plotWidth / renderedCount) * 0.72, 12), 1.4);
                let candles = '';

                for (const candle of candlesToRender) {
                    const x = xForIndex(candle.index, stepX);
                    const yHigh = yForPrice(candle.high, priceMin, priceMax, priceTop, priceBottom);
                    const yLow = yForPrice(candle.low, priceMin, priceMax, priceTop, priceBottom);
                    const yOpen = yForPrice(candle.open, priceMin, priceMax, priceTop, priceBottom);
                    const yClose = yForPrice(candle.close, priceMin, priceMax, priceTop, priceBottom);
                    const rising = candle.close >= candle.open;
                    const color = rising ? '#16a34a' : '#dc2626';
                    const bodyTop = Math.min(yOpen, yClose);
                    const bodyHeight = Math.max(Math.abs(yClose - yOpen), 1);

                    candles += `<line x1="${x}" y1="${yHigh}" x2="${x}" y2="${yLow}" stroke="${color}" stroke-width="1.1" />`;
                    candles += `<rect x="${x - bodyWidth / 2}" y="${bodyTop}" width="${bodyWidth}" height="${bodyHeight}" fill="${color}" />`;
                }

                return candles;
            }

            function getCursorPrice(point) {
                if (currentMode === 'ohlc') {
                    if (Number.isFinite(point.close)) {
                        return point.close;
                    }
                    if (Number.isFinite(point.open)) {
                        return point.open;
                    }
                    if (Number.isFinite(point.high)) {
                        return point.high;
                    }
                    if (Number.isFinite(point.low)) {
                        return point.low;
                    }
                    return null;
                }

                return Number.isFinite(point.close) ? point.close : null;
            }

            function getHoverPosition(stepX, chartWidth) {
                if (hoverClientX === null) {
                    return null;
                }

                const rect = chartContainer.getBoundingClientRect();
                const x = clamp(hoverClientX - rect.left, padding.left, chartWidth - padding.right);
                const indexFloat = clamp(
                    startIndex + ((x - padding.left) / Math.max(stepX, 0.0001)),
                    0,
                    parsedPoints.length - 1
                );

                return { x, indexFloat };
            }

            function getInterpolatedClose(indexFloat) {
                const lowerIndex = clamp(Math.floor(indexFloat), 0, parsedPoints.length - 1);
                const upperIndex = clamp(Math.ceil(indexFloat), 0, parsedPoints.length - 1);
                const lowerValue = parsedPoints[lowerIndex]?.close;
                const upperValue = parsedPoints[upperIndex]?.close;

                if (Number.isFinite(lowerValue) && Number.isFinite(upperValue)) {
                    const weight = indexFloat - lowerIndex;
                    return lowerValue + ((upperValue - lowerValue) * weight);
                }

                if (Number.isFinite(lowerValue)) {
                    return lowerValue;
                }

                if (Number.isFinite(upperValue)) {
                    return upperValue;
                }

                return null;
            }

            function renderCursorOverlay(stepX, firstIndex, lastIndex, priceMin, priceMax, priceTop, priceBottom, volumeBottom, chartWidth, mode) {
                const hoverPosition = getHoverPosition(stepX, chartWidth);
                if (!hoverPosition) {
                    return '';
                }

                const hoveredIndex = clamp(Math.round(hoverPosition.indexFloat), firstIndex, lastIndex);
                const point = parsedPoints[hoveredIndex];
                if (!point) {
                    return '';
                }

                const x = hoverPosition.x;
                const cursorPrice = mode === 'close'
                    ? getInterpolatedClose(hoverPosition.indexFloat)
                    : getCursorPrice(point);
                const dateLabel = formatDate(point.date);
                const xLabelWidth = Math.max(60, dateLabel.length * 7 + 14);
                const xLabelX = clamp(x - (xLabelWidth / 2), padding.left, chartWidth - padding.right - xLabelWidth);
                const xLabelY = volumeBottom + 6;

                let markup = `<line x1="${x}" y1="${priceTop}" x2="${x}" y2="${volumeBottom}" stroke="#64748b" stroke-width="1" stroke-dasharray="4 4" />`;
                markup += `<rect x="${xLabelX}" y="${xLabelY}" width="${xLabelWidth}" height="16" rx="3" fill="#e5e7eb" />`;
                markup += `<text x="${xLabelX + (xLabelWidth / 2)}" y="${xLabelY + 12}" text-anchor="middle" fill="#334155" font-size="11" font-weight="600">${dateLabel}</text>`;

                if (Number.isFinite(cursorPrice)) {
                    const y = yForPrice(cursorPrice, priceMin, priceMax, priceTop, priceBottom);
                    const yLabel = cursorPrice.toFixed(2);
                    const yLabelWidth = Math.max(52, yLabel.length * 7 + 14);
                    const yLabelY = clamp(y - 8, priceTop, volumeBottom - 16);

                    markup += `<line x1="${padding.left}" y1="${y}" x2="${chartWidth - padding.right}" y2="${y}" stroke="#64748b" stroke-width="1" stroke-dasharray="4 4" />`;
                    markup += `<circle cx="${x}" cy="${y}" r="3.5" fill="#1e40af" stroke="#ffffff" stroke-width="1.2" />`;
                    markup += `<rect x="6" y="${yLabelY}" width="${yLabelWidth}" height="16" rx="3" fill="#e5e7eb" />`;
                    markup += `<text x="${6 + (yLabelWidth / 2)}" y="${yLabelY + 12}" text-anchor="middle" fill="#334155" font-size="11" font-weight="600">${yLabel}</text>`;
                }

                return markup;
            }

            function draw() {
                const mode = currentMode;
                const chartWidth = getContainerWidth();
                const plotWidth = getPlotWidth();
                const stepX = getStepX(plotWidth);
                const { first, last } = buildVisibleIndexRange();

                if (hoverClientX !== null) {
                    updateHoverIndexFromClientX(hoverClientX);
                }

                const priceRange = buildPriceRange(mode, first, last);
                if (!priceRange) {
                    hoverInfo.textContent = '';
                    svg.innerHTML = '';
                    return;
                }

                const volumeRange = buildVolumeRange(first, last);

                const priceTop = padding.top;
                const priceBottom = height - padding.bottom - volumePanelHeight - panelGap;
                const volumeTop = priceBottom + panelGap;
                const volumeBottom = height - padding.bottom;

                svg.setAttribute('width', String(chartWidth));
                svg.setAttribute('height', String(height));
                svg.setAttribute('viewBox', `0 0 ${chartWidth} ${height}`);

                const grid = renderGrid({
                    priceMin: priceRange.min,
                    priceMax: priceRange.max,
                    volumeMax: volumeRange.max,
                    mode,
                    stepX,
                    chartWidth,
                    priceTop,
                    priceBottom,
                    volumeTop,
                    volumeBottom,
                    firstIndex: first,
                    lastIndex: last,
                });
                const volumeBars = renderVolumeBars(stepX, plotWidth, volumeRange.max, volumeTop, volumeBottom, first, last);
                const series = mode === 'ohlc'
                    ? renderOhlc(priceRange.min, priceRange.max, stepX, plotWidth, priceTop, priceBottom, first, last)
                    : renderClose(priceRange.min, priceRange.max, stepX, priceTop, priceBottom, first, last);
                const cursorOverlay = renderCursorOverlay(stepX, first, last, priceRange.min, priceRange.max, priceTop, priceBottom, volumeBottom, chartWidth, mode);
                const infoIndex = Number.isInteger(hoverIndex) && hoverIndex >= first && hoverIndex <= last ? hoverIndex : last;
                hoverInfo.textContent = formatHoverText(parsedPoints[infoIndex]);

                svg.innerHTML = `${grid}${volumeBars}${series}${cursorOverlay}`;
            }

            function zoomByWheel(event) {
                event.preventDefault();

                const minBars = minBarsPerView;
                const maxBars = maxBarsPerView;
                if (minBars === maxBars) {
                    return;
                }

                const rect = chartContainer.getBoundingClientRect();
                const plotWidth = getPlotWidth();
                const focusRatio = clamp((event.clientX - rect.left - padding.left) / plotWidth, 0, 1);
                const focusIndex = startIndex + focusRatio * Math.max(barsPerView - 1, 1);
                hoverClientX = event.clientX;

                const deltaMagnitude = Math.min(Math.abs(event.deltaY), 100);
                const zoomPercent = (deltaMagnitude / 100) * 0.5; // max 50% per wheel event
                const factor = event.deltaY > 0 ? 1 + zoomPercent : 1 - zoomPercent;
                const nextBarsPerView = clamp(barsPerView * factor, minBars, maxBars);
                barsPerView = nextBarsPerView;
                startIndex = focusIndex - focusRatio * Math.max(barsPerView - 1, 1);
                startIndex = clamp(startIndex, 0, maxStartIndex());
                updateHoverIndexFromClientX(event.clientX);

                scheduleDraw();
            }

            function updateHoverIndexFromClientX(clientX) {
                hoverClientX = clientX;
                const rect = chartContainer.getBoundingClientRect();
                const leftBound = rect.left + padding.left;
                const rightBound = rect.right - padding.right;

                if (clientX < leftBound || clientX > rightBound) {
                    hoverIndex = null;
                    return;
                }

                const stepX = getStepX(getPlotWidth());
                const index = startIndex + ((clientX - leftBound) / Math.max(stepX, 1));
                hoverIndex = clamp(Math.round(index), 0, parsedPoints.length - 1);
            }

            function onDragStart(event) {
                if (event.button !== 0) {
                    return;
                }

                isDragging = true;
                lastDragX = event.clientX;
            }

            function onDragMove(event) {
                if (!isDragging) {
                    return;
                }

                const dx = event.clientX - lastDragX;
                lastDragX = event.clientX;

                const stepX = getStepX(getPlotWidth());
                startIndex -= dx / Math.max(stepX, 1);
                startIndex = clamp(startIndex, 0, maxStartIndex());

                scheduleDraw();
            }

            function onDragEnd() {
                if (!isDragging) {
                    return;
                }

                isDragging = false;
            }

            function onHoverMove(event) {
                if (isDragging) {
                    return;
                }

                updateHoverIndexFromClientX(event.clientX);
                scheduleDraw();
            }

            function onHoverLeave() {
                hoverIndex = null;
                hoverClientX = null;
                scheduleDraw();
            }

            function setMode(nextMode) {
                currentMode = nextMode;
                modeButtons.forEach((button) => {
                    const isActive = button.dataset.mode === nextMode;
                    button.classList.toggle('bg-blue-600', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('bg-white', !isActive);
                    button.classList.toggle('text-gray-700', !isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
                scheduleDraw();
            }

            function setRange(nextRange) {
                currentRange = Object.prototype.hasOwnProperty.call(rangeBarCounts, nextRange)
                    ? nextRange
                    : defaultRange;
                barsPerView = getBarsForRange(currentRange);
                startIndex = clamp(parsedPoints.length - barsPerView, 0, maxStartIndex());

                rangeButtons.forEach((button) => {
                    const isActive = button.dataset.range === currentRange;
                    button.classList.toggle('bg-slate-700', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('bg-white', !isActive);
                    button.classList.toggle('text-gray-700', !isActive);
                });

                if (hoverClientX !== null) {
                    updateHoverIndexFromClientX(hoverClientX);
                }

                scheduleDraw();
            }

            modeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    setMode(button.dataset.mode === 'ohlc' ? 'ohlc' : 'close');
                });
            });
            rangeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    setRange(button.dataset.range || defaultRange);
                });
            });
            chartContainer.addEventListener('wheel', zoomByWheel, { passive: false });
            chartContainer.addEventListener('mousedown', onDragStart);
            chartContainer.addEventListener('mousemove', onHoverMove);
            chartContainer.addEventListener('mouseleave', onHoverLeave);
            window.addEventListener('mousemove', onDragMove);
            window.addEventListener('mouseup', onDragEnd);
            window.addEventListener('resize', scheduleDraw);

            setMode('close');
            setRange(defaultRange);
        })();
    </script>
@endif

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
        const fallbackYears = Array.isArray(fallbackYearsRaw)
            ? fallbackYearsRaw
            : [];
        const rawFundamentals = @json($fundamentalData ?? []);
        const statementOrder = ['balance_sheet', 'cash_flow_statement', 'income_statement'];
        const statementLabels = {
            balance_sheet: 'Balance sheet',
            cash_flow_statement: 'Cash Flow Statement',
            income_statement: 'Income Statement (P&L)',
        };
        let selectedStatement = 'balance_sheet';
        let selectedPeriod = 'annual';
        let fundamentalsByYear = normalizeFundamentals(rawFundamentals, fallbackYears);
        let years = sortYearsDesc(Object.keys(fundamentalsByYear));
        let selectedYear = years[0] || null;

        function createEmptyStatementSet() {
            return {
                balance_sheet: {},
                cash_flow_statement: {},
                income_statement: {},
            };
        }

        function ensureYear(store, year) {
            if (!store[year]) {
                store[year] = {
                    annual: createEmptyStatementSet(),
                    quarters: {},
                };
            }
            return store[year];
        }

        function normalizeStatementKey(key) {
            const value = String(key || '')
                .trim()
                .toLowerCase()
                .replace(/[\s-]+/g, '_');

            if (value === 'cash_flow' || value === 'cashflow' || value === 'cash_flow_statement') {
                return 'cash_flow_statement';
            }

            if (value === 'income_statement' || value === 'income' || value === 'income_statement_(p&l)' || value === 'p&l' || value === 'pl') {
                return 'income_statement';
            }

            return value === 'balance_sheet' ? 'balance_sheet' : null;
        }

        function normalizeQuarterKey(value) {
            const raw = String(value || '')
                .trim()
                .toUpperCase()
                .replace(/\s+/g, '');

            if (raw === '' || raw === 'ANNUAL' || raw === 'FY' || raw === 'YEAR') {
                return 'annual';
            }

            const digitMatch = raw.match(/Q?([1-4])/);
            if (!digitMatch) {
                return 'annual';
            }

            return `Q${digitMatch[1]}`;
        }

        function normalizeStatementPayload(payload) {
            return payload && typeof payload === 'object' && !Array.isArray(payload) ? payload : {};
        }

        function normalizeFundamentals(raw, yearsFallback) {
            const normalized = {};

            yearsFallback.forEach((year) => {
                const normalizedYear = String(year || '').trim();
                if (/^\d{4}$/.test(normalizedYear)) {
                    ensureYear(normalized, normalizedYear);
                }
            });

            if (!raw || typeof raw !== 'object') {
                return normalized;
            }

            if (Array.isArray(raw)) {
                raw.forEach((row) => {
                    if (!row || typeof row !== 'object') {
                        return;
                    }

                    const year = String(row.year || '').trim();
                    if (!/^\d{4}$/.test(year)) {
                        return;
                    }

                    const statementKey = normalizeStatementKey(row.statement_type || row.statement);
                    if (!statementKey) {
                        return;
                    }

                    const periodKey = normalizeQuarterKey(row.quarter || row.period);
                    const payload = normalizeStatementPayload(row.data || row.values || row.metrics || row.payload);
                    const yearNode = ensureYear(normalized, year);

                    if (periodKey === 'annual') {
                        yearNode.annual[statementKey] = payload;
                        return;
                    }

                    if (!yearNode.quarters[periodKey]) {
                        yearNode.quarters[periodKey] = createEmptyStatementSet();
                    }
                    yearNode.quarters[periodKey][statementKey] = payload;
                });

                return normalized;
            }

            Object.entries(raw).forEach(([year, yearValue]) => {
                const normalizedYear = String(year || '').trim();
                if (!/^\d{4}$/.test(normalizedYear)) {
                    return;
                }

                const yearNode = ensureYear(normalized, normalizedYear);
                if (!yearValue || typeof yearValue !== 'object') {
                    return;
                }

                const annualNode = yearValue.annual && typeof yearValue.annual === 'object'
                    ? yearValue.annual
                    : yearValue;

                statementOrder.forEach((statementKey) => {
                    yearNode.annual[statementKey] = normalizeStatementPayload(annualNode[statementKey]);
                });

                const quarterNode = yearValue.quarters && typeof yearValue.quarters === 'object'
                    ? yearValue.quarters
                    : {};

                Object.entries(quarterNode).forEach(([quarter, quarterValue]) => {
                    const normalizedQuarter = normalizeQuarterKey(quarter);
                    if (normalizedQuarter === 'annual') {
                        return;
                    }

                    if (!yearNode.quarters[normalizedQuarter]) {
                        yearNode.quarters[normalizedQuarter] = createEmptyStatementSet();
                    }

                    if (!quarterValue || typeof quarterValue !== 'object') {
                        return;
                    }

                    statementOrder.forEach((statementKey) => {
                        yearNode.quarters[normalizedQuarter][statementKey] = normalizeStatementPayload(quarterValue[statementKey]);
                    });
                });
            });

            return normalized;
        }

        function sortYearsDesc(yearKeys) {
            return yearKeys
                .filter((year) => /^\d{4}$/.test(year))
                .sort((left, right) => Number(right) - Number(left));
        }

        function getQuarterKeys(year) {
            const quarterMap = fundamentalsByYear[year]?.quarters || {};
            return Object.keys(quarterMap).sort((left, right) => Number(left.slice(1)) - Number(right.slice(1)));
        }

        function formatQuarterLabel(quarter) {
            const labels = {
                Q1: '1st quarter',
                Q2: '2nd quarter',
                Q3: '3rd quarter',
                Q4: '4th quarter',
            };
            return labels[quarter] || quarter;
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function parseMaybeNumber(value) {
            if (typeof value === 'number' && Number.isFinite(value)) {
                return value;
            }

            if (typeof value === 'string' && value.trim() !== '') {
                const numberValue = Number(value.replace(/,/g, ''));
                if (Number.isFinite(numberValue)) {
                    return numberValue;
                }
            }

            return null;
        }

        function formatValue(value) {
            if (value === null || typeof value === 'undefined') {
                return '—';
            }

            const numericValue = parseMaybeNumber(value);
            if (numericValue !== null) {
                const absValue = Math.abs(numericValue);
                const decimals = absValue >= 1 ? 2 : 4;
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: decimals,
                }).format(numericValue);
            }

            if (typeof value === 'boolean') {
                return value ? 'true' : 'false';
            }

            return String(value);
        }

        function flattenRows(source, prefix) {
            if (!source || typeof source !== 'object' || Array.isArray(source)) {
                return [];
            }

            let rows = [];
            Object.entries(source).forEach(([key, value]) => {
                const label = prefix ? `${prefix} / ${key}` : key;
                if (value && typeof value === 'object' && !Array.isArray(value)) {
                    rows = rows.concat(flattenRows(value, label));
                    return;
                }

                rows.push([label, value]);
            });

            return rows;
        }

        function renderYearList() {
            if (years.length === 0) {
                yearList.innerHTML = '<p class="text-sm text-gray-500 px-2 py-1">No years available</p>';
                return;
            }

            const html = years.map((year) => {
                const isActiveYear = year === selectedYear;
                const quarters = getQuarterKeys(year);
                const yearClass = isActiveYear
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-gray-700 border-gray-300 hover:border-blue-300 hover:text-blue-700';
                const quarterHtml = isActiveYear && quarters.length > 0
                    ? `
                        <div class="ml-3 mt-2 border-l border-gray-300 pl-2">
                            <label for="quarter-select-${escapeHtml(year)}" class="block text-[11px] font-medium text-gray-500 mb-1">
                                Quarter (10-Q)
                            </label>
                            <select
                                id="quarter-select-${escapeHtml(year)}"
                                data-role="quarter-select"
                                data-year="${escapeHtml(year)}"
                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-700 focus:border-blue-500 focus:outline-none"
                            >
                                <option value="annual" ${selectedPeriod === 'annual' ? 'selected' : ''}>Annual</option>
                                ${quarters.map((quarter) => `
                                    <option value="${escapeHtml(quarter)}" ${selectedPeriod === quarter ? 'selected' : ''}>
                                        ${escapeHtml(formatQuarterLabel(quarter))}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    `
                    : '';

                return `
                    <div>
                        <button
                            type="button"
                            data-role="year"
                            data-year="${escapeHtml(year)}"
                            class="block w-full rounded-md border px-3 py-2 text-left text-sm font-semibold transition-colors ${yearClass}"
                        >
                            ${escapeHtml(year)}
                        </button>
                        ${quarterHtml}
                    </div>
                `;
            }).join('');

            yearList.innerHTML = html;
        }

        function renderStatementTabs() {
            const buttons = Array.from(statementTabs.querySelectorAll('.fundamentals-statement-btn'));
            buttons.forEach((button, index) => {
                const isActive = button.dataset.statement === selectedStatement;
                button.classList.toggle('bg-slate-700', isActive);
                button.classList.toggle('text-white', isActive);
                button.classList.toggle('bg-white', !isActive);
                button.classList.toggle('text-gray-700', !isActive);
                button.classList.toggle('border-r', index < buttons.length - 1);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function renderContent() {
            if (!selectedYear || !fundamentalsByYear[selectedYear]) {
                periodTitle.textContent = '';
                content.innerHTML = '<div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">No fundamental data available for this instrument.</div>';
                return;
            }

            const yearNode = fundamentalsByYear[selectedYear];
            const periodLabel = selectedPeriod === 'annual'
                ? `${selectedYear} - Annual`
                : `${selectedYear} - ${formatQuarterLabel(selectedPeriod)}`;
            periodTitle.textContent = periodLabel;

            const source = selectedPeriod === 'annual'
                ? yearNode.annual
                : (yearNode.quarters[selectedPeriod] || createEmptyStatementSet());
            const statementData = source[selectedStatement] || {};
            const rows = flattenRows(statementData, '');

            if (rows.length === 0) {
                content.innerHTML = `
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-600">
                        No ${escapeHtml(statementLabels[selectedStatement] || selectedStatement)} data for the selected period.
                    </div>
                `;
                return;
            }

            content.innerHTML = `
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700">Metric</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-700">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            ${rows.map(([label, value]) => `
                                <tr>
                                    <td class="px-4 py-2.5 text-gray-700">${escapeHtml(label)}</td>
                                    <td class="px-4 py-2.5 text-right tabular-nums text-gray-900">${escapeHtml(formatValue(value))}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        yearList.addEventListener('click', function (event) {
            const button = event.target.closest('button[data-role]');
            if (!button) {
                return;
            }

            if (button.dataset.role === 'year') {
                selectedYear = button.dataset.year || null;
                selectedPeriod = 'annual';
                renderYearList();
                renderContent();
                return;
            }

        });

        yearList.addEventListener('change', function (event) {
            const select = event.target.closest('select[data-role="quarter-select"]');
            if (!select) {
                return;
            }

            selectedYear = select.dataset.year || null;
            selectedPeriod = select.value || 'annual';
            renderYearList();
            renderContent();
        });

        statementTabs.addEventListener('click', function (event) {
            const button = event.target.closest('button[data-statement]');
            if (!button) {
                return;
            }

            const nextStatement = normalizeStatementKey(button.dataset.statement);
            if (!nextStatement) {
                return;
            }

            selectedStatement = nextStatement;
            renderStatementTabs();
            renderContent();
        });

        if (selectedYear && !fundamentalsByYear[selectedYear]) {
            selectedYear = years[0] || null;
        }

        renderYearList();
        renderStatementTabs();
        renderContent();
    })();
</script>
@endsection
