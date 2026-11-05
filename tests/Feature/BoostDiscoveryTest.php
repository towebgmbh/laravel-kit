<?php

use Tests\Support\BoostFixture;
use Tests\Support\Guidelines;
use Towebgmbh\LaravelKit\Kit;

beforeEach(function () {
    $this->fixture = BoostFixture::create();
});

afterEach(function () {
    $this->fixture->destroy();
});

it('registers Boost in the test application', function () {
    expect(Artisan::all())->toHaveKey('boost:update');
});

it('discovers the kit guidelines as a third-party package', function () {
    $guidelines = Guidelines::compose($this->fixture);

    expect($guidelines)->toHaveKey('towebgmbh/laravel-kit/core')
        ->and(Guidelines::kitOnly($guidelines)->keys()->all())->toContain('towebgmbh/laravel-kit/core')
        ->and(Guidelines::text($guidelines))->toContain('=== towebgmbh/laravel-kit/core rules ===');
});

it('leaves the kit out when it is not selected in boost.json', function () {
    $guidelines = Guidelines::compose($this->fixture, packages: []);

    expect($guidelines)->not->toHaveKey('towebgmbh/laravel-kit/core')
        ->and(Guidelines::kitOnly($guidelines))->toBeEmpty();
});

it('renders the test guideline only when tests are enforced', function () {
    expect(Guidelines::compose($this->fixture))->toHaveKey('tests')
        ->and(Guidelines::compose($this->fixture, enforceTests: false))->not->toHaveKey('tests');
});

it('counts lines', function () {
    expect(Guidelines::lineCount(''))->toBe(0)
        ->and(Guidelines::lineCount("a\nb\n"))->toBe(2);
});

it('leaves the package root intact when destroyed', function () {
    $fixture = BoostFixture::create();
    $link = $fixture->path('vendor/towebgmbh/laravel-kit');

    expect(is_link($link))->toBeTrue()
        ->and(realpath($link))->toBe(realpath(Kit::path()));

    $fixture->destroy();

    expect(file_exists($fixture->path()))->toBeFalse()
        ->and(Kit::path('composer.json'))->toBeFile()
        ->and(Kit::path('resources/boost/guidelines/core.blade.php'))->toBeFile();
});
