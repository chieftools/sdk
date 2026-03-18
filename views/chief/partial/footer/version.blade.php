<p class="mt-2 text-center font-mono text-xs text-gray-500">
    @if(Illuminate\Support\Str::startsWith(config('app.versionString'), date('Y') . '.'))
        &copy; {{ config('app.versionString') }} ({{ config('app.version') }})
    @else
        &copy; {{ date('Y') }} &mdash; {{ config('app.versionString') }} ({{ config('app.version') }})
    @endif
</p>
