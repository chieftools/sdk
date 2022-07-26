## Chief Tools SDK

[![Total Downloads](https://poser.pugx.org/chieftools/sdk/downloads)](https://packagist.org/packages/chieftools/sdk)
[![Monthly Downloads](https://poser.pugx.org/chieftools/sdk/d/monthly)](https://packagist.org/packages/chieftools/sdk)
[![Latest Stable Version](https://poser.pugx.org/chieftools/sdk/v/stable)](https://packagist.org/packages/chieftools/sdk)
[![License](https://poser.pugx.org/chieftools/sdk/license)](https://packagist.org/packages/chieftools/sdk)

Base functionality and helpers used for building for Chief Tools.

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
<br>Requires `services.chief.webhook_secret` configuration to be set to a random string
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
        'client_id'      => env('CHIEF_CLIENT_ID'),
        'client_secret'  => env('CHIEF_CLIENT_SECRET'),
        'webhook_secret' => env('CHIEF_SECRET'),
        'base_url'       => env('CHIEF_BASE_URL', 'https://account.chief.app'),
        'verify'         => env('CHIEF_VERIFY', true),
        'redirect'       => '/login/callback',
    ],
];
```

That's all, you should be able to authenticate against Account Chief.

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
