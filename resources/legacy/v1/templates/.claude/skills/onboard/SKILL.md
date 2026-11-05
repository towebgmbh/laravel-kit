---
name: onboard
description: Onboard an existing Laravel project. Analyzes the codebase, asks clarifying questions, and generates docs/GUIDELINES.md, docs/SPECIFICATIONS.md, and docs/DECISIONS.md. Use when installing the kit into a project that already has code.
allowed-tools: Read, Grep, Glob, Bash(find:*), Bash(php artisan:*), Bash(git log:*), Bash(cat:*), Bash(wc:*), Bash(head:*), Write
argument-hint: "[quiet] — skip questions and infer everything from code"
---

# Project Initialization

Analyze an existing Laravel codebase and generate project documentation. This produces a reliable baseline so Claude can work on this project without misunderstandings.

## Tool Preference

If the `laravel-boost` MCP server is available, prefer its tools over raw Bash commands for discovery:
- Use Boost's schema inspection for database structure instead of reading migration files
- Use Boost's route listing for route discovery
- Use Boost's package detection for installed packages and versions
- Use Boost's search-docs tool to verify framework-specific patterns you observe

Fall back to Bash commands shown below if Boost MCP is not connected. The Bash commands serve as the definitive fallback and work on every project.

## Phase 1: Automated Discovery

Scan the project silently. Do not ask questions yet — gather facts first.

### Stack Detection
```bash
# PHP & Laravel version
php artisan --version
cat composer.json | grep -E '"php"|"laravel/framework"'

# Frontend stack detection
cat package.json 2>/dev/null | grep -E '"vue"|"react"|"inertia"|"livewire"|"alpine"|"tailwind"|"vite"|"webpack"'
cat composer.json | grep -E '"livewire"|"inertia"'

# Key packages
cat composer.json | grep -E '"spatie/|"laravel/|"filament/|"jetstream"|"breeze"|"fortify"'

# Testing framework
cat composer.json | grep -E '"pestphp/|"phpunit/"'
ls tests/Pest.php 2>/dev/null
```

### Architecture Patterns
```bash
# Domain or type-based organization?
ls -d app/*/ 2>/dev/null | head -20

# Actions, Services, Repositories?
find app -type d -name "Actions" -o -name "Services" -o -name "Repositories" | head -10
find app -type f -name "*Action.php" -o -name "*Service.php" | head -20

# Controllers location & style
find app -path "*/Controllers/*" -name "*.php" | head -20

# Models
find app -name "*.php" -path "*/Models/*" | head -30

# Form Requests
find app -name "*Request.php" | head -20

# Events, Listeners, Observers
find app -type d \( -name "Events" -o -name "Listeners" -o -name "Observers" \) | head -10
```

### Frontend Structure
```bash
# View/page structure
find resources/views -name "*.blade.php" -type f | head -30
find resources/js -name "*.vue" -o -name "*.tsx" -o -name "*.jsx" 2>/dev/null | head -30

# Layouts
find resources -name "*layout*" -o -name "*Layout*" | head -10

# Components
find resources -type d -name "components" -o -name "Components" | head -10
```

### Database & Routes
```bash
# Migration count and recent migrations
find database/migrations -name "*.php" | wc -l
ls -t database/migrations/*.php | head -10

# Route files and complexity
wc -l routes/*.php
php artisan route:list --compact 2>/dev/null | head -40

# Config files
ls config/*.php
```

### Test Coverage
```bash
# Test count and structure
find tests -name "*Test.php" -type f | wc -l
find tests -type d | head -15
php artisan test --compact 2>/dev/null | tail -5
```

### Git History (for context on what's been built)
```bash
git log --oneline -20 2>/dev/null
git log --format="%s" --since="3 months ago" 2>/dev/null | head -30
```

## Phase 2: Analysis

From the gathered data, build an internal understanding of:

1. **Stack**: exact versions, frontend approach, key packages
2. **Architecture pattern**: is it Actions-based, Service-based, fat controllers, or mixed?
3. **Organization**: domain-organized, type-organized, or hybrid?
4. **Naming conventions**: what patterns are actually in use (not what we wish)?
5. **Frontend approach**: Blade-only, Livewire, Inertia+Vue, or mixed?
6. **Test situation**: well-tested, partially tested, or untested?
7. **Technical debt signals**: inconsistent patterns, God-classes, missing separation

Identify **deviations from the kit's rules** but do NOT judge them yet. The project's conventions may be intentional.

## Phase 3: Clarifying Questions

Unless `$ARGUMENTS` contains "quiet", ask the developer targeted questions. Group them, keep them short. Only ask what you genuinely cannot infer from code.

**Always ask:**
- Project purpose in one sentence (code doesn't tell you *why*)
- Who are the users? (roles, tenancy model)
- Any external integrations or APIs?
- Known pain points or technical debt you should be aware of?

**Ask only if ambiguous from the code:**
- "I see both Services and Actions — is there a preferred pattern, or is this a migration in progress?"
- "The frontend uses both Blade and Livewire — which is the primary approach going forward?"
- "I see no tests for [domain]. Is this intentional or a gap to address?"
- "The models use [pattern X] in some places and [pattern Y] in others. Which is the convention?"

Do NOT ask about things you can determine from the code. Do NOT ask more than 6-8 questions total.

## Phase 4: Generate Documentation

Based on discovery + answers, generate the three docs files.

### `docs/GUIDELINES.md`
Fill in the template completely:
- Project name, description, purpose
- Actual stack with versions
- All installed packages with brief purpose
- Domain map with responsibilities
- Key decisions (observed from code + confirmed with developer)
- External integrations
- Environment notes
- **Deviations from kit rules**: List where the project differs from `.claude/rules/` and whether to follow the project's conventions or migrate toward the kit's rules. This is critical — it prevents Claude from "fixing" intentional patterns.

### `docs/SPECIFICATIONS.md`
Populate with existing features, marked as ✅ Implemented:
- One entry per major feature area discovered in the code
- Include routes, models, and actions involved
- Keep descriptions factual, based on code — not aspirational

### `docs/DECISIONS.md`
Create ADR entries for significant patterns observed:
- Architecture pattern choice (Actions vs Services vs mixed)
- Frontend stack choice
- Authentication approach
- Multi-tenancy strategy (if applicable)
- Any non-standard patterns with a note on rationale (ask if unclear)

## Phase 5: Verify

After generating docs, do a sanity check:
- Read back `docs/GUIDELINES.md` and confirm the deviation list is accurate
- Ensure SPECIFICATIONS.md covers the major features visible in routes
- Run `php artisan test` to confirm current test state matches documentation

Present a summary to the developer and ask for confirmation before considering onboard complete.

## Rules
- Be accurate, not aspirational. Document what IS, not what should be.
- The deviation list in GUIDELINES.md is the most important output — it prevents misunderstandings.
- Never modify existing code during onboard. This is documentation only.
- If the project uses patterns that differ from the kit's rules, document them as the project's conventions. The developer decides if/when to migrate.
- Generate docs in `docs/` directory. Create it if it doesn't exist.
- If docs files already exist but contain only template placeholders (HTML comments, `{{...}}`), overwrite them with the generated content. They are empty scaffolds from the install script.
