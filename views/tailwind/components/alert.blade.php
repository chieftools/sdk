@props([
    'icon'     => null,
    'type'     => 'info',
    'closable' => false,
])

@php
    [$bgColor, $hoverBgColor, $headingColor, $textColor, $focusRingColor, $focusRingOffsetColor] = [
        'info'    => ['bg-blue/10', 'hover:bg-blue/15', 'text-blue', 'text-blue', 'focus:ring-blue', 'focus:ring-offset-surface'],
        'danger'  => ['bg-red/10', 'hover:bg-red/15', 'text-red', 'text-red', 'focus:ring-red', 'focus:ring-offset-surface'],
        'success' => ['bg-green/10', 'hover:bg-green/15', 'text-green', 'text-green', 'focus:ring-green', 'focus:ring-offset-surface'],
        'warning' => ['bg-amber/10', 'hover:bg-amber/15', 'text-amber', 'text-amber', 'focus:ring-amber', 'focus:ring-offset-surface'],
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
        <div class="flex-shrink-0 @if(!empty($heading)) -mt-[3px] @endif">
            <i class="fa-fw {{ $icon }} {{ $headingColor }}"></i>
        </div>
        <div class="ml-3">
            @if(!empty($heading))
                <p class="text-sm font-medium {{ $headingColor }}">
                    {{ $heading }}
                </p>
            @endif
            <div class="{{ empty($heading) ? 'mt-0.5' : 'mt-2' }} text-sm {{ $textColor }}">
                <p>
                    {{ $slot }}
                </p>
            </div>
            {{ $actions ?? '' }}
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

@if($closable)
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
@endif
