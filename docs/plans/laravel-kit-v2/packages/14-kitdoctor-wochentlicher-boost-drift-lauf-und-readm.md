<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 14: kit:doctor, wöchentlicher Boost-Drift-Lauf und README

Kind: implement · Status: pending · Model: sonnet · Effort: medium · Commits: – · Verdict: –

## Spec

## Goal

Implement `php artisan kit:doctor`, the weekly workflow that fails when Boost changes a file the kit overrides, and the public README for v2.

Public repository (D-1): no client names, no real project names, generic examples only. English (D-2).

## Context to read

- Concept sections Users and flows (all), Design (Manifest, Overrides und Testlinie, Doku-Layouts, Claude-Extras, Öffentliches Repo), Decisions D-1, D-13, D-14, D-24, D-27, D-31, D-32 to D-38, D-41, Risks.
- `src/Manifest.php`, `src/Overrides.php` (`BOOST_VERSION`, `installedBoostHash()`, `installedBoostVersion()`, `installedBoostIsOlderThanReference()`), `src/Kit.php`, `src/Support/ComposerPackages.php`, `src/Console/*`, `config/kit.php`, `.github/workflows/tests.yml`, current `README.md`, `resources/legacy/v1/README.md` (tone and structure reference; do not copy its example service names).

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `src/Console/DoctorCommand.php` (new): `kit:doctor`, prints one line per check, prefixed `[OK]`, `[WARN]` or `[FAIL]`, and returns `FAILURE` when any `[FAIL]` exists (D-27):
   - Boost: `Overrides::installedBoostVersion()`; `[OK] Laravel Boost <version>`; below 2.10 -> `[FAIL] Laravel Boost <version> is older than 2.10.`
   - `boost.json` missing or invalid -> `[FAIL] boost.json is missing. Run php artisan boost:install.`; package not in `packages` -> `[FAIL] towebgmbh/laravel-kit is not active in boost.json. Run php artisan kit:install.`; else `[OK] towebgmbh/laravel-kit is active in boost.json`.
   - Manifest missing -> `[FAIL] .ai/kit-manifest.json is missing. Run php artisan kit:install.`
   - `config('kit.stack')` not in `Kit::STACKS` or `config('kit.architecture')` not in `Kit::ARCHITECTURES` -> `[FAIL] Invalid kit.<key> value "<value>".`
   - v1 leftovers: any `*.md` in `.claude/rules/` or `.claude/.kit-version` -> `[WARN] Leftover v1 files: <paths>. Run php artisan kit:upgrade.`
   - Pest: `.ai/guidelines/pest/core.blade.php` exists but `ComposerPackages::has(base_path(), 'pestphp/pest')` is false -> `[WARN] .ai/guidelines/pest/core.blade.php exists but Pest is not installed. Run php artisan kit:update.` (D-37).
   - Drift (D-41): when `Overrides::installedBoostIsOlderThanReference()` is true, print `[WARN] Laravel Boost <version> is older than <Overrides::BOOST_VERSION>, the version the kit overrides were written for. Run composer update laravel/boost.` and skip the hash comparison. Otherwise, for each manifest override, `Overrides::installedBoostHash(boost_file)` differs from `boost_sha256` -> `[FAIL] Override drift: <override path> was written for a different version of <boost_file>. Update the kit and run php artisan kit:update.`; equal -> `[OK] Override <path> matches Boost`.
   - Docs size: flat layout (no `docs/README.md`) and the number of domains (direct subfolders of `app/` containing `Actions`, `Models` or `Services`) is at least `config('kit.docs.split_threshold')` -> `[WARN] docs/ is flat with <n> domains (threshold <t>). Run /kit-docs-split.`
   Register in `KitServiceProvider`.
2. `tests/Drift/BoostOverrideDriftTest.php` (new): skipped unless `getenv('KIT_BOOST_DRIFT')` is `1` (D-31); for each `Overrides::all()` entry, expects `Overrides::installedBoostHash()` to equal `boost_sha256`, failure message naming the Boost file and the installed Boost version and telling to update the stub, the reference hash and `Overrides::BOOST_VERSION`.
3. `.github/workflows/boost-drift.yml` (new): `on: schedule: cron "0 6 * * 1"` and `workflow_dispatch`; PHP 8.4; `composer update --prefer-dist --no-interaction --with-all-dependencies` (latest Boost); `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift`. Comment in the file: GitHub runs scheduled and manual workflows only from the default branch.
4. `README.md` (rewrite): sections "Requirements" (PHP 8.3+, Laravel 12 or 13, Laravel Boost 2.10+ set up with `boost:install` (the overrides are written for the Boost version in `Overrides::BOOST_VERSION`; `kit:doctor` warns on older versions), Pint 1.27+ for the hook; Pest recommended, PHPUnit supported), "Installation" (`composer require --dev towebgmbh/laravel-kit`, `php artisan kit:install`, then `/kit-onboard`), "What you get" (lean always-loaded guidelines within a 200-line budget, the size rule small/medium/large, the overrides and the test line, the skills, Claude Code Stop hooks, docs layouts), "Commands" (table and one paragraph per command with options), "Configuration" (`config/kit.php` keys and values including `hooks.docs_guard`; note that the kit sets `boost.enforce_tests` to true unless the project sets it, and that `pest/core` is only written with Pest), "Skills" (exactly the five skills: `kit-onboard`, `kit-spec`, `kit-review`, `kit-brief`, `kit-docs-split`), "Claude Code hooks" (Pint once per turn, the structural docs guard and how to switch it off), "Updating" (`composer update towebgmbh/laravel-kit`, `php artisan kit:update`, `kit:doctor`), "Upgrading from v1" (require, `kit:upgrade --dry-run`, `kit:upgrade`, what is deleted, archived and moved, the report, run the test suite; v1's `/test` and `/migrate` are covered by the test line and `/kit-onboard`), "How it works with Boost" (third-party package, `boost.json` packages, `CLAUDE.md` vs `AGENTS.md`, overrides and drift check), "Development" (`composer test`, `composer lint`, `scripts/smoke.sh`), "Legacy", "License". Remove the "Status" note.

## Acceptance criteria

Serves: AC-8, AC-10, AC-11, AC-12

- In a fixture installed with `kit:install` and with each manifest `boost_sha256` set to `Overrides::installedBoostHash()` of the installed Boost, `kit:doctor` exits 0 without any `[FAIL]` line: with the installed Boost at or above `Overrides::BOOST_VERSION` it prints only `[OK]` lines, below it the D-41 warning (branch the expectation on `Overrides::installedBoostIsOlderThanReference()`, so the test passes on the lowest and the latest Boost in CI). Evidence: `DoctorCommandTest`.
- After changing one override's `boost_sha256` to 64 zeros, `kit:doctor` exits 1 and prints `[FAIL] Override drift` when the installed Boost is not older than `Overrides::BOOST_VERSION`; on an older Boost it prints the D-41 warning and exits 0. Evidence: `DoctorCommandTest`.
- With `.claude/rules/01-architecture.md` present it prints the leftover warning; with a `pest/core` override in a fixture without Pest it prints the Pest warning; with four domains and flat docs it prints the docs warning; with the package missing from `boost.json` it fails. Evidence: `DoctorCommandTest`.
- `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift` passes on the installed Boost when it equals `Overrides::BOOST_VERSION` and fails when a reference hash is altered (verified once locally, not committed). Evidence: command output in the handoff.
- `boost-drift.yml` has the triggers `schedule` and `workflow_dispatch`. Evidence: file.
- README contains the sections "Installation", "Commands", "Updating" and "Upgrading from v1" and lists exactly five skills. Evidence: file.
- No client name, customer data or real project name in the README, the files of this package or the commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Commands/DoctorCommandTest.php`: the scenarios above.
- `tests/Drift/BoostOverrideDriftTest.php` (task 2).

## Checks to run

- `vendor/bin/pest`
- `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift`
- `vendor/bin/pint --test`

## Out of scope

- `scripts/smoke.sh` and the `smoke` CI job (package 13); README only names the script.
- PHPStan/Larastan, reviewer subagents, browser checks (v2.1).
- Tag and Packagist (D-17).
