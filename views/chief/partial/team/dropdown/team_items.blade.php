@php
    /** @var \ChiefTools\SDK\Entities\Team|null $_chief_team */

    $_chief_teams = auth()->user()->teams;
    $_chief_team  = auth()->user()->team;

    if ($_chief_team !== null) {
        $_chief_teams = $_chief_teams->keyBy('id')->except($_chief_team->id);
    }
@endphp

@if($_chief_teams->isNotEmpty())
    <div class="py-1" role="none">
        @foreach($_chief_teams as $_chief_team)
            <x-chief::account.dropdown-link :href="route('team.switch', [$_chief_team])">
                <img class="inline h-4 w-4 rounded-md mr-1" src="{{ $_chief_team->avatar_url }}" alt=""> {{ $_chief_team }}
            </x-chief::account.dropdown-link>
        @endforeach

        <x-chief::account.dropdown-link :href="config('chief.base_url') . '/teams'" icon="fa-people-group" iconType="fad" target="_blank">
            Manage teams
        </x-chief::account.dropdown-link>
    </div>
@endif
