@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mani portfeļi</h1>
            <p class="text-gray-600 mt-1">Pārvaldi savus investīciju portfeļus</p>
        </div>

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
@endsection
