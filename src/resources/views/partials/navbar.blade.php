<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="/" class="flex items-center space-x-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-xl font-bold text-gray-900">VeA EconModels</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ url('/') }}" class="text-gray-700 hover:text-blue-600 transition-colors px-3 py-2 text-sm font-medium">
                    Sākums
                </a>
                <a href="{{ route('models.index') }}" class="text-gray-700 hover:text-blue-600 transition-colors px-3 py-2 text-sm font-medium">
                    Modeļi
                </a>
                <a href="{{ route('instruments.index') }}" class="text-gray-700 hover:text-blue-600 transition-colors px-3 py-2 text-sm font-medium">
                    Instrumenti
                </a>
                <a href="{{ route('indexes.index') }}" class="text-gray-700 hover:text-blue-600 transition-colors px-3 py-2 text-sm font-medium">
                    Indeksi
                </a>
                <a href="{{ route('portfolios.index') }}" class="text-gray-700 hover:text-blue-600 transition-colors px-3 py-2 text-sm font-medium">
                    Portfelis
                </a>
                <a href="{{ route('about') }}" class="text-gray-700 hover:text-blue-600 transition-colors px-3 py-2 text-sm font-medium">
                    Par projektu
                </a>

                @auth
                    <div class="relative ml-4 flex items-center gap-3 border-l border-gray-200 pl-6">
                        <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 hover:text-red-600 transition-colors"
                            >
                                Iziet
                            </button>
                        </form>
                    </div>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center gap-3">
                @auth
                    <span class="text-xs font-medium text-gray-600">{{ Auth::user()->name }}</span>
                @endauth
                <button id="mobile-menu-btn" class="text-gray-700 hover:text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-gray-100">
            <div class="pt-3 space-y-1">
                <a href="{{ url('/') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md">Sākums</a>
                <a href="{{ route('models.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md">Modeļi</a>
                <a href="{{ route('instruments.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md">Instrumenti</a>
                <a href="{{ route('indexes.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md">Indeksi</a>
                <a href="{{ route('portfolios.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md">Portfelis</a>
                <a href="{{ route('about') }}" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md">Par projektu</a>
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="pt-2 border-t border-gray-100 mt-2">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">
                            Iziet
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</nav>

<script>
(function() {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (btn && menu) {
        btn.addEventListener('click', function() {
            menu.classList.toggle('hidden');
        });
    }
})();
</script>
