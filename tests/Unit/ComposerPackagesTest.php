<?php

use Towebgmbh\LaravelKit\Support\ComposerPackages;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/kit-composer-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
});

afterEach(function () {
    @unlink($this->dir.'/composer.json');
    rmdir($this->dir);
});

it('finds packages in require and require-dev', function () {
    file_put_contents($this->dir.'/composer.json', json_encode([
        'require' => ['laravel/framework' => '^13.0'],
        'require-dev' => ['pestphp/pest' => '^4.0'],
    ]));

    expect(ComposerPackages::has($this->dir, 'laravel/framework'))->toBeTrue()
        ->and(ComposerPackages::has($this->dir, 'pestphp/pest'))->toBeTrue()
        ->and(ComposerPackages::has($this->dir, 'phpunit/phpunit'))->toBeFalse();
});

it('returns false for a missing or invalid composer.json', function () {
    expect(ComposerPackages::has($this->dir, 'pestphp/pest'))->toBeFalse();

    file_put_contents($this->dir.'/composer.json', '{not json');

    expect(ComposerPackages::has($this->dir, 'pestphp/pest'))->toBeFalse();
});
