<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 8: Claude-Stop-Hooks: Pint einmal pro Turn und struktureller Doku-Wächter

Kind: implement · Status: done · Model: opus · Effort: medium · Commits: `18acd27…0d36eb8` · Verdict: –

## Spec

## Goal

Add the Claude Code extras to `kit:install`: two `Stop` hooks (Pint once per turn and the structural docs guard), merged into an existing `.claude/settings.json`, and no `PostToolUse` hook (D-14, D-36).

Public repository (D-1): no client names. Output in English (D-2).

## Context to read

- Concept sections Users and flows (7), Design (Claude-Extras, Konfiguration, Öffentliches Repo), Decisions D-1, D-14, D-19, D-20, D-36.
- Package 7: `src/Console/InstallCommand.php` (`installAgentExtras()`), `src/Support/ManagedFiles.php`, `src/Manifest.php`, `config/kit.php` (`hooks.docs_guard`), `tests/Feature/Commands/InstallCommandTest.php`.

Claude Code hook contract used here: hooks live in `.claude/settings.json` under `hooks.<Event>` as a list of `{"matcher"?: string, "hooks": [{"type": "command", "command": string}]}`. A `Stop` hook gets JSON on stdin with `session_id`, `transcript_path`, `cwd`, `hook_event_name` and `stop_hook_active`; printing `{"decision":"block","reason":"..."}` with exit 0 makes the agent continue. The transcript is JSONL; entries have `type` (`user`, `assistant`, others) and `message.content` (string or list of blocks); assistant `tool_use` blocks have `name` and `input.file_path` (`input.notebook_path` for `NotebookEdit`); user entries whose content contains a `tool_result` block are tool results, not prompts.

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `src/Hooks/DocsGuard.php` (new): `final class DocsGuard` with constructor parameter `?Closure $isTracked = null` of type `Closure(string $cwd, string $path): ?bool` (true = tracked by git, false = not tracked, null = `cwd` is not inside a git work tree). Default implementation with `Symfony\\Component\\Process\\Process`: `git -C <cwd> rev-parse --is-inside-work-tree` fails -> null; else `git -C <cwd> ls-files --error-unmatch -- <path>` exit 0 -> true, else false. `handle(string $stdin): ?string` returns null (let the agent stop) when: the JSON is invalid; `stop_hook_active` is true; `<cwd>/config/kit.php` exists and returns an array whose `hooks.docs_guard` is `false` (include it inside try/catch); the transcript is missing or unreadable; anything throws (fail open, D-19). Otherwise take the last `user` entry that is a prompt, collect the paths of later `tool_use` blocks named `Edit`, `Write`, `MultiEdit` or `NotebookEdit`, make them relative to `cwd` (fallback `getcwd()`). A path is new when `$isTracked` returns false; when it returns null only paths written with `Write` count as new. Structural (D-36): a new file under `app/`; any path under `database/migrations/`; any path under `routes/`; a new file under `resources/js/` or `resources/views/`. When there is at least one structural path and no path under `docs/`, return `json_encode(['decision' => 'block', 'reason' => 'This turn changed the project structure (new files, migrations or routes), but docs/ did not. Update the affected docs (specification status, domain map, decisions, pitfalls) or state briefly why no documentation change is needed.'])`; else null.
2. `bin/kit-docs-guard` (new, executable, `#!/usr/bin/env php`): loads the autoloader from `$_composer_autoload_path`, else `__DIR__.'/../vendor/autoload.php'`, else `__DIR__.'/../../../autoload.php'`; reads stdin; prints the result of `(new DocsGuard)->handle()` when not null; always exits 0. Add `"bin": ["bin/kit-docs-guard"]` to `composer.json` (`symfony/process` is already in `require` since package 1).
3. `stubs/claude/hooks/kit-docs-guard.sh` (new): `#!/usr/bin/env bash`; `dir="${CLAUDE_PROJECT_DIR:-$(pwd)}"`; if `$dir/vendor/bin/kit-docs-guard` is not executable, `exit 0`; else `exec "$dir/vendor/bin/kit-docs-guard"`.
4. `src/Support/ClaudeSettings.php` (new): `final class ClaudeSettings` with `merge(string $basePath): string` returning `created`, `updated`, `unchanged` or `invalid`. Reads `.claude/settings.json` (missing -> `{}`; invalid JSON -> return `invalid` and write nothing). Adds to `hooks.Stop` the entry `{"hooks": [{"type": "command", "command": "vendor/bin/pint --dirty --format agent"}]}` unless any hook with that exact command exists under `Stop`, and the entry `{"hooks": [{"type": "command", "command": "\"$CLAUDE_PROJECT_DIR\"/.claude/hooks/kit-docs-guard.sh"}]}` unless a `Stop` command containing `kit-docs-guard.sh` exists. Never adds or removes `PostToolUse` entries. Keeps every other key and entry in order; writes pretty JSON with unescaped slashes and a trailing newline.
5. `InstallCommand::installAgentExtras()`: only when `boost.json` agents contain `claude_code` (D-20): `ManagedFiles::write('.claude/hooks/kit-docs-guard.sh', ...)` then `chmod 0755`, and `ClaudeSettings::merge()`; report `invalid` as a warning `.claude/settings.json is not valid JSON; hooks were not added.`. Update the existing expectations in `tests/Feature/Commands/InstallCommandTest.php`: in `claude_code` fixtures the manifest now has three files (both overrides and `.claude/hooks/kit-docs-guard.sh`; two in a fixture without Pest), and the second-run expectation lists the hook script as `unchanged`.

## Acceptance criteria

Serves: AC-4, AC-6, AC-12

- `DocsGuard` (with a fake `$isTracked`) blocks a turn that writes the new file `app/Billing/Actions/CreateInvoice.php`, edits `database/migrations/2026_01_01_000000_create_invoices_table.php`, edits `routes/web.php`, or writes a new `resources/js/pages/Invoices/Index.vue`, each without a docs edit; passes when only a tracked `app/Billing/Actions/CreateInvoice.php` is edited; with `$isTracked` returning null blocks a `Write` of a new `app/` file and passes an `Edit` of an `app/` file; passes with an additional `Write` of `docs/SPECIFICATIONS.md`; passes when `stop_hook_active` is true; passes when `config/kit.php` sets `hooks.docs_guard` to `false`; passes when only earlier turns touched code; passes on a missing transcript or invalid JSON. Evidence: `DocsGuardTest`.
- `ClaudeSettings::merge()` keeps existing permissions and hooks (including a foreign `PostToolUse` entry), adds both `Stop` hooks once and no `PostToolUse` hook, and a second run returns `unchanged`. Evidence: `ClaudeSettingsTest`.
- `kit:install` in a `claude_code` fixture writes an executable hook script, both `Stop` hooks and three manifest files; in a fixture with only `codex` it writes neither the script nor the settings. Evidence: `InstallCommandTest` (updated expectations pass).
- No client name, customer data or real project name in the files of this package, the transcript fixtures or the commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Unit/DocsGuardTest.php` with JSONL transcript fixtures written to temp files (generic paths only); one test with a real temporary git repository for the default `$isTracked` and one with a non-git temp directory.
- `tests/Unit/ClaudeSettingsTest.php`: missing file, existing settings with other hooks, invalid JSON, idempotency.
- Extend `tests/Feature/Commands/InstallCommandTest.php` (claude_code vs codex, three manifest files).

## Checks to run

- `composer validate --strict`
- `vendor/bin/pest`
- `vendor/bin/pint --test`
- `printf '{"stop_hook_active":true}' | bin/kit-docs-guard; echo "exit=$?"` prints nothing and `exit=0`.

## Out of scope

- `kit:update` (package 9), `kit:upgrade` (package 11), `kit:doctor` and README (package 12), smoke script (package 13).

## Report

Added the Claude Code extras to kit:install: DocsGuard (structural docs guard, fail open), bin/kit-docs-guard (composer bin), the wrapper stub .claude/hooks/kit-docs-guard.sh, ClaudeSettings::merge() adding the two Stop hooks (Pint once per turn, docs guard) without any PostToolUse hook, and InstallCommand::installAgentExtras() gated on the claude_code agent. New DocsGuardTest and ClaudeSettingsTest, extended InstallCommandTest. 185 tests green, Pint clean, composer validate --strict ok. Two commits.

## Handoff

Files: src/Hooks/DocsGuard.php (public const REASON; constructor ?Closure $isTracked(cwd, path): ?bool, default git via symfony/process), bin/kit-docs-guard (composer "bin"), stubs/claude/hooks/kit-docs-guard.sh (0755), src/Support/ClaudeSettings.php (consts PATH, PINT_COMMAND, DOCS_GUARD_COMMAND, results created/updated/unchanged/invalid), InstallCommand::installAgentExtras() plus public const DOCS_GUARD_SCRIPT.
- Package 9 (kit:update): the hook script is a managed file in the manifest (write via ManagedFiles::write, then chmod 0755 unless SKIPPED). Re-run ClaudeSettings::merge() too; it's idempotent and matches by command text (Pint: exact command; guard: any Stop command containing kit-docs-guard.sh). Only do it when boost.json agents contain claude_code (reads them with new BoostConfig().getAgents()).
- The install summary table now has two more rows after the docs: '.claude/hooks/kit-docs-guard.sh' and '.claude/settings.json' (with the merge result) before '.ai/kit-manifest.json'. Fixtures with claude_code record 3 manifest files (2 without Pest).
- Package 12/doctor: settings.json is never hashed; an invalid settings.json gets a warning in install and stays untouched.
- DocsGuard treats user entries with isMeta=true or with tool_result blocks as non-prompts; broken JSONL lines are skipped. Paths outside cwd are ignored for the structural check.
- Watch: the Jarvis PreToolUse guard refuses Bash heredocs that touch Claude settings paths and running scripts it did not see written via Write; use the Write tool for such files.
- D-1: staged diff grepped against the package 13 name list, only hits are the Towebgmbh namespace; commit messages are generic.

## Decisions

- ClaudeSettings decodes JSON as stdClass so empty objects (e.g. "env": {}) survive the round trip; JSON_UNESCAPED_UNICODE added to the spec's pretty print and unescaped slashes.
- ClaudeSettings also returns invalid (writes nothing) when the root is not an object, hooks is not an object or hooks.Stop is not a list.
- DocsGuard skips isMeta user entries when looking for the last prompt, so injected meta messages do not reset the turn.
- DocsGuard asks isTracked only for paths under app/, resources/js/ and resources/views/ (migrations and routes are structural regardless).
- A config/kit.php that throws on include keeps the guard on (only an explicit false disables it).
- bin/kit-docs-guard exits 0 silently when no autoloader or the DocsGuard class is found.
- Added public const InstallCommand::DOCS_GUARD_SCRIPT and DocsGuard::REASON; install table gets a .claude/settings.json row with the merge result.
- Extra tests beyond spec: MultiEdit and NotebookEdit paths, new Blade view, binary run via proc_open, invalid settings warning in kit:install, merge into existing settings in kit:install.
- Skipped running the shell wrapper manually: the Jarvis guard blocks running scripts written via heredoc; the binary itself is covered by a test.

## Checks

- `composer validate --strict`: exit 0, ./composer.json is valid
- `vendor/bin/pest`: exit 0, 185 passed (637 assertions)
- `vendor/bin/pint --test`: exit 0, passed
- `printf '{"stop_hook_active":true}' | bin/kit-docs-guard; echo "exit=$?"`: exit 0, no output, exit=0
