# Laravel Kit

A spec-driven development (SDD) kit for Laravel projects that use [Laravel Boost](https://github.com/laravel/boost).

The kit adds an opinionated structure (domains and actions), a documentation workflow and agent skills on top of Boost. It plugs into Boost as a third-party package, so its guidelines and skills are composed together with Boost's own. The result is one consistent set of rules in `CLAUDE.md`, `AGENTS.md` and every other agent Boost supports, and `boost:update` no longer overwrites or contradicts it.

The kit covers structure and workflow. Boost covers the framework APIs of each version. The kit does not repeat rules about Laravel, Livewire, Inertia or Pest that Boost already ships.

## Requirements

- PHP 8.3 or newer
- Laravel 12 or 13
- Laravel Boost 2.10 or newer, set up with `php artisan boost:install`. The overrides are written for the Boost version in `Overrides::BOOST_VERSION`; `kit:doctor` warns on older versions.
- Laravel Pint 1.27 or newer for the Claude Code hook (`--format agent`)
- Pest is recommended, PHPUnit is supported

## Installation

```bash
php artisan boost:install
composer require --dev towebgmbh/laravel-kit
php artisan kit:install
```

`kit:install` detects the stack and whether the project is new, and asks when it is unsure. It then registers the package in `boost.json`, writes the overrides, `config/kit.php`, the Claude Code hooks and (for new projects) the flat docs, runs `boost:update` and saves `.ai/kit-manifest.json`. It never overwrites a differing file without asking.

Then open your agent and run `/kit-onboard`.

## What you get

- **Lean guidelines**, always loaded, at most 200 lines per project: the size rule, the SDD flow, the docs layouts, the architecture mode, conventions, quality rules and the active stack variant.
- **The size rule:** the agent sizes every task itself and names the size in one sentence. *Small* (a fix, a few files): implement directly, run the affected tests, no spec. *Medium* (several layers or existing behaviour): short plan in chat, implement, update the spec entry and docs. *Large* (new feature, new domain): write a spec with `/kit-spec` using numbered criteria (AC-1, AC-2, ...) and "Open Questions", get approval, then build in phases (data, logic, routes, frontend, integration) without running the whole suite per phase. You can resize a task ("just do it", "spec first").
- **Two Boost overrides** in `.ai/guidelines/`: `enforce-tests.blade.php` carries the test line (a test must find a bug quickly or rule out follow-up bugs, nothing twice, feature tests for flows, unit tests for framework-free logic, no tests for framework behaviour). `pest/core.blade.php` is Boost's Pest text with one change: while working only the affected tests run, and the full suite (`--parallel --compact`) runs at most once at the end, only when shared logic, migrations, config or dependencies changed.
- **Five skills** (see below), copied by Boost into every agent's skills folder.
- **Claude Code Stop hooks:** Pint once per turn and a docs guard.
- **Two docs layouts:** flat to start, split when the project grows.

## Commands

| Command | Purpose |
|---|---|
| `kit:install` | Set up the kit in a new or existing project |
| `kit:update` | Refresh the kit's managed files after `composer update` |
| `kit:upgrade` | Migrate a project from the v1 kit (`.claude/rules`) |
| `kit:doctor` | Check the setup and warn about drift against Boost |

**`kit:install`** options: `--stack=` (`blade`, `livewire`, `inertia-vue`, `inertia-react`, `api`), `--architecture=` (`actions`, `services`, `migrating`), `--new`, `--existing` and `--force` (replace differing kit files without asking). Without options it detects or asks.

**`kit:update`** replaces managed files that are unchanged, shows the diff of changed ones and asks before replacing them (`--force` replaces without asking), then runs `boost:update`.

**`kit:upgrade`** moves a v1 project to v2. `--dry-run` prints the plan and changes nothing. Options `--architecture=` and `--stack=` answer the questions it would otherwise ask.

**`kit:doctor`** prints one line per check, prefixed `[OK]`, `[WARN]` or `[FAIL]`, and exits with 1 when a check fails. It reports the Boost version, whether the package is active in `boost.json`, the manifest, the config values, v1 leftovers, a `pest/core` override without Pest, override drift and docs that outgrew the flat layout. A Boost older than the reference version gives a warning instead of a drift check.

## Configuration

`kit:install` publishes `config/kit.php`. It belongs to your project; `kit:update` never replaces it.

| Key | Values | Default |
|---|---|---|
| `stack` | `blade`, `livewire`, `inertia-vue`, `inertia-react`, `api` | `blade` |
| `architecture` | `actions`, `services`, `migrating` | `actions` |
| `docs.split_threshold` | Number of domains from which the docs should be split | `4` |
| `hooks.docs_guard` | `true` or `false` | `true` |

The architecture modes: `actions` (domain folders with actions), `services` (the existing service pattern stays) and `migrating` (new code goes into actions, old code is converted when touched). Only the active stack and mode are rendered into the guidelines.

Boost renders `enforce-tests` only for projects with enough tests. The kit sets `boost.enforce_tests` to `true` unless your project sets it itself. The `pest/core` override is only written, managed and checked when `pestphp/pest` is in your `composer.json`.

## Skills

| Skill | Purpose |
|---|---|
| `kit-onboard` | Existing project: analyse with Boost's `infer-conventions`, ask at most 6 to 8 questions (always including the architecture decision), write docs, `config/kit.php` and deviations to `.ai/rules`, and migrate foreign docs with a dry run. New project: ask the basics and build the shell (domain folders, docs with a feature backlog, ADR-001 for the stack, architecture tests, `pint.json`, a GitHub workflow). |
| `kit-spec` | Write a feature spec with numbered criteria and open questions |
| `kit-review` | Review a change against Boost's best practices, the spec status, criteria coverage and docs drift |
| `kit-brief` | Summarise the project state at the start of a session (read-only) |
| `kit-docs-split` | Switch the docs from the flat to the split layout |

## Claude Code hooks

When `claude_code` is among the agents in `boost.json`, the kit merges two `Stop` hooks into `.claude/settings.json` and keeps every existing entry:

- `vendor/bin/pint --dirty --format agent` formats the changed files once at the end of a turn.
- `.claude/hooks/kit-docs-guard.sh` sends the agent back at most once when the turn changed structure (a new file under `app/`, a migration, anything in `routes/`, a new file under `resources/js/` or `resources/views/`) but nothing under `docs/`. Edits to existing files pass silently.

Switch the guard off with `'hooks' => ['docs_guard' => false]` in `config/kit.php`.

## Updating

```bash
composer update towebgmbh/laravel-kit
php artisan kit:update
php artisan kit:doctor
```

## Upgrading from v1

v1 (laravel-claude-kit 1.0.0) copied rules into `.claude/rules`. Boost 2 writes its own skills and guidelines, so those copies contradicted them.

```bash
composer require --dev towebgmbh/laravel-kit
php artisan kit:upgrade --dry-run
php artisan kit:upgrade
```

`kit:upgrade` recognises v1 through `.claude/.kit-version` and compares every file with the v1 originals by checksum:

- **Deleted:** unchanged v1 rules, skills and the feature template.
- **Archived:** v1 files you changed go to `.ai/kit-upgrade/v1/`, with the diff in `.ai/kit-upgrade/v1/REPORT.md`. Boost does not load this folder.
- **Moved:** your own rules in `.claude/rules` (not part of v1) go to `.ai/guidelines/project/`.
- **Removed:** a copied `laravel-claude-kit/` folder in the project root.
- **Kept:** `docs/` and any other skills.

It derives `kit.architecture` from "Deviations from Kit Rules" in `docs/GUIDELINES.md` and asks when it cannot, then runs `kit:install`. Run your test suite afterwards. The v1 `/test` and `/migrate` skills are replaced by the test line and `/kit-onboard`.

## How it works with Boost

Boost reads `resources/boost/guidelines/` and `resources/boost/skills/` of every direct Composer dependency, filtered by the `packages` list in `boost.json`; `kit:install` adds the kit there. For Claude Code, Boost writes the guidelines to `CLAUDE.md` when that file exists and to `AGENTS.md` otherwise; Codex and other agents use their own files such as `AGENTS.md`. Boost copies the skills to each agent's skills folder.

A file in `.ai/guidelines/` replaces the built-in Boost text of the same relative path. A package cannot do that, so `kit:install` writes the two overrides into the project. `.ai/kit-manifest.json` stores the hash of each Boost file an override replaces. `kit:doctor` compares it with the installed Boost, and a weekly workflow checks the newest Boost release.

## Development

```bash
composer test        # Pest with Orchestra Testbench
composer lint        # Pint
scripts/smoke.sh     # install and v1 upgrade in a fresh Laravel app
```

`KIT_BOOST_DRIFT=1 vendor/bin/pest tests/Drift` checks that the Boost files the overrides replace are unchanged.

## Legacy

The original v1 kit, an install script that copied rules into `.claude/rules`, is kept in [`resources/legacy/v1`](resources/legacy/v1). `kit:upgrade` uses it to recognise unchanged v1 files in existing projects.

## License

MIT, see [LICENSE](LICENSE).
