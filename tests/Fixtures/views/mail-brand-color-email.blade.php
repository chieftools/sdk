<x-mail::message :quick-links="[
    ['title' => 'Dashboard', 'url' => 'https://example.com/dashboard'],
    ['title' => 'Notification settings', 'url' => 'https://example.com/notifications'],
]">
# Hello

Use the action below.

> Quoted guidance

<x-mail::panel>
Panel copy
</x-mail::panel>

<x-mail::band color="muted">
**Previous certificate**

passport.lsm.nl (Let's Encrypt)
</x-mail::band>

<x-mail::divider>
Replaced by
</x-mail::divider>

<x-mail::band color="success">
**New certificate**

passport.lsm.nl (Let's Encrypt)
</x-mail::band>

<x-mail::table>
| Product | Count |
| --- | ---: |
| Test | 1 |
</x-mail::table>

<x-mail::button :url="'https://example.com/action'">
Continue
</x-mail::button>

Thanks for using the SDK.

<x-slot:subcopy>
Subcopy card content.
</x-slot:subcopy>
</x-mail::message>
