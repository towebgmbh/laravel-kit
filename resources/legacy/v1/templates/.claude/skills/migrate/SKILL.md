---
name: migrate
description: Migrate an existing project's documentation and conventions into the kit's structure. Use when a project already has docs, specs, or convention files in a different format and you want to unify them with the Laravel Claude Kit structure.
allowed-tools: Read, Write, Grep, Glob, Bash(find:*), Bash(git log:*), Bash(cat:*), Bash(wc:*), Bash(head:*), Bash(mv:*), Bash(cp:*)
argument-hint: "[dry-run] — show migration plan without making changes"
---

# Migrate Existing Documentation

Migrate a project's existing documentation, conventions, and specs into the Laravel Claude Kit structure. This skill preserves all existing content — nothing is deleted, only reorganized and merged.

## Tool Preference

If the `laravel-boost` MCP server is available, use its tools for codebase inspection alongside file discovery. Fall back to Bash commands if not connected.

## Phase 1: Discovery — Find Everything

Scan for all existing documentation and convention files. Be thorough — these can live anywhere.

### Common locations for project docs
```bash
# Root-level markdown files
find . -maxdepth 1 -name "*.md" -type f | sort

# Claude-specific files
find .claude -type f 2>/dev/null | sort
ls CLAUDE.md claude.md 2>/dev/null

# Documentation directories
find docs doc documentation -type f -name "*.md" 2>/dev/null | sort

# Spec/convention files anywhere
find . -maxdepth 3 -type f \( \
    -iname "*convention*" -o \
    -iname "*guideline*" -o \
    -iname "*specification*" -o \
    -iname "*spec.md" -o \
    -iname "*rules*" -o \
    -iname "*architecture*" -o \
    -iname "*decisions*" -o \
    -iname "*adr*" -o \
    -iname "PROJECT.md" -o \
    -iname "AGENTS.md" -o \
    -iname "CONVENTIONS.md" -o \
    -iname "STACK.md" -o \
    -iname "WORKFLOW.md" \
\) 2>/dev/null | grep -v vendor | grep -v node_modules | sort
```

### Read every discovered file
Read the full content of each discovered file. Build an internal inventory:
- File path
- Content type (project overview, stack info, conventions/rules, feature specs, decisions, agent instructions, workflow docs)
- Approximate size
- Whether it has active content or is mostly template/placeholder

## Phase 2: Inventory — Map What Exists to Kit Structure

The kit's target structure is:

| Kit Location | Purpose | Content Type |
|---|---|---|
| `CLAUDE.md` (root) | Boost-generated, auto-maintained | Leave untouched |
| `.claude/rules/01-architecture.md` | Actions, domain org, DTOs | Architecture conventions |
| `.claude/rules/02-laravel-conventions.md` | Naming, routing, Eloquent, Jobs, etc. | Laravel-specific conventions |
| `.claude/rules/03-frontend.md` | Blade/Inertia/Livewire conventions | Frontend conventions |
| `.claude/rules/04-testing.md` | PEST conventions | Testing conventions |
| `.claude/rules/05-quality.md` | Code quality, security, git | Quality standards |
| `.claude/rules/06-workflow.md` | Planning format, implementation phases | Workflow conventions |
| `.claude/rules/07+` | Project-specific rules | Custom conventions |
| `docs/GUIDELINES.md` | Project overview, stack, packages, deviations | Project context |
| `docs/SPECIFICATIONS.md` | Feature specs with status | Feature tracking |
| `docs/DECISIONS.md` | Architecture Decision Records | Decision log |

For each discovered file, determine:

1. **Where its content maps to** in the kit structure. A single source file may map to multiple kit files.
2. **Whether it overlaps with kit rules** that were already installed. If so, identify:
   - Content that is **identical** to kit rules → can be dropped (kit covers it)
   - Content that is **more specific** than kit rules → should become a project-specific rule (07+)
   - Content that **contradicts** kit rules → must go in the deviation list in GUIDELINES.md
3. **Whether it contains unique project knowledge** not covered by the kit at all → preserve in the appropriate doc file

### Common mappings (use as heuristic, verify each case)

| Source Pattern | Likely Target |
|---|---|
| `PROJECT.md`, `README.md` (project-specific parts) | `docs/GUIDELINES.md` |
| `CONVENTIONS.md`, `CODING_STANDARDS.md` | `.claude/rules/07-project-conventions.md` (for additions beyond kit rules) |
| `AGENTS.md`, agent prompt files | Evaluate: some → rules, some → skills, some → obsolete |
| `SPECIFICATIONS.md`, `FEATURES.md`, spec files | `docs/SPECIFICATIONS.md` |
| `DECISIONS.md`, `ADR-*.md`, `adr/` | `docs/DECISIONS.md` |
| `STACK.md`, `ARCHITECTURE.md` | Split: overview → `docs/GUIDELINES.md`, conventions → rules |
| `WORKFLOW.md`, `PROCESS.md` | Evaluate against `06-workflow.md`, unique parts → project rule |
| `.claude/rules/*` (existing) | Keep if project-specific, merge/replace if kit covers it |
| `.claude/commands/*` (existing) | Evaluate: migrate to `.claude/skills/` format or keep as-is |

## Phase 3: Present Migration Plan

Present a clear, structured plan to the developer. **Do not execute anything yet.**

### Format

```
MIGRATION PLAN
==============

SOURCE FILES FOUND:
  1. PROJECT.md (42 lines) — project overview, stack, conventions
  2. docs/CONVENTIONS.md (120 lines) — coding standards
  3. docs/specs/auth.md (85 lines) — authentication feature spec
  ...

PROPOSED ACTIONS:

[MERGE] PROJECT.md → docs/GUIDELINES.md
  - Project name, description, stack info → GUIDELINES overview section
  - "Key Decisions" section → GUIDELINES key decisions
  - External API list → GUIDELINES integrations

[MERGE] docs/CONVENTIONS.md → .claude/rules/07-project-conventions.md
  - 15 rules already covered by kit rules 01-06 → drop (kit handles these)
  - 8 project-specific rules → new file 07-project-conventions.md
  - 3 rules that CONTRADICT kit → docs/GUIDELINES.md deviation list

[MOVE] docs/specs/auth.md → docs/SPECIFICATIONS.md
  - Reformat to kit's spec template format
  - Mark as ✅ Implemented

[CONVERT] .claude/commands/deploy.md → .claude/skills/deploy/SKILL.md
  - Add frontmatter (name, description, allowed-tools)
  - Content stays the same

[KEEP] .claude/rules/07-multi-tenancy.md
  - Already in kit format, no changes needed

[ARCHIVE] AGENTS.md → .claude/archive/AGENTS.md.bak
  - Content is agent prompt instructions, now covered by rules + skills
  - Archived for reference, not deleted

CONTRADICTIONS WITH KIT RULES:
  - CONVENTIONS.md says "Use Service classes for business logic"
    Kit rule 01 says "Use Actions, not Services"
    → Will document as deviation. Which convention should we follow going forward?

  - CONVENTIONS.md says "Tests in tests/Feature/ flat structure"
    Kit rule 04 says "Mirror domain structure in tests/"
    → Will document as deviation. Migrate or keep?

FILES NOT TOUCHED:
  - CLAUDE.md (Boost-generated, never modified)
  - .claude/.kit-version
  - composer.json, package.json, etc.
```

### Ask for Confirmation

After presenting the plan:
1. Ask the developer to confirm or adjust each proposed action
2. Ask which contradictions should become deviations (keep existing) vs. migrations (adopt kit way)
3. Ask if any files should be handled differently than proposed

**Do not proceed to Phase 4 without explicit confirmation.**

## Phase 4: Execute Migration

Execute the confirmed plan step by step.

### For each MERGE action:
1. Read the source file
2. Read the target file (or kit template if target doesn't exist yet)
3. Integrate the source content into the target's structure
4. Write the merged result
5. Confirm the merge looks correct

### For each MOVE action:
1. Read the source
2. Reformat to kit's template structure if needed
3. Write to target location
4. Keep the source file until Phase 5

### For each CONVERT action (commands → skills):
1. Read the command file
2. Create skill directory and SKILL.md with frontmatter
3. Migrate content, adjusting for skill format

### For each ARCHIVE action:
1. Create `.claude/archive/` if it doesn't exist
2. Copy the file there with `.bak` extension
3. Original stays until Phase 5

### For the deviation list:
1. Add a `## Deviations from Kit Rules` section to `docs/GUIDELINES.md`
2. For each confirmed deviation: rule reference, project's convention, reason if known

### For project-specific rules:
1. Create `.claude/rules/07-project-conventions.md` (or higher number)
2. Include only rules that are genuinely additions to the kit — not duplicates

## Phase 5: Cleanup

After all migrations are confirmed working:

1. Present a list of source files that are now fully migrated
2. Ask the developer: **"These source files have been fully migrated. Should I delete them, or keep them alongside the new structure?"**
3. If delete: remove the source files
4. If keep: leave them but add a note at the top: `<!-- Migrated to Laravel Claude Kit structure. See docs/ and .claude/rules/. -->`

## Phase 6: Verify

1. Read back all modified/created files to confirm completeness
2. Run `/brief` mentally — does the resulting structure give a clear, complete picture?
3. Check that no content was lost: compare the source inventory from Phase 1 with what landed in the kit structure
4. Present a final summary:
   - Files created/modified
   - Content migrated
   - Deviations documented
   - Anything archived

## Rules
- **Never delete without asking.** Archive first, delete only on confirmation.
- **Never silently drop content.** If something doesn't map to the kit structure, ask where it should go.
- **Contradictions are documented, not resolved.** Present them, let the developer decide the direction.
- **Preserve project-specific knowledge.** Kit rules are generic — anything the project adds beyond them is valuable and must survive the migration.
- **One confirmation gate.** Present the full plan once, get approval, then execute. Don't ask for confirmation on every single file.
- If `$ARGUMENTS` contains "dry-run", stop after Phase 3 (plan only, no execution).
- Leave `CLAUDE.md` (Boost-generated) completely untouched.
- Leave `.claude/.kit-version` untouched.
