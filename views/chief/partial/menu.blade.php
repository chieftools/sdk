@if(config('chief.shell.variant') === 'modern')
    @include('chief::partial.shell.modern')
@else
    @include('chief::partial.shell.legacy')
@endif
