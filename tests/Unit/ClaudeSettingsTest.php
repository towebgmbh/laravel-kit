<?php

use Towebgmbh\LaravelKit\Support\ClaudeSettings;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/kit-settings-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
});

afterEach(function () {
    exec('rm -rf '.escapeshellarg($this->dir));
});

function settingsFile(object $test): string
{
    return $test->dir.'/'.ClaudeSettings::PATH;
}

function putSettings(object $test, string $contents): void
{
    mkdir(dirname(settingsFile($test)));
    file_put_contents(settingsFile($test), $contents);
}

function stopCommands(array $settings): array
{
    return array_merge(...array_map(
        fn (array $entry) => array_column($entry['hooks'], 'command'),
        $settings['hooks']['Stop'] ?? [],
    ));
}

it('creates the settings with both Stop hooks', function () {
    expect((new ClaudeSettings)->merge($this->dir))->toBe('created');

    $raw = file_get_contents(settingsFile($this));
    $settings = json_decode($raw, true);

    expect(stopCommands($settings))->toBe([ClaudeSettings::PINT_COMMAND, ClaudeSettings::DOCS_GUARD_COMMAND])
        ->and($settings['hooks'])->not->toHaveKey('PostToolUse')
        ->and($raw)->toEndWith("}\n")
        ->and($raw)->toContain('"vendor/bin/pint --dirty --format agent"')
        ->and($raw)->toContain('"\"$CLAUDE_PROJECT_DIR\"/.claude/hooks/kit-docs-guard.sh"');
});

it('keeps existing settings and hooks', function () {
    $existing = [
        'permissions' => ['allow' => ['Bash(composer test)'], 'deny' => []],
        'env' => new stdClass,
        'hooks' => [
            'PostToolUse' => [['matcher' => 'Edit|Write', 'hooks' => [['type' => 'command', 'command' => 'npm run lint']]]],
            'Stop' => [['hooks' => [['type' => 'command', 'command' => 'scripts/notify.sh']]]],
        ],
    ];
    putSettings($this, json_encode($existing, JSON_PRETTY_PRINT));

    expect((new ClaudeSettings)->merge($this->dir))->toBe('updated');

    $raw = file_get_contents(settingsFile($this));
    $settings = json_decode($raw, true);

    expect(array_keys($settings))->toBe(['permissions', 'env', 'hooks'])
        ->and($settings['permissions'])->toBe($existing['permissions'])
        ->and($raw)->toContain('"env": {}')
        ->and($settings['hooks']['PostToolUse'])->toBe($existing['hooks']['PostToolUse'])
        ->and(stopCommands($settings))->toBe(['scripts/notify.sh', ClaudeSettings::PINT_COMMAND, ClaudeSettings::DOCS_GUARD_COMMAND]);
});

it('adds only the missing hook', function () {
    putSettings($this, json_encode(['hooks' => ['Stop' => [
        ['hooks' => [['type' => 'command', 'command' => 'bash .claude/hooks/kit-docs-guard.sh']]],
    ]]]));

    expect((new ClaudeSettings)->merge($this->dir))->toBe('updated')
        ->and(stopCommands(json_decode(file_get_contents(settingsFile($this)), true)))
        ->toBe(['bash .claude/hooks/kit-docs-guard.sh', ClaudeSettings::PINT_COMMAND]);
});

it('returns unchanged on a second run', function () {
    $settings = new ClaudeSettings;
    $settings->merge($this->dir);
    $before = file_get_contents(settingsFile($this));

    expect($settings->merge($this->dir))->toBe('unchanged')
        ->and(file_get_contents(settingsFile($this)))->toBe($before);
});

it('leaves invalid settings untouched', function (string $contents) {
    putSettings($this, $contents);

    expect((new ClaudeSettings)->merge($this->dir))->toBe('invalid')
        ->and(file_get_contents(settingsFile($this)))->toBe($contents);
})->with([
    'broken json' => '{"permissions": ',
    'list' => '[1, 2]',
    'hooks not an object' => '{"hooks": []}',
    'Stop not a list' => '{"hooks": {"Stop": {}}}',
]);
