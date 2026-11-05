---
name: review
description: Review code changes against project architecture rules and quality standards. Use when explicitly asked to review code, a commit, or specific files.
allowed-tools: Read, Grep, Glob, Bash(git diff:*), Bash(git log:*)
argument-hint: [file path, "last commit", or empty for uncommitted changes]
---

# Code Review

Review changes against the project's architecture rules and quality standards.

## Tool Preference

If the `laravel-boost` MCP server is available, use its tools to enhance the review:
- Use Boost's schema inspection to verify migration changes match the actual database state
- Use Boost's search-docs to confirm that patterns in reviewed code follow current Laravel best practices
- Use Boost's route listing to verify route registration for new controllers

Fall back to standard file reading and Bash commands if Boost MCP is not connected.

## Determine Scope

- If `$ARGUMENTS` is a file path: review that file.
- If `$ARGUMENTS` is "last commit": review `git diff HEAD~1`.
- If `$ARGUMENTS` is empty: review all uncommitted changes (`git diff` + `git diff --cached`).

## Check Against Rules

Read all files in `.claude/rules/` first. Then verify each changed file against every applicable rule. The rules cover architecture, Laravel conventions, frontend conventions (project-specific — check `03-frontend.md`), testing, quality, and workflow.

Pay special attention to:
- Architecture: Actions pattern, domain organization, no God-classes, explicit types
- Laravel: naming conventions, routing, migration standards, Eloquent patterns
- Frontend: whatever conventions `03-frontend.md` defines for this project
- Testing: coverage exists, PEST conventions, factory usage
- Quality: no debug code, error handling, security, performance

Also check `docs/GUIDELINES.md` for project-specific deviations from the kit rules. If the project intentionally differs from a rule, review against the project's convention, not the kit's.

## Check for Common Issues

- N+1 query risks (missing eager loads)
- Missing validation rules in Form Requests
- Missing authorization checks (Policies, Gates)
- Unused imports or variables
- Missing return types on Actions and public methods
- Inconsistent naming
- Jobs missing `$tries`, `$backoff`, or `failed()` method
- Raw model returns instead of API Resources in JSON endpoints

## Report Format

**✅ Good**: Things done well (reinforce good practices).

**⚠️ Issues**: Each issue with file path, line reference, rule violated, suggested fix. Distinguish between blocking issues and suggestions.

**🧪 Test Coverage**: Missing tests, suggested test scenarios.

**📋 Verdict**: Ready / Needs changes / Needs discussion.

## Rules
- Be specific. Reference exact file paths and line numbers.
- Focus on architecture, logic, and correctness — not formatting nitpicks.
- Group feedback by domain for large changesets.
