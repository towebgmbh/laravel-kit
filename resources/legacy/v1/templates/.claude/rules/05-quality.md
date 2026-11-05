# Quality Standards

## Code Quality
- No `TODO`, `FIXME`, or `HACK` comments in committed code. Track issues externally.
- No commented-out code. Delete it — Git has history.
- No `dd()`, `dump()`, `ray()`, or `console.log()` in committed code.
- No hardcoded values that should be config or constants.
- Methods should do one thing. If you need `and` to describe it, split it.

## Error Handling
- Use custom exceptions for domain-specific errors (`VehicleImportFailed`, `ListingNotFound`).
- Never silently swallow exceptions. Log or rethrow.
- Use `abort()` and `abort_if()` for HTTP error responses in controllers.
- Return meaningful error messages in API responses.

## Security
- Always use Form Requests for validation. Never trust input.
- Use Policies for authorization. Check permissions explicitly.
- Escape output in Blade (use `{{ }}` not `{!! !!}` unless deliberately rendering HTML).
- Never expose internal IDs in URLs if using UUIDs for public-facing routes.
- Use `Gate::authorize()` or `$this->authorize()` in controllers.

## Performance
- Eager load relationships. Fix N+1 queries before committing.
- Use pagination for list endpoints. Never return unbounded collections.
- Use database indexes for frequently queried columns.
- Cache expensive computations and external API responses where appropriate.
- Use `lazy()` or chunk processing for large datasets.

## Git & Commits
- Commit messages: imperative mood, concise (`Add vehicle listing export`, `Fix review score calculation`).
- One logical change per commit. Don't mix refactoring with features.
- Never commit `.env`, credentials, or API keys.

## Before Marking Work as Done
- All existing tests pass (`php artisan test`).
- New code has appropriate tests.
- No IDE warnings or type errors.
- Code follows the naming conventions in these rules.
- Migrations are reversible (implement `down()` method).
- Frontend components render without console errors.
