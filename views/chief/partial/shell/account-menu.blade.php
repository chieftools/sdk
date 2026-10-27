{{-- The account menu's contents: who is signed in, the theme, and the account's links --}}
<div class="flex items-center gap-3 px-4 py-3">
    <img class="size-9 rounded-lg" src="{{ auth()->user()->avatar_url }}" alt="">
    <div class="min-w-0 flex-1">
        <div class="truncate text-sm font-medium text-fg">{{ auth()->user() }}</div>
        <div class="truncate text-xs text-fg-subtle">{{ auth()->user()->email }}</div>
    </div>
</div>

@if(config('chief.shell.theme_selector', true))
    <div class="border-t border-line px-3 py-2.5" role="none">
        <div class="flex gap-1 rounded-md bg-surface-2 p-1">
            <button type="button"
                    class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition"
                    x-bind:class="themeButtonClasses('light')"
                    x-on:click="setTheme('light')">
                <i class="fad fa-fw fa-sun-bright text-[11px]"></i>
                <span>{{ __('chief::ui.shell.light') }}</span>
            </button>
            <button type="button"
                    class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition"
                    x-bind:class="themeButtonClasses('dark')"
                    x-on:click="setTheme('dark')">
                <i class="fad fa-fw fa-moon text-[11px]"></i>
                <span>{{ __('chief::ui.shell.dark') }}</span>
            </button>
            <button type="button"
                    class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition"
                    x-bind:class="themeButtonClasses('system')"
                    x-on:click="setTheme('system')">
                <i class="fad fa-fw fa-display text-[11px]"></i>
                <span>{{ __('chief::ui.shell.system') }}</span>
            </button>
        </div>
    </div>
@endif

@if(config('chief.shell.variant') === 'modern' && config('chief.shell.layout_switcher') && \Illuminate\Support\Facades\Route::has('account.preferences.toggle'))
    {{-- Only larger screens differ: smaller ones have the same header and drawer in either layout --}}
    @php($currentLayout = ChiefTools\SDK\Chief::shellLayout())
    <div class="hidden px-3 pb-2.5 lg:block" role="none">
        <div class="flex gap-1 rounded-md bg-surface-2 p-1">
            @foreach(['topbar' => ['fa-browser', __('chief::ui.shell.top_bar')], 'sidebar' => ['fa-sidebar', __('chief::ui.shell.sidebar')]] as $layoutOption => [$layoutIcon, $layoutLabel])
                <button type="button"
                        @class([
                            'flex flex-1 cursor-pointer items-center justify-center gap-1 rounded px-2 py-1 text-xs font-medium transition',
                            'bg-surface-raised text-fg shadow-sm' => $currentLayout === $layoutOption,
                            'text-fg-subtle hover:text-fg' => $currentLayout !== $layoutOption,
                        ])
                        x-on:click="setLayout(@js($layoutOption))"
                        @if($currentLayout === $layoutOption) aria-pressed="true" @endif>
                    <i class="fad fa-fw {{ $layoutIcon }} text-[11px]"></i>
                    <span>{{ $layoutLabel }}</span>
                </button>
            @endforeach
        </div>
    </div>
@endif

@include('chief::partial.account.dropdown_items')
