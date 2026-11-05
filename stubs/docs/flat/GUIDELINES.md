# Project Guidelines

> The project's context: stack, domains, architecture and the rules that go beyond the kit. Keep it in sync with the code; updating it is part of the definition of done. Run `/kit-onboard` to fill it in.

## Project Overview

**Name**: {{PROJECT_NAME}}
**Goal**: <!-- One sentence: what the application does and for whom. -->
**Users and roles**: <!-- For example: customers, staff, administrators. -->

## Stack

| Layer    | Technology                       |
|----------|----------------------------------|
| Backend  | Laravel, PHP                     |
| Frontend | {{STACK}}                        |
| Testing  | <!-- Pest or PHPUnit -->         |
| CI       | <!-- for example GitHub Actions --> |
| Hosting  | <!-- -->                         |

Key packages and their purpose:

<!--
- **vendor/package**: what the project uses it for
-->

## Domain Map

> One line per domain under `app/<Domain>/`. These domain folders are approved base folders.

<!--
Example:
- **Billing**: invoices, payments, credit notes
- **Catalog**: products, categories, prices
-->

## Architecture

**Mode**: {{ARCHITECTURE}} (set in `config/kit.php`, key `architecture`)

<!--
- actions: business logic lives in Actions under app/<Domain>/Actions/.
- services: the existing service classes stay the pattern.
- migrating: new code goes into Actions; existing services are converted when they are touched.
-->

## Deviations

> Where this project intentionally differs from the kit, the rule lives in `.ai/rules` (recorded with Boost's `record-rule` tool). List them here in one line each so readers know they exist.

<!--
Example:
- Models stay in app/Models/ instead of the domain folders (see .ai/rules).
-->

## External Integrations

<!--
Example:
- Payment provider: card payments, webhooks under /webhooks/payments
- Mail delivery service: transactional mail
-->

## Environment Notes

<!--
Example:
- Requires Redis for the queue worker.
- Media is stored on S3 in production.
-->
