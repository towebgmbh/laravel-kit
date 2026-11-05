<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 14: Paket 10 erneut reviewen

Kind: review · Status: pending · Model: opus · Effort: high · Commits: – · Verdict: –

## Spec

## Goal

Review packages 3, 4, 7, 8 and 9 before the upgrade work builds on them: the guideline text is lean, correct and contradiction-free and stays within the context budget, the size rule keeps small work light, the overrides take effect (also without tests in the project, and `pest/core` only with Pest), `kit:install` renders the chosen stack and architecture in the same run, `kit:install` and `kit:update` never overwrite without asking, and the Stop hooks behave as specified. Report blocking findings with file and line; do not change code.

## Context to read

- The whole concept, especially Design, Decisions D-1, D-4 to D-15, D-19 to D-25, D-28, D-32 to D-37, D-40 and Acceptance AC-2 to AC-6, AC-12 to AC-14.
- Handoffs of packages 1 to 9.
- `resources/boost/guidelines/*`, `stubs/ai/guidelines/*`, `stubs/config/kit.php.stub`, `src/Overrides.php`, `src/KitServiceProvider.php`, `src/Support/*`, `src/Hooks/DocsGuard.php`, `bin/kit-docs-guard`, `stubs/claude/hooks/kit-docs-guard.sh`, `src/Console/InstallCommand.php`, `src/Console/UpdateCommand.php`, `resources/boost/skills/*`, all tests under `tests/`.
- Boost texts: `vendor/laravel/boost/.ai/foundation.blade.php`, `.ai/enforce-tests.blade.php`, `.ai/pest/core.blade.php`, `.ai/phpunit/core.blade.php`, `.ai/laravel/skill/testing-best-practices/`, `.ai/laravel/skill/laravel-best-practices/`; `src/Console/InstallCommand.php` (`determineTestEnforcement()`); `src/Install/GuidelineComposer.php` (`getUserGuidelines()`); `src/Install/Agents/ClaudeCode.php` (`guidelinesPath()`).

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. Content: compare each kit guideline with Boost's texts and skills. Blocking: a kit sentence that contradicts Boost or the test line; a repeated framework API rule (D-4); the D-6 clarification sentences missing; a marker sentence missing; Laravel 12/13 model branch wrong; any rule that forces a spec, plan or docs update on small work (D-32); details that belong in a skill (D-35); `record-rule` used without the fallback.
2. Budget: `ContextBudgetTest` covers all 30 combinations and the limit is 200; the four non-stack guidelines stay at or below 150 lines.
3. Overrides: `pest/core` keeps every Boost bullet except the replaced "Running Tests" ones and keeps `@scoped`; `enforce-tests` carries the full test line and works for PHPUnit too (D-7, D-10); `KitServiceProvider` only sets `boost.enforce_tests` when it is `null`; `Overrides::BOOST_VERSION` and the hashes match that Boost version; `pest/core` is never written, managed or recorded without Pest (D-37).
4. `kit:install`: walk every step against package 7's spec; the running config is set when `config/kit.php` was created in the same run and left alone otherwise (D-40); no path writes without `ManagedFiles` or `writeOnce`; non-interactive runs never replace differing files; `boost.json` keeps other keys and has no duplicate package; errors return `FAILURE`.
5. Hooks: `ClaudeSettings` writes only `Stop` hooks and preserves unknown keys and order; `DocsGuard` implements exactly the structural triggers of D-36, ignores edits of tracked files, handles the `?bool` of `$isTracked`, honours `hooks.docs_guard`, fails open on every error path; `bin/kit-docs-guard` always exits 0; the shell wrapper exits 0 without vendor.
6. `kit:update`: unchanged vs edited detection uses the manifest hash; diff shown before asking; manifest updated only for written or identical files; Pest added/removed handled per package 9.
7. Dependencies: `composer.json` allows `sebastian/diff` 6 to 9 and requires `symfony/process`.
8. Tests: each criterion of AC-2 to AC-6, AC-13 and AC-14 has a test that would fail if the behaviour broke (spot-check by reasoning, not by editing).
9. Public repo (AC-12): run the name searches below; any hit is blocking.

## Acceptance criteria

Serves: AC-2, AC-3, AC-4, AC-5, AC-6, AC-12, AC-13, AC-14

- The verdict is `pass` or `blocking` with numbered findings naming file and line and the violated decision or AC. Evidence: the report.
- Every check command below is reported with its exit code. Evidence: the report's checks.

## Tests to write

None; this package only reviews.

## Checks to run

- `composer validate --strict`
- `vendor/bin/pest`
- `vendor/bin/pint --test`
- `git grep -n -i -E "<name list>"` (expected: no output, exit 1)
- `git log --format=%B | grep -i -E "<name list>"` (expected: no output, exit 1)

## Out of scope

- Fixing findings (a fix package follows when the verdict is blocking).
- Packages 11 to 13 (not built yet).

Check that the findings of the previous review were fixed.
