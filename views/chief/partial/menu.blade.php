<nav class="bg-white shadow" x-data="{ menuOpen: false, userMenuOpen: false }">
    <div class="{{ ($fullwidth ?? false) === true ? 'px-4' : 'max-w-7xl px-2 sm:px-6 lg:px-8' }} mx-auto">
        <div class="relative flex justify-between h-14">
            <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                <button x-on:click="menuOpen = !menuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>

                    <svg class="h-6 w-6" x-bind:class="menuOpen ? 'hidden' : 'block'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="h-6 w-6" x-bind:class="menuOpen ? 'block' : 'hidden'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                <a href="{{ $logoRedirect ?? '/' }}" class="flex shrink-0 items-center">
                    <i class="fad fa-fw {{ config('chief.icon') }} text-brand text-3xl sm:text-2xl"></i><span class="hidden sm:inline-block text-xl">&nbsp;{{ config('app.title') }}</span>
                </a>

                @unless(empty($menuItems))
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8 sm:ml-auto">
                        @foreach($menuItems as $item)
                            @php
                                $itemClass = $item['active'] ?? false
                                    ? 'border-brand-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium';
                                $iconClass = $item['active'] ?? false
                                    ? 'text-gray-900'
                                    : 'text-gray-500 group-hover:text-brand-300';
                            @endphp
                            <a href="{{ $item['href'] }}" class="group {{ $itemClass }}">
                                @if(isset($item['icon']))<i class="fa-fw {{ $item['icon'] }} {{ $iconClass }} mr-1.5"></i> @endif{{ $item['text'] }}
                            </a>
                        @endforeach
                    </div>
                @endunless
            </div>

            @auth
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                    <div class="ml-3 relative">
                        <div>
                            <button x-on:click="userMenuOpen = !userMenuOpen" type="button" class="bg-white rounded-md flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open user menu</span>
                                <img class="h-8 w-8 rounded-md" src="{{ auth()->user()->avatar_url }}" alt="">
                            </button>
                        </div>

                        <div x-cloak
                             x-show="userMenuOpen"
                             x-on:click.away="userMenuOpen = false"
                             x-on:keydown.escape.stop="userMenuOpen = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-20"
                             role="menu"
                             aria-orientation="vertical"
                             aria-labelledby="menu-button"
                             tabindex="-1">
                            @include('chief::partial.account.dropdown_items')
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </div>

    @unless(empty($menuItems))
        <div x-cloak x-show="menuOpen" class="sm:hidden" id="mobile-menu">
            <div class="pt-2 pb-4 space-y-1">
                @foreach($menuItems as $item)
                    @php
                        $itemClass = $item['active'] ?? false
                            ? 'bg-brand-50 border-brand-500 text-brand-700 block pl-3 pr-4 py-2 border-l-4'
                            : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4';
                        $iconClass = $item['active'] ?? false
                            ? 'text-gray-900'
                            : 'text-gray-500 group-hover:text-brand-300';
                    @endphp
                    <a href="{{ $item['href'] }}" class="group {{ $itemClass }} text-base font-medium">
                        @if(isset($item['icon']))<i class="fa-fw {{ $item['icon'] }} {{ $iconClass }} mr-1.5"></i> @endif{{ $item['text'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endunless
</nav>
