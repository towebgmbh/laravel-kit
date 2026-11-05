<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 14: Paket 10 erneut reviewen

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

Re-review after package 13. Package 13 made no commits, so `git diff b6b8454..HEAD` outside docs/plans is empty. The code is the same as in package 12's review, which found no blocking code issue. Pest passes 196/196 (688 assertions), Pint --test and composer validate --strict are green (Laravel 13, Boost 2.10.4). Spot checks confirm: the provider sets boost.enforce_tests only when null, composer.json allows sebastian/diff 6 to 9 and requires symfony/process, ContextBudgetTest covers 5 stacks x 3 modes x 2 layouts with limit 200, and Overrides::BOOST_VERSION matches the installed Boost 2.10.4. The verdict stays blocking for AC-12. The spec counts any hit of the git grep name search as blocking, and it still hits 17 lines in 6 tracked plan files under docs/plans/laravel-kit-v2/packages/. Those files contain the search pattern itself. The tree outside docs/plans, the code history outside docs/plans and all commit messages are clean. No package agent can fix this. Further review/fix rounds will loop until Jarvis or the owner resolves it.

## Handoff

The code from packages 3, 4, 7, 8, 9 and 11 is unchanged since package 12 and ready to build on (196 tests green, Pint clean). Package 12's handoff still applies:
- kit:doctor measures drift against the boost_sha256 stored in the manifest, which is kept when a user skips an edited override.
- kit:upgrade and kit:doctor catch Manifest::load's InvalidArgumentException with an error line and FAILURE.

Open and blocking only for AC-12: the name pattern sits in tracked docs/plans specs (packages 10, 11, 12, 14, 17, 18) and in their history. Only Jarvis or the owner can fix this: take the pattern out of tracked files, including the check text in the specs of 17 and 18, remove or strip docs/plans, and squash or rewrite history before the first push. Another fix/review round by package agents will only repeat this result. I recommend the owner decides it now and then marks this review chain resolved, so package 15 (kit:upgrade) can start. Later name searches should also run with `-- . ':(exclude)docs/plans'` so real hits stay visible.

## Decisions

- Kept the verdict blocking for AC-12 because the spec counts any hit of the name search as blocking; the code has no blocking finding.
- Code diff since b6b8454 outside docs/plans is empty, so I re-verified by running the checks and spot-checking provider, dependencies, budget test and BOOST_VERSION instead of a full line-by-line re-read; package 12 reviewed the same code in full.
- Ran Pest with --compact --colors=never for the reported check (196 passed).
- Ran git grep with -c instead of -n to get per-file counts; line numbers come from an earlier -n run.
- Added two extra searches: tree outside docs/plans and code history (git log -p) outside docs/plans, both without hits.
- The searches without matches are reported with exit 0 as the Bash tool recorded them (it treats grep's no-match as success); they printed nothing or a count of 0.
- Only Laravel 13 / Boost 2.10.4 run locally; the Laravel 12 and lowest-Boost matrix was not run.
- Did not run the shell wrapper stub: the Jarvis guard blocks unwritten scripts; it is unchanged since package 10.

## Checks

- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" composer validate --strict`: exit 0, ./composer.json is valid
- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" vendor/bin/pest --compact --colors=never`: exit 0, 196 passed (688 assertions)
- `PATH="$HOME/Library/Application Support/Herd/bin:$PATH" vendor/bin/pint --test`: exit 0, pint passed
- `git grep -c -i -E "<name list>"`: exit 0, 17 hits in 6 files, all under docs/plans/laravel-kit-v2/packages (10, 11, 12, 14, 17, 18); expected no output: BLOCKING
- `git grep -n -i -E "<name list>" -- . ':(exclude)docs/plans'`: exit 0, no output: no hits outside docs/plans (tool records no-match as success)
- `git log --format=%B | grep -i -E "<name list>"`: exit 0, no output: commit messages clean (tool records no-match as success)
- `git log -p -- . ':(exclude)docs/plans' | grep -c -i -E "<name list>"`: exit 0, 0: no hits in code history outside docs/plans

## Findings

1. [BLOCKING, AC-12 / D-1, data exposure, carried over from package 10 finding 1 and package 12 finding 1, not fixed by package 13] Tracked plan specs hold the full customer and project name list in the AC-12 search command. `git grep -i -E "<pattern>"` finds 17 hits:
   - docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md:50, 51, 82, 83, 87
   - docs/plans/laravel-kit-v2/packages/11-review-befunde-von-paket-10-beheben.md:13
   - docs/plans/laravel-kit-v2/packages/12-paket-10-erneut-reviewen.md:50, 51, 94, 95, 96
   - docs/plans/laravel-kit-v2/packages/14-paket-10-erneut-reviewen.md:50, 51
   - docs/plans/laravel-kit-v2/packages/17-rauchtest-an-frischer-laravel-13-app-install-und-v.md:52, 53
   - docs/plans/laravel-kit-v2/packages/18-schluss-review-gegen-alle-abnahmekriterien.md:52, 53
   The "docs(plan): update laravel-kit-v2" commits put these files into the branch history of the public repo, so a push would publish the names. Outside docs/plans there are no hits in the tree, none in the code history (git log -p) and none in the commit messages.
   Fix (Jarvis or the owner only; package agents may not edit docs/plans or rewrite history, so another fix package cannot resolve it):
   - Keep the pattern out of every tracked file, for example an untracked pattern file outside the worktree or Jarvis-only context. Also change the check text in the specs of packages 17 and 18.
   - Remove docs/plans from the branch or strip the list from it.
   - Squash or rewrite the branch history before the first push.
   Afterwards `git grep -i -E "<pattern>"` and `git log -p | grep -i -E "<pattern>"` must both find nothing.

Non-blocking (unchanged, accepted in package 13):
2. tests/Feature/Commands/UpdateCommandTest.php:93-94: the diff header assertion does not enforce order against the confirmation. The code prints the diff first.
3. src/Hooks/DocsGuard.php:248-254: `git log --since` uses the committer date, so a rebase or amend during a turn can cause one extra block. This fails safe.
