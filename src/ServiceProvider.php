<?php

namespace IronGate\Integration;

use Laravel\Socialite\Contracts\Factory;
use IronGate\Integration\Console\Commands;
use Illuminate\Console\Scheduling\Schedule;
use IronGate\Integration\Socialite\ChiefProvider;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $socialite = $this->app->make(Factory::class);
        $socialite->extend('chief', function ($app) use ($socialite) {
            return $socialite->buildProvider(ChiefProvider::class, $app['config']['services.chief']);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/chief.php' => config_path('chief.php'),
            ], 'chief-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'chief-migrations');

            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

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
        $this->mergeConfigFrom(__DIR__ . '/../config/chief.php', 'chief');
    }
}
