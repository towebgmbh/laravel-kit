<?php

use Tests\Support\BoostFixture;
use Tests\Support\Guidelines;
use Towebgmbh\LaravelKit\Kit;

beforeEach(function () {
    $this->fixture = BoostFixture::create()->withOverrides();
});

afterEach(function () {
    $this->fixture->destroy();
});

it('keeps the kit guidelines within 200 lines', function (string $stack, string $mode, bool $split) {
    config(['kit.stack' => $stack, 'kit.architecture' => $mode]);

    if ($split) {
        $this->fixture->put('docs/README.md', "# Docs\n");
    }

    $lines = Guidelines::lineCount(Guidelines::text(Guidelines::kitOnly(Guidelines::compose($this->fixture))));

    expect($lines)->toBeLessThanOrEqual(200, sprintf('stack %s, mode %s, %s docs: %d lines', $stack, $mode, $split ? 'split' : 'flat', $lines));
})->with(Kit::STACKS)->with(Kit::ARCHITECTURES)->with(['flat' => false, 'split' => true]);
