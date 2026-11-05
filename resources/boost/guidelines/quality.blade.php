# Quality

## Definition of Done

- Tests follow the test line and the affected tests pass.
- Pint is clean on the changed files.
- The docs are updated as the size rule requires.
- Review your own diff before you present it: structure, naming, leftovers, scope.
- Migrations are reversible.

## Code

- No `TODO`, `FIXME` or commented-out code, no `dd()`, `dump()`, `ray()` or `console.log()` in committed code.
- No hard-coded values that belong in config, enums or constants.
- Domain errors get their own exceptions (`InvoiceAlreadyPaid`). Never swallow an exception silently; log or rethrow it.

## Security

- Validate every input with a Form Request and authorise every action with a Policy or Gate.
- Never commit `.env` files, credentials, tokens or API keys, and never paste them into docs or specs.

## Commits

- One logical change per commit, with a short imperative message. Do not mix refactoring with features.
