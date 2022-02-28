@extends('chief::layout.account', ['title' => 'Preferences'])

@section('maincontent')
    <x-tw::panel icon="fa-cog" title="Preferences">
        @foreach($categories as $categoryKey => $preferences)
            <div class="mb-4 last:mb-0">
                @php($category = config("chief.preference_categories.{$categoryKey}"))

                <h2 class="text-lg leading-6 font-medium text-gray-900">{{ $category['name'] }}</h2>

                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($preferences as $preference => [$title, $description, $icon, $default])
                        <li class="py-3 last:pb-0 flex items-center justify-between" x-data="{ on: {{ $user->getPreference($preference, $default) ? 'true' : 'false' }} }">
                            <div class="flex flex-col">
                                <p class="text-sm font-medium text-gray-900">
                                    <i class="fal fa-fw fa-{{ $icon }}"></i> {!! $title !!}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {!! $description !!}
                                </p>
                            </div>
                            <button x-cloak
                                    type="button"
                                    class="ml-4 relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 bg-brand-500"
                                    x-ref="preference_{{ str_slug($preference) }}"
                                    :class="{'bg-brand-500': on, 'bg-gray-200': !on}"
                                    @click="axios.post('{{ route('account.preferences.toggle') }}', {identity: 'preference:{{ $preference }}', state: !on}).then(response => { if (response.status === 200) { on = response.data.state; } })">
                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 translate-x-5" :class="{ 'translate-x-5': on, 'translate-x-0': !(on) }"></span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </x-tw::panel>

    <p class="text-sm text-gray-500 text-center">
        <i class="fal fa-fw fa-question-circle"></i> Missing something you would like to configure? <a href="{{ route('chief.contact') }}" class="text-brand-600 hover:text-brand-500">Tell us!</a>
    </p>
@endsection
