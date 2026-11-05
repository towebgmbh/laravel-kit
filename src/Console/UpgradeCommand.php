<?php

namespace Towebgmbh\LaravelKit\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Laravel\Boost\Support\Config as BoostConfig;
use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;
use Towebgmbh\LaravelKit\Support\StackDetector;
use Towebgmbh\LaravelKit\Upgrade\ArchitectureResolver;
use Towebgmbh\LaravelKit\Upgrade\UpgradeAction;
use Towebgmbh\LaravelKit\Upgrade\UpgradePlan;
use Towebgmbh\LaravelKit\Upgrade\V1Inventory;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class UpgradeCommand extends Command
{
    protected $signature = 'kit:upgrade
        {--dry-run : Show the plan without changing anything}
        {--architecture= : actions, services or migrating}
        {--stack= : blade, livewire, inertia-vue, inertia-react or api}';

    protected $description = 'Upgrade a project from the v1 kit (laravel-claude-kit 1.0.0) to the Laravel Kit';

    public function handle(): int
    {
        $basePath = base_path();
        $inventory = new V1Inventory;

        if (! $this->isV1($basePath)) {
            $this->error('No v1 kit found (.claude/.kit-version 1.0.0).');

            return self::FAILURE;
        }

        if (! (new BoostConfig)->isValid()) {
            $this->error('Boost is not set up. Run [php artisan boost:install] first.');

            return self::FAILURE;
        }

        try {
            Manifest::load($basePath);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage().'. Fix or delete it, then run kit:upgrade again.');

            return self::FAILURE;
        }

        $stack = $this->resolveStack($basePath, $inventory);

        if ($stack === null) {
            return self::FAILURE;
        }

        $architecture = $this->resolveArchitecture($basePath);

        if ($architecture === null) {
            return self::FAILURE;
        }

        $plan = UpgradePlan::build($basePath, $inventory);
        $this->printPlan($plan, $stack, $architecture);

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! confirm('Apply this plan?', default: true)) {
            $this->warn('Upgrade cancelled: nothing was changed.');

            return self::FAILURE;
        }

        // Read before the files move: the report diffs the archived originals.
        $report = $this->report($basePath, $plan, $inventory, $stack, $architecture);

        try {
            $plan->execute();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage().' The upgrade stopped; fix the error and run kit:upgrade again.');

            return self::FAILURE;
        }

        $this->writeReport($basePath, $report);
        $this->info('Upgrade report: '.UpgradePlan::REPORT);

        $arguments = ['--existing' => true, '--stack' => $stack[0], '--architecture' => $architecture[0]];

        if (! $this->input->isInteractive()) {
            $arguments['--no-interaction'] = true;
        }

        if ($this->call('kit:install', $arguments) !== self::SUCCESS) {
            $this->error('kit:install failed. The v1 files are cleaned up; fix the error and run php artisan kit:install.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function isV1(string $basePath): bool
    {
        $file = $basePath.'/'.V1Inventory::VERSION_FILE;

        return is_file($file) && trim((string) file_get_contents($file)) === V1Inventory::VERSION;
    }

    /** @return array{0: string, 1: string}|null the stack and where it came from */
    protected function resolveStack(string $basePath, V1Inventory $inventory): ?array
    {
        $option = $this->option('stack');

        if (is_string($option) && $option !== '') {
            if (! in_array($option, Kit::STACKS, true)) {
                $this->error("Unknown stack [{$option}]. Use one of: ".implode(', ', Kit::STACKS).'.');

                return null;
            }

            return [$option, '--stack option'];
        }

        $frontend = $basePath.'/'.V1Inventory::FRONTEND_RULE;
        $contents = is_file($frontend) ? (string) file_get_contents($frontend) : null;

        if ($contents !== null && ($variant = $inventory->exactFrontendVariant($contents)) !== null) {
            return [$variant, V1Inventory::FRONTEND_RULE];
        }

        if (($detected = (new StackDetector)->detect($basePath)) !== null) {
            return [$detected, 'detected from composer.json and package.json'];
        }

        if ($contents !== null && ($variant = $inventory->frontendVariant($contents)) !== null) {
            return [$variant, V1Inventory::FRONTEND_RULE.' (closest v1 variant)'];
        }

        if (! $this->input->isInteractive()) {
            $this->error('Could not detect the stack. Pass --stack.');

            return null;
        }

        return [(string) select(
            label: 'Which stack does the project use?',
            options: InstallCommand::STACK_LABELS,
            default: 'blade',
        ), 'chosen interactively'];
    }

    /** @return array{0: string, 1: string}|null the architecture and where it came from */
    protected function resolveArchitecture(string $basePath): ?array
    {
        $option = $this->option('architecture');

        if (is_string($option) && $option !== '') {
            if (! in_array($option, Kit::ARCHITECTURES, true)) {
                $this->error("Unknown architecture [{$option}]. Use one of: ".implode(', ', Kit::ARCHITECTURES).'.');

                return null;
            }

            return [$option, '--architecture option'];
        }

        $derived = (new ArchitectureResolver)->resolve($basePath);

        if ($derived !== null) {
            $guidelines = $basePath.'/'.ArchitectureResolver::GUIDELINES;

            $source = match (true) {
                ! is_file($guidelines) => 'default, no '.ArchitectureResolver::GUIDELINES,
                (new ArchitectureResolver)->deviations((string) file_get_contents($guidelines)) === null => 'default, no Deviations section in '.ArchitectureResolver::GUIDELINES,
                default => 'derived from "Deviations from Kit Rules" in '.ArchitectureResolver::GUIDELINES,
            };

            return [$derived, $source];
        }

        if (! $this->input->isInteractive()) {
            $this->error('Could not derive the architecture. Pass --architecture.');

            return null;
        }

        return [(string) select(
            label: 'docs/GUIDELINES.md lists deviations. Which architecture should new code follow?',
            options: [
                'actions' => 'actions: business logic in Actions',
                'services' => 'services: keep the existing Service classes',
                'migrating' => 'migrating: new code in Actions, old code moves when touched',
            ],
            default: 'actions',
        ), 'chosen interactively'];
    }

    /**
     * @param  array{0: string, 1: string}  $stack
     * @param  array{0: string, 1: string}  $architecture
     */
    protected function printPlan(UpgradePlan $plan, array $stack, array $architecture): void
    {
        $this->table(
            ['Action', 'Path', 'Target', 'Reason'],
            array_map(fn (UpgradeAction $a): array => [$a->type, $a->path, $a->target ?? '', $a->reason], $plan->actions()),
        );

        $this->line("Stack: {$stack[0]} ({$stack[1]})");
        $this->line("Architecture: {$architecture[0]} ({$architecture[1]})");
        $this->line('docs/ and other skills in .claude/skills stay unchanged. Then kit:install runs.');
    }

    /**
     * @param  array{0: string, 1: string}  $stack
     * @param  array{0: string, 1: string}  $architecture
     */
    protected function report(string $basePath, UpgradePlan $plan, V1Inventory $inventory, array $stack, array $architecture): string
    {
        $lines = [
            '# Laravel Kit upgrade from v1',
            '',
            'Upgraded from laravel-claude-kit '.V1Inventory::VERSION.' to towebgmbh/laravel-kit '.Kit::VERSION.' on '.date('Y-m-d').'.',
            '',
            "- Architecture: `{$architecture[0]}` ({$architecture[1]})",
            "- Stack: `{$stack[0]}` ({$stack[1]})",
            '- `docs/` and skills other than the five v1 skills were left unchanged.',
            '',
            '## Actions',
            '',
            '| Action | Path | Target | Reason |',
            '| --- | --- | --- | --- |',
        ];

        foreach ($plan->actions() as $action) {
            $target = $action->target === null ? '' : "`{$action->target}`";
            $lines[] = "| {$action->type} | `{$action->path}` | {$target} | {$action->reason} |";
        }

        $archived = $plan->ofType(UpgradeAction::ARCHIVE);

        if ($archived !== []) {
            array_push($lines, '', '## Archived kit files', '',
                'These v1 kit files had local changes. They are archived here and no longer loaded by any agent.',
                'Carry over what still applies: project facts to `docs/`, path rules to `.ai/rules/` (Boost record-rule).');

            foreach ($archived as $action) {
                $project = (string) file_get_contents($basePath.'/'.$action->path);
                $original = (string) $inventory->original($action->path, $project);

                array_push($lines, '', "### {$action->path}", '', "Archived to `{$action->target}`.", '',
                    ...$this->fenced('diff', $this->diff($action->path, $original, $project)));
            }
        }

        $moved = $plan->ofType(UpgradeAction::MOVE);

        if ($moved !== []) {
            array_push($lines, '', '## Project rules', '',
                'Boost loads every file in `.ai/guidelines/` into the agent guidelines.', '');

            foreach ($moved as $action) {
                $note = $this->isPathScoped((string) file_get_contents($basePath.'/'.$action->path))
                    ? ' (path-scoped rule: consider recording it with record-rule)'
                    : '';
                $lines[] = "- `{$action->path}` -> `{$action->target}`{$note}";
            }
        }

        return implode("\n", $lines)."\n";
    }

    protected function writeReport(string $basePath, string $report): void
    {
        $file = $basePath.'/'.UpgradePlan::REPORT;

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, $report);
    }

    protected function diff(string $path, string $original, string $project): string
    {
        $builder = new StrictUnifiedDiffOutputBuilder([
            'collapseRanges' => true,
            'commonLineThreshold' => 1,
            'contextLines' => 3,
            'fromFile' => "{$path} (kit v1)",
            'toFile' => "{$path} (project)",
        ]);

        return rtrim((new Differ($builder))->diff(
            str_replace("\r\n", "\n", $original),
            str_replace("\r\n", "\n", $project),
        ));
    }

    /**
     * A fenced block whose fence is longer than any backtick run inside it.
     *
     * @return list<string>
     */
    protected function fenced(string $info, string $body): array
    {
        preg_match_all('/`+/', $body, $matches);
        $longest = max([0, ...array_map('strlen', $matches[0])]);
        $fence = str_repeat('`', max(3, $longest + 1));

        return [$fence.$info, $body, $fence];
    }

    /** A rule whose YAML frontmatter has a paths: key. */
    protected function isPathScoped(string $contents): bool
    {
        $contents = str_replace("\r\n", "\n", $contents);

        return preg_match('/\A---\n(.*?)\n---(\n|\z)/s', $contents, $match) === 1
            && preg_match('/^paths\s*:/m', $match[1]) === 1;
    }
}
