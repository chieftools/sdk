<x-mail::message :quick-links="$quickLinks">
# Webhooks are disabled

Every webhook from your @if($dashboardUrl !== null)<a href="{{ $dashboardUrl }}">{{ $teamName }}</a>@else**{{ $teamName }}**@endif team to **{{ $url }}** failed for {{ $disableAfterDays }} days, so the endpoint is disabled and receives no events.

<x-mail::panel>
Fix the endpoint, then enable it again. Events that happen while it is disabled are not sent to it later. Deliveries that were still being retried are cancelled, you can resend those from its delivery log.
</x-mail::panel>

<x-mail::button :url="$endpointUrl">
View endpoint
</x-mail::button>

Regards,  
Chief Tools
</x-mail::message>
