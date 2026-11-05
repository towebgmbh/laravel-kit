<?php

namespace Towebgmbh\LaravelKit\Support;

/**
 * A project counts as new while its only migrations are the ones of the
 * Laravel skeleton and the starter kits.
 */
final class ProjectDetector
{
    public function isNew(string $basePath): bool
    {
        $directory = rtrim($basePath, '/').'/database/migrations';

        if (! is_dir($directory)) {
            return true;
        }

        foreach (scandir($directory) ?: [] as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }

            if (! str_starts_with($file, '0001_01_01_')
                && ! str_ends_with($file, '_add_two_factor_columns_to_users_table.php')) {
                return false;
            }
        }

        return true;
    }
}
