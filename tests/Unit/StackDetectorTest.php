<?php

use Towebgmbh\LaravelKit\Support\StackDetector;

function stackProject(array $composer = [], array $npm = [], array $views = []): string
{
    $dir = sys_get_temp_dir().'/kit-stack-'.bin2hex(random_bytes(4));
    mkdir($dir.'/resources/views', 0777, true);

    if ($composer !== []) {
        file_put_contents($dir.'/composer.json', json_encode(['require' => $composer]));
    }

    if ($npm !== []) {
        file_put_contents($dir.'/package.json', json_encode(['devDependencies' => $npm]));
    }

    foreach ($views as $view) {
        file_put_contents($dir.'/resources/views/'.$view, '');
    }

    return $dir;
}

function removeStackProject(string $dir): void
{
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

it('detects the stack', function (?string $expected, array $composer, array $npm, array $views) {
    $dir = stackProject($composer, $npm, $views);

    expect((new StackDetector)->detect($dir))->toBe($expected);

    removeStackProject($dir);
})->with([
    'livewire' => ['livewire', ['livewire/livewire' => '^4.0'], [], []],
    'inertia vue' => ['inertia-vue', ['inertiajs/inertia-laravel' => '^3.0'], ['@inertiajs/vue3' => '^3.0'], []],
    'inertia react' => ['inertia-react', ['inertiajs/inertia-laravel' => '^3.0'], ['@inertiajs/react' => '^3.0'], []],
    'livewire and inertia' => [null, ['livewire/livewire' => '^4.0', 'inertiajs/inertia-laravel' => '^3.0'], ['@inertiajs/vue3' => '^3.0'], []],
    'inertia with both adapters' => [null, ['inertiajs/inertia-laravel' => '^3.0'], ['@inertiajs/vue3' => '^3.0', '@inertiajs/react' => '^3.0'], []],
    'inertia without adapter' => [null, ['inertiajs/inertia-laravel' => '^3.0'], [], []],
    'api with only the welcome view' => ['api', ['laravel/sanctum' => '^4.0'], [], ['welcome.blade.php']],
    'sanctum with blade views' => ['blade', ['laravel/sanctum' => '^4.0'], [], ['home.blade.php']],
    'blade default' => ['blade', ['laravel/framework' => '^13.0'], [], []],
    'missing files' => ['blade', [], [], []],
]);

it('treats invalid json as empty', function () {
    $dir = stackProject();
    file_put_contents($dir.'/composer.json', '{not json');

    expect((new StackDetector)->detect($dir))->toBe('blade');

    removeStackProject($dir);
});
