@props([
    'icon' => null,
    'title' => null,
    'footer' => null,
    'header' => null,
    'noPadder' => false,
    'iconType' => 'fad',
    'collapsed' => false,
    'collapsable' => false,
])

@php
    $hasBody = trim((string) $slot) !== '';
@endphp

<div {{ $attributes->merge(['class' => 'shadow overflow-hidden rounded-xl']) }} @if($collapsable) x-cloak x-data="{show: {{ $collapsed ? 'false' : 'true' }}}" @endif>
    @if($title)
        <div class="bg-surface py-4 px-5 sm:px-6 @if($collapsable) cursor-pointer @else border-b border-line @endif" @if($collapsable) x-on:click="show = !show" x-bind:class="show ? 'border-b border-line' : ''" @endif">
            <h3 class="inline-block text-lg leading-6 font-medium text-fg">
                @if($icon)<i class="{{ $iconType }} fa-fw {{ $icon }} text-fg-subtle"></i> @endif{{ $title }}
            </h3>

            {!! $header ?? '' !!}

            @if($collapsable)
                <button type="button" class="text-muted float-right">
                    <i class="fa fa-fw" x-bind:class="show ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            @endif
        </div>
    @endif
    @if($hasBody)
        <div @class(['bg-surface', 'py-4 px-5' => !$noPadder]) @if($collapsable) x-show="show" @endif>
            {{ $slot }}
        </div>
    @endif
    @if($footer)
        <div @class(['flex gap-x-3 justify-end py-4 px-5 bg-surface-2', 'border-t border-line' => $hasBody]) @if($collapsable) x-show="show" @endif>
            {{ $footer }}
        </div>
    @endif
</div>
