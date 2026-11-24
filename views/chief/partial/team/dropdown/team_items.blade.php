@php
    /** @var \ChiefTools\SDK\Entities\Team $_chief_team */
    /** @var \ChiefTools\SDK\Entities\Team $_current_team */

    $_chief_teams  = auth()->user()->teams;
    $_current_team = auth()->user()->team;
@endphp

<div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-line p-1.5' => config('chief.shell.variant') === 'modern']) role="none">
    <div class="max-h-72 overflow-y-auto overscroll-contain" role="none">
        @foreach($_chief_teams as $_chief_team)
            @continue($_chief_team->is($_current_team))
            @if($_chief_team->isAccessRestricted())
                <x-chief::account.dropdown-link :href="$_chief_team->accessRestrictedUrl()" target="_blank">
                    <span class="opacity-50" title="{{ __('chief::ui.shell.team_access_restricted') }}"><img @if(config('chief.shell.variant') === 'modern') src="{{ $_chief_team->avatar_url }}" @else x-bind:src="teamMenuOpened ? '{{ $_chief_team->avatar_url }}' : ''" src="" @endif class="inline h-4 w-4 rounded-md mr-1 grayscale" alt=""> {{ $_chief_team }} <i class="fad fa-fw fa-lock text-xs"></i></span>
                    <span class="sr-only">{{ __('chief::ui.shell.team_access_restricted') }}</span>
                </x-chief::account.dropdown-link>
            @else
                <x-chief::account.dropdown-link :href="route('team.switch', [$_chief_team])">
                    <img @if(config('chief.shell.variant') === 'modern') src="{{ $_chief_team->avatar_url }}" @else x-bind:src="teamMenuOpened ? '{{ $_chief_team->avatar_url }}' : ''" src="" @endif class="inline h-4 w-4 rounded-md mr-1" alt=""> {{ $_chief_team }}
                </x-chief::account.dropdown-link>
            @endif
        @endforeach
    </div>

    <x-chief::account.dropdown-link :href="route('team.chief.manage', [$_current_team])" icon="fa-people-group" iconType="fad" target="_blank">
        Manage teams
    </x-chief::account.dropdown-link>
</div>
