@extends('chief::layout.account', ['title' => 'Preferences'])

@section('maincontent')
    <x-tw::panel icon="fa-cog" title="Preferences" x-data="{
        toggle(preference) {
            fetch('{{ route('account.preferences.toggle') }}', {
                body: JSON.stringify({identity: `preference:${preference}`, state: !this.on}),
                method: 'post',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then((response) => response.json().then((data) => ({ok: response.ok, data})))
                .then(({ok, data}) => {
                    this.on = data.state ?? this.on;
                    this.error = ok ? null : (data.message ?? 'This preference could not be changed, please try again.');

                    // The layout is drawn on the server, so it changes with the page
                    if (preference === '{{ ChiefTools\SDK\Chief::SIDEBAR_LAYOUT_PREFERENCE }}') {
                        window.location.reload();
                    }
                });
        },
    }">
        @foreach($categories as $categoryKey => $preferences)
            <div class="mb-4 last:mb-0">
                @php($category = config("chief.preference_categories.{$categoryKey}"))

                <h2 class="text-lg leading-6 font-medium text-fg">{{ $category['name'] }}</h2>

                <ul role="list" class="divide-y divide-line">
                    @foreach($preferences as $preference => [$title, $description, $icon, $default])
                        @php($lockedReason = $user->preferenceLockedReason($preference))
                        <li class="py-3 last:pb-0 flex items-center justify-between" x-data="{
                            on: {{ $user->getPreference($preference, $default) ? 'true' : 'false' }},
                            error: null,
                        }">
                            <div class="flex flex-col">
                                <p class="text-sm font-medium text-fg">
                                    <i class="fal fa-fw fa-{{ $icon }}"></i> {!! $title !!}
                                </p>
                                <p class="text-sm text-fg-subtle">
                                    {!! $description !!}
                                </p>
                                @if($lockedReason !== null)
                                    <p class="mt-1 text-sm text-amber" data-preference-locked="{{ $preference }}">
                                        <i class="fal fa-fw fa-lock"></i> {{ $lockedReason }}
                                    </p>
                                @endif
                                <p x-cloak x-show="error" x-text="error" class="mt-1 text-sm text-red" role="alert"></p>
                            </div>
                            <button x-cloak
                                    type="button"
                                    @class([
                                        'ml-4 relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-brand-500 bg-brand-500',
                                        'cursor-pointer' => $lockedReason === null,
                                        'cursor-not-allowed opacity-50' => $lockedReason !== null,
                                    ])
                                    x-ref="preference_{{ str_slug($preference) }}"
                                    :class="{'bg-brand-500': on, 'bg-line-strong': !on}"
                                    @if($lockedReason !== null)
                                        disabled
                                        aria-disabled="true"
                                    @else
                                        @click="toggle('{{ $preference }}')"
                                    @endif>
                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 translate-x-5" :class="{ 'translate-x-5': on, 'translate-x-0': !(on) }"></span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </x-tw::panel>

    <p class="text-sm text-fg-subtle text-center">
        <i class="fal fa-fw fa-question-circle"></i> Missing something you would like to configure? <a href="{{ route('chief.contact') }}" class="text-brand-600 hover:text-brand-500">Tell us!</a>
    </p>
@endsection
