<?php

namespace Tests\Support;

use Composer\InstalledVersions;
use Illuminate\Contracts\Foundation\Application;
use Laravel\Roster\ProjectManager;
use RuntimeException;
use Towebgmbh\LaravelKit\Kit;

/**
 * A throw-away Laravel project in which Boost sees the kit as a direct
 * third-party dependency: composer.json and composer.lock list the kit,
 * and vendor/towebgmbh/laravel-kit links to the package root.
 */
final class BoostFixture
{
    private function __construct(private readonly string $root) {}

    /**
     * @param  array<int|string, string>  $composerPackages  extra require packages, as names or name => version
     * @param  array<int|string, string>  $npmPackages  npm packages, as names or name => version
     * @param  array<string, mixed>|null  $boostJson  boost.json contents, null for the default
     */
    public static function create(
        array $composerPackages = [],
        array $npmPackages = [],
        ?array $boostJson = null,
        bool $pest = true,
    ): self {
        $root = sys_get_temp_dir().'/kit-fixture-'.bin2hex(random_bytes(6));

        if (! mkdir($root, 0777, true)) {
            throw new RuntimeException("Cannot create fixture directory [{$root}].");
        }

        $fixture = new self(realpath($root) ?: $root);

        $require = ['laravel/framework' => self::frameworkVersion()]
            + self::normalize($composerPackages);
        $requireDev = ['laravel/boost' => self::installedVersion('laravel/boost', '2.10.0'),
            Kit::PACKAGE => Kit::VERSION];

        if ($pest) {
            $requireDev['pestphp/pest'] = self::installedVersion('pestphp/pest', '4.1.0');
        }

        $fixture->putJson('composer.json', [
            'name' => 'example/app',
            'type' => 'project',
            'require' => self::constraints($require),
            'require-dev' => self::constraints($requireDev),
        ]);

        $fixture->putJson('composer.lock', [
            'packages' => self::lockEntries($require),
            'packages-dev' => self::lockEntries($requireDev),
        ]);

        $fixture->put('CLAUDE.md', '');
        $fixture->put('AGENTS.md', '');

        $fixture->putJson('boost.json', $boostJson ?? [
            'agents' => ['claude_code'],
            'guidelines' => true,
            'packages' => [Kit::PACKAGE],
        ]);

        $npm = self::normalize($npmPackages);

        if ($npm !== []) {
            $fixture->putJson('package.json', [
                'private' => true,
                'type' => 'module',
                'dependencies' => self::constraints($npm),
            ]);

            $lockPackages = ['' => ['dependencies' => self::constraints($npm)]];

            foreach ($npm as $name => $version) {
                $lockPackages['node_modules/'.$name] = ['version' => $version];
            }

            $fixture->putJson('package-lock.json', [
                'name' => 'app',
                'lockfileVersion' => 3,
                'requires' => true,
                'packages' => $lockPackages,
            ]);
        }

        mkdir($fixture->path('vendor/towebgmbh'), 0777, true);
        symlink(Kit::path(), $fixture->path('vendor/towebgmbh/laravel-kit'));

        return $fixture;
    }

    public function path(string $relative = ''): string
    {
        return $relative === '' ? $this->root : $this->root.'/'.ltrim($relative, '/');
    }

    public function put(string $relative, string $contents): self
    {
        $path = $this->path($relative);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);

        return $this;
    }

    /**
     * @param  array<mixed>  $data
     */
    public function putJson(string $relative, array $data): self
    {
        return $this->put($relative, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * Points the application at the fixture and drops the cached Roster scan,
     * so Boost scans the fixture's lock files on the next resolve.
     */
    public function activate(Application $app): self
    {
        $app->setBasePath($this->root);
        $app->forgetInstance(ProjectManager::class);

        return $this;
    }

    /** Removes the fixture directory without following the vendor symlink. */
    public function destroy(): void
    {
        self::remove($this->root);
    }

    private static function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::remove($path.'/'.$entry);
            }
        }

        rmdir($path);
    }

    /**
     * @param  array<int|string, string>  $packages
     * @return array<string, string>
     */
    private static function normalize(array $packages): array
    {
        $normalized = [];

        foreach ($packages as $key => $value) {
            if (is_int($key)) {
                $normalized[$value] = self::installedVersion($value, '1.0.0');
            } else {
                $normalized[$key] = ltrim($value, 'v');
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $versions
     * @return array<string, string>
     */
    private static function constraints(array $versions): array
    {
        return array_map(fn (string $version): string => '^'.$version, $versions);
    }

    /**
     * @param  array<string, string>  $versions
     * @return list<array{name: string, version: string}>
     */
    private static function lockEntries(array $versions): array
    {
        $entries = [];

        foreach ($versions as $name => $version) {
            $entries[] = ['name' => $name, 'version' => 'v'.$version];
        }

        return $entries;
    }

    /** The installed framework version, so Boost loads the guidelines for its major. */
    private static function frameworkVersion(): string
    {
        $app = function_exists('app') ? app() : null;
        $version = $app instanceof Application ? $app->version() : null;

        return ltrim($version ?? self::installedVersion('laravel/framework', '13.0.0'), 'v');
    }

    private static function installedVersion(string $package, string $fallback): string
    {
        $version = InstalledVersions::isInstalled($package)
            ? InstalledVersions::getPrettyVersion($package)
            : null;

        return ltrim($version !== null && preg_match('/^v?\d+\.\d+/', $version) ? $version : $fallback, 'v');
    }
}
