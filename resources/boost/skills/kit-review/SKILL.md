---
name: kit-review
description: Review code changes against the project structure, the test line, the specs and the docs. Use when explicitly asked to review code, a commit or specific files.
argument-hint: "[file path, \"last commit\", or empty for uncommitted changes]"
---

# Code Review

Review changes for correctness, structure, tests, spec status and documentation drift.

## Step 1: Load the Best Practices

Activate Boost's `laravel-best-practices` and `testing-best-practices` skills first. They define the framework and test conventions of the installed versions; do not restate them here.

## Step 2: Determine the Scope

- If the argument is a file path: review that file.
- If the argument is "last commit": review `git diff HEAD~1`.
- If the argument is empty: review all uncommitted changes (`git diff` and `git diff --cached`).

If Boost's MCP tools are available, use them to inspect the schema, routes and docs; otherwise read the files.

## Step 3: Check

- **Structure**: read `config('kit.architecture')` and `config('kit.stack')` (or `config/kit.php`) and check the changes against the matching guideline: where business logic lives, thin controllers, DTOs, Form Requests, Policies, models, the stack's folders and patterns.
- **Deviations**: read the rules in `.ai/rules` and `.ai/guidelines/project`. If the project differs from the kit on purpose, review against the recorded rule.
- **Tests**: required areas are covered (permissions, state transitions, money and quantity calculations, integrations with `Http::fake()`); nothing is tested twice; no tests for framework behaviour, getters or HTML; datasets instead of copies; only external services are mocked.
- **Docs drift**: apply the size rule. Medium and large changes must update the spec and the docs; small changes only when documented behaviour or structure changed. Report code that changed without the matching docs.
- **Common issues**: N+1 queries, missing validation or authorization, missing return types, raw models returned from JSON endpoints, jobs without `$tries`, `$backoff` or `failed()`.

## Step 4: Specs and Acceptance Criteria

For work that has a spec (`docs/SPECIFICATIONS.md` or `docs/domains/<domain>/SPECIFICATIONS.md`):

- Check the spec status (🔲 Planned, 🚧 In Progress, ✅ Implemented, ❌ Dropped) matches the code.
- Check the acceptance criteria coverage: every acceptance criterion `AC-n` needs a test that names it or a named manual check in the spec. List every uncovered criterion as blocking.
- Report a spec with a blocking open question that was implemented anyway.

## Report

Group the findings and give each one a file and line:

- **Blocking**: must be fixed (bugs, missing authorization, uncovered acceptance criteria, wrong structure).
- **Non-blocking**: suggestions.
- **Good**: what was done well, briefly.
- **Verdict**: Ready, Needs changes or Needs discussion.

## Rules

- Read-only: never modify code. Do not run the complete test suite; run only the tests of the reviewed change if you need evidence.
- Focus on logic, structure and correctness, not formatting (Pint handles that).
- Be specific and reference exact files and lines.
