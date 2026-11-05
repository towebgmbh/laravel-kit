<?php

use Tests\Support\BoostFixture;
use Tests\Support\V1ProjectFixture;
use Towebgmbh\LaravelKit\Kit;
use Towebgmbh\LaravelKit\Manifest;
use Towebgmbh\LaravelKit\Upgrade\UpgradeAction;
use Towebgmbh\LaravelKit\Upgrade\UpgradePlan;

function kitUpgradeFixture(?string $deviation = V1ProjectFixture::SERVICES_DEVIATION): BoostFixture
{
    $fixture = V1ProjectFixture::create($deviation);
    $fixture->activate(app());

    // Boost writes CLAUDE.md and AGENTS.md relative to the working directory.
    chdir($fixture->path());

    return $fixture;
}

/**
 * Every file and folder of the fixture with a content hash, without
 * descending into the vendor symlink.
 *
 * @return array<string, string>
 */
function kitUpgradeTree(BoostFixture $fixture): array
{
    $root = $fixture->path();
    $tree = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($iterator as $file) {
        $relative = substr($file->getPathname(), strlen($root) + 1);

        $tree[$relative] = match (true) {
            $file->isLink() => 'link:'.readlink($file->getPathname()),
            $file->isDir() => 'dir',
            default => hash_file('sha256', $file->getPathname()),
        };
    }

    ksort($tree);

    return $tree;
}

const KIT_UNCHANGED_V1 = [
    '.claude/rules/01-architecture.md',
    '.claude/rules/02-laravel-conventions.md',
    '.claude/rules/03-frontend.md',
    '.claude/rules/05-quality.md',
    '.claude/rules/06-workflow.md',
    '.claude/skills/onboard/SKILL.md',
    '.claude/skills/migrate/SKILL.md',
    '.claude/skills/test/SKILL.md',
    '.claude/skills/brief/SKILL.md',
    '.claude/templates/FEATURE_TEMPLATE.md',
    '.claude/.kit-version',
];

const KIT_MODIFIED_V1 = ['.claude/rules/04-testing.md', '.claude/skills/review/SKILL.md'];

const KIT_PROJECT_RULES = ['00-overview.md', '07-project-rules.md'];

afterEach(function () {
    chdir(Kit::path());

    if (isset($this->fixture)) {
        $this->fixture->destroy();
    }
});

it('plans the upgrade of a v1 project', function () {
    $this->fixture = kitUpgradeFixture();

    $plan = UpgradePlan::build($this->fixture->path());
    $paths = fn (string $type): array => array_map(fn (UpgradeAction $a): string => $a->path, $plan->ofType($type));

    expect($paths(UpgradeAction::DELETE))->toEqualCanonicalizing(KIT_UNCHANGED_V1)
        ->and($paths(UpgradeAction::ARCHIVE))->toEqualCanonicalizing(KIT_MODIFIED_V1)
        ->and($paths(UpgradeAction::MOVE))->toBe(['.claude/rules/00-overview.md', '.claude/rules/07-project-rules.md'])
        ->and($paths(UpgradeAction::REMOVE_DIR))->toBe(['laravel-claude-kit'])
        ->and($paths(UpgradeAction::KEEP))->toBe([]);

    $targets = array_map(fn (UpgradeAction $a): ?string => $a->target, $plan->ofType(UpgradeAction::ARCHIVE));
    expect($targets)->toContain('.ai/kit-upgrade/v1/.claude/rules/04-testing.md')
        ->and($targets)->toContain('.ai/kit-upgrade/v1/.claude/skills/review/SKILL.md');

    foreach ($plan->actions() as $action) {
        expect($action->path)->not->toStartWith('docs/')
            ->and($action->path)->not->toContain('laravel-best-practices');
    }
});

it('shows the plan on a dry run and changes nothing', function () {
    $this->fixture = kitUpgradeFixture();
    $before = kitUpgradeTree($this->fixture);

    // A line counts for the first expectation it matches, so the summary lines come first.
    $command = $this->artisan('kit:upgrade', ['--dry-run' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Stack: livewire')
        ->expectsOutputToContain('Architecture: services')
        ->expectsOutputToContain('Dry run: nothing was changed.');

    foreach (KIT_UNCHANGED_V1 as $path) {
        $command->expectsOutputToContain($path);
    }

    $command
        ->expectsOutputToContain('.ai/kit-upgrade/v1/.claude/rules/04-testing.md')
        ->expectsOutputToContain('.ai/kit-upgrade/v1/.claude/skills/review/SKILL.md')
        ->expectsOutputToContain('.ai/guidelines/project/00-overview.md')
        ->expectsOutputToContain('.ai/guidelines/project/07-project-rules.md')
        ->expectsOutputToContain('remove-dir')
        ->assertSuccessful();

    expect(kitUpgradeTree($this->fixture))->toBe($before);
});

it('upgrades a v1 project and runs kit:install', function () {
    $this->fixture = kitUpgradeFixture();
    $f = $this->fixture;
    $docs = hash_file('sha256', $f->path('docs/GUIDELINES.md'));

    $this->artisan('kit:upgrade', ['--no-interaction' => true])->assertSuccessful();

    foreach ([...KIT_UNCHANGED_V1, ...KIT_MODIFIED_V1] as $path) {
        expect(file_exists($f->path($path)))->toBeFalse("{$path} should be gone");
    }

    foreach (KIT_MODIFIED_V1 as $path) {
        expect($f->path(".ai/kit-upgrade/v1/{$path}"))->toBeFile();
    }

    expect(file_get_contents($f->path('.ai/kit-upgrade/v1/.claude/rules/04-testing.md')))
        ->toContain(V1ProjectFixture::TESTING_ADDITION);

    expect(file_get_contents($f->path('.ai/guidelines/project/00-overview.md')))->toBe(V1ProjectFixture::OVERVIEW)
        ->and(file_get_contents($f->path('.ai/guidelines/project/07-project-rules.md')))->toBe(V1ProjectFixture::PROJECT_RULE);

    // Emptied v1 folders are gone, the Boost skill and docs/ stay.
    foreach (['.claude/rules', '.claude/templates', '.claude/skills/onboard', '.claude/skills/review', 'laravel-claude-kit'] as $dir) {
        expect(file_exists($f->path($dir)))->toBeFalse("{$dir} should be gone");
    }

    expect(file_get_contents($f->path('.claude/skills/laravel-best-practices/SKILL.md')))->toBe(V1ProjectFixture::BOOST_SKILL)
        ->and(hash_file('sha256', $f->path('docs/GUIDELINES.md')))->toBe($docs)
        ->and(file_exists($f->path('docs/SPECIFICATIONS.md')))->toBeFalse();

    $report = file_get_contents($f->path(UpgradePlan::REPORT));
    expect(preg_match_all('/^`{3,}diff$/m', $report))->toBe(2)
        ->and($report)->toContain('+'.V1ProjectFixture::TESTING_ADDITION)
        ->and($report)->toContain('-'.V1ProjectFixture::REVIEW_LINE)
        ->and($report)->toContain('+'.V1ProjectFixture::REVIEW_CHANGED)
        ->and($report)->toContain('`.claude/rules/07-project-rules.md` -> `.ai/guidelines/project/07-project-rules.md` (path-scoped rule: consider recording it with record-rule)')
        ->and($report)->not->toContain('00-overview.md` (path-scoped')
        ->and($report)->toContain('Architecture: `services` (derived from "Deviations from Kit Rules" in docs/GUIDELINES.md)');

    $config = file_get_contents($f->path('config/kit.php'));
    expect($config)->toContain("'architecture' => 'services'")
        ->and($config)->toContain("'stack' => 'livewire'");

    $manifest = json_decode((string) file_get_contents($f->path(Manifest::PATH)), true);
    expect($manifest['project'])->toBe('existing')
        ->and($manifest['version'])->toBe(Kit::VERSION);

    $claude = file_get_contents($f->path('CLAUDE.md'));
    expect($claude)->toContain('This project keeps its Service classes.')
        ->and($claude)->toContain('Stack: Livewire 4 with Flux')
        ->and($claude)->not->toContain('Business logic lives in Actions.');
});

it('derives actions from the unchanged v1 guidelines template', function () {
    $this->fixture = kitUpgradeFixture(deviation: null);

    $this->artisan('kit:upgrade', ['--no-interaction' => true])
        ->expectsOutputToContain('Architecture: actions')
        ->assertSuccessful();

    expect(file_get_contents($this->fixture->path('config/kit.php')))->toContain("'architecture' => 'actions'")
        ->and(file_get_contents($this->fixture->path('CLAUDE.md')))->toContain('Business logic lives in Actions.');
});

it('labels the default architecture when GUIDELINES.md has no Deviations section', function () {
    $this->fixture = kitUpgradeFixture();
    $this->fixture->put('docs/GUIDELINES.md', "# Guidelines\n\n## Architecture\n\nUses services.\n");

    $this->artisan('kit:upgrade', ['--dry-run' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Architecture: actions (default, no Deviations section in docs/GUIDELINES.md)')
        ->assertSuccessful();
});

it('fails without a v1 kit', function () {
    $this->fixture = kitUpgradeFixture();
    unlink($this->fixture->path('.claude/.kit-version'));

    $this->artisan('kit:upgrade', ['--no-interaction' => true])
        ->expectsOutputToContain('No v1 kit found (.claude/.kit-version 1.0.0).')
        ->assertFailed();

    expect($this->fixture->path('.claude/rules/01-architecture.md'))->toBeFile();
});

it('fails on another kit version', function () {
    $this->fixture = kitUpgradeFixture();
    file_put_contents($this->fixture->path('.claude/.kit-version'), "0.9.0\n");

    $this->artisan('kit:upgrade', ['--dry-run' => true])
        ->expectsOutputToContain('No v1 kit found (.claude/.kit-version 1.0.0).')
        ->assertFailed();
});

it('fails without interaction when the deviations are ambiguous', function () {
    $this->fixture = kitUpgradeFixture('- Models live in app/Models/ by type, not in domain folders.');
    $before = kitUpgradeTree($this->fixture);

    $this->artisan('kit:upgrade', ['--no-interaction' => true])
        ->expectsOutputToContain('Could not derive the architecture. Pass --architecture.')
        ->assertFailed();

    expect(kitUpgradeTree($this->fixture))->toBe($before);
});

it('takes the architecture and stack from the options', function () {
    $this->fixture = kitUpgradeFixture('- Models live in app/Models/ by type, not in domain folders.');

    $this->artisan('kit:upgrade', ['--architecture' => 'migrating', '--stack' => 'blade', '--no-interaction' => true])
        ->assertSuccessful();

    $config = file_get_contents($this->fixture->path('config/kit.php'));
    expect($config)->toContain("'architecture' => 'migrating'")
        ->and($config)->toContain("'stack' => 'blade'");
});

it('rejects an unknown architecture', function () {
    $this->fixture = kitUpgradeFixture();

    $this->artisan('kit:upgrade', ['--architecture' => 'hexagonal', '--no-interaction' => true])
        ->expectsOutputToContain('Unknown architecture [hexagonal]')
        ->assertFailed();
});

it('keeps a project rule whose target exists', function () {
    $this->fixture = kitUpgradeFixture();
    $this->fixture->put('.ai/guidelines/project/00-overview.md', "# Existing\n");

    $this->artisan('kit:upgrade', ['--no-interaction' => true])->assertSuccessful();

    expect(file_get_contents($this->fixture->path('.ai/guidelines/project/00-overview.md')))->toBe("# Existing\n")
        ->and(file_get_contents($this->fixture->path('.claude/rules/00-overview.md')))->toBe(V1ProjectFixture::OVERVIEW);
});

it('keeps a laravel-claude-kit folder without install.sh', function () {
    $this->fixture = kitUpgradeFixture();
    unlink($this->fixture->path('laravel-claude-kit/install.sh'));

    $plan = UpgradePlan::build($this->fixture->path());

    expect(array_map(fn (UpgradeAction $a): string => $a->path, $plan->ofType(UpgradeAction::KEEP)))->toBe(['laravel-claude-kit'])
        ->and($plan->ofType(UpgradeAction::REMOVE_DIR))->toBe([]);
});

it('fails on an invalid manifest before changing anything', function () {
    $this->fixture = kitUpgradeFixture();
    $this->fixture->put(Manifest::PATH, 'not json');
    $before = kitUpgradeTree($this->fixture);

    $this->artisan('kit:upgrade', ['--no-interaction' => true])
        ->expectsOutputToContain('The kit manifest is not valid JSON')
        ->assertFailed();

    expect(kitUpgradeTree($this->fixture))->toBe($before);
});
