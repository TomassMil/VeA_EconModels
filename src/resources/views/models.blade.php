@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Investīciju teorijas — vērtēšanas modeļi</h1>
            <p class="text-gray-600 mt-2 max-w-3xl">
                50 nosaukti modeļi, sadalīti 6 kategorijās. Katrs modelis ar autoru, gadu un īsu pielietojuma aprakstu.
            </p>
        </div>

        {{-- Category quick-jump nav --}}
        <nav class="mb-10 flex flex-wrap gap-2 sticky top-16 bg-white py-3 border-b border-gray-100 z-10">
            @foreach ($valuationCategories as $catKey => $cat)
                <a href="#{{ $catKey }}"
                   class="text-xs font-medium text-gray-700 hover:text-blue-600 border border-gray-200 rounded-full px-3 py-1.5 hover:bg-blue-50 transition-colors">
                    {{ \Illuminate\Support\Str::before($cat['title'], '.') }}.
                    <span class="text-gray-500">({{ count($cat['models']) }})</span>
                </a>
            @endforeach
        </nav>

        {{-- Categories with models --}}
        @foreach ($valuationCategories as $catKey => $cat)
            <section id="{{ $catKey }}" class="mb-12 scroll-mt-32">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $cat['title'] }}</h2>
                    <p class="text-gray-600 mt-1">{{ $cat['description'] }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($cat['models'] as $model)
                        <article class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-blue-300 transition-all">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <h3 class="text-base font-semibold text-gray-900 leading-tight">{{ $model['name'] }}</h3>
                                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded shrink-0">{{ $model['year'] }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">{{ $model['author'] }}</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $model['note'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="mt-12 p-5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600">
            <strong class="text-gray-800">Atsauce:</strong>
            Modeļu klasifikācija un detaļas balstītas uz akadēmisko literatūru un MBA līmeņa vērtēšanas teorijas materiāliem.
            Pilna formula, autorlapas un pielietojuma piemēri pieejami specifiskajā literatūrā par katru modeli.
        </div>
    </div>
</div>
@endsection
