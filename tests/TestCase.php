<?php

namespace Tests;

use Laravel\Boost\BoostServiceProvider;
use Laravel\Roster\RosterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Towebgmbh\LaravelKit\KitServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return array_values(array_filter([
            BoostServiceProvider::class,
            class_exists(RosterServiceProvider::class) ? RosterServiceProvider::class : null,
            KitServiceProvider::class,
        ]));
    }

    /**
     * Boost skips its registration when the app runs unit tests
     * (BoostServiceProvider::shouldRun()). Setting the environment to
     * `local` before the providers register is enough: Boost then binds
     * its singletons and registers `boost:install` and `boost:update`.
     */
    protected function defineEnvironment($app): void
    {
        $app['env'] = 'local';

        $app['config']->set('boost.browser_logs_watcher', false);
        $app['config']->set('cache.default', 'array');
    }
}
