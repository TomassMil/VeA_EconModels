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
@endsection
