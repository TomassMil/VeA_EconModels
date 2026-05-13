@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="w-[90%] max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ url()->previous() }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 mb-4">
            ← Atpakaļ
        </a>

        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ $instrument->ticker }}
                </h1>
                @if ($instrument->company_name)
                    <p class="text-gray-600 mt-2">
                        {{ $instrument->company_name }}
                    </p>
                @endif
            </div>

            @auth
                @if ($userPortfolios->isNotEmpty())
                    <div class="relative">
                        <button type="button" id="portfolio-toggle-btn"
                                class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Pievienot savā portfelī
                        </button>

                        <div id="portfolio-dropdown" class="hidden absolute right-0 top-full mt-2 w-96 bg-white rounded-xl border border-gray-200 shadow-lg z-30 p-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Izvēlies portfeli un summu</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Portfelis</label>
                                    <select id="portfolio-select" class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                                        @foreach ($userPortfolios as $p)
                                            <option value="{{ $p->id }}" data-free="{{ $p->free_capital }}">{{ $p->name }} — brīvs: ${{ number_format((float)$p->free_capital, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-1 block">Summa ($)</label>
                                    <input type="number" id="portfolio-amount" placeholder="0.00" min="0.01" step="0.01"
                                           class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                                </div>
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                    <label class="text-xs font-semibold text-amber-800 mb-1 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Pirkuma datums (backtestēšanai)
                                    </label>
                                    <input type="date" id="portfolio-date"
                                           @if (!empty($earliestInstrumentDate)) min="{{ $earliestInstrumentDate }}" @endif
                                           @if (!empty($latestInstrumentDate)) max="{{ $latestInstrumentDate }}" @endif
                                           class="w-full rounded-lg border border-amber-300 bg-white text-sm px-3 py-2 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
                                    <p class="text-[10px] text-amber-700 mt-1">
                                        Atstāj tukšu = jaunākā cena.
                                        @if (!empty($earliestInstrumentDate) && !empty($latestInstrumentDate))
                                            Dati: <strong>{{ $earliestInstrumentDate }}</strong> – <strong>{{ $latestInstrumentDate }}</strong>.
                                        @endif
                                    </p>
                                </div>
                                <button type="button" id="add-to-portfolio-btn"
                                        class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                                    Pievienot
                                </button>
                                <p id="portfolio-msg" class="text-xs text-center hidden"></p>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('portfolios.index') }}"
                       class="flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Izveido portfeli
                    </a>
                @endif
            @endauth
        </div>

        <script>
        (function() {
            const toggleBtn = document.getElementById('portfolio-toggle-btn');
            const dropdown = document.getElementById('portfolio-dropdown');
            if (!toggleBtn || !dropdown) return;

            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target) && e.target !== toggleBtn) {
                    dropdown.classList.add('hidden');
                }
            });

            const btn = document.getElementById('add-to-portfolio-btn');
            const select = document.getElementById('portfolio-select');
            const amountInput = document.getElementById('portfolio-amount');
            const dateInput = document.getElementById('portfolio-date');
            const msg = document.getElementById('portfolio-msg');
            const instrumentId = {{ $instrument->id }};

            btn.addEventListener('click', function() {
                const portfolioId = select.value;
                const amount = parseFloat(amountInput.value);
                const date = dateInput.value || null;
                if (!amount || amount <= 0) {
                    msg.textContent = 'Ievadi summu.';
                    msg.className = 'text-xs text-red-600 text-center';
                    msg.classList.remove('hidden');
                    return;
                }

                btn.disabled = true;
                btn.textContent = '...';
                msg.classList.add('hidden');

                const payload = { instrument_id: instrumentId, amount: amount };
                if (date) payload.transaction_date = date;

                fetch(`/portfelis/${portfolioId}/add-instrument`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
                .then(({ ok, data }) => {
                    if (!ok) {
                        msg.textContent = data.error || 'Kļūda.';
                        msg.className = 'text-xs text-red-600 text-center';
                    } else {
                        msg.textContent = 'Veiksmīgi pievienots portfelī!';
                        msg.className = 'text-xs text-green-600 font-medium text-center';
                        amountInput.value = '';
                        // Update free capital in dropdown
                        const opt = select.options[select.selectedIndex];
                        const newFree = parseFloat(opt.dataset.free) - amount;
                        opt.dataset.free = newFree;
                        opt.textContent = opt.textContent.replace(/brīvs: \$[\d,.]+/, 'brīvs: $' + newFree.toFixed(2));
                    }
                    msg.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Pievienot';
                })
                .catch(() => {
                    msg.textContent = 'Savienojuma kļūda.';
                    msg.className = 'text-xs text-red-600 text-center';
                    msg.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Pievienot';
                });
            });
        })();
        </script>

        @include('instruments.partials._technical')

        @include('instruments.partials._fundamental')

    </div>
</div>
@endsection
