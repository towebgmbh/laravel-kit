<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 22: Paket 18 erneut reviewen

Kind: review · Status: done · Model: opus · Effort: high · Commits: none · Verdict: blocking

## Spec

## Goal

Review the complete package against every AC (AC-1 to AC-14) and every decision before Simon's acceptance, with emphasis on packages 11 to 13 (upgrade deletes files in user projects), on the guiding principle "pragmatism over ceremony" (D-32 to D-36), on PHPUnit projects (D-37, D-38), on the in-process config (D-40), on older Boost versions (D-41) and on the public-repository rule. Report blocking findings with file and line; do not change code.

## Context to read

- The whole concept and all handoffs of packages 1 to 13, including package 10's findings and how they were resolved.
- The full repository: `composer.json`, `config/`, `src/`, `bin/`, `stubs/`, `resources/boost/`, `scripts/`, `tests/`, `.github/workflows/`, `README.md`.

Tooling: if `php` or `composer` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. Map each AC to its evidence (test file, command, workflow file); a missing or non-failing test is blocking.
2. `kit:upgrade`: verify it can only delete the listed v1 paths, `.claude/.kit-version` and a `laravel-claude-kit/` folder containing `install.sh`; that `docs/` and foreign skills are never touched; that `--dry-run` writes nothing (including the report and the manifest); that archived files keep their full content; that the unchanged v1 template yields `actions`; that the derived architecture reaches `CLAUDE.md` in the same run.
3. `kit:install`: the chosen stack and architecture appear in `CLAUDE.md` right after the command when `config/kit.php` was created in that run (D-40).
4. `kit:doctor`: exit codes per D-27, messages as specified, drift via manifest vs installed Boost file, warning instead of drift for Boost older than `Overrides::BOOST_VERSION` (D-41), Pest warning.
5. Content: exactly five skills (`kit-onboard`, `kit-spec`, `kit-review`, `kit-brief`, `kit-docs-split`); guidelines and skills contain no contradiction sentence and no repeated framework API rules (D-4); small work needs no spec, plan or docs update unless structure or documented behaviour changes (D-32); the spec template has numbered criteria and "Open Questions" (D-33, D-34); `ContextBudgetTest` enforces 200 lines (D-35); `record-rule` always with its fallback; `kit-onboard` offers Pest and writes the arch test only with Pest (D-38); English only.
6. PHPUnit projects: no `pest/core` file, no Pest guideline, test line present (D-7, D-37).
7. Hooks: only `Stop` hooks, docs guard structural triggers and switch per D-36.
8. README: sections of AC-11 present, commands, options and skills match the code exactly.
9. CI: `tests.yml` matrix PHP 8.3/8.4 x Laravel 12/13 x Boost lowest/latest, `pint` and `smoke` jobs; smoke pins Boost to `Overrides::BOOST_VERSION`; `boost-drift.yml` with `schedule`, `workflow_dispatch` and `KIT_BOOST_DRIFT=1`.
10. Dependencies: `sebastian/diff` `^6.0|^7.0|^8.0|^9.0`, `symfony/process` in `require`.
11. Public repository (AC-12): run the name searches below over the tree and over all commit messages; any hit is blocking. Also look for customer data (email addresses other than placeholders, domains, URLs of client sites).

## Acceptance criteria

Serves: AC-1, AC-2, AC-3, AC-4, AC-5, AC-6, AC-7, AC-8, AC-9, AC-10, AC-11, AC-12, AC-13, AC-14

- Verdict `pass`, or `blocking` with numbered findings naming file and line and the AC or decision. Evidence: the report.
- Every check below is reported with its exit code. Evidence: the report's checks.

## Tests to write

None; this package only reviews.

## Checks to run

- `composer validate --strict`
- `vendor/bin/pest`
- `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift`
- `vendor/bin/pint --test`
- `bash scripts/smoke.sh`
- `git grep -n -i -E "<name list>"` (expected: no output, exit 1)
- `git log --format=%B | grep -i -E "<name list>"` (expected: no output, exit 1)
- `git grep -n -E "[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,}" -- ':!resources/legacy' ':!composer.json'` (review every hit)

## Out of scope

- Fixing findings (a fix package follows when the verdict is blocking).
- Tagging and Packagist (D-17).

Check that the findings of the previous review were fixed.

## Report

Re-reviewed package 21 (diff 4b5176a..HEAD outside docs/plans: src/Console/DoctorCommand.php checkLeftovers() and tests/Feature/Commands/DoctorCommandTest.php) and re-ran every check of the final review. Package 20 findings 2 and 3 are fixed. The leftover warning now ends with "Remove them, or restore .claude/.kit-version and run php artisan kit:upgrade." when the version file is missing. v1 skills and the feature template count without the version file only when they are an unchanged v1 original, so a project's own `review` skill no longer warns. Five tests cover this and fail without the change. All checks are green: composer validate, Pest (268 passed, 2 skipped as designed), drift test with KIT_BOOST_DRIFT=1 (2 passed), Pint clean, smoke (PASS: fresh, PASS: upgrade). Commit messages and the code history outside docs/plans have no name hits. The email search finds only the test@example.com placeholder. The verdict stays blocking for one reason: finding 1 (AC-12). The name list is still in the tracked docs/plans/laravel-kit-v2 package specs, now also in package 22's own spec. Only Jarvis or the owner can remove it. The code has no blocking finding.

## Handoff

Code state is ready for acceptance: Pest 268 passed / 2 skipped, drift 2 passed, Pint clean, composer validate ok, smoke PASS fresh and upgrade (Laravel 13, Boost pinned to 2.10.4). Package 20 findings 2 and 3 are fixed in 44f08db. The only blocker is still AC-12. The name list sits in the tracked plan specs docs/plans/laravel-kit-v2/packages/10, 11, 12, 14, 17, 18, 20 and 22. Before the first push or pull request, Jarvis or the owner must remove it (or the whole docs/plans/laravel-kit-v2 folder) from the branch and squash or rewrite the history. Then rerun `git grep` and the `git log --format=%B` search and expect no output. Do not loop more fix/review packages over AC-12: no code package can fix it, and each new review spec that quotes the list adds hits. One small non-blocking finding is open: kit:doctor tells projects without a v1 version file to "Remove" their own .claude/rules files. It can go to a follow-up or be accepted. Not verified locally: the Laravel 12 and Boost 2.10.0 matrix cells and the GitHub workflows (they only run after a push). php is at ~/Library/Application Support/Herd/bin.

## Decisions

- Kept the verdict blocking for AC-12 because the spec counts any hit of the name search as blocking; the code has no blocking finding and the fix needs Jarvis or the owner.
- Reviewed the package 21 diff line by line; re-verified the rest by re-running all checks incl. smoke instead of a full re-read, since code outside the diff is unchanged since package 20.
- Ran the name search with -c (per-file counts) instead of -n.
- Ran the email search with docs/plans excluded so plan text quoting the placeholder does not hide real hits.
- Added a search over the code history outside docs/plans (git log -p): no hits.
- The no-match greps are reported with exit 0 as the Bash tool recorded them (they end with echo); grep itself printed exit 1 / count 0.
- Rated the .claude/rules 'Remove them' hint non-blocking: warning only, exit 0 per D-27.
- Only Laravel 13 / Boost 2.10.4 ran locally; Laravel 12, Boost 2.10.0 and the GitHub workflows were not run.
- Did not run the shell wrapper stub by hand; it is unchanged since package 10 and the smoke test installs it.

## Checks

- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; composer validate --strict`: exit 0, ./composer.json is valid
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; vendor/bin/pest --colors=never --compact`: exit 0, 268 passed, 2 skipped (drift without KIT_BOOST_DRIFT), 929 assertions
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift --colors=never`: exit 0, 2 passed (enforce-tests and pest/core match reference hashes)
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; vendor/bin/pint --test`: exit 0, passed
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; bash scripts/smoke.sh > /tmp/kit-smoke-review22.log 2>&1; echo "exit $?"; grep -E "PASS|FAIL" /tmp/kit-smoke-review22.log | tail -20`: exit 0, script exit 0: PASS: fresh, PASS: upgrade
- `git grep -c -i -E "<name list>"; echo "exit $?"`: exit 0, Hits only in docs/plans/laravel-kit-v2/packages 10, 11, 12, 14, 17, 18, 20, 22: finding 1
- `git log --format=%B | grep -i -E "<name list>"; echo "exit $?"`: exit 0, No output, grep printed exit 1 (no match): no names in any commit message
- `git grep -n -E "[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,}" -- ':!resources/legacy' ':!composer.json' ':!docs/plans'; echo "exit $?"`: exit 0, Only tests/Unit/DocsGuardTest.php:203 test@example.com placeholder
- `git log -p -- . ':(exclude)docs/plans' | grep -c -i -E "<name list>"; echo "exit $?"`: exit 0, Count 0, grep printed exit 1: no names in code history outside docs/plans

## Findings

1. [BLOCKING, AC-12 / D-1] `git grep -i -E "<name list>"` still finds hits, all in tracked plan files: docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md (5), 11-review-befunde-von-paket-10-beheben.md (1), 12-paket-10-erneut-reviewen.md (5), 14-paket-10-erneut-reviewen.md (6), 17-rauchtest-an-frischer-laravel-13-app-install-und-v.md (4), 18-schluss-review-gegen-alle-abnahmekriterien.md (4), 20-paket-18-erneut-reviewen.md (5), 22-paket-18-erneut-reviewen.md (2). They are the name list in the check commands and the quoted review text. The tree outside docs/plans, the code history outside docs/plans (git log -p) and all commit messages have no hit. A code package cannot fix this. Before the first push or pull request, Jarvis or the owner must take the name list out of the tracked files (or drop docs/plans/laravel-kit-v2 from the branch) and squash or rewrite the branch history so no commit carries it. Then rerun both searches and expect no output. Every further review spec that embeds the list adds new hits, so use a placeholder in future specs.
2. [non-blocking, AC-8 wording] src/Console/DoctorCommand.php:124 and :152. Without .claude/.kit-version, every *.md in .claude/rules still counts as a v1 leftover, and the warning now says "Remove them". A v2 project that keeps its own Claude Code rules in .claude/rules (for example 00-project.md) is told to delete project-owned rules. Consider checking rule files without the version file with V1Inventory::isUnchanged as well (03-frontend.md against its variants), or saying "Move project rules to .ai/guidelines/project/" instead of "Remove them". The command only warns (exit 0), so no data is at risk.
