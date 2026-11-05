<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 12: Paket 10 erneut reviewen

Kind: review · Status: done · Model: opus · Effort: high · Commits: none · Verdict: blocking

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

## Report

I re-reviewed the fix commit 97b6ceb against the package 10 findings and the specs of packages 3, 4, 7, 8 and 9. Package 11 fixed code findings 2 to 7 correctly and with tests. Pest passes 196/196 (688 assertions), and Pint and composer validate --strict are green. The code has no blocking findings. The verdict stays blocking for the same reason as before, AC-12: the spec counts any hit of the git grep name search as blocking, and the search still hits (11 lines). All hits are in tracked plan files under docs/plans/laravel-kit-v2/packages/ (10, 11, 12, 15, 16), which hold the name pattern itself. Outside docs/plans the tree has no hits, and the commit messages are clean. A code fix package cannot resolve this, because agents may not edit docs/plans and may not rewrite history.

## Handoff

The code from packages 3, 4, 7, 8, 9 and 11 is ready to build on. Package 11's fixes are verified:
- DocsGuard counts a tracked file under app/, resources/js/ or resources/views/ as new when a commit since the prompt's transcript timestamp added it (git log --diff-filter=A --since=<epoch>).
- When the user keeps an edited override (SKIPPED) and an entry exists, install and update keep its recorded boost_sha256. kit:doctor (package 14) should measure drift against that stored value.
- An invalid manifest gives an error line and FAILURE in kit:install and kit:update. kit:upgrade and kit:doctor should catch Manifest::load's InvalidArgumentException the same way.
- The quality security rule now names Livewire component validation.
- The enforce-tests lead-in is a paragraph and has the "run the affected tests" bullet back.
- The interactive UpdateCommandTest asserts the diff header.

Still open, blocking AC-12: the customer and project name pattern sits in tracked files docs/plans/laravel-kit-v2/packages/10, 11, 12, 15 and 16, and in the branch history of those files. Jarvis or the owner has to fix this, a package agent cannot:
- Keep the name list out of every tracked file, for example with an untracked pattern file or Jarvis-only context.
- Remove docs/plans from the branch or strip the list from it.
- Squash or rewrite the branch history before the first push.

Until then, every later review (package 13 onwards, final review) will hit the same search. Also run the name search with `-- . ':(exclude)docs/plans'` there, so real code hits stay visible.

## Decisions

- Kept the verdict blocking for the AC-12 hits in docs/plans, because the spec says any hit of the name search is blocking; the code itself has no blocking finding.
- Also searched the tree outside docs/plans, and earlier the history outside docs/plans (git log -p): no hits.
- Ran Pest with --compact for the reported check, the same suite as the earlier full-output run (196 passed both times).
- I could not run the shell wrapper stubs/claude/hooks/kit-docs-guard.sh: the Jarvis guard blocks running it. It is unchanged since package 10 and I checked by reading that it exits 0 without vendor/bin/kit-docs-guard.
- Only Laravel 13 with the installed Boost 2.10.4 runs locally; I reviewed the Laravel 12 and lowest-Boost paths by reading, not by running the matrix.

## Checks

- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" composer validate --strict`: exit 0, ./composer.json is valid
- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" vendor/bin/pest --compact`: exit 0, 196 passed (688 assertions)
- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" vendor/bin/pint --test`: exit 0, pint passed
- `git grep -n -i -E "<name list>"`: exit 0, 11 hits, all in docs/plans/laravel-kit-v2/packages/10, 11, 12, 15, 16 (the search pattern itself); expected no output and exit 1
- `git log --format=%B | grep -i -E "<name list>"`: exit 0, no output: commit messages clean (the Bash tool records grep's no-match as success; an earlier run with echo showed grep exit 1)
- `git grep -n -i -E "<name list>" -- . ':(exclude)docs/plans'`: exit 0, no output: no hits outside docs/plans (tool records no-match as success; earlier run showed exit 1)

## Findings

1. [BLOCKING, AC-12 / D-1, data exposure, carried over from package 10 finding 1] The tracked plan specs hold the full list of customer and project names in the AC-12 search command. Files and lines:
   - docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md:50-51, 82-83, 87
   - docs/plans/laravel-kit-v2/packages/11-review-befunde-von-paket-10-beheben.md:13
   - docs/plans/laravel-kit-v2/packages/12-paket-10-erneut-reviewen.md:50-51
   - docs/plans/laravel-kit-v2/packages/15-rauchtest-an-frischer-laravel-13-app-install-und-v.md:52-53
   - docs/plans/laravel-kit-v2/packages/16-schluss-review-gegen-alle-abnahmekriterien.md:52-53

   `git grep -n -i -E "<pattern>"` exits 0 with 11 hits, all in these files. The commits "docs(plan): update laravel-kit-v2" commit them to the branch of the public repo, so a push would publish the names. Outside docs/plans, `git grep` finds nothing, and commit messages are clean.

   Fix (Jarvis or the owner, not a package agent, because docs/plans must not be edited by packages):
   - Keep the pattern out of every tracked file, for example in an untracked file outside the worktree or in Jarvis-only context.
   - Remove docs/plans from the branch or strip the list from it.
   - Rewrite or squash the branch history before the first push.

   Afterwards both `git grep` and `git log -p | grep -i -E "<pattern>"` must exit 1.

Non-blocking findings (no change needed for the verdict):
2. tests/Feature/Commands/UpdateCommandTest.php:93-94: `expectsOutputToContain('+++ … (kit)')` proves the diff is printed in the interactive run. Laravel's PendingCommand does not enforce order against `expectsConfirmation`, so a regression that prints the diff after the question would still pass. The code prints it first.
3. src/Hooks/DocsGuard.php:248-254: `git log --since` uses the committer date. When the agent rebases or amends during a turn, files from commits rewritten in the turn count as new, and the guard can block once. This fails safe (one extra block per turn, stop_hook_active lets the agent through) and is acceptable.
