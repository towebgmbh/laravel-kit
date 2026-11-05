# Feature Kickoff Template

Use this template when handing off a new feature to Claude Code. Fill it in and pass it as the initial prompt, or append it to `docs/SPECIFICATIONS.md`.

---

```
Feature: [Name]

## What
[One paragraph: what does this feature do from the user's perspective?]

## Why
[What problem does it solve?]

## Scope
- IN: [What's included in this iteration]
- OUT: [What's explicitly excluded / deferred]

## User Stories
- As a [role], I want to [action], so that [benefit].

## Data
- Models involved: [existing or new]
- Key relationships: [e.g., Vehicle hasMany Listings]
- New fields: [list with types]

## UI/UX
- Pages needed: [list with route]
- Key interactions: [forms, filters, modals]
- Reference: [link to design/mockup if available]

## Constraints
- [Technical constraints, API limits, package requirements]
- [Performance requirements]
- [Multi-tenancy considerations]

## Done When
- [ ] All endpoints working with validation
- [ ] Tests passing (feature + unit)
- [ ] UI renders correctly
- [ ] Spec updated in docs/SPECIFICATIONS.md
```
