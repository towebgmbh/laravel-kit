---
name: kit-brief
description: Summarize the current project state for session orientation. Use at the start of a session or when re-orienting after a break.
argument-hint: "[domain name] or empty for the full project overview"
---

# Project Brief

Build a short, factual picture of the project. Read-only: never modify code and never run the complete test suite.

## Step 1: Read the Docs

- If `docs/README.md` exists, the docs are split: read it first (the map), then only the domains asked for, or the ones with work in progress.
- Otherwise read `docs/GUIDELINES.md`, `docs/SPECIFICATIONS.md` and `docs/DECISIONS.md`.
- Also read `config/kit.php` and the rules in `.ai/rules` and `.ai/guidelines/project`.

If the docs are missing or contain only placeholders, say so and recommend `/kit-onboard`. Work from the code for now.

## Step 2: Scan the Code

Use Boost's MCP tools if available (application info, schema, routes); otherwise read the files and use `ls`, `find` and `git log`.

- Domain folders under `app/`, with models and action counts.
- Migrations, routes and test files (counts only).
- Recent changes: `git log --oneline -10`.

## Step 3: Specs in Progress

List the specs with status 🚧 In Progress or 🔲 Planned, with their open questions. Mark the blocking ones: work on that spec waits until the user answers.

## Step 4: Health

Run `php artisan kit:doctor` and report its warnings. Report missing docs and anything that contradicts the recorded rules.

## Summary

- **Project**: name, goal, stack (`config('kit.stack')`), architecture mode (`config('kit.architecture')`), docs layout (flat or split).
- **Domains**: one line each.
- **Specs**: in progress, planned, blocking questions.
- **State**: recent changes, doctor warnings, missing docs.

If a domain was requested, go deeper: its models, actions, controllers, routes, tests and relationships. Keep it scannable.
