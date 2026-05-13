<nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">
            {{-- Logo + app name --}}
            <a href="/" class="flex items-center space-x-3">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-lg font-bold text-gray-900">VeA EconModels</span>
            </a>

            {{-- User info + logout --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('about') }}" class="hidden sm:inline text-xs text-gray-500 hover:text-gray-700 transition-colors">
                    Par projektu
                </a>
                @auth
                    <div class="flex items-center gap-3 border-l border-gray-200 pl-4">
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
        </div>
    </div>
</nav>
