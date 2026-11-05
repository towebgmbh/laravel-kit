<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 2: Testbench-Harness mit Boost-Fixture und niedrigste Boost-Version

Kind: implement · Status: done · Model: opus · Effort: medium · Commits: `5ddd70c…9964038` · Verdict: –

## Spec

## Goal

Build the test harness every later package relies on: a Testbench `TestCase` in which Boost registers, a `BoostFixture` that makes a throw-away project in which Boost sees the kit as a direct third-party dependency, and a `Guidelines` helper that renders guidelines through Boost's `GuidelineComposer`. Prove with a placeholder guideline that Boost discovers the kit, test the service provider, and determine the lowest Boost 2.10.x the kit works with.

Public repository (D-1): no client names in code, tests or commit messages. English (D-2).

## Context to read

- Concept sections Design (Einklinken in Boost, Kollisionsschutz, Öffentliches Repo), Decisions D-1, D-3, D-5, D-7, D-25, D-42 and Risks (Boost 2.10.0, Roster, provider in tests).
- Package 1: `composer.json`, `src/Kit.php`, `src/KitServiceProvider.php`, `config/kit.php`, `phpunit.xml`, `tests/Pest.php`, `.github/workflows/tests.yml`.
- Boost: `vendor/laravel/boost/src/Install/ThirdPartyPackage.php`, `src/Install/GuidelineComposer.php` (`guidelines()`, `config()`, `getThirdPartyGuidelines()`, `guidelinePath()`, static `composeGuidelines(Collection)`), `src/Install/GuidelineConfig.php` (`enforceTests`, `aiGuidelines`), `src/Install/Agents/ClaudeCode.php` (`guidelinesPath()` writes `CLAUDE.md` only when it exists, else `AGENTS.md`), `src/Support/PackageRegistry.php` (`boostPath()`), `src/BoostServiceProvider.php` (`shouldRun()` returns false when `app()->runningUnitTests()`).
- Roster: `vendor/laravel/roster/src/Scanners/ComposerLock.php`, `src/Scanners/PackageScanner.php` (how versions and `direct` are read), `src/ProjectManager.php` (`scan()`, `fresh()`, cache).

Tooling: if `php` or `composer` is not on PATH, run `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"` first.

## Tasks

1. `tests/TestCase.php` (new): extends `Orchestra\\Testbench\\TestCase`, providers `Laravel\\Boost\\BoostServiceProvider`, `Laravel\\Roster\\RosterServiceProvider` if it exists, `Towebgmbh\\LaravelKit\\KitServiceProvider`. Boost does not register under `runningUnitTests()`: set the application environment to `local` in `defineEnvironment()` (`$app['env'] = 'local'`), set `boost.browser_logs_watcher` false and `cache.default` `array`. If Boost still does not register, bind the singletons from `BoostServiceProvider::register()` in `TestCase` and register `Laravel\\Boost\\Console\\UpdateCommand` and `InstallCommand`; document which way works in a comment. Update `tests/Pest.php`: `uses(Tests\\TestCase::class)->in('Feature', 'Drift')`.
2. `tests/Support/BoostFixture.php` (new): `final class BoostFixture` that creates a throw-away project directory under `sys_get_temp_dir()`: `composer.json` (require `laravel/framework`, require-dev `laravel/boost`, `towebgmbh/laravel-kit`, and `pestphp/pest` unless `pest: false`, plus optional stack packages), a matching `composer.lock` (`packages` and `packages-dev` with names and versions in the format Roster parses), empty `CLAUDE.md` and `AGENTS.md` (the Laravel 13 skeleton ships both; Boost's `claude_code` agent writes to `CLAUDE.md` only when it exists), optional `package.json` with `package-lock.json` for `@inertiajs/vue3` or `@inertiajs/react`, `boost.json` (default `{"agents":["claude_code"],"guidelines":true,"packages":["towebgmbh/laravel-kit"]}`, no `skills` key), and the symlink `vendor/towebgmbh/laravel-kit` -> package root. API: `static create(array $composerPackages = [], array $npmPackages = [], ?array $boostJson = null, bool $pest = true): self`, `path(string $relative = '')`, `put(string $relative, string $contents)`, `activate(Application $app)` (calls `$app->setBasePath()`, forgets the `Laravel\\Roster\\ProjectManager` instance so it rescans) and `destroy()` (removes the directory, never follows the vendor symlink). Use `laravel/framework` in the lock with the major of the installed framework (`app()->version()`).
3. `tests/Support/Guidelines.php` (new): `final class Guidelines` with `static compose(BoostFixture $fixture, bool $enforceTests = true, ?array $packages = null): Illuminate\\Support\\Collection` (activates the fixture, builds a `Laravel\\Boost\\Install\\GuidelineConfig` with `enforceTests` and `aiGuidelines` = `$packages ?? ['towebgmbh/laravel-kit']`, returns `app(GuidelineComposer::class)->config($config)->guidelines()`); `static text(Collection $guidelines): string` returning `GuidelineComposer::composeGuidelines($guidelines)`; `static kitOnly(Collection $guidelines): Collection` keeping keys starting with `towebgmbh/laravel-kit/`; `static lineCount(string $text): int`.
4. `resources/boost/guidelines/core.blade.php` (new, placeholder): only the line `# Laravel Kit` and one sentence; package 3 replaces it.
5. Lowest Boost: run the suite once with `composer require "laravel/boost:2.10.0" -W`. If it fails because of a missing Boost API, raise to the lowest 2.10.x that passes, set that as the `require` constraint in `composer.json` and as the `lowest` version in `.github/workflows/tests.yml`, restore the latest Boost afterwards and report the deviation in the handoff (Risks). Do not commit `composer.lock`.

## Acceptance criteria

Serves: AC-1, AC-2, AC-12

- `vendor/bin/pest` passes on the latest Laravel and Boost installed locally and on the lowest Boost determined in task 5. Evidence: command output.
- With the fixture and `aiGuidelines = ['towebgmbh/laravel-kit']`, `Guidelines::compose()` contains the key `towebgmbh/laravel-kit/core`; with `aiGuidelines = []` it does not; `Guidelines::text()` of the collection contains `=== towebgmbh/laravel-kit/core rules ===`. Evidence: `tests/Feature/BoostDiscoveryTest.php`.
- `config('kit.stack')` is `blade`, `config('kit.hooks.docs_guard')` is `true` and `config('boost.enforce_tests')` is `true` without project config; a pre-set `false` stays `false`. Evidence: `tests/Feature/ServiceProviderTest.php`.
- No client name, customer data or real project name in the files of this package or its commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/BoostDiscoveryTest.php`: kit guideline discovered via the fixture; absent when not selected in `aiGuidelines`; `Guidelines::text()` output; fixture `destroy()` leaves the package root intact.
- `tests/Feature/ServiceProviderTest.php`: config defaults merged; `boost.enforce_tests` defaults to true; a pre-set `false` stays `false` (dedicated test case with `defineEnvironment`); `kit-config` publish tag registered.

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`
- Once: `composer require "laravel/boost:2.10.0" -W && vendor/bin/pest`, then back to the latest Boost (`composer require "laravel/boost:^2.10" -W` or the raised constraint).

## Out of scope

- Guideline content, overrides and contradiction tests (package 3).
- Stack guideline, `StackDetector` and the context budget test (package 4).
- Skills and commands (packages 5 to 12).

## Report

Testbench harness in place: tests/TestCase.php (Boost, Roster and Kit providers, env local), tests/Support/BoostFixture.php (throw-away project with composer.json/lock, CLAUDE.md, AGENTS.md, boost.json, optional package.json/package-lock.json, vendor symlink to the package), tests/Support/Guidelines.php (compose/text/kitOnly/lineCount via GuidelineComposer), placeholder resources/boost/guidelines/core.blade.php. BoostDiscoveryTest and ServiceProviderTest prove discovery, selection filtering, enforce-tests and config defaults. 17 tests green on Boost 2.10.4 and on 2.10.0, so the lowest Boost stays 2.10.0 (no constraint or workflow change).

## Handoff

Use `BoostFixture::create(composerPackages, npmPackages, boostJson, pest)` + `Guidelines::compose($fixture, enforceTests, packages)`; always `$fixture->destroy()` in afterEach. Packages can be given as names (version from InstalledVersions, fallback 1.0.0) or name => version (e.g. ['livewire/livewire' => '4.0.0'], ['@inertiajs/vue3' => '3.0.0']). Lock entries use "v"-prefixed versions; composer.json constraints are "^x.y.z". laravel/framework in the lock takes app()->version(). `activate()` sets the base path and forgets ProjectManager so Roster rescans; GuidelineComposer is resolved fresh each call. Boost registers just by setting $app['env']='local' in defineEnvironment (no manual singleton binding needed); `boost:update` is available via Artisan. The fixture writes no `.ai/` dir; put overrides with `$fixture->put('.ai/guidelines/...', ...)`. tests/Pest.php binds Tests\TestCase to Feature and Drift; Unit tests run without the app. tests/Feature/.gitkeep removed; tests/Drift/.gitkeep remains until package 12. Package 3 replaces the placeholder core.blade.php (BoostDiscoveryTest only checks key and header, not content). Laravel 12 not run locally (only Testbench 11 installed); CI matrix covers it. D-1: diff and commit messages checked, only generic names (fixture project is `example/app`).

## Decisions

- Lowest Boost stays 2.10.0: the full suite passed on it (with Roster 1.0.0), so composer.json ^2.10 and the workflow 'lowest' pin are unchanged.
- Boost registers in tests by setting env 'local' in defineEnvironment; no fallback binding of Boost singletons was needed (documented in TestCase).
- BoostFixture::create accepts package lists as names or name => version; versions default to the installed version via Composer\InstalledVersions.
- The pre-set enforce_tests=false case is a PHPUnit class test (ServiceProviderOwnEnforceTestsTest) with defineEnvironment; it also re-registers KitServiceProvider with force to prove the provider keeps false.
- Added BoostDiscoveryTest cases checking that the 'tests' (enforce-tests) guideline renders only with enforceTests true, and that boost:update is registered.

## Checks

- `vendor/bin/pest`: exit 0, 17 passed (44 assertions) on Boost 2.10.4
- `vendor/bin/pint --test`: exit 0, passed
