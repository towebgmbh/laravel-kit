<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 6: Skills kit-onboard (beide Modi, Migration fremder Doku) und kit-docs-split mit Doku-Vorlagen und Projekt-Hülle

Kind: implement · Status: done · Model: opus · Effort: medium · Commits: `e87693e…47cb5c0` · Verdict: –

## Spec

## Goal

Ship `kit-onboard` with both modes (existing project analysis including the migration of foreign docs, new project kickoff that builds the project shell) and `kit-docs-split`, plus the flat doc stubs that `kit:install` writes for new projects and the split templates. After this package the kit has exactly five skills (D-13).

Public repository (D-1): generic examples only (`Billing`, `Catalog`). Content in English (D-2).

## Context to read

- Concept sections Users and flows (1, 2), Design (Einklinken in Boost, Doku-Layouts, Manifest, Öffentliches Repo), Decisions D-1, D-9, D-11, D-13, D-22, D-23, D-32, D-38.
- Package 5 skills (`resources/boost/skills/kit-*`), `tests/Feature/Skills/SkillDiscoveryTest.php`, `tests/Feature/Skills/SkillContentTest.php`.
- v1 sources: `resources/legacy/v1/templates/.claude/skills/onboard/SKILL.md` (quiet mode, deviation list), `.claude/skills/migrate/SKILL.md` (discovery, MERGE/MOVE/CONVERT/KEEP/ARCHIVE plan, dry-run, approval), `docs/GUIDELINES.md`, `docs/SPECIFICATIONS.md`, `docs/DECISIONS.md`.
- Boost: `vendor/laravel/boost/.ai/boost/skill/infer-conventions/SKILL.blade.php` (records rules with the `record-rule` MCP tool and falls back to reporting the rule text when it is unavailable), `src/Mcp/Tools/RecordRule.php`.
- `src/Manifest.php` (`project` key: `new` or `existing`).
- Fact: the current Laravel 13 skeleton uses PHPUnit, not Pest.

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `stubs/docs/flat/GUIDELINES.md`, `stubs/docs/flat/SPECIFICATIONS.md`, `stubs/docs/flat/DECISIONS.md` (new): modernised v1 templates with placeholders `{{PROJECT_NAME}}`, `{{STACK}}`, `{{ARCHITECTURE}}`; no reference to `.claude/rules`; GUIDELINES has Project Overview, Stack, Domain Map, Architecture (mode from `config/kit.php`), Deviations (pointing to `.ai/rules`), External Integrations, Environment Notes; SPECIFICATIONS has the status legend and a "Backlog" section; DECISIONS has the ADR format. Example comments stay generic (no real services or company names).
2. `resources/boost/skills/kit-onboard/SKILL.md` (new): decides the mode from `.ai/kit-manifest.json` key `project` (fallback: the D-23 migration rule). Argument `quiet` skips questions and marks assumptions in the docs; argument `dry-run` only shows the plan.
   - Existing project: run the `infer-conventions` sweep (read-only part first), ask at most 6 to 8 questions in one round, always including the architecture decision (convert to Actions, keep Services, migrate step by step); then fill `docs/` (flat), set `architecture` in `config/kit.php`, record deviations into `.ai/rules` with Boost's `record-rule` MCP tool, and for "migrate step by step" record a migration rule. Without the `record-rule` tool (Boost MCP not set up), write the rule files in `.ai/rules` by hand in Boost's format or report the rule text, as `infer-conventions` does.
   - Foreign docs (part of the existing-project mode, replaces v1 `/migrate`): look for `PROJECT.md`, `CONVENTIONS.md`, `STACK.md`, `ARCHITECTURE.md`, `WORKFLOW.md`, `*specification*`, `*guideline*` and agent instruction files outside `vendor/` and `node_modules/` (ignore the Boost-generated block in `CLAUDE.md`/`AGENTS.md`); when any exist, offer the migration: a plan with the actions MERGE, MOVE, CONVERT, KEEP, ARCHIVE and the target per file (`docs/`, `.ai/guidelines/project/`, `.ai/rules`), shown first as a dry run, executed only after the user confirms, nothing deleted without approval (sources archived).
   - New project (kickoff): ask for name, goal, users and roles, domains, core entities, stack, MVP scope, integrations; when `pestphp/pest` is not in `composer.json`, add the question whether to install Pest (recommended), and on yes run `composer require pestphp/pest --dev -W` and `vendor/bin/pest --init` (D-38). Build the shell without business logic: `app/<Domain>/Actions/.gitkeep` and `app/<Domain>/Models/.gitkeep` per domain; fill `docs/GUIDELINES.md` (stack, domain map), `docs/SPECIFICATIONS.md` (feature backlog in build order, each item with goal, scope and acceptance criteria so the list serves as the brief of a multi-session initiative), `docs/DECISIONS.md` with ADR-001 on the stack; copy `stubs/pint.json` to `pint.json`; copy `stubs/tests.yml` to `.github/workflows/tests.yml`; only when Pest is installed write `tests/Arch/ArchitectureTest.php` from `stubs/ArchitectureTest.php.stub` with one block per domain (without Pest, skip it and say so); set `stack` and `architecture` in `config/kit.php`; after installing Pest run `php artisan kit:update` so the `pest/core` override is added.
   - Both modes end with `php artisan boost:update` so the guidelines re-render. The stub paths are relative to the skill folder (for example `.claude/skills/kit-onboard/stubs/`).
3. `resources/boost/skills/kit-onboard/stubs/pint.json` (new, preset `laravel`), `stubs/tests.yml` (new: GitHub workflow on push and pull_request, PHP from `composer.json`, `composer install`, `npm ci && npm run build` when `package.json` exists, `vendor/bin/pint --test`, `php artisan test --parallel --compact`; works with Pest and PHPUnit), `stubs/ArchitectureTest.php.stub` (new: Pest `arch()` expectations with placeholder `{{DOMAIN}}`: Actions in `App\\{{DOMAIN}}\\Actions` have a `handle` method; controllers do not use `Illuminate\\Support\\Facades\\DB`; no `App\\Services` namespace). Use only Pest arch APIs that exist in Pest 3, 4 and 5.
4. `resources/boost/skills/kit-docs-split/SKILL.md` (new) and `resources/boost/skills/kit-docs-split/templates/README.md`, `DOMAIN_README.md` (sections: When to read, Entry points, Code map, Invariants, Tests, Decisions, Neighbours, Files), `DOMAIN_SPEC.md`, `CROSS_CUTTING.md`, `ADR.md` (all new). The skill converts flat to split: map in `docs/README.md`, one folder per domain with README and SPECIFICATIONS, cross-cutting concerns, each ADR from `DECISIONS.md` into `docs/decisions/ADR-0NN-<slug>.md` with `DECISIONS.md` as index, templates copied to `docs/_templates/`; every document reachable from the map; nothing lost (check by listing every heading of the flat files and where it went); ends with `php artisan boost:update`.
5. Extend `SkillDiscoveryTest` to exactly the five skills `kit-onboard`, `kit-spec`, `kit-review`, `kit-brief`, `kit-docs-split` (no `kit-test`, no `kit-migrate`).

## Acceptance criteria

Serves: AC-9, AC-12

- Boost discovers exactly the five `kit-*` skills without parse failures. Evidence: `SkillDiscoveryTest`.
- `kit-onboard` names both modes, the architecture question, the manifest-based mode detection, the quiet mode, the foreign-docs migration with dry run and confirmation, the Pest offer and the `record-rule` fallback. Evidence: `OnboardStubsTest` asserts "Existing project", "New project", `.ai/kit-manifest.json`, `quiet`, `dry-run`, `PROJECT.md`, `composer require pestphp/pest --dev -W` and `record-rule` in the SKILL.md.
- `stubs/pint.json` is valid JSON, `stubs/tests.yml` parses with `Symfony\\Component\\Yaml\\Yaml`, the architecture stub with `{{DOMAIN}}` = `Billing` passes `php -l`. Evidence: `OnboardStubsTest`.
- The flat doc stubs contain the three placeholders and no `.claude/rules`. Evidence: `OnboardStubsTest`.
- No client name, customer data or real project name in the skills, templates, stubs or the commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Skills/OnboardStubsTest.php`: the checks above; split templates exist and `DOMAIN_README.md` contains the eight section headings.
- Update `tests/Feature/Skills/SkillDiscoveryTest.php` (five names).

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- Writing flat docs into projects (package 7 `kit:install`).
- Commands and hooks (packages 7 to 12).

## Report

Shipped kit-onboard (existing project with infer-conventions sweep, max 6-8 questions incl. architecture decision, record-rule with manual fallback, foreign-docs migration MERGE/MOVE/CONVERT/KEEP/ARCHIVE with dry run and confirmation; new project kickoff with Pest offer and project shell), kit-docs-split with five split templates, the onboard stubs (pint.json, tests.yml, ArchitectureTest.php.stub) and the flat doc stubs under stubs/docs/flat/. SkillDiscoveryTest now expects exactly the five kit skills; new OnboardStubsTest. Full suite 123 passed, Pint clean. Two commits: 73b956b, 47cb5c0.

## Handoff

Files: stubs/docs/flat/{GUIDELINES,SPECIFICATIONS,DECISIONS}.md with placeholders {{PROJECT_NAME}}, {{STACK}}, {{ARCHITECTURE}} (package 7 kit:install replaces them; no other placeholders, the rest are HTML comments). resources/boost/skills/kit-onboard/{SKILL.md, stubs/pint.json, stubs/tests.yml, stubs/ArchitectureTest.php.stub}; resources/boost/skills/kit-docs-split/{SKILL.md, templates/README.md, DOMAIN_README.md, DOMAIN_SPEC.md, CROSS_CUTTING.md, ADR.md}. Templates use {{PROJECT_NAME}}, {{DOMAIN}}, {{CONCERN}}, {{TITLE}} as fill-in markers for the agent (.md files are not Blade-rendered by Boost, only Markdown-formatted). kit-onboard reads the manifest key `project` and falls back to the D-23 migration rule; it archives migrated foreign docs to .ai/kit-upgrade/foreign/ (outside .ai/guidelines so Boost does not load them) and calls `php artisan kit:update` after installing Pest, so package 9 kit:update must add the pest/core override when Pest appears. kit-docs-split removes docs/GUIDELINES.md and docs/SPECIFICATIONS.md after the split (DECISIONS.md stays as index); kit:doctor/kit:upgrade should not assume GUIDELINES.md in split layout. The arch stub was run against Pest 5 (passes, vacuous for missing namespaces); APIs used: arch(), expect, not->toUse, not->toBeUsed, classes(), toHaveMethod. tests.yml reads the PHP version from composer.json via jq and runs `php artisan test --parallel --compact`; the skill tells PHPUnit projects to add brianium/paratest or drop --parallel. OnboardStubsTest uses Symfony\Component\Yaml (transitive via laravel/roster, not in require-dev). D-1: staged diff grepped against the name list from the package 13 spec, only hit is the package namespace Towebgmbh; commit messages generic.

## Decisions

- Foreign-docs ARCHIVE target is .ai/kit-upgrade/foreign/ (spec only said 'sources archived'); outside .ai/guidelines so Boost never loads them.
- Agent instruction files searched: CLAUDE.md, AGENTS.md (outside the <laravel-boost-guidelines> block), GEMINI.md, .cursorrules, .cursor/rules/, .github/copilot-instructions.md, .junie/, .claude/commands/.
- Quiet mode picks the architecture from code: Services only -> services, Actions only -> actions, both -> migrating; assumptions marked with <!-- assumption: ... --> in docs.
- Manual record-rule fallback documented in Boost's format: .ai/rules file with frontmatter paths, # heading, ## title + note, plus a row in .ai/rules/index.md.
- ArchitectureTest stub: shared rules once (controllers not using DB facade, App\Services not used) plus a marked domain block repeated per domain; 'no App\Services namespace' is expressed as not->toBeUsed().
- tests.yml derives the PHP version from composer.json with jq, runs npm steps only when package.json exists (hashFiles), copies .env.example and generates a key; skill notes paratest for PHPUnit --parallel.
- kit-docs-split deletes flat GUIDELINES.md and SPECIFICATIONS.md after every heading is mapped; DECISIONS.md becomes the ADR index.
- Added kit-docs-split templates README.md (map) with {{PROJECT_NAME}} and DOMAIN_SPEC.md with status legend and Backlog section.
- SkillDiscoveryTest constant renamed KIT_SKILLS_SO_FAR -> KIT_SKILLS.
- OnboardStubsTest also asserts MERGE, ARCHIVE, 'Architecture decision' and 'php artisan boost:update' in kit-onboard.

## Checks

- `vendor/bin/pest tests/Feature/Skills`: exit 0, 32 passed
- `vendor/bin/pest tests/Unit/TmpArchTest.php`: exit 0, arch stub with Billing: 3 passed on Pest 5 (temp file removed)
- `vendor/bin/pest --compact`: exit 0, 123 passed (433 assertions)
- `vendor/bin/pint --test`: exit 0, passed
