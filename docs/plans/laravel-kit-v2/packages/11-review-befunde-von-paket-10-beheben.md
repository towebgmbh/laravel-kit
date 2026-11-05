<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 11: Review-Befunde von Paket 10 beheben

Kind: fix · Status: done · Model: default · Effort: default · Commits: `fd15284…97b6ceb` · Verdict: –

## Spec

The review of package 10 found blocking issues. Address each finding below. In your handoff, name every finding you did not address and why.

## Findings

1. [BLOCKING, AC-12 / D-1, data exposure] docs/plans/laravel-kit-v2/packages/10-review-guidelines-overrides-stack-budget-install-h.md:50-51, 13-rauchtest-an-frischer-laravel-13-app-install-und-v.md:52-53, 14-schluss-review-gegen-alle-abnahmekriterien.md:52-53: these tracked files contain the full list of customer and project names used in the AC-12 search pattern, so `git grep -n -i -E "<name list>"` exits 0 with 6 hits. The concept says the name list lives "only in the specs, never in the repo", but the specs are committed to this public repository (commits "docs(plan): update laravel-kit-v2"). Pushing the branch would publish the names even if a later commit deletes the files. Nothing is public yet because no push has happened. Fix (needs Jarvis or the owner, because package agents may not edit docs/plans): keep the name list out of every tracked file, for example by moving the search pattern to a file outside the worktree or into Jarvis-only context. Then remove the list from the plan files, or remove docs/plans from the branch, and rewrite or squash the branch history before the first push, so no commit contains the names. Repeat the check afterwards: `git grep` must exit 1, and so must `git log -p | grep -i -E "<pattern>"` over the history.

Non-blocking findings (no change needed for the verdict):
2. src/Hooks/DocsGuard.php:206-229 (D-19/D-36 design limit): "new" means untracked at Stop time. If the agent commits inside the turn (which the core guideline encourages: "Each phase is committable"), a new app/, resources/js/ or resources/views/ file is already tracked and the guard lets the turn through. Suggestion: also count a path as new when it was added since the turn started (for example `git log --diff-filter=A` since the time of the prompt's transcript entry).
3. src/Console/UpdateCommand.php:104-105 (and InstallCommand.php:256-257, documented decision of package 7): recordOverride stores the new reference boost_sha256 even when the user keeps an edited override (SKIPPED). kit:doctor then measures drift against the new reference, although the local file is based on the older Boost text. Consider keeping the previous override entry when the write is skipped.
4. resources/boost/guidelines/quality.blade.php:19 "Validate every input with a Form Request" conflicts with the Livewire variant (stack.blade.php:19 "A component validates input") and with Boost's livewire/core ("Validate and authorize in actions"). Suggestion: "Validate every input (Form Requests for controllers, component validation in Livewire)".
5. src/Console/InstallCommand.php:81 and src/Console/UpdateCommand.php:37: Manifest::load throws InvalidArgumentException on an invalid .ai/kit-manifest.json. The commands do not catch it, so the user sees an exception trace instead of an error line and FAILURE.
6. stubs/ai/guidelines/enforce-tests.blade.php:3: "- Follow the Laravel Kit test line:" is a bullet followed by sibling bullets; the rules read better as a heading or nested list. Boost's "run the affected tests and ensure they pass" was dropped. Quality's DoD and Boost's phpunit/pest core cover it, so this is only cosmetic.
7. tests/Feature/Commands/UpdateCommandTest.php:89-99: the interactive test does not assert that the diff is printed before the confirmation (AC-5 "shows the diff and asks"). The code does print it first (UpdateCommand.php:157), but nothing would catch a regression.

## Report

Findings 2 to 7 of the package 10 review are fixed in one commit (97b6ceb); the suite is green (196 tests), Pint and composer validate are clean. Finding 1 (AC-12, customer names in the tracked plan files) is not addressed: it needs a decision by Jarvis or the owner, because package agents may not edit docs/plans or rewrite history.

## Handoff

Changed: (2) src/Hooks/DocsGuard.php: new optional constructor arg $addedSince(cwd, path, since): bool (default: git log --diff-filter=A --since=<epoch>); a tracked file under app/, resources/js/ or resources/views/ also counts as new when a commit since the last prompt's transcript timestamp added it; without a timestamp the old behaviour stays. (3) InstallCommand and UpdateCommand keep the previous override entry in the manifest (boost_sha256) when the write was SKIPPED and an entry exists; a first install with a differing file still records it. Package 14 (kit:doctor) can rely on that: drift is measured against the Boost text the local override was written for. (4) quality.blade.php Security rule now reads "Validate every input (Form Requests for controllers, component validation in Livewire) and authorise ...", the three KitGuidelines snapshots were updated. (5) Both commands catch InvalidArgumentException from Manifest::load, print the message plus a hint and return FAILURE (install fails before writing anything). kit:upgrade and kit:doctor should do the same. (6) enforce-tests stub: the lead-in is a plain paragraph line followed by bullets, and "When tests apply, run the affected tests and ensure they pass." was added; the marker 'Follow the Laravel Kit test line:' is unchanged. (7) UpdateCommandTest asserts the '+++ path (kit)' diff output in the interactive test. New tests: invalid manifest (install, update), kept override hash (install, update), commit-in-turn in DocsGuardTest. Local PHP: Herd's php is not on PATH in the Bash tool; use PATH="$HOME/Library/Application Support/Herd/bin:$PATH" (PHP 8.5.12 locally). NOT addressed: finding 1 (AC-12). The name pattern is still in docs/plans/laravel-kit-v2/packages/10, 13 and 14 (and package 11's spec may copy it), so `git grep` with the pattern still hits and the names are in branch history. Before the first push Jarvis or the owner must remove the list from the plan files (or docs/plans from the branch) and rewrite or squash the history so no commit holds the names; package 12 and the final review will hit the same issue until then, and their name search should run against a tree and history without docs/plans.

## Decisions

- Finding 1 (AC-12, name list in tracked docs/plans files) not addressed: package agents may not edit docs/plans or rewrite branch history; needs Jarvis or the owner before the first push.
- Finding 3: keep the previous override entry only when the write was skipped and an entry already exists; a first install with a differing file still records the entry (package 7 decision stays).
- Finding 2: implemented via a second injectable closure and the prompt's transcript timestamp (git log --diff-filter=A --since=<epoch>); applies to any tool, not only Write, and is skipped when the transcript has no timestamp.
- Finding 4: reworded the quality rule instead of changing the stack guideline; the line count is unchanged, so the context budget is unaffected.
- Finding 6: made the lead-in a paragraph (not a bullet) and re-added Boost's 'run the affected tests' as one bullet, so enforce-tests.blade.php grew by two lines; Overrides reference hashes refer to Boost files and stay valid.
- D-1: staged diff and commit message checked for customer names; only the Towebgmbh namespace appears.

## Checks

- `vendor/bin/pest --compact`: exit 0, 196 passed (688 assertions)
- `vendor/bin/pint --test`: exit 0, passed
- `composer validate --strict`: exit 0, composer.json is valid
