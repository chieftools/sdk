<?php

namespace IronGate\Integration;

use ParagonIE\Certainty;
use Laravel\Passport\Passport;
use Laravel\Passport\RouteRegistrar;
use IronGate\Integration\Http\Middleware;
use IronGate\Integration\Console\Commands;
use Illuminate\Console\Scheduling\Schedule;
use IronGate\Integration\GraphQL\ContextFactory;
use IronGate\Integration\Socialite\ChiefProvider;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use IronGate\Integration\Entities\Passport as PassportEntities;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutes();

        $this->loadPassport();

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
    }

    private function loadPassport(): void
    {
        Passport::routes(function (RouteRegistrar $routes) {
            $routes->forAccessTokens();
            $routes->forAuthorization();
        });

        Passport::useTokenModel(PassportEntities\Token::class);

        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addDays(31));
        Passport::personalAccessTokensExpireIn(now()->addYears(20));
    }

    private function loadCommands(): void
    {
        $this->commands([
            Commands\RefreshApps::class,
            Commands\QueueHealthCheck::class,
        ]);

        if (!empty(config('queue.monitor'))) {
            $this->app->booted(function () {
                /** @var \Illuminate\Console\Scheduling\Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);

                $schedule->command(Commands\QueueHealthCheck::class)
                         ->appendOutputTo(storage_path('logs/schedule.log'))
                         ->runInBackground()
                         ->withoutOverlapping()
                         ->everyMinute();
            });
        }
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
