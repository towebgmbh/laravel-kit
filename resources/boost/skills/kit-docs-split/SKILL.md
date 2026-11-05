---
name: kit-docs-split
description: Convert the flat docs (docs/GUIDELINES.md, SPECIFICATIONS.md, DECISIONS.md) into the split layout with a map, one folder per domain, cross-cutting concerns and one file per ADR. Use when the project reaches the split threshold in config/kit.php or the flat files get too long.
argument-hint: "[dry-run] to show the plan only"
---

# Split the Docs

Move the flat docs into the split layout without losing anything. The agent then reads the map first and only the affected domains.

## Target Layout

```
docs/
├── README.md                      map: overview, stack, domains, cross-cutting, decisions
├── DECISIONS.md                   index of the ADRs
├── domains/<domain>/README.md     index card of the domain
├── domains/<domain>/SPECIFICATIONS.md
├── cross-cutting/<concern>/README.md
├── decisions/ADR-0NN-<slug>.md
└── _templates/                    the templates of this skill
```

The templates are in this skill's `templates/` folder (for example `.claude/skills/kit-docs-split/templates/`).

## Step 1: Check

- If `docs/README.md` exists, the docs are already split. Say so and stop.
- Read `docs/GUIDELINES.md`, `docs/SPECIFICATIONS.md`, `docs/DECISIONS.md` and the domain folders under `app/`.
- Make the heading inventory: list every heading (`#` to `####`) of the three flat files. Each one gets a target in the plan.

## Step 2: Plan

Build the plan and show it:

- **Domains**: one folder `docs/domains/<domain>/` per domain of the domain map (lowercase, kebab-case), with the code paths it covers.
- **Specs**: every feature of `SPECIFICATIONS.md` and every backlog item goes to the `SPECIFICATIONS.md` of its domain. A feature across domains goes to the domain that owns its main entity and is linked from the others.
- **Cross-cutting**: concerns that span domains (for example authentication, multi-tenancy, integrations, deployment) go to `docs/cross-cutting/<concern>/README.md`.
- **Decisions**: every ADR of `DECISIONS.md` becomes `docs/decisions/ADR-0NN-<slug>.md` with its number kept; `DECISIONS.md` becomes the index.
- **Map**: overview, stack, architecture mode, deviations and environment notes of `GUIDELINES.md` go to `docs/README.md`.
- The heading inventory with the target of each heading.

With `dry-run`, stop here. Otherwise ask the user to confirm the plan.

## Step 3: Write

1. Copy `templates/` to `docs/_templates/`.
2. Write `docs/README.md` from `templates/README.md`.
3. Per domain: `docs/domains/<domain>/README.md` from `templates/DOMAIN_README.md` (fill every section from the code and the old docs; write "None yet" rather than leaving a section out) and `SPECIFICATIONS.md` from `templates/DOMAIN_SPEC.md`.
4. Per concern: `docs/cross-cutting/<concern>/README.md` from `templates/CROSS_CUTTING.md`.
5. Per ADR: `docs/decisions/ADR-0NN-<slug>.md` from `templates/ADR.md`, content unchanged. Rewrite `docs/DECISIONS.md` as the index: one line per ADR with number, title, status and link.
6. Remove `docs/GUIDELINES.md` and `docs/SPECIFICATIONS.md` once every heading has landed (they live on in git history).

## Step 4: Verify

- Every document under `docs/` is reachable from `docs/README.md` (directly or through a domain card or the decisions index). List any orphan and link it.
- Nothing lost: go through the heading inventory and name the file each heading went to. Every heading needs a target.
- Links between documents resolve.

## Finish

Run `php artisan boost:update` so the guidelines switch to the split layout. Summarize the new structure and the heading inventory.

## Rules

- Move content, do not rewrite it. Shorten only duplicates.
- From now on, register every new document in `docs/README.md`; new domains start from `docs/_templates/`.
