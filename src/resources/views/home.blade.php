@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-full px-4 sm:px-6 lg:px-8">
        <!-- Header with Search -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Ekonomikas Modeļu Informācijas Sistēma
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
            <div id="treeWrap" class="relative min-w-max mx-auto inline-block">
                <svg id="treeSvg" class="absolute top-0 left-0 w-full h-full pointer-events-none" style="z-index: 0;"></svg>

                <div id="treeGrid" class="flex items-center space-x-32 relative" style="z-index: 1;">

                    <!-- Level 1: Root Node -->
                    <div class="flex items-center" style="height: 900px;">
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
                    <div class="flex flex-col justify-center space-y-16" style="height: 900px;">
                        <!-- 1. Investīciju teorijas -->
                        <a
                            href="{{ route('theories.index') }}"
                            class="tree-node bg-white border-2 border-purple-400 rounded-lg px-6 py-4 shadow-md hover:border-purple-600 block"
                            data-node="cat-1"
                            data-type="category"
                            data-parent="root"
                            data-color="#a855f7"
                            data-search="1. Investīciju teorijas valuation models"
                        >
                            <h3 class="font-bold text-gray-900">1. Investīciju teorijas</h3>
                            <p class="text-sm text-gray-600 mt-1">Vērtēšanas modeļi (50 nosaukti modeļi)</p>
                        </a>

                        <!-- 2. Fundamentālie dati -->
                        <a
                            href="{{ route('fundamentals.index') }}"
                            class="tree-node bg-white border-2 border-blue-400 rounded-lg px-6 py-4 shadow-md hover:border-blue-600 block"
                            data-node="cat-2"
                            data-type="category"
                            data-parent="root"
                            data-color="#3b82f6"
                            data-search="2. Fundamentālie dati instrumenti"
                        >
                            <h3 class="font-bold text-gray-900">2. Fundamentālie dati</h3>
                            <p class="text-sm text-gray-600 mt-1">Instrumentu saraksts un fundamentālā analīze</p>
                        </a>

                        <!-- 3. Tehniskie dati -->
                        <a
                            href="{{ route('technical.index') }}"
                            class="tree-node bg-white border-2 border-green-400 rounded-lg px-6 py-4 shadow-md hover:border-green-600 block"
                            data-node="cat-3"
                            data-type="category"
                            data-parent="root"
                            data-color="#22c55e"
                            data-search="3. Tehniskie dati cenas grafiks Engela trijstūris"
                        >
                            <h3 class="font-bold text-gray-900">3. Tehniskie dati</h3>
                            <p class="text-sm text-gray-600 mt-1">Cenas grafiki un Engela trijstūris</p>
                        </a>

                        <!-- 4. Portfeļi -->
                        <a
                            href="{{ route('portfolios.index') }}"
                            class="tree-node bg-white border-2 border-orange-400 rounded-lg px-6 py-4 shadow-md hover:border-orange-600 block"
                            data-node="cat-4"
                            data-type="category"
                            data-parent="root"
                            data-color="#f97316"
                            data-search="4. Portfeļi portfolio risks peļņa QuantStats"
                        >
                            <h3 class="font-bold text-gray-900">4. Portfeļi</h3>
                            <p class="text-sm text-gray-600 mt-1">Personīgo portfeļu pārvaldība un riska/peļņas analīze</p>
                        </a>
                    </div>

                    <!-- Level 3: Subcategories -->
                    <div class="flex flex-col justify-start space-y-3" style="height: 900px; padding-top: 20px;">

                        {{-- Sub-branches under "1. Investīciju teorijas" — 6 valuation categories --}}
                        @foreach ($valuationCategories as $catKey => $cat)
                            <a
                                href="{{ route('theories.index') }}#{{ $catKey }}"
                                class="tree-node bg-purple-50 border border-purple-300 rounded-lg px-4 py-3 shadow-sm hover:bg-purple-100 block"
                                data-node="sub-1-{{ $loop->index + 1 }}"
                                data-type="subcategory"
                                data-parent="cat-1"
                                data-color="#c084fc"
                                data-search="{{ $cat['title'] }}"
                            >
                                <span class="text-sm font-semibold text-gray-800">{{ $cat['title'] }}</span>
                                <span class="text-xs text-gray-600 block mt-1">{{ count($cat['models']) }} modeļi</span>
                            </a>
                        @endforeach

                        {{-- Sub-branches under "3. Tehniskie dati" — featured tickers --}}
                        @foreach ($featuredTickers as $ticker)
                            @php $inst = $tickerInstruments->get($ticker); @endphp
                            @if ($inst)
                                <a
                                    href="{{ route('instruments.show', $inst->id) }}"
                                    class="tree-node bg-green-50 border border-green-300 rounded-lg px-4 py-3 shadow-sm hover:bg-green-100 block"
                                    data-node="sub-3-{{ $loop->index + 1 }}"
                                    data-type="subcategory"
                                    data-parent="cat-3"
                                    data-color="#4ade80"
                                    data-search="{{ $ticker }} {{ $inst->company_name }}"
                                >
                                    <span class="text-sm font-semibold text-gray-800">{{ $inst->ticker }}</span>
                                    <span class="text-xs text-gray-600 block mt-1">{{ $inst->company_name }}</span>
                                </a>
                            @endif
                        @endforeach

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
        const w = Math.max(wrap.scrollWidth, wrap.clientWidth);
        const h = Math.max(wrap.scrollHeight, wrap.clientHeight);
        svg.setAttribute('width', w);
        svg.setAttribute('height', h);
        svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
    }

    function drawPath(fromEl, toEl, stroke, strokeWidth, opacity) {
        const a = pointRightCenter(fromEl);
        const b = pointLeftCenter(toEl);

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

        const root = getNodeEl('root');
        const cats = allNodes.filter(n => n.dataset.type === 'category');

        if (isVisible(root)) {
            cats.forEach(cat => {
                if (!isVisible(cat)) return;
                drawPath(root, cat, cat.dataset.color || '#3b82f6', 2, 1);
            });
        }

        const subs = allNodes.filter(n => n.dataset.type === 'subcategory');
        subs.forEach(sub => {
            if (!isVisible(sub)) return;
            const parentId = sub.dataset.parent;
            const parent = parentId ? getNodeEl(parentId) : null;
            if (!isVisible(parent)) return;

            drawPath(parent, sub, sub.dataset.color || parent.dataset.color || '#94a3b8', 1.5, 0.65);
        });
    }

    function normalize(s) {
        return (s || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '');
    }

    function applySearch(qRaw) {
        const q = normalize(qRaw.trim());

        allNodes.forEach(el => {
            el.classList.remove('is-hidden', 'is-match');
        });

        if (!q) {
            meta.textContent = '';
            redraw();
            return;
        }

        const matches = new Set();

        allNodes.forEach(el => {
            const hay = normalize(el.dataset.search || el.textContent);
            if (hay.includes(q)) {
                matches.add(el.dataset.node);
            }
        });

        const keep = new Set();
        keep.add('root');
        matches.forEach(id => keep.add(id));

        allNodes
            .filter(el => el.dataset.type === 'subcategory')
            .forEach(sub => {
                if (!keep.has(sub.dataset.node)) return;
                if (sub.dataset.parent) keep.add(sub.dataset.parent);
            });

        allNodes
            .filter(el => el.dataset.type === 'subcategory')
            .forEach(sub => {
                const parent = sub.dataset.parent;
                if (parent && keep.has(parent) && matches.has(parent)) {
                    keep.add(sub.dataset.node);
                }
            });

        allNodes.forEach(el => {
            if (!keep.has(el.dataset.node)) el.classList.add('is-hidden');
        });

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

        const first = visibleMatches.length ? getNodeEl(visibleMatches[0]) : null;
        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }

        redraw();
    }

    const ro = new ResizeObserver(() => redraw());
    ro.observe(wrap);

    const scrollParent = wrap.parentElement;
    if (scrollParent) {
        scrollParent.addEventListener('scroll', () => redraw(), { passive: true });
    }

    window.addEventListener('load', () => redraw());
    window.addEventListener('resize', () => redraw());

    input.addEventListener('input', (e) => applySearch(e.target.value));
    btn.addEventListener('click', () => applySearch(input.value));

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            input.value = '';
            applySearch('');
        }
    });
})();
</script>
@endsection
