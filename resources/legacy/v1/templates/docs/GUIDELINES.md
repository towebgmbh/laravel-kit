# Project Guidelines

> This document describes the project's stack, key decisions, and conventions that go beyond the general rules in `.claude/rules/`. Update it as the project evolves.

## Project Overview

**Name**: {{PROJECT_NAME}}
**Description**: {{PROJECT_DESCRIPTION}}
**Type**: {{FRONTEND_TYPE}}

## Stack

| Layer        | Technology                     |
|--------------|-------------------------------|
| Backend      | Laravel 12, PHP 8.3+          |
| Frontend     | {{FRONTEND_TECH}}             |
| Testing      | PEST                          |
| IDE          | PHPStorm                      |
| CI/CD        | —                             |
| Hosting      | —                             |

## Installed Packages

> Update this list as packages are added.

- **laravel/boost** — Project scaffolding and AI configuration
- **spatie/laravel-medialibrary** — File and image management

## Domain Map

> List the domains and their responsibilities as they emerge.

<!--
Example:
- **Vehicles**: Vehicle models, listings, import/export
- **Reviews**: Review sources, custom questions, review management
-->

## Key Decisions

> Document significant choices here. For detailed reasoning, use `docs/DECISIONS.md`.

<!--
Example:
- Using UUIDs for all public-facing routes
- Multi-tenancy via team_id scoping, not separate databases
-->

## Deviations from Kit Rules

> If this project intentionally differs from `.claude/rules/`, document it here so Claude follows the project's conventions instead.

<!--
Example:
- Uses Service classes instead of Actions for legacy reasons — do NOT refactor to Actions
- Models live in app/Models/ (type-based), not domain directories — intentional for this project
-->

## External Integrations

> List APIs, webhooks, and external services.

<!--
Example:
- AutoScout24 API — vehicle listing sync
- Stripe — payment processing
-->

## Environment Notes

> Any setup or deployment specifics.

<!--
Example:
- Requires Redis for queue worker
- Uses S3 for media storage in production
-->
