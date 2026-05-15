@extends('layouts.app-shell')

@section('content')
<div class="h-full flex flex-col">
    <div class="max-w-4xl mx-auto p-8 sm:p-12">

        {{-- Welcome header --}}
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">
                Sveiks, {{ Auth::user()->name ?? 'lietotāj' }}!
            </h1>
            <p class="text-lg text-gray-600">
                VeA EconModels — interaktīva sistēma vērtspapīru analīzei, modelēšanai un portfeļu pārvaldībai.
            </p>
        </div>

        {{-- 4 large cards mirroring the sidebar sections --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10">

            <a href="{{ route('theories.index') }}" class="group block bg-white rounded-xl border-2 border-gray-200 hover:border-purple-400 shadow-sm hover:shadow-md transition-all p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-purple-700">Investīciju teorijas</h3>
                        <p class="text-sm text-gray-600 mt-1">50 vērtēšanas modeļi: Graham, Altman Z, Gordon DDM, Black-Scholes u.c.</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('fundamentals.index') }}" class="group block bg-white rounded-xl border-2 border-gray-200 hover:border-blue-400 shadow-sm hover:shadow-md transition-all p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-blue-700">Fundamentālie dati</h3>
                        <p class="text-sm text-gray-600 mt-1">Bilance, peļņas/zaudējumu pārskats, naudas plūsma 5000+ instrumentiem (SimFin + EDGAR).</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('technical.index') }}" class="group block bg-white rounded-xl border-2 border-gray-200 hover:border-emerald-400 shadow-sm hover:shadow-md transition-all p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-emerald-700">Tehniskie dati</h3>
                        <p class="text-sm text-gray-600 mt-1">Cenas vēsture, OHLC sveces, volume un Engela trijstūris.</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('portfolios.index') }}" class="group block bg-white rounded-xl border-2 border-gray-200 hover:border-orange-400 shadow-sm hover:shadow-md transition-all p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-orange-700">Portfeļi</h3>
                        <p class="text-sm text-gray-600 mt-1">Personīgie portfeļi, modeļu backtests, risks vs. peļņa, QuantStats atskaites.</p>
                    </div>
                </div>
            </a>

        </div>

        {{-- Quick tip --}}
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-100 rounded-xl p-5 text-sm text-gray-700">
            <p class="font-semibold mb-1">💡 Sākam ar</p>
            <p>
                Izvēlies sadaļu kreisajā joslā vai uz kartēm augstāk. <strong>Fundamentālajos datos</strong> meklē instrumentu pēc ticker vai uzņēmuma, lai redzētu tā finanšu pārskatus.
                <strong>Tehniskajos datos</strong> redzēsi cenas grafiku un Engela trijstūri tam pašam instrumentam.
                <strong>Portfeļos</strong> vari izveidot backtest, salīdzināt to ar S&amp;P 500, Nasdaq, modeļiem.
            </p>
        </div>

    </div>
</div>
@endsection
