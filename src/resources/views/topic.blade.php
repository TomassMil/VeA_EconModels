@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ url('/') }}" class="text-sm text-blue-600 hover:text-blue-800">
                ← Atpakaļ uz karti
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h1 class="text-3xl font-bold text-gray-900">
                {{ $title }}
            </h1>
            <p class="text-sm text-gray-500 mt-2">
                Tēmas kods: {{ $slug }}
            </p>

            <div class="mt-8 space-y-4 text-gray-700">
                <p>
                    Šeit būs saturs, grafiki un teorijas par šo apakškategoriju.
                </p>
                <p class="text-sm text-gray-500">
                    Pievienojiet sadaļas, kad būs zināmas prasības no pasniedzēja.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
