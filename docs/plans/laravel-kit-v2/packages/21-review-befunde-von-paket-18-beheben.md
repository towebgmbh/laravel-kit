<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 21: Review-Befunde von Paket 18 beheben

Kind: fix · Status: running · Model: default · Effort: default · Commits: – · Verdict: –

## Spec

The review of package 20 found blocking issues. Address each finding below. In your handoff, name every finding you did not address and why.

## Findings

1. [BLOCKING, AC-12 / D-1] `git grep -i -E "<name list>"` still finds hits, all in tracked plan files: docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md (lines 50, 51, 82, 83, 87), 11-review-befunde-von-paket-10-beheben.md:13, 12-paket-10-erneut-reviewen.md (50, 51, 94-96), 14-paket-10-erneut-reviewen.md (50, 51, 88-91), 17-rauchtest-an-frischer-laravel-13-app-install-und-v.md (52, 53, 82, 83), 18-schluss-review-gegen-alle-abnahmekriterien.md (52, 53, 85, 86), 20-paket-18-erneut-reviewen.md (52, 53). They are the name list inside check commands and review text. The tree outside docs/plans and all commit messages have no hit. A code package cannot fix this. Jarvis or the owner must remove the name list from the tracked files (or drop docs/plans/laravel-kit-v2 from the branch) and squash or rewrite the branch history before the first push or pull request, then rerun both searches with no output expected.
2. [non-blocking, AC-8 usability] src/Console/DoctorCommand.php:145 always ends the leftover warning with "Run php artisan kit:upgrade." With the new check (lines 126-138), the warning also fires for v1 skills, the feature template or laravel-claude-kit/install.sh when .claude/.kit-version is missing. In that case kit:upgrade aborts with "No v1 kit found (.claude/.kit-version 1.0.0)." (src/Console/UpgradeCommand.php:37). Without the version file, name the manual fix instead, e.g. "Remove them or restore .claude/.kit-version and run php artisan kit:upgrade."
3. [non-blocking, AC-8 accuracy] src/Console/DoctorCommand.php:126-132 counts any .claude/skills/{onboard,migrate,review,test,brief}/SKILL.md as a v1 leftover, whatever its content. A project's own skill with one of these generic names (for example `review`) gives a permanent false warning. Consider warning only when V1Inventory::has()/isUnchanged() matches, or when the file looks like a v1 kit skill.
