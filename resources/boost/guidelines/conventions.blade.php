# Conventions

- Within a domain, classes go to their own folder: `app/{Domain}/Models/`, `DTOs/`, `Enums/`, `Policies/`, `Jobs/`, `Events/`, `Listeners/`, `Notifications/`, `Mail/`, `Resources/`, `Queries/`, `Rules/`. Create a folder only when the first class needs it.
- Names: models singular (`Invoice`), controllers singular plus `Controller` (`InvoiceController`), Form Requests verb plus noun plus `Request` (`StoreInvoiceRequest`), Policies model plus `Policy`, API Resources model plus `Resource`, Jobs and Notifications describe what happens (`SendInvoiceReminder`, `InvoicePaid`).
- Routes: group them by domain with prefix and middleware; URIs are plural kebab-case (`/credit-notes`). Web routes return views or redirects, API routes return Resources.
- Migrations: one change per migration, descriptive snake_case names (`create_invoices_table`, `add_status_to_invoices_table`).
- Jobs and Listeners stay thin and call an Action or service for the business logic.
- Side effects (mails, syncs, logging) go through Events and Listeners; Observers only for simple model lifecycle hooks.
- Mail templates live in `resources/views/mail/{domain}/`. Config per domain goes to `config/{domain}.php`.
