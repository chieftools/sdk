## Integration Chief 

Tools for integrating Chief Tools with Account Chief.

### Installation

Start with requiring the package:

```bash
composer require irongate/integration
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
