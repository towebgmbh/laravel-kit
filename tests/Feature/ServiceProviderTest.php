<?php

use Illuminate\Support\ServiceProvider;
use Towebgmbh\LaravelKit\KitServiceProvider;

it('merges the kit config defaults', function () {
    expect(config('kit.stack'))->toBe('blade')
        ->and(config('kit.architecture'))->toBe('actions')
        ->and(config('kit.docs.split_threshold'))->toBe(4)
        ->and(config('kit.hooks.docs_guard'))->toBeTrue();
});

it('enforces tests in Boost when the project has no own value', function () {
    expect(config('boost.enforce_tests'))->toBeTrue();
});

it('registers the kit-config publish tag', function () {
    $paths = ServiceProvider::pathsToPublish(KitServiceProvider::class, 'kit-config');

    expect($paths)->toHaveCount(1)
        ->and(array_values($paths)[0])->toBe(config_path('kit.php'));
});
