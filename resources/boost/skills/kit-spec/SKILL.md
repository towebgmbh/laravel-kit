---
name: kit-spec
description: Write or update the specification of a large feature, new domain or initiative, with numbered acceptance criteria and open questions. Use before implementing large work, after classifying it with the size rule.
argument-hint: "[feature name]"
---

# Feature Specification

Write the spec for large work. This skill is only for work the size rule classifies as large.

## Step 1: Check the Size

- Small or medium work needs no spec. Say so in one sentence and stop; follow the size rule instead (direct change, or a short plan in the chat and a spec entry afterwards).
- Large work (new feature, new domain, initiative) continues below.

## Step 2: Find the Spec File

- Read `docs/README.md`. If it exists, the docs are split: use `docs/domains/<domain>/SPECIFICATIONS.md` for the domain of the feature (create the domain folder from `docs/_templates/` if the feature opens a new domain, and register it in `docs/README.md`).
- Otherwise the docs are flat: use `docs/SPECIFICATIONS.md`.
- Read the existing specs of the affected domain first, and `docs/GUIDELINES.md` or the domain index card, so the new spec fits.

## Step 3: Write the Spec

Fill in `FEATURE_TEMPLATE.md` (next to this file) for the feature and add it to the spec file. Ask the user only for what you cannot infer from the code and the docs.

- Keep the heading `Acceptance Criteria` with numbered criteria `AC-1`, `AC-2`, ...: observable, checkable, each with how it is verified (a test or a named manual check).
- Keep the heading `Open Questions`. Mark each question `blocking` or `non-blocking`. Do not implement while a blocking question is open; ask the user instead of guessing.
- Plan the tests with the test line: nothing twice, feature tests for endpoints and flows, unit tests for framework-free logic. Details are in Boost's `testing-best-practices` skill.
- Name the files under `docs/` the feature updates.

Status legend for the heading of each spec:

- 🔲 Planned
- 🚧 In Progress
- ✅ Implemented
- ❌ Dropped

## Step 4: Get Approval

Show the spec to the user and ask them to approve it and to answer every blocking open question. Do not start the Data phase before both are done. While implementing, set the status to 🚧 and to ✅ when every criterion is met; update the spec when the scope changes.
