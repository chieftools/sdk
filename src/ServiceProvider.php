<?php

namespace ChiefTools\SDK;

use GuzzleHttp;
use ChiefTools\SDK\API\Client;
use Sentry\Laravel\Integration;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\DB;
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
use Nuwave\Lighthouse\Events as LighthouseEvents;
use ChiefTools\SDK\Auth\RemoteTeamAccessTokenGuard;
use ChiefTools\SDK\Auth\RemoteUserAccessTokenGuard;
use ChiefTools\SDK\Mail\ReportingFailoverTransport;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Database\Eloquent\MissingAttributeException;
use ChiefTools\SDK\GraphQL\Listeners\BuildExtensionsResponse;
use Nuwave\Lighthouse\Subscriptions\SubscriptionServiceProvider;
use Nuwave\Lighthouse\Subscriptions\Contracts\StoresSubscriptions;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use ChiefTools\SDK\GraphQL\Subscriptions\Storage\RedisStorageManager;
use ChiefTools\SDK\Broadcasting\Channels\LighthouseSubscriptionChannel;
use Nuwave\Lighthouse\Subscriptions\Storage\RedisStorageManager as LighthouseRedisStorageManager;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutes();

        $this->configureAuth();

        $this->configureMail();

        $this->configureViews();

        $this->configureEvents();

        $this->configureGraphQL();

        $this->configureMiddleware();

        $this->configureSocialiteIntegration();

        $this->configureDeveloperProtections();

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
        $this->mergeConfigFrom(static::basePath('config/session.php'), 'session', force: true);
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
                'driver' => 'chief_remote_user',
            ], config('auth.guards.chief', [])),

            'auth.guards.chief_team' => array_merge([
                'driver' => 'chief_remote_team',
            ], config('auth.guards.chief_team', [])),
        ]);
    }

    private function configureAuth(): void
    {
        if (!config('chief.auth')) {
            return;
        }

        Auth::resolved(function (AuthManager $auth) {
            $auth->extend('chief_remote_user', static function (Application $app, string $name) {
                $guard = new RequestGuard(
                    new RemoteUserAccessTokenGuard($name, $app->make(Client::class), $app->make('cache')),
                    request(),
                );

                $app->refresh('request', $guard, 'setRequest');

                return $guard;
            });

            $auth->extend('chief_remote_team', static function (Application $app, string $name) {
                $guard = new RequestGuard(
                    new RemoteTeamAccessTokenGuard($name, $app->make(Client::class), $app->make('cache')),
                    request(),
                );

                $app->refresh('request', $guard, 'setRequest');

                return $guard;
            });
        });
    }

    private function configureMail(): void
    {
        ReportingFailoverTransport::registerTransport();
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
        }
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
        ], 'chief-migrations');

        if (Chief::runsMigrations()) {
            $this->loadMigrationsFrom([
                static::basePath('database/migrations'),
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

                $app->extend(
                    StoresSubscriptions::class,
                    static function (StoresSubscriptions $storage) {
                        // Replace the Lighthouse storage manager with our own implementation
                        if ($storage instanceof LighthouseRedisStorageManager) {
                            return app(RedisStorageManager::class);
                        }

                        return $storage;
                    },
                );
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
        DB::prohibitDestructiveCommands(app()->isProduction());

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

    protected function mergeConfigFrom($path, $key, $force = false): void
    {
        if (!($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, $force ? array_merge(
                $config->get($key, []), require $path,
            ) : array_merge(
                require $path, $config->get($key, []),
            ));
        }
    }

    public static function basePath(string $path): string
    {
        return __DIR__ . '/../' . ltrim($path, '/');
    }
}
