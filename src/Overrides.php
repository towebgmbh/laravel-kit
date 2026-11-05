<?php

namespace Towebgmbh\LaravelKit;

use Composer\InstalledVersions;
use InvalidArgumentException;

/**
 * The Boost guidelines the kit replaces in a project. Each override is a stub
 * copied to the project's `.ai/guidelines/`, where Boost takes it instead of
 * its own text under the same relative path.
 */
final class Overrides
{
    /** The Boost version the overrides were written against. */
    public const BOOST_VERSION = '2.10.4';

    /**
     * @return array<string, array{stub: string, boost_file: string, boost_sha256: string, requires: string|null}>
     */
    public static function all(): array
    {
        return [
            '.ai/guidelines/enforce-tests.blade.php' => [
                'stub' => 'stubs/ai/guidelines/enforce-tests.blade.php',
                'boost_file' => '.ai/enforce-tests.blade.php',
                'boost_sha256' => 'da3c2b35679918d42a8ef3075aedd98ed52b64748b62be80b40c0db1e7966463',
                'requires' => null,
            ],
            '.ai/guidelines/pest/core.blade.php' => [
                'stub' => 'stubs/ai/guidelines/pest/core.blade.php',
                'boost_file' => '.ai/pest/core.blade.php',
                'boost_sha256' => 'f436693358efe107a3bcd654c66bd1bb46bd6cb3297d50a1cf0786d76592de85',
                'requires' => 'pestphp/pest',
            ],
        ];
    }

    /** The contents of the stub for an override, keyed by its project path. */
    public static function stub(string $path): string
    {
        $override = self::all()[$path] ?? throw new InvalidArgumentException("Unknown kit override [{$path}].");

        return (string) file_get_contents(Kit::path($override['stub']));
    }

    /** The hash of a file in the installed Boost package, null when it is missing. */
    public static function installedBoostHash(string $boostFile): ?string
    {
        $root = InstalledVersions::isInstalled('laravel/boost')
            ? InstalledVersions::getInstallPath('laravel/boost')
            : null;

        if ($root === null) {
            return null;
        }

        $file = rtrim($root, '/').'/'.ltrim($boostFile, '/');

        return is_file($file) ? Manifest::hash((string) file_get_contents($file)) : null;
    }

    public static function installedBoostVersion(): ?string
    {
        if (! InstalledVersions::isInstalled('laravel/boost')) {
            return null;
        }

        $version = InstalledVersions::getPrettyVersion('laravel/boost');

        return $version === null ? null : ltrim($version, 'v');
    }

    public static function installedBoostIsReference(): bool
    {
        return self::installedBoostVersion() === self::BOOST_VERSION;
    }

    public static function installedBoostIsOlderThanReference(): bool
    {
        $installed = self::installedBoostVersion();

        return $installed !== null
            && preg_match('/^\d+\.\d+/', $installed) === 1
            && version_compare($installed, self::BOOST_VERSION, '<');
    }
}
