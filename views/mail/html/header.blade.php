@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ static_asset('icons/' . config('chief.id') . '_full_512.png') }}" class="logo light" alt="{{ config('app.name') }} Logo">
<img src="{{ static_asset('icons/' . config('chief.id') . '_full_white_512.png') }}" class="logo dark" alt="{{ config('app.name') }} Logo" style="display: none;">
</a>
</td>
</tr>
