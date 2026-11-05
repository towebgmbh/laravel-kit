<?php

namespace Towebgmbh\LaravelKit\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Support\Config as BoostConfig;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;
use Towebgmbh\LaravelKit\Overrides;
use Towebgmbh\LaravelKit\Support\ComposerPackages;
use Towebgmbh\LaravelKit\Support\ManagedFiles;
use Towebgmbh\LaravelKit\Support\ProjectDetector;
use Towebgmbh\LaravelKit\Support\StackDetector;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class InstallCommand extends Command
{
    protected $signature = 'kit:install
        {--stack= : blade, livewire, inertia-vue, inertia-react or api}
        {--architecture= : actions, services or migrating}
        {--new : Treat the project as new}
        {--existing : Treat the project as existing}
        {--force : Replace differing kit files without asking}';

    protected $description = 'Install the Laravel Kit into a project set up with Laravel Boost';

    /** Labels of the stacks, as the stack guideline names them. */
    public const STACK_LABELS = [
        'blade' => 'Blade with Alpine',
        'livewire' => 'Livewire 4 with Flux',
        'inertia-vue' => 'Inertia 3 with Vue',
        'inertia-react' => 'Inertia 3 with React',
        'api' => 'API only',
    ];

    public const FLAT_DOCS = ['GUIDELINES.md', 'SPECIFICATIONS.md', 'DECISIONS.md'];

    /** @var array<int, array{0: string, 1: string}> */
    protected array $results = [];

    public function handle(): int
    {
        $this->results = [];
        $boost = new BoostConfig;

        if (! $boost->isValid()) {
            $this->error('Boost is not set up. Run [php artisan boost:install] first.');

            return self::FAILURE;
        }

        $basePath = base_path();

        $stack = $this->resolveStack($basePath);

        if ($stack === null) {
            return self::FAILURE;
        }

        $project = $this->resolveProject($basePath);

        if ($project === null) {
            return self::FAILURE;
        }

        $architecture = $this->resolveArchitecture($project);

        if ($architecture === null) {
            return self::FAILURE;
        }

        $this->registerPackage($boost);

        $files = new ManagedFiles(
            $basePath,
            Manifest::load($basePath),
            (bool) $this->option('force'),
            fn (string $path): bool => $this->input->isInteractive()
                && confirm("[{$path}] differs from the kit version. Replace it?", default: false),
        );

        $this->installOverrides($files, $basePath);
        $this->installConfig($files, $stack, $architecture);

        if ($project === 'new') {
            $this->installDocs($files, $basePath, $stack, $architecture);
        }

        $this->installAgentExtras($files);

        $manifest = $files->manifest();
        $manifest->setVersion(Kit::VERSION);
        $manifest->setProject($project);
        $manifest->save();
        $this->results[] = [Manifest::PATH, 'saved'];

        $status = $this->call('boost:update', ['--no-discover' => true]);

        $this->newLine();
        $this->table(['Path', 'Result'], $this->results);

        if ($status !== self::SUCCESS) {
            $this->error('boost:update failed. Fix the error above and run [php artisan boost:update].');

            return self::FAILURE;
        }

        $this->info('Next: open your agent and run /kit-onboard.');

        return self::SUCCESS;
    }

    /** Claude Code hooks and other agent extras; filled in by the hooks package. */
    protected function installAgentExtras(ManagedFiles $files): void {}

    protected function resolveStack(string $basePath): ?string
    {
        $option = $this->option('stack');

        if (is_string($option) && $option !== '') {
            if (! in_array($option, Kit::STACKS, true)) {
                $this->error("Unknown stack [{$option}]. Use one of: ".implode(', ', Kit::STACKS).'.');

                return null;
            }

            return $option;
        }

        $detected = (new StackDetector)->detect($basePath);

        if (! $this->input->isInteractive()) {
            if ($detected === null) {
                $this->error('Could not detect the stack. Pass --stack.');
            }

            return $detected;
        }

        return (string) select(
            label: 'Which stack does the project use?',
            options: self::STACK_LABELS,
            default: $detected ?? 'blade',
        );
    }

    protected function resolveProject(string $basePath): ?string
    {
        if ($this->option('new') && $this->option('existing')) {
            $this->error('Pass either --new or --existing, not both.');

            return null;
        }

        if ($this->option('new')) {
            return 'new';
        }

        if ($this->option('existing')) {
            return 'existing';
        }

        $new = (new ProjectDetector)->isNew($basePath);

        if ($this->input->isInteractive()) {
            $label = $new ? 'This looks like a new project. Is that right?' : 'This looks like an existing project. Is that right?';

            if (! confirm($label, default: true)) {
                $new = ! $new;
            }
        }

        return $new ? 'new' : 'existing';
    }

    protected function resolveArchitecture(string $project): ?string
    {
        $option = $this->option('architecture');

        if (is_string($option) && $option !== '') {
            if (! in_array($option, Kit::ARCHITECTURES, true)) {
                $this->error("Unknown architecture [{$option}]. Use one of: ".implode(', ', Kit::ARCHITECTURES).'.');

                return null;
            }

            return $option;
        }

        if ($project === 'new' || ! $this->input->isInteractive()) {
            return 'actions';
        }

        return (string) select(
            label: 'Which architecture should new code follow?',
            options: [
                'actions' => 'actions: business logic in Actions',
                'services' => 'services: keep the existing Service classes',
                'migrating' => 'migrating: new code in Actions, old code moves when touched',
            ],
            default: 'actions',
            hint: '/kit-onboard asks again after analysing the project.',
        );
    }

    protected function registerPackage(BoostConfig $boost): void
    {
        $packages = $boost->getPackages();

        if (in_array(Kit::PACKAGE, $packages, true)) {
            $this->results[] = ['boost.json', 'unchanged'];

            return;
        }

        $boost->setPackages(array_values(array_unique([...$packages, Kit::PACKAGE])));
        $this->results[] = ['boost.json', 'package added'];
    }

    protected function installOverrides(ManagedFiles $files, string $basePath): void
    {
        foreach (Overrides::all() as $path => $override) {
            if ($override['requires'] !== null && ! ComposerPackages::has($basePath, $override['requires'])) {
                $this->line("Skipped {$path}: Pest is not installed.");
                $this->results[] = [$path, 'not needed'];

                continue;
            }

            $this->record($path, $files->write($path, Overrides::stub($path)));
            $files->manifest()->recordOverride($path, $override['boost_file'], $override['boost_sha256']);
        }
    }

    protected function installConfig(ManagedFiles $files, string $stack, string $architecture): void
    {
        $contents = strtr((string) file_get_contents(Kit::path('stubs/config/kit.php.stub')), [
            '{{STACK}}' => $stack,
            '{{ARCHITECTURE}}' => $architecture,
        ]);

        $result = $files->writeOnce('config/kit.php', $contents);
        $this->record('config/kit.php', $result);

        if ($result === ManagedFiles::CREATED) {
            // boost:update runs in this process with the config loaded at boot.
            config(['kit.stack' => $stack, 'kit.architecture' => $architecture]);

            return;
        }

        if (config('kit.stack') !== $stack || config('kit.architecture') !== $architecture) {
            $this->warn('config/kit.php exists and was left unchanged.');
        }
    }

    protected function installDocs(ManagedFiles $files, string $basePath, string $stack, string $architecture): void
    {
        $replace = [
            '{{PROJECT_NAME}}' => $this->projectName($basePath),
            '{{STACK}}' => self::STACK_LABELS[$stack],
            '{{ARCHITECTURE}}' => $architecture,
        ];

        foreach (self::FLAT_DOCS as $doc) {
            $contents = strtr((string) file_get_contents(Kit::path("stubs/docs/flat/{$doc}")), $replace);

            $this->record("docs/{$doc}", $files->writeOnce("docs/{$doc}", $contents));
        }
    }

    protected function projectName(string $basePath): string
    {
        $composer = json_decode((string) @file_get_contents($basePath.'/composer.json'), true);
        $name = is_array($composer) && is_string($composer['name'] ?? null) ? $composer['name'] : '';

        if ($name !== '') {
            return str_contains($name, '/') ? substr($name, strrpos($name, '/') + 1) : $name;
        }

        return basename($basePath);
    }

    protected function record(string $path, string $result): void
    {
        if ($result === ManagedFiles::SKIPPED) {
            $this->warn("Skipped {$path}: it differs from the kit version. Run php artisan kit:update to review the diff.");
        }

        $this->results[] = [$path, $result];
    }
}
