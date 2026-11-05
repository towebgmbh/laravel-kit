# Frontend Conventions — Livewire

## Livewire Components
- One Livewire component per page or per distinct interactive unit.
- Components live in `app/{Domain}/Livewire/` or `app/Livewire/{Domain}/`.
- Views live in `resources/views/livewire/{domain}/`.
- Keep component classes thin: validate, call Actions, set state. No business logic.
- Name components descriptively: `VehicleListingTable`, `ReviewFormWizard`.

## Component Architecture
- Use Livewire for interactive islands within Blade layouts.
- Full-page components use layout: `#[Layout('layouts.app')]`.
- Nest components sparingly. Prefer events (`$dispatch`, `$on`) for cross-component communication.
- Use `wire:model` for two-way binding. Use `wire:model.live` only when instant reactivity is needed.

## Actions & State
- Keep `$rules` and validation in the Livewire component (acts as both controller and form request).
- Call Actions from Livewire methods — don't put business logic in the component.
- Use lifecycle hooks (`mount`, `updated`, `dehydrate`) intentionally, not as catch-alls.
- Computed properties (`#[Computed]`) for derived state. Cache expensive computations.

## Blade Integration
- Livewire components embed in Blade layouts via `<livewire:component-name />` or `@livewire`.
- Use `@persist` for elements that should survive re-renders (media players, maps).
- Use `wire:loading` and `wire:target` for loading states.

## Tailwind CSS
- Use Tailwind utility classes directly. No custom CSS unless absolutely necessary.
- Extract repeated patterns into Blade components, not `@apply` directives.
- Use consistent spacing scale (p-4, p-6, p-8 — not arbitrary values).
- Responsive: mobile-first with `sm:`, `md:`, `lg:` breakpoints.

## Forms & Validation
- Use Livewire's built-in validation with `#[Validate]` attributes or `$rules` property.
- Show errors with `@error('field')` in the component's Blade view.
- Use `$this->reset()` and `$this->resetValidation()` after successful submissions.
- File uploads via `WithFileUploads` trait and `wire:model`.

## JavaScript Interop
- Use Alpine.js alongside Livewire for client-side-only interactions (dropdowns, modals).
- Access Livewire state from Alpine with `$wire`.
- Use `@script` / `@endscript` for component-scoped JavaScript.
- Dispatch browser events from Livewire with `$this->dispatch('event-name')`.
