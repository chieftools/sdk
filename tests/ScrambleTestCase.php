<?php

namespace Tests;

use Dedoc\Scramble\ScrambleServiceProvider;

abstract class ScrambleTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ScrambleServiceProvider::class,
        ];
    }
}
