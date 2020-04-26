<?php

namespace IronGate\Chief;

use ParagonIE\Certainty;
use Laravel\Passport\Passport;
use IronGate\Chief\Http\Middleware;
use IronGate\Chief\Console\Commands;
use Laravel\Passport\RouteRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events as AuthEvents;
use Illuminate\Support\Facades\Broadcast;
use IronGate\Chief\GraphQL\ContextFactory;
use Illuminate\Console\Scheduling\Schedule;
use IronGate\Chief\Socialite\ChiefProvider;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use IronGate\Chief\Broadcasting\Channels\LighthouseSubscriptionChannel;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutes();

        $this->loadEvents();

        $this->loadPassport();

        $this->loadBroadcast();

        $this->loadViewsFrom(static::basePath('views'), 'chief');

        $this->loadMiddleware();

        $this->loadSocialiteIntegration();

        $this->app->singleton(CreatesContext::class, ContextFactory::class);

        if ($this->app->runningInConsole()) {
            $this->publishStaticFiles();

            $this->loadCommands();
        }
    }

    public function register(): void
    {
        Passport::ignoreMigrations();

        $this->mergeConfigFrom(static::basePath('config/chief.php'), 'chief');
        $this->mergeConfigFrom(static::basePath('config/sentry.php'), 'sentry');
        $this->mergeConfigFrom(static::basePath('config/former.php'), 'former');
        $this->mergeConfigFrom(static::basePath('config/lighthouse.php'), 'lighthouse');

        $this->app->singleton(Certainty\RemoteFetch::class, function () {
            $fetch = new Certainty\RemoteFetch(storage_path('framework/cache'));

            return $fetch->setChronicle(config('chief.chronicle.url'), config('chief.chronicle.pubkey'));
        });
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

    private function loadEvents(): void
    {
        Event::listen(AuthEvents\Login::class, Listeners\Auth\Login::class);
    }

    private function loadPassport(): void
    {
        Passport::routes(function (RouteRegistrar $routes) {
            $routes->forAccessTokens();
            $routes->forAuthorization();
        }, config('chief.routes.passport', []));

        Passport::useTokenModel(Entities\Passport\Token::class);

        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addDays(31));
        Passport::personalAccessTokensExpireIn(now()->addYears(20));
    }

    private function loadCommands(): void
    {
        $this->commands([
            Commands\QueueHealthCheck::class,
        ]);

        if (!empty(config('chief.queue.monitor'))) {
            $this->app->booted(function () {
                /** @var \Illuminate\Console\Scheduling\Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);

                $schedule->command(Commands\QueueHealthCheck::class)
                         ->runInBackground()
                         ->withoutOverlapping()
                         ->everyMinute();
            });
        }
    }

    private function loadBroadcast(): void
    {
        if (!config('chief.graphql.subscriptions.enabled')) {
            return;
        }

        Broadcast::channel('lighthouse-{id}-{time}', LighthouseSubscriptionChannel::class);
    }

    private function loadMiddleware(): void
    {
        $this->app->router->aliasMiddleware('auth.auto', Middleware\AutoAuthenticate::class);
        $this->app->router->aliasMiddleware('request.secure', Middleware\ForceSecure::class);
        $this->app->router->aliasMiddleware('sentry.context', Middleware\SentryContext::class);
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

    private function loadSocialiteIntegration(): void
    {
        if (!$this->app['config']['services.chief']) {
            return;
        }

        /** @var \Laravel\Socialite\SocialiteManager $socialite */
        $socialite = $this->app->make(Socialite::class);

        $socialite->extend('chief', function ($app) use ($socialite) {
            return $socialite->buildProvider(
                ChiefProvider::class,
                $app['config']['services.chief']
            );
        });
    }

    public static function basePath(string $path): string
    {
        return __DIR__ . '/../' . ltrim($path, '/');
    }
}
