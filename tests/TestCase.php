<?php

namespace Tests;

use ChiefTools\SDK\ServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SocialiteServiceProvider::class,
            ServiceProvider::class,
        ];
    }
}
