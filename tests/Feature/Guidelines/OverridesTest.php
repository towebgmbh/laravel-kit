<?php

use Tests\Support\BoostFixture;
use Tests\Support\Contradictions;
use Tests\Support\Guidelines;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Overrides;

const KIT_TEST_LINE_MARKER = 'Follow the Laravel Kit test line:';
const KIT_PEST_RUN_MARKER = 'at most once at the end of a run or task';

beforeEach(function () {
    $this->fixture = BoostFixture::create();
});

afterEach(function () {
    $this->fixture->destroy();
});

it('replaces enforce-tests and pest/core with the kit overrides', function () {
    $this->fixture->withOverrides();

    $guidelines = Guidelines::compose($this->fixture);

    expect($guidelines['tests']['path'])->toBe(realpath($this->fixture->path('.ai/guidelines/enforce-tests.blade.php')))
        ->and($guidelines['tests']['content'])->toContain(KIT_TEST_LINE_MARKER)
        ->and($guidelines['pest/core']['path'])->toBe(realpath($this->fixture->path('.ai/guidelines/pest/core.blade.php')))
        ->and($guidelines['pest/core']['content'])->toContain(KIT_PEST_RUN_MARKER);
});

it('renders the overrides once, not as extra user guidelines', function () {
    $this->fixture->withOverrides();

    $text = Guidelines::text(Guidelines::compose($this->fixture));

    expect(substr_count($text, KIT_TEST_LINE_MARKER))->toBe(1)
        ->and(substr_count($text, KIT_PEST_RUN_MARKER))->toBe(1);
});

it('leaves Boost texts in place without the stubs', function () {
    $text = Guidelines::text(Guidelines::compose($this->fixture));

    expect($text)->not->toContain(KIT_TEST_LINE_MARKER)
        ->not->toContain(KIT_PEST_RUN_MARKER);
});

it('renders no contradiction with the stubs in place', function (string $mode) {
    config(['kit.architecture' => $mode]);
    $this->fixture->withOverrides();

    $text = Guidelines::text(Guidelines::compose($this->fixture));

    foreach (Contradictions::SENTENCES as $sentence) {
        expect($text)->not->toContain($sentence);
    }
})->with(['actions', 'services', 'migrating']);

it('lists both overrides with their Boost files and stubs', function () {
    $overrides = Overrides::all();

    expect(array_keys($overrides))->toBe(['.ai/guidelines/enforce-tests.blade.php', '.ai/guidelines/pest/core.blade.php'])
        ->and($overrides['.ai/guidelines/enforce-tests.blade.php']['requires'])->toBeNull()
        ->and($overrides['.ai/guidelines/pest/core.blade.php']['requires'])->toBe('pestphp/pest');

    foreach ($overrides as $path => $override) {
        expect(Kit::path($override['stub']))->toBeFile()
            ->and(Overrides::stub($path))->not->toBeEmpty();
    }
});

it('rejects an unknown override', function () {
    Overrides::stub('.ai/guidelines/foundation.blade.php');
})->throws(InvalidArgumentException::class);

it('matches the reference hashes of the installed Boost files', function () {
    if (! Overrides::installedBoostIsReference()) {
        $this->markTestSkipped(sprintf(
            'Installed Boost %s is not the reference version %s; the weekly drift run checks the hashes.',
            Overrides::installedBoostVersion() ?? 'unknown',
            Overrides::BOOST_VERSION,
        ));
    }

    foreach (Overrides::all() as $override) {
        expect(Overrides::installedBoostHash($override['boost_file']))->toBe($override['boost_sha256']);
    }
});

it('compares the installed Boost version with the reference', function () {
    $installed = Overrides::installedBoostVersion();

    expect($installed)->not->toBeNull()
        ->and(Overrides::installedBoostIsOlderThanReference())->toBe(version_compare($installed, Overrides::BOOST_VERSION, '<'))
        ->and(Overrides::installedBoostHash('.ai/missing.blade.php'))->toBeNull();
});
