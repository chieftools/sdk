<?php

namespace IronGate\Integration;

use Laravel\Socialite\Facades\Socialite;
use IronGate\Integration\Socialite\ChiefProvider;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
        Socialite::extend('chief', function () {
            return Socialite::buildProvider(ChiefProvider::class, config('services.chief'));
        });
    }

    /**
     * Register any auth services.
     */
    public function register()
    {
        //
    }
}
