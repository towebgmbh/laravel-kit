<?php

namespace Towebgmbh\LaravelKit\Support;

/** Reads the packages a project requires directly in its composer.json. */
final class ComposerPackages
{
    public static function has(string $basePath, string $package): bool
    {
        $file = rtrim($basePath, '/').'/composer.json';

        if (! is_file($file)) {
            return false;
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            return false;
        }

        foreach (['require', 'require-dev'] as $section) {
            if (is_array($data[$section] ?? null) && array_key_exists($package, $data[$section])) {
                return true;
            }
        }

        return false;
    }
}
