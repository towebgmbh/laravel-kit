<?php

use Tests\Support\BoostFixture;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;
use Towebgmbh\LaravelKit\Overrides;

function kitDoctorFixture(bool $pest = true): BoostFixture
{
    $fixture = BoostFixture::create(
        boostJson: ['agents' => ['claude_code'], 'guidelines' => true, 'skills' => ['laravel-best-practices']],
        pest: $pest,
    );
    $fixture->activate(app());
    chdir($fixture->path());

    test()->artisan('kit:install', ['--stack' => 'livewire', '--new' => true, '--no-interaction' => true])
        ->assertSuccessful();

    // Reference the installed Boost, whatever its version.
    $manifest = json_decode((string) file_get_contents($fixture->path(Manifest::PATH)), true);

    foreach ($manifest['overrides'] as $path => $override) {
        $manifest['overrides'][$path]['boost_sha256'] = Overrides::installedBoostHash($override['boost_file']);
    }

    file_put_contents($fixture->path(Manifest::PATH), json_encode($manifest));

    return $fixture;
}

afterEach(function () {
    chdir(Kit::path());

    if (isset($this->fixture)) {
        $this->fixture->destroy();
    }
});

it('reports a healthy installation without failures', function () {
    $this->fixture = kitDoctorFixture();

    $command = $this->artisan('kit:doctor')
        ->expectsOutputToContain('[OK] towebgmbh/laravel-kit is active in boost.json')
        ->expectsOutputToContain('[OK] No v1 leftovers');

    if (Overrides::installedBoostIsOlderThanReference()) {
        $command->expectsOutputToContain('[WARN] Laravel Boost');
    } else {
        $command->expectsOutputToContain('[OK] Override .ai/guidelines/enforce-tests.blade.php matches Boost');
    }

    $command->assertSuccessful();
});

it('reports a changed override hash as drift', function () {
    $this->fixture = kitDoctorFixture();
    $manifest = json_decode((string) file_get_contents($this->fixture->path(Manifest::PATH)), true);
    $manifest['overrides']['.ai/guidelines/enforce-tests.blade.php']['boost_sha256'] = str_repeat('0', 64);
    file_put_contents($this->fixture->path(Manifest::PATH), json_encode($manifest));

    $command = $this->artisan('kit:doctor');

    if (Overrides::installedBoostIsOlderThanReference()) {
        $command->expectsOutputToContain('is older than '.Overrides::BOOST_VERSION)->assertSuccessful();
    } else {
        $command->expectsOutputToContain('[FAIL] Override drift')->assertFailed();
    }
});

it('warns about v1 leftovers', function () {
    $this->fixture = kitDoctorFixture();
    $this->fixture->put('.claude/rules/01-architecture.md', '# rule');

    $this->artisan('kit:doctor')
        ->expectsOutputToContain('[WARN] Leftover v1 files: .claude/rules/01-architecture.md. Run php artisan kit:upgrade.')
        ->assertSuccessful();
});

it('warns about a pest override without Pest', function () {
    $this->fixture = kitDoctorFixture(pest: false);
    $this->fixture->put('.ai/guidelines/pest/core.blade.php', 'x');

    $this->artisan('kit:doctor')
        ->expectsOutputToContain('[WARN] .ai/guidelines/pest/core.blade.php exists but Pest is not installed. Run php artisan kit:update.')
        ->assertSuccessful();
});

it('warns when flat docs reach the split threshold', function () {
    $this->fixture = kitDoctorFixture();

    foreach (['Billing', 'Orders', 'Catalog', 'Shipping'] as $domain) {
        $this->fixture->put("app/{$domain}/Actions/.gitkeep", '');
    }

    $this->artisan('kit:doctor')
        ->expectsOutputToContain('[WARN] docs/ is flat with 4 domains (threshold 4). Run /kit-docs-split.')
        ->assertSuccessful();
});

it('fails when the package is not in boost.json', function () {
    $this->fixture = kitDoctorFixture();
    $this->fixture->putJson('boost.json', ['agents' => ['claude_code'], 'guidelines' => true, 'packages' => []]);

    $this->artisan('kit:doctor')
        ->expectsOutputToContain('[FAIL] towebgmbh/laravel-kit is not active in boost.json. Run php artisan kit:install.')
        ->assertFailed();
});

it('fails without a manifest and with an invalid config value', function () {
    $this->fixture = kitDoctorFixture();
    unlink($this->fixture->path(Manifest::PATH));
    config(['kit.stack' => 'vue']);

    $this->artisan('kit:doctor')
        ->expectsOutputToContain('[FAIL] .ai/kit-manifest.json is missing. Run php artisan kit:install.')
        ->expectsOutputToContain('[FAIL] Invalid kit.stack value "vue".')
        ->assertFailed();
});
