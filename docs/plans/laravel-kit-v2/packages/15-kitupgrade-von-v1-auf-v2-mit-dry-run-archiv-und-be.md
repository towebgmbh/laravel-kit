<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 15: kit:upgrade von v1 auf v2 mit Dry-Run, Archiv und Bericht

Kind: implement · Status: done · Model: opus · Effort: high · Commits: `e9dae16…36ebf56` · Verdict: –

## Spec

## Goal

Implement `php artisan kit:upgrade` (with `--dry-run`): it detects a v1 kit install, compares the v1 files by checksum with `resources/legacy/v1/templates`, deletes unchanged ones, archives modified kit files with a diff report, moves project-own rules to `.ai/guidelines/project/`, derives `kit.architecture` and the stack, removes a copied `laravel-claude-kit/` folder, keeps `docs/` and foreign skills, and then runs `kit:install`.

This command deletes files in user projects: be conservative, only touch the paths listed here. Public repository (D-1): fixtures stay generic. Output in English (D-2).

## Context to read

- Concept sections Users and flows (5), Design (Konfiguration, Upgrade v1, Öffentliches Repo), Decisions D-1, D-8, D-28, D-29, D-30, D-40.
- Package 10 review findings and their fixes, if any.
- `resources/legacy/v1/install.sh` (what v1 copied where: `.claude/rules/*.md`, `.claude/rules/03-frontend.md` from a variant, `.claude/skills/<name>/SKILL.md`, `.claude/templates/FEATURE_TEMPLATE.md`, `.claude/.kit-version`), `resources/legacy/v1/templates/**`, `templates/docs/GUIDELINES.md` (section `## Deviations from Kit Rules`: a blockquote line `> If this project intentionally differs from ...` followed by an HTML comment with examples).
- `src/Console/InstallCommand.php` (sets the running config when it creates `config/kit.php`, D-40), `src/Support/StackDetector.php`, `src/Manifest.php`, `tests/Support/BoostFixture.php`.
- Boost: `vendor/laravel/boost/src/Install/SkillWriter.php` (`sync()` overwrites a skill folder of a skill listed in `boost.json` `skills`).

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `src/Upgrade/V1Inventory.php` (new): the v1 file set and their reference hashes (`Manifest::hash` after trimming trailing whitespace at the end of the file) from `Kit::path('resources/legacy/v1/templates')`: `.claude/rules/01-architecture.md`, `02-laravel-conventions.md`, `04-testing.md`, `05-quality.md`, `06-workflow.md`; `.claude/rules/03-frontend.md` against the three variants (`blade`, `livewire`, `inertia-vue`); `.claude/skills/{onboard,migrate,review,test,brief}/SKILL.md`; `.claude/templates/FEATURE_TEMPLATE.md`. Method `original(string $projectPath): ?string` (for `03-frontend.md`: the variant with the smallest diff) and `frontendVariant(string $contents): ?string` (exact match or closest).
2. `src/Upgrade/UpgradePlan.php` (new): builds an ordered list of actions `delete`, `archive`, `move`, `remove-dir`, `keep` with source, target and reason, from the project files: unchanged v1 file -> `delete`; modified v1 file -> `archive` to `.ai/kit-upgrade/v1/<same relative path>`; any other file in `.claude/rules/` (top level, `*.md`) -> `move` to `.ai/guidelines/project/<filename>` (target exists -> `keep` with reason); `.claude/.kit-version` -> `delete`; `laravel-claude-kit/` at the root with `install.sh` inside -> `remove-dir`, without it -> `keep`; skills other than the five v1 names are never listed (D-29). Empty `.claude/rules`, `.claude/templates` and v1 skill folders are removed after the moves.
3. `src/Upgrade/ArchitectureResolver.php` (new): reads `docs/GUIDELINES.md`, takes the text from `## Deviations from Kit Rules` to the next `## `, strips HTML comments, blockquote lines (lines starting with `>` after optional whitespace) and blank lines (D-30): empty -> `actions`; contains "migrat" (case-insensitive) -> `migrating`; contains "service" -> `services`; other text -> null (ask). Missing file or section -> `actions`.
4. `src/Console/UpgradeCommand.php` (new): `kit:upgrade {--dry-run} {--architecture=} {--stack=}`. No `.claude/.kit-version` or a value other than `1.0.0` -> error `No v1 kit found (.claude/.kit-version 1.0.0).` and `FAILURE`. Stack: option, else the `03-frontend.md` variant, else `StackDetector`. Architecture: option, else `ArchitectureResolver`, else interactive `select`; non-interactive without a result -> error `Could not derive the architecture. Pass --architecture.` and `FAILURE`. `--dry-run`: print the plan as a table (Action, Path, Target, Reason) plus the derived stack and architecture, change nothing, exit 0. Otherwise execute the plan, write `.ai/kit-upgrade/v1/REPORT.md` (each action; for every archived file a fenced `diff` block original -> project version via `sebastian/diff`; for moved rules with a YAML frontmatter containing `paths:` the note "path-scoped rule: consider recording it with record-rule"; the source of the architecture value) and then `$this->call('kit:install', ['--existing' => true, '--stack' => $stack, '--architecture' => $architecture])` (pass `--no-interaction` through when the upgrade runs non-interactively). Never touch `docs/`. Register in `KitServiceProvider`.
5. `tests/Support/V1ProjectFixture.php` (new): builds a v1 project inside a `BoostFixture` that uses the default `boost.json` of `BoostFixture` without a `skills` key (so `boost:update` does not overwrite the Boost skill folder below): rules 01, 02, 05, 06 unchanged; `04-testing.md` with one appended line `- Use datasets for currency rounding cases.`; `03-frontend.md` = livewire variant unchanged; `00-overview.md` and `07-project-rules.md` (project-own, one with `paths:` frontmatter, generic content); skills `onboard`, `migrate`, `test`, `brief` unchanged, `review` with one changed line; `FEATURE_TEMPLATE.md` unchanged; `.claude/.kit-version` = `1.0.0`; `.claude/skills/laravel-best-practices/SKILL.md` (stands for a Boost skill, must stay); `laravel-claude-kit/install.sh`; `docs/GUIDELINES.md` from the v1 template with the Deviations line `- Uses Service classes instead of Actions for legacy reasons — do NOT refactor to Actions` uncommented.

## Acceptance criteria

Serves: AC-7, AC-12

- `kit:upgrade --dry-run` on the fixture lists `delete` for 01, 02, 03, 05, 06, the four unchanged skills, the template and `.kit-version`; `archive` for `04-testing.md` and `review/SKILL.md`; `move` for `00-overview.md` and `07-project-rules.md`; `remove-dir` for `laravel-claude-kit`; stack `livewire`, architecture `services`; and the fixture tree is byte-identical before and after. Evidence: `UpgradeCommandTest`.
- `kit:upgrade --no-interaction` produces exactly that end state, `.ai/kit-upgrade/v1/REPORT.md` with two diff blocks and the `paths:` note, `.ai/guidelines/project/00-overview.md` and `07-project-rules.md`, `config/kit.php` with `'architecture' => 'services'` and `'stack' => 'livewire'`, the manifest with `project` `existing`, untouched `docs/` and `.claude/skills/laravel-best-practices/SKILL.md`. Evidence: `UpgradeCommandTest`.
- After `kit:upgrade --no-interaction` the fixture's `CLAUDE.md` contains "This project keeps its Service classes." and "Stack: Livewire 4 with Flux" and not "Business logic lives in Actions." (the derived values reach the in-process `boost:update`, D-40). Evidence: `UpgradeCommandTest`.
- With the unchanged v1 `docs/GUIDELINES.md` template the resolver returns `actions` and `kit:upgrade --no-interaction` succeeds without `--architecture`. Evidence: `ArchitectureResolverTest`, `UpgradeCommandTest`.
- Without `.kit-version` the command fails with the message; with ambiguous Deviations text and no option it fails non-interactively. Evidence: `UpgradeCommandTest`.
- No client name, customer data or real project name in the files of this package, the v1 fixture or the commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Commands/UpgradeCommandTest.php`: the scenarios above (snapshot the fixture tree with path -> hash before/after dry-run).
- `tests/Unit/V1InventoryTest.php`: every legacy file matches itself; CRLF copy still matches; variant detection.
- `tests/Unit/ArchitectureResolverTest.php`: dataset (unchanged v1 template -> `actions`, empty/commented, services, migrating, ambiguous, missing file).

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- `kit:doctor` reporting v1 leftovers (package 12).
- The upgrade scenario in the smoke script (package 13).
- Changing files under `resources/legacy/v1/`.

## Report

Implemented php artisan kit:upgrade {--dry-run} {--architecture=} {--stack=}. It checks for .claude/.kit-version 1.0.0 and compares the 12 v1 kit files by checksum with resources/legacy/v1/templates (03-frontend.md against the three variants). Unchanged files are deleted. Modified kit files are archived to .ai/kit-upgrade/v1/ with a diff in REPORT.md. Project-own rules move to .ai/guidelines/project/. A copied laravel-claude-kit/ with install.sh is removed, and empty v1 folders are cleaned up. The command derives stack and architecture, then runs kit:install --existing in the same process. docs/ and foreign skills are never touched. New tests: UpgradeCommandTest, V1InventoryTest, ArchitectureResolverTest, with a V1ProjectFixture. Full suite: 256 passed. Pint is clean. Two commits.

## Handoff

New code:
- src/Upgrade/V1Inventory.php: the v1 file set, hash() = Manifest::hash(rtrim(contents)), isUnchanged(), original(path, ?contents), exactFrontendVariant(), frontendVariant(), constants VERSION_FILE, VERSION, FRONTEND_RULE, SKILLS, FRONTEND_VARIANTS.
- src/Upgrade/UpgradePlan.php: build(basePath), actions(), ofType(), execute(), constants ARCHIVE_DIR, REPORT, PROJECT_GUIDELINES, RULES_DIR, KIT_COPY.
- src/Upgrade/UpgradeAction.php: readonly type/path/target/reason.
- src/Upgrade/ArchitectureResolver.php: resolve(basePath), deviations(contents).
- src/Console/UpgradeCommand.php, registered in KitServiceProvider.
- tests/Support/V1ProjectFixture.php: create(?string $deviation). Pass null for the unchanged v1 GUIDELINES template. It has public constants for the fixture texts.

For package 16 (kit:doctor), v1 leftovers can reuse V1Inventory::VERSION_FILE, V1Inventory::paths() and UpgradePlan::RULES_DIR. For package 17 (smoke upgrade scenario): `kit:upgrade --no-interaction` works on a fresh app. The v1 fixture logic in V1ProjectFixture shows what to copy from resources/legacy/v1/templates. The stack comes from an exact 03-frontend.md match, so a livewire variant gives `livewire` even without livewire in composer.json.

Watch:
- Interactive runs ask "Apply this plan?" before changing anything.
- The report is built before the files move.
- A failed kit:install after the cleanup returns FAILURE with a hint to rerun kit:install.
- REPORT.md quotes v1 contradiction sentences inside diff blocks. That is harmless because Boost only loads .ai/guidelines/, not .ai/kit-upgrade/.
- Moved project rules are loaded by Boost as user guidelines, including their frontmatter text.

D-1: I reviewed the diff and both commit messages before committing. The fixture content is generic (orders, invoices, billing). No client or project names.

## Decisions

- original() takes (string $projectPath, ?string $contents = null): projectPath is the relative project path; contents is needed to pick the closest 03-frontend variant.
- Added exactFrontendVariant() and isUnchanged()/hashes()/has()/paths() to V1Inventory besides the specified methods.
- Stack order: --stack, exact 03-frontend.md variant, StackDetector, closest variant, interactive select (else error 'Pass --stack'). Detector before closest variant: v1 had no inertia-react variant.
- Separate UpgradeAction readonly value class (src/Upgrade/UpgradeAction.php) for plan entries.
- Archive target already exists -> keep (not overwrite), like the move rule; laravel-claude-kit symlinks are ignored, symlinked rule files are not moved.
- Only top-level regular *.md files in .claude/rules count as project rules; other files and subfolders there stay untouched.
- Interactive (non-dry-run) upgrade asks 'Apply this plan?' (default yes) after printing the plan; --no-interaction applies directly.
- kit:upgrade also fails before changing anything when boost.json is invalid or the manifest is invalid JSON (same messages as kit:install), including on --dry-run.
- A .kit-version other than 1.0.0 gives the same 'No v1 kit found' error.
- REPORT.md fences diffs with a fence longer than any backtick run in the diff (v1 files contain code fences); diff headers '(kit v1)' and '(project)'; CRLF normalised before diffing.
- The report records the source of the stack too, not only the architecture.
- Fixture: the services deviation line is inserted after the Deviations blockquote (the template's comment block stays); review SKILL.md changes the '# Code Review' heading; laravel-claude-kit also holds a README.md.
- Extra tests beyond spec: other kit version, options override, unknown architecture, project rule target exists, kit copy without install.sh, invalid manifest.

## Checks

- `vendor/bin/pest --compact --colors=never`: exit 0, 256 passed (891 assertions)
- `vendor/bin/pint --test`: exit 0, passed
