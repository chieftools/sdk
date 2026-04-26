<div @class(['py-1' => config('chief.shell.variant') !== 'modern', 'border-t border-gray-200 p-1.5 dark:border-white/10' => config('chief.shell.variant') === 'modern']) role="none">
    <form method="POST" action="{{ route('auth.logout') }}" role="none">
        @if(config('chief.shell.variant') === 'modern')
            <button type="submit" class="group flex w-full cursor-pointer items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100" role="menuitem" tabindex="-1">
                <i class="fa fa-fw fa-sign-out text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-200"></i>
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
