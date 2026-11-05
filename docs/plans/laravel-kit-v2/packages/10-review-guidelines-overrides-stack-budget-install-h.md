<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 10: Review: Guidelines, Overrides, Stack, Budget, Install, Hooks und Update

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

## Report

Reviewed packages 3, 4, 7, 8 and 9 against the plan and the package specs. composer validate, Pest (191 passed, 669 assertions) and Pint are green. The code meets the spec: the guideline content (size rule, D-6 sentences, record-rule fallback, L12/L13 model branch), the budget (30 combinations within 200 lines, non-stack guidelines within 150), the overrides (pest/core keeps every Boost 2.10.4 bullet and @scoped, the hashes match, Pest-only per D-37), kit:install (D-40, no unasked overwrite, boost.json without duplicates), ClaudeSettings, DocsGuard, kit:update and the dependencies. The verdict is blocking for one reason: the AC-12 name search `git grep` returns hits. The customer name list is in tracked plan files under docs/plans/laravel-kit-v2/packages/ (specs 10, 13 and 14) and would be published with the branch history of this public repo. The commit message search is clean. There are no code findings that block.

## Handoff

Code review result for packages 3, 4, 7, 8 and 9: no blocking code findings. The guidelines, overrides, budget, kit:install, hooks and kit:update match the specs and AC-2 to AC-6, AC-13 and AC-14. Pest 191/191, Pint and composer validate are green on Laravel 13.40.0 with Boost 2.10.4.
The only blocking issue is AC-12: the customer name search pattern sits in tracked plan files (docs/plans/laravel-kit-v2/packages/10, 13, 14), so `git grep` with the pattern hits, and the names would be published with the branch history. This needs a decision by Jarvis or the owner on how plan files and the name list stay out of the public repo, and the history must be cleaned before the first push. A code fix package cannot solve it alone, because docs/plans must not be edited by package agents.
Watch for packages 11 and 12: reuse ManagedFiles/StrictUnifiedDiffOutputBuilder as handed off. Consider findings 3 (override hash kept on skip) and 5 (catch an invalid manifest) when building kit:doctor and kit:upgrade. Package 13 and the final review will hit the same git grep issue until finding 1 is resolved; the search there should run against a tree and history without docs/plans.

## Decisions

- Treated the git grep hits inside docs/plans (excluded from the reviewed diff) as blocking, because the spec says any hit of the AC-12 search is blocking and the files are tracked in the public repo's branch history.
- Graded the DocsGuard commit-in-turn gap as non-blocking: the behaviour follows D-19 (new = untracked via git ls-files) as specified.
- Did not run the shell wrapper by hand; its exit-0 path without vendor was verified by reading the script, and the binary is covered by DocsGuardTest.
- Only Laravel 13.40.0 / Boost 2.10.4 are installed locally; the Laravel 12 and lowest-Boost branches were reviewed by reading, not by running the matrix.
- Ran Pest with --compact for the reported check (same suite, same result as the earlier full-output run).
- The commit message search reports exit 0 as the Bash tool recorded it: the tool treats a grep without matches as success; the command printed nothing, so there were no matches (an earlier run with an explicit exit echo showed grep's own exit 1).

## Checks

- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" composer validate --strict`: exit 0, ./composer.json is valid
- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" vendor/bin/pest --compact`: exit 0, 191 passed (669 assertions)
- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" vendor/bin/pint --test`: exit 0, pint passed
- `git grep -n -i -E "<name list>"`: exit 0, 6 hits, all in tracked plan specs docs/plans/laravel-kit-v2/packages/10, 13, 14 (the search pattern itself); expected no output and exit 1
- `git log --format=%B | grep -i -E "<name list>"`: exit 0, no output: no customer names in commit messages (tool records grep's no-match as success)

## Findings

1. [BLOCKING, AC-12 / D-1, data exposure] docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md:50-51, 13-rauchtest-an-frischer-laravel-13-app-install-und-v.md:52-53, 14-schluss-review-gegen-alle-abnahmekriterien.md:52-53: these tracked files contain the full list of customer and project names used in the AC-12 search pattern, so `git grep -n -i -E "<name list>"` exits 0 with 6 hits. The concept says the name list lives "only in the specs, never in the repo", but the specs are committed to this public repository (commits "docs(plan): update laravel-kit-v2"). Pushing the branch would publish the names even if a later commit deletes the files. Nothing is public yet because no push has happened. Fix (needs Jarvis or the owner, because package agents may not edit docs/plans): keep the name list out of every tracked file, for example by moving the search pattern to a file outside the worktree or into Jarvis-only context. Then remove the list from the plan files, or remove docs/plans from the branch, and rewrite or squash the branch history before the first push, so no commit contains the names. Repeat the check afterwards: `git grep` must exit 1, and so must `git log -p | grep -i -E "<pattern>"` over the history.

Non-blocking findings (no change needed for the verdict):
2. src/Hooks/DocsGuard.php:206-229 (D-19/D-36 design limit): "new" means untracked at Stop time. If the agent commits inside the turn (which the core guideline encourages: "Each phase is committable"), a new app/, resources/js/ or resources/views/ file is already tracked and the guard lets the turn through. Suggestion: also count a path as new when it was added since the turn started (for example `git log --diff-filter=A` since the time of the prompt's transcript entry).
3. src/Console/UpdateCommand.php:104-105 (and InstallCommand.php:256-257, documented decision of package 7): recordOverride stores the new reference boost_sha256 even when the user keeps an edited override (SKIPPED). kit:doctor then measures drift against the new reference, although the local file is based on the older Boost text. Consider keeping the previous override entry when the write is skipped.
4. resources/boost/guidelines/quality.blade.php:19 "Validate every input with a Form Request" conflicts with the Livewire variant (stack.blade.php:19 "A component validates input") and with Boost's livewire/core ("Validate and authorize in actions"). Suggestion: "Validate every input (Form Requests for controllers, component validation in Livewire)".
5. src/Console/InstallCommand.php:81 and src/Console/UpdateCommand.php:37: Manifest::load throws InvalidArgumentException on an invalid .ai/kit-manifest.json. The commands do not catch it, so the user sees an exception trace instead of an error line and FAILURE.
6. stubs/ai/guidelines/enforce-tests.blade.php:3: "- Follow the Laravel Kit test line:" is a bullet followed by sibling bullets; the rules read better as a heading or nested list. Boost's "run the affected tests and ensure they pass" was dropped. Quality's DoD and Boost's phpunit/pest core cover it, so this is only cosmetic.
7. tests/Feature/Commands/UpdateCommandTest.php:89-99: the interactive test does not assert that the diff is printed before the confirmation (AC-5 "shows the diff and asks"). The code does print it first (UpdateCommand.php:157), but nothing would catch a regression.
