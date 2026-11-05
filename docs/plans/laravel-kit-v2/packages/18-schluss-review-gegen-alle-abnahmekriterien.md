<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 18: Schluss-Review gegen alle Abnahmekriterien

Kind: review · Status: pending · Model: opus · Effort: high · Commits: – · Verdict: –

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
