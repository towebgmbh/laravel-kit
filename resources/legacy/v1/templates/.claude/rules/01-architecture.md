# Architecture Rules

## Actions Pattern
- Use single-purpose Action classes instead of Service classes.
- Each Action has exactly one public method: `execute()`.
- Place Actions in domain directories: `app/{Domain}/Actions/`.
- Name Actions as verbs: `CreateInvoice`, `SyncVehicleListing`, `SendWelcomeEmail`.
- Actions may call other Actions. Prefer composition over inheritance.
- Never put business logic in Controllers, Models, or Form Requests.

## Domain Organization
- Organize code by domain, not by type: `app/Billing/`, `app/Vehicles/`, not `app/Actions/`, `app/Services/`.
- Each domain directory may contain: `Actions/`, `Models/`, `DTOs/`, `Enums/`, `Events/`, `Queries/`, `Rules/`, `Policies/`, `Notifications/`, `Jobs/`.
- Shared infrastructure goes in `app/Support/` or `app/Common/`.
- Domain boundaries are soft — cross-domain calls via Actions are acceptable. No strict DDD overhead.

## Data Flow
- Controllers receive requests, validate via Form Requests, call Actions, return responses.
- Controllers stay thin: validate → execute → respond. No logic.
- Return types are always explicit on Actions and public methods.

## DTOs (Data Transfer Objects)
- Use DTOs for structured data passing between layers when more than 3 parameters are involved.
- Place DTOs in `app/{Domain}/DTOs/`.
- Name DTOs as nouns describing the data: `VehicleData`, `ReviewImportPayload`.
- DTOs are immutable: use `readonly` properties and constructor promotion.
- Use `static` factory methods for creation from requests or arrays: `VehicleData::fromRequest(StoreVehicleRequest $request)`.
- Simple cases (1-3 typed parameters) can be passed directly — don't over-DTO.

```php
final readonly class VehicleData
{
    public function __construct(
        public string $make,
        public string $model,
        public int $year,
        public ?string $vin = null,
    ) {}

    public static function fromRequest(StoreVehicleRequest $request): self
    {
        return new self(...$request->validated());
    }
}
```

## Models
- Models contain relationships, scopes, accessors/mutators, and casts. No business logic.
- Always define `$fillable` explicitly. Never use `$guarded = []`.
- Use Enums for status fields and fixed value sets (PHP 8.1+ backed enums).
- Prefer query scopes over repeating where-clauses.

## General
- Favor explicit over implicit. No magic strings, no untyped arrays for structured data.
- Use strict types: `declare(strict_types=1);` in every PHP file.
- No God-classes. If a class exceeds ~200 lines, it likely does too much.
