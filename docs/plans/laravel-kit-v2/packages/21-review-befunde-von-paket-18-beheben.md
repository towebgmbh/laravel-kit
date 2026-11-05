<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 21: Review-Befunde von Paket 18 beheben

Kind: fix · Status: done · Model: default · Effort: default · Commits: `4b5176a…44f08db` · Verdict: –

## Spec

The review of package 20 found blocking issues. Address each finding below. In your handoff, name every finding you did not address and why.

## Findings

1. [BLOCKING, AC-12 / D-1] `git grep -i -E "<name list>"` still finds hits, all in tracked plan files: docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md (lines 50, 51, 82, 83, 87), 11-review-befunde-von-paket-10-beheben.md:13, 12-paket-10-erneut-reviewen.md (50, 51, 94-96), 14-paket-10-erneut-reviewen.md (50, 51, 88-91), 17-rauchtest-an-frischer-laravel-13-app-install-und-v.md (52, 53, 82, 83), 18-schluss-review-gegen-alle-abnahmekriterien.md (52, 53, 85, 86), 20-paket-18-erneut-reviewen.md (52, 53). They are the name list inside check commands and review text. The tree outside docs/plans and all commit messages have no hit. A code package cannot fix this. Jarvis or the owner must remove the name list from the tracked files (or drop docs/plans/laravel-kit-v2 from the branch) and squash or rewrite the branch history before the first push or pull request, then rerun both searches with no output expected.
2. [non-blocking, AC-8 usability] src/Console/DoctorCommand.php:145 always ends the leftover warning with "Run php artisan kit:upgrade." With the new check (lines 126-138), the warning also fires for v1 skills, the feature template or laravel-claude-kit/install.sh when .claude/.kit-version is missing. In that case kit:upgrade aborts with "No v1 kit found (.claude/.kit-version 1.0.0)." (src/Console/UpgradeCommand.php:37). Without the version file, name the manual fix instead, e.g. "Remove them or restore .claude/.kit-version and run php artisan kit:upgrade."
3. [non-blocking, AC-8 accuracy] src/Console/DoctorCommand.php:126-132 counts any .claude/skills/{onboard,migrate,review,test,brief}/SKILL.md as a v1 leftover, whatever its content. A project's own skill with one of these generic names (for example `review`) gives a permanent false warning. Consider warning only when V1Inventory::has()/isUnchanged() matches, or when the file looks like a v1 kit skill.

## Report

Findings 2 and 3 (kit:doctor leftover check) are fixed in commit 44f08db. Finding 1 (AC-12, name list in tracked docs/plans files) is not addressed: docs/plans is read-only for packages and the fix needs a history rewrite by Jarvis or the owner. Pest 268 passed, 2 skipped; Pint clean.

## Handoff

Changed: src/Console/DoctorCommand.php checkLeftovers(). Skills and the feature template from V1Inventory::paths() now count as leftovers only if their contents are an unchanged v1 original (V1Inventory::isUnchanged), or if .claude/.kit-version exists (then kit:upgrade handles them, changed or not). A project's own skill named e.g. `review` no longer gives a permanent warning. Rules in .claude/rules/*.md, laravel-claude-kit/install.sh and the version file still count as before. The warning ends with "Run php artisan kit:upgrade." when the version file exists, otherwise with "Remove them, or restore .claude/.kit-version and run php artisan kit:upgrade." (kit:upgrade aborts without the version file). Tests in tests/Feature/Commands/DoctorCommandTest.php: the old leftover test now has a version file; new tests for the manual-fix message, unchanged v1 skills/template/kit copy without version file, an ignored project skill, and a changed v1 skill with version file. Watch: a customised v1 skill without .claude/.kit-version is no longer reported (indistinguishable from a project skill). Finding 1 stays open: before the first push or pull request, Jarvis or the owner must take the name list out of docs/plans/laravel-kit-v2 (or drop that folder) and squash or rewrite the history, then rerun the git grep and git log --format=%B name searches and expect no output. Not run: smoke script (needs network; it does not touch the changed check, the fixture has no v1 skills after upgrade), Laravel 12 / Boost 2.10.0 matrix cells, GitHub workflows. php is at ~/Library/Application Support/Herd/bin.

## Decisions

- Finding 1 (AC-12, name list in tracked docs/plans files) not addressed: docs/plans is read-only for packages and the fix needs a history rewrite by Jarvis or the owner.
- Finding 3: chose 'unchanged v1 original, or version file present' over 'only unchanged'; with the version file the project is a v1 project and kit:upgrade archives changed skills, so they stay leftovers. A customised v1 skill without the version file is not reported.
- Finding 2: the hint depends only on whether .claude/.kit-version exists; it also applies to rule-file leftovers, whose old test message changed accordingly.
- Did not run the smoke script; it needs network and the changed check is not part of it.
- D-1: checked the diff and commit message for customer names; none, fixtures are generic.

## Checks

- `vendor/bin/pest --compact --colors=never`: exit 0, 268 passed, 2 skipped (929 assertions)
- `vendor/bin/pint --test`: exit 0, passed
