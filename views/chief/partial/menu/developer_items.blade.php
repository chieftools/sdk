@if(Illuminate\Support\Facades\Route::has('api.docs'))
    <x-chief::account.menu-link :href="route('api.docs')" :active="active('api/docs')" text="API documentation" icon="fa-rectangle-api"/>
@endif
