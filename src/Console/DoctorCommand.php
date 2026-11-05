<?php

namespace Towebgmbh\LaravelKit\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Laravel\Boost\Support\Config as BoostConfig;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;
use Towebgmbh\LaravelKit\Overrides;
use Towebgmbh\LaravelKit\Support\ComposerPackages;
use Towebgmbh\LaravelKit\Upgrade\UpgradePlan;
use Towebgmbh\LaravelKit\Upgrade\V1Inventory;

class DoctorCommand extends Command
{
    protected $signature = 'kit:doctor';

    protected $description = 'Check the kit setup and report drift against Laravel Boost';

    protected int $failures = 0;

    public function handle(): int
    {
        $this->failures = 0;
        $basePath = base_path();

        $this->checkBoostVersion();
        $this->checkBoostJson();
        $manifest = $this->checkManifest($basePath);
        $this->checkConfig();
        $this->checkLeftovers($basePath);
        $this->checkPest($basePath);
        $this->checkDrift($manifest);
        $this->checkDocsSize($basePath);

        return $this->failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function checkBoostVersion(): void
    {
        $version = Overrides::installedBoostVersion();

        if ($version === null) {
            $this->failLine('Laravel Boost is not installed.');

            return;
        }

        if (preg_match('/^\d+\.\d+/', $version) === 1 && version_compare($version, '2.10', '<')) {
            $this->failLine("Laravel Boost {$version} is older than 2.10.");

            return;
        }

        $this->ok("Laravel Boost {$version}");
    }

    protected function checkBoostJson(): void
    {
        $boost = new BoostConfig;

        if (! $boost->isValid()) {
            $this->failLine('boost.json is missing. Run php artisan boost:install.');

            return;
        }

        if (! in_array(Kit::PACKAGE, $boost->getPackages(), true)) {
            $this->failLine(Kit::PACKAGE.' is not active in boost.json. Run php artisan kit:install.');

            return;
        }

        $this->ok(Kit::PACKAGE.' is active in boost.json');
    }

    protected function checkManifest(string $basePath): ?Manifest
    {
        try {
            $manifest = Manifest::load($basePath);
        } catch (InvalidArgumentException $e) {
            $this->failLine($e->getMessage().'. Fix or delete it, then run php artisan kit:install.');

            return null;
        }

        if (! $manifest->exists()) {
            $this->failLine(Manifest::PATH.' is missing. Run php artisan kit:install.');

            return null;
        }

        $this->ok(Manifest::PATH.' found (kit '.($manifest->version() ?? 'unknown').')');

        return $manifest;
    }

    protected function checkConfig(): void
    {
        $valid = true;

        foreach (['stack' => Kit::STACKS, 'architecture' => Kit::ARCHITECTURES] as $key => $allowed) {
            $value = config("kit.{$key}");

            if (! is_string($value) || ! in_array($value, $allowed, true)) {
                $shown = is_scalar($value) ? (string) $value : gettype($value);
                $this->failLine("Invalid kit.{$key} value \"{$shown}\".");
                $valid = false;
            }
        }

        if ($valid) {
            $this->ok('config/kit.php: stack '.config('kit.stack').', architecture '.config('kit.architecture'));
        }
    }

    protected function checkLeftovers(string $basePath): void
    {
        $found = [];

        foreach (glob($basePath.'/'.UpgradePlan::RULES_DIR.'/*.md') ?: [] as $file) {
            $found[] = UpgradePlan::RULES_DIR.'/'.basename($file);
        }

        if (is_file($basePath.'/'.V1Inventory::VERSION_FILE)) {
            $found[] = V1Inventory::VERSION_FILE;
        }

        if ($found !== []) {
            $this->warnLine('Leftover v1 files: '.implode(', ', $found).'. Run php artisan kit:upgrade.');

            return;
        }

        $this->ok('No v1 leftovers');
    }

    protected function checkPest(string $basePath): void
    {
        $path = '.ai/guidelines/pest/core.blade.php';

        if (is_file($basePath.'/'.$path) && ! ComposerPackages::has($basePath, 'pestphp/pest')) {
            $this->warnLine("{$path} exists but Pest is not installed. Run php artisan kit:update.");
        }
    }

    protected function checkDrift(?Manifest $manifest): void
    {
        if ($manifest === null) {
            return;
        }

        if (Overrides::installedBoostIsOlderThanReference()) {
            $this->warnLine('Laravel Boost '.Overrides::installedBoostVersion().' is older than '.Overrides::BOOST_VERSION
                .', the version the kit overrides were written for. Run composer update laravel/boost.');

            return;
        }

        foreach ($manifest->overrides() as $path => $override) {
            $installed = Overrides::installedBoostHash($override['boost_file']);

            if ($installed !== null && $installed === $override['boost_sha256']) {
                $this->ok("Override {$path} matches Boost");

                continue;
            }

            $this->failLine("Override drift: {$path} was written for a different version of {$override['boost_file']}. "
                .'Update the kit and run php artisan kit:update.');
        }
    }

    protected function checkDocsSize(string $basePath): void
    {
        if (is_file($basePath.'/docs/README.md')) {
            return;
        }

        $threshold = (int) config('kit.docs.split_threshold', 4);
        $domains = 0;

        foreach (glob($basePath.'/app/*', GLOB_ONLYDIR) ?: [] as $dir) {
            foreach (['Actions', 'Models', 'Services'] as $sub) {
                if (is_dir($dir.'/'.$sub)) {
                    $domains++;

                    break;
                }
            }
        }

        if ($domains >= $threshold) {
            $this->warnLine("docs/ is flat with {$domains} domains (threshold {$threshold}). Run /kit-docs-split.");
        }
    }

    protected function ok(string $message): void
    {
        $this->line("[OK] {$message}");
    }

    protected function warnLine(string $message): void
    {
        $this->line("[WARN] {$message}");
    }

    protected function failLine(string $message): void
    {
        $this->failures++;
        $this->line("[FAIL] {$message}");
    }
}
