<div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-line p-1.5' => config('chief.shell.variant') === 'modern']) role="none">
    <form method="POST" action="{{ route('auth.logout') }}" role="none">
        @if(config('chief.shell.variant') === 'modern')
            <button type="submit" class="group flex w-full cursor-pointer items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium text-fg-muted transition hover:bg-surface-2 hover:text-fg" role="menuitem" tabindex="-1">
                <i class="fa fa-fw fa-sign-out text-fg-faint group-hover:text-fg-muted"></i>
                <span class="min-w-0 flex-1 truncate">Sign out</span>
            </button>
        @else
            <button type="submit" class="group block w-full cursor-pointer px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
                <i class="mr-1 fa fa-fw fa-sign-out text-gray-400 group-hover:text-gray-500"></i> Sign out
            </button>
        @endif
        @csrf
    </form>
</div>
