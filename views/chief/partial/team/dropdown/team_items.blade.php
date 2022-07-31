@php
    /** @var \ChiefTools\SDK\Entities\Team $_chief_team */
    /** @var \ChiefTools\SDK\Entities\Team $_current_team */

    $_chief_teams  = auth()->user()->teams;
    $_current_team = auth()->user()->team;
@endphp

<div class="py-1" role="none">
    @foreach($_chief_teams as $_chief_team)
        @continue($_chief_team->is($_current_team))
        <x-chief::account.dropdown-link :href="route('team.switch', [$_chief_team])">
            <img class="inline h-4 w-4 rounded-md mr-1" src="{{ $_chief_team->avatar_url }}" alt=""> {{ $_chief_team }}
        </x-chief::account.dropdown-link>
    @endforeach

    <x-chief::account.dropdown-link :href="route('team.chief.manage', [$_chief_team])" icon="fa-people-group" iconType="fad" target="_blank">
        Manage teams
    </x-chief::account.dropdown-link>
</div>
