## Chief Tools base

[![Total Downloads](https://poser.pugx.org/irongate/chief/downloads)](https://packagist.org/packages/irongate/chief)
[![Monthly Downloads](https://poser.pugx.org/irongate/chief/d/monthly)](https://packagist.org/packages/irongate/chief)
[![Latest Stable Version](https://poser.pugx.org/irongate/chief/v/stable)](https://packagist.org/packages/irongate/chief)
[![License](https://poser.pugx.org/irongate/chief/license)](https://packagist.org/packages/irongate/chief)

Base functionality and helpers used for building for Chief Tools.

### Configures

- (Socialite) authentication through [Account Chief](https://account.chief.app/)
- [Laravel Passport](https://laravel.com/docs/6.x/passport) for API access
- [Sentry](https://docs.sentry.io/platforms/php/laravel/) client 
- [Lighthouse GraphQL](https://lighthouse-php.com/) with base schema and scalars
    - Session protected endpoint `/api/graphql/web`
    - Session protected (GraphiQL) playground `/api/playground`
    - OAuth (Passport) protected endpoint `/api/graphql`
- Account pages to show profile information & preferences
- Basic API documentation & Passport personal access token management
- Redirects to Chief Tools homepage for `/contact`, `/privacy`, `/terms`
- [Chief Tools](https://chief.app/) webhook handler to be notified when a user account is closed or updated
- Health check queue job pinging `QUEUE_MONITOR_URL` every minute using the default queue (disabled when `QUEUE_MONITOR_URL` is empty or unset)
- Login event listener to update the `last_login` column on the `users` table 

### Provides

#### Concerns

- `IronGate\Chief\Concerns\Observable`
<br>For use on a Eloquent model, registers observers automatically on booting of the model

```php
class Entity extends \Illuminate\Database\Eloquent\Model
{
    use \IronGate\Chief\Concerns\Observable;

    public static function onCreated(self $entity): void
    {
        // do stuff with `$entity`
    }
}
```

- `IronGate\Chief\Concerns\UsesUUID`
<br>For use on a Eloquent model, automatically generates a UUIDv4 when creating a model

#### Middleware

- `IronGate\Chief\Middleware\AuthenticateChief`
<br>Validates a request comes from [Chief Tools](https://chief.app/)
<br>Requires `services.chief.webhook_secret` configuration to be set to a random string
- `IronGate\Chief\Middleware\AutoAuthenticate`
<br>Uses both the `api` and `web` guard and sets the first that is authenticated
- `IronGate\Chief\Middleware\ForceSecure`
<br>Make sure the request is over `https://`
- `IronGate\Chief\Middleware\MoveAccessTokenFromURLToHeader`
<br>Move the access token from `access_token` GET paramater to the `Authorization` header
- `IronGate\Chief\Middleware\SecurityHeaders`
<br>Adds a default set of security headers, can be configured by setting `chief.response.securityheaders` (array) in the app config
- `IronGate\Chief\Middleware\SentryContext`
<br>[Sentry](https://docs.sentry.io/platforms/php/) context middleware which set's the user context
- `IronGate\Chief\Middleware\TrustProxiesOnVapor`
<br>Configures `fideloper/proxy` to be used on [Laravel Vapor](https://vapor.laravel.com/)

#### Validation rules

- `IronGate\Chief\Rules\UUID`
<br>Valites the input value is a UUIDv4

#### Helpers

- `active($whitelist = null, $blacklist = null, $active = 'active', $inactive = '')`
<br>Get active state based on whitelist. Used to indicate active menu's
- `timezones(): array`
<br>Return an key-value list of all timezones
- `validate($fields, $rules): bool`
<br>Validate fields against rules. Example `validate($id, new \IronGate\Chief\Rules\UUID)`
- `latest_ca_bundle_file_path(): string`
<br>Get the path to the most up-to-date CA bundle file, uses [Certainty](https://github.com/paragonie/certainty) under the hood

### Installation

Start with requiring the package:

```bash
composer require irongate/chief
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
#import ../../vendor/irongate/chief/routes/graphql/schema.graphql
```

Anything you want to add the the schema you can do thereafter, for example:

```graphql
#import ../../vendor/irongate/chief/routes/graphql/schema.graphql

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
