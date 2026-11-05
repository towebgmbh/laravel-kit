<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 1: Grundgerüst: composer.json, Kit, Config, Provider, Manifest, Pint und CI

Kind: implement · Status: done · Model: sonnet · Effort: medium · Commits: `839cc85…c7407dc` · Verdict: –

## Spec

## Goal

Create the Composer package skeleton of `towebgmbh/laravel-kit` v2: `composer.json`, the `Kit` class, `config/kit.php`, the service provider, the `Manifest` class with unit tests, Pint config and the CI matrix. Package 2 adds the Testbench harness on top.

Public repository (D-1): never write client names, customer data or real project names in code, tests, docs or commit messages. All kit content in English (D-2).

## Context to read

- Concept sections Design (Paket, Konfiguration, Overrides und Testlinie, Manifest, Öffentliches Repo), Decisions D-1, D-3, D-7, D-18, D-28, D-42.
- `README.md`, `.gitignore`, `resources/legacy/v1/` (already present, do not change).

Tooling: if `php` or `composer` is not on PATH, run `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"` first.

## Tasks

1. `composer.json` (new): name `towebgmbh/laravel-kit`, type `library`, license `MIT`, description from `README.md`. `require`: `php` `^8.3`, `illuminate/support` `^12.0|^13.0`, `illuminate/console` `^12.0|^13.0`, `laravel/boost` `^2.10`, `sebastian/diff` `^6.0|^7.0|^8.0|^9.0` (D-28), `symfony/process` `^7.2|^8.0`. `require-dev`: `orchestra/testbench` `^10.0|^11.0`, `pestphp/pest` `^3.8|^4.1|^5.0`, `laravel/pint` `^1.27`. Autoload `Towebgmbh\\LaravelKit\\` -> `src/`, autoload-dev `Tests\\` -> `tests/`. `extra.laravel.providers`: `Towebgmbh\\LaravelKit\\KitServiceProvider`. Scripts `test` (`pest`), `lint` (`pint`), `test:lint` (`pint --test`). `config.allow-plugins.pestphp/pest-plugin: true`, `sort-packages: true`, `minimum-stability: stable`, `prefer-stable: true`. The `bin` entry is added by package 8.
2. `src/Kit.php` (new): `final class Kit` with `public const VERSION = '2.0.0'`, `PACKAGE = 'towebgmbh/laravel-kit'`, `STACKS = ['blade', 'livewire', 'inertia-vue', 'inertia-react', 'api']`, `ARCHITECTURES = ['actions', 'services', 'migrating']`, and `public static function path(string $path = ''): string` returning the absolute path inside the package root.
3. `config/kit.php` (new): keys `stack` (default `'blade'`), `architecture` (default `'actions'`), `docs` => `['split_threshold' => 4]`, `hooks` => `['docs_guard' => true]`, each with a short English comment listing the allowed values. No `env()` calls (the docs guard includes this file outside Laravel).
4. `src/KitServiceProvider.php` (new): `register()` merges `config/kit.php` under `kit` and, when `config('boost.enforce_tests')` is `null`, sets it to `true` (D-7: Boost then always renders `enforce-tests`, which carries the kit test line); `boot()` publishes the config with tag `kit-config` when running in console and has an (empty for now) command list that later packages fill via `$this->commands([...])`.
5. `src/Manifest.php` (new): `final class Manifest` for `.ai/kit-manifest.json` with `PATH = '.ai/kit-manifest.json'`, `static load(string $basePath): self` (empty manifest when the file is missing), `exists()`, `version()`/`setVersion()`, `project()`/`setProject(string)` (`new` or `existing`, else `InvalidArgumentException`), `recordFile(string $relativePath, string $contents)`, `fileHash(string $relativePath): ?string`, `forgetFile()`, `files(): array`, `recordOverride(string $relativePath, string $boostFile, string $boostSha256)`, `forgetOverride()`, `overrides(): array`, `save()` (creates `.ai/`, pretty JSON, keys sorted, unescaped slashes, trailing newline) and `static hash(string $contents): string` (sha256 after normalising CRLF to LF). JSON shape: `{"version": "2.0.0", "project": "new", "files": {"<path>": {"sha256": "..."}}, "overrides": {"<path>": {"boost_file": "...", "boost_sha256": "..."}}}`.
6. `phpunit.xml` (new, suites `Unit`, `Feature`, `Drift` pointing to `tests/Unit`, `tests/Feature`, `tests/Drift`) and `tests/Pest.php` (new, empty apart from a comment; package 2 binds the Testbench `TestCase` for `Feature` and `Drift`).
7. `pint.json` (new): preset `laravel`. `.github/workflows/tests.yml` (new): on `push` and `pull_request`; job `tests` with matrix `php: [8.3, 8.4]`, `laravel: [12, 13]`, `boost: [lowest, latest]` (include testbench `^10.0` for 12, `^11.0` for 13); steps: checkout, `shivammathur/setup-php` (coverage none), `composer require "laravel/framework:^${{ matrix.laravel }}.0" "orchestra/testbench:${{ matrix.testbench }}" --dev --no-update`, for `lowest` `composer require "laravel/boost:2.10.0" --no-update` (package 2 may raise this version), `composer update --prefer-dist --no-interaction --with-all-dependencies`, `vendor/bin/pest`. Job `pint`: PHP 8.4, `composer install`, `vendor/bin/pint --test`. Keep `composer.lock` ignored as in `.gitignore`.

## Acceptance criteria

Serves: AC-1, AC-12

- `composer install` and `vendor/bin/pest` pass locally. Evidence: command output.
- `Manifest` round-trips its JSON and hashes CRLF and LF content identically. Evidence: `tests/Unit/ManifestTest.php`.
- The workflow file is valid YAML and lists the full matrix. Evidence: file review.
- No client name, customer data or real project name in the files of this package or its commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Unit/ManifestTest.php`: empty manifest when missing; save and load round trip; `recordFile`/`fileHash`; `recordOverride`/`forgetOverride`; invalid project value throws; CRLF/LF hash equality.

## Checks to run

- `composer validate --strict`
- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- Testbench `TestCase`, `BoostFixture`, `Guidelines` helper, the placeholder guideline, the provider test and the lowest Boost version (package 2).
- Guideline content and overrides (package 3), stack (package 4), skills (packages 5 and 6), commands (packages 7, 9, 11, 12), `bin/kit-docs-guard` (package 8), smoke script (package 13), README rewrite (package 12).

## Report

Package skeleton created and committed (c7407dc): composer.json, Kit, config/kit.php, KitServiceProvider, Manifest with unit tests, phpunit.xml, tests/Pest.php, pint.json and the CI workflow with the full matrix. composer validate, pest (7 tests) and pint --test are green locally.

## Handoff

Files: composer.json, src/Kit.php, src/KitServiceProvider.php (register merges config and sets boost.enforce_tests=true if null; boot publishes tag kit-config and has an empty $this->commands([]) list for later packages), src/Manifest.php (save writes keys sorted: files, overrides, project, version; version defaults to Kit::VERSION; project may be null until set), config/kit.php, phpunit.xml (suites Unit, Feature, Drift), tests/Pest.php (comment only; package 2 binds the Testbench TestCase), tests/Feature/.gitkeep and tests/Drift/.gitkeep (remove when real tests land), pint.json (laravel preset), .github/workflows/tests.yml (jobs tests with matrix php 8.3/8.4 x laravel 12/13 x boost lowest/latest, and pint). Lowest boost is pinned to 2.10.0 in the workflow; package 2 may raise it. composer.lock is gitignored. Local PHP is 8.5 (Herd; PATH export needed); the hook blocks running a freshly created script in the same Bash call as writing it, so split such calls. Watch: Manifest::files() returns the in-memory order, only the saved JSON is sorted (tests use toEqual). D-1 check: diff and commit message contain no client names or project data.

## Decisions

- Manifest save() output has top-level keys sorted alphabetically (files, overrides, project, version) and empty files/overrides are written as {} objects.
- Manifest::load throws InvalidArgumentException for a manifest file that is not valid JSON.
- Added tests/Feature/.gitkeep and tests/Drift/.gitkeep so the phpunit suite directories exist.
- Added the ManifestTest afterEach cleanup with is_file/is_dir guards to avoid warnings.

## Checks

- `composer validate --strict`: exit 0, composer.json is valid
- `vendor/bin/pest`: exit 0, 7 passed (20 assertions)
- `vendor/bin/pint --test`: exit 0, passed
