@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">
                {{ $category['title'] }}
            </h1>
            @if (!empty($category['subtitle']))
                <p class="text-lg text-gray-600 mt-2">{{ $category['subtitle'] }}</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-3">Apraksts</h2>
            <p class="text-gray-700 leading-relaxed">
                {{ $category['description'] }}
            </p>

            <div class="mt-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Apakškategorijas</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($subtopics as $slug => $label)
                        <a
                            href="{{ route('topic.show', $slug) }}"
                            class="block rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-gray-800 font-semibold hover:bg-blue-100 hover:border-blue-300 transition"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
