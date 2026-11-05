<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 21: Abnahme durch Simon

Kind: acceptance · Status: pending · Model: opus · Effort: medium · Commits: – · Verdict: –

## Spec

## Goal

Prepare Simon's acceptance of Laravel Kit v2 and give him a test guide. The package is a Composer library, so there is no Herd site and no browser (D-17); Simon works in a terminal on the task branch's checkout.

## Context to read

- The whole concept, especially Acceptance AC-1 to AC-14.
- Handoffs of packages 13 and 14 (smoke output, name search, review verdict).
- `README.md`, `scripts/smoke.sh`, `.github/workflows/tests.yml`, `.github/workflows/boost-drift.yml`, `resources/boost/guidelines/core.blade.php`, `resources/boost/skills/kit-spec/FEATURE_TEMPLATE.md`.

Tooling: if `php` or `composer` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. Demo user and login: none (a package, no web app).
2. Demo data: none to seed; `scripts/smoke.sh --keep` builds two fresh Laravel 13 apps in `<work>/fresh` and `<work>/upgrade` (the latter with a generated v1 layout) and prints the work directory `<work>`.
3. Start page: a terminal in the repository checkout of the task branch.
4. Run the checks below yourself first and report each with its exit code; a failing check is a blocking finding naming the AC.
5. Write the test guide for Simon with these steps, in this order, each with what he does and what he sees:
   1. AC-1: `composer install && vendor/bin/pest && vendor/bin/pint --test` -> all tests green, Pint reports no issues; on GitHub the latest run of "tests" on the pull request is green for all matrix jobs and the `smoke` job.
   2. AC-2, AC-9, AC-13: `vendor/bin/pest tests/Feature/Guidelines tests/Feature/Skills` -> green, including `ContextBudgetTest`.
   3. AC-3: `bash scripts/smoke.sh --keep --scenario=fresh` -> `PASS: fresh`; open `<work>/fresh/CLAUDE.md` and `<work>/fresh/AGENTS.md` and see the sections `=== towebgmbh/laravel-kit/core rules ===`, "Stack: Livewire 4 with Flux" and the test line "Follow the Laravel Kit test line:", and no `# Pest` section (the fresh app uses PHPUnit); `ls <work>/fresh/.claude/skills` lists the five `kit-*` skills.
   4. AC-14: in `<work>/fresh/CLAUDE.md` read the size rule (small, medium, large); open `.claude/skills/kit-spec/FEATURE_TEMPLATE.md` and see "Open Questions" and "Acceptance Criteria" with `AC-1`.
   5. AC-4, AC-6: in `<work>/fresh`, look at `.claude/settings.json` (two `Stop` hooks: Pint and the docs guard, no `PostToolUse`) and run `php artisan kit:install --stack=livewire --new --no-interaction` again -> the managed files are `unchanged`, `config/kit.php` and the docs are `exists`. Optional: open Claude Code in `<work>/fresh`, ask it to add a new Action file without docs and see it being sent back once; ask it to change one line in an existing file and see no interruption.
   6. AC-5: edit `.ai/guidelines/enforce-tests.blade.php` in `<work>/fresh` and run `php artisan kit:update` -> a diff and the question whether to replace it; answer no.
   7. AC-8: `php artisan kit:doctor` in `<work>/fresh` -> only `[OK]` lines (the smoke script restored the manifest after its drift check, and Boost is pinned to `Overrides::BOOST_VERSION`); the smoke output already showed `[FAIL] Override drift` after the manifest hash change.
   8. AC-7: `bash scripts/smoke.sh --keep --scenario=upgrade` -> `PASS: upgrade`; open `<work>/upgrade/.ai/kit-upgrade/v1/REPORT.md` (diff of `04-testing.md`), see `.ai/guidelines/project/07-project-rules.md`, and see "This project keeps its Service classes." in `<work>/upgrade/CLAUDE.md`.
   9. AC-10: `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift` -> green locally; `.github/workflows/boost-drift.yml` has the triggers `schedule` and `workflow_dispatch`. On GitHub the workflow can be started by hand only after the merge into the default branch.
   10. AC-11: read `README.md` sections "Installation", "Commands", "Updating", "Upgrading from v1".
   11. AC-12: the name searches of packages 13 and 14 had no hits.
   12. Afterwards: delete the work directories (`rm -rf <work>`). Tag `v2.0.0` and Packagist are Simon's own steps (D-17).

## Acceptance criteria

Serves: AC-1, AC-2, AC-3, AC-4, AC-5, AC-6, AC-7, AC-8, AC-9, AC-10, AC-11, AC-12, AC-13, AC-14

- Verdict `pass` or `blocking` with findings naming the AC, the checks with exit codes, and the test guide. Evidence: the report.

## Tests to write

None.

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`
- `KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift`
- `bash scripts/smoke.sh`

## Out of scope

- Code changes of any kind.
- Tag, release and Packagist registration (D-17).
