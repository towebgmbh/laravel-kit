<?php

namespace Towebgmbh\LaravelKit;

use Illuminate\Support\ServiceProvider;

class KitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(Kit::path('config/kit.php'), 'kit');

        // Boost then always renders `enforce-tests`, which carries the kit test line.
        if ($this->app['config']->get('boost.enforce_tests') === null) {
            $this->app['config']->set('boost.enforce_tests', true);
        }
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            Kit::path('config/kit.php') => config_path('kit.php'),
        ], 'kit-config');

        $this->commands([
            // Filled by later packages.
        ]);
    }
}
