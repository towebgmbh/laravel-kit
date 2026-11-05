<?php

namespace Towebgmbh\LaravelKit;

final class Kit
{
    public const VERSION = '2.0.0';

    public const PACKAGE = 'towebgmbh/laravel-kit';

    public const STACKS = ['blade', 'livewire', 'inertia-vue', 'inertia-react', 'api'];

    public const ARCHITECTURES = ['actions', 'services', 'migrating'];

    /** Absolute path inside the package root. */
    public static function path(string $path = ''): string
    {
        $root = dirname(__DIR__);

        return $path === '' ? $root : $root.'/'.ltrim($path, '/');
    }
}
