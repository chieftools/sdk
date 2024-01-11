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

<div {{ $attributes->merge(['class' => 'shadow overflow-hidden rounded-md']) }} @if($collapsable) x-cloak x-data="{show: {{ $collapsed ? 'false' : 'true' }}}" @endif>
    @if($title)
        <div class="bg-white py-4 px-5 border-b border-gray-200 sm:px-6" @if($collapsable) x-on:click="show = !show" @endif>
            <h3 class="inline-block text-lg leading-6 font-medium text-gray-900">
                @if($icon)<i class="{{ $iconType }} fa-fw {{ $icon }} text-gray-500"></i> @endif{{ $title }}
            </h3>

            {!! $header ?? '' !!}

            @if($collapsable)
                <button type="button" class="text-muted float-right">
                    <i class="fa fa-fw" x-bind:class="show ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            @endif
        </div>
    @endif
    <div @class(['bg-white', 'py-4 px-5' => !$noPadder]) @if($collapsable) x-show="show" @endif>
        {{ $slot }}
    </div>
    @if($footer)
        <div class="flex gap-x-3 justify-end py-4 px-5 bg-gray-50"  @if($collapsable) x-show="show" @endif>
            {{ $footer }}
        </div>
    @endif
</div>
