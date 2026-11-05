<?php

namespace Towebgmbh\LaravelKit\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Laravel\Boost\Support\Config as BoostConfig;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;
use Towebgmbh\LaravelKit\Overrides;
use Towebgmbh\LaravelKit\Support\ClaudeSettings;
use Towebgmbh\LaravelKit\Support\ComposerPackages;
use Towebgmbh\LaravelKit\Support\ManagedFiles;

use function Laravel\Prompts\confirm;

class UpdateCommand extends Command
{
    protected $signature = 'kit:update
        {--force : Replace changed kit files without asking}';

    protected $description = 'Update the kit files of a project to the installed kit version';

    /** @var array<int, array{0: string, 1: string}> */
    protected array $results = [];

    /** @var array<string, string> new kit contents by relative path */
    protected array $pending = [];

    public function handle(): int
    {
        $this->results = [];
        $this->pending = [];
        $basePath = base_path();

        try {
            $manifest = Manifest::load($basePath);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage().'. Fix or delete it, then run php artisan kit:install.');

            return self::FAILURE;
        }

        if (! $manifest->exists()) {
            $this->error('The kit is not installed. Run php artisan kit:install.');

            return self::FAILURE;
        }

        $boost = new BoostConfig;

        if (! $boost->isValid()) {
            $this->error('Boost is not set up. Run [php artisan boost:install] first.');

            return self::FAILURE;
        }

        $files = new ManagedFiles(
            $basePath,
            $manifest,
            (bool) $this->option('force'),
            fn (string $path): bool => $this->confirmReplace($path),
            replaceUnmodified: true,
        );

        $this->updateOverrides($files, $basePath);
        $this->updateAgentExtras($files, $boost);

        $packages = $boost->getPackages();

        if (! in_array(Kit::PACKAGE, $packages, true)) {
            $boost->setPackages(array_values(array_unique([...$packages, Kit::PACKAGE])));
            $this->results[] = ['boost.json', 'package added'];
        }

        $manifest->setVersion(Kit::VERSION);
        $manifest->save();
        $this->results[] = [Manifest::PATH, 'saved'];

        $status = $this->call('boost:update', ['--no-discover' => true]);

        $this->newLine();
        $this->table(['Path', 'Result'], $this->results);

        if ($status !== self::SUCCESS) {
            $this->error('boost:update failed. Fix the error above and run [php artisan boost:update].');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function updateOverrides(ManagedFiles $files, string $basePath): void
    {
        $manifest = $files->manifest();

        foreach (Overrides::all() as $path => $override) {
            $applies = $override['requires'] === null || ComposerPackages::has($basePath, $override['requires']);

            if (! $applies) {
                $this->removeOverride($files, $path);

                continue;
            }

            $contents = Overrides::stub($path);
            $this->pending[$path] = $contents;
            $result = $files->write($path, $contents);
            $this->record($path, $result);

            // A kept local override stays based on the Boost text it was written for.
            if ($result !== ManagedFiles::SKIPPED || ! isset($manifest->overrides()[$path])) {
                $manifest->recordOverride($path, $override['boost_file'], $override['boost_sha256']);
            }
        }
    }

    /** Pest is gone: drop the pest/core override when it is still the kit's file. */
    protected function removeOverride(ManagedFiles $files, string $path): void
    {
        $manifest = $files->manifest();

        if (! isset($manifest->overrides()[$path])) {
            return;
        }

        if ($files->deleteIfUnmodified($path)) {
            $manifest->forgetFile($path);
            $manifest->forgetOverride($path);
            $this->results[] = [$path, 'removed'];

            return;
        }

        $this->warn("[{$path}] is kept because it has local changes; remove it by hand.");
        $this->results[] = [$path, 'kept'];
    }

    protected function updateAgentExtras(ManagedFiles $files, BoostConfig $boost): void
    {
        if (! in_array('claude_code', $boost->getAgents(), true)) {
            return;
        }

        $script = InstallCommand::DOCS_GUARD_SCRIPT;
        $contents = (string) file_get_contents(Kit::path('stubs/claude/hooks/kit-docs-guard.sh'));
        $this->pending[$script] = $contents;
        $result = $files->write($script, $contents);
        $this->record($script, $result);

        if ($result !== ManagedFiles::SKIPPED) {
            chmod(base_path($script), 0755);
        }

        $settings = (new ClaudeSettings)->merge(base_path());

        if ($settings === ClaudeSettings::INVALID) {
            $this->warn(ClaudeSettings::PATH.' is not valid JSON; hooks were not added.');
        }

        $this->results[] = [ClaudeSettings::PATH, $settings];
    }

    protected function confirmReplace(string $path): bool
    {
        $this->line($this->diff($path));

        if (! $this->input->isInteractive()) {
            $this->warn("Skipped [{$path}]: local changes kept.");

            return false;
        }

        return confirm("Replace [{$path}] with the kit version?", default: false);
    }

    protected function diff(string $path): string
    {
        $builder = new StrictUnifiedDiffOutputBuilder([
            'collapseRanges' => true,
            'commonLineThreshold' => 1,
            'contextLines' => 3,
            'fromFile' => "{$path} (yours)",
            'toFile' => "{$path} (kit)",
        ]);

        return rtrim((new Differ($builder))->diff(
            (string) file_get_contents(base_path($path)),
            $this->pending[$path] ?? '',
        ));
    }

    protected function record(string $path, string $result): void
    {
        $this->results[] = [$path, $result];
    }
}
