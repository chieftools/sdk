@if(config('chief.shell.variant') === 'modern')
    @php
        /** @var \ChiefTools\SDK\Entities\Team $_chief_team */

        $_chief_teams = auth()->user()->teams;
        $_current_team = auth()->user()->team;
    @endphp

    <div class="px-4 py-3" role="none">
        <div class="text-[10px] font-semibold uppercase text-gray-400 dark:text-gray-500">Team</div>
    </div>

    <div class="px-1.5 pb-1.5" role="none">
        @foreach($_chief_teams as $_chief_team)
            @php($_active_team = $_chief_team->is($_current_team))

            @if($_active_team || !Illuminate\Support\Facades\Route::has('team.switch'))
                <div @class([
                    'flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-left',
                    'bg-gray-50 dark:bg-gray-800' => $_active_team,
                ]) role="menuitem">
            @else
                <a href="{{ route('team.switch', [$_chief_team]) }}"
                   class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-left transition hover:bg-gray-50 dark:hover:bg-gray-800"
                   role="menuitem">
            @endif
                    <img class="size-6 shrink-0 rounded-md" src="{{ $_chief_team->avatar_url }}" alt="">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-gray-950 dark:text-gray-100">{{ $_chief_team }}</span>
                    </span>
                    @if($_active_team)
                        <i class="fa fa-fw fa-check text-xs text-[var(--chief-shell-accent)]"></i>
                    @endif
            @if($_active_team || !Illuminate\Support\Facades\Route::has('team.switch'))
                </div>
            @else
                </a>
            @endif
        @endforeach

        @if(Illuminate\Support\Facades\Route::has('team.new'))
            <a href="{{ route('team.new') }}"
               class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
               role="menuitem">
                <span class="grid size-6 shrink-0 place-items-center rounded-md border border-dashed border-gray-300 text-gray-400 dark:border-white/15 dark:text-gray-400">
                    <i class="fa fa-fw fa-plus text-[10px]"></i>
                </span>
                <span class="min-w-0 flex-1 truncate">New team</span>
            </a>
        @elseif(Illuminate\Support\Facades\Route::has('team.chief.manage'))
            <a href="{{ route('team.chief.manage', [$_current_team]) }}"
               target="_blank"
               rel="noopener"
               class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
               role="menuitem">
                <span class="grid size-6 shrink-0 place-items-center rounded-md border border-dashed border-gray-300 text-gray-400 dark:border-white/15 dark:text-gray-400">
                    <i class="fad fa-fw fa-people-group text-[11px]"></i>
                </span>
                <span class="min-w-0 flex-1 truncate">Manage teams</span>
            </a>
        @endif
    </div>

    @if(Illuminate\Support\Facades\Route::has('team.chief.manage.plan') || Illuminate\Support\Facades\Route::has('team.chief.manage.single'))
        <div class="border-t border-gray-200 p-1.5 dark:border-white/10" role="none">
            @if(Illuminate\Support\Facades\Route::has('team.chief.manage.plan'))
                <a href="{{ route('team.chief.manage.plan', [$_current_team]) }}"
                   target="_blank"
                   rel="noopener"
                   class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                   role="menuitem">
                    <span class="grid size-6 shrink-0 place-items-center rounded-md bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                        <i class="fad fa-fw fa-credit-card text-[11px]"></i>
                    </span>
                    <span class="min-w-0 flex-1 truncate">Manage plan</span>
                </a>
            @endif

            @if(Illuminate\Support\Facades\Route::has('team.chief.manage.single'))
                <a href="{{ route('team.chief.manage.single', [$_current_team]) }}"
                   target="_blank"
                   rel="noopener"
                   class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                   role="menuitem">
                    <span class="grid size-6 shrink-0 place-items-center rounded-md bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                        <i class="fad fa-fw fa-gear text-[11px]"></i>
                    </span>
                    <span class="min-w-0 flex-1 truncate">Manage team</span>
                </a>
            @endif
        </div>
    @endif
@else
    @include('chief::partial.team.dropdown.account_items')

    @include('chief::partial.team.dropdown.team_items')
@endif
