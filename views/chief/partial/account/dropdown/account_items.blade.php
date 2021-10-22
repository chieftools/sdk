<div class="px-4 py-3" role="none">
    <p class="text-sm" role="none">
        Signed in as:
    </p>
    <p class="text-sm font-medium text-gray-900 truncate" role="none">
        {{ auth()->user() }}
    </p>
</div>
<div class="py-1" role="none">
    <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
        <i class="mr-1 fa fa-fw fa-user-circle text-gray-400 group-hover:text-gray-500"></i> Profile
    </a>
    @if(IronGate\Chief\Entities\User::hasPreferences())
        <a href="{{ route('account.preferences') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem" tabindex="-1">
            <i class="mr-1 fa fa-fw fa-cog text-gray-400 group-hover:text-gray-500"></i> Preferences
        </a>
    @endif
</div>
