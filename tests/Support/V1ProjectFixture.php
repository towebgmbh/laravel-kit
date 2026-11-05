<?php

namespace Tests\Support;

use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Upgrade\V1Inventory;

/**
 * A project as the v1 installer left it, inside a BoostFixture with the
 * default boost.json (no `skills` key, so boost:update leaves the stand-in
 * Boost skill folder alone). Some kit files are unchanged, two are modified,
 * two rules are project-own.
 */
final class V1ProjectFixture
{
    public const SERVICES_DEVIATION = '- Uses Service classes instead of Actions for legacy reasons — do NOT refactor to Actions';

    public const TESTING_ADDITION = '- Use datasets for currency rounding cases.';

    public const REVIEW_LINE = '# Code Review';

    public const REVIEW_CHANGED = '# Code Review (project checklist first)';

    public const OVERVIEW = "# Project Overview\n\nAn example application that manages orders and invoices.\n";

    public const PROJECT_RULE = <<<'MD'
---
paths:
  - "app/Billing/**"
---

# Billing Rules

- Store amounts as integer cents.
- Round only when an amount is displayed.

MD;

    /** The blockquote line under "## Deviations from Kit Rules" in the v1 template. */
    public const DEVIATIONS_HINT = "> If this project intentionally differs from `.claude/rules/`, document it here so Claude follows the project's conventions instead.";

    public const BOOST_SKILL = "---\nname: laravel-best-practices\ndescription: Stand-in for a Boost skill.\n---\n\n# Laravel Best Practices\n";

    /**
     * @param  string|null  $deviation  text in "Deviations from Kit Rules"; null keeps the v1 template unchanged
     */
    public static function create(?string $deviation = self::SERVICES_DEVIATION): BoostFixture
    {
        $fixture = BoostFixture::create();
        $templates = Kit::path('resources/legacy/v1/templates');

        foreach (['01-architecture.md', '02-laravel-conventions.md', '05-quality.md', '06-workflow.md'] as $rule) {
            $fixture->put(".claude/rules/{$rule}", self::read("{$templates}/.claude/rules/{$rule}"));
        }

        $fixture->put('.claude/rules/03-frontend.md', self::read("{$templates}/".V1Inventory::FRONTEND_VARIANTS['livewire']));
        $fixture->put('.claude/rules/04-testing.md', self::read("{$templates}/.claude/rules/04-testing.md").self::TESTING_ADDITION."\n");
        $fixture->put('.claude/rules/00-overview.md', self::OVERVIEW);
        $fixture->put('.claude/rules/07-project-rules.md', self::PROJECT_RULE);

        foreach (['onboard', 'migrate', 'test', 'brief'] as $skill) {
            $fixture->put(".claude/skills/{$skill}/SKILL.md", self::read("{$templates}/.claude/skills/{$skill}/SKILL.md"));
        }

        $review = self::read("{$templates}/.claude/skills/review/SKILL.md");
        $fixture->put('.claude/skills/review/SKILL.md', preg_replace(
            '/^'.preg_quote(self::REVIEW_LINE, '/').'$/m', self::REVIEW_CHANGED, $review, 1,
        ));

        $fixture->put('.claude/templates/FEATURE_TEMPLATE.md', self::read("{$templates}/.claude/templates/FEATURE_TEMPLATE.md"));
        $fixture->put(V1Inventory::VERSION_FILE, V1Inventory::VERSION."\n");
        $fixture->put('.claude/skills/laravel-best-practices/SKILL.md', self::BOOST_SKILL);

        $fixture->put('laravel-claude-kit/install.sh', self::read(Kit::path('resources/legacy/v1/install.sh')));
        $fixture->put('laravel-claude-kit/README.md', "# Laravel Claude Kit\n");

        $guidelines = self::read("{$templates}/docs/GUIDELINES.md");

        if ($deviation !== null) {
            $guidelines = str_replace(self::DEVIATIONS_HINT."\n", self::DEVIATIONS_HINT."\n\n{$deviation}\n", $guidelines);
        }

        $fixture->put('docs/GUIDELINES.md', $guidelines);

        return $fixture;
    }

    private static function read(string $path): string
    {
        return (string) file_get_contents($path);
    }
}
