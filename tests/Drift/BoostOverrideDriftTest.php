<?php

use Towebgmbh\LaravelKit\Overrides;

beforeEach(function () {
    if (getenv('KIT_BOOST_DRIFT') !== '1') {
        $this->markTestSkipped('Set KIT_BOOST_DRIFT=1 to compare the overrides with the installed Boost.');
    }
});

it('finds the Boost files the overrides replace unchanged', function (string $path, array $override) {
    $installed = Overrides::installedBoostHash($override['boost_file']);

    expect($installed)->toBe(
        $override['boost_sha256'],
        "Boost {$override['boost_file']} changed in Boost ".Overrides::installedBoostVersion()
        .". Update the stub {$override['stub']} to the new Boost text, then set the reference hash of [{$path}] "
        .'in Overrides::all() to '.($installed ?? 'the new hash').' and Overrides::BOOST_VERSION to the new version.',
    );
})->with(fn () => collect(Overrides::all())->map(fn ($o, $p) => [$p, $o])->values()->all());
