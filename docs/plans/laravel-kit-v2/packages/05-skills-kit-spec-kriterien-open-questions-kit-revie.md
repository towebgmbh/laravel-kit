<!-- Jarvis maintains this file and overwrites it before every work package. Change the plan on the initiative page in Jarvis. -->

# Package 5: Skills kit-spec (Kriterien, Open Questions), kit-review und kit-brief

Kind: implement · Status: running · Model: sonnet · Effort: medium · Commits: – · Verdict: –

## Spec

## Goal

Ship three agent skills under `resources/boost/skills/` that Boost discovers and syncs into every agent's skill folder: `kit-spec` (feature template with numbered acceptance criteria and "Open Questions"), `kit-review` (including acceptance-criteria coverage) and `kit-brief`. They modernise the v1 skills to the v2 layout (`.ai/guidelines`, `.ai/rules`, flat or split `docs/`), the size rule and the test line. There is no `kit-test` and no `kit-migrate` (D-13).

Public repository (D-1): generic examples only. Content in English (D-2).

## Context to read

- Concept sections Users and flows (3), Design (Doku-Layouts, Einklinken in Boost, Öffentliches Repo), Decisions D-1, D-7, D-10, D-11, D-13, D-32, D-33, D-34.
- Package 3: `resources/boost/guidelines/core.blade.php` (size rule), `stubs/ai/guidelines/enforce-tests.blade.php` (test line).
- v1 sources: `resources/legacy/v1/templates/.claude/skills/{review,brief}/SKILL.md`, `.claude/templates/FEATURE_TEMPLATE.md`, `docs/SPECIFICATIONS.md` (status legend).
- Boost skill handling: `vendor/laravel/boost/src/Install/SkillComposer.php` (`parseSkill()`, frontmatter needs `name` and `description`; `SKILL.md` or `SKILL.blade.php`), `src/Install/SkillWriter.php` (`copyDirectory()` copies the whole skill folder; `.md` is reformatted, `.blade.php` rendered).
- Boost skills referenced: `vendor/laravel/boost/.ai/laravel/skill/laravel-best-practices/`, `testing-best-practices/`.

Tooling: if `php` is not on PATH, `export PATH="$HOME/Library/Application Support/Herd/bin:$PATH"`.

## Tasks

1. `resources/boost/skills/kit-spec/FEATURE_TEMPLATE.md` (new, modernised v1 template): What, Why, Scope, User Stories, Data, UI/UX, Constraints, Open Questions, Phases (Data, Logic, Routes, Frontend, Integration), Tests, Docs to update, Acceptance Criteria. "Acceptance Criteria" replaces v1's "Done When" and lists numbered, checkable criteria `AC-1`, `AC-2`, ..., each with how it is verified (a test or a named manual check) (D-33). "Open Questions" lists questions, each marked `blocking` or `non-blocking`, with the sentence "Do not implement while a blocking question is open; ask the user instead of guessing." (D-34).
2. `resources/boost/skills/kit-spec/SKILL.md` (new): only for large work per the size rule (for small or medium work it says so and stops); writes or updates the spec in `docs/SPECIFICATIONS.md` (flat) or `docs/domains/<domain>/SPECIFICATIONS.md` (split, detected by `docs/README.md`) from the template next to the SKILL.md, with the v1 status legend; asks the user to approve the spec and to answer blocking open questions before implementation.
3. `resources/boost/skills/kit-review/SKILL.md` (new): scope argument like v1 (file path, `last commit`, empty = uncommitted changes); first activate `laravel-best-practices` and `testing-best-practices`; then check structure against `config('kit.architecture')` and `config('kit.stack')`, the test line (nothing twice, required areas), docs drift (code changed without docs, per the size rule), deviations recorded in `.ai/rules`; for work with a spec: the spec status, and that every acceptance criterion `AC-n` has a test naming it or a named manual check (list uncovered criteria as blocking). Output findings as blocking / non-blocking with file and line.
4. `resources/boost/skills/kit-brief/SKILL.md` (new): v1 `brief` adapted: read `docs/README.md` first in the split layout, then only the domains asked for; flat: `docs/GUIDELINES.md`; lists specs in progress and their open questions; reports missing docs and `php artisan kit:doctor` warnings. Must not run the full test suite.
5. Frontmatter for every skill: `name` equal to the folder name, `description` (one or two sentences, when to use), `argument-hint` where v1 had one. No `allowed-tools`. No sentence from `Contradictions::SENTENCES`.

## Acceptance criteria

Serves: AC-9, AC-12, AC-14

- Boost's `SkillComposer` with the fixture and `aiGuidelines = ['towebgmbh/laravel-kit']` discovers `kit-spec`, `kit-review`, `kit-brief` with non-empty descriptions and records no parse failure (`Laravel\\Boost\\Support\\SkillParseFailures`). Evidence: `SkillDiscoveryTest`.
- The template contains the headings "Open Questions" and "Acceptance Criteria", the token `AC-1` and the do-not-implement sentence; `kit-review` contains "acceptance criterion" coverage instructions. Evidence: `SkillContentTest`.
- No skill text contains a contradiction sentence or the words "full suite after each phase". Evidence: `SkillContentTest`.
- No client name, customer data or real project name in the skill texts, examples or the commit message (D-1). Evidence: `git diff --cached` and the commit message reviewed before committing; stated in the handoff.

## Tests to write

- `tests/Feature/Skills/SkillDiscoveryTest.php`: build `new SkillComposer(app(ProjectManager::class), $config)` on the activated fixture; assert exactly the kit skills present so far (package 6 extends the list to five); frontmatter `name` equals folder.
- `tests/Feature/Skills/SkillContentTest.php`: template and review markers; contradiction check over every file in `resources/boost/skills`.

## Checks to run

- `vendor/bin/pest`
- `vendor/bin/pint --test`

## Out of scope

- `kit-onboard`, `kit-docs-split`, flat doc stubs and new-project stubs (package 6).
- Commands (packages 7 to 12).
