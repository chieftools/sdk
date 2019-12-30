@include('chief::partial.account.dropdown.account_items')
<div class="dropdown-divider"></div>

@include('chief::partial.account.dropdown.developer_items')
<div class="dropdown-divider"></div>

@include('chief::partial.account.dropdown.support_items')
<div class="dropdown-divider"></div>

{{-- This is the only include that will include it's own `dropdown-divider` below --}}
@include('chief::partial.account.dropdown.chief_items')

@include('chief::partial.account.dropdown.auth_items')
