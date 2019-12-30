<li id="userProfileDropdown" class="nav-item dropdown {{ active('account/*') }}">
    <a href="{{ route('account.profile') }}" data-target="#userProfileDropdown" class="nav-link dropdown-toggle mr-0 pr-0" data-toggle="dropdown">
        <img src="https://secure.gravatar.com/avatar/{{ md5(auth()->user()->email) }}?s=40&d=mm" alt="{{ auth()->user()->name }}" class="rounded-circle" style="margin-right: 5px; margin-top: -4px" width="20">
        <span class="d-md-none d-lg-inline-block">
            {{ auth()->user()->name }}
        </span>
        <i class="fal fa-chevron-down"></i>
    </a>
    <div class="dropdown-menu" role="menu">
        @include('chief::partial.account.dropdown_items')
    </div>
</li>
