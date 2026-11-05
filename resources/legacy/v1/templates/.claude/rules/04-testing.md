# Testing Conventions

## Framework
- Use PEST exclusively. No PHPUnit test classes.
- Run tests with `php artisan test` or `./vendor/bin/pest`.

## Structure
- Feature tests: `tests/Feature/{Domain}/` — test full request cycles (HTTP tests).
- Unit tests: `tests/Unit/{Domain}/` — test Actions, DTOs, pure logic in isolation.
- Mirror the domain structure from `app/` in `tests/`.

## Naming
- Test files: `{Subject}Test.php` (`CreateVehicleTest.php`, `VehiclesControllerTest.php`).
- Descriptions: readable sentences (`it('creates a vehicle with valid data')`).
- Use `describe()` blocks to group related tests by Action or endpoint.

## Feature Tests
- Test the full HTTP cycle: request → validation → action → response/redirect.
- Assert status codes, redirects, database state, and flash messages.
- Test both happy path and validation errors.
- Use `actingAs()` for authenticated routes.
- Always test authorization (can/cannot access).

```php
describe('POST /vehicles', function () {
    it('creates a vehicle with valid data', function () {
        $user = User::factory()->create();

        actingAs($user)
            ->post('/vehicles', [
                'make' => 'BMW',
                'model' => 'R1250GS',
            ])
            ->assertRedirect('/vehicles');

        expect(Vehicle::count())->toBe(1);
    });

    it('rejects invalid data', function () {
        actingAs(User::factory()->create())
            ->post('/vehicles', [])
            ->assertSessionHasErrors(['make', 'model']);
    });
});
```

## Unit Tests
- Test Actions with direct `execute()` calls.
- Mock external dependencies (APIs, file system). Never mock Eloquent.
- Use Factories for model instances.

## Factories & Seeders
- Every model has a Factory. Keep factories minimal but valid.
- Use states for variants: `Vehicle::factory()->listed()->create()`.
- Seeders are for development data, not test data. Tests use factories only.

## Data
- Use `RefreshDatabase` trait (PEST uses it via `uses()` in `Pest.php`).
- Never rely on seeded data in tests. Each test creates its own state.
- Use `assertDatabaseHas()` and `assertDatabaseMissing()` for persistence checks.

## Coverage
- Every Action gets at least one unit test.
- Every endpoint gets at least happy-path + validation-error feature tests.
- Test edge cases: empty states, boundary values, unauthorized access.
