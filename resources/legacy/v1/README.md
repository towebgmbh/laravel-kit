# Laravel Claude Kit

A reusable starter kit for AI-assisted Laravel development with Claude Code. Provides architecture rules, on-demand skills, and documentation scaffolding — works for new projects and existing codebases alike.

## Stack Assumptions

- Laravel 12+ with PHP 8.3+
- Frontend: **Blade**, **Inertia.js + Vue 3**, or **Livewire** (selected during install)
- Tailwind CSS for styling
- PEST for testing
- Actions-based architecture with domain organization
- Laravel Boost recommended (`php artisan boost:install`)

## Quick Start

```bash
# Clone the kit (once)
git clone <repo-url> ~/laravel-claude-kit

# In your Laravel project root:
bash ~/laravel-claude-kit/install.sh
```

The installer asks for project name, description, and frontend stack, then scaffolds everything. Never overwrites existing files — safe to re-run.

### New Project

```
laravel new app && cd app
php artisan boost:install
bash ~/laravel-claude-kit/install.sh
# → Fill in docs/GUIDELINES.md, start building
```

### Existing Project

```
cd existing-project
bash ~/laravel-claude-kit/install.sh
# → Open Claude Code, run /onboard
# → Claude analyzes the codebase, asks questions, generates docs
```

For existing projects, the installer skips doc templates — `/onboard` generates them from your actual code instead.

### Existing Project with Docs Already

```
cd project-with-existing-docs
bash ~/laravel-claude-kit/install.sh
# → Open Claude Code, run /migrate
# → Claude maps your existing docs into the kit structure
```

For projects that already have conventions, specs, or decision logs in a different format, `/migrate` reorganizes them into the kit's structure without losing content.

## What's Included

### `.claude/rules/` — Always-Active Rules

Loaded automatically on every Claude Code interaction.

| File | Purpose |
|------|---------|
| `01-architecture.md` | Actions pattern, domain organization, DTOs, data flow |
| `02-laravel-conventions.md` | Naming, routing, migrations, Eloquent, Policies, Jobs, Resources, Mail |
| `03-frontend.md` | **Selected during install** — Blade, Inertia+Vue, or Livewire |
| `04-testing.md` | PEST conventions, test structure, coverage expectations |
| `05-quality.md` | Code quality, security, performance, git |
| `06-workflow.md` | Planning output format, implementation phases, self-review |

Frontend variants live in `rules/variants/` in the kit repo. The installer copies the selected one as `03-frontend.md`. To switch, delete the file and re-run the installer.

### `.claude/skills/` — On-Demand Skills

| Skill | Command | Purpose |
|-------|---------|---------|
| `onboard` | `/onboard` | **Onboard existing project** — analyze codebase, ask questions, generate docs |
| `migrate` | `/migrate` | **Unify existing docs** — map existing conventions/specs into kit structure |
| `review` | `/review` | Review code against rules (uncommitted changes, files, or last commit) |
| `test` | `/test` | Write or extend PEST tests for a specific target |
| `brief` | `/brief` | Session orientation — summarize project state, detect missing docs |

### `.claude/templates/` — Reference Templates

| File | Purpose |
|------|---------|
| `FEATURE_TEMPLATE.md` | Copy-paste template for feature handoffs to Claude Code |

### `docs/` — Living Documentation

| File | Purpose |
|------|---------|
| `GUIDELINES.md` | Project overview, stack, packages, conventions, **deviation list** |
| `SPECIFICATIONS.md` | Feature specs: planned → in progress → implemented |
| `DECISIONS.md` | Architecture Decision Records (lightweight ADR) |

## How It All Fits Together

```
CLAUDE.md (Boost)          → Auto-detected project info, model schemas, routes
.claude/rules/ (Kit)       → Architectural standards (always active)
.claude/skills/ (Kit)      → On-demand: /onboard, /migrate, /review, /test, /brief
.claude/templates/ (Kit)   → Reference templates (feature handoff)
docs/ (Kit)                → Project context (grows with project)
.claude/.kit-version       → Installed kit version
```

## The `/onboard` Workflow

For existing projects, `/onboard` is the key skill. It:

1. **Scans** — composer.json, package.json, directory structure, routes, migrations, tests, git history
2. **Analyzes** — detects patterns, architecture style, naming conventions, frontend approach, test coverage
3. **Asks** — targeted questions about things code can't reveal (purpose, users, pain points, intentional deviations)
4. **Generates** — populates GUIDELINES.md, SPECIFICATIONS.md, and DECISIONS.md from actual code
5. **Documents deviations** — lists where the project differs from kit rules and whether to follow the project's way or migrate

The deviation list is critical: it prevents Claude from "fixing" intentional patterns in your codebase.

## The `/migrate` Workflow

For projects that already have documentation or conventions in a different format:

1. **Discovers** — finds all markdown files, convention docs, specs, agent prompts, ADRs, anywhere in the project
2. **Maps** — determines where each file's content belongs in the kit structure (rules, docs, skills, or archive)
3. **Detects conflicts** — identifies where existing conventions contradict kit rules and flags them for decision
4. **Plans** — presents a full migration plan with MERGE, MOVE, CONVERT, KEEP, and ARCHIVE actions
5. **Executes** — after your explicit approval, reorganizes everything into the kit structure
6. **Cleans up** — archives source files, never deletes without asking

Use `/migrate dry-run` to see the plan without executing anything.

This is the path for projects that were already set up with Claude Code (e.g., with a `PROJECT.md`, `CONVENTIONS.md`, custom `.claude/commands/`) and want to adopt the kit's structure without losing existing work.

## Adding Hooks

The kit does not include hooks because they tend to be project-specific. However, hooks are a powerful complement. Here's a recommended starting point — add this to `.claude/settings.json`:

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Write(*.php)",
        "hooks": [
          {
            "type": "command",
            "command": "./vendor/bin/pint $CLAUDE_FILE_PATH"
          }
        ]
      }
    ]
  }
}
```

This runs Laravel Pint on every PHP file Claude edits, ensuring code style is always consistent. Other useful hooks:

- **PostToolUse `Write(*.blade.php)`** → Run Prettier with Blade plugin
- **PostToolUse `Write(*.vue)`** → Run ESLint + Prettier
- **PreToolUse `Bash`** → Log all bash commands for audit

See the Claude Code docs on hooks for the full configuration reference.

## Customizing

**Project-specific rules**: Add files in `.claude/rules/` (e.g., `07-multi-tenancy.md`). Auto-loaded.

**Project-specific skills**: New directories in `.claude/skills/` with a `SKILL.md`.

**Switch frontend variant**: Delete `.claude/rules/03-frontend.md`, re-run `install.sh`.

**Updating the kit**: Pull kit repo updates, re-run `install.sh` — skips existing files. Check `.claude/.kit-version` to see what's installed. Compare and merge manually for updates to existing files.

## Versioning

The installer writes the kit version to `.claude/.kit-version`. When you update the kit repo and re-run the installer on an existing project, it updates this file. This lets you track which projects are on which kit version.

## Design Principles

1. **Rules over commands** — Standards are rules (always active), not workflows you invoke.
2. **Don't duplicate built-ins** — No `/plan` or `/implement`. Claude's Plan mode and the rules handle this.
3. **Skills for distinct tasks** — `/review`, `/test`, `/brief`, `/onboard` are standalone workflows.
4. **Document what IS** — `/onboard` captures reality, not aspirations. Deviations are documented, not hidden.
5. **Grow, don't rebuild** — Empty docs for new projects, generated docs for existing ones. Both grow the same way.
6. **Frontend-agnostic** — Rules, skills, and docs adapt to the chosen frontend stack. No Inertia bias.
