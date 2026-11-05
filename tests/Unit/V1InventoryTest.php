<?php

use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Upgrade\V1Inventory;

function v1Template(string $path): string
{
    return (string) file_get_contents(Kit::path('resources/legacy/v1/templates/'.$path));
}

/** @return array<string, array{0: string, 1: string}> project path => template path */
function v1Files(): array
{
    $files = [];

    foreach ((new V1Inventory)->paths() as $path) {
        if ($path !== V1Inventory::FRONTEND_RULE) {
            $files[$path] = [$path, $path];
        }
    }

    foreach (V1Inventory::FRONTEND_VARIANTS as $variant => $template) {
        $files["03-frontend ({$variant})"] = [V1Inventory::FRONTEND_RULE, $template];
    }

    return $files;
}

it('lists the v1 kit files', function () {
    expect((new V1Inventory)->paths())->toHaveCount(12)
        ->toContain('.claude/rules/03-frontend.md')
        ->toContain('.claude/skills/review/SKILL.md')
        ->toContain('.claude/templates/FEATURE_TEMPLATE.md');
});

it('matches every legacy file with itself', function (string $path, string $template) {
    $inventory = new V1Inventory;
    $contents = v1Template($template);

    expect($inventory->isUnchanged($path, $contents))->toBeTrue()
        ->and($inventory->original($path, $contents))->toBe($contents);
})->with(v1Files());

it('matches a CRLF copy with trailing whitespace', function (string $path, string $template) {
    $copy = str_replace("\n", "\r\n", rtrim(v1Template($template)))."\r\n\r\n  ";

    expect((new V1Inventory)->isUnchanged($path, $copy))->toBeTrue();
})->with(v1Files());

it('does not match a modified file', function () {
    $contents = v1Template('.claude/rules/04-testing.md')."- One more rule.\n";

    expect((new V1Inventory)->isUnchanged('.claude/rules/04-testing.md', $contents))->toBeFalse();
});

it('has no original for other paths', function () {
    $inventory = new V1Inventory;

    expect($inventory->has('.claude/rules/07-project-rules.md'))->toBeFalse()
        ->and($inventory->original('.claude/rules/07-project-rules.md'))->toBeNull()
        ->and($inventory->hashes('.claude/rules/07-project-rules.md'))->toBe([]);
});

it('detects the exact frontend variant', function (string $variant) {
    $contents = v1Template(V1Inventory::FRONTEND_VARIANTS[$variant]);

    expect((new V1Inventory)->frontendVariant($contents))->toBe($variant)
        ->and((new V1Inventory)->exactFrontendVariant($contents))->toBe($variant);
})->with(array_keys(V1Inventory::FRONTEND_VARIANTS));

it('detects the closest frontend variant of a modified file', function (string $variant) {
    $contents = v1Template(V1Inventory::FRONTEND_VARIANTS[$variant])."\n## Project\n\n- One extra rule.\n";
    $inventory = new V1Inventory;

    expect($inventory->exactFrontendVariant($contents))->toBeNull()
        ->and($inventory->frontendVariant($contents))->toBe($variant)
        ->and($inventory->original(V1Inventory::FRONTEND_RULE, $contents))
        ->toBe(v1Template(V1Inventory::FRONTEND_VARIANTS[$variant]));
})->with(array_keys(V1Inventory::FRONTEND_VARIANTS));

it('finds no variant for unrelated text', function () {
    expect((new V1Inventory)->frontendVariant("Completely unrelated\n"))->toBeNull()
        ->and((new V1Inventory)->frontendVariant(''))->toBeNull();
});
