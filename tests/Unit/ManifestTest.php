<?php

use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/kit-manifest-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
});

afterEach(function () {
    if (is_file($this->dir.'/'.Manifest::PATH)) {
        unlink($this->dir.'/'.Manifest::PATH);
    }
    if (is_dir($this->dir.'/.ai')) {
        rmdir($this->dir.'/.ai');
    }
    rmdir($this->dir);
});

it('is empty when the file is missing', function () {
    $manifest = Manifest::load($this->dir);

    expect($manifest->exists())->toBeFalse()
        ->and($manifest->version())->toBeNull()
        ->and($manifest->project())->toBeNull()
        ->and($manifest->files())->toBe([])
        ->and($manifest->overrides())->toBe([]);
});

it('round trips through save and load', function () {
    $manifest = Manifest::load($this->dir);
    $manifest->setVersion('2.0.0');
    $manifest->setProject('existing');
    $manifest->recordFile('b.txt', 'b');
    $manifest->recordFile('a.txt', 'a');
    $manifest->recordOverride('.ai/guidelines/x.blade.php', 'src/x.blade.php', 'abc');
    $manifest->save();

    $raw = file_get_contents($this->dir.'/'.Manifest::PATH);
    expect($raw)->toEndWith("}\n")
        ->and(array_keys(json_decode($raw, true)))->toBe(['files', 'overrides', 'project', 'version'])
        ->and(array_keys(json_decode($raw, true)['files']))->toBe(['a.txt', 'b.txt']);

    $loaded = Manifest::load($this->dir);
    expect($loaded->exists())->toBeTrue()
        ->and($loaded->version())->toBe('2.0.0')
        ->and($loaded->project())->toBe('existing')
        ->and($loaded->files())->toEqual($manifest->files())
        ->and($loaded->overrides())->toBe([
            '.ai/guidelines/x.blade.php' => ['boost_file' => 'src/x.blade.php', 'boost_sha256' => 'abc'],
        ]);
});

it('defaults the version to the kit version on save', function () {
    Manifest::load($this->dir)->save();

    expect(Manifest::load($this->dir)->version())->toBe(Kit::VERSION);
});

it('records and forgets files', function () {
    $manifest = Manifest::load($this->dir);
    $manifest->recordFile('a.txt', 'a');

    expect($manifest->fileHash('a.txt'))->toBe(hash('sha256', 'a'));

    $manifest->forgetFile('a.txt');

    expect($manifest->fileHash('a.txt'))->toBeNull();
});

it('records and forgets overrides', function () {
    $manifest = Manifest::load($this->dir);
    $manifest->recordOverride('o', 'f', 'h');
    expect($manifest->overrides())->toHaveKey('o');

    $manifest->forgetOverride('o');
    expect($manifest->overrides())->toBe([]);
});

it('rejects an invalid project value', function () {
    Manifest::load($this->dir)->setProject('legacy');
})->throws(InvalidArgumentException::class);

it('hashes CRLF and LF content identically', function () {
    expect(Manifest::hash("a\r\nb\r\n"))->toBe(Manifest::hash("a\nb\n"));
});
