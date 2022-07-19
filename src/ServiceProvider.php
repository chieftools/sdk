<?php

namespace IronGate\Chief;

use GuzzleHttp;
use Pusher\Pusher;
use RuntimeException;
use ParagonIE\Certainty;
use IronGate\Chief\API\Client;
use Laravel\Passport\Passport;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use IronGate\Chief\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use IronGate\Chief\Console\Commands;
use Laravel\Passport\RouteRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Application;
use Illuminate\Auth\Events as AuthEvents;
use Illuminate\Mail\Events as MailEvents;
use Illuminate\Support\Facades\Broadcast;
use IronGate\Chief\GraphQL\ContextFactory;
use Illuminate\Console\Scheduling\Schedule;
use IronGate\Chief\Socialite\ChiefProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Nuwave\Lighthouse\Events as LighthouseEvents;
use Laravel\Socialite\Contracts\Factory as Socialite;
use IronGate\Chief\Auth\RemotePersonalAccessTokenGuard;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use IronGate\Chief\GraphQL\Listeners\BuildExtensionsResponse;
use Nuwave\Lighthouse\Subscriptions\SubscriptionServiceProvider;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use IronGate\Chief\Broadcasting\Channels\LighthouseSubscriptionChannel;

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
            Commands\QueueHealthCheck::class,
        ]);

        if (!empty(config('chief.queue.monitor'))) {
            $this->app->afterResolving(Schedule::class, static function (Schedule $schedule) {
                $schedule->command(Commands\QueueHealthCheck::class)
                         ->everyMinute()
                         ->onOneServer()
                         ->runInBackground();
            });
        }
    }

    private function registerAuth(): void
    {
        if (app()->configurationIsCached()) {
            return;
        }

        config([
            'auth.guards.ctp' => array_merge([
                'driver'   => 'chief_remote_pat',
                'provider' => null,
            ], config('auth.guards.ctp', [])),
        ]);
    }

    private function configureAuth(): void
    {
        Auth::resolved(function (AuthManager $auth) {
            $auth->extend('chief_remote_pat', function (Application $app, string $name, array $config) use ($auth) {
                $guard = new RequestGuard(
                    new RemotePersonalAccessTokenGuard($name, new Client, $app->make('cache')),
                    request(),
                    $auth->createUserProvider($config['provider'] ?? null)
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
        Passport::ignoreMigrations();

        Passport::useTokenModel(Entities\Passport\Token::class);

        if (!config('chief.auth.passport')) {
            return;
        }

        Passport::routes(static function (RouteRegistrar $routes) {
            $routes->forAccessTokens();
            $routes->forAuthorization();
        }, config('chief.routes.passport', []));

        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addDays(31));
        Passport::personalAccessTokensExpireIn(now()->addYears(20));
    }

    private function configureMiddleware(): void
    {
        $this->app->router->aliasMiddleware('auth.auto', Middleware\AutoAuthenticate::class);
        $this->app->router->aliasMiddleware('request.secure', Middleware\ForceSecure::class);
    }

    private function publishStaticFiles(): void
    {
        $this->publishes([
            static::basePath('config/chief.php') => config_path('chief.php'),
        ], 'chief-config');

        $this->publishes([
            static::basePath('database/migrations') => database_path('migrations'),
        ], 'chief-migrations');

        $this->loadMigrationsFrom(static::basePath('database/migrations'));
    }

    private function configureSocialiteIntegration(): void
    {
        /** @var \Laravel\Socialite\SocialiteManager $socialite */
        /** @noinspection PhpUnhandledExceptionInspection */
        $socialite = $this->app->make(Socialite::class);

        $socialite->extend(
            'chief',
            static fn ($app) => $socialite->buildProvider(ChiefProvider::class, config('services.chief'))
        );
    }

    private function registerGraphQLSubscriptions(): void
    {
        if (!config('chief.graphql.subscriptions.enabled')) {
            return;
        }

        $this->app->booting(
            fn () => $this->app->register(SubscriptionServiceProvider::class)
        );
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
