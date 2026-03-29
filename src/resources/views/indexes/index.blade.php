@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Indeksi</h1>
            <p class="text-gray-600 mt-2">Tirgus indeksi un personalizēti instrumentu grozi.</p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm font-medium text-green-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- ── Public / Market Indexes ── --}}
        <section class="mb-10">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Tirgus indeksi</h2>

            @if ($publicIndexes->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-5 py-8 text-center text-gray-500">
                    Tirgus indeksu dati vēl nav pieejami. Drīzumā tiks pievienoti S&P 500, NASDAQ, Dow Jones u.c.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($publicIndexes as $idx)
                        <a href="{{ route('indexes.show', $idx) }}"
                           class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-blue-400 hover:shadow-md transition-all">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <h3 class="font-bold text-gray-900">{{ $idx->name }}</h3>
                            </div>
                            @if ($idx->description)
                                <p class="text-sm text-gray-600 mb-3">{{ $idx->description }}</p>
                            @endif
                            <span class="text-xs font-medium text-blue-600">
                                {{ $idx->instruments_count }} instrumenti
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ── User Indexes ── --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Mani indeksi</h2>
                <a href="{{ route('indexes.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Izveidot jaunu indeksu
                </a>
            </div>

            @if ($userIndexes->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-5 py-8 text-center text-gray-500">
                    Tev vēl nav izveidotu indeksu. Izveido savu pirmo!
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($userIndexes as $idx)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm hover:border-blue-400 hover:shadow-md transition-all">
                            <a href="{{ route('indexes.show', $idx) }}" class="flex-1 min-w-0">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-50">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 truncate">{{ $idx->name }}</p>
                                        @if ($idx->description)
                                            <p class="text-sm text-gray-600 truncate">{{ $idx->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                            <div class="flex items-center gap-4 ml-4 flex-shrink-0">
                                <span class="text-sm text-gray-500">{{ $idx->instruments_count }} instrumenti</span>
                                <form method="POST" action="{{ route('indexes.destroy', $idx) }}"
                                      onsubmit="return confirm('Vai tiešām vēlies dzēst šo indeksu?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="rounded p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                            title="Dzēst">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
</div>
@endsection
