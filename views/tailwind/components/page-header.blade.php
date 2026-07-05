@props([
    'breadcrumbs' => [],
])
<header {{ $attributes->merge(['class' => 'py-4']) }}>
    <div class="mx-auto flex flex-wrap items-end justify-between gap-x-4 gap-y-3">
        <div class="min-w-0 flex-[1_1_max-content]">
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
                                        <span class="ml-2 text-sm text-fg-muted">{{ $breadcrumb['title'] ?? '???' }}</span>
                                    @else
                                        <a href="{{ $breadcrumb['href'] ?? '#' }}" class="ml-2 text-sm text-brand-500 hover:text-brand-700" {{ $breadcrumb['wire'] ?? false ? 'wire:navigate' : '' }}>{{ $breadcrumb['title'] ?? '???' }}</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endunless

            <h1 class="mt-2 text-2xl leading-7 text-fg sm:truncate">
                {{ $slot }}
            </h1>
        </div>

        @if(isset($actions))
            <div class="flex flex-none">
                {{ $actions }}
            </div>
        @endif
    </div>
</header>
