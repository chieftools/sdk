@props([
    'breadcrumbs' => [],
])
<header {{ $attributes->merge(['class' => 'py-4']) }}>
    <div class="mx-auto sm:flex sm:items-center sm:justify-between">
        <div class="flex-1 min-w-0">
            @unless(empty($breadcrumbs))
                <nav class="flex">
                    <ol class="flex items-center space-x-2 ml-1.5">
                        <li>
                            <div>
                                <a href="{{ home() }}" class="text-brand-500 hover:text-brand-700 router-link-exact-active router-link-active">
                                    <i class="fa fa-fw fa-house text-brand text-sm"></i>
                                </a>
                            </div>
                        </li>
                        @foreach($breadcrumbs as $breadcrumb)
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-fw fa-chevron-right text-muted text-xs"></i>
                                    @if($loop->last)
                                        <span class="ml-2 text-sm">{{ $breadcrumb['title'] ?? '???' }}</span>
                                    @else
                                        <a href="{{ $breadcrumb['href'] ?? '#' }}" class="ml-2 text-sm text-brand-500 hover:text-brand-700" {{ $breadcrumb['wire'] ?? false ? 'wire:navigate' : '' }}>{{ $breadcrumb['title'] ?? '???' }}</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endunless

            <h1 class="mt-2 text-2xl leading-7 text-gray-600 sm:truncate">
                {{ $slot }}
            </h1>
        </div>

        @if(isset($actions))
            <div class="mt-5 flex xl:mt-0 xl:ml-4">
                {{ $actions }}
            </div>
        @endif
    </div>
</header>
