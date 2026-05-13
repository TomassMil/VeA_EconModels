<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VeA EconModels</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        ::-webkit-scrollbar { height: 8px; width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="bg-gray-50 h-screen flex flex-col overflow-hidden">
    @include('partials.navbar')

    {{-- App shell: 3 columns (sidebar / middle / right), each scrolls independently --}}
    <div class="flex-1 grid grid-cols-12 overflow-hidden" style="height: calc(100vh - 56px);">

        {{-- Sidebar (col-span-2 = ~16.7%) --}}
        <aside class="col-span-2 bg-white border-r border-gray-200 overflow-y-auto">
            @include('partials.sidebar')
        </aside>

        @hasSection('middle')
            {{-- Middle column (col-span-3 = 25%) --}}
            <section class="col-span-3 bg-white border-r border-gray-200 overflow-y-auto">
                @yield('middle')
            </section>

            {{-- Right column (col-span-7 = ~58.3%) --}}
            <main class="col-span-7 overflow-y-auto bg-gray-50">
                @yield('right')
            </main>
        @else
            {{-- No middle column — main content takes the rest --}}
            <main class="col-span-10 overflow-y-auto bg-gray-50">
                @yield('content')
            </main>
        @endif
    </div>
</body>
</html>
