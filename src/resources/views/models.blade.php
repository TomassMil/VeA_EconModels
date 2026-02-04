@extends('layouts.app')

@section('content')
<div class="py-10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-start justify-between gap-6 flex-wrap mb-8">
            <div>
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">ModeÄ¼i</h1>
                <p class="text-gray-600 mt-2">
                    IzvÄ“lies modeli, lai atvÄ“rtu tÄ“mas lapu.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($topics as $slug => $title)
                <a
                    href="{{ route('topic.show', $slug) }}"
                    class="group bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-blue-300 transition-all"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 group-hover:text-blue-700">
                                {{ $title }}
                            </h2>
                            <p class="text-xs text-gray-500 mt-1">Kods: {{ $slug }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <canvas
                            class="w-full h-40 rounded-lg border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100"
                            width="400"
                            height="160"
                        ></canvas>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
