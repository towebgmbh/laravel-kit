# Test Enforcement

Follow the Laravel Kit test line:

- A test must find an error fast or rule out follow-up errors.
- Nothing twice: what a feature test covers gets no unit test. The exception is branching logic in an Action.
- Write feature tests for endpoints and flows, unit tests for framework-free logic.
- Always test permissions, state transitions, money and quantity calculations, and integrations with `Http::fake()`.
- Do not test framework behaviour, getters or HTML.
- Use datasets (data providers in PHPUnit) instead of copied tests.
- Mock only external services.
- When tests apply, run the affected tests and ensure they pass.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When a spec lists acceptance criteria, name the criterion a test covers (for example `AC-2`) in the test description.
- Read the `testing-best-practices` skill before writing tests.
