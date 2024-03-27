<?php

namespace ChiefTools\SDK;

use GuzzleHttp;
use Pusher\Pusher;
use RuntimeException;
use ParagonIE\Certainty;
use ChiefTools\SDK\API\Client;
use Laravel\Passport\Passport;
use Sentry\Laravel\Integration;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use ChiefTools\SDK\Http\Middleware;
use ChiefTools\SDK\Console\Commands;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Events as AuthEvents;
use Illuminate\Mail\Events as MailEvents;
use Illuminate\Support\Facades\Broadcast;
use ChiefTools\SDK\GraphQL\ContextFactory;
use ChiefTools\SDK\Socialite\ChiefProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Broadcasting\BroadcastManager;
use ChiefTools\SDK\Auth\RemoteAccessTokenGuard;
use Nuwave\Lighthouse\Events as LighthouseEvents;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Database\Eloquent\MissingAttributeException;
use ChiefTools\SDK\GraphQL\Listeners\BuildExtensionsResponse;
use Laravel\Passport\RouteRegistrar as Passport10RouteRegistrar;
use Nuwave\Lighthouse\Subscriptions\SubscriptionServiceProvider;
use Nuwave\Lighthouse\Subscriptions\Contracts\StoresSubscriptions;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use ChiefTools\SDK\GraphQL\Subscriptions\Storage\RedisStorageManager;
use ChiefTools\SDK\Broadcasting\Channels\LighthouseSubscriptionChannel;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutes();

        $this->configureAuth();

        $this->configureViews();

        $this->configureEvents();

        $this->configureGraphQL();

        $this->configurePassport();

        $this->configureMiddleware();

        $this->configureSocialiteIntegration();

        $this->configureDeveloperProtections();

        if (is_running_on_vapor()) {
            $this->ensureMixAndAssetUrlsAreConfigured();
        }

        if ($this->app->runningInConsole()) {
            $this->loadCommands();

            $this->publishStaticFiles();
        }
    }

    public function register(): void
    {
        $this->loadConfig();

        $this->registerAuth();

        $this->registerGraphQLSubscriptions();

        $this->app->bind(GuzzleHttp\Client::class, static fn () => http());

        $this->app->singleton(Client::class, static fn () => new Client);

        $this->app->singleton(Certainty\RemoteFetch::class, static function () {
            return (new Certainty\RemoteFetch(storage_path('framework/cache')))
                ->setChronicle(config('chief.chronicle.url'), config('chief.chronicle.pubkey'));
        });
    }

    private function loadConfig(): void
    {
        // This is a micro optimization because `mergeConfigFrom` does this check itself
        // but we do it once instead of for every `mergeConfigFrom` call. Since we do
        // quite a few `mergeConfigFrom` calls we do this check once saving cycles.
        if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
            return;
        }

        $this->mergeConfigFrom(static::basePath('config/cors.php'), 'cors');
        $this->mergeConfigFrom(static::basePath('config/chief.php'), 'chief');
        $this->mergeConfigFrom(static::basePath('config/sentry.php'), 'sentry');
        $this->mergeConfigFrom(static::basePath('config/session.php'), 'session');
        $this->mergeConfigFrom(static::basePath('config/javascript.php'), 'javascript');
        $this->mergeConfigFrom(static::basePath('config/lighthouse.php'), 'lighthouse');
    }

    private function loadRoutes(): void
    {
        if (config('chief.routes.api')) {
            $this->loadRoutesFrom(static::basePath('routes/api.php'));
        }

        if (config('chief.routes.web')) {
            $this->loadRoutesFrom(static::basePath('routes/web.php'));
        }

        if (config('chief.routes.web-api')) {
            $this->loadRoutesFrom(static::basePath('routes/web-api.php'));
        }
    }

    private function loadCommands(): void
    {
        $this->commands([
            Commands\SyncTeams::class,
            Commands\SyncUsers::class,
            Commands\DownloadPricing::class,
            Commands\QueueHealthCheck::class,
            Commands\GraphQL\BroadcastPong::class,
        ]);

        if (!empty(config('chief.queue.monitor'))) {
            $this->app->afterResolving(Schedule::class, static function (Schedule $schedule) {
                $schedule->command(Commands\QueueHealthCheck::class)
                         ->everyMinute()
                         ->onOneServer()
                         ->runInBackground();

                if (config('chief.graphql.subscriptions.enabled')) {
                    $schedule->command(Commands\GraphQL\BroadcastPong::class)
                             ->everyMinute()
                             ->onOneServer()
                             ->runInBackground();
                }
            });
        }
    }

    private function registerAuth(): void
    {
        if (app()->configurationIsCached()) {
            return;
        }

        if (!config('chief.auth')) {
            return;
        }

        config([
            'auth.guards.chief' => array_merge([
                'driver'   => 'chief_remote',
                'provider' => null,
            ], config('auth.guards.chief', [])),
        ]);
    }

    private function configureAuth(): void
    {
        if (!config('chief.auth')) {
            return;
        }

        Auth::resolved(function (AuthManager $auth) {
            $auth->extend('chief_remote', function (Application $app, string $name, array $config) use ($auth) {
                $guard = new RequestGuard(
                    new RemoteAccessTokenGuard($name, $app->make(Client::class), $app->make('cache')),
                    request(),
                    $auth->createUserProvider($config['provider'] ?? null),
                );

                $app->refresh('request', $guard, 'setRequest');

                return $guard;
            });
        });
    }

    private function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../views/chief', 'chief');
        $this->loadViewsFrom(__DIR__ . '/../views/tailwind', 'tw');
    }

    private function configureEvents(): void
    {
        Event::listen(AuthEvents\Login::class, Listeners\Auth\Login::class);
        Event::listen(AuthEvents\Authenticated::class, Listeners\Auth\Authenticated::class);
        Event::listen(MailEvents\MessageSending::class, Listeners\Mail\PreventAutoResponders::class);
    }

    private function configureGraphQL(): void
    {
        $this->app->singleton(CreatesContext::class, ContextFactory::class);

        if (config('chief.graphql.subscriptions.enabled')) {
            Broadcast::channel('lighthouse-{id}-{time}', LighthouseSubscriptionChannel::class);

            Event::listen(LighthouseEvents\BuildExtensionsResponse::class, BuildExtensionsResponse::class);

            $this->app->bind(Pusher::class, static function (Application $app) {
                // Try the default driver first
                $driver = $app->make(BroadcastManager::class)->driver();

                if ($driver instanceof PusherBroadcaster) {
                    return $driver->getPusher();
                }

                // Try the pusher driver second
                $driver = $app->make(BroadcastManager::class)->driver('pusher');

                if ($driver instanceof PusherBroadcaster) {
                    return $driver->getPusher();
                }

                // Bail if we still can't find a Pusher broadcaster
                throw new RuntimeException('Cannot resolve Pusher client from non Pusher broadcaster.');
            });
        }
    }

    private function configurePassport(): void
    {
        // If the Passport package is not installed we can skip this
        if (!class_exists(Passport::class)) {
            return;
        }

        Passport::ignoreMigrations();

        if (!config('chief.auth')) {
            return;
        }

        Passport::useTokenModel(Entities\Passport\Token::class);

        if (!config('chief.auth.passport')) {
            return;
        }

        // Passport v10 uses the RouteRegistrar class to register routes so we modify it when available, for 11+ we manually override the routes
        if (class_exists(Passport10RouteRegistrar::class)) {
            Passport::routes(static function (Passport10RouteRegistrar $routes) {
                // Register no routes, they are not needed!
            }, config('chief.routes.passport', []));
        } else {
            Passport::ignoreRoutes();
        }

        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addDays(31));
        Passport::personalAccessTokensExpireIn(now()->addYears(20));
    }

    private function configureMiddleware(): void
    {
        $router = $this->app['router'];

        $router->model('team_hint', Chief::teamModel());
        $router->aliasMiddleware('team', Middleware\TeamUrlContext::class);
        $router->aliasMiddleware('auth.auto', Middleware\AutoAuthenticate::class);
        $router->aliasMiddleware('request.secure', Middleware\ForceSecure::class);
    }

    private function publishStaticFiles(): void
    {
        $this->publishes([
            static::basePath('config/chief.php') => config_path('chief.php'),
        ], 'chief-config');

        $this->publishes([
            static::basePath('database/migrations') => database_path('migrations'),
            ...(class_exists(Passport::class) && config('chief.auth.passport'))
                ? [static::basePath('database/passport-migrations') => database_path('migrations')]
                : [],
        ], 'chief-migrations');

        if (Chief::runsMigrations()) {
            $this->loadMigrationsFrom([
                static::basePath('database/migrations'),
                ...(class_exists(Passport::class) && config('chief.auth.passport'))
                    ? [static::basePath('database/passport-migrations')]
                    : [],
            ]);
        }
    }

    private function registerGraphQLSubscriptions(): void
    {
        if (!config('chief.graphql.subscriptions.enabled')) {
            return;
        }

        $this->app->booting(
            static function (Application $app) {
                $app->register(SubscriptionServiceProvider::class);

                $app->singleton(StoresSubscriptions::class, RedisStorageManager::class);
            },
        );
    }

    private function configureSocialiteIntegration(): void
    {
        if (!config('chief.auth')) {
            return;
        }

        if (!$this->app->bound(Socialite::class)) {
            return;
        }

        /** @var \Laravel\Socialite\SocialiteManager $socialite */
        /** @noinspection PhpUnhandledExceptionInspection */
        $socialite = $this->app->make(Socialite::class);

        $socialite->extend(
            'chief',
            static fn ($app) => $socialite->buildProvider(ChiefProvider::class, config('services.chief')),
        );
    }

    private function configureDeveloperProtections(): void
    {
        Model::preventLazyLoading();
        Model::preventAccessingMissingAttributes();

        // In production we just report the violations instead of crashing the application since it's mostly performance issue not always a security issue
        if (app()->isProduction()) {
            Model::handleLazyLoadingViolationUsing(
                Integration::lazyLoadingViolationReporter(),
            );

            Model::handleMissingAttributeViolationUsing(
                static fn (Model $model, string $key) => report(new MissingAttributeException($model, $key)),
            );
        }

        // Except for this one, we don't want to mass assign anything in production either
        Model::preventSilentlyDiscardingAttributes();
    }

    private function ensureMixAndAssetUrlsAreConfigured(): void
    {
        config([
            'app.mix_url'   => replace_custom_asset_domain($_ENV['MIX_URL'] ?? '/'),
            'app.asset_url' => replace_custom_asset_domain($_ENV['ASSET_URL'] ?? '/'),
        ]);
    }

    private static function basePath(string $path): string
    {
        return __DIR__ . '/../' . ltrim($path, '/');
    }
}
