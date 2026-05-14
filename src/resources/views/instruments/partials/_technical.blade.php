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
                {{-- Chart toolbar — range + interval --}}
                <div class="flex flex-wrap items-center justify-end gap-2 mb-2">
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

                {{-- Chart (full width) --}}
                <div id="chart-container" class="min-w-0 w-full relative overflow-hidden rounded-lg cursor-none select-none mb-4">
                    <div
                        id="chart-hover-info"
                        class="absolute left-3 top-2 z-10 text-xs text-gray-500 pointer-events-none font-medium tracking-wide"
                    ></div>
                    <svg id="price-chart" class="h-[420px] w-full"></svg>
                </div>

                {{-- Engela trijstūris toolbar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mt-4 mb-2">
                    <h3 class="text-sm font-semibold text-gray-700">Engela trijstūris</h3>
                    <div id="engel-days-group" class="inline-flex rounded border border-gray-300 overflow-hidden bg-white">
                        <button type="button" class="engel-days-btn px-2 py-1 text-[11px] font-medium bg-slate-700 text-white border-r border-gray-300" data-days="30">30d</button>
                        <button type="button" class="engel-days-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700 border-r border-gray-300" data-days="60">60d</button>
                        <button type="button" class="engel-days-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700 border-r border-gray-300" data-days="90">90d</button>
                        <button type="button" class="engel-days-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700 border-r border-gray-300" data-days="180">180d</button>
                        <button type="button" class="engel-days-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700 border-r border-gray-300" data-days="365">1y</button>
                        <button type="button" class="engel-days-btn px-2 py-1 text-[11px] font-medium bg-white text-gray-700" data-days="730">2y</button>
                    </div>
                </div>

                {{-- Engel canvas — max 480px square, centered --}}
                <div id="engel-triangle-container" class="min-w-0 flex flex-col items-center">
                    <div class="relative rounded-lg border border-gray-100 bg-white overflow-hidden flex items-center justify-center w-full max-w-[480px]" style="aspect-ratio:1/1">
                        <canvas id="engel-canvas" class="block"></canvas>
                        <div id="engel-spinner" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10 hidden">
                            <svg class="animate-spin h-8 w-8 text-slate-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span class="text-xs text-slate-400 font-medium">Computing...</span>
                        </div>
                        <div id="engel-tooltip" class="absolute z-20 hidden rounded bg-gray-900 px-2 py-1 text-[10px] text-white shadow-lg pointer-events-none whitespace-nowrap leading-relaxed"></div>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-gray-600">
                    Šim instrumentam vēl nav cenas datu tabulā <code>prices_daily</code>.
                </div>
            @endif
        </section>

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
                date: point.time,
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
            let engelDays = 30;

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
                const year = date.getUTCFullYear();

                return `${day}/${month}/${year}`;
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

            function renderEngelBracket(stepX, priceTop, volumeBottom, chartWidth) {
                const n = parsedPoints.length;
                if (engelDays <= 0 || n < 2) return '';

                const bracketStart = Math.max(0, n - engelDays);
                const bracketEnd = n - 1;

                const x1 = xForIndex(bracketStart, stepX);
                const x2 = xForIndex(bracketEnd, stepX);

                // Only draw if at least partially visible
                if (x2 < padding.left || x1 > chartWidth - padding.right) return '';

                const clampedX1 = Math.max(x1, padding.left);
                const clampedX2 = Math.min(x2, chartWidth - padding.right);
                if (clampedX2 - clampedX1 < 2) return '';

                const y = volumeBottom + 2;
                const tickH = 6;
                let markup = '';

                // Shaded region behind the chart
                markup += `<rect x="${clampedX1}" y="${priceTop}" width="${clampedX2 - clampedX1}" height="${volumeBottom - priceTop}" fill="#6366f1" opacity="0.04" />`;

                // Bracket: |-----|
                markup += `<line x1="${clampedX1}" y1="${y}" x2="${clampedX1}" y2="${y + tickH}" stroke="#6366f1" stroke-width="1.5" />`;
                markup += `<line x1="${clampedX1}" y1="${y + tickH / 2}" x2="${clampedX2}" y2="${y + tickH / 2}" stroke="#6366f1" stroke-width="1.5" />`;
                markup += `<line x1="${clampedX2}" y1="${y}" x2="${clampedX2}" y2="${y + tickH}" stroke="#6366f1" stroke-width="1.5" />`;

                // Label
                const midX = (clampedX1 + clampedX2) / 2;
                markup += `<text x="${midX}" y="${y + tickH + 11}" text-anchor="middle" fill="#6366f1" font-size="10" font-weight="600">${engelDays}d</text>`;

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
                const engelBracket = renderEngelBracket(stepX, priceTop, volumeBottom, chartWidth);
                const infoIndex = Number.isInteger(hoverIndex) && hoverIndex >= first && hoverIndex <= last ? hoverIndex : last;
                hoverInfo.textContent = formatHoverText(parsedPoints[infoIndex]);

                svg.innerHTML = `${grid}${engelBracket}${volumeBars}${series}${cursorOverlay}`;
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

            // Listen for engel triangle period changes
            window.addEventListener('engel-period-changed', function (e) {
                engelDays = e.detail.days || 30;
                scheduleDraw();
            });

            setMode('close');
            setRange(defaultRange);
        })();
    </script>

    <script>
        (function () {
            const allPrices = @json($priceSeries);
            const canvas = document.getElementById('engel-canvas');
            const tooltipEl = document.getElementById('engel-tooltip');
            const spinner = document.getElementById('engel-spinner');
            const container = document.getElementById('engel-triangle-container');
            const daysButtons = Array.from(document.querySelectorAll('.engel-days-btn'));
            if (!canvas || !tooltipEl || !container || !Array.isArray(allPrices) || allPrices.length < 2) return;

            const allParsed = allPrices.map(p => ({ date: p.time, close: parseFloat(p.close) }));
            const dpr = window.devicePixelRatio || 1;
            const ctx = canvas.getContext('2d');

            let currentN = 30;
            let prices = [];
            let cachedMaxDiff = 0;
            let drawAbortController = null;

            // Zoom/pan state — viewport in cell coordinates
            let vpX = 0, vpY = 0; // top-left cell (fractional)
            let vpSize = 30;      // how many cells visible across the viewport
            let isDragging = false, lastDragX = 0, lastDragY = 0;

            function getContainerSize() {
                const wrapper = canvas.parentElement;
                return Math.floor(wrapper.clientWidth);
            }

            function clampViewport() {
                const n = prices.length;
                vpSize = Math.max(4, Math.min(vpSize, n));
                vpX = Math.max(0, Math.min(vpX, n - vpSize));
                vpY = Math.max(0, Math.min(vpY, n - vpSize));
            }

            function buildAndDraw(N) {
                if (drawAbortController) drawAbortController.abort();

                currentN = N;
                prices = allParsed.slice(-N);
                const n = prices.length;
                if (n < 2) return;

                // Reset viewport
                vpX = 0; vpY = 0; vpSize = n;
                cachedMaxDiff = computeMaxDiff(n);

                drawEngel();

                // Notify the price chart about the selected period
                window.dispatchEvent(new CustomEvent('engel-period-changed', { detail: { days: n } }));
            }

            function drawEngel() {
                if (drawAbortController) drawAbortController.abort();
                const n = prices.length;
                if (n < 2) return;

                clampViewport();

                const size = getContainerSize();
                canvas.width = size * dpr;
                canvas.height = size * dpr;
                canvas.style.width = size + 'px';
                canvas.style.height = size + 'px';
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                const cellPx = size / vpSize;
                const r0 = Math.floor(vpY);
                const r1 = Math.min(n - 1, Math.ceil(vpY + vpSize));
                const c0 = Math.floor(vpX);
                const c1 = Math.min(n - 1, Math.ceil(vpX + vpSize));
                const totalVisible = (r1 - r0 + 1) * (c1 - c0 + 1);
                const CHUNK_THRESHOLD = 60000;

                if (totalVisible > CHUNK_THRESHOLD) {
                    spinner.classList.remove('hidden');
                    drawEngelChunked(n, size, cellPx, r0, r1, c0, c1);
                } else {
                    spinner.classList.add('hidden');
                    drawEngelImmediate(n, size, cellPx, r0, r1, c0, c1);
                }
            }

            function drawEngelImmediate(n, size, cellPx, r0, r1, c0, c1) {
                const drawGrid = cellPx >= 4;
                const drawText = cellPx >= 28;
                for (let row = r0; row <= r1; row++) {
                    for (let col = c0; col <= c1; col++) {
                        const x = (col - vpX) * cellPx;
                        const y = (row - vpY) * cellPx;
                        ctx.fillStyle = getCellColor(row, col, cachedMaxDiff);
                        ctx.fillRect(x, y, cellPx, cellPx);
                        if (drawGrid) {
                            ctx.strokeStyle = '#e2e8f0';
                            ctx.lineWidth = 0.5;
                            ctx.strokeRect(x + 0.25, y + 0.25, cellPx - 0.5, cellPx - 0.5);
                        }
                        if (drawText && col >= row) {
                            const val = col === row ? prices[row].close : prices[col].close - prices[row].close;
                            const label = col === row ? '$' + val.toFixed(1) : (val >= 0 ? '+' : '') + val.toFixed(1);
                            ctx.fillStyle = '#334155';
                            ctx.font = `${Math.max(8, Math.min(cellPx * 0.28, 12))}px sans-serif`;
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(label, x + cellPx / 2, y + cellPx / 2);
                        }
                    }
                }
            }

            function drawEngelChunked(n, size, cellPx, r0, r1, c0, c1) {
                const controller = { aborted: false };
                drawAbortController = { abort() { controller.aborted = true; } };

                const usePixel = cellPx < 2;

                if (usePixel) {
                    const pxSize = Math.ceil(size * dpr);
                    const imgData = ctx.createImageData(pxSize, pxSize);
                    const data = imgData.data;
                    const cellPxDpr = cellPx * dpr;
                    let rowIdx = r0;
                    const ROWS_PER_CHUNK = Math.max(1, Math.floor(40000 / (c1 - c0 + 1)));

                    function chunk() {
                        if (controller.aborted) return;
                        const end = Math.min(rowIdx + ROWS_PER_CHUNK, r1 + 1);
                        for (let row = rowIdx; row < end; row++) {
                            const py0 = Math.max(0, Math.floor((row - vpY) * cellPxDpr));
                            const py1 = Math.min(pxSize, Math.floor((row - vpY + 1) * cellPxDpr));
                            for (let col = c0; col <= c1; col++) {
                                const px0 = Math.max(0, Math.floor((col - vpX) * cellPxDpr));
                                const px1 = Math.min(pxSize, Math.floor((col - vpX + 1) * cellPxDpr));
                                const rgb = parseRgb(getCellColor(row, col, cachedMaxDiff));
                                for (let py = py0; py < py1; py++) {
                                    for (let px = px0; px < px1; px++) {
                                        const idx = (py * pxSize + px) * 4;
                                        data[idx] = rgb[0]; data[idx+1] = rgb[1]; data[idx+2] = rgb[2]; data[idx+3] = 255;
                                    }
                                }
                            }
                        }
                        rowIdx = end;
                        if (rowIdx <= r1) {
                            requestAnimationFrame(chunk);
                        } else {
                            ctx.putImageData(imgData, 0, 0);
                            spinner.classList.add('hidden');
                            drawAbortController = null;
                        }
                    }
                    requestAnimationFrame(chunk);
                } else {
                    let rowIdx = r0;
                    const ROWS_PER_CHUNK = Math.max(1, Math.floor(20000 / (c1 - c0 + 1)));

                    function chunk() {
                        if (controller.aborted) return;
                        const end = Math.min(rowIdx + ROWS_PER_CHUNK, r1 + 1);
                        for (let row = rowIdx; row < end; row++) {
                            for (let col = c0; col <= c1; col++) {
                                const x = (col - vpX) * cellPx;
                                const y = (row - vpY) * cellPx;
                                ctx.fillStyle = getCellColor(row, col, cachedMaxDiff);
                                ctx.fillRect(x, y, cellPx, cellPx);
                                if (cellPx >= 4) {
                                    ctx.strokeStyle = '#e2e8f0';
                                    ctx.lineWidth = 0.5;
                                    ctx.strokeRect(x + 0.25, y + 0.25, cellPx - 0.5, cellPx - 0.5);
                                }
                            }
                        }
                        rowIdx = end;
                        if (rowIdx <= r1) {
                            requestAnimationFrame(chunk);
                        } else {
                            spinner.classList.add('hidden');
                            drawAbortController = null;
                        }
                    }
                    requestAnimationFrame(chunk);
                }
            }

            function computeMaxDiff(n) {
                let maxDiff = 0;
                for (let i = 0; i < n; i++) {
                    for (let j = i + 1; j < n; j++) {
                        const d = Math.abs(prices[j].close - prices[i].close);
                        if (d > maxDiff) maxDiff = d;
                    }
                }
                return maxDiff;
            }

            function getCellColor(row, col, maxDiff) {
                if (col < row) return '#fafafa';
                if (col === row) return '#cbd5e1';
                const diff = prices[col].close - prices[row].close;
                const t = maxDiff > 0 ? Math.min(Math.abs(diff) / maxDiff, 1) : 0;
                if (diff >= 0) {
                    return `rgb(${Math.round(240 - t * 186)},${Math.round(250 - t * 50)},${Math.round(240 - t * 186)})`;
                } else {
                    return `rgb(${Math.round(250 - t * 16)},${Math.round(240 - t * 186)},${Math.round(240 - t * 186)})`;
                }
            }

            const colorCache = {};
            function parseRgb(color) {
                if (colorCache[color]) return colorCache[color];
                let r, g, b;
                if (color.startsWith('#')) {
                    const hex = color.slice(1);
                    r = parseInt(hex.slice(0, 2), 16);
                    g = parseInt(hex.slice(2, 4), 16);
                    b = parseInt(hex.slice(4, 6), 16);
                } else {
                    const m = color.match(/(\d+)/g);
                    r = parseInt(m[0]); g = parseInt(m[1]); b = parseInt(m[2]);
                }
                const result = [r, g, b];
                colorCache[color] = result;
                return result;
            }

            // --- Zoom (scroll wheel) ---
            canvas.addEventListener('wheel', (e) => {
                e.preventDefault();
                const n = prices.length;
                if (n < 2) return;

                const rect = canvas.getBoundingClientRect();
                const mx = (e.clientX - rect.left) / rect.width;
                const my = (e.clientY - rect.top) / rect.height;

                const focusCellX = vpX + mx * vpSize;
                const focusCellY = vpY + my * vpSize;

                const delta = Math.min(Math.abs(e.deltaY), 100);
                const zoomFactor = e.deltaY > 0 ? 1 + delta / 200 : 1 - delta / 200;
                vpSize = Math.max(4, Math.min(vpSize * zoomFactor, n));

                vpX = focusCellX - mx * vpSize;
                vpY = focusCellY - my * vpSize;
                clampViewport();
                drawEngel();
            }, { passive: false });

            // --- Pan (drag) ---
            canvas.addEventListener('mousedown', (e) => {
                if (e.button !== 0) return;
                isDragging = true;
                lastDragX = e.clientX;
                lastDragY = e.clientY;
                canvas.style.cursor = 'grabbing';
            });

            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                const rect = canvas.getBoundingClientRect();
                const cellPx = rect.width / vpSize;
                vpX -= (e.clientX - lastDragX) / cellPx;
                vpY -= (e.clientY - lastDragY) / cellPx;
                lastDragX = e.clientX;
                lastDragY = e.clientY;
                clampViewport();
                drawEngel();
            });

            window.addEventListener('mouseup', () => {
                if (!isDragging) return;
                isDragging = false;
                canvas.style.cursor = '';
            });

            // --- Tooltip ---
            canvas.addEventListener('mousemove', (e) => {
                if (isDragging) { tooltipEl.style.display = 'none'; return; }
                const n = prices.length;
                if (n < 2) return;
                const rect = canvas.getBoundingClientRect();
                const mx = e.clientX - rect.left;
                const my = e.clientY - rect.top;
                const cellPx = rect.width / vpSize;
                const col = Math.floor(vpX + mx / cellPx);
                const row = Math.floor(vpY + my / cellPx);

                if (row < 0 || row >= n || col < 0 || col >= n || col < row) {
                    tooltipEl.style.display = 'none';
                    return;
                }

                let html;
                if (col === row) {
                    html = `<strong>${prices[row].date}</strong><br>Close: $${prices[row].close.toFixed(2)}`;
                } else {
                    const diff = prices[col].close - prices[row].close;
                    const pct = ((diff / prices[row].close) * 100).toFixed(2);
                    const sign = diff >= 0 ? '+' : '';
                    html = `<strong>${prices[row].date} &rarr; ${prices[col].date}</strong><br>${sign}$${diff.toFixed(2)} (${sign}${pct}%)`;
                }

                tooltipEl.innerHTML = html;
                tooltipEl.style.display = 'block';

                const containerRect = canvas.parentElement.getBoundingClientRect();
                let left = e.clientX - containerRect.left + 12;
                let top = e.clientY - containerRect.top - 8;
                const tw = tooltipEl.offsetWidth;
                if (left + tw > containerRect.width) left = left - tw - 24;
                if (top < 0) top = 4;
                tooltipEl.style.left = left + 'px';
                tooltipEl.style.top = top + 'px';
            });

            canvas.addEventListener('mouseleave', () => {
                tooltipEl.style.display = 'none';
            });

            // --- Day selector buttons ---
            daysButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const days = parseInt(btn.dataset.days);
                    daysButtons.forEach(b => {
                        b.classList.remove('bg-slate-700', 'text-white');
                        b.classList.add('bg-white', 'text-gray-700');
                    });
                    btn.classList.remove('bg-white', 'text-gray-700');
                    btn.classList.add('bg-slate-700', 'text-white');
                    buildAndDraw(days);
                });
            });

            // Redraw on resize
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => drawEngel(), 150);
            });

            // Initial draw
            buildAndDraw(30);
        })();
    </script>
