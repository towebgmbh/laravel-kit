<?php

namespace Towebgmbh\LaravelKit\Upgrade;

use FilesystemIterator;
use RuntimeException;

/**
 * What kit:upgrade does with the v1 files of a project. Only the paths the
 * v1 installer wrote are touched: .claude/rules/*.md, the five v1 skills,
 * .claude/templates/FEATURE_TEMPLATE.md, .claude/.kit-version and a copied
 * laravel-claude-kit/ folder. docs/ and other skills are never listed.
 */
final class UpgradePlan
{
    public const ARCHIVE_DIR = '.ai/kit-upgrade/v1';

    public const REPORT = self::ARCHIVE_DIR.'/REPORT.md';

    public const PROJECT_GUIDELINES = '.ai/guidelines/project';

    public const RULES_DIR = '.claude/rules';

    public const KIT_COPY = 'laravel-claude-kit';

    /** Folders removed after the upgrade when they are empty. */
    private const CLEANUP_DIRS = ['.claude/rules', '.claude/templates'];

    /** @param  list<UpgradeAction>  $actions */
    private function __construct(
        private readonly string $basePath,
        private readonly array $actions,
    ) {}

    public static function build(string $basePath, V1Inventory $inventory = new V1Inventory): self
    {
        $basePath = rtrim($basePath, '/');
        $actions = [];

        foreach ($inventory->paths() as $path) {
            if (! is_file("{$basePath}/{$path}")) {
                continue;
            }

            if ($inventory->isUnchanged($path, (string) file_get_contents("{$basePath}/{$path}"))) {
                $actions[] = new UpgradeAction(UpgradeAction::DELETE, $path, null, 'unchanged v1 kit file');

                continue;
            }

            $target = self::ARCHIVE_DIR.'/'.$path;
            $actions[] = file_exists("{$basePath}/{$target}")
                ? new UpgradeAction(UpgradeAction::KEEP, $path, $target, 'modified v1 kit file; archive target exists')
                : new UpgradeAction(UpgradeAction::ARCHIVE, $path, $target, 'modified v1 kit file; diff in the report');
        }

        foreach (self::projectRules($basePath, $inventory) as $path) {
            $target = self::PROJECT_GUIDELINES.'/'.basename($path);
            $actions[] = file_exists("{$basePath}/{$target}")
                ? new UpgradeAction(UpgradeAction::KEEP, $path, $target, 'project rule; target exists')
                : new UpgradeAction(UpgradeAction::MOVE, $path, $target, 'project rule');
        }

        if (is_file($basePath.'/'.V1Inventory::VERSION_FILE)) {
            $actions[] = new UpgradeAction(UpgradeAction::DELETE, V1Inventory::VERSION_FILE, null, 'v1 version marker');
        }

        $kitCopy = $basePath.'/'.self::KIT_COPY;

        if (is_dir($kitCopy) && ! is_link($kitCopy)) {
            $actions[] = is_file($kitCopy.'/install.sh')
                ? new UpgradeAction(UpgradeAction::REMOVE_DIR, self::KIT_COPY, null, 'copied v1 kit')
                : new UpgradeAction(UpgradeAction::KEEP, self::KIT_COPY, null, 'no install.sh inside; not the v1 kit');
        }

        return new self($basePath, $actions);
    }

    /** @return list<UpgradeAction> */
    public function actions(): array
    {
        return $this->actions;
    }

    /** @return list<UpgradeAction> */
    public function ofType(string $type): array
    {
        return array_values(array_filter($this->actions, fn (UpgradeAction $action): bool => $action->type === $type));
    }

    /** Runs every action, then removes the v1 folders that are left empty. */
    public function execute(): void
    {
        foreach ($this->actions as $action) {
            $source = $this->basePath.'/'.$action->path;

            match ($action->type) {
                UpgradeAction::DELETE => self::unlink($source),
                UpgradeAction::ARCHIVE, UpgradeAction::MOVE => self::move($source, $this->basePath.'/'.$action->target),
                UpgradeAction::REMOVE_DIR => self::removeDirectory($source),
                default => null,
            };
        }

        $dirs = self::CLEANUP_DIRS;

        foreach (V1Inventory::SKILLS as $skill) {
            $dirs[] = ".claude/skills/{$skill}";
        }

        foreach ($dirs as $dir) {
            $path = $this->basePath.'/'.$dir;

            if (is_dir($path) && ! is_link($path) && (new FilesystemIterator($path))->valid() === false) {
                rmdir($path);
            }
        }
    }

    /**
     * Top-level Markdown files in .claude/rules that are not v1 kit files.
     *
     * @return list<string>
     */
    private static function projectRules(string $basePath, V1Inventory $inventory): array
    {
        $files = glob($basePath.'/'.self::RULES_DIR.'/*.md') ?: [];
        $rules = [];

        foreach ($files as $file) {
            $path = self::RULES_DIR.'/'.basename($file);

            if (is_file($file) && ! is_link($file) && ! $inventory->has($path)) {
                $rules[] = $path;
            }
        }

        sort($rules);

        return $rules;
    }

    private static function unlink(string $path): void
    {
        if (! unlink($path)) {
            throw new RuntimeException("Cannot delete [{$path}].");
        }
    }

    private static function move(string $from, string $to): void
    {
        if (! is_dir(dirname($to)) && ! mkdir(dirname($to), 0755, true)) {
            throw new RuntimeException('Cannot create ['.dirname($to).'].');
        }

        if (! rename($from, $to)) {
            throw new RuntimeException("Cannot move [{$from}] to [{$to}].");
        }
    }

    /** Deletes a folder without following symlinks inside it. */
    private static function removeDirectory(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            self::unlink($path);

            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::removeDirectory($path.'/'.$entry);
            }
        }

        rmdir($path);
    }
}
