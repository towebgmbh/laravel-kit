# Planning & Implementation Workflow

## Before Writing Code
- Understand the full scope before touching any file. Ask clarifying questions if requirements are unclear.
- Check `docs/SPECIFICATIONS.md` for existing plans and `docs/DECISIONS.md` for past architectural choices.
- Identify which domains are affected, what can be reused, and what needs to be created.

## Planning Output
When planning a feature (whether asked explicitly or using Plan mode), produce:
- **Goal**: one sentence, user-perspective.
- **Affected domains** and existing code to reuse.
- **Data model changes**: new models, fields, relationships, migrations.
- **Actions** to create with responsibility and signature.
- **Routes & controllers**: HTTP method, URI, controller method.
- **Frontend**: pages, components, forms.
- **Test scenarios**: happy path, edge cases, authorization.
- Offer to save the plan to `docs/SPECIFICATIONS.md`.

## Implementation Phases
Implement in logical, committable phases. Each phase leaves the app in a working state.
1. **Data layer**: migrations, models, factories, enums
2. **Business logic**: actions, DTOs, events — with unit tests for each Action
3. **API/Routes**: controllers, form requests, route registration — with feature tests per endpoint
4. **Frontend**: pages, components, forms
5. **Integration**: verify full flow end-to-end, add edge case tests

Write tests alongside the code in each phase, not as a final bolt-on step. Run `php artisan test` after each phase. Do not proceed if tests fail.

## After Implementation
- Run the full test suite.
- Verify routes with `php artisan route:list`.
- Update `docs/SPECIFICATIONS.md` to reflect the implemented feature.
- Review your own changes against the rules in this directory before presenting them.
