<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 9: kit:update mit Diff und Rückfrage

Kind: implement · Status: pending · Model: sonnet · Effort: medium · Commits: – · Verdict: –

## Spec

## Goal

Implement `php artisan kit:update`: after `composer update towebgmbh/laravel-kit` it replaces unchanged managed files, shows the diff for changed ones and asks, adds or drops the `pest/core` override when Pest was installed or removed, refreshes the manifest and the Claude hooks, and runs `boost:update`.

Public repository (D-1): no client names. Output in English (D-2).

## Context to read

- Concept sections Users and flows (4), Design (Manifest, Overrides und Testlinie, Claude-Extras, Öffentliches Repo), Decisions D-1, D-15, D-20, D-22, D-28, D-37.
- Packages 7 and 8: `src/Console/InstallCommand.php`, `src/Support/ManagedFiles.php`, `src/Support/ComposerPackages.php`, `src/Support/ClaudeSettings.php`, `src/Manifest.php`, `src/Overrides.php`, `stubs/claude/hooks/kit-docs-guard.sh`, `tests/Feature/Commands/InstallCommandTest.php`, `tests/Support/BoostFixture.php`.
- `sebastian/diff` (`SebastianBergmann\\Diff\\Differ`, `Output\\UnifiedDiffOutputBuilder`; the API is the same in versions 6 to 9, verify against the installed version).

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `src/Console/UpdateCommand.php` (new): `kit:update {--force}`. No manifest -> error `The kit is not installed. Run php artisan kit:install.` and `FAILURE`.
2. Managed files: the overrides that apply (`requires` null or `ComposerPackages::has()` true, D-37) plus `.claude/hooks/kit-docs-guard.sh` when `claude_code` is an agent in `boost.json`. For each: missing -> write; current hash equals the manifest hash -> replace with the new stub; current differs from the manifest hash and from the new stub -> print a unified diff (current -> kit version) and ask `Replace [path] with the kit version?` (`Laravel\\Prompts\\confirm`, default false; non-interactive: skip with `Skipped [path]: local changes kept.`; `--force`: replace). Reuse `ManagedFiles` and extend it with a diff-aware confirm callback rather than duplicating its logic.
3. Pest removed: when the manifest has the `pest/core` override but Pest is no longer in `composer.json`, delete the file only if its hash equals the manifest hash (else warn `[path] is kept because it has local changes; remove it by hand.`) and `forgetOverride()`/`forgetFile()`.
4. Then refresh override entries from `Overrides::all()`, `setVersion(Kit::VERSION)`, ensure the package in `boost.json`, `ClaudeSettings::merge()` for `claude_code`, `chmod 0755` the hook script, save the manifest, `$this->call('boost:update', ['--no-discover' => true])`, print a summary table (path, result). Register the command in `KitServiceProvider`.

## Acceptance criteria

Serves: AC-5, AC-12

- `kit:update` replaces an unchanged override whose stub changed (simulated by changing file contents and manifest hash together), prints a diff and skips an edited override non-interactively, replaces it with `--force`, and calls `boost:update` (the fixture's `CLAUDE.md` is rewritten). Evidence: `UpdateCommandTest`.
- After adding Pest to the fixture's `composer.json`, `kit:update` writes `.ai/guidelines/pest/core.blade.php` and records it; after removing Pest it deletes the unchanged file and drops the manifest entry. Evidence: `UpdateCommandTest`.
- Without a manifest it exits 1 with the message. Evidence: `UpdateCommandTest`.
- No client name, customer data or real project name in the files of this package or its commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Commands/UpdateCommandTest.php`: the scenarios above, on fixtures installed with `kit:install --no-interaction`.

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- `kit:upgrade` (package 11), `kit:doctor` and README (package 12), smoke script (package 13).
