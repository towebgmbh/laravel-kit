---
name: kit-onboard
description: Onboard a project to the Laravel Kit. For an existing project it analyses the code, asks a few questions, writes the docs, the architecture mode and the deviation rules, and migrates foreign docs. For a new project it runs the kickoff and builds the project shell. Use after `php artisan kit:install`.
argument-hint: "[quiet] to skip questions, [dry-run] to show the plan only"
---

# Kit Onboard

Bring the project under the kit: correct docs in `docs/`, the right `stack` and `architecture` in `config/kit.php`, and the project's deviations as rules in `.ai/rules`. Never change application code or business logic in this skill.

## Arguments

- `quiet`: ask no questions. Infer everything from the code and mark every assumption in the docs with `<!-- assumption: ... -->` so the user can review it later.
- `dry-run`: run the analysis and show the full plan (files to write, rules to record, migrations of foreign docs), then stop without changing anything.

## Step 0: Choose the Mode

Read `.ai/kit-manifest.json`. Its key `project` decides the mode:

- `existing`: follow **Existing project**.
- `new`: follow **New project**.

Without a manifest or without the key, decide from `database/migrations/`: if every migration starts with `0001_01_01_` or ends with `_add_two_factor_columns_to_users_table.php` (skeleton and starter kit migrations only), the project is new; otherwise it is existing. Say which mode you use in one sentence. If the docs in `docs/` already hold real content (not only placeholders and HTML comments), update them instead of starting over.

If `docs/README.md` exists, the docs are split: write into the domain folders instead of the flat files (see `/kit-docs-split`).

## Existing project

### 1. Analyse

Run the read-only part of Boost's `infer-conventions` skill (its Steps 0 to 2: orient, predefined sweep, open-ended pass). Do not record anything yet. Add what the docs need:

- Stack and versions (Boost's application info tool, or `composer.json` and `package.json`).
- Domain folders under `app/` and their models, actions and services.
- Business logic location: Actions, Services, controllers or mixed.
- Routes, migrations and tests (counts), external integrations (HTTP clients, SDKs, webhooks), `git log --oneline -20`.

### 2. Foreign docs

Look for documentation in other formats, outside `vendor/` and `node_modules/`:

- `PROJECT.md`, `CONVENTIONS.md`, `STACK.md`, `ARCHITECTURE.md`, `WORKFLOW.md`;
- files matching `*specification*` or `*guideline*` (case-insensitive), except the kit's own `docs/` files;
- agent instruction files: `CLAUDE.md`, `AGENTS.md`, `GEMINI.md`, `.cursorrules`, `.cursor/rules/`, `.github/copilot-instructions.md`, `.junie/`, `.claude/commands/`. In `CLAUDE.md` and `AGENTS.md`, ignore the block Boost generates (between `<laravel-boost-guidelines>` and `</laravel-boost-guidelines>`); only text outside it counts.

When any of them exists, offer the migration. Build a plan with one action per file and its target:

| Action | Meaning |
|---|---|
| MERGE | Content goes into an existing kit file (`docs/GUIDELINES.md`, `docs/SPECIFICATIONS.md`, `docs/DECISIONS.md`) |
| MOVE | The file moves into the kit layout, reformatted (for example a spec into `docs/SPECIFICATIONS.md`) |
| CONVERT | Project conventions become rules in `.ai/rules`, or guidance in `.ai/guidelines/project/<name>.md` |
| KEEP | The file stays as it is (say why) |
| ARCHIVE | The source is copied to `.ai/kit-upgrade/foreign/` after its content was migrated |

For each file, also name: rules the kit or Boost already covers (dropped), rules that contradict the kit (become deviations), and project knowledge that must survive. Show the plan first as a dry run. Execute it only after the user confirms it (in `quiet` mode, show the plan and stop). Nothing is deleted without approval: archive each migrated source first, then ask whether to delete it. Never drop content silently; when something has no target, ask.

### 3. Questions

Unless `quiet` is set, ask at most 6 to 8 questions in one round. Ask only what the code cannot tell you. Always include:

- **Architecture decision**: convert to Actions (`actions`), keep Services (`services`), or migrate step by step (`migrating`: new code in Actions, existing services converted when touched). Recommend the option that matches the code.
- Project goal in one sentence, users and roles.
- Conflicts from the sweep that need a decision, and whether the migration of foreign docs should run.

In `quiet` mode, choose the architecture from the code: Services in use means `services`, Actions only means `actions`, both means `migrating`.

### 4. Write

1. Fill the docs (flat layout): `docs/GUIDELINES.md` (overview, stack, domain map, architecture mode, deviations as one-line pointers, integrations, environment), `docs/SPECIFICATIONS.md` (existing features as ✅ Implemented, factual, with routes and models), `docs/DECISIONS.md` (ADRs for the observed architecture, frontend stack and authentication). Document what is, not what should be.
2. Set `architecture` (and `stack` if it is wrong) in `config/kit.php`.
3. Record every approved convention and deviation from the kit with Boost's `record-rule` MCP tool, one call per glob, as `infer-conventions` describes. For `migrating`, also record a migration rule, for example for `app/**`: "New business logic goes into Actions under `app/<Domain>/Actions/`. Convert an existing service into Actions when you change it."
4. Without the `record-rule` tool (Boost MCP not set up or rules disabled), write the rule by hand in Boost's format: a file in `.ai/rules/` with frontmatter `paths:` (the globs), a `# Heading` and one `## Title` per rule followed by the note; add the file to `.ai/rules/index.md`. If you cannot write it, report the full rule text to the user.
5. Run the migration of foreign docs if the user confirmed it.

## New project

### 1. Kickoff questions

Unless `quiet` is set, ask in one round:

- Project name and goal in one sentence.
- Users and roles.
- Domains (for example `Billing`, `Catalog`) and the core entities of each.
- Stack: `blade`, `livewire`, `inertia-vue`, `inertia-react` or `api` (propose what `config/kit.php` and `composer.json` suggest).
- MVP scope: what is in, what is explicitly out.
- External integrations.
- When `pestphp/pest` is not in `composer.json`: install Pest? (recommended)

### 2. Pest

If the user wants Pest, run `composer require pestphp/pest --dev -W` and then `vendor/bin/pest --init`. Afterwards run `php artisan kit:update` so the kit adds its `pest/core` override.

### 3. Build the shell

No business logic: only structure, docs and tooling. The stubs are in this skill's folder, for example `.claude/skills/kit-onboard/stubs/` (the folder of this file in your agent).

1. Per domain: `app/<Domain>/Actions/.gitkeep` and `app/<Domain>/Models/.gitkeep`.
2. `docs/GUIDELINES.md`: overview, stack, users and roles, domain map, architecture mode, integrations.
3. `docs/SPECIFICATIONS.md`: the feature backlog in build order under "Backlog". Each item has a goal, a scope and acceptance criteria, so the list serves as the brief of a multi-session initiative. Mark every item 🔲 Planned.
4. `docs/DECISIONS.md`: ADR-001 on the stack (context, decision, consequences).
5. Copy `stubs/pint.json` to `pint.json` (ask before replacing an existing file).
6. Copy `stubs/tests.yml` to `.github/workflows/tests.yml`. For PHPUnit projects without `brianium/paratest`, either add it (`composer require brianium/paratest --dev`) or remove `--parallel`.
7. Only when Pest is installed: write `tests/Arch/ArchitectureTest.php` from `stubs/ArchitectureTest.php.stub`, keep the shared rules once and repeat the domain block once per domain with `{{DOMAIN}}` replaced. Without Pest, skip it and say so.
8. Set `stack` and `architecture` (`actions` unless the user chose otherwise) in `config/kit.php`.

## Finish

1. Run `php artisan boost:update` so the guidelines render with the new config and docs.
2. Run only the tests you added (for example `vendor/bin/pest tests/Arch`), not the complete suite.
3. Summarize: mode, files written, architecture and stack, rules recorded (or reported), foreign docs migrated or archived, assumptions to review. Recommend `/kit-spec` for the first backlog item of a new project.

## Rules

- Accuracy over aspiration: document the code as it is.
- Never overwrite a file with real content without asking; placeholders and HTML comments may be replaced.
- Never edit the Boost-generated block in `CLAUDE.md` or `AGENTS.md`; `boost:update` rewrites it.
- One confirmation gate: present the full plan once, then execute.
