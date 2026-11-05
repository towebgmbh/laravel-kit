<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 3: Kit-Guidelines (Kern mit Grössenregel und Doku, Architektur, Konventionen, Qualität) und die zwei Overrides

Kind: implement · Status: pending · Model: opus · Effort: medium · Commits: – · Verdict: –

## Spec

## Goal

Write the kit's always-loaded guidelines (`core`, `architecture`, `conventions`, `quality`) and the two Boost overrides (`enforce-tests` with the kit test line, `pest/core`), and prove through Boost's `GuidelineComposer` that the kit text is present, the overrides replace Boost's texts, the architecture mode and docs layout render correctly, and no contradiction sentence appears. Keep the text lean: these four guidelines together stay at or below 150 lines (the stack guideline of package 4 gets the rest of the 200-line budget, D-35).

Guiding principle (D-32): pragmatism over ceremony; the kit must never slow down small work.

Public repository (D-1): no client names or real project names anywhere, examples stay generic (for example `Billing`, `Invoice`). All content in English (D-2).

## Context to read

- Concept sections Users and flows (3), Design (Einklinken in Boost, Overrides und Testlinie, Kontextbudget, Doku-Layouts, Öffentliches Repo), Decisions D-1, D-4, D-6, D-7, D-9, D-10, D-11, D-24, D-25, D-32 to D-35, D-37.
- Packages 1 and 2; `src/Kit.php`, `src/Manifest.php`, `src/KitServiceProvider.php`, `tests/Support/BoostFixture.php`, `tests/Support/Guidelines.php` (`compose()`, `text(Collection)`, `kitOnly()`, `lineCount()`).
- The v1 rules this content modernises: `resources/legacy/v1/templates/.claude/rules/01-architecture.md`, `02-laravel-conventions.md`, `04-testing.md`, `05-quality.md`, `06-workflow.md`.
- Boost texts: `vendor/laravel/boost/.ai/foundation.blade.php`, `.ai/enforce-tests.blade.php`, `.ai/pest/core.blade.php`, `.ai/boost/skill/infer-conventions/SKILL.blade.php` (its fallback when `record-rule` is unavailable), `src/Mcp/Tools/RecordRule.php` (`record-rule` is an MCP tool), and `src/Concerns/RendersBladeGuidelines.php` (Boost replaces backticks, `@include`, `@props`, `<x-` before `Blade::render`; do not use those as directives; `config()` and `@if`/`@switch` work).
- Boost versions: 2.10.0 to 2.10.3 share one `pest/core` text (it ends with "After the feature tests pass, ask the user to run the complete suite ..."); 2.10.4 has another one without that sentence.

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `resources/boost/guidelines/core.blade.php` (replace the placeholder), in this order:
   - Title `# Laravel Kit` and the principle (the kit sets structure and workflow, Boost the framework APIs per version).
   - Size rule (D-32), starting with the marker sentence "Classify every piece of work as small, medium or large, state the size in one sentence and start without asking; the user may reclassify it at any time." Then: small (fix, adjustment, few files, no new behaviour across layers): implement directly, run only the affected tests, no spec, no plan, docs only when documented behaviour or structure changes. Medium (several layers or existing behaviour): short plan in the chat, implement, update the affected spec entry and docs. Large (new feature, new domain, initiative): spec via `/kit-spec` approved by the user, numbered acceptance criteria, no implementation while a blocking open question is listed in the spec (D-34), then the phases Data, Logic, Routes, Frontend, Integration, each committable, tests written with the code, only affected tests per phase and no full-suite run per phase; tests name the criterion they cover (D-33).
   - Docs (D-11): the two D-6 clarifications verbatim: "Keeping `docs/` up to date is a standing request of the user, so the rule to create documentation only on request does not apply to `docs/`." and "The domain folders listed in the project docs are approved base folders." `@if (file_exists(base_path('docs/README.md')))` the split layout with the marker "Read `docs/README.md` first, then only the affected domains." and "Everything must be reachable from `docs/README.md`; register every new document there."; `@else` the flat layout with the marker "Read `docs/GUIDELINES.md` first." and: when the project reaches `{{ config('kit.docs.split_threshold', 4) }}` domains, run `/kit-docs-split`. Deviations from the kit go to `.ai/rules` with Boost's `record-rule` MCP tool; without it, write the rule file in `.ai/rules` by hand (or report the rule text to the user).
   - One line pointing to the test line in the `enforce-tests` rules (do not repeat it).
2. `resources/boost/guidelines/architecture.blade.php` (new): `@switch(config('kit.architecture', 'actions'))` with three cases. `actions` starts with the marker sentence "Business logic lives in Actions." and covers D-9 (`app/{Domain}/Actions/` with one public `handle()` method, thin singular controllers, DTOs as `final readonly class` above 3 parameters in `app/{Domain}/DTOs/`, Form Requests, Policies via auto-discovery or `#[UsePolicy]`, never unguarded models, enums for fixed values, `app/Support/` for shared code). `services` starts with "This project keeps its Service classes." and describes the existing service pattern without asking to convert. `migrating` starts with "New code goes into Actions; existing Service classes are converted when you change them anyway." Model rules render by framework version: `@if (version_compare(app()->version(), '13.0', '>='))` the Laravel 13 attributes (`#[Table]`, `#[Fillable]`, `#[UsePolicy]`, `#[ObservedBy]`, `#[ScopedBy]`, `#[UseFactory]`) plus `casts()`, else `$fillable` and `casts()`. Do not repeat Eloquent API details Boost provides.
3. `resources/boost/guidelines/conventions.blade.php` (new): structure-only conventions from v1 `02-laravel-conventions.md` (where routes, migrations, jobs, policies, resources, events, notifications live and how they are named per domain), singular controllers; no framework API rules.
4. `resources/boost/guidelines/quality.blade.php` (new): Definition of Done per size (tests per the test line, Pint, docs per the size rule, self-review), security and commit rules from v1 `05-quality.md` that are not framework API rules.
5. `stubs/ai/guidelines/enforce-tests.blade.php` (new): heading `# Test Enforcement`, first bullet the marker "Follow the Laravel Kit test line:", then the test line of D-10, one bullet each, valid for Pest and PHPUnit: a test must find an error fast or rule out follow-up errors; nothing twice (what a feature test covers gets no unit test, except branching logic in an Action); feature tests for endpoints and flows, unit tests for framework-free logic; required: permissions, state transitions, money and quantity calculations, integrations with `Http::fake()`; no tests for framework behaviour, getters or HTML; datasets (data providers in PHPUnit) instead of copies; mock only external services. Then: "Pure copy, styling, and layout-only changes do not require new or updated tests.", "When a spec lists acceptance criteria, name the criterion a test covers (for example `AC-2`) in the test description.", "Read the `testing-best-practices` skill before writing tests."
6. `stubs/ai/guidelines/pest/core.blade.php` (new): start from Boost's `.ai/pest/core.blade.php` of the installed (latest) version, keep the `@php` header, the `@scoped(['tests/**'])` wrapper and all bullets outside "Running Tests". Replace "Running Tests" with: "Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `{{ $assist->artisanCommand('test --compact') }}`.", "Rerun a test after each change to it.", the existing `binCommand('pest')` bullet, "While working, run only the affected tests. Never run the complete suite in between." and "Run the complete suite at most once at the end of a run or task, and only when the change justifies it (shared logic, migrations, config, dependencies): `{{ $assist->artisanCommand('test --parallel --compact') }}`." The sentence "ask the user to run the complete suite" must not appear.
7. `src/Overrides.php` (new): `final class Overrides` with `public const BOOST_VERSION = '<the Boost version the overrides were written against, for example 2.10.4>'`; `public static function all(): array` keyed by project path (`.ai/guidelines/enforce-tests.blade.php`, `.ai/guidelines/pest/core.blade.php`) -> `['stub' => 'stubs/ai/guidelines/...', 'boost_file' => '.ai/enforce-tests.blade.php' | '.ai/pest/core.blade.php', 'boost_sha256' => '<Manifest::hash of that file in BOOST_VERSION>', 'requires' => null | 'pestphp/pest']` (D-37: `pest/core` requires Pest); `stub(string $path): string` (contents); `static installedBoostHash(string $boostFile): ?string` using `Composer\\InstalledVersions::getInstallPath('laravel/boost')`; `static installedBoostVersion(): ?string` (`ltrim(InstalledVersions::getPrettyVersion('laravel/boost'), 'v')`); `static installedBoostIsReference(): bool` (installed equals `BOOST_VERSION`); `static installedBoostIsOlderThanReference(): bool` (`version_compare(installed, BOOST_VERSION, '<')`).
8. `tests/Support/Contradictions.php` (new): `final class Contradictions` with `public const SENTENCES` = "Every Action gets at least one unit test", "ask the user to run the complete suite", "Run `php artisan test` after each phase", "Each Action has exactly one public method: `execute()`", "AuthServiceProvider".
9. Tests copy the override stubs into the fixture's `.ai/guidelines/` (that is what `kit:install` does in package 7).

## Acceptance criteria

Serves: AC-1, AC-2, AC-12, AC-13, AC-14

- For each architecture mode, the composed guidelines contain `towebgmbh/laravel-kit/core`, `/architecture`, `/conventions`, `/quality`, the mode's marker sentence and none of the other two markers. Evidence: `KitGuidelinesTest`.
- `core` contains the size-rule marker sentence and the words "small", "medium", "large", and mentions the `record-rule` fallback. Evidence: `KitGuidelinesTest`.
- Flat and split layouts render their marker sentence depending on `docs/README.md` in the fixture. Evidence: `KitGuidelinesTest`.
- With the override stubs in a fixture with Pest and `enforceTests` true, the `tests` entry's path is the fixture's `.ai/guidelines/enforce-tests.blade.php` and contains "Follow the Laravel Kit test line:"; the `pest/core` entry contains "at most once at the end of a run or task". Without the stubs neither marker appears. Evidence: `OverridesTest`.
- No sentence of `Contradictions::SENTENCES` appears in the composed text in any mode with the stubs in place. Evidence: `KitGuidelinesTest`, `OverridesTest`.
- `core`, `architecture`, `conventions` and `quality` together have at most 150 lines in every mode and layout. Evidence: `KitGuidelinesTest` (`Guidelines::lineCount(Guidelines::text(Guidelines::kitOnly(...)))`).
- `Overrides::all()` hashes match the installed Boost files when `Overrides::installedBoostIsReference()` is true; otherwise that test is skipped with the message naming both versions (the weekly drift test of package 12 enforces it). Evidence: `OverridesTest`.
- No client name, customer data or real project name in the guideline texts, stubs, tests or the commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Guidelines/KitGuidelinesTest.php`: dataset of the three modes (markers, keys, contradictions, 150-line limit); flat vs split dataset; Laravel 13 renders `#[Fillable]`, Laravel 12 renders `$fillable` (branch on `app()->version()`); Pest `toMatchSnapshot()` of `Guidelines::text(Guidelines::kitOnly(...))` per mode, skipped below Laravel 13 (D-25).
- `tests/Feature/Guidelines/OverridesTest.php`: both overrides take effect; without the stubs the kit markers "Follow the Laravel Kit test line:" and "at most once at the end of a run or task" are absent (do not rely on Boost's own sentences, they differ between 2.10.3 and 2.10.4); contradictions absent with stubs; reference hash check as above.

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- `stack.blade.php`, `StackDetector` and `ContextBudgetTest` (package 4).
- Writing overrides into projects, the Pest condition at install time and the manifest entries (package 7).
- The weekly drift test and workflow (package 12).
