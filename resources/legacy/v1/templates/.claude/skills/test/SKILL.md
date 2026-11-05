---
name: test
description: Write or extend PEST tests for a specific action, controller, feature, or file. Use when explicitly asked to write tests or improve test coverage.
allowed-tools: Read, Write, Grep, Glob, Bash(php artisan test:*), Bash(./vendor/bin/pest:*)
argument-hint: [action class, controller, feature name, or file path]
---

# Write & Run Tests

Write or extend PEST tests for the given target.

## Determine What to Test

- Action class → unit tests for `execute()` in `tests/Unit/{Domain}/`.
- Controller → feature tests for all endpoints in `tests/Feature/{Domain}/`.
- Feature name → find related Actions and Controllers, test both layers.
- File path → determine type and test accordingly.

## Before Writing

Check `tests/Feature/` and `tests/Unit/` for existing tests. Extend rather than duplicate.

## Write Tests

Use PEST syntax with `describe()` blocks and `it()` with readable descriptions.

### Per Endpoint, Cover:
- Authenticated user can perform the action
- Unauthenticated user gets 401/redirect
- Unauthorized user gets 403
- Valid input produces expected result and database state
- Invalid input returns validation errors with correct fields
- Edge cases: empty states, boundary values, duplicates

### Per Action, Cover:
- Returns expected output for valid input
- Handles edge cases gracefully
- Side effects occur (events dispatched, records created)
- Throws appropriate exceptions for invalid state

## Run Tests

```bash
php artisan test --filter={TestFileName}
```

Fix failures before considering the task done. Then run the full suite:

```bash
php artisan test
```

## Rules
- PEST syntax only. No PHPUnit classes.
- Use `RefreshDatabase`. Each test creates its own state via factories.
- Never mock Eloquent models. Use factories with explicit attributes for test-critical data.
- Test behavior, not implementation details.
- Mock external APIs and file operations to keep tests fast.
