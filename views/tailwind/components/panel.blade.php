@props([
    'icon' => null,
    'title' => null,
    'footer' => null,
])

<div class="shadow sm:rounded-md sm:overflow-hidden">
    @if($title)
        <div class="bg-white py-4 px-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                @if($icon)<i class="fad fa-fw {{ $icon }}"></i> @endif{{ $title }}
            </h3>
        </div>
    @endif
    <div class="bg-white py-4 px-5">
        {{ $slot }}
    </div>
    @if($footer)
        <div class="py-4 px-5 bg-gray-50 text-right">
            {{ $footer }}
        </div>
    @endif
</div>
