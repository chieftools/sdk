<?php

namespace IronGate\Chief;

use ParagonIE\Certainty;
use Laravel\Passport\Passport;
use Illuminate\Mail\MailManager;
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
use Nuwave\Lighthouse\Subscriptions\SubscriptionServiceProvider;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use IronGate\Chief\Support\Mail\PreventAutoRespondersTransportPlugin;
use IronGate\Chief\Broadcasting\Channels\LighthouseSubscriptionChannel;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutes();

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
        Passport::ignoreMigrations();

        $this->mergeConfigFrom(static::basePath('config/chief.php'), 'chief');
        $this->mergeConfigFrom(static::basePath('config/sentry.php'), 'sentry');
        $this->mergeConfigFrom(static::basePath('config/javascript.php'), 'javascript');
        $this->mergeConfigFrom(static::basePath('config/lighthouse.php'), 'lighthouse');

        $this->registerGraphQLSubscriptions();

        $this->app->extend('mail.manager', function (MailManager $manager) {
            $manager->getSwiftMailer()->registerPlugin(new PreventAutoRespondersTransportPlugin);

            return $manager;
        });

        $this->app->singleton(Certainty\RemoteFetch::class, static function () {
            return (new Certainty\RemoteFetch(storage_path('framework/cache')))
                ->setChronicle(config('chief.chronicle.url'), config('chief.chronicle.pubkey'));
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

    private function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../views/chief', 'chief');
        $this->loadViewsFrom(__DIR__ . '/../views/tailwind', 'tw');
    }

    private function configureEvents(): void
    {
        Event::listen(AuthEvents\Login::class, Listeners\Auth\Login::class);
        Event::listen(AuthEvents\Authenticated::class, Listeners\Auth\Authenticated::class);
    }

    private function configurePassport(): void
    {
        Passport::routes(static function (RouteRegistrar $routes) {
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

    private function configureGraphQL(): void
    {
        $this->app->singleton(CreatesContext::class, ContextFactory::class);

        if (config('chief.graphql.subscriptions.enabled')) {
            Broadcast::channel('lighthouse-{id}-{time}', LighthouseSubscriptionChannel::class);
        }
    }

    private function configureSocialiteIntegration(): void
    {
        if (!$this->app['config']['services.chief']) {
            return;
        }

        /** @var \Laravel\Socialite\SocialiteManager $socialite */
        $socialite = $this->app->make(Socialite::class);

        $socialite->extend('chief', static function ($app) use ($socialite) {
            return $socialite->buildProvider(
                ChiefProvider::class,
                $app['config']['services.chief']
            );
        });
    }

    private function registerGraphQLSubscriptions(): void
    {
        if (!config('chief.graphql.subscriptions.enabled')) {
            return;
        }

        $this->app->booting(function () {
            $this->app->register(SubscriptionServiceProvider::class);
        });
    }

    private function ensureMixAndAssetUrlsAreConfigured(): void
    {
        config([
            'app.mix_url'   => replace_custom_asset_domain($_ENV['MIX_URL'] ?? '/'),
            'app.asset_url' => replace_custom_asset_domain($_ENV['ASSET_URL'] ?? '/'),
        ]);
    }

    public static function basePath(string $path): string
    {
        return __DIR__ . '/../' . ltrim($path, '/');
    }
}
