{{-- Right column content for technical view (used both server-rendered and AJAX-injected) --}}
@if ($instrument)
    <div class="p-4 sm:p-5">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 pb-2 border-b border-gray-200">
            <div class="flex items-baseline gap-2 min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 shrink-0">{{ $instrument->ticker }}</h1>
                @if ($instrument->company_name)
                    <span class="text-sm text-gray-700 truncate">· {{ $instrument->company_name }}</span>
                @endif
                @if ($instrument->sector)
                    <span class="text-xs text-gray-500 truncate hidden sm:inline">· {{ $instrument->sector }}{{ $instrument->industry ? ' / ' . $instrument->industry : '' }}</span>
                @endif
            </div>
            {{-- Mode switch — pilna lapas pārlāde + saglabā filtru URL query --}}
            <a href="{{ route('fundamentals.show', $instrument) }}" data-mode-switch
               class="shrink-0 inline-flex items-center gap-1.5 rounded-md border border-blue-300 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Fundamentālie dati →
            </a>
        </div>

        @include('instruments.partials._technical')
    </div>
@else
    <div class="h-full flex items-center justify-center p-8">
        <div class="text-center max-w-md">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Tehniskie dati</h2>
            <p class="text-sm text-gray-600">
                Izvēlies instrumentu no kreisās joslas, lai apskatītu cenas grafiku
                un Engela trijstūri.
            </p>
        </div>
    </div>
@endif
