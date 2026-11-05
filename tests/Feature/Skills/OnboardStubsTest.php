<?php

use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Towebgmbh\LaravelKit\Kit;

function onboardStub(string $file): string
{
    return file_get_contents(Kit::path("resources/boost/skills/kit-onboard/stubs/{$file}"));
}

it('describes both modes, the mode detection, the migration and the fallbacks in kit-onboard', function () {
    $skill = file_get_contents(Kit::path('resources/boost/skills/kit-onboard/SKILL.md'));

    expect($skill)->toContain('Existing project')
        ->toContain('New project')
        ->toContain('.ai/kit-manifest.json')
        ->toContain('quiet')
        ->toContain('dry-run')
        ->toContain('PROJECT.md')
        ->toContain('MERGE')
        ->toContain('ARCHIVE')
        ->toContain('composer require pestphp/pest --dev -W')
        ->toContain('record-rule')
        ->toContain('Architecture decision')
        ->toContain('php artisan boost:update');
});

it('ships a valid pint.json with the laravel preset', function () {
    $config = json_decode(onboardStub('pint.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($config['preset'])->toBe('laravel');
});

it('ships a parsable GitHub workflow for tests and pint', function () {
    $workflow = Yaml::parse(onboardStub('tests.yml'));

    expect($workflow['on'])->toHaveKeys(['push', 'pull_request']);

    $steps = collect($workflow['jobs']['tests']['steps'])->pluck('run')->filter()->implode("\n");

    expect($steps)->toContain('composer install')
        ->toContain('npm ci && npm run build')
        ->toContain('vendor/bin/pint --test')
        ->toContain('php artisan test --parallel --compact');
});

it('ships an architecture test stub that is valid PHP for a domain', function () {
    $stub = onboardStub('ArchitectureTest.php.stub');

    expect($stub)->toContain('{{DOMAIN}}');

    $file = tempnam(sys_get_temp_dir(), 'kit-arch').'.php';
    file_put_contents($file, str_replace('{{DOMAIN}}', 'Billing', $stub));

    $process = new Process([PHP_BINARY, '-l', $file]);
    $process->run();
    unlink($file);

    expect($process->getExitCode())->toBe(0, $process->getOutput().$process->getErrorOutput());
    expect($stub)->toContain("toHaveMethod('handle')")
        ->toContain('Illuminate\Support\Facades\DB')
        ->toContain('App\Services');
});

it('ships flat doc stubs with the placeholders and without v1 rules', function (string $file) {
    $text = file_get_contents(Kit::path("stubs/docs/flat/{$file}"));

    expect($text)->toContain('{{PROJECT_NAME}}')
        ->toContain('{{STACK}}')
        ->toContain('{{ARCHITECTURE}}')
        ->not->toContain('.claude/rules');
})->with(['GUIDELINES.md', 'SPECIFICATIONS.md', 'DECISIONS.md']);

it('ships the split templates', function (string $file) {
    expect(Kit::path("resources/boost/skills/kit-docs-split/templates/{$file}"))->toBeFile();
})->with(['README.md', 'DOMAIN_README.md', 'DOMAIN_SPEC.md', 'CROSS_CUTTING.md', 'ADR.md']);

it('has the eight sections in the domain index card', function () {
    $card = file_get_contents(Kit::path('resources/boost/skills/kit-docs-split/templates/DOMAIN_README.md'));

    foreach (['When to read', 'Entry points', 'Code map', 'Invariants', 'Tests', 'Decisions', 'Neighbours', 'Files'] as $section) {
        expect($card)->toContain("\n## {$section}\n");
    }
});
