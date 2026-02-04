@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200 shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-slate-50 to-white"></div>
            <div class="relative p-8 sm:p-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold tracking-wide text-blue-600 uppercase">Par projektu</p>
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mt-2">
                        Uz ekonomikas teorijas stohastikās optimizācijas algoritmiem balstītas informācijas sistēmas izveide
                    </h1>
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <div class="rounded-xl border border-slate-200 bg-white/80 p-6 min-h-[220px]">
                            <!-- Aprakstu pievienosi šeit -->
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-white/80 p-6 min-h-[100px]">
                            <!-- Galvenie mērķi / kopsavilkums -->
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white/80 p-6 min-h-[100px]">
                            <!-- Avoti, rīki vai piezīmes -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
