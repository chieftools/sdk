@props([
    'icon'     => null,
    'type'     => 'info',
    'closable' => false,
])

@php
    [$bgColor, $hoverBgColor, $headingColor, $textColor, $focusRingColor, $focusRingOffsetColor] = [
        'info'    => ['bg-blue-50', 'hover:bg-blue-100', 'text-blue-800', 'text-blue-700', 'focus:ring-blue-600', 'focus:ring-offset-blue-50'],
        'danger'  => ['bg-red-50', 'hover:bg-red-100', 'text-red-800', 'text-red-700', 'focus:ring-red-600', 'focus:ring-offset-red-50'],
        'success' => ['bg-green-50', 'hover:bg-green-100', 'text-green-800', 'text-green-700', 'focus:ring-green-600', 'focus:ring-offset-green-50'],
        'warning' => ['bg-yellow-50', 'hover:bg-yellow-100', 'text-yellow-800', 'text-yellow-700', 'focus:ring-yellow-600', 'focus:ring-offset-yellow-50'],
    ][$type];

    if ($icon === null) {
        $icon = [
            'info'    => 'fad fa-exclamation-circle',
            'danger'  => 'fad fa-exclamation-triangle',
            'success' => 'fad fa-exclamation-circle',
            'warning' => 'fad fa-exclamation-triangle',
        ][$type];
    }
@endphp

<div {{ $attributes->merge(['class' => "alert-container rounded-md p-4 {$bgColor}"]) }}>
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fa-fw {{ $icon }} {{ $headingColor }}"></i>
        </div>
        <div class="ml-3">
            @if(!empty($heading))
                <h3 class="text-sm font-medium {{ $headingColor }}">
                    {{ $heading }}
                </h3>
            @endif
            <div class="{{ empty($heading) ? 'mt-0.5' : 'mt-2' }} text-sm {{ $textColor }}">
                <p>
                    {{ $slot }}
                </p>
            </div>
        </div>
        @if($closable)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="alert-close-button inline-flex {{ $bgColor }} rounded-md p-1.5 {{ $textColor }} {{ $hoverBgColor }} focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $focusRingOffsetColor }} {{ $focusRingColor }}">
                        <span class="sr-only">Dismiss</span>
                        <i class="fal fa-fw fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

@once
    @push('body.script')
        <script>
            (function () {
                document.querySelectorAll('.alert-close-button').forEach((button) => {
                    button.addEventListener('click', e => {
                        e.preventDefault();

                        let parent = button.parentNode;

                        while (parent && !parent.classList.contains('alert-container')) {
                            parent = parent.parentNode;
                        }

                        if (parent) {
                            parent.remove();
                        }
                    });
                });
            })();
        </script>
    @endpush
@endonce
