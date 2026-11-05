# Laravel Kit

The Laravel Kit sets the structure and the workflow of this project. Boost's guidelines and skills cover the framework and package APIs of the installed versions; the kit does not repeat them.

## Size Rule

Classify every piece of work as small, medium or large, state the size in one sentence and start without asking; the user may reclassify it at any time.

- **Small** (fix, adjustment, few files, no new behaviour across layers): implement directly and run only the affected tests. No spec, no plan. Update the docs only when documented behaviour or structure changes.
- **Medium** (several layers, or a change of existing behaviour): give a short plan in the chat, implement, then update the affected spec entry and the docs.
- **Large** (new feature, new domain, initiative): write a spec with `/kit-spec` and get the user's approval. The spec numbers its acceptance criteria (`AC-1`, `AC-2`, ...). Do not implement while the spec lists a blocking open question; ask instead of guessing.
- Build large work in the phases Data, Logic, Routes, Frontend, Integration. Each phase is committable and leaves the app working. Write tests with the code, run only the affected tests per phase and never the complete suite per phase. Tests name the criterion they cover.

## Documentation

- Keeping `docs/` up to date is a standing request of the user, so the rule to create documentation only on request does not apply to `docs/`.
- The domain folders listed in the project docs are approved base folders.
- The docs must always match the code: updating them is part of the definition of done (see the size rule).
@if (file_exists(base_path('docs/README.md')))
- Read `docs/README.md` first, then only the affected domains.
- `docs/README.md` is the map. Each domain has `docs/domains/<domain>/README.md` (index card) and `SPECIFICATIONS.md`; concerns across domains live in `docs/cross-cutting/<concern>/`; decisions are `docs/decisions/ADR-0NN-*.md`, indexed in `docs/DECISIONS.md`; templates are in `docs/_templates/`.
- Everything must be reachable from `docs/README.md`; register every new document there.
@else
- Read `docs/GUIDELINES.md` first.
- `docs/GUIDELINES.md` holds the domains and project rules, `docs/SPECIFICATIONS.md` the specs, `docs/DECISIONS.md` the decisions.
- When the project reaches {{ config('kit.docs.split_threshold', 4) }} domains, run `/kit-docs-split`.
@endif
- Record deviations from the kit as rules in `.ai/rules` with Boost's `record-rule` MCP tool. Without it, write the rule file in `.ai/rules` by hand, or report the rule text to the user.

## Tests

- Follow the test line in the Test Enforcement rules.
