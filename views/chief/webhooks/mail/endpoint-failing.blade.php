<x-mail::message :quick-links="$quickLinks">
# Webhooks are failing

Webhooks from your @if($dashboardUrl !== null)<a href="{{ $dashboardUrl }}">{{ $teamName }}</a>@else**{{ $teamName }}**@endif team to **{{ $url }}** started failing {{ $failingSince }}. {{ $appName }} retries failed deliveries for about {{ $retryDays }} {{ str('day')->plural($retryDays) }}.

<x-mail::panel>
Check that the endpoint answers with a 2xx status within {{ $timeoutSeconds }} seconds. If every delivery keeps failing, the endpoint is disabled after {{ $disableAfterDays }} days.
</x-mail::panel>

<x-mail::button :url="$endpointUrl">
View endpoint
</x-mail::button>

Regards,  
Chief Tools
</x-mail::message>
