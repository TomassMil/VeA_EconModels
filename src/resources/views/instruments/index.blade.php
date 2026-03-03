@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Instrumenti</h1>
            <p class="text-gray-600 mt-2">Meklē pēc ticker vai uzņēmuma nosaukuma.</p>
        </div>

        <form method="GET" action="{{ route('instruments.index') }}" class="mb-6">
            <label for="q" class="sr-only">Meklēšana</label>
            <div class="relative">
                <div class="flex gap-3">
                    <input
                        id="q"
                        name="q"
                        type="text"
                        value="{{ $search }}"
                        placeholder="Piemēram: AAPL vai Apple"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
                        autocomplete="off"
                        oninput="window.onInstrumentSearchInput()"
                    >
                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700 transition-colors"
                    >
                        Meklēt
                    </button>
                </div>

                <div
                    id="instrument-search-popup"
                    class="hidden absolute z-20 mt-2 w-full rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden"
                >
                    <ul id="instrument-search-popup-list" class="max-h-80 overflow-y-auto"></ul>
                </div>
            </div>
        </form>

        <div class="space-y-3">
            @forelse ($instruments as $instrument)
                <a
                    href="{{ route('instruments.show', $instrument) }}"
                    class="block rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm hover:border-blue-400 hover:shadow-md transition-all"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xl font-semibold text-gray-900">{{ $instrument->ticker }}</p>
                            @if ($instrument->company_name)
                                <p class="text-gray-700">{{ $instrument->company_name }}</p>
                            @endif
                        </div>
                        @if ($instrument->exchange)
                            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                                {{ $instrument->exchange }}
                            </span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-5 py-8 text-center text-gray-600">
                    Instrumenti netika atrasti.
                </div>
            @endforelse
        </div>

        @if ($instruments->hasPages())
            <div class="mt-8">
                {{ $instruments->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById('q');
        const popup = document.getElementById('instrument-search-popup');
        const list = document.getElementById('instrument-search-popup-list');
        const searchUrl = @json(route('instruments.search', [], false));
        const detailPathTemplate = @json(route('instruments.show', ['instrument' => '__ID__'], false));
        let debounceTimer = null;
        let requestCounter = 0;

        if (!input || !popup || !list) {
            return;
        }

        function hidePopup() {
            popup.classList.add('hidden');
            list.innerHTML = '';
        }

        function renderItem(item) {
            const exchange = item.exchange ? `<span class="text-xs font-medium text-blue-700 bg-blue-50 rounded-full px-2 py-0.5">${escapeHtml(item.exchange)}</span>` : '';
            const companyName = item.company_name ? `<p class="text-sm text-gray-600 mt-1">${escapeHtml(item.company_name)}</p>` : '';
            const instrumentId = encodeURIComponent(String(item.id));
            const detailUrl = detailPathTemplate.replace('__ID__', instrumentId);

            return `
                <li>
                    <a href="${detailUrl}" class="block px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-b-0">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-gray-900">${escapeHtml(item.ticker)}</p>
                            ${exchange}
                        </div>
                        ${companyName}
                    </a>
                </li>
            `;
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function onInputChange() {
            const query = input.value.trim();
            const currentRequest = ++requestCounter;

            if (!query) {
                hidePopup();
                return;
            }

            try {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok || currentRequest !== requestCounter) {
                    return;
                }

                const payload = await response.json();
                const rows = Array.isArray(payload.data) ? payload.data : [];

                if (rows.length === 0) {
                    list.innerHTML = '<li class="px-4 py-3 text-sm text-gray-500">Nav atrastu instrumentu.</li>';
                    popup.classList.remove('hidden');
                    return;
                }

                list.innerHTML = rows.map(renderItem).join('');
                popup.classList.remove('hidden');
            } catch (_error) {
                hidePopup();
            }
        }

        window.onInstrumentSearchInput = function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(onInputChange, 180);
        };

        input.addEventListener('focus', function () {
            if (input.value.trim() !== '') {
                onInputChange();
            }
        });

        input.addEventListener('input', function () {
            window.onInstrumentSearchInput();
        });

        document.addEventListener('click', function (event) {
            if (!popup.contains(event.target) && event.target !== input) {
                hidePopup();
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hidePopup();
                input.blur();
            }
        });
    })();
</script>
@endsection
