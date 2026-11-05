<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 20: Paket 18 erneut reviewen

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

Re-reviewed package 19 (diff 637dcef..HEAD outside docs/plans: README.md, DoctorCommand, UpgradeCommand and two new tests) and re-ran every check of the final review. Findings 2 to 5 of package 18 are fixed. The README kit-brief row now matches the skill, the CLAUDE.md/AGENTS.md wording is correct, kit:doctor now also flags the v1 skills, the feature template and laravel-claude-kit/install.sh, and the upgrade labels a missing Deviations section as a default. All checks are green: composer validate, Pest (265 passed, 2 skipped as designed), drift test with KIT_BOOST_DRIFT=1 (2 passed), Pint clean, smoke (PASS: fresh, PASS: upgrade). Commit messages and the tree outside docs/plans have no name hits, and the email search only finds the test@example.com placeholder. The verdict stays blocking for one reason only: finding 1 of package 18 (AC-12). The name list is still in the tracked docs/plans/laravel-kit-v2 package specs, and only Jarvis or the owner can remove it. The code itself has no blocking finding.

## Handoff

Code state is ready for acceptance: Pest 265 passed / 2 skipped, drift 2 passed, Pint clean, composer validate ok, smoke PASS fresh and upgrade (Laravel 13, Boost pinned to 2.10.4). Package 18 findings 2-5 are fixed in af9a5d0. The only blocker left is AC-12. The name search hits only the tracked plan files docs/plans/laravel-kit-v2/packages/10, 11, 12, 14, 17, 18 and 20 (the name list in their check commands and quoted review text). No code package can fix this: docs/plans is read-only for packages. Before the first push or pull request, Jarvis or the owner must take docs/plans/laravel-kit-v2 (or at least the name list) out of the branch and squash or rewrite the history so no commit carries it. Then rerun `git grep` and the `git log --format=%B` search and expect no output. Two small non-blocking findings on kit:doctor's new leftover check can go to a follow-up or be accepted as they are (findings 2 and 3). Still not verified locally: the Laravel 12 and Boost 2.10.0 matrix cells and the GitHub workflows (they only run after a push).

## Decisions

- Kept the verdict blocking for AC-12 because the spec counts any hit of the name search as blocking; the code has no blocking finding and the fix needs Jarvis or the owner, not a code package.
- Reviewed the package 19 diff line by line; re-verified the rest by re-running all checks incl. smoke instead of a full re-read, since code outside the diff is unchanged since package 18.
- Re-ran the checks without a cd prefix because Jarvis could not match the first report; results were identical.
- Ran the name search with -c (per-file counts) instead of -n; line numbers come from an earlier -n run in this review.
- Ran the email search with docs/plans excluded so the plan text quoting the placeholder does not hide real hits; an earlier run without the exclusion found the same placeholder plus plan text only.
- Rated the two new kit:doctor findings non-blocking: warnings only, exit 0 per D-27.
- Only Laravel 13 / Boost 2.10.4 ran locally; the Laravel 12 and Boost 2.10.0 matrix cells and the GitHub workflows were not run.
- Did not run the shell wrapper stub by hand; it is unchanged since package 10 and the smoke test installs it.

## Checks

- `pwd; export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; composer validate --strict`: exit 0, ./composer.json is valid
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; vendor/bin/pest --colors=never --compact`: exit 0, 265 passed, 2 skipped (drift without KIT_BOOST_DRIFT), 920 assertions
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift --colors=never`: exit 0, 2 passed (enforce-tests and pest/core match reference hashes)
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; vendor/bin/pint --test`: exit 0, passed
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; bash scripts/smoke.sh > /tmp/kit-smoke-review20b.log 2>&1`: exit 0, PASS: fresh, PASS: upgrade
- `git grep -c -i -E "<name list>"`: exit 0, Hits only in docs/plans/laravel-kit-v2/packages 10 (5), 11 (1), 12 (5), 14 (6), 17 (4), 18 (4), 20 (2): finding 1
- `git log --format=%B | grep -i -E "<name list>"`: exit 0, No output: no names in any commit message (the tool records the no-match grep as exit 0)
- `git grep -n -E "[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,}" -- ':!resources/legacy' ':!composer.json' ':!docs/plans'`: exit 0, Only tests/Unit/DocsGuardTest.php:203 test@example.com (placeholder)
- `git grep -c -i -E "<name list>" -- . ':(exclude)docs/plans'; echo "outside-plans exit=$?"`: exit 0, No output outside docs/plans; git grep exit 1 (no match)

## Findings

1. [BLOCKING, AC-12 / D-1] `git grep -i -E "<name list>"` still finds hits, all in tracked plan files: docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md (lines 50, 51, 82, 83, 87), 11-review-befunde-von-paket-10-beheben.md:13, 12-paket-10-erneut-reviewen.md (50, 51, 94-96), 14-paket-10-erneut-reviewen.md (50, 51, 88-91), 17-rauchtest-an-frischer-laravel-13-app-install-und-v.md (52, 53, 82, 83), 18-schluss-review-gegen-alle-abnahmekriterien.md (52, 53, 85, 86), 20-paket-18-erneut-reviewen.md (52, 53). They are the name list inside check commands and review text. The tree outside docs/plans and all commit messages have no hit. A code package cannot fix this. Jarvis or the owner must remove the name list from the tracked files (or drop docs/plans/laravel-kit-v2 from the branch) and squash or rewrite the branch history before the first push or pull request, then rerun both searches with no output expected.
2. [non-blocking, AC-8 usability] src/Console/DoctorCommand.php:145 always ends the leftover warning with "Run php artisan kit:upgrade." With the new check (lines 126-138), the warning also fires for v1 skills, the feature template or laravel-claude-kit/install.sh when .claude/.kit-version is missing. In that case kit:upgrade aborts with "No v1 kit found (.claude/.kit-version 1.0.0)." (src/Console/UpgradeCommand.php:37). Without the version file, name the manual fix instead, e.g. "Remove them or restore .claude/.kit-version and run php artisan kit:upgrade."
3. [non-blocking, AC-8 accuracy] src/Console/DoctorCommand.php:126-132 counts any .claude/skills/{onboard,migrate,review,test,brief}/SKILL.md as a v1 leftover, whatever its content. A project's own skill with one of these generic names (for example `review`) gives a permanent false warning. Consider warning only when V1Inventory::has()/isUnchanged() matches, or when the file looks like a v1 kit skill.
