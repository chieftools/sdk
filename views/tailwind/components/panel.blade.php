@props([
    'icon' => null,
    'class' => null,
    'title' => null,
    'footer' => null,
    'header' => null,
    'iconType' => 'fad',
])

<div class="shadow sm:rounded-md sm:overflow-hidden {{ $class ?? '' }}">
    @if($title)
        <div class="bg-white py-4 px-5 border-b border-gray-200 sm:px-6">
            <h3 class="inline-block text-lg leading-6 font-medium text-gray-900">
                @if($icon)<i class="{{ $iconType }} fa-fw {{ $icon }}"></i> @endif{{ $title }}
            </h3>

            {!! $header ?? '' !!}
        </div>
    @endif
    <div class="bg-white py-4 px-5">
        {{ $slot }}
    </div>
    @if($footer)
        <div class="flex gap-x-3 justify-end py-4 px-5 bg-gray-50">
            {{ $footer }}
        </div>
    @endif
</div>
