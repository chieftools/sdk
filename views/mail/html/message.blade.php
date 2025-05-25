<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
<a href="{{ config('app.url') }}">{{ config('app.name') }}</a>. A <a href="https://chief.app?ref={{ config('chief.id') }}-mail">Chief Tools</a> product.

&copy; {{ date('Y') }} &mdash; {{ config('app.versionString') }} ({{ config('app.version') }})
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
