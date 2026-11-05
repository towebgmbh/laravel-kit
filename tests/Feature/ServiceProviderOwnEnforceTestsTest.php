<?php

namespace Tests\Feature;

use Tests\TestCase;
use Towebgmbh\LaravelKit\KitServiceProvider;

class ServiceProviderOwnEnforceTestsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('boost.enforce_tests', false);
    }

    public function test_a_project_value_of_false_stays_false(): void
    {
        $this->assertFalse(config('boost.enforce_tests'));

        $this->app->register(KitServiceProvider::class, force: true);

        $this->assertFalse(config('boost.enforce_tests'));
    }
}
