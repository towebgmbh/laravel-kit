<?php

use Tests\Support\BoostFixture;
use Tests\Support\Contradictions;
use Towebgmbh\LaravelKit\Kit;

const KIT_INSTALL_BOOST_JSON = [
    'agents' => ['claude_code'],
    'guidelines' => true,
    'skills' => ['laravel-best-practices'],
];

function kitInstallFixture(bool $pest = true, array $composerPackages = [], array $boostJson = KIT_INSTALL_BOOST_JSON): BoostFixture
{
    $fixture = BoostFixture::create(
        composerPackages: $composerPackages,
        boostJson: $boostJson,
        pest: $pest,
    );
    $fixture->activate(app());

    // Boost writes CLAUDE.md and AGENTS.md relative to the working directory.
    chdir($fixture->path());

    return $fixture;
}

function kitJson(BoostFixture $fixture, string $path): array
{
    return json_decode((string) file_get_contents($fixture->path($path)), true);
}

afterEach(function () {
    chdir(Kit::path());

    if (isset($this->fixture)) {
        $this->fixture->destroy();
    }
});

it('installs the kit into a new project with Pest', function () {
    $this->fixture = kitInstallFixture();

    $this->artisan('kit:install', ['--stack' => 'livewire', '--new' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Next: open your agent and run /kit-onboard.')
        ->assertSuccessful();

    $f = $this->fixture;

    expect($f->path('.ai/guidelines/enforce-tests.blade.php'))->toBeFile()
        ->and($f->path('.ai/guidelines/pest/core.blade.php'))->toBeFile();

    $config = file_get_contents($f->path('config/kit.php'));
    expect($config)->toContain("'stack' => 'livewire'")
        ->and($config)->toContain("'architecture' => 'actions'")
        ->and($config)->toContain("'docs_guard' => true");

    foreach (['GUIDELINES.md', 'SPECIFICATIONS.md', 'DECISIONS.md'] as $doc) {
        expect($f->path("docs/{$doc}"))->toBeFile()
            ->and(file_get_contents($f->path("docs/{$doc}")))->not->toContain('{{');
    }

    expect(file_get_contents($f->path('docs/GUIDELINES.md')))->toContain('**Name**: app');

    $manifest = kitJson($f, '.ai/kit-manifest.json');
    expect($manifest['version'])->toBe('2.0.0')
        ->and($manifest['project'])->toBe('new')
        ->and(array_keys($manifest['files']))->toEqualCanonicalizing([
            '.ai/guidelines/enforce-tests.blade.php',
            '.ai/guidelines/pest/core.blade.php',
            '.claude/hooks/kit-docs-guard.sh',
        ])
        ->and($manifest['overrides'])->toHaveCount(2)
        ->and($manifest['overrides']['.ai/guidelines/pest/core.blade.php']['boost_file'])->toBe('.ai/pest/core.blade.php');

    expect(kitJson($f, 'boost.json')['packages'])->toBe([Kit::PACKAGE]);

    $claude = file_get_contents($f->path('CLAUDE.md'));
    expect($claude)->toContain('towebgmbh/laravel-kit/core')
        ->and($claude)->toContain('Follow the Laravel Kit test line:')
        ->and($claude)->toContain('Stack: Livewire 4 with Flux');

    foreach (Contradictions::SENTENCES as $sentence) {
        expect($claude)->not->toContain($sentence);
    }

    foreach (['kit-brief', 'kit-docs-split', 'kit-onboard', 'kit-review', 'kit-spec'] as $skill) {
        expect($f->path(".claude/skills/{$skill}/SKILL.md"))->toBeFile();
    }
});

it('renders the chosen stack and architecture in the same run', function () {
    $this->fixture = kitInstallFixture();

    $this->artisan('kit:install', [
        '--stack' => 'livewire',
        '--architecture' => 'services',
        '--new' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $claude = file_get_contents($this->fixture->path('CLAUDE.md'));

    expect($claude)->toContain('Stack: Livewire 4 with Flux')
        ->and($claude)->toContain('This project keeps its Service classes.')
        ->and($claude)->not->toContain('Stack: Blade with Alpine')
        ->and($claude)->not->toContain('Business logic lives in Actions.');
});

it('skips the Pest override in a project without Pest', function () {
    $this->fixture = kitInstallFixture(pest: false);

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Skipped .ai/guidelines/pest/core.blade.php: Pest is not installed.')
        ->assertSuccessful();

    $f = $this->fixture;
    $manifest = kitJson($f, '.ai/kit-manifest.json');
    $claude = file_get_contents($f->path('CLAUDE.md'));

    expect($f->path('.ai/guidelines/pest/core.blade.php'))->not->toBeFile()
        ->and($manifest['overrides'])->toHaveCount(1)
        ->and($manifest['files'])->toHaveCount(2)
        ->and($claude)->not->toMatch('/^# Pest$/m')
        ->and($claude)->not->toContain('This project uses Pest')
        ->and($claude)->toContain('Follow the Laravel Kit test line:');
});

it('never replaces a differing kit file without asking', function () {
    $this->fixture = kitInstallFixture();
    $custom = "# My own Pest rules\n";
    $this->fixture->put('.ai/guidelines/pest/core.blade.php', $custom);

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Skipped .ai/guidelines/pest/core.blade.php: it differs from the kit version.')
        ->assertSuccessful();

    expect(file_get_contents($this->fixture->path('.ai/guidelines/pest/core.blade.php')))->toBe($custom);

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--force' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect(file_get_contents($this->fixture->path('.ai/guidelines/pest/core.blade.php')))
        ->toBe(file_get_contents(Kit::path('stubs/ai/guidelines/pest/core.blade.php')));
});

it('asks before replacing a differing file in interactive runs', function () {
    $this->fixture = kitInstallFixture();
    $this->fixture->put('.ai/guidelines/enforce-tests.blade.php', "custom\n");

    // The prompts fall back to the console question helper only in the testing environment.
    app()['env'] = 'testing';

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true])
        ->expectsConfirmation('[.ai/guidelines/enforce-tests.blade.php] differs from the kit version. Replace it?', 'yes')
        ->assertSuccessful();

    expect(file_get_contents($this->fixture->path('.ai/guidelines/enforce-tests.blade.php')))
        ->toBe(file_get_contents(Kit::path('stubs/ai/guidelines/enforce-tests.blade.php')));
});

it('reports unchanged and existing files on a second run', function () {
    $this->fixture = kitInstallFixture();
    $options = ['--stack' => 'livewire', '--new' => true, '--no-interaction' => true];

    $this->artisan('kit:install', $options)->assertSuccessful();

    $this->artisan('kit:install', $options)
        ->expectsTable(['Path', 'Result'], [
            ['boost.json', 'unchanged'],
            ['.ai/guidelines/enforce-tests.blade.php', 'unchanged'],
            ['.ai/guidelines/pest/core.blade.php', 'unchanged'],
            ['config/kit.php', 'exists'],
            ['docs/GUIDELINES.md', 'exists'],
            ['docs/SPECIFICATIONS.md', 'exists'],
            ['docs/DECISIONS.md', 'exists'],
            ['.claude/hooks/kit-docs-guard.sh', 'unchanged'],
            ['.claude/settings.json', 'unchanged'],
            ['.ai/kit-manifest.json', 'saved'],
        ])
        ->assertSuccessful();

    expect(kitJson($this->fixture, 'boost.json')['packages'])->toBe([Kit::PACKAGE]);
});

it('writes no docs for an existing project', function () {
    $this->fixture = kitInstallFixture();

    $this->artisan('kit:install', ['--stack' => 'blade', '--existing' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect($this->fixture->path('docs'))->not->toBeDirectory()
        ->and(kitJson($this->fixture, '.ai/kit-manifest.json')['project'])->toBe('existing');
});

it('detects an existing project from its migrations', function () {
    $this->fixture = kitInstallFixture();
    $this->fixture->put('database/migrations/0001_01_01_000000_create_users_table.php', '<?php');
    $this->fixture->put('database/migrations/2026_01_01_000000_create_invoices_table.php', '<?php');

    $this->artisan('kit:install', ['--stack' => 'blade', '--no-interaction' => true])->assertSuccessful();

    expect(kitJson($this->fixture, '.ai/kit-manifest.json')['project'])->toBe('existing')
        ->and($this->fixture->path('docs'))->not->toBeDirectory();
});

it('detects the stack from the composer packages', function () {
    $this->fixture = kitInstallFixture(composerPackages: ['livewire/livewire' => '4.0.0']);

    $this->artisan('kit:install', ['--new' => true, '--no-interaction' => true])->assertSuccessful();

    expect(file_get_contents($this->fixture->path('config/kit.php')))->toContain("'stack' => 'livewire'");
});

it('requires --stack when the stack is ambiguous', function () {
    $this->fixture = kitInstallFixture(composerPackages: [
        'livewire/livewire' => '4.0.0',
        'inertiajs/inertia-laravel' => '3.0.0',
    ]);

    $this->artisan('kit:install', ['--new' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Could not detect the stack. Pass --stack.')
        ->assertFailed();

    expect($this->fixture->path('.ai/kit-manifest.json'))->not->toBeFile();
});

it('rejects --new together with --existing', function () {
    $this->fixture = kitInstallFixture();

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--existing' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Pass either --new or --existing, not both.')
        ->assertFailed();
});

it('rejects an unknown stack or architecture', function () {
    $this->fixture = kitInstallFixture();

    $this->artisan('kit:install', ['--stack' => 'svelte', '--new' => true, '--no-interaction' => true])->assertFailed();
    $this->artisan('kit:install', ['--stack' => 'blade', '--architecture' => 'hexagonal', '--new' => true, '--no-interaction' => true])->assertFailed();
});

it('fails without boost.json', function () {
    $this->fixture = kitInstallFixture();
    unlink($this->fixture->path('boost.json'));

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Boost is not set up. Run [php artisan boost:install] first.')
        ->assertExitCode(1);

    expect($this->fixture->path('.ai'))->not->toBeDirectory();
});

it('leaves an existing config untouched and warns when it differs', function () {
    $this->fixture = kitInstallFixture();
    $this->fixture->put('config/kit.php', "<?php return ['stack' => 'blade'];\n");

    $this->artisan('kit:install', ['--stack' => 'livewire', '--new' => true, '--no-interaction' => true])
        ->expectsOutputToContain('config/kit.php exists and was left unchanged.')
        ->assertSuccessful();

    expect(file_get_contents($this->fixture->path('config/kit.php')))->toBe("<?php return ['stack' => 'blade'];\n");
});

function kitStopCommands(BoostFixture $fixture): array
{
    return array_merge(...array_map(
        fn (array $entry) => array_column($entry['hooks'], 'command'),
        kitJson($fixture, '.claude/settings.json')['hooks']['Stop'] ?? [],
    ));
}

it('installs the Claude Code Stop hooks', function () {
    $this->fixture = kitInstallFixture();

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--no-interaction' => true])->assertSuccessful();

    $f = $this->fixture;
    $script = $f->path('.claude/hooks/kit-docs-guard.sh');

    expect($script)->toBeFile()
        ->and(is_executable($script))->toBeTrue()
        ->and(file_get_contents($script))->toBe(file_get_contents(Kit::path('stubs/claude/hooks/kit-docs-guard.sh')))
        ->and(kitStopCommands($f))->toBe([
            'vendor/bin/pint --dirty --format agent',
            '"$CLAUDE_PROJECT_DIR"/.claude/hooks/kit-docs-guard.sh',
        ])
        ->and(kitJson($f, '.claude/settings.json')['hooks'])->not->toHaveKey('PostToolUse')
        ->and(kitJson($f, '.ai/kit-manifest.json')['files'])->toHaveKey('.claude/hooks/kit-docs-guard.sh');
});

it('merges the hooks into existing Claude settings', function () {
    $this->fixture = kitInstallFixture();
    $this->fixture->put('.claude/settings.json', json_encode([
        'permissions' => ['allow' => ['Bash(composer test)']],
        'hooks' => ['PostToolUse' => [['matcher' => 'Edit', 'hooks' => [['type' => 'command', 'command' => 'npm run lint']]]]],
    ]));

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--no-interaction' => true])->assertSuccessful();

    $settings = kitJson($this->fixture, '.claude/settings.json');

    expect($settings['permissions']['allow'])->toBe(['Bash(composer test)'])
        ->and(array_column($settings['hooks']['PostToolUse'][0]['hooks'], 'command'))->toBe(['npm run lint'])
        ->and($settings['hooks']['PostToolUse'])->toHaveCount(1)
        ->and(kitStopCommands($this->fixture))->toHaveCount(2);
});

it('warns about invalid Claude settings and leaves them alone', function () {
    $this->fixture = kitInstallFixture();
    $this->fixture->put('.claude/settings.json', '{"hooks": ');

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--no-interaction' => true])
        ->expectsOutputToContain('.claude/settings.json is not valid JSON; hooks were not added.')
        ->assertSuccessful();

    expect(file_get_contents($this->fixture->path('.claude/settings.json')))->toBe('{"hooks": ');
});

it('writes no Claude extras without the claude_code agent', function () {
    $this->fixture = kitInstallFixture(boostJson: [...KIT_INSTALL_BOOST_JSON, 'agents' => ['codex']]);

    $this->artisan('kit:install', ['--stack' => 'blade', '--new' => true, '--no-interaction' => true])->assertSuccessful();

    expect($this->fixture->path('.claude/hooks/kit-docs-guard.sh'))->not->toBeFile()
        ->and($this->fixture->path('.claude/settings.json'))->not->toBeFile()
        ->and(kitJson($this->fixture, '.ai/kit-manifest.json')['files'])->not->toHaveKey('.claude/hooks/kit-docs-guard.sh');
});
