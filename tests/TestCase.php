<?php

namespace Tests;

use ChiefTools\SDK\ServiceProvider;
use Tests\Fixtures\Webhooks\ExampleEventType;
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

    protected function defineEnvironment($app): void
    {
        $app['config']->set('chief.webhooks.enabled', true);
        $app['config']->set('chief.webhooks.events', ExampleEventType::class);
    }
}
