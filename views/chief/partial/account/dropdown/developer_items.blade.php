@if(Illuminate\Support\Facades\Route::has('api.docs'))
    <div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-line p-1.5' => config('chief.shell.variant') === 'modern']) role="none">
        <x-chief::account.dropdown-link :href="route('api.docs')" icon="fa-rectangle-api">
            API documentation
        </x-chief::account.dropdown-link>
    </div>
@endif
