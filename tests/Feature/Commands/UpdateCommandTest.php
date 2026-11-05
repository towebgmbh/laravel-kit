<?php

use Tests\Support\BoostFixture;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;

function kitUpdateFixture(bool $pest = true): BoostFixture
{
    $fixture = BoostFixture::create(
        boostJson: ['agents' => ['claude_code'], 'guidelines' => true, 'skills' => ['laravel-best-practices']],
        pest: $pest,
    );
    $fixture->activate(app());
    chdir($fixture->path());

    test()->artisan('kit:install', ['--stack' => 'livewire', '--new' => true, '--no-interaction' => true])
        ->assertSuccessful();

    return $fixture;
}

function kitUpdateManifest(BoostFixture $fixture): array
{
    return json_decode((string) file_get_contents($fixture->path(Manifest::PATH)), true);
}

afterEach(function () {
    chdir(Kit::path());

    if (isset($this->fixture)) {
        $this->fixture->destroy();
    }
});

const KIT_ENFORCE = '.ai/guidelines/enforce-tests.blade.php';

it('fails without a manifest', function () {
    $this->fixture = BoostFixture::create(boostJson: ['agents' => ['claude_code'], 'guidelines' => true]);
    $this->fixture->activate(app());
    chdir($this->fixture->path());

    $this->artisan('kit:update')
        ->expectsOutputToContain('The kit is not installed. Run php artisan kit:install.')
        ->assertFailed();
});

it('replaces an unchanged override whose kit version changed and runs boost:update', function () {
    $this->fixture = kitUpdateFixture();
    $f = $this->fixture;

    // An older kit version wrote different contents; file and manifest agree.
    $old = "old kit version\n";
    file_put_contents($f->path(KIT_ENFORCE), $old);
    $manifest = kitUpdateManifest($f);
    $manifest['files'][KIT_ENFORCE]['sha256'] = Manifest::hash($old);
    file_put_contents($f->path(Manifest::PATH), json_encode($manifest));
    unlink($f->path('CLAUDE.md'));
    touch($f->path('CLAUDE.md'));

    $this->artisan('kit:update', ['--no-interaction' => true])->assertSuccessful();

    expect(file_get_contents($f->path(KIT_ENFORCE)))->not->toBe($old)
        ->and(file_get_contents($f->path('CLAUDE.md')))->toContain('laravel-boost-guidelines')
        ->and(kitUpdateManifest($f)['files'][KIT_ENFORCE]['sha256'])->toBe(Manifest::hash(file_get_contents($f->path(KIT_ENFORCE))));
});

it('shows the diff and keeps an edited override without interaction, and replaces it with --force', function () {
    $this->fixture = kitUpdateFixture();
    $f = $this->fixture;
    $kit = file_get_contents($f->path(KIT_ENFORCE));
    file_put_contents($f->path(KIT_ENFORCE), "local edit\n");

    $this->artisan('kit:update', ['--no-interaction' => true])
        ->expectsOutputToContain('+++ '.KIT_ENFORCE.' (kit)')
        ->expectsOutputToContain('Skipped ['.KIT_ENFORCE.']: local changes kept.')
        ->assertSuccessful();

    expect(file_get_contents($f->path(KIT_ENFORCE)))->toBe("local edit\n");

    $this->artisan('kit:update', ['--force' => true, '--no-interaction' => true])->assertSuccessful();

    expect(file_get_contents($f->path(KIT_ENFORCE)))->toBe($kit);
});

it('asks before replacing an edited override interactively', function () {
    $this->fixture = kitUpdateFixture();
    $f = $this->fixture;
    $kit = file_get_contents($f->path(KIT_ENFORCE));
    file_put_contents($f->path(KIT_ENFORCE), "local edit\n");
    app()['env'] = 'testing';

    $this->artisan('kit:update')
        ->expectsConfirmation('Replace ['.KIT_ENFORCE.'] with the kit version?', 'yes')
        ->assertSuccessful();

    expect(file_get_contents($f->path(KIT_ENFORCE)))->toBe($kit);
});

it('adds pest/core when Pest appears and removes it when Pest is gone', function () {
    $this->fixture = kitUpdateFixture(pest: false);
    $f = $this->fixture;
    $pestCore = '.ai/guidelines/pest/core.blade.php';

    expect($f->path($pestCore))->not->toBeFile();

    $composer = kitJson($f, 'composer.json');
    $composer['require-dev']['pestphp/pest'] = '^4.0';
    $f->putJson('composer.json', $composer);

    $this->artisan('kit:update', ['--no-interaction' => true])->assertSuccessful();

    expect($f->path($pestCore))->toBeFile()
        ->and(kitUpdateManifest($f)['overrides'])->toHaveKey($pestCore)
        ->and(kitUpdateManifest($f)['files'])->toHaveKey($pestCore);

    unset($composer['require-dev']['pestphp/pest']);
    $f->putJson('composer.json', $composer);

    $this->artisan('kit:update', ['--no-interaction' => true])->assertSuccessful();

    expect($f->path($pestCore))->not->toBeFile()
        ->and(kitUpdateManifest($f)['overrides'])->not->toHaveKey($pestCore)
        ->and(kitUpdateManifest($f)['files'])->not->toHaveKey($pestCore);
});

it('keeps an edited pest/core when Pest is gone', function () {
    $this->fixture = kitUpdateFixture();
    $f = $this->fixture;
    $pestCore = '.ai/guidelines/pest/core.blade.php';
    file_put_contents($f->path($pestCore), "mine\n");
    $composer = kitJson($f, 'composer.json');
    unset($composer['require-dev']['pestphp/pest'], $composer['require']['pestphp/pest']);
    $f->putJson('composer.json', $composer);

    $this->artisan('kit:update', ['--no-interaction' => true])
        ->expectsOutputToContain('is kept because it has local changes')
        ->assertSuccessful();

    expect(file_get_contents($f->path($pestCore)))->toBe("mine\n");
});
