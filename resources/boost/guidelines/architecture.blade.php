# Architecture

- Organise code by domain, not by type: `app/Billing/`, `app/Catalog/`. Shared code goes to `app/Support/`.
@switch(config('kit.architecture', 'actions'))
@case('services')
- This project keeps its Service classes. Follow the existing service pattern; do not convert services to Actions unless the user asks.
- Keep business logic in the services, not in controllers, models or Form Requests.
@break
@case('migrating')
- New code goes into Actions; existing Service classes are converted when you change them anyway.
- An Action lives in `app/{Domain}/Actions/`, is named as a verb (`CreateInvoice`) and has one public method `handle()`. Do not convert code you do not touch.
@break
@default
- Business logic lives in Actions. An Action lives in `app/{Domain}/Actions/`, is named as a verb (`CreateInvoice`) and has one public method `handle()`.
- Actions may call other Actions. No business logic in controllers, models or Form Requests.
@endswitch
- Controllers are thin and singular (`InvoiceController`): validate with a Form Request, call the business logic, return the response.
- Pass more than 3 parameters as a DTO: a `final readonly class` in `app/{Domain}/DTOs/`, named as a noun (`InvoiceData`).
- Authorise with Policies, found by auto-discovery or linked with `#[UsePolicy]`.
- Use enums in `app/{Domain}/Enums/` for statuses and fixed value sets.
@if (version_compare(app()->version(), '13.0', '>='))
- Models declare their setup with attributes (`#[Table]`, `#[Fillable]`, `#[UsePolicy]`, `#[ObservedBy]`, `#[ScopedBy]`, `#[UseFactory]`) and casts in `casts()`. Never make a model unguarded.
@else
- Models list `$fillable` explicitly and define casts in `casts()`. Never make a model unguarded.
@endif
- Models hold relationships, scopes, accessors and casts, no business logic.
