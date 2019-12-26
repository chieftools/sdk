## Chief base

[![Total Downloads](https://poser.pugx.org/irongate/chief-base/downloads)](https://packagist.org/packages/irongate/chief-base)
[![Monthly Downloads](https://poser.pugx.org/irongate/chief-base/d/monthly)](https://packagist.org/packages/irongate/chief-base)
[![Latest Stable Version](https://poser.pugx.org/irongate/chief-base/v/stable)](https://packagist.org/packages/irongate/chief-base)
[![License](https://poser.pugx.org/irongate/chief-base/license)](https://packagist.org/packages/irongate/chief-base)

Tools and base functionality for Chief apps.

### Contains / configures

- Configured [Laravel Passport](https://laravel.com/docs/6.x/passport) including migrations
- Configured Socialite + routes authenticating against [Account Chief](https://account.chief.app/)
- Configured [Sentry](https://docs.sentry.io/platforms/php/laravel/) client including optional context middleware
- (optional) [Lighthouse GraphQL](https://lighthouse-php.com/) server with base schema

### Installation

Start with requiring the package:

```bash
composer require irongate/chief-base
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
#import ../../vendor/irongate/integrationchief/routes/graphql/schema.graphql
```

Anything you want to add the the schema you can do thereafter, for example:

```graphql
#import ../../vendor/irongate/integrationchief/routes/graphql/schema.graphql

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
