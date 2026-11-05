# Laravel Kit

A spec-driven development (SDD) kit for Laravel projects that use [Laravel Boost](https://github.com/laravel/boost).

The kit adds an opinionated structure (domains and actions), a documentation workflow and agent skills on top of Boost. It plugs into Boost as a third-party package, so its guidelines and skills are composed together with Boost's own. The result is one consistent set of rules in `CLAUDE.md`, `AGENTS.md` and every other agent Boost supports, and `boost:update` no longer overwrites or contradicts it.

> **Status:** v2 is in development. Nothing here is ready for use yet.

## Planned usage

```bash
composer require --dev towebgmbh/laravel-kit
php artisan kit:install
```

| Command | Purpose |
|---|---|
| `kit:install` | Set up the kit in a new or existing project |
| `kit:update` | Refresh the kit's managed files after `composer update` |
| `kit:upgrade` | Migrate a project from the v1 kit (`.claude/rules`) |
| `kit:doctor` | Check the setup and warn about drift against Boost |

## Requirements

- Laravel 12 or 13
- Laravel Boost 2.10 or newer

## Legacy

The original v1 kit, an install script that copied rules into `.claude/rules`, is kept in [`resources/legacy/v1`](resources/legacy/v1). `kit:upgrade` uses it to recognise unchanged v1 files in existing projects.

## License

MIT, see [LICENSE](LICENSE).
