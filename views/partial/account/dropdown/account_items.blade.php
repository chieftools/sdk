<a class="dropdown-item" href="{{ route('account.profile') }}">
    <i class="fal fa-fw fa-user-circle"></i> Profile
</a>
@if(IronGate\Chief\Entities\User::hasPreferences())
    <a class="dropdown-item" href="{{ route('account.preferences') }}">
        <i class="fal fa-fw fa-cog"></i> Preferences
    </a>
@endif
