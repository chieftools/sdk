@php
    $minimalMenu = isset($minimalMenu) && $minimalMenu;
    $menuLogoText = !isset($menuLogoText) || $menuLogoText;
    $fullwidthMenu = $minimalMenu || (isset($fullwidthMenu) && $fullwidthMenu);
@endphp
<nav @class(['mb-5' => $minimalMenu, 'bg-white shadow' => !$minimalMenu])
     x-data="{ menuOpen: false, userMenuOpen: false, teamMenuOpen: false, teamMenuOpened: false }"
     @if($minimalMenu)
         x-bind:class="menuOpen ? 'bg-white shadow' : ''"
    @endif
>
    <div @class(['px-2 sm:px-6 lg:px-8', 'max-w-7xl mx-auto' => !$fullwidthMenu])>
        <div class="relative flex justify-between h-14">
            <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                <button x-cloak x-on:click="menuOpen = !menuOpen" type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-brand-500"
                        aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>

                    <svg class="h-6 w-6" x-show="menuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <svg class="h-6 w-6" x-show="!menuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                <a href="{{ $logoRedirect ?? home() }}" class="flex shrink-0 items-center text" aria-label="Back to home">
                    @if(config('chief.brand.logoUrl'))
                        <img class="max-h-8 max-w-8" src="{{ config('chief.brand.logoUrl') }}" alt="{{ config('app.title') }}">
                    @elseif(config('chief.brand.brandIcon'))
                        <span class="fa-stack text-brand">
                            <i class="fad {{ config('chief.brand.icon') }} fa-stack-2x"></i>
                            <i class="fab {{ config('chief.brand.brandIcon') }} fa-stack-1x" style="font-size: 8px;"></i>
                        </span>
                    @else
                        <i class="fad fa-fw {{ config('chief.brand.icon') }} text-brand text-3xl sm:text-2xl"></i>
                    @endif
                    @if($menuLogoText)
                        <span class="hidden md:inline-block text-xl">&nbsp;{{ config('app.title') }}</span>
                    @endif
                </a>

                @unless(empty($menuItems))
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-4 md:space-x-6 sm:ml-auto main-menu-items">
                        @foreach($menuItems as $item)
                            @php
                                $item['router-link-active'] = false;

                                if (!empty($item['vue-href']) && $item['active'] ?? false) {
                                    $item['active'] = false; // prevent normal active classes from being applied
                                    $item['router-link-active'] = true;
                                }

                                if ($minimalMenu) {
                                    $itemClass = $item['active'] ?? false
                                        ? 'text-gray-900 inline-flex items-center px-1 pt-1 text-sm font-medium'
                                        : 'text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium';
                                } else {
                                    $itemClass = $item['active'] ?? false
                                        ? 'border-brand-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium'
                                        : 'border-transparent text-gray-500 hover:border-brand-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium';
                                }

                                $iconClass = $item['active'] ?? false
                                    ? 'text-brand'
                                    : 'text-gray-500 group-hover:text-brand-300';
                            @endphp
                            @if(isset($item['vue']) && $item['vue'])
                                <router-link v-bind:to="{!! $item['to'] ?? $item['href'] !!}" class="group {{ $itemClass }}">
                                    @if(isset($item['icon']))
                                        <i class="fa-fw {{ $item['icon'] }} {{ $iconClass }} mr-1.5"></i>
                                    @endif<span class="hidden md:inline">{{ $item['text'] }}</span>
                                </router-link>
                            @else
                                <a href="{{ $item['href'] }}" @class(["group {$itemClass}", 'router-link-active' => $item['router-link-active'] ?? false]) @if(!empty($item['vue-href'])) vue-href='{{ $item['vue-href'] }}' @endif @if(isset($item['wire']) && $item['wire']) wire:navigate @endif>
                                    @if(isset($item['icon']))
                                        <i class="fa-fw {{ $item['icon'] }} {{ $iconClass }} mr-1.5"></i>
                                    @endif<span class="hidden md:inline">{{ $item['text'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endunless
            </div>

            @auth
                @if(config('chief.teams'))
                    <div class="absolute inset-y-0 right-10 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                        <div class="ml-3 relative">
                            <div>
                                <button x-on:click="teamMenuOpen = !teamMenuOpen; teamMenuOpened = true" type="button"
                                        class="bg-white rounded-md flex text-sm focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-brand-500" id="team-menu-button"
                                        aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open team menu</span>
                                    <img class="h-8 w-8 rounded-md" src="{{ auth()->user()->team->avatar_url }}" alt="">
                                </button>
                                <div class="absolute -right-1.5 -bottom-1.5 text-[8px] leading-[12px] text-white text-center font-bold bg-brand rounded-full border-white border-2 w-4 h-4">
                                    T
                                </div>
                            </div>

                            <div x-cloak
                                 x-show="teamMenuOpen"
                                 x-on:click.away="teamMenuOpen = false"
                                 x-on:keydown.escape.stop="teamMenuOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black/5 divide-y divide-gray-100 focus:outline-hidden z-20"
                                 role="menu"
                                 aria-orientation="vertical"
                                 aria-labelledby="menu-button"
                                 tabindex="-1">
                                @include('chief::partial.team.dropdown_items')
                            </div>
                        </div>
                    </div>
                @endif

                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto {{ config('chief.teams') ? 'sm:ml-0' : 'sm:ml-6' }} sm:pr-0">
                    <div class="{{ config('chief.teams') ? 'ml-4' : 'ml-3' }} relative">
                        <div>
                            <button x-on:click="userMenuOpen = !userMenuOpen" type="button"
                                    class="bg-white rounded-md flex text-sm focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-brand-500" id="user-menu-button"
                                    aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open user menu</span>
                                <img class="h-8 w-8 rounded-md" src="{{ auth()->user()->avatar_url }}" alt="">
                            </button>
                            @if(config('chief.teams'))
                                <div class="absolute -right-1.5 -bottom-1.5 text-[8px] leading-[12px] text-white text-center font-bold bg-brand rounded-full border-white border-2 w-4 h-4">
                                    P
                                </div>
                            @endif
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
                             class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black/5 divide-y divide-gray-100 focus:outline-hidden z-20"
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
                        @if(isset($item['icon']))
                            <i class="fa-fw {{ $item['icon'] }} {{ $iconClass }} mr-1.5"></i>
                        @endif{{ $item['text'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endunless
</nav>
