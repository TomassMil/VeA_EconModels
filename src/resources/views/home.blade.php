@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-full px-4 sm:px-6 lg:px-8">
        <!-- Header with Search -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Ekonomikas Modeļu Karta
            </h1>
            <p class="text-lg text-gray-600 mb-6">
                Interaktīva vizualizācija ekonomikas modeļiem un to savstarpējām sakarībām
            </p>

            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto">
                <div class="relative">
                    <input
                        id="searchInput"
                        type="text"
                        placeholder="Meklēt modeļus..."
                        class="w-full px-6 py-4 pr-12 rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:outline-none text-lg shadow-sm"
                        autocomplete="off"
                    >
                    <button type="button" id="searchBtn" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
                <div id="searchMeta" class="text-sm text-gray-500 mt-2"></div>
            </div>
        </div>

        <!-- Tree Container -->
        <div class="overflow-x-auto overflow-y-visible py-8 flex justify-center">
            <!-- This wrapper is the coordinate system for SVG + nodes -->
            <div id="treeWrap" class="relative min-w-max mx-auto inline-block">
                <!-- SVG overlay (auto-drawn by JS so each line hits the exact node centers) -->
                <svg id="treeSvg" class="absolute top-0 left-0 w-full h-full pointer-events-none" style="z-index: 0;"></svg>

                <!-- Tree Structure -->
                <div id="treeGrid" class="flex items-center space-x-32 relative" style="z-index: 1;">

                    <!-- Level 1: Root Node -->
                    <div class="flex items-center" style="height: 800px;">
                        <div
                            class="tree-node bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-lg px-8 py-4 shadow-lg"
                            data-node="root"
                            data-type="root"
                            data-search="Ekonomikas Modeļi Sākums"
                        >
                            <h2 class="text-xl font-bold">Ekonomikas Modeļi</h2>
                            <p class="text-sm text-blue-100 mt-1">Sākums</p>
                        </div>
                    </div>

                    <!-- Level 2: Categories -->
                    <div class="flex flex-col justify-center space-y-20" style="height: 800px;">
                        <!-- 1. Laika rindas -->
                        <div
                            class="tree-node bg-white border-2 border-blue-400 rounded-lg px-6 py-4 shadow-md hover:border-blue-600"
                            data-node="cat-1"
                            data-type="category"
                            data-parent="root"
                            data-color="#3b82f6"
                            data-search="1. Laika rindas Time Series Analysis"
                        >
                            <h3 class="font-bold text-gray-900">1. Laika rindas</h3>
                            <p class="text-sm text-gray-600 mt-1">Time Series Analysis</p>
                        </div>

                        <!-- 2. Laika rindu prognozēšana -->
                        <div
                            class="tree-node bg-white border-2 border-green-400 rounded-lg px-6 py-4 shadow-md hover:border-green-600"
                            data-node="cat-2"
                            data-type="category"
                            data-parent="root"
                            data-color="#22c55e"
                            data-search="2. Laika rindu prognozēšana Time Series Forecasting"
                        >
                            <h3 class="font-bold text-gray-900">2. Laika rindu prognozēšana</h3>
                            <p class="text-sm text-gray-600 mt-1">Time Series Forecasting</p>
                        </div>

                        <!-- 3. Makroekonomika -->
                        <div
                            class="tree-node bg-white border-2 border-purple-400 rounded-lg px-6 py-4 shadow-md hover:border-purple-600"
                            data-node="cat-3"
                            data-type="category"
                            data-parent="root"
                            data-color="#a855f7"
                            data-search="3. Makroekonomika Macroeconomics"
                        >
                            <h3 class="font-bold text-gray-900">3. Makroekonomika</h3>
                            <p class="text-sm text-gray-600 mt-1">Macroeconomics</p>
                        </div>

                        <!-- 4. Ekonomikas izaugsme -->
                        <div
                            class="tree-node bg-white border-2 border-orange-400 rounded-lg px-6 py-4 shadow-md hover:border-orange-600"
                            data-node="cat-4"
                            data-type="category"
                            data-parent="root"
                            data-color="#f97316"
                            data-search="4. Ekonomikas izaugsme Economic Growth"
                        >
                            <h3 class="font-bold text-gray-900">4. Ekonomikas izaugsme</h3>
                            <p class="text-sm text-gray-600 mt-1">Economic Growth</p>
                        </div>
                    </div>

                    <!-- Level 3: Subcategories -->
                    <div class="flex flex-col justify-start space-y-3" style="height: 800px; padding-top: 20px;">
                        <!-- 1.1 - 1.4 -->
                        <a
                            href="{{ route('topic.show', '1-1-regularas') }}"
                            class="tree-node bg-blue-50 border border-blue-300 rounded-lg px-4 py-3 shadow-sm hover:bg-blue-100 block"
                            data-node="sub-1-1"
                            data-type="subcategory"
                            data-parent="cat-1"
                            data-color="#60a5fa"
                            data-search="1.1 Regulāras"
                        >
                            <span class="text-sm font-semibold text-gray-800">1.1. Regulāras</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '1-2-stohastiskas') }}"
                            class="tree-node bg-blue-50 border border-blue-300 rounded-lg px-4 py-3 shadow-sm hover:bg-blue-100 block"
                            data-node="sub-1-2"
                            data-type="subcategory"
                            data-parent="cat-1"
                            data-color="#60a5fa"
                            data-search="1.2 Stohastiskas"
                        >
                            <span class="text-sm font-semibold text-gray-800">1.2. Stohastiskas</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '1-3-haotiskas') }}"
                            class="tree-node bg-blue-50 border border-blue-300 rounded-lg px-4 py-3 shadow-sm hover:bg-blue-100 block"
                            data-node="sub-1-3"
                            data-type="subcategory"
                            data-parent="cat-1"
                            data-color="#60a5fa"
                            data-search="1.3 Haotiskas"
                        >
                            <span class="text-sm font-semibold text-gray-800">1.3. Haotiskas</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '1-4-naudas-piedavajums') }}"
                            class="tree-node bg-blue-50 border border-blue-300 rounded-lg px-4 py-3 shadow-sm hover:bg-blue-100 block"
                            data-node="sub-1-4"
                            data-type="subcategory"
                            data-parent="cat-1"
                            data-color="#60a5fa"
                            data-search="1.4 P = d*R + y*H + B*s d + B + y = 1"
                        >
                            <span class="text-sm font-semibold text-gray-800">1.4. P = d*R + y*H + B*s</span>
                            <span class="text-xs text-gray-600 block mt-1">d + B + y = 1</span>
                        </a>

                        <!-- 2.1 - 2.3 -->
                        <a
                            href="{{ route('topic.show', '2-1-trend-linija') }}"
                            class="tree-node bg-green-50 border border-green-300 rounded-lg px-4 py-3 shadow-sm hover:bg-green-100 block"
                            data-node="sub-2-1"
                            data-type="subcategory"
                            data-parent="cat-2"
                            data-color="#4ade80"
                            data-search="2.1 Trend līnija"
                        >
                            <span class="text-sm font-semibold text-gray-800">2.1. Trend līnija</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '2-2-hp-modelis') }}"
                            class="tree-node bg-green-50 border border-green-300 rounded-lg px-4 py-3 shadow-sm hover:bg-green-100 block"
                            data-node="sub-2-2"
                            data-type="subcategory"
                            data-parent="cat-2"
                            data-color="#4ade80"
                            data-search="2.2 HP modelis"
                        >
                            <span class="text-sm font-semibold text-gray-800">2.2. HP modelis</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '2-3-ssa') }}"
                            class="tree-node bg-green-50 border border-green-300 rounded-lg px-4 py-3 shadow-sm hover:bg-green-100 block"
                            data-node="sub-2-3"
                            data-type="subcategory"
                            data-parent="cat-2"
                            data-color="#4ade80"
                            data-search="2.3 SSA"
                        >
                            <span class="text-sm font-semibold text-gray-800">2.3. SSA</span>
                        </a>

                        <!-- 3.1 - 3.4 -->
                        <a
                            href="{{ route('topic.show', '3-1-keynes') }}"
                            class="tree-node bg-purple-50 border border-purple-300 rounded-lg px-4 py-3 shadow-sm hover:bg-purple-100 block"
                            data-node="sub-3-1"
                            data-type="subcategory"
                            data-parent="cat-3"
                            data-color="#c084fc"
                            data-search="3.1 Keynes"
                        >
                            <span class="text-sm font-semibold text-gray-800">3.1. Keynes</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '3-2-is-lm') }}"
                            class="tree-node bg-purple-50 border border-purple-300 rounded-lg px-4 py-3 shadow-sm hover:bg-purple-100 block"
                            data-node="sub-3-2"
                            data-type="subcategory"
                            data-parent="cat-3"
                            data-color="#c084fc"
                            data-search="3.2 IS-LM"
                        >
                            <span class="text-sm font-semibold text-gray-800">3.2. IS-LM</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '3-3-ret') }}"
                            class="tree-node bg-purple-50 border border-purple-300 rounded-lg px-4 py-3 shadow-sm hover:bg-purple-100 block"
                            data-node="sub-3-3"
                            data-type="subcategory"
                            data-parent="cat-3"
                            data-color="#c084fc"
                            data-search="3.3 RET"
                        >
                            <span class="text-sm font-semibold text-gray-800">3.3. RET</span>
                        </a>

                        <a
                            href="{{ route('topic.show', '3-4-dsge-is') }}"
                            class="tree-node bg-purple-50 border border-purple-300 rounded-lg px-4 py-3 shadow-sm hover:bg-purple-100 block"
                            data-node="sub-3-4"
                            data-type="subcategory"
                            data-parent="cat-3"
                            data-color="#c084fc"
                            data-search="3.4 DSGE IS"
                        >
                            <span class="text-sm font-semibold text-gray-800">3.4. DSGE{IS}</span>
                        </a>

                        <!-- 4.1 Placeholder -->
                        <a
                            href="{{ route('topic.show', '4-1-placeholder') }}"
                            class="tree-node bg-orange-50 border border-orange-300 rounded-lg px-4 py-3 shadow-sm hover:bg-orange-100 block"
                            data-node="sub-4-1"
                            data-type="subcategory"
                            data-parent="cat-4"
                            data-color="#fb923c"
                            data-search="4.1 Placeholder"
                        >
                            <span class="text-sm font-semibold text-gray-800">4.1. (Placeholder)</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tree-node { transition: all 160ms ease; }
    .is-hidden { display: none !important; }
    .is-match { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25); border-color: rgba(59, 130, 246, 0.65) !important; }
</style>

<script>
(function () {
    const wrap = document.getElementById('treeWrap');
    const svg  = document.getElementById('treeSvg');
    const input = document.getElementById('searchInput');
    const btn = document.getElementById('searchBtn');
    const meta = document.getElementById('searchMeta');

    const allNodes = Array.from(wrap.querySelectorAll('[data-node]'));

    function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

    function getNodeEl(nodeId) {
        return wrap.querySelector(`[data-node="${CSS.escape(nodeId)}"]`);
    }

    // Coordinates relative to wrap (NOT viewport)
    function rectRelToWrap(el) {
        const wrapRect = wrap.getBoundingClientRect();
        const r = el.getBoundingClientRect();
        return {
            left:   (r.left - wrapRect.left) + wrap.scrollLeft,
            top:    (r.top  - wrapRect.top)  + wrap.scrollTop,
            width:  r.width,
            height: r.height,
            right:  (r.right - wrapRect.left) + wrap.scrollLeft,
            bottom: (r.bottom - wrapRect.top) + wrap.scrollTop
        };
    }

    function pointRightCenter(el) {
        const rr = rectRelToWrap(el);
        return { x: rr.right, y: rr.top + rr.height / 2 };
    }

    function pointLeftCenter(el) {
        const rr = rectRelToWrap(el);
        return { x: rr.left, y: rr.top + rr.height / 2 };
    }

    function isVisible(el) {
        return !!el && !el.classList.contains('is-hidden');
    }

    function setSvgSizeToContent() {
        // Ensure SVG covers the full content area (including overflow)
        const w = Math.max(wrap.scrollWidth, wrap.clientWidth);
        const h = Math.max(wrap.scrollHeight, wrap.clientHeight);
        svg.setAttribute('width', w);
        svg.setAttribute('height', h);
        svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
    }

    function drawPath(fromEl, toEl, stroke, strokeWidth, opacity) {
        const a = pointRightCenter(fromEl);
        const b = pointLeftCenter(toEl);

        // Create a pleasant elbow/curve that always ends exactly at the subcategory center.
        // Use horizontal "pull" based on distance.
        const dx = b.x - a.x;
        const pull = clamp(dx * 0.35, 60, 180);

        const c1 = { x: a.x + pull, y: a.y };
        const c2 = { x: b.x - pull, y: b.y };

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', `M ${a.x} ${a.y} C ${c1.x} ${c1.y}, ${c2.x} ${c2.y}, ${b.x} ${b.y}`);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', stroke || '#94a3b8');
        path.setAttribute('stroke-width', String(strokeWidth || 2));
        if (opacity !== undefined) path.setAttribute('opacity', String(opacity));
        path.setAttribute('stroke-linecap', 'round');
        svg.appendChild(path);
    }

    function redraw() {
        setSvgSizeToContent();
        svg.innerHTML = '';

        // 1) root -> categories
        const root = getNodeEl('root');
        const cats = allNodes.filter(n => n.dataset.type === 'category');

        if (isVisible(root)) {
            cats.forEach(cat => {
                if (!isVisible(cat)) return;
                drawPath(root, cat, cat.dataset.color || '#3b82f6', 2, 1);
            });
        }

        // 2) categories -> subcategories
        const subs = allNodes.filter(n => n.dataset.type === 'subcategory');
        subs.forEach(sub => {
            if (!isVisible(sub)) return;
            const parentId = sub.dataset.parent;
            const parent = parentId ? getNodeEl(parentId) : null;
            if (!isVisible(parent)) return;

            // Lighter lines for sub-links (as you had)
            drawPath(parent, sub, sub.dataset.color || parent.dataset.color || '#94a3b8', 1.5, 0.65);
        });
    }

    function normalize(s) {
        return (s || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, ''); // strip accents
    }

    function applySearch(qRaw) {
        const q = normalize(qRaw.trim());

        // reset
        allNodes.forEach(el => {
            el.classList.remove('is-hidden', 'is-match');
        });

        if (!q) {
            meta.textContent = '';
            redraw();
            return;
        }

        // Decide matches on subcategories and categories (root is never a "match", just stays if anything matches)
        const matches = new Set();

        allNodes.forEach(el => {
            const hay = normalize(el.dataset.search || el.textContent);
            if (hay.includes(q)) {
                matches.add(el.dataset.node);
            }
        });

        // Ensure if a subcategory matches -> show its category + root
        const keep = new Set();
        keep.add('root');

        // Keep all matching nodes
        matches.forEach(id => keep.add(id));

        // For each kept subcategory, keep its parent category
        allNodes
            .filter(el => el.dataset.type === 'subcategory')
            .forEach(sub => {
                if (!keep.has(sub.dataset.node)) return;
                if (sub.dataset.parent) keep.add(sub.dataset.parent);
            });

        // Also: if a category matches, keep its children? (optional: yes)
        allNodes
            .filter(el => el.dataset.type === 'subcategory')
            .forEach(sub => {
                const parent = sub.dataset.parent;
                if (parent && keep.has(parent) && matches.has(parent)) {
                    keep.add(sub.dataset.node);
                }
            });

        // Hide everything not in keep
        allNodes.forEach(el => {
            if (!keep.has(el.dataset.node)) el.classList.add('is-hidden');
        });

        // Highlight actual textual matches
        matches.forEach(id => {
            const el = getNodeEl(id);
            if (el && !el.classList.contains('is-hidden')) el.classList.add('is-match');
        });

        const visibleMatches = Array.from(matches).filter(id => {
            const el = getNodeEl(id);
            return el && !el.classList.contains('is-hidden');
        });

        meta.textContent = visibleMatches.length
            ? `Atrasts: ${visibleMatches.length}`
            : `Nekas netika atrasts`;

        // Scroll to first visible match (nice UX)
        const first = visibleMatches.length ? getNodeEl(visibleMatches[0]) : null;
        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }

        redraw();
    }

    // Redraw on resize + on horizontal scroll (because positions change relative to wrap)
    const ro = new ResizeObserver(() => redraw());
    ro.observe(wrap);

    // scroll container is the parent with overflow-x-auto (closest one)
    const scrollParent = wrap.parentElement; // the div with overflow-x-auto
    if (scrollParent) {
        scrollParent.addEventListener('scroll', () => redraw(), { passive: true });
    }

    // Initial draw after layout
    window.addEventListener('load', () => redraw());
    window.addEventListener('resize', () => redraw());

    // Search hooks
    input.addEventListener('input', (e) => applySearch(e.target.value));
    btn.addEventListener('click', () => applySearch(input.value));

    // Optional: ESC clears search
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            input.value = '';
            applySearch('');
        }
    });
})();
</script>
@endsection
