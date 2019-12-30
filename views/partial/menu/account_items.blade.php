<li class="nav-header">Account</li>
<li class="nav-item">
    <a class="nav-link {{ active('account/profile') }}" href="{{ route('account.profile') }}">
        <i class="fad fa-fw fa-user-circle"></i> Profile
    </a>
</li>
@if(IronGate\Integration\Entities\User::hasPreferences())
    <li class="nav-item">
        <a class="nav-link {{ active('account/preferences') }}" href="{{ route('account.preferences') }}">
            <i class="fad fa-fw fa-cog"></i> Preferences
        </a>
    </li>
@endif
