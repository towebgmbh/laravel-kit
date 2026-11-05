<?php

use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Upgrade\ArchitectureResolver;

function resolverProject(?string $guidelines): string
{
    $dir = sys_get_temp_dir().'/kit-arch-'.bin2hex(random_bytes(4));
    mkdir($dir.'/docs', 0777, true);

    if ($guidelines !== null) {
        file_put_contents($dir.'/docs/GUIDELINES.md', $guidelines);
    }

    return $dir;
}

function resolverTemplate(): string
{
    return (string) file_get_contents(Kit::path('resources/legacy/v1/templates/docs/GUIDELINES.md'));
}

function resolverSection(string $body): string
{
    return "# Guidelines\n\n## Architecture\n\nServices everywhere in this heading.\n\n"
        ."## Deviations from Kit Rules\n\n{$body}\n\n## External Integrations\n\n- Migrating payments soon.\n";
}

afterEach(function () {
    if (isset($this->dir)) {
        if (is_file($this->dir.'/docs/GUIDELINES.md')) {
            unlink($this->dir.'/docs/GUIDELINES.md');
        }
        rmdir($this->dir.'/docs');
        rmdir($this->dir);
    }
});

it('derives the architecture', function (?string $guidelines, ?string $expected) {
    $this->dir = resolverProject($guidelines);

    expect((new ArchitectureResolver)->resolve($this->dir))->toBe($expected);
})->with([
    'unchanged v1 template' => [fn () => resolverTemplate(), 'actions'],
    'missing file' => [null, 'actions'],
    'missing section' => ["# Guidelines\n\n## Architecture\n\nUses services.\n", 'actions'],
    'empty section' => [fn () => resolverSection(''), 'actions'],
    'only comments and blockquotes' => [fn () => resolverSection("> Document deviations here.\n\n<!--\n- Uses Service classes\n-->"), 'actions'],
    'services' => [fn () => resolverSection('- Uses Service classes instead of Actions for legacy reasons'), 'services'],
    'migrating' => [fn () => resolverSection('- Migrating from services to Actions step by step'), 'migrating'],
    'ambiguous' => [fn () => resolverSection('- Models live in app/Models/ by type'), null],
    'CRLF services' => [fn () => str_replace("\n", "\r\n", resolverSection('- Keeps the service layer')), 'services'],
    'last section' => ["# Guidelines\n\n## Deviations from Kit Rules\n\n- Gradual migration to Actions\n", 'migrating'],
]);
