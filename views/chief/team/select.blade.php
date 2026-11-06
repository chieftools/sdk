@php
    /** @var \ChiefTools\SDK\Entities\User $user */
    /** @var \ChiefTools\SDK\Entities\Team $team */
    /** @var \Illuminate\Support\Collection<int, \ChiefTools\SDK\Entities\Team> $teams */
    /** @var \Illuminate\Support\Collection<int, string> $destinations */
@endphp

{{-- Without the app's menu, which needs a current team the team selection does not have --}}
@extendsfirst(['layout.base', 'chief::layout.html'], ['title' => __('chief::ui.team_select.title'), 'fullHeight' => true])

@section('body')
    <div class="flex min-h-full flex-col items-center justify-center bg-bg px-4 py-16 text-fg">
        <main class="w-full max-w-md">
            <div class="flex justify-center text-brand">
                @if(config('chief.brand.logoUrl'))
                    <img class="max-h-12 max-w-12" src="{{ config('chief.brand.logoUrl') }}" alt="{{ config('app.title') }}">
                @else
                    <i class="fad fa-fw {{ config('chief.brand.icon') }} fa-3x" title="{{ config('app.title') }}"></i>
                @endif
            </div>

            <h1 class="mt-6 text-center text-xl font-semibold text-fg">{{ __('chief::ui.team_select.title') }}</h1>
            <p class="mt-1 text-center text-sm text-fg-muted">
                {{ $destinations->isEmpty() ? __('chief::ui.team_select.none') : __('chief::ui.team_select.intro') }}
            </p>

            @if($teams->isNotEmpty())
                <ul class="mt-6 divide-y divide-line overflow-hidden rounded-lg border border-line bg-surface">
                    @foreach($teams as $team)
                        <li>
                            @if($destinations->has($team->id))
                                <a href="{{ $destinations->get($team->id) }}" class="flex items-center gap-3 px-4 py-3 transition outline-none hover:bg-surface-2 focus-visible:bg-surface-2">
                            @else
                                <div class="flex items-center gap-3 px-4 py-3 opacity-50" title="{{ __('chief::ui.team_select.no_access') }}">
                            @endif
                                <img class="size-8 shrink-0 rounded-md" src="{{ $team->avatar_url }}" alt="">
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-1.5 truncate text-sm font-medium text-fg">
                                        <span class="truncate">{{ $team }}</span>
                                        @if($team->id === $user->default_team_id)
                                            <span class="inline-flex h-5 shrink-0 items-center rounded-sm bg-surface-3 px-1.5 text-xs font-medium leading-none text-fg-muted">{{ __('chief::ui.team_select.default') }}</span>
                                        @endif
                                    </span>
                                    <span class="block truncate font-mono text-xs text-fg-subtle">{{ $team->slug }}</span>
                                </span>
                            @if($destinations->has($team->id))
                                    <i class="fa fa-fw fa-chevron-right shrink-0 text-xs text-fg-faint"></i>
                                </a>
                            @else
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(Illuminate\Support\Facades\Route::has('team.chief.manage') && $teams->isNotEmpty())
                <div class="mt-4 text-center">
                    <a href="{{ route('team.chief.manage', [$user->defaultOrFirstTeam()]) }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:text-brand-500">
                        {{ __('chief::ui.team_select.manage') }} <i class="fa fa-fw fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                </div>
            @endif
        </main>
    </div>
@endsection
