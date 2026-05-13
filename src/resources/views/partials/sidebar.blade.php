@php
    // Determine active section based on current route name
    $currentRoute = Route::currentRouteName();
    $isFundamental = in_array($currentRoute, ['home', 'fundamentals.index', 'fundamentals.show']);
    $isTechnical = in_array($currentRoute, ['technical.index', 'technical.show']);
    $isTheories = $currentRoute === 'theories.index';
    $isPortfolios = str_starts_with((string) $currentRoute, 'portfolios.');
@endphp

<nav class="h-full flex flex-col py-4">
    <p class="px-4 mb-3 text-[10px] uppercase font-semibold tracking-wider text-gray-400">Sadaļas</p>

    {{-- Investīciju teorijas (standalone page) --}}
    <a href="{{ route('theories.index') }}"
       class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors
              {{ $isTheories ? 'bg-purple-50 text-purple-700 border-l-4 border-purple-500' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent' }}">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        Investīciju teorijas
    </a>

    {{-- Fundamentālie dati (app-shell) — saglabā filtru URL query --}}
    <a href="{{ route('fundamentals.index') }}" data-preserve-query
       class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors
              {{ $isFundamental ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-500' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent' }}">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Fundamentālie dati
    </a>

    {{-- Tehniskie dati (app-shell) — saglabā filtru URL query --}}
    <a href="{{ route('technical.index') }}" data-preserve-query
       class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors
              {{ $isTechnical ? 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-500' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent' }}">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
        Tehniskie dati
    </a>

    {{-- Portfeļi (standalone page) --}}
    <a href="{{ route('portfolios.index') }}"
       class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors
              {{ $isPortfolios ? 'bg-orange-50 text-orange-700 border-l-4 border-orange-500' : 'text-gray-700 hover:bg-gray-50 border-l-4 border-transparent' }}">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        Portfeļi
    </a>

    <div class="mt-auto px-4 pt-4 border-t border-gray-100">
        <p class="text-[10px] text-gray-400">v2026.05 · App Shell</p>
    </div>
</nav>
