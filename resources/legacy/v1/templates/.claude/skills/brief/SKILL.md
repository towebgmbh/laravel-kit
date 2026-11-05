---
name: brief
description: Summarize the current project state for session orientation. Use at the start of a new session or when re-orienting after a break.
allowed-tools: Read, Grep, Glob, Bash(find:*), Bash(git log:*), Bash(php artisan route:list:*), Bash(php artisan test:*), Bash(cat:*), Bash(wc:*)
argument-hint: "[domain name] or empty for full project overview"
---

# Project Brief

Build a comprehensive understanding of the current project state.

## Tool Preference

If the `laravel-boost` MCP server is available, prefer its tools over raw Bash commands:
- Use Boost's schema inspection instead of manual migration file scanning
- Use Boost's route listing instead of `php artisan route:list`
- Use Boost's package/version detection instead of parsing composer.json manually

Fall back to Bash commands if Boost MCP is not connected.

## Step 1: Check Documentation State

Read these files if they exist:
- `CLAUDE.md` (root) — Boost-generated project info
- `docs/GUIDELINES.md` — project overview, stack, key decisions
- `docs/SPECIFICATIONS.md` — planned and implemented features
- `docs/DECISIONS.md` — architectural decision records

**If docs contain only HTML comments or template placeholders** (e.g., `{{PROJECT_NAME}}`, empty sections with `<!-- Example: ... -->`), the project has not been onboarded yet. Inform the developer:

> "The docs are still templates — no project-specific content yet. Run `/onboard` to analyze the codebase and generate real documentation. I'll work with what I can find in the code directly for now."

Then continue with code scanning below.

## Step 2: Detect Stack

Don't assume the frontend stack. Detect it:
```bash
# Backend
php artisan --version 2>/dev/null

# Frontend detection
cat package.json 2>/dev/null | grep -E '"vue"|"react"|"inertia"|"livewire"|"alpine"'
cat composer.json | grep -E '"livewire"|"inertia"'

# Views/pages
find resources/views -name "*.blade.php" -type f 2>/dev/null | wc -l
find resources/js -name "*.vue" -type f 2>/dev/null | wc -l
find app -path "*/Livewire/*" -name "*.php" 2>/dev/null | wc -l
```

## Step 3: Scan Codebase Structure

```bash
# Domain structure
ls -d app/*/ 2>/dev/null | head -20

# Key counts
find app -name "*Action.php" -type f 2>/dev/null | wc -l
find app -name "*Controller.php" -type f 2>/dev/null | wc -l
find app -path "*/Models/*" -name "*.php" -type f 2>/dev/null | wc -l
find database/migrations -name "*.php" -type f 2>/dev/null | wc -l
find tests -name "*Test.php" -type f 2>/dev/null | wc -l
```

## Step 4: Produce Summary

### Project: [Name from GUIDELINES.md or CLAUDE.md or directory name]
**Stack**: [detected — e.g., "Laravel 12, Blade + Alpine.js, Tailwind CSS, PEST"]
**Packages**: [key packages from composer.json]

**Domains**:
- [Domain]: brief description, key models, action count

**Database**: migration count, key tables and relationships

**Routes**: count, key route groups (`php artisan route:list --compact | head -30`)

**Test Coverage**: test count, last run status (`php artisan test --compact 2>/dev/null | tail -5`)

**Current State**:
- Recent changes: `git log --oneline -10`
- Open/planned features from SPECIFICATIONS.md (if populated)
- Anything inconsistent with `.claude/rules/`

## If a Specific Domain Was Requested

Deep-dive: all models, actions, controllers, routes, tests, and relationships for that domain.

## Rules
- Read-only. Never modify code.
- Be factual. Report what exists, don't speculate.
- Detect the stack from code — never assume a specific frontend.
- Keep it scannable — this is a session briefing, not a novel.
