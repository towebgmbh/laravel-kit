# Laravel Conventions

## Naming
- Models: singular PascalCase (`Vehicle`, `ReviewSource`).
- Controllers: plural resource name + Controller (`VehiclesController`, `ReviewSourcesController`).
- Actions: verb + noun PascalCase (`CreateVehicle`, `ImportReviews`).
- Form Requests: verb + noun + Request (`StoreVehicleRequest`, `UpdateListingRequest`).
- Policies: singular model name + Policy (`VehiclePolicy`, `ReviewPolicy`).
- Jobs: verb + noun + Job or descriptive verb (`SyncVehicleListing`, `ProcessReviewImport`).
- Notifications: verb + noun (`ReviewSubmitted`, `ListingExpiring`).
- Migrations: descriptive snake_case (`create_vehicles_table`, `add_status_to_listings_table`).
- Routes: plural kebab-case resources (`/review-sources`, `/vehicle-listings`).
- API Resources: singular model name + Resource (`VehicleResource`, `ReviewResource`).

## Routing
- Use `Route::resource()` and `Route::apiResource()` where applicable.
- Group routes by domain with prefixes and middleware.
- API routes live in `routes/api.php` with versioned prefixes when needed.
- Web routes return views or redirects. API routes return JSON via Resources.

## Migrations & Database
- Each migration does one thing. No multi-table migrations.
- Always add foreign key constraints with `constrained()->cascadeOnDelete()` or explicit on-delete behavior.
- Use `uuid` or `ulid` for public-facing IDs when appropriate. Auto-increment for internal PKs is fine.
- Index columns used in where-clauses, foreign keys, and sorting.

## Eloquent
- Use `::query()->` instead of static calls for better IDE support and readability.
- Eager load relationships to prevent N+1. Use `$with` sparingly — prefer explicit `::with()`.
- Prefer `firstOrFail()` and `findOrFail()` over manual null checks + 404.
- Use `whenLoaded()` in Resources to safely handle optional relationships.

## Form Requests
- All validation lives in Form Requests, never in Controllers or Actions.
- Use `authorize()` for policy-based authorization.
- Use `prepareForValidation()` for input normalization (trimming, casting).

## Policies & Authorization
- Every model that has user-facing CRUD gets a Policy: `app/{Domain}/Policies/{Model}Policy.php`.
- Register Policies in `AuthServiceProvider` or use auto-discovery.
- Use `$this->authorize('action', $model)` in controllers or `Gate::authorize()`.
- Policy methods match controller actions: `viewAny`, `view`, `create`, `update`, `delete`.
- For non-model authorization, use Gates defined in `AuthServiceProvider`.
- Always test both authorized and unauthorized access in feature tests.

## Jobs & Queues
- Place Jobs in `app/{Domain}/Jobs/`.
- Jobs encapsulate async work: API syncs, heavy processing, email sending.
- Keep Jobs thin — call an Action inside the `handle()` method for business logic.
- Implement `ShouldQueue` for async execution. Use `dispatchSync()` only when synchronous execution is required.
- Define `$tries`, `$backoff`, and `$timeout` on every queued Job.
- Use `failed()` method for error handling and notification.
- Use specific queue names for different priorities: `high`, `default`, `low`.

```php
final class SyncVehicleListing implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Vehicle $vehicle,
    ) {}

    public function handle(SyncVehicleToScout $action): void
    {
        $action->execute($this->vehicle);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Vehicle sync failed: {$this->vehicle->id}", ['error' => $e->getMessage()]);
    }
}
```

## API Resources
- Use API Resources for all JSON responses: `app/{Domain}/Resources/{Model}Resource.php`.
- Never return raw models or arrays from API endpoints.
- Use `whenLoaded()` for optional relationships to avoid N+1 in resource collections.
- Use Resource Collections when you need pagination metadata or collection-level wrapping.
- Keep Resources focused — if an endpoint needs a different shape, create a separate Resource.

## Notifications & Mail
- Place Notifications in `app/{Domain}/Notifications/`.
- Place Mailables in `app/{Domain}/Mail/` (when not using Notifications).
- Prefer Notifications over raw Mail for user-facing messages (supports multiple channels).
- Queue notifications: implement `ShouldQueue` on the Notification class.
- Use Markdown mail for consistent, maintainable email templates.
- Store email templates in `resources/views/mail/{domain}/`.

## Events & Observers
- Use Events + Listeners for side effects (emails, logging, syncing).
- Observers are acceptable for simple model lifecycle hooks.
- Never put complex logic in Observers — dispatch an Event and handle it in a Listener or Action.
- Place Events in `app/{Domain}/Events/`, Listeners in `app/{Domain}/Listeners/`.

## Config & Environment
- Never access `env()` outside of config files. Always use `config()`.
- Create custom config files per domain when needed (`config/vehicles.php`).
