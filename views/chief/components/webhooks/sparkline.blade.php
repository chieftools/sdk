@props([
    // The 24 hours to draw, oldest first: `[['hour' => Carbon, 'succeeded' => int, 'failed' => int], ...]`
    'hours' => [],
])
@php
    $peak     = max(1, ...array_map(static fn (array $hour): int => $hour['succeeded'] + $hour['failed'], $hours ?: [['succeeded' => 0, 'failed' => 0]]));
    $timezone = config('app.timezone_user') ?: config('app.timezone');
    $height   = 20;
@endphp
{{-- Attempts per hour on the endpoint's own scale, failed stacked on succeeded, a faint tick for an hour without any --}}
<svg viewBox="0 0 {{ max(1, count($hours)) }} {{ $height }}" preserveAspectRatio="none" {{ $attributes->class('block h-5 w-24 shrink-0') }} role="img" aria-label="Delivery attempts per hour over the last 24 hours">
    @foreach($hours as $index => $hour)
        @php
            $succeeded = $hour['succeeded'] / $peak * ($height - 1);
            $failed    = $hour['failed'] / $peak * ($height - 1);
            $label     = $hour['hour']->copy()->setTimezone($timezone)->format('H:00') . ' · ' . ($hour['succeeded'] + $hour['failed'] === 0 ? 'no attempts' : $hour['succeeded'] . ' succeeded, ' . $hour['failed'] . ' failed');
        @endphp
        <g>
            <title>{{ $label }}</title>
            {{-- The full column takes the hover, so thin bars still show their tooltip --}}
            <rect x="{{ $index }}" y="0" width="1" height="{{ $height }}" fill="transparent" />
            @if($hour['succeeded'] + $hour['failed'] === 0)
                <rect x="{{ $index + 0.2 }}" y="{{ $height - 1 }}" width="0.6" height="1" class="fill-zinc-300 dark:fill-white/15" />
            @else
                @if($hour['succeeded'] > 0)
                    <rect x="{{ $index + 0.2 }}" y="{{ round($height - $succeeded, 2) }}" width="0.6" height="{{ round($succeeded, 2) }}" class="fill-green-500 dark:fill-green-400" />
                @endif
                @if($hour['failed'] > 0)
                    <rect x="{{ $index + 0.2 }}" y="{{ round($height - $succeeded - $failed, 2) }}" width="0.6" height="{{ round($failed, 2) }}" class="fill-red-500 dark:fill-red-400" />
                @endif
            @endif
        </g>
    @endforeach
</svg>
