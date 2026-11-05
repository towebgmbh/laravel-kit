<?php

namespace Towebgmbh\LaravelKit\Upgrade;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;

/**
 * The files the v1 installer (resources/legacy/v1/install.sh) copied into a
 * project, with their originals from resources/legacy/v1/templates.
 */
final class V1Inventory
{
    public const VERSION_FILE = '.claude/.kit-version';

    public const VERSION = '1.0.0';

    public const FRONTEND_RULE = '.claude/rules/03-frontend.md';

    public const SKILLS = ['onboard', 'migrate', 'review', 'test', 'brief'];

    /** v1 frontend variant => template path of its 03-frontend.md. */
    public const FRONTEND_VARIANTS = [
        'blade' => '.claude/rules/variants/03-frontend-blade.md',
        'livewire' => '.claude/rules/variants/03-frontend-livewire.md',
        'inertia-vue' => '.claude/rules/variants/03-frontend-inertia-vue.md',
    ];

    /** @var array<string, string> template path => contents */
    private array $templates = [];

    public function __construct(private readonly ?string $templatesPath = null) {}

    /**
     * Project paths of the v1 kit files, in plan order.
     *
     * @return list<string>
     */
    public function paths(): array
    {
        $paths = [
            '.claude/rules/01-architecture.md',
            '.claude/rules/02-laravel-conventions.md',
            self::FRONTEND_RULE,
            '.claude/rules/04-testing.md',
            '.claude/rules/05-quality.md',
            '.claude/rules/06-workflow.md',
        ];

        foreach (self::SKILLS as $skill) {
            $paths[] = ".claude/skills/{$skill}/SKILL.md";
        }

        $paths[] = '.claude/templates/FEATURE_TEMPLATE.md';

        return $paths;
    }

    public function has(string $projectPath): bool
    {
        return in_array($projectPath, $this->paths(), true);
    }

    /** The checksum a v1 file is compared with: CRLF normalised, trailing whitespace at the end trimmed. */
    public static function hash(string $contents): string
    {
        return Manifest::hash(rtrim($contents));
    }

    /**
     * Reference hashes of a project path; 03-frontend.md has one per variant.
     *
     * @return array<string, string> template path => hash
     */
    public function hashes(string $projectPath): array
    {
        $hashes = [];

        foreach ($this->templatePaths($projectPath) as $template) {
            $hashes[$template] = self::hash($this->template($template));
        }

        return $hashes;
    }

    /** Whether the contents equal a v1 original of the path (any variant for 03-frontend.md). */
    public function isUnchanged(string $projectPath, string $contents): bool
    {
        return in_array(self::hash($contents), $this->hashes($projectPath), true);
    }

    /**
     * The v1 original of a project path. For 03-frontend.md the variant that
     * matches the given project contents exactly or with the smallest diff.
     */
    public function original(string $projectPath, ?string $contents = null): ?string
    {
        if ($projectPath === self::FRONTEND_RULE) {
            $variant = $contents === null ? null : $this->frontendVariant($contents);

            return $variant === null ? null : $this->template(self::FRONTEND_VARIANTS[$variant]);
        }

        $templates = $this->templatePaths($projectPath);

        return $templates === [] ? null : $this->template($templates[0]);
    }

    /** The v1 variant whose 03-frontend.md equals the contents. */
    public function exactFrontendVariant(string $contents): ?string
    {
        foreach (self::FRONTEND_VARIANTS as $variant => $template) {
            if (self::hash($this->template($template)) === self::hash($contents)) {
                return $variant;
            }
        }

        return null;
    }

    /** The v1 variant whose 03-frontend.md equals the contents or differs from them least. */
    public function frontendVariant(string $contents): ?string
    {
        $exact = $this->exactFrontendVariant($contents);

        if ($exact !== null) {
            return $exact;
        }

        $best = null;
        $bestChanges = PHP_INT_MAX;

        foreach (self::FRONTEND_VARIANTS as $variant => $template) {
            [$common, $changes] = $this->compare($this->template($template), $contents);

            if ($common > 0 && $changes < $bestChanges) {
                $best = $variant;
                $bestChanges = $changes;
            }
        }

        return $best;
    }

    /**
     * Common and changed (added or removed) lines between two texts.
     *
     * @return array{0: int, 1: int}
     */
    private function compare(string $from, string $to): array
    {
        $common = 0;
        $changes = 0;

        foreach ((new Differ(new DiffOnlyOutputBuilder))->diffToArray(self::lines($from), self::lines($to)) as [, $type]) {
            match ($type) {
                Differ::OLD => $common++,
                Differ::ADDED, Differ::REMOVED => $changes++,
                default => null,
            };
        }

        return [$common, $changes];
    }

    /** @return list<string> */
    private static function lines(string $contents): array
    {
        $contents = rtrim(str_replace("\r\n", "\n", $contents));

        return $contents === '' ? [] : explode("\n", $contents);
    }

    /** @return list<string> */
    private function templatePaths(string $projectPath): array
    {
        if ($projectPath === self::FRONTEND_RULE) {
            return array_values(self::FRONTEND_VARIANTS);
        }

        return $this->has($projectPath) ? [$projectPath] : [];
    }

    private function template(string $templatePath): string
    {
        return $this->templates[$templatePath] ??= (string) file_get_contents(
            ($this->templatesPath ?? Kit::path('resources/legacy/v1/templates')).'/'.$templatePath,
        );
    }
}
