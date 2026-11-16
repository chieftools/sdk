## Chief Tools SDK

[![Total Downloads](https://poser.pugx.org/chieftools/sdk/downloads)](https://packagist.org/packages/chieftools/sdk)
[![Monthly Downloads](https://poser.pugx.org/chieftools/sdk/d/monthly)](https://packagist.org/packages/chieftools/sdk)
[![Latest Stable Version](https://poser.pugx.org/chieftools/sdk/v/stable)](https://packagist.org/packages/chieftools/sdk)
[![License](https://poser.pugx.org/chieftools/sdk/license)](https://packagist.org/packages/chieftools/sdk)

Base functionality and helpers used for building Chief Tools.

Keep in mind that this package is not meant to be used standalone, but as a base for building our own Chief Tools, this package is open-sourced for inspiration and to be used as a reference.

### Configures

- Authentication through [Account Chief](https://account.chief.app/) (powered by [Socialite](https://laravel.com/docs/9.x/socialite))
- Configured [Sentry](https://docs.sentry.io/platforms/php/laravel/) client
- [Lighthouse GraphQL](https://lighthouse-php.com/) with base schema and scalars
    - Session protected endpoint `/api/graphql/web`
    - Session protected (GraphiQL) playground `/api/playground`
    - Access token protected endpoint `/api/graphql` (tokens managed by [Account Chief](https://account.chief.app/))
- Basic API documentation pages for GraphQL endpoint
- Account pages to show profile information and preferences
- Team pages to show team information, preferences and billing
- Redirects to Chief Tools for `/contact`, `/privacy`, `/terms`
- [Account Chief](https://account.chief.app/) webhook handler to be notified when user, team or tokens change
- Login event listener to update the `last_login` column on the `users` table
- Health check queue job pinging `QUEUE_MONITOR_URL` every minute using the default queue (disabled when `QUEUE_MONITOR_URL` is empty or unset)

### Provides

#### Middleware

- `ChiefTools\SDK\Middleware\AuthenticateChief`
  <br>Validates a request comes from [Chief Tools](https://chief.app/)
  <br>Requires `chief.webhook_signing_secret` (`CHIEF_WEBHOOK_SIGNING_SECRET`) to be set to the signing secret of the app's endpoint in Account Chief
- `ChiefTools\SDK\Middleware\AutoAuthenticate`
  <br>Uses both the `api` and `web` guard and sets the first that is authenticated
- `ChiefTools\SDK\Middleware\ForceSecure`
  <br>Make sure the request is over `https://`
- `ChiefTools\SDK\Middleware\MoveAccessTokenFromURLToHeader`
  <br>Move the access token from `access_token` GET paramater to the `Authorization` header
- `ChiefTools\SDK\Middleware\SecurityHeaders`
  <br>Adds a default set of security headers, can be configured by setting `chief.response.securityheaders` (array) in the app config
- `ChiefTools\SDK\Middleware\TrustProxiesOnVapor`
  <br>Configures `fideloper/proxy` to be used on [Laravel Vapor](https://vapor.laravel.com/)

#### Validation rules

- `ChiefTools\SDK\Rules\UUID`
  <br>Valites the input value is a UUIDv4

#### Helpers

- `active($whitelist = null, $blacklist = null, $active = 'active', $inactive = '')`
  <br>Get active state based on whitelist. Used to indicate active menu's
- `timezones(): array`
  <br>Return an key-value list of all timezones
- `validate($fields, $rules): bool`
  <br>Validate fields against rules. Example `validate($id, new \ChiefTools\SDK\Rules\UUID)`
- `latest_ca_bundle_file_path(): string`
  <br>Get the path to the most up-to-date CA bundle file, uses [Certainty](https://github.com/paragonie/certainty) under the hood

### Installation

Start with requiring the package:

```bash
composer require chieftools/sdk
```

Publish the configuration files and optionally the migrations:

```bash
php artisan vendor:publish --tag=chief-config

# php artisan vendor:publish --tag=chief-migrations
```

Run the app migrations to create the users table:

```bash
php artisan migrate
```

Add the Chief service to the `config/services.php`:

```php
<?php

return [
    'chief' => [
        'client_id'     => env('CHIEF_CLIENT_ID'),
        'client_secret' => env('CHIEF_CLIENT_SECRET'),
        'base_url'      => env('CHIEF_BASE_URL', 'https://account.chief.app'),
        'verify'        => env('CHIEF_VERIFY', true),
        'redirect'      => '/login/callback',
    ],
];
```

That's all, you should be able to authenticate against Account Chief.

### Outbound webhooks

Apps send signed webhooks to the endpoints their teams add. The SDK stores events, delivers and retries them, and ships the settings pages, the REST API and the e-mails. An app only declares its events and calls them where things happen.

#### Add webhooks to an app

1. List the events as a backed enum implementing `ChiefTools\SDK\Webhook\Outbound\Contracts\WebhookEventType`, with `#[WebhookObject]` and `#[WebhookData]` on each case so the OpenAPI document describes the payloads.
2. Enable them in `config/chief.php`. Only set what differs from the defaults in `WebhookConfig`, the `User-Agent` defaults to the app name, like `BackupChief-Webhooks/1.0`:
   ```php
   'webhooks' => [
       'enabled'           => true,
       'events'            => App\Enums\Webhook\EventType::class,
       'queue'             => 'webhooks',
       'breadcrumb_parent' => ['title' => 'Settings', 'route' => 'settings'],
   ],
   ```
3. Give the queue its own Horizon supervisor with `tries` set to 1, the SDK schedules retries on the delivery itself.
4. Extend `ChiefTools\SDK\Webhook\Outbound\EventSender` with one method per event that renders the API resource and calls `send()`. Call those methods from the code that makes the change, before any e-mail about it.
5. Register the pages with `Route::chiefWebhooks('settings/webhooks')` in the team route group and the API with `Route::chiefWebhooksApi('<app>:webhooks')`. Add the `<app>:webhooks:read` and `<app>:webhooks:write` scopes and the webhooks capability to the app in Account Chief.
6. Put `<x-chief::webhooks.settings-card :team="$team" description="..." />` on the settings page, with a `class` for the spacing between the page's cards.
7. Listen for `EndpointFailing` and `EndpointDisabled` and send `ChiefTools\SDK\Webhook\Outbound\Mail\EndpointFailing` or `EndpointDisabled` to the team members whose notification preferences want it, with an idempotency key per endpoint and failure.
8. Test with `ChiefTools\SDK\Webhook\Outbound\Testing\Webhooks::subscribe($team)` and `Webhooks::sent()`.

#### Event conventions

Receivers often handle events from several apps, so events with the same meaning look the same everywhere.

- Name events `<resource>.<past tense verb>` in snake case, like `server.created` or `ip_list.updated`. The resource matches its API resource and ID prefix.
- `data.object` is the resource as the API returns it at the time of the event. A `.deleted` event sends the resource as it was right before. Never send secrets or one-time tokens, like an enrollment token.
- Every resource that can be created, changed and removed through the API or the app gets `.created`, `.updated` and `.deleted`. `.updated` covers changes a person or integration makes, not counters or timestamps the app updates on its own.
- A problem and its end are a pair, like `server.offline` and `server.online` or `backup.failing` and `backup.recovered`. Send the opening event once per problem, not on every check, and the closing event only when the problem went away. A problem that ends for another reason, like a deleted rule, closes with a field that says why or without an event.
- The closing event carries `<state>_since`, like `offline_since` or `failing_since`: when the app recorded the problem that the opening event announced, as an ISO 8601 UTC time. It is not the last contact or heartbeat.
- Extra fields next to `object` are snake case and documented with `#[WebhookData]`. IDs of other resources use their API ID with prefix, like `run_id`. Enumerated values are extensible enums, receivers treat unknown values as unknown.
- Group events in the enum like the app's settings and docs: the resource lifecycle first, then availability and alerts, then everything else.

### GraphQL API

You will need to create a `routes/graphql/schema.graphql` in your own project with the following contents:

```graphql
#import ../../vendor/chieftools/sdk/routes/graphql/schema.graphql
```

Anything you want to add the the schema you can do thereafter, for example:

```graphql
#import ../../vendor/chieftools/sdk/routes/graphql/schema.graphql

#import ./types/*.graphql
#import ./queries/*.graphql
```

Keep in mind that the `User` type is already provided so you will need to extend that if you want to append fields.

```graphql
type OfType implements Entity {
    id: ID!
}

extend type User {
    relation: [OfType!]! @hasMany(type: "paginator")
}
```
