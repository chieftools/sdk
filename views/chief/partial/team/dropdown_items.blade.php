@if(config('chief.shell.variant') === 'modern')
    @php
        /** @var \ChiefTools\SDK\Entities\Team $_chief_team */

        $_current_team = auth()->user()->team;
        $_other_teams = auth()->user()->teams->reject(fn ($team) => $team->is($_current_team))->values();
        $_can_switch_team = Illuminate\Support\Facades\Route::has('team.switch');
        $_show_team_search = $_other_teams->count() >= 7;
        $_team_palette_scope = config('app.title', config('app.name', 'Chief Tools')) . ' > Teams > ';
    @endphp

    <div class="shrink-0 px-3 pt-3 pb-3" role="none">
        <div class="flex items-center gap-3 px-1" role="none">
            <img class="size-9 rounded-lg" src="{{ $_current_team->avatar_url }}" alt="">
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium text-fg">{{ $_current_team }}</div>
                <div class="truncate text-xs text-fg-subtle">{{ __('chief::ui.shell.current_team') }}</div>
            </div>
        </div>

        @if(Illuminate\Support\Facades\Route::has('team.chief.manage.plan') || Illuminate\Support\Facades\Route::has('team.chief.manage.single'))
            <div class="mt-3 flex gap-1.5" role="none">
                @if(Illuminate\Support\Facades\Route::has('team.chief.manage.plan'))
                    <a href="{{ route('team.chief.manage.plan', [$_current_team]) }}"
                       target="_blank"
                       rel="noopener"
                       class="group flex min-w-0 flex-1 items-center gap-2 rounded-md border border-line px-2.5 py-1.5 text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg outline-none focus-visible:bg-surface-2 focus-visible:text-fg"
                       role="menuitem">
                        <i class="fad fa-fw fa-credit-card shrink-0 text-xs text-fg-subtle"></i>
                        <span class="min-w-0 truncate">Plan</span>
                        <i class="fa fa-fw fa-arrow-up-right-from-square ml-auto shrink-0 text-[10px] text-fg-faint group-hover:text-fg-muted"></i>
                    </a>
                @endif

                @if(Illuminate\Support\Facades\Route::has('team.chief.manage.single'))
                    <a href="{{ route('team.chief.manage.single', [$_current_team]) }}"
                       target="_blank"
                       rel="noopener"
                       class="group flex min-w-0 flex-1 items-center gap-2 rounded-md border border-line px-2.5 py-1.5 text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg outline-none focus-visible:bg-surface-2 focus-visible:text-fg"
                       role="menuitem">
                        <i class="fad fa-fw fa-gear shrink-0 text-xs text-fg-subtle"></i>
                        <span class="min-w-0 truncate">Settings</span>
                        <i class="fa fa-fw fa-arrow-up-right-from-square ml-auto shrink-0 text-[10px] text-fg-faint group-hover:text-fg-muted"></i>
                    </a>
                @endif
            </div>
        @endif
    </div>

    @if($_other_teams->isNotEmpty())
        <div class="flex min-h-0 flex-col border-t border-line"
             x-data="{
                 teamQuery: '',
                 teamNames: @js($_other_teams->map(fn ($team) => mb_strtolower($team->name))->all()),
                 normalizeTeamQuery(value) {
                     return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
                 },
                 teamMatches(name) {
                     const query = this.normalizeTeamQuery(this.teamQuery);

                     return query === '' || this.normalizeTeamQuery(name).includes(query);
                 },
                 hasTeamMatches() {
                     return this.teamNames.some(name => this.teamMatches(name));
                 },
                 visibleTeamOptions() {
                     return [...this.$refs.teamOptions.querySelectorAll('[data-team-option]')].filter(option => option.offsetParent !== null);
                 },
                 openFirstTeamMatch() {
                     this.visibleTeamOptions()[0]?.click();
                 },
             }"
             @if($_show_team_search)
                 x-init="$watch('teamOpen', open => {
                     teamQuery = '';
                     $refs.teamOptions.scrollTop = 0;

                     if (open && window.matchMedia('(pointer: fine)').matches) {
                         $nextTick(() => $refs.teamSearch.focus());
                     }
                 })"
             @endif
             role="none">
            @if($_show_team_search)
                <div class="shrink-0 px-1.5 pt-1.5" role="none">
                    <label class="flex items-center gap-2.5 rounded-md bg-surface-2 px-2.5 py-1.5">
                        <i class="fa fa-fw fa-magnifying-glass text-xs text-fg-faint"></i>
                        <span class="sr-only">{{ __('chief::ui.shell.find_team') }}</span>
                        <input type="search"
                               x-ref="teamSearch"
                               x-model="teamQuery"
                               x-on:keydown.enter.prevent="openFirstTeamMatch()"
                               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-fg placeholder:text-fg-faint focus:outline-none focus:ring-0"
                               placeholder="{{ __('chief::ui.shell.find_team') }}"
                               autocomplete="off"
                               spellcheck="false">
                    </label>
                </div>
            @endif

            <div x-ref="teamOptions" class="max-h-80 min-h-0 overflow-y-auto overscroll-contain p-1.5" role="none">
                @foreach($_other_teams as $_chief_team)
                    @if($_can_switch_team)
                        <a href="{{ route('team.switch', [$_chief_team]) }}"
                           x-show="teamMatches(@js(mb_strtolower($_chief_team->name)))"
                           data-team-option
                           class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-left transition outline-none hover:bg-surface-2 focus-visible:bg-surface-2"
                           role="menuitem">
                    @else
                        <div x-show="teamMatches(@js(mb_strtolower($_chief_team->name)))"
                             class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-left"
                             role="menuitem">
                    @endif
                            <img class="size-6 shrink-0 rounded-md" src="{{ $_chief_team->avatar_url }}" alt="" loading="lazy">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-fg">{{ $_chief_team }}</span>
                            </span>
                    @if($_can_switch_team)
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach

                @if($_show_team_search)
                    <div x-cloak x-show="!hasTeamMatches()" class="px-2.5 py-2 text-sm text-fg-subtle" role="none">
                        {{ __('chief::ui.shell.no_teams_found') }}
                    </div>
                @endif
            </div>

            @if($_show_team_search && $_can_switch_team)
                <button type="button"
                        class="flex w-full shrink-0 cursor-pointer items-center justify-between gap-2 px-4 pt-1 pb-2.5 text-left text-xs text-fg-subtle transition hover:text-fg outline-none focus-visible:bg-surface-2 focus-visible:text-fg"
                        x-on:click="openPalette(@js($_team_palette_scope) + teamQuery)">
                    <span class="truncate">{{ __('chief::ui.shell.switch_team_anywhere') }}</span>
                    <kbd class="shrink-0 rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px] font-medium text-fg-subtle">Cmd K</kbd>
                </button>
            @endif
        </div>
    @endif

    @if(Illuminate\Support\Facades\Route::has('team.new') || Illuminate\Support\Facades\Route::has('team.chief.manage'))
        <div class="shrink-0 border-t border-line p-1.5" role="none">
            @if(Illuminate\Support\Facades\Route::has('team.new'))
                <a href="{{ route('team.new') }}"
                   class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg outline-none focus-visible:bg-surface-2 focus-visible:text-fg"
                   role="menuitem">
                    <span class="grid size-6 shrink-0 place-items-center rounded-md border border-dashed border-line-strong text-fg-faint">
                        <i class="fa fa-fw fa-plus text-[10px]"></i>
                    </span>
                    <span class="min-w-0 flex-1 truncate">New team</span>
                </a>
            @else
                <a href="{{ route('team.chief.manage', [$_current_team]) }}"
                   target="_blank"
                   rel="noopener"
                   class="group flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg outline-none focus-visible:bg-surface-2 focus-visible:text-fg"
                   role="menuitem">
                    <span class="grid size-6 shrink-0 place-items-center rounded-md border border-dashed border-line-strong text-fg-faint">
                        <i class="fad fa-fw fa-people-group text-[11px]"></i>
                    </span>
                    <span class="min-w-0 flex-1 truncate">Manage teams</span>
                    <i class="fa fa-fw fa-arrow-up-right-from-square shrink-0 text-[10px] text-fg-faint group-hover:text-fg-muted"></i>
                </a>
            @endif
        </div>
    @endif
@else
    @include('chief::partial.team.dropdown.account_items')

    @include('chief::partial.team.dropdown.team_items')
@endif
