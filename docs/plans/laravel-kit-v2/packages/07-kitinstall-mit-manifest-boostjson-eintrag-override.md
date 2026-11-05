<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 7: kit:install mit Manifest, boost.json-Eintrag, Overrides, Config und flacher Doku

Kind: implement · Status: done · Model: opus · Effort: medium · Commits: `93da9af…4cb268d` · Verdict: –

## Spec

## Goal

Implement `php artisan kit:install`: it detects stack and project type (or asks), registers the package in `boost.json`, writes the overrides (`pest/core` only when the project uses Pest), `config/kit.php` and (for new projects) the flat docs, never overwrites without asking, runs `boost:update` with the chosen stack and architecture, and writes `.ai/kit-manifest.json`. Claude extras follow in package 8, `kit:update` in package 9.

Public repository (D-1): no client names. Output in English (D-2).

## Context to read

- Concept sections Users and flows (1, 2), Design (Konfiguration, Einklinken in Boost, Overrides und Testlinie, Manifest, Öffentliches Repo), Decisions D-1, D-5, D-7, D-15, D-21, D-22, D-23, D-37, D-40.
- `src/Kit.php`, `src/Manifest.php`, `src/Overrides.php` (`requires` key), `src/Support/StackDetector.php`, `src/KitServiceProvider.php`, `config/kit.php`, `stubs/ai/guidelines/*`, `stubs/docs/flat/*`.
- `tests/TestCase.php`, `tests/Support/BoostFixture.php` (creates empty `CLAUDE.md` and `AGENTS.md`; `pest: false` builds a fixture without Pest).
- Boost: `vendor/laravel/boost/src/Support/Config.php` (`isValid()`, `getPackages()`, `setPackages()`, `getAgents()`; it works on `base_path('boost.json')`), `src/Console/UpdateCommand.php` (`--no-discover`; it requires agents and guidelines or skills in boost.json), `src/Install/GuidelineComposer.php` (`getUserGuidelines()`: a `.ai/guidelines` file without a Boost counterpart renders as its own guideline).
- Fact: `boost:update` runs in the same process via `$this->call()`, so it renders with the config loaded at boot (package defaults when `config/kit.php` did not exist yet).

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `src/Support/ComposerPackages.php` (new): `final class ComposerPackages` with `static has(string $basePath, string $package): bool` reading `require` and `require-dev` of the project's `composer.json` (missing or invalid file -> false).
2. `src/Support/ProjectDetector.php` (new): `final class ProjectDetector` with `isNew(string $basePath): bool`, true when every file in `database/migrations` starts with `0001_01_01_` or ends with `_add_two_factor_columns_to_users_table.php` (D-23); a missing folder counts as new.
3. `src/Support/ManagedFiles.php` (new): `final class ManagedFiles` constructed with the base path, the `Manifest`, a `bool $force` and a confirm callback `Closure(string $path): bool`. `write(string $relativePath, string $contents, bool $managed = true): string` returns `created`, `unchanged`, `replaced` or `skipped`: missing -> write; identical -> nothing; different -> write only when `$force` or the callback confirms, else `skipped`. Managed files are recorded in the manifest when written or identical. `writeOnce(string $relativePath, string $contents): string` writes only when missing and returns `created` or `exists`, never records.
4. `src/Console/InstallCommand.php` (new): signature `kit:install {--stack= : blade, livewire, inertia-vue, inertia-react or api} {--architecture= : actions, services or migrating} {--new : Treat the project as new} {--existing : Treat the project as existing} {--force : Replace differing kit files without asking}`. Steps in order:
   1. `(new Laravel\\Boost\\Support\\Config)->isValid()` false -> error `Boost is not set up. Run [php artisan boost:install] first.` and `FAILURE` (D-21).
   2. Stack: option (validated against `Kit::STACKS`), else `StackDetector::detect()`; interactive: `Laravel\\Prompts\\select` with the detected default; non-interactive without a result -> error `Could not detect the stack. Pass --stack.` and `FAILURE`.
   3. Project type: `--new`/`--existing` (both -> error), else `ProjectDetector`; interactive `confirm` ("This looks like a new project. Is that right?").
   4. Architecture: option (validated), else `actions` for new projects; for existing projects an interactive `select` (default `actions`, hint that `/kit-onboard` asks again), non-interactive `actions`.
   5. boost.json: add `Kit::PACKAGE` to `packages` if missing (`setPackages(array_values(array_unique([...])))`).
   6. Overrides: for each `Overrides::all()` entry whose `requires` is null or `ComposerPackages::has($basePath, $requires)` is true (D-37), `ManagedFiles::write()` the stub and `Manifest::recordOverride()` with its `boost_file` and `boost_sha256`. Skipped `pest/core` prints `Skipped .ai/guidelines/pest/core.blade.php: Pest is not installed.` and is not recorded.
   7. `config/kit.php`: `writeOnce` from `stubs/config/kit.php.stub` (new file: a copy of the package's `config/kit.php`, including `hooks.docs_guard`, with `{{STACK}}` and `{{ARCHITECTURE}}`). When the result is `created`, set the running config with `config(['kit.stack' => $stack, 'kit.architecture' => $architecture])` (D-40). When it is `exists`, keep the values loaded at boot and, if they differ from the chosen ones, warn `config/kit.php exists and was left unchanged.`.
   8. New projects only: `writeOnce` of `docs/GUIDELINES.md`, `docs/SPECIFICATIONS.md`, `docs/DECISIONS.md` from `stubs/docs/flat/` with `{{PROJECT_NAME}}` (`name` from the project's `composer.json` after the slash, else the folder name), `{{STACK}}`, `{{ARCHITECTURE}}`.
   9. A protected hook method `installAgentExtras(ManagedFiles $files): void` that does nothing yet (package 8 fills it).
   10. Manifest: `setVersion(Kit::VERSION)`, `setProject()`, `save()`.
   11. `$this->call('boost:update', ['--no-discover' => true])`.
   12. Summary table (path, result) and the line `Next: open your agent and run /kit-onboard.`
5. Register `InstallCommand` in `KitServiceProvider`.
6. Confirm callback for interactive runs: `Laravel\\Prompts\\confirm("[{$path}] differs from the kit version. Replace it?", default: false)`; non-interactive -> false. Skipped files print `Skipped [path]: it differs from the kit version. Run php artisan kit:update to review the diff.`

## Acceptance criteria

Serves: AC-2, AC-3, AC-4, AC-12

- In a fixture with Pest, `boost.json` agents `["claude_code"]`, guidelines true and skills `["laravel-best-practices"]`, `kit:install --stack=livewire --new --no-interaction` exits 0, writes both overrides, `config/kit.php` with `'stack' => 'livewire'` and `'docs_guard' => true`, the three flat docs, the manifest (`version` 2.0.0, `project` new, two files, two overrides), adds the package to `boost.json`; the fixture's `CLAUDE.md` contains `towebgmbh/laravel-kit/core` and "Follow the Laravel Kit test line:" (the fixture has no tests, so this proves D-7), and the five kit skills land in `.claude/skills/`. Evidence: `InstallCommandTest`.
- `kit:install --stack=livewire --architecture=services --new --no-interaction` in a fresh fixture writes a `CLAUDE.md` that contains "Stack: Livewire 4 with Flux" and "This project keeps its Service classes." and neither "Stack: Blade with Alpine" nor "Business logic lives in Actions.", without a second `boost:update` (D-40). Evidence: `InstallCommandTest`.
- In a fixture without Pest (`BoostFixture::create(pest: false)`), `.ai/guidelines/pest/core.blade.php` is not written, the manifest has one override, and `CLAUDE.md` contains no line `# Pest` and no "This project uses Pest". Evidence: `InstallCommandTest`.
- A pre-existing different `.ai/guidelines/pest/core.blade.php` stays byte-identical without `--force` in non-interactive mode and is reported as skipped; with `--force` it is replaced. Evidence: `InstallCommandTest`.
- A second run reports the managed files as `unchanged` and `config/kit.php` and the docs as `exists`; `boost.json` packages have no duplicates. Evidence: `InstallCommandTest`.
- Existing project: no docs written. Missing `boost.json`: exit 1 with the message. Evidence: `InstallCommandTest`.
- No client name, customer data or real project name in the files of this package or its commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Commands/InstallCommandTest.php`: the scenarios above, plus stack detection from the fixture's composer packages and the `--new --existing` error.
- `tests/Unit/ComposerPackagesTest.php`, `tests/Unit/ProjectDetectorTest.php`, `tests/Unit/ManagedFilesTest.php`.

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- Claude hooks, `.claude/settings.json`, `DocsGuard` (package 8).
- `kit:update` (package 9), `kit:upgrade` (package 11), `kit:doctor` (package 12).

## Report

kit:install is implemented. It checks for a valid boost.json, resolves the stack (option, StackDetector or prompt), the project type (--new/--existing, ProjectDetector or confirm) and the architecture. It adds the package to boost.json and writes the overrides (pest/core only when the project requires Pest). It creates config/kit.php from a stub and sets the running config when it created the file (D-40), and creates the flat docs for new projects. Then it saves the manifest, runs boost:update --no-discover and prints a summary table. New helpers: ComposerPackages, ProjectDetector, ManagedFiles. Full suite: 149 passed. Pint is clean.

## Handoff

Files: src/Console/InstallCommand.php (registered in KitServiceProvider), src/Support/{ComposerPackages,ProjectDetector,ManagedFiles}.php, stubs/config/kit.php.stub ({{STACK}}, {{ARCHITECTURE}}), tests/Feature/Commands/InstallCommandTest.php, tests/Unit/{ComposerPackages,ProjectDetector,ManagedFiles}Test.php.
- Package 8: fill the protected hook InstallCommand::installAgentExtras(ManagedFiles $files). Read boost.json agents yourself (D-20). Record rows via $this->record($path, $result), which also prints the skip warning. ManagedFiles exposes manifest().
- Package 9 (kit:update): reuse ManagedFiles. Its result constants are CREATED/UNCHANGED/REPLACED/SKIPPED/EXISTS. The confirm closure gets the relative path, so add the diff there or before the call. InstallCommand::STACK_LABELS and FLAT_DOCS are public constants.
- Command instances are reused across artisan calls in tests, so reset any per-run state at the start of handle() (that was a real bug here with $results).
- TEST HARNESS CHANGE: tests/TestCase::setUp re-runs BoostServiceProvider::register() when RuleRepository is not bound. Boost's register() sees the testing env, so its singletons (RuleRepository, GuidelineConfig, BoostManager) were missing, and boost:update/boost:install crashed in syncRuleFiles.
- Boost writes CLAUDE.md/AGENTS.md relative to the CWD (ClaudeCode::guidelinesPath returns 'CLAUDE.md'). Tests that run boost:update must chdir() into the fixture and back to Kit::path() in afterEach, see kitInstallFixture(). Otherwise a CLAUDE.md lands in the package root.
- Laravel Prompts only fall back to the console question helper (expectsConfirmation) when app env is 'testing'. The interactive test sets app()['env']='testing' after boot.
- Boost's install drops packages from boost.json that are not installed in the project (a fake 'vendor/other' disappeared), so do not assert foreign package entries survive in fixtures.
- D-1: staged diff checked against the package 13 name list, only hits are the Towebgmbh namespace; commit messages are generic.

## Decisions

- recordOverride is called for every applicable override even when the file was skipped as differing (the file still overrides the Boost text, so drift tracking stays useful); only the files entry is omitted for skipped files.
- Summary table rows: boost.json ('package added' or 'unchanged'), each override, config/kit.php, docs, '.ai/kit-manifest.json' => 'saved'; skipped pest/core shows 'not needed'.
- {{STACK}} in the flat docs is the readable label (e.g. 'Livewire 4 with Flux', public InstallCommand::STACK_LABELS); config/kit.php gets the raw value.
- Unknown --stack or --architecture values fail with an error listing the allowed values; nothing is written before validation passes.
- If boost:update fails, kit:install still prints the table but returns FAILURE with a hint to rerun boost:update (manifest is already saved).
- Interactive project confirm also asks for the existing case ('This looks like an existing project. Is that right?'); answering no flips the type.
- ProjectDetector ignores .gitkeep in database/migrations; it is an instance class like StackDetector, ComposerPackages::has is static as specified.
- tests/TestCase setUp re-runs BoostServiceProvider::register() when RuleRepository is unbound (Boost singletons were missing in tests; package 2's env=local alone was not enough for boost:update).
- Dropped a planned test that a foreign boost.json package survives: Boost's own install prunes uninstalled packages; duplicate-free packages are covered by the second-run test.

## Checks

- `vendor/bin/pest`: exit 0, 149 passed (550 assertions)
- `vendor/bin/pint --test`: exit 0, passed
