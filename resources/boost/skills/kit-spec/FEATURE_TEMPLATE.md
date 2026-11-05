# Feature: [Name]

Status: 🔲 Planned

## What

[One paragraph: what does this feature do from the user's perspective?]

## Why

[What problem does it solve?]

## Scope

- IN: [What is included in this iteration]
- OUT: [What is explicitly excluded or deferred]

## User Stories

- As a [role], I want to [action], so that [benefit].

## Data

- Models involved: [existing or new]
- Key relationships: [e.g. Invoice hasMany Lines]
- New fields: [list with types]

## UI/UX

- Pages needed: [list with route]
- Key interactions: [forms, filters, modals]
- Reference: [link to design or mockup, if available]

## Constraints

- [Technical constraints, API limits, package requirements]
- [Performance requirements]
- [Multi-tenancy or permission considerations]

## Open Questions

List every question that is not answered yet. Mark each one `blocking` or `non-blocking`.

- [ ] [blocking] [Question]
- [ ] [non-blocking] [Question]

Do not implement while a blocking question is open; ask the user instead of guessing.

## Phases

Each phase is committable and leaves the app working. Run only the affected tests per phase.

1. **Data**: migrations, models, factories.
2. **Logic**: actions, DTOs, policies, events.
3. **Routes**: controllers, form requests, routes.
4. **Frontend**: pages, components, views.
5. **Integration**: external services, jobs, notifications.

## Tests

- Feature tests for endpoints and flows, unit tests for framework-free logic.
- Required: permissions, state transitions, money and quantity calculations, integrations with `Http::fake()`.
- Every test names the criterion it covers, e.g. `it('rejects an unpaid invoice (AC-2)', ...)`.

## Docs to Update

- [Files under `docs/` this feature changes: specification, guidelines, decisions, domain index card]

## Acceptance Criteria

Numbered, checkable criteria. Each names how it is verified: a test or a named manual check.

- AC-1 [Observable behaviour]. Verified by: [test file or `it()` name | manual check: what to do and see]
- AC-2 [Observable behaviour]. Verified by: [test file or `it()` name | manual check: what to do and see]
