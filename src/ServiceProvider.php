<?php

namespace IronGate\Integration;

use ParagonIE\Certainty;
use Laravel\Socialite\Contracts\Factory;
use IronGate\Integration\Console\Commands;
use Illuminate\Console\Scheduling\Schedule;
use IronGate\Integration\Socialite\ChiefProvider;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(static::basePath('routes/web.php'));
        $this->loadViewsFrom(static::basePath('views'), 'chief');

        $socialite = $this->app->make(Factory::class);
        $socialite->extend('chief', function ($app) use ($socialite) {
            return $socialite->buildProvider(ChiefProvider::class, $app['config']['services.chief']);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                static::basePath('config/chief.php') => config_path('chief.php'),
            ], 'chief-config');

            $this->publishes([
                static::basePath('database/migrations') => database_path('migrations'),
            ], 'chief-migrations');

            $this->loadMigrationsFrom(static::basePath('database/migrations'));

            $this->commands([
                Commands\RefreshApps::class,
                Commands\QueueHealthCheck::class,
            ]);

            $this->app->booted(function () {
                /** @var \Illuminate\Console\Scheduling\Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);

                if (!empty(config('queue.monitor'))) {
                    $schedule->command(Commands\QueueHealthCheck::class)
                             ->appendOutputTo(storage_path('logs/schedule.log'))
                             ->runInBackground()
                             ->withoutOverlapping()
                             ->everyMinute();
                }
            });
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(static::basePath('config/chief.php'), 'chief');

        $this->app->singleton(Certainty\RemoteFetch::class, function () {
            $fetch = new Certainty\RemoteFetch(storage_path('framework/cache'));

            return $fetch->setChronicle(config('chief.chronicle.url'), config('chief.chronicle.pubkey'));
        });
    }

    /**
     * Base path helper for the package.
     *
     * @param string $path
     *
     * @return string
     */
    public static function basePath(string $path): string
    {
        return __DIR__ . '/../' . ltrim($path, '/');
    }
}
