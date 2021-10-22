<div class="py-1" role="none">
    <form method="POST" action="{{ route('auth.logout') }}" role="none">
        <button type="submit" class="group block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
            <i class="mr-1 fa fa-fw fa-sign-out text-gray-400 group-hover:text-gray-500"></i> Sign out
        </button>
        @csrf
    </form>
</div>
