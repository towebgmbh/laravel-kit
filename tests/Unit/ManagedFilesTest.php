<?php

use Towebgmbh\LaravelKit\Manifest;
use Towebgmbh\LaravelKit\Support\ManagedFiles;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/kit-managed-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
    $this->manifest = Manifest::load($this->dir);
    $this->asked = [];
});

afterEach(function () {
    exec('rm -rf '.escapeshellarg($this->dir));
});

function managedFiles(object $test, bool $force = false, bool $answer = false): ManagedFiles
{
    return new ManagedFiles($test->dir, $test->manifest, $force, function (string $path) use ($test, $answer): bool {
        $test->asked[] = $path;

        return $answer;
    });
}

it('creates a missing file and records it', function () {
    $result = managedFiles($this)->write('.ai/guidelines/a.blade.php', 'kit');

    expect($result)->toBe('created')
        ->and(file_get_contents($this->dir.'/.ai/guidelines/a.blade.php'))->toBe('kit')
        ->and($this->manifest->fileHash('.ai/guidelines/a.blade.php'))->toBe(Manifest::hash('kit'));
});

it('leaves an identical file alone and records it', function () {
    file_put_contents($this->dir.'/a.md', 'kit');

    expect(managedFiles($this)->write('a.md', 'kit'))->toBe('unchanged')
        ->and($this->manifest->fileHash('a.md'))->toBe(Manifest::hash('kit'))
        ->and($this->asked)->toBe([]);
});

it('skips a differing file when the user declines', function () {
    file_put_contents($this->dir.'/a.md', 'own');

    expect(managedFiles($this)->write('a.md', 'kit'))->toBe('skipped')
        ->and(file_get_contents($this->dir.'/a.md'))->toBe('own')
        ->and($this->manifest->fileHash('a.md'))->toBeNull()
        ->and($this->asked)->toBe(['a.md']);
});

it('replaces a differing file when the user confirms', function () {
    file_put_contents($this->dir.'/a.md', 'own');

    expect(managedFiles($this, answer: true)->write('a.md', 'kit'))->toBe('replaced')
        ->and(file_get_contents($this->dir.'/a.md'))->toBe('kit');
});

it('replaces a differing file with force without asking', function () {
    file_put_contents($this->dir.'/a.md', 'own');

    expect(managedFiles($this, force: true)->write('a.md', 'kit'))->toBe('replaced')
        ->and($this->asked)->toBe([]);
});

it('does not record unmanaged files', function () {
    managedFiles($this)->write('a.md', 'kit', managed: false);

    expect($this->manifest->files())->toBe([]);
});

it('writes a file once and never records it', function () {
    $files = managedFiles($this);

    expect($files->writeOnce('config/kit.php', 'one'))->toBe('created')
        ->and($files->writeOnce('config/kit.php', 'two'))->toBe('exists')
        ->and(file_get_contents($this->dir.'/config/kit.php'))->toBe('one')
        ->and($this->manifest->files())->toBe([]);
});
