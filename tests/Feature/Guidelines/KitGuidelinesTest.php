<?php

use Tests\Support\BoostFixture;
use Tests\Support\Contradictions;
use Tests\Support\Guidelines;

const KIT_ARCHITECTURE_MARKERS = [
    'actions' => 'Business logic lives in Actions.',
    'services' => 'This project keeps its Service classes.',
    'migrating' => 'New code goes into Actions; existing Service classes are converted when you change them anyway.',
];

beforeEach(function () {
    $this->fixture = BoostFixture::create()->withOverrides();
});

afterEach(function () {
    $this->fixture->destroy();
});

it('renders the kit guidelines with the marker of the active architecture mode only', function (string $mode) {
    config(['kit.architecture' => $mode]);

    $guidelines = Guidelines::compose($this->fixture);
    $text = Guidelines::text($guidelines);

    expect($guidelines->keys()->all())->toContain(
        'towebgmbh/laravel-kit/core',
        'towebgmbh/laravel-kit/architecture',
        'towebgmbh/laravel-kit/conventions',
        'towebgmbh/laravel-kit/quality',
    )->and($text)->toContain(KIT_ARCHITECTURE_MARKERS[$mode]);

    foreach (KIT_ARCHITECTURE_MARKERS as $other => $marker) {
        if ($other !== $mode) {
            expect($text)->not->toContain($marker);
        }
    }

    foreach (Contradictions::SENTENCES as $sentence) {
        expect($text)->not->toContain($sentence);
    }
})->with(['actions', 'services', 'migrating']);

it('renders the size rule and the record-rule fallback in core', function () {
    $core = Guidelines::compose($this->fixture)['towebgmbh/laravel-kit/core']['content'];

    expect($core)
        ->toContain('Classify every piece of work as small, medium or large, state the size in one sentence and start without asking; the user may reclassify it at any time.')
        ->toContain('**Small**')
        ->toContain('**Medium**')
        ->toContain('**Large**')
        ->toContain('Do not implement while the spec lists a blocking open question')
        ->toContain('never the complete suite per phase')
        ->toContain('Keeping `docs/` up to date is a standing request of the user, so the rule to create documentation only on request does not apply to `docs/`.')
        ->toContain('The domain folders listed in the project docs are approved base folders.')
        ->toContain('`record-rule` MCP tool. Without it, write the rule file in `.ai/rules` by hand');
});

it('renders the docs layout the project uses', function (bool $split, string $expected, string $absent) {
    if ($split) {
        $this->fixture->put('docs/README.md', "# Docs\n");
    }

    $core = Guidelines::compose($this->fixture)['towebgmbh/laravel-kit/core']['content'];

    expect($core)->toContain($expected)->not->toContain($absent);
})->with([
    'flat' => [false, 'Read `docs/GUIDELINES.md` first.', 'Read `docs/README.md` first, then only the affected domains.'],
    'split' => [true, 'Read `docs/README.md` first, then only the affected domains.', 'Read `docs/GUIDELINES.md` first.'],
]);

it('takes the split threshold from the config', function () {
    config(['kit.docs.split_threshold' => 6]);

    expect(Guidelines::compose($this->fixture)['towebgmbh/laravel-kit/core']['content'])
        ->toContain('When the project reaches 6 domains, run `/kit-docs-split`.');
});

it('keeps core, architecture, conventions and quality within 150 lines', function (string $mode, bool $split) {
    config(['kit.architecture' => $mode]);

    if ($split) {
        $this->fixture->put('docs/README.md', "# Docs\n");
    }

    $lines = Guidelines::lineCount(Guidelines::text(Guidelines::kitOnly(Guidelines::compose($this->fixture))));

    expect($lines)->toBeLessThanOrEqual(150);
})->with(['actions', 'services', 'migrating'])->with(['flat' => false, 'split' => true]);

it('renders the model rules of the installed framework version', function () {
    $architecture = Guidelines::compose($this->fixture)['towebgmbh/laravel-kit/architecture']['content'];

    if (version_compare(app()->version(), '13.0', '>=')) {
        expect($architecture)->toContain('#[Fillable]')->not->toContain('$fillable');
    } else {
        expect($architecture)->toContain('$fillable')->not->toContain('#[Fillable]');
    }
});

it('matches the snapshot of the kit guidelines', function (string $mode) {
    if (version_compare(app()->version(), '13.0', '<')) {
        $this->markTestSkipped('Snapshots are recorded on Laravel 13 only.');
    }

    config(['kit.architecture' => $mode]);

    expect(Guidelines::text(Guidelines::kitOnly(Guidelines::compose($this->fixture))))->toMatchSnapshot();
})->with(['actions', 'services', 'migrating']);
