<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 18: Schluss-Review gegen alle Abnahmekriterien

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

## Report

Final review of the whole package against AC-1 to AC-14 and the decisions. All checks pass: composer validate, Pest (263 passed, 2 skipped as designed), the drift test with KIT_BOOST_DRIFT=1 (2 passed), Pint clean, and scripts/smoke.sh (PASS: fresh, PASS: upgrade, exit 0). Every AC has a working test or smoke step. kit:upgrade only deletes the listed v1 paths, .kit-version and a laravel-claude-kit/ folder with install.sh, and only removes v1 folders when they are empty. It leaves docs/ and other skills alone, its dry run changes nothing (checked with a tree hash), archived files keep their full content, the unchanged v1 template gives actions, and the derived mode reaches CLAUDE.md in the same run. D-40, D-41, D-27, D-37/D-38 and the Stop-hook-only setup all match the plan. The only blocking finding is again AC-12: the name search finds 23 lines in tracked docs/plans specs. Outside docs/plans, in the code history and in all commit messages there are no hits. The email search finds only the test@example.com placeholder. Four findings do not block: README inaccuracies, a narrow doctor leftover check, and a resolver default.

## Handoff

Code state: all package checks are green (Pest 263 passed and 2 skipped, drift 2 passed with KIT_BOOST_DRIFT=1, Pint clean, composer validate ok, smoke PASS fresh/upgrade). AC-1 to AC-11, AC-13 and AC-14 are met by tests or smoke steps. AC-12 is met everywhere except the tracked docs/plans/laravel-kit-v2 specs, which hold the name list (finding 1). Only Jarvis or the owner can fix that by removing it from the tracked files and squashing or rewriting the branch history before the first push or pull request. A fix package can handle findings 2 to 5: README.md:77 kit-brief text, README.md:119 CLAUDE.md/AGENTS.md wording, the kit:doctor leftover check (reuse V1Inventory::paths()) and the resolver's label for a missing Deviations section. Not verified locally: the Laravel 12 and Boost 2.10.0 matrix cells, the GitHub workflows (tests.yml matrix, smoke job, boost-drift.yml only run after a push or on the default branch) and the shell wrapper stub run by hand. For acceptance: bash scripts/smoke.sh needs network and Composer and takes a few minutes. Run later name searches with -- . ':(exclude)docs/plans' as well, so real hits stay visible.

## Decisions

- Kept the verdict blocking for the AC-12 hits in docs/plans because the spec counts any hit as blocking. The code itself has no blocking finding.
- Rated the README, doctor and resolver findings (2 to 5) as non-blocking: AC-11's sections are present and the behaviour follows D-27/D-30 as specified.
- Reported the exit codes the Bash calls recorded (they end with echo). For the commit message search, grep's own exit was 1 (no match), as the summary says.
- Extra search not listed in checks (its command exceeds the 500-character field): the name pattern over the tree and the code history (git log -p), both excluding docs/plans. Neither had a hit.
- Tried to run one CI matrix cell (Laravel 12, Boost 2.10.0) in an ignored build/ copy. The Jarvis guard blocked git archive in a review turn, so the Laravel 12 and lowest-Boost cells were checked by reading only. Locally only PHP 8.5, Laravel 13 and Boost 2.10.4 ran.
- Ran Pest with --compact --colors=never for the reported check; an earlier full-output run gave the same result (263 passed, 2 skipped).
- Did not run the stubs/claude/hooks/kit-docs-guard.sh wrapper by hand. It is unchanged since package 10; the smoke test checks it is installed, and DocsGuardTest runs the binary.

## Checks

- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; composer validate --strict; echo "exit=$?"`: exit 0, ./composer.json is valid
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; vendor/bin/pest --colors=never --compact; echo "exit=$?"`: exit 0, 263 passed, 2 skipped (drift test without KIT_BOOST_DRIFT), 915 assertions
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift --colors=never; echo "exit=$?"; vendor/bin/pint --test; echo "pint exit=$?"`: exit 0, Drift: 2 passed (Boost 2.10.4 = BOOST_VERSION), exit 0; Pint: passed, exit 0
- `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"; bash scripts/smoke.sh > /tmp/kit-smoke-review.log 2>&1; echo "exit=$?"; tail -30 /tmp/kit-smoke-review.log`: exit 0, PASS: fresh, PASS: upgrade, script exit 0; doctor printed only [OK] lines
- `git grep -n -i -E "<name list>" > /tmp/kit-grep1.txt; echo "exit=$?"; wc -l < /tmp/kit-grep1.txt; cut -d: -f1 /tmp/kit-grep1.txt | sort | uniq -c`: exit 0, git grep exit 0: 23 hits, all in docs/plans/laravel-kit-v2/packages 10, 11, 12, 14, 17, 18 (finding 1)
- `git log --format=%B | grep -i -E "<name list>"; echo "exit=$?"`: exit 0, No output; grep's own exit was 1 (echoed as exit=1), so no names in any commit message. The Bash call itself ended with exit 0.
- `git grep -n -E "[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,}" -- ':!resources/legacy' ':!composer.json'; echo "exit=$?"`: exit 0, One hit: tests/Unit/DocsGuardTest.php:203 test@example.com (placeholder, fine)

## Findings

1. [BLOCKING, AC-12 / D-1] `git grep -n -i -E "<name list>"` finds 23 lines, all in tracked plan files: docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md (5), 11-review-befunde-von-paket-10-beheben.md (1), 12-paket-10-erneut-reviewen.md (5), 14-paket-10-erneut-reviewen.md (6), 17-rauchtest-an-frischer-laravel-13-app-install-und-v.md (4), 18-schluss-review-gegen-alle-abnahmekriterien.md (2). These are the name list inside the check commands of the specs, plus review text that quotes it. The tree outside docs/plans, the code history outside docs/plans and all commit messages have no hits. Package agents cannot fix this because docs/plans is read-only for them. Before the first push or pull request, Jarvis or the owner must take the name list out of the tracked files (or drop docs/plans/laravel-kit-v2 from the branch) and squash or rewrite the branch history so no commit carries it. Then rerun both searches and expect no output.
2. [non-blocking, README accuracy] README.md:77 describes `kit-brief` as "Turn the spec and backlog into a brief for a larger piece of work". The skill does something else (resources/boost/skills/kit-brief/SKILL.md:3): it summarises the current project state for session orientation and is read-only, as in v1. Change the row to match, for example "Summarise the project state at the start of a session (read-only)".
3. [non-blocking, README accuracy] README.md:119 says "Boost writes the guidelines to `CLAUDE.md` when that file exists and to `AGENTS.md` otherwise". That is true only for the claude_code agent (ClaudeCode::guidelinesPath()). Codex and other agents always write AGENTS.md. Suggested wording: "For Claude Code, Boost writes to CLAUDE.md when it exists, otherwise to AGENTS.md; Codex and other agents use their own files such as AGENTS.md."
4. [non-blocking, AC-8 completeness] src/Console/DoctorCommand.php:118-137 only counts `.claude/rules/*.md` and `.claude/.kit-version` as v1 leftovers. It misses the five v1 skills (`.claude/skills/{onboard,migrate,review,test,brief}/SKILL.md`), `.claude/templates/FEATURE_TEMPLATE.md` and `laravel-claude-kit/install.sh`. A project that kept those files but has no `.kit-version` shows "[OK] No v1 leftovers". Use V1Inventory::paths() plus UpgradePlan::KIT_COPY and add a test case.
5. [non-blocking, D-30 edge] In src/Upgrade/ArchitectureResolver.php:20-27 a docs/GUIDELINES.md without the "## Deviations from Kit Rules" section (`deviations()` returns null) silently gives `actions`. src/Console/UpgradeCommand.php:180-182 then labels the source as 'derived from "Deviations from Kit Rules" in docs/GUIDELINES.md', although that section does not exist. D-30 only covers an empty section. Either label it "default, no Deviations section in docs/GUIDELINES.md" or treat a missing section as undecided (ask, or require --architecture without interaction), and add a resolver test for that case.
